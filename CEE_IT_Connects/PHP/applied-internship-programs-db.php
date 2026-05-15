<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}
$program = $_SESSION['program'];
$internship_program =
    $student_id = $_SESSION['user_id'];
$internship_id = $_POST['internship_id'];

$internshipStmt = $pdo->prepare("SELECT program FROM internships WHERE id = :id");
$internshipStmt->execute([':id' => $internship_id]);
$internship = $internshipStmt->fetch(PDO::FETCH_ASSOC);

if (!$internship) {
    die("Internship not found.");
}

$internship_program = $internship['program'];

// Check if student's program matches
if ($program !== $internship_program) {
    echo "<script></script>";
    header("Location: applied-Internship-programs.php?");
    exit;
}
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