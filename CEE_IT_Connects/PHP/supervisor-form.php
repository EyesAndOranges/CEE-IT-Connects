<?php
require 'db.php';

$token = trim($_GET['token'] ?? '');
if (!$token) {
    http_response_code(404);
    die('Invalid link.');
}

$stmt = $pdo->prepare("
    SELECT ss.student_id, ss.supervisor_name, ss.supervisor_email,
           s.full_name AS student_name
    FROM student_supervisors ss
    JOIN students s ON s.id = ss.student_id
    WHERE ss.eval_token = ?
");
$stmt->execute([$token]);
$sup = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sup) {
    http_response_code(404);
    die('Evaluation link not found or already used.');
}

// Check if already submitted
$check = $pdo->prepare("SELECT id FROM ojt_evaluations_supervisor WHERE student_id = ?");
$check->execute([$sup['student_id']]);
if ($check->fetchColumn()) {
    die('<p style="font-family:sans-serif;text-align:center;margin-top:80px;">
        Evaluation already submitted. Thank you!</p>');
}

// ── Render the same form as the modal, but as a standalone page ──
// Include the full HTML form here (copy from the modal-body contents)
// and POST to: ojt-evaluation-submit.php
// with hidden fields: action=submit_supervisor_evaluation, student_id, eval_token
?>