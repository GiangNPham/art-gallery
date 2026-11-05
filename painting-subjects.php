<?php
require_once __DIR__ . '/includes/painting-subjects.php';
art_require_login();
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    art_error(405, 'Method not allowed', 'Open painting subjects from the navigation link.');
}
$db = art_db();
$subjects = art_subject_options($db);
$counts = [];
$countRows = $db->query('SELECT ps.SubjectID, COUNT(DISTINCT p.PaintingID) AS PaintingCount
    FROM PaintingSubjects ps LEFT JOIN Paintings p ON p.PaintingID = ps.PaintingID
    GROUP BY ps.SubjectID');
foreach ($countRows as $row) $counts[(int) $row['SubjectID']] = (int) $row['PaintingCount'];
$assignments = $db->query('SELECT ps.PaintingSubjectID, ps.PaintingID, ps.SubjectID, p.Title
    FROM PaintingSubjects ps LEFT JOIN Paintings p ON p.PaintingID = ps.PaintingID
    ORDER BY p.Title, ps.PaintingID, ps.SubjectID, ps.PaintingSubjectID');
art_header('Painting subjects');
?>
<p><a href="painting-subject-add.php">ADD painting subject</a></p>
<table>
    <thead><tr><th>Subject</th><th>Paintings</th></tr></thead>
    <tbody>
    <?php foreach ($subjects as $subjectId => $subjectName): ?>
        <tr data-subject-id="<?= $subjectId ?>">
            <td><?= art_h($subjectName) ?></td>
            <td><?= $counts[$subjectId] ?? 0 ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php if (!$subjects): ?><p>No subjects found.</p><?php endif; ?>
<h2>Painting links</h2>
<table>
    <thead><tr><th>Painting</th><th>Subject</th><th>Delete</th></tr></thead>
    <tbody>
    <?php foreach ($assignments as $assignment): ?>
        <tr data-painting-subject-id="<?= (int) $assignment['PaintingSubjectID'] ?>">
            <td><?php if ($assignment['Title'] !== null): ?>
                <a href="art-detail.php?id=<?= (int) $assignment['PaintingID'] ?>"><?= art_h($assignment['Title']) ?></a>
            <?php else: ?>Missing painting #<?= (int) $assignment['PaintingID'] ?><?php endif; ?></td>
            <td><?= art_h($subjects[(int) $assignment['SubjectID']] ?? 'Unknown subject #' . (int) $assignment['SubjectID']) ?></td>
            <td><a href="painting-subject-delete.php?id=<?= (int) $assignment['PaintingSubjectID'] ?>">Delete</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php if (!$assignments->num_rows): ?><p>No painting subjects found.</p><?php endif; ?>
<?php art_footer(); ?>
