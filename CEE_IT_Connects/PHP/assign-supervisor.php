<?php
session_start();
require 'auth.php';
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$studentId = (int) ($_POST['student_id'] ?? 0);
$supName = trim($_POST['supervisor_name'] ?? '');
$supEmail = trim($_POST['supervisor_email'] ?? '');
$deptNote = trim($_POST['department_note'] ?? '');

if (!$studentId || !$supName || !$supEmail) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}
if (!filter_var($supEmail, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

$token = bin2hex(random_bytes(32));

try {
    // Check if a supervisor already exists for this student
    $check = $pdo->prepare("SELECT supervisor_email FROM student_supervisors WHERE student_id = ?");
    $check->execute([$studentId]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Email changed → reset eval_sent_at so it can resend
        $emailChanged = $existing['supervisor_email'] !== $supEmail;

        $stmt = $pdo->prepare("
            UPDATE student_supervisors 
            SET supervisor_name   = ?,
                supervisor_email  = ?,
                department_note   = ?,
                eval_sent_at      = " . ($emailChanged ? 'NULL' : 'eval_sent_at') . "
            WHERE student_id = ?
        ");
        $stmt->execute([$supName, $supEmail, $deptNote, $studentId]);

    } else {
        $stmt = $pdo->prepare("
            INSERT INTO student_supervisors 
                (student_id, supervisor_name, supervisor_email, department_note, eval_token)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$studentId, $supName, $supEmail, $deptNote, $token]);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}