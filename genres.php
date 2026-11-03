<?php
require_once __DIR__ . '/includes/app.php';
art_require_login();
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    art_error(405, 'Method not allowed', 'Open Genres from the navigation link.');
}
$genres = art_db()->query('SELECT g.GenreID, g.GenreName, g.Description,
    COUNT(DISTINCT p.PaintingID) AS PaintingCount
    FROM Genres g LEFT JOIN PaintingGenres pg ON pg.GenreID = g.GenreID
    LEFT JOIN Paintings p ON p.PaintingID = pg.PaintingID
    GROUP BY g.GenreID, g.GenreName, g.Description ORDER BY g.GenreName');
$paintings = art_db()->query('SELECT PaintingID, ImageFileName, Title FROM Paintings ORDER BY Title, PaintingID');
$paintingGenres = [];
$assignments = art_db()->query('SELECT DISTINCT pg.PaintingID, g.GenreID, g.GenreName
    FROM PaintingGenres pg INNER JOIN Genres g ON g.GenreID = pg.GenreID
    ORDER BY g.GenreName, g.GenreID');
foreach ($assignments as $assignment) {
    $paintingGenres[(int) $assignment['PaintingID']][] = $assignment;
}
art_header('Genres');
?>
<h2>Paintings and their genres</h2>
<?php if ($paintings->num_rows): ?>
<table>
    <thead><tr><th scope="col">Image</th><th scope="col">Title</th><th scope="col">Genres</th></tr></thead>
    <tbody>
    <?php foreach ($paintings as $painting): ?>
    <tr data-painting-id="<?= (int) $painting['PaintingID'] ?>">
        <td>
            <a href="art-detail.php?id=<?= (int) $painting['PaintingID'] ?>"><img src="<?= art_h(art_image($painting['ImageFileName'])) ?>" alt="<?= art_h($painting['Title']) ?>" width="80" height="80" loading="lazy"></a>
        </td>
        <td>
            <a href="art-detail.php?id=<?= (int) $painting['PaintingID'] ?>"><?= art_h($painting['Title']) ?></a>
        </td>
        <td>
        <?php $assignedGenres = $paintingGenres[(int) $painting['PaintingID']] ?? []; ?>
        <?php if ($assignedGenres): ?>
        <ul>
            <?php foreach ($assignedGenres as $assignedGenre): ?>
            <li><a href="genre-detail.php?id=<?= (int) $assignedGenre['GenreID'] ?>"><?= art_h($assignedGenre['GenreName']) ?></a></li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?><p>No genre assigned.</p><?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?><p>No paintings found.</p><?php endif; ?>
<h2>Manage genres</h2>
<p><a href="genre-add.php">ADD genre</a></p>
<table>
    <thead><tr><th>Name</th><th>Description</th><th>Paintings</th><th>Delete</th></tr></thead>
    <tbody>
    <?php foreach ($genres as $genre): ?>
        <tr data-genre-id="<?= (int) $genre['GenreID'] ?>">
            <td><a href="genre-detail.php?id=<?= (int) $genre['GenreID'] ?>"><?= art_h($genre['GenreName']) ?></a></td>
            <td><?= nl2br(art_h(art_text($genre['Description'] ?? ''))) ?></td>
            <td><?= (int) $genre['PaintingCount'] ?></td>
            <td><a href="genre-delete.php?id=<?= (int) $genre['GenreID'] ?>">Delete</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php if (!$genres->num_rows): ?><p>No genres found.</p><?php endif; ?>
<?php art_footer(); ?>
