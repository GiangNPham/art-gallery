<?php
// Run once with XAMPP PHP from Terminal; never through the web server.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../includes/painting-subjects.php';
$db = art_db();
if (art_subjects_have_ids($db)) {
    echo "Subjects already has IDs. No changes needed.\n";
    exit;
}
$seed = art_subject_seed();
$names = array_column($db->query('SELECT SubjectName FROM Subjects')->fetch_all(MYSQLI_ASSOC), 'SubjectName');
$actual = array_count_values($names);
$expected = array_count_values(array_values($seed));
ksort($actual);
ksort($expected);
if ($actual !== $expected) {
    fwrite(STDERR, "Subjects differs from the original seed. No changes made; map existing subjects to their painting links first.\n");
    exit(1);
}
$db->query('CREATE TABLE Subjects_with_ids (
    SubjectID INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
    SubjectName VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8');
$stmt = $db->prepare('INSERT INTO Subjects_with_ids (SubjectID, SubjectName) VALUES (?, ?)');
foreach ($seed as $id => $name) {
    $stmt->bind_param('is', $id, $name);
    $stmt->execute();
}
// Atomic rename preserves the original table and every painting-subject link.
// Existing backup/staging tables cause an error rather than being overwritten.
$db->query('RENAME TABLE Subjects TO Subjects_without_ids_backup, Subjects_with_ids TO Subjects');
echo "Subjects now has stable IDs. Original names retained in Subjects_without_ids_backup.\n";
