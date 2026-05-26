<?php
session_start();
require 'auth.php';
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$studentId     = (int)($_POST['student_id']      ?? 0);
$supName       = trim($_POST['supervisor_name']   ?? '');
$supEmail      = trim($_POST['supervisor_email']  ?? '');
$deptNote      = trim($_POST['department_note']   ?? '');

if (!$studentId || !$supName || !$supEmail) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}
if (!filter_var($supEmail, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

// Generate a secure token (used in the public eval URL)
$token = bin2hex(random_bytes(32));

try {
    // Upsert: insert or update if student already has a supervisor
    $stmt = $pdo->prepare("
        INSERT INTO student_supervisors
            (student_id, supervisor_name, supervisor_email, department_note, eval_token, updated_at)
        VALUES (?, ?, ?, ?, ?, NOW())
        ON CONFLICT (student_id)
        DO UPDATE SET
            supervisor_name  = EXCLUDED.supervisor_name,
            supervisor_email = EXCLUDED.supervisor_email,
            department_note  = EXCLUDED.department_note,
            eval_token       = CASE
                                   WHEN student_supervisors.eval_token IS NULL
                                   THEN EXCLUDED.eval_token
                                   ELSE student_supervisors.eval_token  -- keep existing token
                               END,
            updated_at       = NOW()
    ");
    $stmt->execute([$studentId, $supName, $supEmail, $deptNote, $token]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}