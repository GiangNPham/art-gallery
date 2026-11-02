<?php
require_once __DIR__ . '/includes/app.php';
art_require_login();
$id = art_id($_GET['id'] ?? null);
if (!$id) art_error(400, 'Choose an artist', 'Use the Delete link beside an artist on the Artists page.');
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    art_error(405, 'Method not allowed', 'Use the deletion confirmation form.');
}
$db = art_db();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') art_csrf();
$deleting = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($deleting) $db->begin_transaction();
try {
    // Artwork creation locks the same artist so it cannot race this deletion.
    $stmt = $db->prepare('SELECT FirstName, LastName FROM Artists WHERE ArtistID = ?' . ($deleting ? ' FOR UPDATE' : ''));
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $artist = $stmt->get_result()->fetch_assoc();
    $stmt = $db->prepare('SELECT PaintingID FROM Paintings WHERE ArtistID = ? LIMIT 1' . ($deleting ? ' FOR UPDATE' : ''));
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $hasPaintings = (bool) $stmt->get_result()->fetch_assoc();
    if ($artist && $hasPaintings) {
        $error = 'This artist cannot be deleted because they still have paintings in the database. Delete their paintings first.';
        if ($deleting) http_response_code(409);
    }
    if ($deleting) {
        if ($artist && !$hasPaintings) {
            $stmt = $db->prepare('DELETE FROM Artists WHERE ArtistID = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
        }
        $db->commit();
        if (!$error) {
            $_SESSION['flash'] = $artist ? 'Artist deleted.' : 'That artist has already been removed.';
            header('Location: artists.php', true, 303);
            exit;
        }
    }
} catch (Throwable $exception) {
    if ($deleting) $db->rollback();
    throw $exception;
}
if (!$artist) art_error(404, 'Artist not found', 'This artist may have been removed.');
art_header('Delete artist');
?>
<p><?= art_h(art_artist($artist)) ?></p>
<?php if ($error): ?>
    <p class="error" role="alert"><?= art_h($error) ?></p>
    <p><a href="art-main.php?id=<?= $id ?>">View this artist’s paintings</a> | <a href="artists.php">Back to Artists</a></p>
<?php else: ?>
    <p>Delete this artist?</p>
    <form method="post" action="artist-delete.php?id=<?= $id ?>">
        <input type="hidden" name="csrf" value="<?= art_h(art_token()) ?>">
        <button type="submit">Delete artist</button> <a href="artists.php">Keep artist</a>
    </form>
<?php endif; ?>
<?php art_footer(); ?>
