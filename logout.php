<?php
require_once __DIR__ . '/includes/app.php';
art_session();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    art_error(405, 'Use the Sign out button', 'Sign out using the button in the gallery header.');
}
art_require_login();
art_csrf();
$_SESSION = [];
$params = session_get_cookie_params();
setcookie(session_name(), '', [
    'expires' => time() - 3600,
    'path' => $params['path'],
    'domain' => $params['domain'],
    'secure' => $params['secure'],
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_destroy();
header('Cache-Control: no-store');
header('Location: art-login.php', true, 303);
