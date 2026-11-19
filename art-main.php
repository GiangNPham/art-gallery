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
$pageText = $_GET['page'] ?? '1';
if (!is_string($pageText) || !preg_match('/\A[1-9][0-9]{0,8}\z/', $pageText)) {
    art_error(400, 'Invalid page', 'Choose a valid page number.');
}
$page = (int) $pageText;
$pageSize = 20;
$artists = $db->query('SELECT ArtistID, FirstName, LastName FROM Artists ORDER BY LastName, FirstName')->fetch_all(MYSQLI_ASSOC);
$where = ' FROM Paintings p LEFT JOIN Artists a ON p.ArtistID = a.ArtistID
    WHERE (? = 0 OR p.ArtistID = ?)
      AND (? = \'\' OR p.Title LIKE CONCAT(\'%\', ?, \'%\'))
      AND (? = 0 OR p.YearOfWork = ?)';
$artistId = $artist ?? 0;
$countStmt = $db->prepare('SELECT COUNT(*) AS total' . $where);
$countStmt->bind_param('iissii', $artistId, $artistId, $title, $title, $year, $year);
$countStmt->execute();
$total = (int) $countStmt->get_result()->fetch_assoc()['total'];
$pageCount = max(1, (int) ceil($total / $pageSize));
if ($page > $pageCount) $page = $pageCount;
$offset = ($page - 1) * $pageSize;
$sql = 'SELECT p.PaintingID, p.ImageFileName, p.Title, p.YearOfWork, a.FirstName, a.LastName
    ' . $where . ' ORDER BY ' . $orderBy . ' LIMIT ? OFFSET ?';
$stmt = $db->prepare($sql);
$stmt->bind_param('iissiiii', $artistId, $artistId, $title, $title, $year, $year, $pageSize, $offset);
$stmt->execute();
$paintings = $stmt->get_result();
$pageUrl = static function (int $number) use ($artist, $title, $yearText, $sortKey, $direction): string {
    return 'art-main.php?' . http_build_query([
        'id' => $artist ?? '', 'title' => $title, 'year' => $yearText,
        'sort' => $sortKey, 'direction' => $direction, 'page' => $number,
    ]);
};

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
    <div class="title-filter">
        <label for="title">Title:
            <input id="title" name="title" value="<?= art_h($title) ?>" maxlength="255" placeholder="Start typing a title" autocomplete="off" aria-autocomplete="list" aria-controls="title-suggestions" aria-expanded="false">
        </label>
        <ul id="title-suggestions" class="suggestions" role="listbox" aria-label="Painting title suggestions" hidden></ul>
    </div>
    <label for="year">Year: <input id="year" name="year" type="number" min="1" max="9999" value="<?= art_h($yearText) ?>" placeholder="All years"></label>
    <?php art_sort_controls($sortOptions, $sortKey, $direction); ?>
    <button type="submit">Apply</button><a href="art-main.php">Clear</a>
</form>
<p>Showing <strong><?= $total ? number_format($offset + 1) . '–' . number_format($offset + $paintings->num_rows) : '0' ?></strong> of <strong><?= number_format($total) ?></strong> paintings</p>
<div class="table-scroll" role="region" aria-label="Collection table" tabindex="0">
<table class="paintings-table">
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
<?php if ($pageCount > 1): ?>
<nav class="pagination" aria-label="Painting pages">
    <?php if ($page > 1): ?><a href="<?= art_h($pageUrl($page - 1)) ?>">Previous</a><?php endif; ?>
    <span>Page <?= $page ?> of <?= $pageCount ?></span>
    <?php if ($page < $pageCount): ?><a href="<?= art_h($pageUrl($page + 1)) ?>">Next</a><?php endif; ?>
</nav>
<?php endif; ?>
<script src="assets/title-suggestions.js" defer></script>
<?php art_footer(); ?>
