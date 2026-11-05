<?php
require_once __DIR__ . '/includes/app.php';
art_require_login();
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    art_error(405, 'Method not allowed', 'Choose a gallery from the Galleries page.');
}
$id = art_id($_GET['id'] ?? null);
if (!$id) art_error(400, 'Choose a gallery', 'Choose a gallery from the Galleries page.');
$stmt = art_db()->prepare('SELECT GalleryID, GalleryName, GalleryNativeName, GalleryCity, GalleryCountry,
    Latitude, Longitude, GalleryWebSite FROM Galleries WHERE GalleryID = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$gallery = $stmt->get_result()->fetch_assoc();
if (!$gallery) art_error(404, 'Gallery not found', 'This gallery may have been removed.');
$stmt = art_db()->prepare('SELECT PaintingID, Title FROM Paintings WHERE GalleryID = ? ORDER BY Title');
$stmt->bind_param('i', $id);
$stmt->execute();
$paintings = $stmt->get_result();
$website = (string) ($gallery['GalleryWebSite'] ?? '');
$safeWebsite = filter_var($website, FILTER_VALIDATE_URL)
    && in_array(strtolower(parse_url($website, PHP_URL_SCHEME) ?: ''), ['http', 'https'], true);
art_header('Gallery details');
?>
<dl>
    <dt>Name</dt><dd><?= art_h($gallery['GalleryName']) ?></dd>
    <?php if ($gallery['GalleryNativeName']): ?><dt>Native name</dt><dd><?= art_h($gallery['GalleryNativeName']) ?></dd><?php endif; ?>
    <?php if ($gallery['GalleryCity']): ?><dt>City</dt><dd><?= art_h($gallery['GalleryCity']) ?></dd><?php endif; ?>
    <?php if ($gallery['GalleryCountry']): ?><dt>Country</dt><dd><?= art_h($gallery['GalleryCountry']) ?></dd><?php endif; ?>
    <?php if ($gallery['Latitude'] !== null && $gallery['Longitude'] !== null): ?>
        <dt>Coordinates</dt><dd><?= art_h($gallery['Latitude']) ?>, <?= art_h($gallery['Longitude']) ?></dd>
    <?php endif; ?>
    <?php if ($safeWebsite): ?><dt>Website</dt><dd><a href="<?= art_h($website) ?>"><?= art_h($website) ?></a></dd><?php endif; ?>
</dl>
<h2>Paintings (<?= $paintings->num_rows ?>)</h2>
<?php if ($paintings->num_rows): ?><ul>
    <?php foreach ($paintings as $painting): ?><li><a href="art-detail.php?id=<?= (int) $painting['PaintingID'] ?>"><?= art_h($painting['Title']) ?></a></li><?php endforeach; ?>
</ul><?php else: ?><p>No paintings are linked to this gallery.</p><?php endif; ?>
<p><a href="gallery-delete.php?id=<?= $id ?>">Delete gallery</a> | <a href="galleries.php">Back to Galleries</a></p>
<?php art_footer(); ?>
