<?php
require_once __DIR__ . '/includes/painting-subjects.php';
art_require_login();
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    art_error(405, 'Method not allowed', 'Choose a subject from Subjects.');
}
$id = art_id($_GET['id'] ?? null);
if (!$id) art_error(400, 'Choose a subject', 'Choose a subject from Subjects.');
$db = art_db();
if (!art_subjects_have_ids($db)) art_error(503, 'Subjects setup required', 'Follow the Subjects setup instructions in README.md.');
$stmt = $db->prepare('SELECT SubjectID, SubjectName FROM Subjects WHERE SubjectID = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$subject = $stmt->get_result()->fetch_assoc();
if (!$subject) art_error(404, 'Subject not found', 'This subject may have been removed.');
$stmt = $db->prepare('SELECT DISTINCT p.PaintingID, p.Title FROM PaintingSubjects ps
    INNER JOIN Paintings p ON p.PaintingID = ps.PaintingID WHERE ps.SubjectID = ? ORDER BY p.Title');
$stmt->bind_param('i', $id);
$stmt->execute();
$paintings = $stmt->get_result();
art_header('Subject details');
?>
<dl>
    <dt>Subject ID</dt><dd><?= (int) $subject['SubjectID'] ?></dd>
    <dt>Name</dt><dd><?= art_h($subject['SubjectName']) ?></dd>
</dl>
<h2>Paintings (<?= (int) $paintings->num_rows ?>)</h2>
<?php if ($paintings->num_rows): ?>
<ul>
<?php foreach ($paintings as $painting): ?>
    <li><a href="art-detail.php?id=<?= (int) $painting['PaintingID'] ?>"><?= art_h($painting['Title']) ?></a></li>
<?php endforeach; ?>
</ul>
<?php else: ?><p>No paintings are linked to this subject.</p><?php endif; ?>
<p><a href="subject-delete.php?id=<?= $id ?>">Delete subject</a> | <a href="subjects.php">Back to Subjects</a></p>
<?php art_footer(); ?>
