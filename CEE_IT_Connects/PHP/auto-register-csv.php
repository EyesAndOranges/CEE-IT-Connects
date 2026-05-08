<?php
session_start();

if (!isset($_FILES['students_csv'])) {
    die("No file uploaded.");
}

$tmp = $_FILES['students_csv']['tmp_name'];

if ($_FILES['students_csv']['error'] !== UPLOAD_ERR_OK) {
    die("Upload failed.");
}

$destination = __DIR__ . '/../Sources/students.csv';

if (!move_uploaded_file($tmp, $destination)) {
    die("Could not save CSV.");
}

header("Location: internship-ui.php");
exit;
?>