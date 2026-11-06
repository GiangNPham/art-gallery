<?php
require_once __DIR__ . '/includes/painting-subjects.php';
art_require_login();
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    art_error(405, 'Method not allowed', 'Open painting subjects from the navigation link.');
}
$db = art_db();
$subjects = art_subject_options($db);
$assignments = $db->query('SELECT ps.PaintingSubjectID, ps.PaintingID, ps.SubjectID, p.Title, p.ImageFileName
    FROM PaintingSubjects ps LEFT JOIN Paintings p ON p.PaintingID = ps.PaintingID
    ORDER BY p.Title, ps.PaintingID, ps.SubjectID, ps.PaintingSubjectID');
$paintings = [];
foreach ($assignments as $assignment) {
    $paintingId = (int) $assignment['PaintingID'];
    if (!isset($paintings[$paintingId])) {
        $paintings[$paintingId] = [
            'PaintingID' => $paintingId,
            'Title' => $assignment['Title'],
            'ImageFileName' => $assignment['ImageFileName'],
            'subjects' => [],
        ];
    }
    $paintings[$paintingId]['subjects'][] = $assignment;
}
art_header('Painting subjects');
?>
<p><a href="painting-subject-add.php">ADD painting subject</a></p>
<h2>Painting links</h2>
<table>
    <thead><tr><th>Image</th><th>Title</th><th>Subject</th><th>Delete</th></tr></thead>
    <tbody>
    <?php foreach ($paintings as $painting): ?>
    <?php foreach ($painting['subjects'] as $index => $assignment): ?>
        <tr>
            <?php if ($index === 0): ?>
            <td rowspan="<?= count($painting['subjects']) ?>"><?php if ($painting['Title'] !== null): ?>
                <a href="art-detail.php?id=<?= (int) $painting['PaintingID'] ?>"><img src="<?= art_h(art_image($painting['ImageFileName'])) ?>" alt="<?= art_h($painting['Title']) ?>" width="80" height="80" loading="lazy"></a>
            <?php else: ?>No image available.<?php endif; ?></td>
            <td rowspan="<?= count($painting['subjects']) ?>"><?php if ($painting['Title'] !== null): ?>
                <a href="art-detail.php?id=<?= (int) $painting['PaintingID'] ?>"><?= art_h($painting['Title']) ?></a>
            <?php else: ?>Missing painting #<?= (int) $painting['PaintingID'] ?><?php endif; ?></td>
            <?php endif; ?>
            <td><a href="subject-detail.php?id=<?= (int) $assignment['SubjectID'] ?>"><?= art_h($subjects[(int) $assignment['SubjectID']] ?? 'Unknown subject #' . (int) $assignment['SubjectID']) ?></a></td>
            <td><a href="painting-subject-delete.php?id=<?= (int) $assignment['PaintingSubjectID'] ?>">Delete</a></td>
        </tr>
    <?php endforeach; ?>
    <?php endforeach; ?>
    </tbody>
</table>
<?php if (!$paintings): ?><p>No painting subjects found.</p><?php endif; ?>
<?php art_footer(); ?>
