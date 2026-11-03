<?php
require_once __DIR__ . '/includes/app.php';
art_require_login();

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    art_error(405, 'Method not allowed', 'Use the update era form.');
}
$name = isset($_GET['name']) && is_string($_GET['name']) ? trim($_GET['name']) : '';
if ($name === '' || mb_strlen($name) > 255) art_error(400, 'Choose an era', 'Use the Update link beside an era.');

$db = art_db();
$stmt = $db->prepare('SELECT EraName, EraYears FROM Eras WHERE EraName = ?');
$stmt->bind_param('s', $name);
$stmt->execute();
$era = $stmt->get_result()->fetch_assoc();
if (!$era) art_error(404, 'Era not found', 'This era may have been removed.');

$years = $era['EraYears'] ?? '';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    art_csrf();
    $years = art_post('years');
    if ($years === '' || mb_strlen($years) > 255) $errors[] = 'Enter era years of 1–255 characters.';
    if (!$errors) {
        $stmt = $db->prepare('UPDATE Eras SET EraYears = ? WHERE EraName = ?');
        $stmt->bind_param('ss', $years, $name);
        $stmt->execute();
        $_SESSION['flash'] = 'Era updated.';
        header('Location: era-detail.php?name=' . rawurlencode($name), true, 303);
        exit;
    }
    http_response_code(422);
}
art_header('Update era');
?>
<?php if ($errors): ?><div class="error" role="alert"><ul>
    <?php foreach ($errors as $error): ?><li><?= art_h($error) ?></li><?php endforeach; ?>
</ul></div><?php endif; ?>
<form method="post" action="era-update.php?name=<?= rawurlencode($name) ?>">
    <input type="hidden" name="csrf" value="<?= art_h(art_token()) ?>">
    <p><strong>Era name:</strong> <?= art_h($name) ?></p>
    <p class="help">The era name identifies this record and cannot be changed.</p>
    <p><label>Era years: <input name="years" maxlength="255" required value="<?= art_h($years) ?>"></label></p>
    <button type="submit">Update era</button> <a href="era-detail.php?name=<?= rawurlencode($name) ?>">Cancel</a>
</form>
<?php art_footer(); ?>
