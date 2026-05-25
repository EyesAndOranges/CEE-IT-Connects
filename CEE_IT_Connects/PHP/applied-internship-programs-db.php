<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}
$student_id = (int) $_SESSION['user_id'];
$internship_id = (int) $_POST['internship_id'];


// Fetch student's program from DB instead of relying on session
$studentStmt = $pdo->prepare("SELECT program FROM students WHERE id = ?");
$studentStmt->execute([$student_id]);
$student = $studentStmt->fetch(PDO::FETCH_ASSOC);
$studentProgram = $student['program'] ?? '';


$internshipStmt = $pdo->prepare("SELECT program FROM internships WHERE id = ?");
$internshipStmt->execute([$internship_id]);
$internship = $internshipStmt->fetch(PDO::FETCH_ASSOC);


if (!$internship) {
    die("Internship not found.");
}

if ($studentProgram !== $internship['program']) {
    header("Location: applied-Internship-programs.php?error=program_mismatch");
    exit;
}
$stmt = $pdo->prepare("DELETE FROM internship_bookmarks WHERE id = ? AND student_id = ?");

$stmt->execute([$_POST['bookmark_id'], $_SESSION['user_id']]);
$stmt = $pdo->prepare("
    INSERT INTO internship_bookmarks (student_id, internship_id, created_at)
    VALUES (:student_id, :internship_id, NOW())
    ON CONFLICT (student_id, internship_id)
    DO UPDATE SET created_at = NOW()
");
$stmt->execute([
    ':student_id' => $student_id,
    ':internship_id' => $internship_id,
]);

header("Location: applied-Internship-programs.php?bookmarked=1");
exit;