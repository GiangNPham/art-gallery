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
<?php if (art_is_admin()): ?><p><a href="era-add.php">ADD era</a></p><?php endif; ?>
<div class="table-scroll" role="region" aria-label="Collection table" tabindex="0">
<table>
    <thead><tr><th>Name</th><th>Years</th><?php if (art_is_admin()): ?><th>Update</th><th>Delete</th><?php endif; ?></tr></thead>
    <tbody>
    <?php foreach ($eras as $era): ?>
        <tr>
            <td><a href="era-detail.php?name=<?= rawurlencode($era['EraName']) ?>"><?= art_h($era['EraName']) ?></a></td>
            <td><?= art_h($era['EraYears']) ?></td>
            <?php if (art_is_admin()): ?><td><a href="era-update.php?name=<?= rawurlencode($era['EraName']) ?>">Update</a></td><?php endif; ?>
            <?php if (art_is_admin()): ?><td><a href="era-delete.php?name=<?= rawurlencode($era['EraName']) ?>">Delete</a></td><?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php if (!$eras->num_rows): ?><p>No eras found.</p><?php endif; ?>
<?php art_footer(); ?>
