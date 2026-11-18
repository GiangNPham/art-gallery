<?php
require_once __DIR__ . '/includes/app.php';
art_require_admin();

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    art_error(405, 'Method not allowed', 'Use the update artwork form.');
}

$id = art_id($_GET['id'] ?? null);
if (!$id) art_error(400, 'Choose an artwork', 'Use the Update link beside an artwork.');

$painting = art_painting($id);
if (!$painting) art_error(404, 'Artwork not found', 'This artwork may have been removed from the collection.');

$values = [
    'title' => $painting['Title'],
    'year' => $painting['YearOfWork'] ?: '',
    'medium' => $painting['Medium'] ?? '',
    'description' => $painting['Description'] ?? '',
    'wiki_link' => $painting['WikiLink'] ?? '',
];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    art_csrf();
    foreach ($values as $field => $_) $values[$field] = art_post($field);
    if ($values['title'] === '' || mb_strlen($values['title']) > 255) $errors[] = 'Enter a title of 1–255 characters.';
    if (!art_valid_optional_year($_POST['year'] ?? '')) {
        $errors['year'] = 'Painting year must be a whole number from 1 to 9999, or left blank.';
    }
    if (mb_strlen($values['medium']) > 255) $errors[] = 'Use no more than 255 characters for the medium.';
    if (mb_strlen($values['description']) > 10000) $errors[] = 'Use no more than 10,000 characters for the description.';
    if (!is_string($_POST['wiki_link'] ?? '') || ($values['wiki_link'] !== '' && !art_http_link($values['wiki_link']))) {
        $errors['wiki_link'] = 'Enter a valid HTTP or HTTPS wiki link of no more than 255 characters, or leave it blank.';
    }

    if (!$errors) {
        $year = $values['year'] === '' ? null : (int) $values['year'];
        $stmt = art_db()->prepare('UPDATE Paintings SET Title = ?, YearOfWork = ?, Medium = ?, Description = ?, WikiLink = ? WHERE PaintingID = ?');
        $stmt->bind_param('sisssi', $values['title'], $year, $values['medium'], $values['description'], $values['wiki_link'], $id);
        $stmt->execute();
        if ($stmt->affected_rows === 0 && !art_painting($id)) {
            art_error(404, 'Artwork not found', 'This artwork may have been removed from the collection.');
        }
        $_SESSION['flash'] = 'Artwork updated.';
        header('Location: art-detail.php?id=' . $id, true, 303);
        exit;
    }
    http_response_code(422);
}
art_header('Update artwork');
?>
<?php if ($errors): ?><div class="error" role="alert"><ul>
    <?php foreach ($errors as $error): ?><li><?= art_h($error) ?></li><?php endforeach; ?>
</ul></div><?php endif; ?>
<form method="post" action="art-update.php?id=<?= $id ?>">
    <input type="hidden" name="csrf" value="<?= art_h(art_token()) ?>">
    <p><strong>Painting ID:</strong> <?= $id ?></p>
    <p><label>Title: <input name="title" maxlength="255" required value="<?= art_h($values['title']) ?>"></label></p>
    <p><label>Year (optional):
        <input name="year" inputmode="numeric" pattern="[1-9][0-9]{0,3}" placeholder="e.g. 1889" aria-describedby="year-help"
            <?= isset($errors['year']) ? 'aria-invalid="true"' : '' ?> value="<?= art_h($values['year']) ?>">
    </label><br><small class="help" id="year-help">Enter a whole year from 1 to 9999.</small></p>
    <p><label>Medium (optional): <input name="medium" maxlength="255" value="<?= art_h($values['medium']) ?>"></label></p>
    <p><label>Description (optional):<br><textarea name="description" rows="6" cols="60" maxlength="10000"><?= art_h($values['description']) ?></textarea></label></p>
    <p><label>Wiki link (optional):
        <input name="wiki_link" type="url" maxlength="255" pattern="[Hh][Tt][Tt][Pp][Ss]?://.+"
            placeholder="https://en.wikipedia.org/wiki/The_Starry_Night" size="45" aria-describedby="wiki-help"
            <?= isset($errors['wiki_link']) ? 'aria-invalid="true"' : '' ?> value="<?= art_h($values['wiki_link']) ?>">
    </label><br><small class="help" id="wiki-help">Start the link with https:// or http://.</small></p>
    <button type="submit">Update artwork</button> <a href="art-detail.php?id=<?= $id ?>">Cancel</a>
</form>
<?php art_footer(); ?>
