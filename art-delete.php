<?php
require_once __DIR__ . '/includes/app.php';
art_require_login();
art_session();
$id = art_id($_GET['id'] ?? null);
if (!$id) art_error(400, 'Choose an artwork', 'Use the Delete button beside an artwork in the collection.');
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    art_error(405, 'Method not allowed', 'Use the deletion confirmation form.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    art_csrf();
    $db = art_db();
    // Lock the record while deleting so repeated submissions cannot remove a different artwork.
    $db->begin_transaction();
    try {
        $stmt = $db->prepare('SELECT ImageFileName FROM Paintings WHERE PaintingID = ? FOR UPDATE');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $painting = $stmt->get_result()->fetch_assoc();
        if ($painting) {
            $stmt = $db->prepare('DELETE FROM PaintingGenres WHERE PaintingID = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt = $db->prepare('DELETE FROM Paintings WHERE PaintingID = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
        }
        $db->commit();
    } catch (Throwable $error) {
        $db->rollback();
        throw $error;
    }
    // Original Lab14 images remain available. Only uploads created by this app are removed.
    if ($painting && preg_match('/\Aadded-[a-f0-9]{24}\z/', $painting['ImageFileName'])) {
        foreach (['jpg', 'png', 'gif', 'webp'] as $extension) {
            $path = __DIR__ . '/images/art/works/square-medium/' . $painting['ImageFileName'] . '.' . $extension;
            if (is_file($path) && !unlink($path)) error_log('Could not remove gallery image: ' . basename($path));
        }
    }
    $_SESSION['flash'] = $painting ? 'Artwork deleted from the collection.' : 'That artwork has already been removed.';
    header('Location: art-main.php', true, 303);
    exit;
}
$painting = art_painting($id);
if (!$painting) art_error(404, 'Artwork not found', 'This artwork may have been removed from the collection.');
art_header('Delete artwork');
?>
<p>Delete this artwork?</p>
<p><img src="<?= art_h(art_image($painting['ImageFileName'])) ?>" alt="<?= art_h($painting['Title']) ?>" width="80" height="80">
    <?= art_h($painting['Title']) ?> by <?= art_h(art_artist($painting)) ?></p>
<form method="post" action="art-delete.php?id=<?= $id ?>"><input type="hidden" name="csrf" value="<?= art_h(art_token()) ?>">
    <button type="submit">Delete artwork</button> <a href="art-main.php">Keep artwork</a>
</form>
<?php art_footer(); ?>
