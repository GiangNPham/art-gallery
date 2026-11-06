<?php
require_once __DIR__ . '/includes/painting-subjects.php';
art_require_login();
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    art_error(405, 'Method not allowed', 'Open Subjects from the navigation link.');
}
$db = art_db();
if (!art_subjects_have_ids($db)) art_error(503, 'Subjects setup required', 'Follow the Subjects setup instructions in README.md.');
$subjects = $db->query('SELECT s.SubjectID, s.SubjectName, COUNT(DISTINCT p.PaintingID) AS PaintingCount
    FROM Subjects s LEFT JOIN PaintingSubjects ps ON ps.SubjectID = s.SubjectID
    LEFT JOIN Paintings p ON p.PaintingID = ps.PaintingID
    GROUP BY s.SubjectID, s.SubjectName ORDER BY s.SubjectName, s.SubjectID');
art_header('Subjects');
?>
<p><a href="subject-add.php">ADD subject</a></p>
<table>
    <thead><tr><th>ID</th><th>Name</th><th>Paintings</th><th>Delete</th></tr></thead>
    <tbody>
    <?php foreach ($subjects as $subject): ?>
        <tr>
            <td><?= (int) $subject['SubjectID'] ?></td>
            <td><a href="subject-detail.php?id=<?= (int) $subject['SubjectID'] ?>"><?= art_h($subject['SubjectName']) ?></a></td>
            <td><?= (int) $subject['PaintingCount'] ?></td>
            <td><a href="subject-delete.php?id=<?= (int) $subject['SubjectID'] ?>">Delete</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php if (!$subjects->num_rows): ?><p>No subjects found.</p><?php endif; ?>
<?php art_footer(); ?>
