<?php
require_once __DIR__ . '/includes/app.php';
art_require_login();

$name = isset($_GET['name']) && is_string($_GET['name']) ? trim($_GET['name']) : '';
if ($name === '' || mb_strlen($name) > 255) art_error(400, 'Choose an era', 'Use the Delete link beside an era.');
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    art_error(405, 'Method not allowed', 'Use the deletion confirmation form.');
}

$db = art_db();
$deleting = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($deleting) {
    art_csrf();
    $db->begin_transaction();
}
try {
    $stmt = $db->prepare('SELECT EraName, EraYears FROM Eras WHERE EraName = ?' . ($deleting ? ' FOR UPDATE' : ''));
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $era = $stmt->get_result()->fetch_assoc();
    if ($deleting) {
        if ($era) {
            $stmt = $db->prepare('DELETE FROM Eras WHERE EraName = ?');
            $stmt->bind_param('s', $name);
            $stmt->execute();
        }
        $db->commit();
        $_SESSION['flash'] = $era ? 'Era deleted.' : 'That era has already been removed.';
        header('Location: eras.php', true, 303);
        exit;
    }
} catch (Throwable $exception) {
    if ($deleting) $db->rollback();
    throw $exception;
}
if (!$era) art_error(404, 'Era not found', 'This era may have been removed.');

art_header('Delete era');
?>
<p><strong><?= art_h($era['EraName']) ?></strong> (<?= art_h($era['EraYears']) ?>)</p>
<p>Delete this era?</p>
<form method="post" action="era-delete.php?name=<?= rawurlencode($name) ?>">
    <input type="hidden" name="csrf" value="<?= art_h(art_token()) ?>">
    <button type="submit">Delete era</button> <a href="eras.php">Keep era</a>
</form>
<?php art_footer(); ?>
