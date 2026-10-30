<?php
require_once __DIR__ . '/includes/auth.php';
art_session();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Cache-Control: no-store');
    header('Location: art-login.php', true, 303);
    exit;
}
art_csrf();
$userid = art_post('userid');
// Keep the password exactly as entered; escape only displayed text.
$password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
$customer = art_check_credentials($userid, $password);
if (!$customer) {
    unset($_SESSION['customer']);
    http_response_code(401);
    art_login_page($userid, 'Invalid user ID or password.');
    exit;
}
art_sign_in($customer);
header('Cache-Control: no-store');
header('Location: art-main.php', true, 303);
