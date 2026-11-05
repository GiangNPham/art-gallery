<?php
require_once __DIR__ . '/app.php';

// The supplied Subjects table has no SubjectID column. Its seed rows correspond
// to the 1-based IDs stored in PaintingSubjects. Use real IDs if the table gains them.
function art_subjects_have_ids(mysqli $db): bool
{
    return $db->query("SHOW COLUMNS FROM Subjects LIKE 'SubjectID'")->num_rows > 0;
}

function art_subject_options(mysqli $db): array
{
    $subjects = [];
    if (art_subjects_have_ids($db)) {
        $rows = $db->query('SELECT SubjectID, SubjectName FROM Subjects ORDER BY SubjectID')->fetch_all(MYSQLI_ASSOC);
        $nameCounts = array_count_values(array_map('strval', array_column($rows, 'SubjectName')));
        foreach ($rows as $subject) {
            $id = (int) $subject['SubjectID'];
            $name = $subject['SubjectName'] ?: 'Unnamed subject';
            $subjects[$id] = $name . (($nameCounts[(string) $subject['SubjectName']] ?? 0) > 1 ? ' (#' . $id . ')' : '');
        }
    } else {
        // The original art-small.sql inserts these names in SubjectID order.
        // Confirm the current table still contains the same seed names before labeling IDs.
        $seed = [
            1 => 'Animals', 2 => 'Landscape', 3 => 'Music', 4 => 'Sports',
            5 => 'Abstract', 6 => 'Transportation', 7 => 'Architecture',
            8 => 'Religion', 9 => 'Still Life', 10 => 'People',
            11 => 'Botanical', 12 => 'Cuisine', 13 => 'History', 14 => 'Love',
            15 => 'Family', 16 => 'Performing Arts', 17 => 'Allegory',
            18 => 'Mythological', 19 => 'Power', 20 => 'Military', 21 => 'Abstract',
        ];
        $names = array_column($db->query('SELECT SubjectName FROM Subjects')->fetch_all(MYSQLI_ASSOC), 'SubjectName');
        $actual = array_count_values($names);
        $expected = array_count_values(array_values($seed));
        ksort($actual);
        ksort($expected);
        if ($actual === $expected) {
            foreach ($seed as $id => $name) $subjects[$id] = $name . ($expected[$name] > 1 ? ' (#' . $id . ')' : '');
        } else {
            for ($id = 1; $id <= count($names); $id++) $subjects[$id] = 'Subject #' . $id;
        }
    }
    return $subjects;
}
