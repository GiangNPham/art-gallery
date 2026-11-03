<?php
require_once __DIR__ . '/includes/app.php';
art_require_login();
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    art_error(405, 'Method not allowed', 'Choose a genre from the Genres page.');
}
$id = art_id($_GET['id'] ?? null);
if (!$id) art_error(400, 'Choose a genre', 'Choose a genre from the Genres page.');
$stmt = art_db()->prepare('SELECT GenreID, GenreName, Description, Link FROM Genres WHERE GenreID = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$genre = $stmt->get_result()->fetch_assoc();
if (!$genre) art_error(404, 'Genre not found', 'This genre may have been removed.');
$stmt = art_db()->prepare('SELECT DISTINCT p.PaintingID, p.Title FROM PaintingGenres pg
    INNER JOIN Paintings p ON p.PaintingID = pg.PaintingID WHERE pg.GenreID = ? ORDER BY p.Title');
$stmt->bind_param('i', $id);
$stmt->execute();
$paintings = $stmt->get_result();
$link = $genre['Link'] ?? '';
$safeLink = filter_var($link, FILTER_VALIDATE_URL)
    && in_array(strtolower(parse_url($link, PHP_URL_SCHEME) ?: ''), ['http', 'https'], true);
art_header('Genre details');
?>
<dl>
    <dt>Genre ID</dt><dd><?= (int) $genre['GenreID'] ?></dd>
    <dt>Name</dt><dd><?= art_h($genre['GenreName']) ?></dd>
    <dt>Description</dt><dd><?= nl2br(art_h(art_text($genre['Description'] ?? ''))) ?: 'No description provided.' ?></dd>
    <dt>Reference link</dt><dd><?php if ($safeLink): ?><a href="<?= art_h($link) ?>"><?= art_h($link) ?></a><?php else: ?>No reference link available.<?php endif; ?></dd>
</dl>
<h2>Paintings (<?= (int) $paintings->num_rows ?>)</h2>
<?php if ($paintings->num_rows): ?>
<ul>
<?php foreach ($paintings as $painting): ?>
    <li><a href="art-detail.php?id=<?= (int) $painting['PaintingID'] ?>"><?= art_h($painting['Title']) ?></a></li>
<?php endforeach; ?>
</ul>
<?php else: ?><p>No paintings are linked to this genre.</p><?php endif; ?>
<p><a href="genre-delete.php?id=<?= $id ?>">Delete genre</a> | <a href="genres.php">Back to Genres</a></p>
<?php art_footer(); ?>
