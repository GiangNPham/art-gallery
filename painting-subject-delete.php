<?php
require_once __DIR__ . '/includes/painting-subjects.php';
art_require_login();
$id = art_id($_GET['id'] ?? null);
if (!$id) art_error(400, 'Choose a painting subject', 'Use the Delete link beside a painting subject.');
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    art_error(405, 'Method not allowed', 'Use the deletion confirmation form.');
}
$db = art_db();
$deleting = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($deleting) {
    art_csrf();
    $db->begin_transaction();
}
try {
    $stmt = $db->prepare('SELECT PaintingID, SubjectID FROM PaintingSubjects WHERE PaintingSubjectID = ?' . ($deleting ? ' FOR UPDATE' : ''));
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $assignment = $stmt->get_result()->fetch_assoc();
    if ($deleting) {
        if ($assignment) {
            $stmt = $db->prepare('DELETE FROM PaintingSubjects WHERE PaintingSubjectID = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
        }
        $db->commit();
        $_SESSION['flash'] = $assignment ? 'Painting subject deleted.' : 'That painting subject has already been removed.';
        header('Location: painting-subjects.php', true, 303);
        exit;
    }
} catch (Throwable $exception) {
    if ($deleting) $db->rollback();
    throw $exception;
}
if (!$assignment) art_error(404, 'Painting subject not found', 'This link may have been removed.');
$stmt = $db->prepare('SELECT Title FROM Paintings WHERE PaintingID = ?');
$stmt->bind_param('i', $assignment['PaintingID']);
$stmt->execute();
$painting = $stmt->get_result()->fetch_assoc();
$subjects = art_subject_options($db);
$subjectLabel = $subjects[(int) $assignment['SubjectID']] ?? 'Unknown subject #' . (int) $assignment['SubjectID'];
art_header('Delete painting subject');
?>
<p>Remove <strong><?= art_h($subjectLabel) ?></strong> from
    <strong><?= art_h($painting['Title'] ?? 'painting #' . (int) $assignment['PaintingID']) ?></strong>?</p>
<form method="post" action="painting-subject-delete.php?id=<?= $id ?>">
    <input type="hidden" name="csrf" value="<?= art_h(art_token()) ?>">
    <button type="submit">Delete painting subject</button> <a href="painting-subjects.php">Keep painting subject</a>
</form>
<?php art_footer(); ?>
