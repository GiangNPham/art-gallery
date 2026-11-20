<?php
// Task 10: client profile
require_once __DIR__ . '/includes/auth.php';
art_require_login();
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD', 'POST'], true)) {
    header('Allow: GET, HEAD, POST');
    art_error(405, 'Method not allowed', 'Use the profile forms.');
}
$db = art_db();
$customerId = art_customer()['customer_id'];

$stmt = $db->prepare('SELECT c.FirstName, c.LastName, c.Email, c.Phone, c.Address, c.City, c.Country, l.UserName, l.Role, l.DateJoined
    FROM Customers c INNER JOIN CustomerLogon l ON l.CustomerID = c.CustomerID WHERE c.CustomerID = ?');
$stmt->bind_param('i', $customerId);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();
if (!$record) art_error(404, 'Profile not found', 'Your account could not be found.');

$values = [
    'first_name' => (string) $record['FirstName'], 'last_name' => (string) $record['LastName'],
    'email' => (string) $record['Email'], 'phone' => (string) $record['Phone'],
    'address' => (string) $record['Address'], 'city' => (string) $record['City'], 'country' => (string) $record['Country'],
];
$errors = [];
$passwordErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    art_csrf();
    $action = art_post('action');
    if ($action === 'details') {
        foreach ($values as $name => $unused) $values[$name] = art_post($name);
        $namePattern = '/\A\p{L}[\p{L} \'.\-]{0,49}\z/u';
        $placePattern = '/\A\p{L}[\p{L} \'.\-]{0,99}\z/u';
        if (!preg_match($namePattern, $values['first_name'])) $errors['first_name'] = 'Enter your first name (letters only, up to 50 characters).';
        if (!preg_match($namePattern, $values['last_name'])) $errors['last_name'] = 'Enter your last name (letters only, up to 50 characters).';
        if (mb_strlen($values['email']) > 255 || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address, like name@example.com.';
        }
        if ($values['phone'] !== '' && (!preg_match('/\A[0-9+() .\-]{7,25}\z/', $values['phone']) || preg_match_all('/[0-9]/', $values['phone']) < 7)) {
            $errors['phone'] = 'Enter a phone number with at least 7 digits, like 214-555-0123.';
        }
        if (mb_strlen($values['address']) > 255) $errors['address'] = 'Address must be 255 characters or fewer.';
        foreach (['city', 'country'] as $field) {
            if ($values[$field] !== '' && !preg_match($placePattern, $values[$field])) $errors[$field] = 'Use letters only (up to 100 characters), or leave it blank.';
        }
        if (!$errors) {
            $optional = static fn(string $v): ?string => $v === '' ? null : $v;
            $phone = $optional($values['phone']);
            $address = $optional($values['address']);
            $city = $optional($values['city']);
            $country = $optional($values['country']);
            $stmt = $db->prepare('UPDATE Customers SET FirstName = ?, LastName = ?, Email = ?, Phone = ?, Address = ?, City = ?, Country = ? WHERE CustomerID = ?');
            $stmt->bind_param('sssssssi', $values['first_name'], $values['last_name'], $values['email'], $phone, $address, $city, $country, $customerId);
            $stmt->execute();
            $_SESSION['customer']['first_name'] = $values['first_name'];
            $_SESSION['customer']['last_name'] = $values['last_name'];
            $_SESSION['flash'] = 'Your profile was updated.';
            header('Location: profile.php', true, 303);
            exit;
        }
    } elseif ($action === 'password') {
        $current = isset($_POST['current_password']) && is_string($_POST['current_password']) ? $_POST['current_password'] : '';
        $new = isset($_POST['new_password']) && is_string($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirm = isset($_POST['confirm_password']) && is_string($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        $stmt = $db->prepare('SELECT Pass FROM CustomerLogon WHERE CustomerID = ?');
        $stmt->bind_param('i', $customerId);
        $stmt->execute();
        $hash = (string) ($stmt->get_result()->fetch_column() ?? '');
        if ($current === '' || !password_verify($current, $hash)) $passwordErrors['current_password'] = 'Your current password is not correct.';
        if (strlen($new) < 8 || strlen($new) > 72 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/[0-9]/', $new)) {
            $passwordErrors['new_password'] = 'Password must be 8-72 characters with at least one letter and one number.';
        } elseif ($new === $current) {
            $passwordErrors['new_password'] = 'Choose a password different from your current one.';
        }
        if (!hash_equals($new, $confirm)) $passwordErrors['confirm_password'] = 'The two passwords do not match.';
        if (!$passwordErrors) {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $db->prepare('UPDATE CustomerLogon SET Pass = ?, DateLastModified = NOW() WHERE CustomerID = ?');
            $stmt->bind_param('si', $newHash, $customerId);
            $stmt->execute();
            session_regenerate_id(true);
            $_SESSION['flash'] = 'Your password was changed.';
            header('Location: profile.php', true, 303);
            exit;
        }
    } else {
        art_error(400, 'Unknown request', 'Use the forms on the profile page.');
    }
    http_response_code(422);
}

function profile_error(array $errors, string $field): void
{
    if (isset($errors[$field])) echo '<span class="error" role="alert"> ' . art_h($errors[$field]) . '</span>';
}

art_header('My profile');
$joined = $record['DateJoined'] ? substr((string) $record['DateJoined'], 0, 10) : 'unknown';
?>
<h2>Account</h2>
<p>User ID: <strong><?= art_h($record['UserName']) ?></strong><br>
Account type: <strong><?= $record['Role'] === 'admin' ? 'Administrator' : 'Client' ?></strong><br>
Member since: <strong><?= art_h($joined) ?></strong></p>

<h2>Personal details</h2>
<form method="post" action="profile.php" id="details-form">
    <input type="hidden" name="csrf" value="<?= art_h(art_token()) ?>">
    <input type="hidden" name="action" value="details">
    <p><label>First name: <input name="first_name" value="<?= art_h($values['first_name']) ?>" maxlength="50" placeholder="e.g. Juliette" required></label><?php profile_error($errors, 'first_name'); ?></p>
    <p><label>Last name: <input name="last_name" value="<?= art_h($values['last_name']) ?>" maxlength="50" placeholder="e.g. Kolto" required></label><?php profile_error($errors, 'last_name'); ?></p>
    <p><label>Email: <input name="email" type="email" value="<?= art_h($values['email']) ?>" maxlength="255" placeholder="name@example.com" required></label><?php profile_error($errors, 'email'); ?></p>
    <p><label>Phone: <input name="phone" type="tel" value="<?= art_h($values['phone']) ?>" maxlength="25" pattern="[0-9+\(\)\-. ]{7,25}" placeholder="214-555-0123 (optional)"></label><?php profile_error($errors, 'phone'); ?></p>
    <p><label>Address: <input name="address" value="<?= art_h($values['address']) ?>" maxlength="255" placeholder="123 Main St (optional)"></label><?php profile_error($errors, 'address'); ?></p>
    <p><label>City: <input name="city" value="<?= art_h($values['city']) ?>" maxlength="100" placeholder="Dallas (optional)"></label><?php profile_error($errors, 'city'); ?></p>
    <p><label>Country: <input name="country" value="<?= art_h($values['country']) ?>" maxlength="100" placeholder="United States (optional)"></label><?php profile_error($errors, 'country'); ?></p>
    <button type="submit">Save changes</button>
</form>

<h2>Change password</h2>
<form method="post" action="profile.php" id="password-form">
    <input type="hidden" name="csrf" value="<?= art_h(art_token()) ?>">
    <input type="hidden" name="action" value="password">
    <p><label>Current password: <input name="current_password" type="password" maxlength="72" autocomplete="current-password" required></label><?php profile_error($passwordErrors, 'current_password'); ?></p>
    <p><label>New password: <input name="new_password" type="password" minlength="8" maxlength="72" placeholder="8+ characters, letters and numbers" autocomplete="new-password" required></label><?php profile_error($passwordErrors, 'new_password'); ?></p>
    <p><label>Confirm new password: <input name="confirm_password" type="password" maxlength="72" placeholder="Type the new password again" autocomplete="new-password" required></label><?php profile_error($passwordErrors, 'confirm_password'); ?></p>
    <button type="submit">Change password</button>
</form>
<script src="assets/profile.js" defer></script>
<?php art_footer(); ?>