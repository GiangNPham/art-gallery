<?php
require_once __DIR__ . '/includes/app.php';
art_require_login();

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    art_error(405, 'Method not allowed', 'Open artwork details from the gallery.');
}

$id = art_id($_GET['id'] ?? null);
if (!$id) art_error(400, 'Choose an artwork', 'Select an artwork title or image in the gallery.');

$painting = art_painting($id);
if (!$painting) art_error(404, 'Artwork not found', 'This artwork may have been removed from the collection.');

$stmt = art_db()->prepare('SELECT g.GenreName
    FROM PaintingGenres pg INNER JOIN Genres g ON g.GenreID = pg.GenreID
    WHERE pg.PaintingID = ? ORDER BY g.GenreName');
$stmt->bind_param('i', $id);
$stmt->execute();
$genres = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'GenreName');

$description = trim(art_text($painting['Description'] ?? ''));
if ($description === '') $description = trim(art_text($painting['Excerpt'] ?? ''));

art_header($painting['Title']);
?>
<article class="art-detail">
    <div class="art-detail-image">
        <img src="<?= art_h(art_image($painting['ImageFileName'])) ?>" alt="<?= art_h($painting['Title']) ?>">
    </div>
    <div class="art-detail-information">
        <h2><?= art_h($painting['Title']) ?></h2>
        <dl>
            <dt>Artist</dt>
            <dd><?= art_h(art_artist($painting)) ?></dd>
            <dt>Year</dt>
            <dd><?= (int) $painting['YearOfWork'] ?: 'Unknown' ?></dd>
            <?php if (trim((string) $painting['Medium']) !== ''): ?><dt>Medium</dt>
                <dd><?= art_h($painting['Medium']) ?></dd><?php endif; ?>
            <?php if ((int) $painting['Width'] > 0 || (int) $painting['Height'] > 0): ?>
                <dt>Dimensions</dt>
                <dd><?= (int) $painting['Width'] ?: '?' ?> × <?= (int) $painting['Height'] ?: '?' ?></dd>
            <?php endif; ?>
            <?php if ($genres): ?><dt>Genre</dt>
                <dd><?= art_h(implode(', ', $genres)) ?></dd><?php endif; ?>
            <dt>Painting ID</dt>
            <dd><?= $id ?></dd>
        </dl>
        <?php if ($description !== ''): ?><h2>Description</h2>
            <p><?= nl2br(art_h($description)) ?></p><?php endif; ?>
        <p class="detail-actions"><a href="art-main.php">Back to Art Gallery</a> <a href="art-delete.php?id=<?= $id ?>">Delete artwork</a></p>
    </div>
</article>
<?php art_footer(); ?>