<?php
require_once __DIR__ . '/includes/app.php';
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
$artists = $db->query('SELECT ArtistID, FirstName, LastName FROM Artists ORDER BY LastName, FirstName')->fetch_all(MYSQLI_ASSOC);
$sql = 'SELECT p.PaintingID, p.ImageFileName, p.Title, p.YearOfWork, a.FirstName, a.LastName
    FROM Paintings p LEFT JOIN Artists a ON p.ArtistID = a.ArtistID
    WHERE (? = 0 OR p.ArtistID = ?)
      AND (? = \'\' OR p.Title LIKE CONCAT(\'%\', ?, \'%\'))
      AND (? = 0 OR p.YearOfWork = ?)
    ORDER BY p.Title';
$artistId = $artist ?? 0;
$stmt = $db->prepare($sql);
$stmt->bind_param('iissii', $artistId, $artistId, $title, $title, $year, $year);
$stmt->execute();
$paintings = $stmt->get_result();
art_header('Art Gallery');
?>
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
    <button type="submit">Filter</button><a href="art-main.php">Clear</a>
</form>
<table>
    <thead>
        <tr>
            <th>Image</th>
            <th>Title</th>
            <th>Artist</th>
            <th>Year</th>
            <th>Delete</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($paintings as $painting): ?>
            <tr data-painting-id="<?= (int) $painting['PaintingID'] ?>">
                <td><a href="art-detail.php?id=<?= (int) $painting['PaintingID'] ?>"><img src="<?= art_h(art_image($painting['ImageFileName'])) ?>" alt="<?= art_h($painting['Title']) ?>" width="80" height="80" loading="lazy"></a></td>
                <td><a href="art-detail.php?id=<?= (int) $painting['PaintingID'] ?>"><?= art_h($painting['Title']) ?></a></td>
                <td><?= art_h(art_artist($painting)) ?></td>
                <td><?= (int) $painting['YearOfWork'] ?: '' ?></td>
                <td>
                    <form method="get" action="art-delete.php"><input type="hidden" name="id" value="<?= (int) $painting['PaintingID'] ?>"><button type="submit">Delete</button></form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php if (!$paintings->num_rows): ?><p>No artworks found.</p><?php endif; ?>
<?php art_footer(); ?>