<?php
session_start();

$csvPath = __DIR__ . '/../Sources/students.csv';

if (!file_exists($csvPath)) {
    die("CSV file not found.");
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="students.csv"');
header('Content-Length: ' . filesize($csvPath));

readfile($csvPath);
exit();