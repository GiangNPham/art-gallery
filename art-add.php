<?php
require_once __DIR__ . '/includes/app.php';
art_require_login();
$db = art_db();
$values = array_fill_keys(['title', 'artist', 'year'], '');
$errors = [];
$upload = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    art_csrf();
    foreach ($values as $field => $_) $values[$field] = art_post($field);
    if ($values['title'] === '' || mb_strlen($values['title']) > 255) $errors[] = 'Enter a title of 1–255 characters.';
    if ($values['artist'] === '' || mb_strlen($values['artist']) > 100) {
        $errors[] = 'Enter an artist name of 1–100 characters.';
    } else {
        $artistParts = preg_split('/\s+/', $values['artist']);
        $artistLastName = array_pop($artistParts);
        $artistFirstName = $artistParts ? implode(' ', $artistParts) : null;
        if (mb_strlen($artistLastName) > 50 || mb_strlen((string) $artistFirstName) > 50) {
            $errors[] = 'Use no more than 50 characters for the first name and 50 for the last name.';
        }
    }
    if ($values['year'] !== '' && !preg_match('/\A[1-9][0-9]{0,3}\z/', $values['year'])) $errors[] = 'Year must be between 1 and 9999, or left blank.';

    $file = $_FILES['image'] ?? null;
    if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Choose an image to upload.';
    } elseif (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $errors[] = 'The image upload did not finish. Please try again.';
    } elseif (($file['size'] ?? 0) < 1 || $file['size'] > 5 * 1024 * 1024) {
        $errors[] = 'The image must be no larger than 5 MB.';
    } elseif (!is_uploaded_file($file['tmp_name'])) {
        $errors[] = 'The uploaded image is invalid.';
    } else {
        $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $size = @getimagesize($file['tmp_name']);
        if (!$size || !isset($types[$mime])) $errors[] = 'Upload a JPEG, PNG, GIF, or WebP image.';
        else $upload = ['temporary' => $file['tmp_name'], 'extension' => $types[$mime]];
    }

    if (!$errors && $upload) {
        $imageStem = 'added-' . bin2hex(random_bytes(12));
        $imagePath = __DIR__ . '/images/art/works/square-medium/' . $imageStem . '.' . $upload['extension'];
        $db->begin_transaction();
        try {
            $stmt = $db->prepare("SELECT ArtistID FROM Artists
                WHERE LOWER(TRIM(CONCAT_WS(' ', FirstName, LastName))) = LOWER(?) LIMIT 1");
            $stmt->bind_param('s', $values['artist']);
            $stmt->execute();
            $artistId = $stmt->get_result()->fetch_column();
            if (!$artistId) {
                $stmt = $db->prepare('INSERT INTO Artists (FirstName, LastName) VALUES (?, ?)');
                $stmt->bind_param('ss', $artistFirstName, $artistLastName);
                $stmt->execute();
                $artistId = $db->insert_id;
            }
            if (!move_uploaded_file($upload['temporary'], $imagePath)) {
                throw new RuntimeException('The image could not be saved. Give PHP write access to the image folder.');
            }
            $year = $values['year'] === '' ? null : (int) $values['year'];
            $stmt = $db->prepare('INSERT INTO Paintings (Title, ArtistID, YearOfWork, ImageFileName) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('siis', $values['title'], $artistId, $year, $imageStem);
            $stmt->execute();
            $db->commit();
            $_SESSION['flash'] = 'Artwork added to the collection.';
            header('Location: art-main.php', true, 303);
            exit;
        } catch (Throwable $error) {
            $db->rollback();
            if (is_file($imagePath)) unlink($imagePath);
            $errors[] = $error instanceof RuntimeException ? $error->getMessage() : 'The artwork could not be saved.';
            http_response_code(500);
        }
    } elseif ($errors) http_response_code(422);
}
art_header('Add artwork');
?>
<?php if ($errors): ?><div class="error" role="alert">
        <p>Please check the form</p>
        <ul><?php foreach ($errors as $error): ?><li><?= art_h($error) ?></li><?php endforeach; ?></ul>
    </div><?php endif; ?>
<form method="post" action="art-add.php" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= art_h(art_token()) ?>">
    <p><label>Title: <input name="title" value="<?= art_h($values['title']) ?>" maxlength="255" required></label></p>
    <p><label>Artist name: <input name="artist" value="<?= art_h($values['artist']) ?>" maxlength="100" placeholder="Example: Vincent van Gogh" required></label></p>
    <p><label>Year (optional): <input name="year" type="number" min="1" max="9999" value="<?= art_h($values['year']) ?>"></label></p>
    <p><label>Artwork image: <input name="image" type="file" accept="image/jpeg,image/png,image/gif,image/webp" required></label></p>
    <p class="help">JPEG, PNG, GIF, or WebP; maximum 5 MB.</p>
    <button type="submit">ADD</button> <a href="art-main.php">Cancel</a>
</form>
<?php art_footer(); ?>