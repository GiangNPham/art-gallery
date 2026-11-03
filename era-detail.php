<?php
require_once __DIR__ . '/includes/app.php';
art_require_login();

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    art_error(405, 'Method not allowed', 'Choose an era from the Eras page.');
}

$name = isset($_GET['name']) && is_string($_GET['name']) ? trim($_GET['name']) : '';
if ($name === '' || mb_strlen($name) > 255) art_error(400, 'Choose an era', 'Choose an era from the Eras page.');

$stmt = art_db()->prepare('SELECT EraName, EraYears FROM Eras WHERE EraName = ?');
$stmt->bind_param('s', $name);
$stmt->execute();
$era = $stmt->get_result()->fetch_assoc();
if (!$era) art_error(404, 'Era not found', 'This era may have been removed.');

art_header('Era details');
?>
<dl>
    <dt>Name</dt><dd><?= art_h($era['EraName']) ?></dd>
    <dt>Years</dt><dd><?= art_h($era['EraYears']) ?></dd>
</dl>
<p><a href="era-update.php?name=<?= rawurlencode($era['EraName']) ?>">Update era</a> |
<a href="era-delete.php?name=<?= rawurlencode($era['EraName']) ?>">Delete era</a> |
<a href="eras.php">Back to Eras</a></p>
<?php art_footer(); ?>
