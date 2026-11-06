<?php
require_once __DIR__ . '/includes/painting-subjects.php';
art_require_login();
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    art_error(405, 'Method not allowed', 'Use the deletion confirmation form.');
}
$id = art_id($_GET['id'] ?? null);
if (!$id) art_error(400, 'Choose a subject', 'Use the Delete link beside a subject.');
$db = art_db();
if (!art_subjects_have_ids($db)) art_error(503, 'Subjects setup required', 'Follow the Subjects setup instructions in README.md.');
$deleting = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($deleting) {
    art_csrf();
    $db->begin_transaction();
}
try {
    $stmt = $db->prepare('SELECT SubjectName FROM Subjects WHERE SubjectID = ?' . ($deleting ? ' FOR UPDATE' : ''));
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $subject = $stmt->get_result()->fetch_assoc();
    $stmt = $db->prepare('SELECT PaintingSubjectID FROM PaintingSubjects WHERE SubjectID = ?' . ($deleting ? ' FOR UPDATE' : ''));
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $hasLinks = $stmt->get_result()->num_rows > 0;
    if ($deleting) {
        if ($subject && !$hasLinks) {
            $stmt = $db->prepare('DELETE FROM Subjects WHERE SubjectID = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
        }
        $db->commit();
        if (!$subject || !$hasLinks) {
            $_SESSION['flash'] = $subject ? 'Subject deleted.' : 'That subject has already been removed.';
            header('Location: subjects.php', true, 303);
            exit;
        }
        http_response_code(409);
    }
} catch (Throwable $error) {
    if ($deleting) $db->rollback();
    throw $error;
}
if (!$subject) art_error(404, 'Subject not found', 'This subject may have been removed.');
art_header('Delete subject');
?>
<p><?= art_h($subject['SubjectName']) ?></p>
<?php if ($hasLinks): ?>
    <p class="error" role="alert">This subject cannot be deleted while painting-subject links use it. Remove those links from Painting subjects first.</p>
    <p><a href="subjects.php">Back to Subjects</a></p>
<?php else: ?>
    <p>Delete this subject?</p>
    <form method="post" action="subject-delete.php?id=<?= $id ?>">
        <input type="hidden" name="csrf" value="<?= art_h(art_token()) ?>">
        <button type="submit">Delete subject</button> <a href="subjects.php">Keep subject</a>
    </form>
<?php endif; ?>
<?php art_footer(); ?>
