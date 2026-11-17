<?php
$updating = $id !== null;
$heading = $updating ? 'Update artist' : 'Add artist';
$action = $updating ? 'artist-update.php?id=' . $id : 'artist-add.php';
$values = array_fill_keys(['first_name', 'last_name', 'nationality', 'birth', 'death'], '');
$errors = [];
$chronologyError = 'Year of death must be later than year of birth.';

if ($updating) {
    $findArtist = art_db()->prepare('SELECT FirstName, LastName, Nationality, YearOfBirth, YearOfDeath FROM Artists WHERE ArtistID = ?');
    $findArtist->bind_param('i', $id);
    $findArtist->execute();
    $artist = $findArtist->get_result()->fetch_assoc();
    if (!$artist) art_error(404, 'Artist not found', 'This artist may have been removed.');
    $values = [
        'first_name' => $artist['FirstName'] ?? '',
        'last_name' => $artist['LastName'],
        'nationality' => $artist['Nationality'] ?? '',
        'birth' => $artist['YearOfBirth'] ?: '',
        'death' => $artist['YearOfDeath'] ?: '',
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    art_csrf();
    foreach ($values as $field => $_) $values[$field] = art_post($field);
    if ($values['last_name'] === '') $errors[] = 'Enter a last name (or the artist’s single name).';
    foreach (['first_name', 'last_name', 'nationality'] as $field) {
        if (mb_strlen($values[$field]) > 50) $errors[] = 'Names and nationality must each be no more than 50 characters.';
    }
    foreach (['birth' => 'Year of birth', 'death' => 'Year of death'] as $field => $label) {
        if (!art_valid_optional_year($_POST[$field] ?? '')) {
            $errors[$field] = $label . ' must be a whole number from 1 to 9999, or left blank.';
        }
    }
    if (!isset($errors['birth']) && !isset($errors['death'])
        && $values['birth'] !== '' && $values['death'] !== '' && (int) $values['death'] <= (int) $values['birth']) {
        $errors['death'] = $chronologyError;
    }
    if (!$errors) {
        $birth = $values['birth'] === '' ? null : (int) $values['birth'];
        $death = $values['death'] === '' ? null : (int) $values['death'];
        if ($updating) {
            $stmt = art_db()->prepare('UPDATE Artists SET FirstName = ?, LastName = ?, Nationality = ?, YearOfBirth = ?, YearOfDeath = ? WHERE ArtistID = ?');
            $stmt->bind_param('sssiii', $values['first_name'], $values['last_name'], $values['nationality'], $birth, $death, $id);
        } else {
            $stmt = art_db()->prepare('INSERT INTO Artists (FirstName, LastName, Nationality, YearOfBirth, YearOfDeath) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('sssii', $values['first_name'], $values['last_name'], $values['nationality'], $birth, $death);
        }
        $stmt->execute();
        if ($updating && $stmt->affected_rows === 0) {
            $findArtist->execute();
            if (!$findArtist->get_result()->fetch_assoc()) art_error(404, 'Artist not found', 'This artist may have been removed.');
        }
        $_SESSION['flash'] = $updating ? 'Artist updated.' : 'Artist added.';
        header('Location: artists.php', true, 303);
        exit;
    }
    http_response_code(422);
}
art_header($heading);
?>
<?php if ($errors): ?><div class="error" role="alert"><ul>
    <?php foreach (array_unique($errors) as $error): ?><li><?= art_h($error) ?></li><?php endforeach; ?>
</ul></div><?php endif; ?>
<form method="post" action="<?= art_h($action) ?>" data-chronology-error="<?= art_h($chronologyError) ?>">
    <input type="hidden" name="csrf" value="<?= art_h(art_token()) ?>">
    <?php if ($updating): ?><p><strong>Artist ID:</strong> <?= $id ?></p><?php endif; ?>
    <p><label>First name (optional): <input name="first_name" maxlength="50" value="<?= art_h($values['first_name']) ?>"></label></p>
    <p><label>Last name: <input name="last_name" maxlength="50" required value="<?= art_h($values['last_name']) ?>"></label></p>
    <p><label>Nationality (optional): <input name="nationality" maxlength="50" value="<?= art_h($values['nationality']) ?>"></label></p>
    <p><label>Year of birth (optional):
        <input name="birth" inputmode="numeric" pattern="[1-9][0-9]{0,3}" placeholder="e.g. 1881" aria-describedby="birth-help"
            <?= isset($errors['birth']) ? 'aria-invalid="true"' : '' ?> value="<?= art_h($values['birth']) ?>">
    </label><br><small class="help" id="birth-help">Enter a whole year from 1 to 9999.</small></p>
    <p><label>Year of death (optional):
        <input name="death" inputmode="numeric" pattern="[1-9][0-9]{0,3}" placeholder="e.g. 1973" aria-describedby="death-help"
            <?= isset($errors['death']) ? 'aria-invalid="true"' : '' ?> value="<?= art_h($values['death']) ?>">
    </label><br><small class="help" id="death-help">Enter a whole year from 1 to 9999. If both years are known, the death year must be later.</small></p>
    <button type="submit"><?= $updating ? 'Update artist' : 'ADD artist' ?></button> <a href="artists.php">Cancel</a>
</form>
<script src="assets/artist-form.js" defer></script>
<?php art_footer(); ?>
