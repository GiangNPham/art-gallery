<?php
// Task 10: client registration. Creates Customers row and CustomerLogon row  with client role 
require_once __DIR__ . '/includes/auth.php';
art_session();
if (art_customer()) {
    header('Location: art-main.php', true, 303);
    exit;
}
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD', 'POST'], true)) {
    header('Allow: GET, HEAD, POST');
    art_error(405, 'Method not allowed', 'Use the registration form.');
}

$values = ['first_name' => '', 'last_name' => '', 'email' => '', 'userid' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    art_csrf();
    foreach ($values as $name => $unused) $values[$name] = art_post($name);
    // Passwords kept exactly as typed
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
    $confirm = isset($_POST['confirm']) && is_string($_POST['confirm']) ? $_POST['confirm'] : '';

    $namePattern = '/\A\p{L}[\p{L} \'.\-]{0,49}\z/u';
    if (!preg_match($namePattern, $values['first_name'])) $errors['first_name'] = 'Enter your first name (letters only, up to 50 characters).';
    if (!preg_match($namePattern, $values['last_name'])) $errors['last_name'] = 'Enter your last name (letters only, up to 50 characters).';
    if (mb_strlen($values['email']) > 255 || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address, like name@example.com.';
    }
    if (!preg_match('/\A[A-Za-z0-9_.\-]{3,30}\z/', $values['userid'])) {
        $errors['userid'] = 'User ID must be 3-30 characters: letters, numbers, dot, dash or underscore.';
    }
    if (strlen($password) < 8 || strlen($password) > 72
        || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'Password must be 8-72 characters with at least one letter and one number.';
    }
    if (!hash_equals($password, $confirm)) $errors['confirm'] = 'The two passwords do not match.';

    if (!$errors) {
        $db = art_db();
        $db->begin_transaction();
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO CustomerLogon (UserName, Pass, Salt, State, DateJoined, DateLastModified, Role)
                VALUES (?, ?, '', 1, NOW(), NOW(), 'client')");
            $stmt->bind_param('ss', $values['userid'], $hash);
            $stmt->execute();
            $id = (int) $db->insert_id;
            $stmt = $db->prepare('INSERT INTO Customers (CustomerID, FirstName, LastName, Email) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('isss', $id, $values['first_name'], $values['last_name'], $values['email']);
            $stmt->execute();
            $db->commit();
            $_SESSION['flash'] = 'Account created. Please sign in.';
            header('Location: art-login.php', true, 303);
            exit;
        } catch (mysqli_sql_exception $error) {
            $db->rollback();
            if ($error->getCode() !== 1062) throw $error;
            $errors['userid'] = 'That user ID is already taken. Choose another.';
        }
    }
    http_response_code(422);
}

function register_error(array $errors, string $field): void
{
    if (isset($errors[$field])) echo '<span class="error" role="alert"> ' . art_h($errors[$field]) . '</span>';
}

art_header('Create an account', 'register');
?>
<form method="post" action="register.php" id="register-form">
    <input type="hidden" name="csrf" value="<?= art_h(art_token()) ?>">
    <p><label>First name: <input name="first_name" value="<?= art_h($values['first_name']) ?>" maxlength="50" placeholder="e.g. Juliette" autocomplete="given-name" required autofocus></label><?php register_error($errors, 'first_name'); ?></p>
    <p><label>Last name: <input name="last_name" value="<?= art_h($values['last_name']) ?>" maxlength="50" placeholder="e.g. Kolto" autocomplete="family-name" required></label><?php register_error($errors, 'last_name'); ?></p>
    <p><label>Email: <input name="email" type="email" value="<?= art_h($values['email']) ?>" maxlength="255" placeholder="name@example.com" autocomplete="email" required></label><?php register_error($errors, 'email'); ?></p>
    <p><label>User ID: <input name="userid" value="<?= art_h($values['userid']) ?>" maxlength="30" pattern="[A-Za-z0-9_.\-]{3,30}" placeholder="e.g. art_fan42" autocomplete="username" required></label><?php register_error($errors, 'userid'); ?><br><span class="help">3-30 characters: letters, numbers, . - _</span></p>
    <p><label>Password: <input name="password" type="password" minlength="8" maxlength="72" placeholder="8+ characters, letters and numbers" autocomplete="new-password" required></label><?php register_error($errors, 'password'); ?></p>
    <p><label>Confirm password: <input name="confirm" type="password" maxlength="72" placeholder="Type the password again" autocomplete="new-password" required></label><?php register_error($errors, 'confirm'); ?></p>
    <button type="submit">Create account</button> <a href="art-login.php">Already have an account? Sign in</a>
</form>
<script src="assets/register.js" defer></script>
<?php art_footer(); ?>