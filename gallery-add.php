<?php
require_once __DIR__ . '/includes/app.php';
art_require_login();
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    art_error(405, 'Method not allowed', 'Use the add gallery form.');
}
$values = array_fill_keys(['name', 'native_name', 'city', 'country', 'latitude', 'longitude', 'website'], '');
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    art_csrf();
    foreach ($values as $field => $_) $values[$field] = art_post($field);
    if ($values['name'] === '' || mb_strlen($values['name']) > 255) $errors[] = 'Enter a gallery name of 1–255 characters.';
    foreach (['native_name', 'city', 'country', 'website'] as $field) {
        if (mb_strlen($values[$field]) > 255) $errors[] = 'Optional text fields must be no more than 255 characters.';
    }
    foreach (['latitude' => 90, 'longitude' => 180] as $field => $limit) {
        if ($values[$field] !== '' && (!preg_match('/\A-?(?:\d+(?:\.\d+)?|\.\d+)\z/', $values[$field])
            || abs((float) $values[$field]) > $limit)) {
            $errors[] = ucfirst($field) . ' must be a number between -' . $limit . ' and ' . $limit . '.';
        }
    }
    if ($values['website'] !== '' && (!filter_var($values['website'], FILTER_VALIDATE_URL)
        || !in_array(strtolower(parse_url($values['website'], PHP_URL_SCHEME) ?: ''), ['http', 'https'], true))) {
        $errors[] = 'Enter a valid http or https website URL.';
    }
    if (!$errors) {
        $latitude = $values['latitude'] === '' ? null : (float) $values['latitude'];
        $longitude = $values['longitude'] === '' ? null : (float) $values['longitude'];
        $stmt = art_db()->prepare('INSERT INTO Galleries
            (GalleryName, GalleryNativeName, GalleryCity, GalleryCountry, Latitude, Longitude, GalleryWebSite)
            VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('ssssdds', $values['name'], $values['native_name'], $values['city'], $values['country'], $latitude, $longitude, $values['website']);
        $stmt->execute();
        $_SESSION['flash'] = 'Gallery added.';
        header('Location: galleries.php', true, 303);
        exit;
    }
    http_response_code(422);
}
art_header('Add gallery');
?>
<?php if ($errors): ?><div class="error" role="alert"><ul>
    <?php foreach (array_unique($errors) as $error): ?><li><?= art_h($error) ?></li><?php endforeach; ?>
</ul></div><?php endif; ?>
<form method="post" action="gallery-add.php">
    <input type="hidden" name="csrf" value="<?= art_h(art_token()) ?>">
    <p><label>Name: <input name="name" maxlength="255" required value="<?= art_h($values['name']) ?>"></label></p>
    <p><label>Native name (optional): <input name="native_name" maxlength="255" value="<?= art_h($values['native_name']) ?>"></label></p>
    <p><label>City (optional): <input name="city" maxlength="255" value="<?= art_h($values['city']) ?>"></label></p>
    <p><label>Country (optional): <input name="country" maxlength="255" value="<?= art_h($values['country']) ?>"></label></p>
    <p><label>Latitude (optional): <input name="latitude" type="number" step="any" min="-90" max="90" value="<?= art_h($values['latitude']) ?>"></label></p>
    <p><label>Longitude (optional): <input name="longitude" type="number" step="any" min="-180" max="180" value="<?= art_h($values['longitude']) ?>"></label></p>
    <p><label>Website (optional): <input name="website" type="url" maxlength="255" value="<?= art_h($values['website']) ?>"></label></p>
    <button type="submit">ADD gallery</button> <a href="galleries.php">Cancel</a>
</form>
<?php art_footer(); ?>
