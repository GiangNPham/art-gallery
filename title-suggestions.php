<?php
require_once __DIR__ . '/includes/app.php';
art_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    art_error(405, 'Method not allowed', 'Use the title search in the gallery.');
}

$query = $_GET['q'] ?? '';
if (!is_string($query) || mb_strlen($query) > 100) {
    art_error(400, 'Invalid title', 'Enter a shorter painting title.');
}
$query = trim($query);
$titles = [];
if ($query !== '') {
    $prefix = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $query);
    $stmt = art_db()->prepare("SELECT DISTINCT Title FROM Paintings
        WHERE Title LIKE CONCAT(?, '%') ESCAPE '!'
        ORDER BY Title LIMIT 8");
    $stmt->bind_param('s', $prefix);
    $stmt->execute();
    $titles = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'Title');
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
echo json_encode(['titles' => $titles], JSON_INVALID_UTF8_SUBSTITUTE);
