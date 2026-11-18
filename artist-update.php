<?php
require_once __DIR__ . '/includes/app.php';
art_require_admin();

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    art_error(405, 'Method not allowed', 'Use the update artist form.');
}

$id = art_id($_GET['id'] ?? null);
if (!$id) art_error(400, 'Choose an artist', 'Use the Update link beside an artist.');

require __DIR__ . '/includes/artist-form.php';
