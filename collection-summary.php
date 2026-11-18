<?php
require_once __DIR__ . '/includes/app.php';
art_require_login();

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    art_error(405, 'Method not allowed', 'Open the collection summary from the navigation link.');
}

$totals = art_db()->query('SELECT
    (SELECT COUNT(*) FROM Artists) AS Artists,
    (SELECT COUNT(*) FROM Paintings) AS Paintings,
    (SELECT COUNT(*) FROM Galleries) AS Galleries,
    (SELECT COUNT(*) FROM Eras) AS Eras,
    (SELECT COUNT(*) FROM Genres) AS Genres,
    (SELECT COUNT(*) FROM Subjects) AS Subjects,
    (SELECT COUNT(*) FROM PaintingGenres) AS PaintingGenres,
    (SELECT COUNT(*) FROM PaintingSubjects) AS PaintingSubjects')->fetch_assoc();

$lists = [
    'Artists' => ['Artists', 'artists.php'],
    'Paintings' => ['Paintings', 'art-main.php'],
    'Galleries' => ['Galleries', 'galleries.php'],
    'Eras' => ['Eras', 'eras.php'],
    'Genres' => ['Genres', 'genres.php'],
    'Subjects' => ['Subjects', 'subjects.php'],
    'PaintingGenres' => ['Painting–genre assignments', 'painting-genres.php'],
    'PaintingSubjects' => ['Painting–subject assignments', 'painting-subjects.php'],
];
art_header('Collection summary');
?>
<div class="table-scroll" role="region" aria-label="Collection table" tabindex="0">
<table>
    <thead><tr><th scope="col">Collection table</th><th scope="col">Total records</th></tr></thead>
    <tbody>
    <?php foreach ($lists as $table => [$label, $url]): ?>
        <tr>
            <th scope="row"><a href="<?= art_h($url) ?>"><?= art_h($label) ?></a></th>
            <td><?= number_format((int) $totals[$table]) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php art_footer(); ?>
