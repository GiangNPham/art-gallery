<?php
require_once __DIR__ . '/includes/painting-subjects.php';
require_once __DIR__ . '/includes/list-sort.php';
art_require_login();
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    art_error(405, 'Method not allowed', 'Open Subjects from the navigation link.');
}
$db = art_db();
if (!art_subjects_have_ids($db)) art_error(503, 'Subjects setup required', 'Follow the Subjects setup instructions in README.md.');
$sortOptions = [
    'name' => ['Subject name', 's.SubjectName'],
    'id' => ['Subject ID', 's.SubjectID'],
    'paintings' => ['Painting count', 'PaintingCount', 's.SubjectName'],
];
[$orderBy, $sortKey, $direction] = art_list_sort($sortOptions, 's.SubjectID');
$subjects = $db->query('SELECT s.SubjectID, s.SubjectName, COUNT(DISTINCT p.PaintingID) AS PaintingCount
    FROM Subjects s LEFT JOIN PaintingSubjects ps ON ps.SubjectID = s.SubjectID
    LEFT JOIN Paintings p ON p.PaintingID = ps.PaintingID
    GROUP BY s.SubjectID, s.SubjectName ORDER BY ' . $orderBy);
art_header('Subjects');
?>
<?php if (art_is_admin()): ?><p><a href="subject-add.php">ADD subject</a></p><?php endif; ?>
<form class="filters" method="get" action="subjects.php">
    <?php art_sort_controls($sortOptions, $sortKey, $direction); ?>
    <button type="submit">Sort</button>
</form>
<div class="table-scroll" role="region" aria-label="Collection table" tabindex="0">
<table>
    <thead><tr><th>ID</th><th>Name</th><th>Paintings</th><?php if (art_is_admin()): ?><th>Delete</th><?php endif; ?></tr></thead>
    <tbody>
    <?php foreach ($subjects as $subject): ?>
        <tr>
            <td><?= (int) $subject['SubjectID'] ?></td>
            <td><a href="subject-detail.php?id=<?= (int) $subject['SubjectID'] ?>"><?= art_h($subject['SubjectName']) ?></a></td>
            <td><?= (int) $subject['PaintingCount'] ?></td>
            <?php if (art_is_admin()): ?><td><a href="subject-delete.php?id=<?= (int) $subject['SubjectID'] ?>">Delete</a></td><?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php if (!$subjects->num_rows): ?><p>No subjects found.</p><?php endif; ?>
<?php art_footer(); ?>
