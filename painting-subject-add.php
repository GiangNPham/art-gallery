<?php
require_once __DIR__ . '/includes/painting-subjects.php';
art_require_login();
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    art_error(405, 'Method not allowed', 'Use the add painting subject form.');
}
$db = art_db();
$subjects = art_subject_options($db);
$paintings = $db->query('SELECT PaintingID, Title FROM Paintings ORDER BY Title, PaintingID')->fetch_all(MYSQLI_ASSOC);
$paintingValue = '';
$subjectValue = '';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    art_csrf();
    $paintingValue = art_post('painting_id');
    $subjectValue = art_post('subject_id');
    $paintingId = art_id($paintingValue);
    $subjectId = art_id($subjectValue);
    if (!$paintingId) $errors[] = 'Choose a valid painting.';
    if (!$subjectId || !isset($subjects[$subjectId])) $errors[] = 'Choose a valid subject.';
    if (!$errors) {
        $db->begin_transaction();
        try {
            // Lock the painting so two requests through this form cannot add the same link.
            $stmt = $db->prepare('SELECT PaintingID FROM Paintings WHERE PaintingID = ? FOR UPDATE');
            $stmt->bind_param('i', $paintingId);
            $stmt->execute();
            if (!$stmt->get_result()->fetch_assoc()) {
                $errors[] = 'The selected painting is no longer available.';
            } else {
                if (art_subjects_have_ids($db)) {
                    $stmt = $db->prepare('SELECT SubjectID FROM Subjects WHERE SubjectID = ? FOR UPDATE');
                    $stmt->bind_param('i', $subjectId);
                    $stmt->execute();
                    if (!$stmt->get_result()->fetch_assoc()) $errors[] = 'The selected subject is no longer available.';
                } elseif (!isset(art_subject_options($db)[$subjectId])) {
                    $errors[] = 'The selected subject is no longer available.';
                }
                if (!$errors) {
                    $stmt = $db->prepare('SELECT PaintingSubjectID FROM PaintingSubjects
                        WHERE PaintingID = ? AND SubjectID = ? LIMIT 1 FOR UPDATE');
                    $stmt->bind_param('ii', $paintingId, $subjectId);
                    $stmt->execute();
                    if ($stmt->get_result()->fetch_assoc()) {
                        $errors[] = 'This painting already has that subject.';
                    } else {
                        $stmt = $db->prepare('INSERT INTO PaintingSubjects (PaintingID, SubjectID) VALUES (?, ?)');
                        $stmt->bind_param('ii', $paintingId, $subjectId);
                        $stmt->execute();
                    }
                }
            }
            if ($errors) $db->rollback();
            else {
                $db->commit();
                $_SESSION['flash'] = 'Painting subject added.';
                header('Location: painting-subjects.php', true, 303);
                exit;
            }
        } catch (Throwable $exception) {
            $db->rollback();
            throw $exception;
        }
    }
    http_response_code(422);
}
art_header('Add painting subject');
?>
<?php if ($errors): ?><div class="error" role="alert"><ul>
    <?php foreach ($errors as $error): ?><li><?= art_h($error) ?></li><?php endforeach; ?>
</ul></div><?php endif; ?>
<form method="post" action="painting-subject-add.php">
    <input type="hidden" name="csrf" value="<?= art_h(art_token()) ?>">
    <p><label>Painting: <select name="painting_id" required>
        <option value="">Choose a painting</option>
        <?php foreach ($paintings as $painting): ?>
            <option value="<?= (int) $painting['PaintingID'] ?>" <?= $paintingValue === (string) $painting['PaintingID'] ? 'selected' : '' ?>><?= art_h($painting['Title']) ?> (#<?= (int) $painting['PaintingID'] ?>)</option>
        <?php endforeach; ?>
    </select></label></p>
    <p><label>Subject: <select name="subject_id" required>
        <option value="">Choose a subject</option>
        <?php foreach ($subjects as $id => $label): ?>
            <option value="<?= $id ?>" <?= $subjectValue === (string) $id ? 'selected' : '' ?>><?= art_h($label) ?></option>
        <?php endforeach; ?>
    </select></label></p>
    <button type="submit">ADD painting subject</button> <a href="painting-subjects.php">Cancel</a>
</form>
<?php art_footer(); ?>
