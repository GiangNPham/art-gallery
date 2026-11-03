<?php
require_once __DIR__ . '/includes/app.php';
art_require_login();
$id = art_id($_GET['id'] ?? null);
if (!$id) art_error(400, 'Choose a genre', 'Use the Delete link beside a genre on the Painting genres page.');
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    art_error(405, 'Method not allowed', 'Use the deletion confirmation form.');
}
$db = art_db();
$error = '';
$deleting = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($deleting) {
    art_csrf();
    $db->begin_transaction();
}
try {
    $stmt = $db->prepare('SELECT GenreName FROM Genres WHERE GenreID = ?' . ($deleting ? ' FOR UPDATE' : ''));
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $genre = $stmt->get_result()->fetch_assoc();
    // Lock the genre's associations before checking for paintings and deleting.
    $stmt = $db->prepare('SELECT PaintingID FROM PaintingGenres WHERE GenreID = ?' . ($deleting ? ' FOR UPDATE' : ''));
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt = $db->prepare('SELECT p.PaintingID FROM PaintingGenres pg
        INNER JOIN Paintings p ON p.PaintingID = pg.PaintingID
        WHERE pg.GenreID = ? LIMIT 1' . ($deleting ? ' FOR UPDATE' : ''));
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $hasPaintings = (bool) $stmt->get_result()->fetch_assoc();
    if ($genre && $hasPaintings) {
        $error = 'This genre cannot be deleted because it still has paintings in the database. Delete the related paintings first.';
        if ($deleting) http_response_code(409);
    }
    if ($deleting) {
        if ($genre && !$hasPaintings) {
            // Remove stale associations left by paintings deleted before this feature.
            $stmt = $db->prepare('DELETE FROM PaintingGenres WHERE GenreID = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt = $db->prepare('DELETE FROM Genres WHERE GenreID = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
        }
        $db->commit();
        if (!$error) {
            $_SESSION['flash'] = $genre ? 'Genre deleted.' : 'That genre has already been removed.';
            header('Location: painting-genres.php', true, 303);
            exit;
        }
    }
} catch (Throwable $exception) {
    if ($deleting) $db->rollback();
    throw $exception;
}
if (!$genre) art_error(404, 'Genre not found', 'This genre may have been removed.');
art_header('Delete genre');
?>
<p><?= art_h($genre['GenreName']) ?></p>
<?php if ($error): ?>
    <p class="error" role="alert"><?= art_h($error) ?></p>
    <p><a href="painting-genres.php">Back to Painting genres</a></p>
<?php else: ?>
    <p>Delete this genre?</p>
    <form method="post" action="painting-genre-delete.php?id=<?= $id ?>">
        <input type="hidden" name="csrf" value="<?= art_h(art_token()) ?>">
        <button type="submit">Delete genre</button> <a href="painting-genres.php">Keep genre</a>
    </form>
<?php endif; ?>
<?php art_footer(); ?>
