<?php
require_once __DIR__ . '/includes/app.php';
art_require_login();

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    art_error(405, 'Method not allowed', 'Open eras from the navigation link.');
}

$eras = art_db()->query('SELECT EraName, EraYears FROM Eras ORDER BY EraName');
art_header('Eras');
?>
<p><a href="era-add.php">ADD era</a></p>
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
