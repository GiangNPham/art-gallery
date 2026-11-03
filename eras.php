<?php
require_once __DIR__ . '/includes/app.php';
art_require_login();

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    art_error(405, 'Method not allowed', 'Open eras from the navigation link.');
}

$search = isset($_GET['search']) && is_string($_GET['search']) ? trim($_GET['search']) : '';
if (mb_strlen($search) > 255) art_error(400, 'Invalid search', 'Enter no more than 255 characters.');

$stmt = art_db()->prepare('SELECT EraName, EraYears FROM Eras
    WHERE ? = \'\' OR EraName LIKE CONCAT(\'%\', ?, \'%\') OR EraYears LIKE CONCAT(\'%\', ?, \'%\')
    ORDER BY EraName');
$stmt->bind_param('sss', $search, $search, $search);
$stmt->execute();
$eras = $stmt->get_result();
art_header('Eras');
?>
<p><a href="era-add.php">ADD era</a></p>
<form class="filters" method="get" action="eras.php">
    <label for="search">Search name or years:
        <input id="search" name="search" maxlength="255" value="<?= art_h($search) ?>">
    </label>
    <button type="submit">Search</button>
    <a href="eras.php">Clear</a>
</form>
<table>
    <thead><tr><th>Name</th><th>Years</th><th>Update</th><th>Delete</th></tr></thead>
    <tbody>
    <?php foreach ($eras as $era): ?>
        <tr>
            <td><a href="era-detail.php?name=<?= rawurlencode($era['EraName']) ?>"><?= art_h($era['EraName']) ?></a></td>
            <td><?= art_h($era['EraYears']) ?></td>
            <td><a href="era-update.php?name=<?= rawurlencode($era['EraName']) ?>">Update</a></td>
            <td><a href="era-delete.php?name=<?= rawurlencode($era['EraName']) ?>">Delete</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php if (!$eras->num_rows): ?><p>No eras found.</p><?php endif; ?>
<?php art_footer(); ?>
