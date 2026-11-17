<?php
require_once __DIR__ . '/includes/app.php';
require_once __DIR__ . '/includes/list-sort.php';
art_require_login();
$sortOptions = [
    'name' => ['Last name', 'a.LastName', 'a.FirstName'],
    'nationality' => ['Nationality', 'a.Nationality', 'a.LastName', 'a.FirstName'],
    'birth' => ['Birth year', 'a.YearOfBirth', 'a.LastName', 'a.FirstName'],
    'paintings' => ['Painting count', 'PaintingCount', 'a.LastName', 'a.FirstName'],
];
[$orderBy, $sortKey, $direction] = art_list_sort($sortOptions, 'a.ArtistID');
$artists = art_db()->query('SELECT a.ArtistID, a.FirstName, a.LastName, a.Nationality,
    a.YearOfBirth, a.YearOfDeath, COUNT(p.PaintingID) AS PaintingCount
    FROM Artists a LEFT JOIN Paintings p ON p.ArtistID = a.ArtistID
    GROUP BY a.ArtistID, a.FirstName, a.LastName, a.Nationality, a.YearOfBirth, a.YearOfDeath
    ORDER BY ' . $orderBy);
art_header('Artists');
?>
<p><a href="artist-add.php">ADD artist</a></p>
<form class="filters" method="get" action="artists.php">
    <?php art_sort_controls($sortOptions, $sortKey, $direction); ?>
    <button type="submit">Sort</button>
</form>
<table>
    <thead><tr><th>Name</th><th>Nationality</th><th>Born</th><th>Died</th><th>Paintings</th><th>Update</th><th>Delete</th></tr></thead>
    <tbody>
    <?php foreach ($artists as $artist): ?>
        <tr data-artist-id="<?= (int) $artist['ArtistID'] ?>">
            <td><?= art_h(art_artist($artist)) ?></td>
            <td><?= art_h($artist['Nationality']) ?></td>
            <td><?= (int) $artist['YearOfBirth'] ?: '' ?></td>
            <td><?= (int) $artist['YearOfDeath'] ?: '' ?></td>
            <td><a href="art-main.php?id=<?= (int) $artist['ArtistID'] ?>"><?= (int) $artist['PaintingCount'] ?></a></td>
            <td><a href="artist-update.php?id=<?= (int) $artist['ArtistID'] ?>">Update</a></td>
            <td><a href="artist-delete.php?id=<?= (int) $artist['ArtistID'] ?>">Delete</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php if (!$artists->num_rows): ?><p>No artists found.</p><?php endif; ?>
<?php art_footer(); ?>
