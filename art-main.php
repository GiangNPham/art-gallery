<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/list-sort.php';
art_require_login();
$db = art_db();
$artist = isset($_GET['id']) && $_GET['id'] !== '' ? art_id($_GET['id']) : null;
if (isset($_GET['id']) && $_GET['id'] !== '' && !$artist) art_error(400, 'Invalid artist', 'Choose an artist from the list.');
$title = isset($_GET['title']) && is_string($_GET['title']) ? trim($_GET['title']) : '';
if (isset($_GET['title']) && (!is_string($_GET['title']) || mb_strlen($title) > 255)) {
    art_error(400, 'Invalid title', 'Enter a title of no more than 255 characters.');
}
$yearText = isset($_GET['year']) && is_string($_GET['year']) ? trim($_GET['year']) : '';
if ((isset($_GET['year']) && !is_string($_GET['year']))
    || ($yearText !== '' && !preg_match('/\A[1-9][0-9]{0,3}\z/', $yearText))
) {
    art_error(400, 'Invalid year', 'Enter a year between 1 and 9999.');
}
$year = $yearText === '' ? 0 : (int) $yearText;
$sortOptions = [
    'title' => ['Painting title', 'p.Title'],
    'artist' => ['Artist last name', 'a.LastName', 'a.FirstName', 'p.Title'],
    'year' => ['Year', 'p.YearOfWork', 'p.Title'],
];
[$orderBy, $sortKey, $direction] = art_list_sort($sortOptions, 'p.PaintingID');
$artists = $db->query('SELECT ArtistID, FirstName, LastName FROM Artists ORDER BY LastName, FirstName')->fetch_all(MYSQLI_ASSOC);
$sql = 'SELECT p.PaintingID, p.ImageFileName, p.Title, p.YearOfWork, a.FirstName, a.LastName
    FROM Paintings p LEFT JOIN Artists a ON p.ArtistID = a.ArtistID
    WHERE (? = 0 OR p.ArtistID = ?)
      AND (? = \'\' OR p.Title LIKE CONCAT(\'%\', ?, \'%\'))
      AND (? = 0 OR p.YearOfWork = ?)
    ORDER BY ' . $orderBy;
$artistId = $artist ?? 0;
$stmt = $db->prepare($sql);
$stmt->bind_param('iissii', $artistId, $artistId, $title, $title, $year, $year);
$stmt->execute();
$paintings = $stmt->get_result();

//added for task8 to select paintings to put in the carousel
$featured = $db->query('SELECT p.PaintingID, p.ImageFileName, p.Title, a.FirstName, a.LastName
    FROM Paintings p LEFT JOIN Artists a ON p.ArtistID = a.ArtistID ORDER BY RAND() LIMIT 12')->fetch_all(MYSQLI_ASSOC);


art_header('Art Gallery');
?>

<?php if ($featured): ?>
<link rel="stylesheet" href="assets/carousel.css">
<section class="carousel" aria-roledescription="carousel" aria-label="Featured paintings">
    <h2>Featured paintings</h2>
    <div class="carousel-frame">
        <button type="button" class="carousel-btn carousel-prev" aria-label="Previous paintings">&#10094;</button>
        <div class="carousel-viewport">
            <ul class="carousel-track">
                <?php foreach ($featured as $item): ?>
                    <li class="carousel-item">
                        <a href="art-detail.php?id=<?= (int) $item['PaintingID'] ?>">
                            <img src="<?= art_h(art_image($item['ImageFileName'])) ?>" alt="<?= art_h($item['Title']) ?>" loading="lazy">
                            <span class="carousel-caption"><?= art_h($item['Title']) ?><small><?= art_h(art_artist($item)) ?></small></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <button type="button" class="carousel-btn carousel-next" aria-label="Next paintings">&#10095;</button>
    </div>
    <div class="carousel-controls"><button type="button" class="carousel-toggle" aria-pressed="false">Pause</button></div>
</section>
<script src="assets/carousel.js" defer></script>
<?php endif; ?>



<form class="filters" method="get" action="art-main.php">
    <label for="artist">Artist:
        <select id="artist" name="id">
            <option value="">All artists</option>
            <?php foreach ($artists as $row): ?>
                <option value="<?= (int) $row['ArtistID'] ?>" <?= $artist === (int) $row['ArtistID'] ? 'selected' : '' ?>><?= art_h(art_artist($row)) ?></option>
            <?php endforeach; ?>
        </select></label>
    <label for="title">Title: <input id="title" name="title" value="<?= art_h($title) ?>" maxlength="255" placeholder="All titles"></label>
    <label for="year">Year: <input id="year" name="year" type="number" min="1" max="9999" value="<?= art_h($yearText) ?>" placeholder="All years"></label>
    <?php art_sort_controls($sortOptions, $sortKey, $direction); ?>
    <button type="submit">Apply</button><a href="art-main.php">Clear</a>
</form>
<p>Paintings shown <strong><?= number_format($paintings->num_rows) ?></strong></p>
<div class="table-scroll" role="region" aria-label="Collection table" tabindex="0">
<table>
    <thead>
        <tr>
            <th>Image</th>
            <th>Title</th>
            <th>Artist</th>
            <th>Year</th>
            <?php if (art_is_admin()): ?><th>Update</th><th>Delete</th><?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($paintings as $painting): ?>
            <tr data-painting-id="<?= (int) $painting['PaintingID'] ?>">
                <td><a href="art-detail.php?id=<?= (int) $painting['PaintingID'] ?>"><img src="<?= art_h(art_image($painting['ImageFileName'])) ?>" alt="<?= art_h($painting['Title']) ?>" width="80" height="80" loading="lazy"></a></td>
                <td><a href="art-detail.php?id=<?= (int) $painting['PaintingID'] ?>"><?= art_h($painting['Title']) ?></a></td>
                <td><?= art_h(art_artist($painting)) ?></td>
                <td><?= (int) $painting['YearOfWork'] ?: '' ?></td>
                <?php if (art_is_admin()): ?><td><a href="art-update.php?id=<?= (int) $painting['PaintingID'] ?>">Update</a></td>
                <td>
                    <form method="get" action="art-delete.php"><input type="hidden" name="id" value="<?= (int) $painting['PaintingID'] ?>"><button type="submit">Delete</button></form>
                </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php if (!$paintings->num_rows): ?><p>No artworks found.</p><?php endif; ?>
<?php art_footer(); ?>
