<?php
require_once __DIR__ . '/includes/app.php';
art_require_login();

$name = '';
$years = '';
$errors = [];
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    art_error(405, 'Method not allowed', 'Use the add era form.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    art_csrf();
    $name = art_post('name');
    $years = art_post('years');
    if ($name === '' || mb_strlen($name) > 255) $errors[] = 'Enter an era name of 1–255 characters.';
    if ($years === '' || mb_strlen($years) > 255) $errors[] = 'Enter era years of 1–255 characters.';
    if (!$errors) {
        $stmt = art_db()->prepare('SELECT EraName FROM Eras WHERE EraName = ?');
        $stmt->bind_param('s', $name);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $errors[] = 'An era with this name already exists. Choose another name.';
        } else {
            $stmt = art_db()->prepare('INSERT INTO Eras (EraName, EraYears) VALUES (?, ?)');
            $stmt->bind_param('ss', $name, $years);
            $stmt->execute();
            $_SESSION['flash'] = 'Era added.';
            header('Location: eras.php', true, 303);
            exit;
        }
    }
    http_response_code(422);
}
art_header('Add era');
?>
<?php if ($errors): ?><div class="error" role="alert"><ul>
    <?php foreach ($errors as $error): ?><li><?= art_h($error) ?></li><?php endforeach; ?>
</ul></div><?php endif; ?>
<form method="post" action="era-add.php">
    <input type="hidden" name="csrf" value="<?= art_h(art_token()) ?>">
    <p><label>Era name: <input name="name" maxlength="255" required value="<?= art_h($name) ?>"></label></p>
    <p><label>Era years: <input name="years" maxlength="255" required value="<?= art_h($years) ?>" placeholder="Example: 1400-1550"></label></p>
    <button type="submit">ADD era</button> <a href="eras.php">Cancel</a>
</form>
<?php art_footer(); ?>
