<?php
require_once __DIR__ . '/includes/app.php';
art_require_login();
$values = array_fill_keys(['first_name', 'last_name', 'nationality', 'birth', 'death'], '');
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    art_csrf();
    foreach ($values as $field => $_) $values[$field] = art_post($field);
    if ($values['last_name'] === '') $errors[] = 'Enter a last name (or the artist’s single name).';
    foreach (['first_name', 'last_name', 'nationality'] as $field) {
        if (mb_strlen($values[$field]) > 50) $errors[] = 'Names and nationality must each be no more than 50 characters.';
    }
    foreach (['birth', 'death'] as $field) {
        if ($values[$field] !== '' && !preg_match('/\A[1-9][0-9]{0,3}\z/', $values[$field])) {
            $errors[] = 'Years must be between 1 and 9999, or left blank.';
        }
    }
    if (!$errors && $values['birth'] !== '' && $values['death'] !== '' && (int) $values['death'] < (int) $values['birth']) {
        $errors[] = 'Year of death cannot be earlier than year of birth.';
    }
    if (!$errors) {
        $birth = $values['birth'] === '' ? null : (int) $values['birth'];
        $death = $values['death'] === '' ? null : (int) $values['death'];
        $stmt = art_db()->prepare('INSERT INTO Artists (FirstName, LastName, Nationality, YearOfBirth, YearOfDeath) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('sssii', $values['first_name'], $values['last_name'], $values['nationality'], $birth, $death);
        $stmt->execute();
        $_SESSION['flash'] = 'Artist added.';
        header('Location: artists.php', true, 303);
        exit;
    }
    http_response_code(422);
}
art_header('Add artist');
?>
<?php if ($errors): ?><div class="error" role="alert"><ul>
    <?php foreach (array_unique($errors) as $error): ?><li><?= art_h($error) ?></li><?php endforeach; ?>
</ul></div><?php endif; ?>
<form method="post" action="artist-add.php">
    <input type="hidden" name="csrf" value="<?= art_h(art_token()) ?>">
    <p><label>First name (optional): <input name="first_name" maxlength="50" value="<?= art_h($values['first_name']) ?>"></label></p>
    <p><label>Last name: <input name="last_name" maxlength="50" required value="<?= art_h($values['last_name']) ?>"></label></p>
    <p><label>Nationality (optional): <input name="nationality" maxlength="50" value="<?= art_h($values['nationality']) ?>"></label></p>
    <p><label>Year of birth (optional): <input name="birth" type="number" min="1" max="9999" value="<?= art_h($values['birth']) ?>"></label></p>
    <p><label>Year of death (optional): <input name="death" type="number" min="1" max="9999" value="<?= art_h($values['death']) ?>"></label></p>
    <button type="submit">ADD artist</button> <a href="artists.php">Cancel</a>
</form>
<?php art_footer(); ?>
