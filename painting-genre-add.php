<?php
require_once __DIR__ . '/includes/app.php';
art_require_login();
$name = '';
$description = '';
$errors = [];
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    art_error(405, 'Method not allowed', 'Use the add genre form.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    art_csrf();
    $name = art_post('name');
    $description = art_post('description');
    if ($name === '' || mb_strlen($name) > 50) $errors[] = 'Enter a genre name of 1–50 characters.';
    if (mb_strlen($description) > 10000) $errors[] = 'Use no more than 10,000 characters for the description.';
    if (!$errors) {
        try {
            $stmt = art_db()->prepare('INSERT INTO Genres (GenreName, Description) VALUES (?, ?)');
            $stmt->bind_param('ss', $name, $description);
            $stmt->execute();
            $_SESSION['flash'] = 'Genre added.';
            header('Location: painting-genres.php', true, 303);
            exit;
        } catch (mysqli_sql_exception $exception) {
            if ($exception->getCode() !== 1062) throw $exception;
            $errors[] = 'A genre with this name already exists. Choose another name.';
        }
    }
    http_response_code(422);
}
art_header('Add genre');
?>
<?php if ($errors): ?><div class="error" role="alert"><ul>
    <?php foreach ($errors as $error): ?><li><?= art_h($error) ?></li><?php endforeach; ?>
</ul></div><?php endif; ?>
<form method="post" action="painting-genre-add.php">
    <input type="hidden" name="csrf" value="<?= art_h(art_token()) ?>">
    <p><label>Genre name: <input name="name" maxlength="50" required value="<?= art_h($name) ?>"></label></p>
    <p><label>Description (optional):<br><textarea name="description" rows="6" cols="60" maxlength="10000"><?= art_h($description) ?></textarea></label></p>
    <button type="submit">ADD genre</button> <a href="painting-genres.php">Cancel</a>
</form>
<?php art_footer(); ?>
