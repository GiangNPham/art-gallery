<?php
require_once __DIR__ . '/includes/auth.php';
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    art_error(405, 'Use the sign-in form', 'Enter your credentials on the customer sign-in page.');
}
if (art_customer()) {
    header('Location: art-main.php', true, 303);
    exit;
}
art_login_page();
