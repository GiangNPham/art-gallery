<?php
require_once __DIR__ . '/app.php';

function art_check_credentials(string $userid, string $password): ?array
{
    $db = art_db();
    $stmt = $db->prepare('SELECT c.CustomerID, c.FirstName, c.LastName, l.UserName, l.Pass
        FROM CustomerLogon l INNER JOIN Customers c ON c.CustomerID = l.CustomerID
        WHERE l.UserName = ? AND l.State = 1 LIMIT 1');
    $stmt->bind_param('s', $userid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row || !password_verify($password, (string) $row['Pass'])) return null;
    return [
        'customer_id' => (int) $row['CustomerID'],
        'userid' => (string) $row['UserName'],
        'first_name' => (string) $row['FirstName'],
        'last_name' => (string) $row['LastName'],
    ];
}

function art_sign_in(array $customer): void
{
    art_session();
    session_regenerate_id(true);
    $_SESSION = [
        'customer' => $customer,
        'csrf' => bin2hex(random_bytes(32)),
    ];
}

function art_login_page(string $userid = '', string $error = ''): void
{
    art_header('Customer sign in', 'login');
    require __DIR__ . '/login-form.php';
    art_footer();
}
