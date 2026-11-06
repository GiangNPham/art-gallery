<?php
require_once __DIR__ . '/includes/app.php';
art_require_login();
$genres = art_db()->query('SELECT g.GenreID, g.GenreName, g.Description,
    COUNT(DISTINCT p.PaintingID) AS PaintingCount
    FROM Genres g LEFT JOIN PaintingGenres pg ON pg.GenreID = g.GenreID
    LEFT JOIN Paintings p ON p.PaintingID = pg.PaintingID
    GROUP BY g.GenreID, g.GenreName, g.Description ORDER BY g.GenreName');
art_header('Genres');
?>
<p><a href="genre-add.php">ADD genre</a></p>
<table>
    <thead><tr><th>Name</th><th>Description</th><th>Paintings</th><th>Delete</th></tr></thead>
    <tbody>
    <?php foreach ($genres as $genre): ?>
        <tr data-genre-id="<?= (int) $genre['GenreID'] ?>">
            <td><?= art_h($genre['GenreName']) ?></td>
            <td><?= nl2br(art_h(art_text($genre['Description'] ?? ''))) ?></td>
            <td><?= (int) $genre['PaintingCount'] ?></td>
            <td><a href="genre-delete.php?id=<?= (int) $genre['GenreID'] ?>">Delete</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php if (!$genres->num_rows): ?><p>No genres found.</p><?php endif; ?>
<?php art_footer(); ?>
