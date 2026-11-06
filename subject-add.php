<?php
require_once __DIR__ . '/includes/painting-subjects.php';
art_require_login();
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    art_error(405, 'Method not allowed', 'Use the add subject form.');
}
$db = art_db();
if (!art_subjects_have_ids($db)) art_error(503, 'Subjects setup required', 'Follow the Subjects setup instructions in README.md.');
$name = '';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    art_csrf();
    $name = art_post('name');
    if ($name === '' || mb_strlen($name) > 255) $errors[] = 'Enter a subject name of 1–255 characters.';
    if (!$errors) {
        $stmt = $db->prepare('SELECT SubjectID FROM Subjects WHERE SubjectName = ? LIMIT 1');
        $stmt->bind_param('s', $name);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) $errors[] = 'A subject with this name already exists.';
        else {
            $stmt = $db->prepare('INSERT INTO Subjects (SubjectName) VALUES (?)');
            $stmt->bind_param('s', $name);
            $stmt->execute();
            $_SESSION['flash'] = 'Subject added.';
            header('Location: subjects.php', true, 303);
            exit;
        }
    }
    http_response_code(422);
}
art_header('Add subject');
?>
<?php foreach ($errors as $error): ?><p class="error" role="alert"><?= art_h($error) ?></p><?php endforeach; ?>
<form method="post" action="subject-add.php">
    <input type="hidden" name="csrf" value="<?= art_h(art_token()) ?>">
    <p><label>Subject name: <input name="name" maxlength="255" required value="<?= art_h($name) ?>"></label></p>
    <button type="submit">ADD subject</button> <a href="subjects.php">Cancel</a>
</form>
<?php art_footer(); ?>
