<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$student_id = $_SESSION['user_id'];
$internship_id = $_POST['internship_id'];

// UPSERT (PostgreSQL way)
$stmt = $pdo->prepare("
    INSERT INTO internship_bookmarks (student_id, internship_id)
    VALUES (:student_id, :internship_id)
    ON CONFLICT (student_id, internship_id)
    DO NOTHING
");

$stmt->execute([
    ':student_id' => $student_id,
    ':internship_id' => $internship_id
]);

header("Location: applied-Internship-programs.php?bookmarked=1");
exit;