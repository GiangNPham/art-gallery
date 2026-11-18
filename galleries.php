<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/list-sort.php';
art_require_login();
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    art_error(405, 'Method not allowed', 'Open galleries from the navigation link.');
}
$sortOptions = [
    'name' => ['Gallery name', 'g.GalleryName'],
    'city' => ['City', 'g.GalleryCity', 'g.GalleryName'],
    'country' => ['Country', 'g.GalleryCountry', 'g.GalleryName'],
    'paintings' => ['Painting count', 'PaintingCount', 'g.GalleryName'],
];
[$orderBy, $sortKey, $direction] = art_list_sort($sortOptions, 'g.GalleryID');
$galleries = art_db()->query('SELECT g.GalleryID, g.GalleryName, g.GalleryCity, g.GalleryCountry,
    COUNT(p.PaintingID) AS PaintingCount
    FROM Galleries g LEFT JOIN Paintings p ON p.GalleryID = g.GalleryID
    GROUP BY g.GalleryID, g.GalleryName, g.GalleryCity, g.GalleryCountry
    ORDER BY ' . $orderBy);
art_header('Galleries');
?>
<?php if (art_is_admin()): ?><p><a href="gallery-add.php">ADD gallery</a></p><?php endif; ?>
<form class="filters" method="get" action="galleries.php">
    <?php art_sort_controls($sortOptions, $sortKey, $direction); ?>
    <button type="submit">Sort</button>
</form>
<div class="table-scroll" role="region" aria-label="Collection table" tabindex="0">
<table>
    <thead><tr><th>Name</th><th>City</th><th>Country</th><th>Paintings</th><?php if (art_is_admin()): ?><th>Delete</th><?php endif; ?></tr></thead>
    <tbody>
    <?php foreach ($galleries as $gallery): ?>
        <tr data-gallery-id="<?= (int) $gallery['GalleryID'] ?>">
            <td><a href="gallery-detail.php?id=<?= (int) $gallery['GalleryID'] ?>"><?= art_h($gallery['GalleryName']) ?></a></td>
            <td><?= art_h($gallery['GalleryCity']) ?></td>
            <td><?= art_h($gallery['GalleryCountry']) ?></td>
            <td><a href="gallery-detail.php?id=<?= (int) $gallery['GalleryID'] ?>"><?= (int) $gallery['PaintingCount'] ?></a></td>
            <?php if (art_is_admin()): ?><td><a href="gallery-delete.php?id=<?= (int) $gallery['GalleryID'] ?>">Delete</a></td><?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php if (!$galleries->num_rows): ?><p>No galleries found.</p><?php endif; ?>
<?php art_footer(); ?>
