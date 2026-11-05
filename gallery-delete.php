<?php
require_once __DIR__ . '/includes/app.php';
art_require_login();
$id = art_id($_GET['id'] ?? null);
if (!$id) art_error(400, 'Choose a gallery', 'Use the Delete link beside a gallery.');
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    art_error(405, 'Method not allowed', 'Use the deletion confirmation form.');
}
$db = art_db();
$deleting = $_SERVER['REQUEST_METHOD'] === 'POST';
$error = '';
if ($deleting) {
    art_csrf();
    $db->begin_transaction();
}
try {
    $stmt = $db->prepare('SELECT GalleryName FROM Galleries WHERE GalleryID = ?' . ($deleting ? ' FOR UPDATE' : ''));
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $gallery = $stmt->get_result()->fetch_assoc();
    if ($gallery) {
        $stmt = $db->prepare('SELECT PaintingID FROM Paintings WHERE GalleryID = ? LIMIT 1' . ($deleting ? ' FOR UPDATE' : ''));
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $error = 'This gallery cannot be deleted because paintings still reference it.';
            if ($deleting) http_response_code(409);
        }
    }
    if ($deleting) {
        if ($gallery && !$error) {
            $stmt = $db->prepare('DELETE FROM Galleries WHERE GalleryID = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
        }
        $db->commit();
        if (!$error) {
            $_SESSION['flash'] = $gallery ? 'Gallery deleted.' : 'That gallery has already been removed.';
            header('Location: galleries.php', true, 303);
            exit;
        }
    }
} catch (mysqli_sql_exception $exception) {
    if ($deleting) $db->rollback();
    if ($exception->getCode() !== 1451 || !isset($gallery) || !$gallery) throw $exception;
    $error = 'This gallery cannot be deleted because paintings still reference it.';
    http_response_code(409);
} catch (Throwable $exception) {
    if ($deleting) $db->rollback();
    throw $exception;
}
if (!$gallery) art_error(404, 'Gallery not found', 'This gallery may have been removed.');
art_header('Delete gallery');
?>
<p><strong><?= art_h($gallery['GalleryName']) ?></strong></p>
<?php if ($error): ?>
    <p class="error" role="alert"><?= art_h($error) ?></p>
    <p><a href="gallery-detail.php?id=<?= $id ?>">View this gallery’s paintings</a> | <a href="galleries.php">Back to Galleries</a></p>
<?php else: ?>
    <p>Delete this gallery?</p>
    <form method="post" action="gallery-delete.php?id=<?= $id ?>">
        <input type="hidden" name="csrf" value="<?= art_h(art_token()) ?>">
        <button type="submit">Delete gallery</button> <a href="galleries.php">Keep gallery</a>
    </form>
<?php endif; ?>
<?php art_footer(); ?>
