<?php
session_start();

$sourceDir = __DIR__ . '/../Sources/';
$activeFile = file_exists($sourceDir . 'active_csv.txt')
    ? trim(file_get_contents($sourceDir . 'active_csv.txt'))
    : 'students.csv';

$csvPath = $sourceDir . $activeFile;

if (!file_exists($csvPath)) {
    die("CSV file not found.");
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $activeFile . '"');
header('Content-Length: ' . filesize($csvPath));

readfile($csvPath);
exit();