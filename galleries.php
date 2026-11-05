<?php
require_once __DIR__ . '/includes/app.php';
art_require_login();
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    art_error(405, 'Method not allowed', 'Open galleries from the navigation link.');
}
$galleries = art_db()->query('SELECT g.GalleryID, g.GalleryName, g.GalleryCity, g.GalleryCountry,
    COUNT(p.PaintingID) AS PaintingCount
    FROM Galleries g LEFT JOIN Paintings p ON p.GalleryID = g.GalleryID
    GROUP BY g.GalleryID, g.GalleryName, g.GalleryCity, g.GalleryCountry
    ORDER BY g.GalleryName, g.GalleryID');
art_header('Galleries');
?>
<p><a href="gallery-add.php">ADD gallery</a></p>
<table>
    <thead><tr><th>Name</th><th>City</th><th>Country</th><th>Paintings</th><th>Delete</th></tr></thead>
    <tbody>
    <?php foreach ($galleries as $gallery): ?>
        <tr data-gallery-id="<?= (int) $gallery['GalleryID'] ?>">
            <td><a href="gallery-detail.php?id=<?= (int) $gallery['GalleryID'] ?>"><?= art_h($gallery['GalleryName']) ?></a></td>
            <td><?= art_h($gallery['GalleryCity']) ?></td>
            <td><?= art_h($gallery['GalleryCountry']) ?></td>
            <td><a href="gallery-detail.php?id=<?= (int) $gallery['GalleryID'] ?>"><?= (int) $gallery['PaintingCount'] ?></a></td>
            <td><a href="gallery-delete.php?id=<?= (int) $gallery['GalleryID'] ?>">Delete</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php if (!$galleries->num_rows): ?><p>No galleries found.</p><?php endif; ?>
<?php art_footer(); ?>
