<?php
session_start();

if (!isset($_POST['csv'])) {
    header("Location: internship-ui.php");
    exit();
}

$csvPath = __DIR__ . '/../Sources/students.csv';

if (!isset($_POST['csv']) || !is_array($_POST['csv'])) {
    die('No CSV data received.');
}

// read existing header
$header = [];
if (($handle = fopen($csvPath, 'r')) !== false) {
    $header = fgetcsv($handle);
    fclose($handle);
} else {
    die('Could not open CSV for reading.');
}

// write updated file
$handle = fopen($csvPath, 'w');

if ($handle === false) {
    die('Could not open CSV for writing.');
}

// preserve header
if (!empty($header)) {
    fputcsv($handle, $header);
}

// save edited rows
foreach ($_POST['csv'] as $row) {
    $row = array_map('trim', $row);

    if (count(array_filter($row, fn($v) => $v !== '')) === 0) {
        continue;
    }

    fputcsv($handle, $row);
}

fclose($handle);

header('Location: internship-ui.php');
exit();
?>