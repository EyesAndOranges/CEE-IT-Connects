<?php
session_start();
require 'db.php';

$student_id = $_SESSION['user_id'] ?? null;
if (!$student_id)
    die("Not logged in");

/* =========================
   STEP 1: RESUME UPLOAD
========================= */
if (isset($_FILES['resume'])) {

    $file = $_FILES['resume'];

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf')
        die("Only PDF allowed");

    if ($file['size'] > 5 * 1024 * 1024)
        die("Too large");

    $newName = uniqid('resume_') . '.pdf';
    $path = __DIR__ . "/uploads/resumes/" . $newName;

    move_uploaded_file($file['tmp_name'], $path);

    // save file
    $stmt = $pdo->prepare("
        INSERT INTO student_resumes (student_id, file_name, file_path)
        VALUES (:id, :name, :path)
    ");

    $stmt->execute([
        'id' => $student_id,
        'name' => $file['name'],
        'path' => 'uploads/resumes/' . $newName
    ]);

    // mark progress
    $stmt = $pdo->prepare("
        INSERT INTO student_progress (student_id, step_key, is_done)
        VALUES (:id, 'resume', TRUE)
        ON CONFLICT (student_id, step_key)
        DO UPDATE SET is_done = TRUE
    ");

    $stmt->execute(['id' => $student_id]);

    header("Location: application-progress.php");
    exit;
}

/* =========================
   STEP 2: APPLICATION CHECKBOX
========================= */
if (isset($_POST['application_submitted'])) {

    $stmt = $pdo->prepare("
        INSERT INTO student_progress (student_id, step_key, is_done)
        VALUES (:id, 'application', TRUE)
        ON CONFLICT (student_id, step_key)
        DO UPDATE SET is_done = TRUE
    ");

    $stmt->execute(['id' => $student_id]);

    header("Location: application-progress.php");
    exit;
}
?>