<?php
// The gallery is self-contained so the artwork folder can be copied to htdocs.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
function art_db(): mysqli
{
    static $db;
    if ($db instanceof mysqli) return $db;
    $config = require __DIR__ . '/../config.php';
    try {
        $db = new mysqli($config['host'], $config['username'], $config['password'], $config['database'], $config['port']);
        $db->set_charset('utf8mb4');
        return $db;
    } catch (mysqli_sql_exception $error) {
        error_log($error->getMessage());
        art_error(503, 'The collection is unavailable', 'Start MySQL and follow the gallery README to import the Lab14 art database.');
    }
}
function art_h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function art_text($value): string
{
    return html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
function art_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name('artwork_session');
        session_start([
            'use_strict_mode' => 1,
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
        ]);
    }
}
function art_customer(): ?array
{
    art_session();
    $customer = $_SESSION['customer'] ?? null;
    return is_array($customer) && isset($customer['customer_id'], $customer['userid'], $customer['first_name'], $customer['last_name'])
        ? $customer : null;
}
function art_require_login(): void
{
    if (!art_customer()) {
        header('Cache-Control: no-store');
        header('Location: art-login.php', true, 303);
        exit;
    }
}
function art_token(): string
{
    art_session();
    return $_SESSION['csrf'] ?? ($_SESSION['csrf'] = bin2hex(random_bytes(32)));
}
function art_post(string $name): string
{
    return isset($_POST[$name]) && is_string($_POST[$name]) ? trim($_POST[$name]) : '';
}
function art_csrf(): void
{
    if (!hash_equals(art_token(), art_post('csrf'))) {
        art_error(403, 'Please reload the form', 'The form has expired. Return to the collection and try again.');
    }
}
function art_id($value): ?int
{
    if (!is_string($value) || !preg_match('/\A[1-9][0-9]{0,9}\z/', $value)) return null;
    $id = (int) $value;
    return $id <= 2147483647 ? $id : null;
}
function art_image($stem): string
{
    if (is_string($stem) && preg_match('/\A[a-zA-Z0-9_-]{1,50}\z/', $stem)) {
        foreach (['jpg', 'png', 'gif', 'webp'] as $extension) {
            if (is_file(__DIR__ . '/../images/art/works/square-medium/' . $stem . '.' . $extension)) {
                return 'images/art/works/square-medium/' . rawurlencode($stem) . '.' . $extension;
            }
        }
    }
    return 'assets/image-unavailable.svg';
}
function art_artist(array $row): string
{
    return trim(($row['FirstName'] ?? '') . ' ' . ($row['LastName'] ?? '')) ?: 'Unknown artist';
}
function art_header(string $title, string $active = 'collection'): void
{
    art_session();
    $customer = art_customer();
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1"><title>' . art_h($title)
        . '</title><link rel="stylesheet" href="assets/gallery.css"></head><body>'
        . '<h1>' . art_h($title) . '</h1><nav>';
    if ($customer) {
        echo '<a href="art-main.php">Art Gallery</a> | <a href="artists.php">Artists</a> | <form class="logout-form" action="art-add.php" method="get"><button type="submit">ADD artwork</button></form> | '
            . art_h($customer['first_name'] . ' ' . $customer['last_name'])
            . ' (' . art_h($customer['userid']) . ') '
            . '<form class="logout-form" action="logout.php" method="post"><input type="hidden" name="csrf" value="' . art_h(art_token()) . '">'
            . '<button type="submit">Sign out</button></form>';
    } else {
        echo '<a aria-current="page" href="art-login.php">Customer sign in</a>';
    }
    echo '</nav><main>';
    if (isset($_SESSION['flash'])) {
        echo '<div class="notice" role="status">' . art_h($_SESSION['flash']) . '</div>';
        unset($_SESSION['flash']);
    }
}
function art_footer(): void
{
    echo '</main></body></html>';
}
function art_error(int $code, string $title, string $message): never
{
    http_response_code($code);
    art_header($title);
    echo '<p>' . art_h($message) . '</p><a href="art-main.php">Back to Art Gallery</a>';
    art_footer();
    exit;
}
function art_painting(int $id): ?array
{
    $stmt = art_db()->prepare('SELECT p.*, a.FirstName, a.LastName
        FROM Paintings p LEFT JOIN Artists a ON p.ArtistID = a.ArtistID
        WHERE p.PaintingID = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
// Show a useful page for SQL failures, keeping server details in the error log.
set_exception_handler(function (Throwable $error): void {
    error_log((string) $error);
    if (!headers_sent()) art_error(500, 'We could not complete that request', 'Please try again. Check the gallery database setup if the problem continues.');
    echo '<p class="error" role="alert">The request could not be completed. Please try again.</p>';
});
