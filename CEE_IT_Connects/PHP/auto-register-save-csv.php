<?php
session_start();
require 'db.php';

$csvPath = __DIR__ . '/../Sources/students.csv';

if (!isset($_POST['csv']) || !is_array($_POST['csv'])) {
    die("No CSV data received.");
}

if (!isset($_POST['headers']) || !is_array($_POST['headers'])) {
    die("No headers received.");
}

$rows = $_POST['csv'];
ksort($rows);

/* map headers from hidden inputs — no need to shift $rows at all */
$headers = [];
foreach ($_POST['headers'] as $colIndex => $value) {
    $h = strtolower(trim($value));
    $headers[$colIndex] = match ($h) {
        'student id' => 'student_id',
        'full name' => 'full_name',
        'e-mail', 'email' => 'email',
        'program' => 'program',
        'year' => 'year_level',
        'section' => 'section',
        'contact no.', 'contact number' => 'contact_number',
        default => $h
    };
}

/* write CSV */
$fp = fopen($csvPath, 'w');
if (!$fp)
    die("Cannot open CSV.");

fwrite($fp, implode("\t", array_values($headers)) . "\n");

foreach ($rows as $row) {
    if (!is_array($row))
        continue;

    $cleanRow = [];
    foreach ($headers as $colIndex => $colName) {
        $cleanRow[] = $row[$colIndex] ?? '';
    }

    if (count(array_filter($cleanRow)) === 0)
        continue;

    fwrite($fp, implode("\t", $cleanRow) . "\n");
}

fclose($fp);

/* save to database */
$stmt = $pdo->prepare("
    INSERT INTO students (email, full_name, student_id, program, year_level, section, contact_number, password_hash)
    VALUES (:email, :full_name, :student_id, :program, :year_level, :section, :contact_number, :password_hash)
    ON CONFLICT (student_id) DO UPDATE SET
        email          = EXCLUDED.email,
        full_name      = EXCLUDED.full_name,
        program        = EXCLUDED.program,
        year_level     = EXCLUDED.year_level,
        section        = EXCLUDED.section,
        contact_number = EXCLUDED.contact_number
");

foreach ($rows as $row) {
    if (!is_array($row))
        continue;

    $cleanRow = [];
    foreach ($headers as $colIndex => $colName) {
        $cleanRow[$colName] = trim($row[$colIndex] ?? '');
    }

    if (empty($cleanRow['student_id']))
        continue;

    $stmt->execute([
        'email' => $cleanRow['email'] ?? '',
        'full_name' => $cleanRow['full_name'] ?? '',
        'student_id' => $cleanRow['student_id'],
        'program' => $cleanRow['program'] ?? '',
        'year_level' => (int) ($cleanRow['year_level'] ?? 0),
        'section' => $cleanRow['section'] ?? '',
        'contact_number' => $cleanRow['contact_number'] ?? '',
        'password_hash' => password_hash($cleanRow['student_id'], PASSWORD_DEFAULT)
    ]);
}

header("Location: internship-ui.php");
exit();