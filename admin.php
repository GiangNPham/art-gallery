<?php
// Task 10: admin main page

require_once __DIR__ . '/includes/app.php';
art_require_admin();
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    art_error(405, 'Method not allowed', 'Open the admin page from the navigation link.');
}
$db = art_db();
$totals = $db->query('SELECT
    (SELECT COUNT(*) FROM Artists) AS Artists,
    (SELECT COUNT(*) FROM Paintings) AS Paintings,
    (SELECT COUNT(*) FROM Galleries) AS Galleries,
    (SELECT COUNT(*) FROM Eras) AS Eras,
    (SELECT COUNT(*) FROM Genres) AS Genres,
    (SELECT COUNT(*) FROM Subjects) AS Subjects')->fetch_assoc();
$users = $db->query("SELECT COUNT(*) AS total, COALESCE(SUM(Role = 'client'), 0) AS clients,
    COALESCE(SUM(Role = 'admin'), 0) AS admins, COALESCE(SUM(State = 0), 0) AS disabled FROM CustomerLogon")->fetch_assoc();
$recent = $db->query('SELECT l.UserName, l.Role, l.DateJoined, c.FirstName, c.LastName
    FROM CustomerLogon l LEFT JOIN Customers c ON c.CustomerID = l.CustomerID
    ORDER BY l.DateJoined DESC, l.CustomerID DESC LIMIT 5')->fetch_all(MYSQLI_ASSOC);
$reports = [
    'Artists' => ['Artists', 'artists.php'], 'Paintings' => ['Paintings', 'art-main.php'],
    'Galleries' => ['Galleries', 'galleries.php'], 'Eras' => ['Eras', 'eras.php'],
    'Genres' => ['Genres', 'genres.php'], 'Subjects' => ['Subjects', 'subjects.php'],
];
art_header('Admin');
?>
<h2>Manage the collection</h2>
<p>
    <a href="art-add.php">Add artwork</a> | <a href="artist-add.php">Add artist</a> | <a href="era-add.php">Add era</a> |
    <a href="gallery-add.php">Add gallery</a> | <a href="genre-add.php">Add genre</a> | <a href="subject-add.php">Add subject</a> |
    <a href="painting-genre-add.php">Add painting genre</a> | <a href="painting-subject-add.php">Add painting subject</a>
</p>
<p class="help">To update or delete an item, open its list (below or in the navigation) and use the Update and Delete links.</p>

<h2>Manage users</h2>
<p><a href="admin-users.php">View and manage user accounts</a></p>

<h2>Reports</h2>
<table>
    <thead><tr><th scope="col">Collection table</th><th scope="col">Total records</th></tr></thead>
    <tbody>
    <?php foreach ($reports as $key => [$label, $url]): ?>
        <tr><th scope="row"><a href="<?= art_h($url) ?>"><?= art_h($label) ?></a></th><td><?= number_format((int) $totals[$key]) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
<table>
    <thead><tr><th scope="col">User accounts</th><th scope="col">Total</th></tr></thead>
    <tbody>
        <tr><th scope="row">All accounts</th><td><?= number_format((int) $users['total']) ?></td></tr>
        <tr><th scope="row">Clients</th><td><?= number_format((int) $users['clients']) ?></td></tr>
        <tr><th scope="row">Admins</th><td><?= number_format((int) $users['admins']) ?></td></tr>
        <tr><th scope="row">Disabled</th><td><?= number_format((int) $users['disabled']) ?></td></tr>
    </tbody>
</table>
<h3>Newest accounts</h3>
<table>
    <thead><tr><th scope="col">User ID</th><th scope="col">Name</th><th scope="col">Type</th><th scope="col">Joined</th></tr></thead>
    <tbody>
    <?php foreach ($recent as $row): ?>
        <tr>
            <td><?= art_h($row['UserName']) ?></td>
            <td><?= art_h(trim(($row['FirstName'] ?? '') . ' ' . ($row['LastName'] ?? ''))) ?></td>
            <td><?= $row['Role'] === 'admin' ? 'Admin' : 'Client' ?></td>
            <td><?= art_h($row['DateJoined'] ? substr((string) $row['DateJoined'], 0, 10) : '') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php art_footer(); ?>