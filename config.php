<?php
// Standard XAMPP settings used by the Lab 14 exercises.
return [
    'host' => getenv('ART_DB_HOST') ?: '127.0.0.1',
    'port' => (int) (getenv('ART_DB_PORT') ?: 3306),
    'database' => getenv('ART_DB_NAME') ?: 'art',
    'username' => getenv('ART_DB_USER') ?: 'root',
    'password' => getenv('ART_DB_PASSWORD') !== false ? getenv('ART_DB_PASSWORD') : '',
];
