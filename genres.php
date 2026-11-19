<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/list-sort.php';
art_require_login();
$sortOptions = [
    'name' => ['Genre name', 'g.GenreName'],
    'paintings' => ['Painting count', 'PaintingCount', 'g.GenreName'],
];
[$orderBy, $sortKey, $direction] = art_list_sort($sortOptions, 'g.GenreID');
$genres = art_db()->query('SELECT g.GenreID, g.GenreName, g.Description,
    COUNT(DISTINCT p.PaintingID) AS PaintingCount
    FROM Genres g LEFT JOIN PaintingGenres pg ON pg.GenreID = g.GenreID
    LEFT JOIN Paintings p ON p.PaintingID = pg.PaintingID
    GROUP BY g.GenreID, g.GenreName, g.Description ORDER BY ' . $orderBy);
art_header('Genres');
?>
<?php if (art_is_admin()): ?><p><a href="genre-add.php">ADD genre</a></p><?php endif; ?>
<form class="filters" method="get" action="genres.php">
    <?php art_sort_controls($sortOptions, $sortKey, $direction); ?>
    <button type="submit">Sort</button>
</form>
<div class="table-scroll" role="region" aria-label="Collection table" tabindex="0">
<table class="genres-table">
    <colgroup>
        <col class="genre-name-column">
        <col>
        <col class="genre-count-column">
        <?php if (art_is_admin()): ?><col class="genre-action-column"><?php endif; ?>
    </colgroup>
    <thead><tr><th>Name</th><th>Description</th><th>Paintings</th><?php if (art_is_admin()): ?><th>Delete</th><?php endif; ?></tr></thead>
    <tbody>
    <?php foreach ($genres as $genre): ?>
        <tr data-genre-id="<?= (int) $genre['GenreID'] ?>">
            <td><a href="genre-detail.php?id=<?= (int) $genre['GenreID'] ?>"><?= art_h($genre['GenreName']) ?></a></td>
            <td><?= nl2br(art_h(art_text($genre['Description'] ?? ''))) ?></td>
            <td><?= (int) $genre['PaintingCount'] ?></td>
            <?php if (art_is_admin()): ?><td><a href="genre-delete.php?id=<?= (int) $genre['GenreID'] ?>">Delete</a></td><?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php if (!$genres->num_rows): ?><p>No genres found.</p><?php endif; ?>
<?php art_footer(); ?>
