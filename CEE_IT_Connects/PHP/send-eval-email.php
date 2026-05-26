<?php
session_start();
require 'auth.php';
require 'db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$studentId = (int)($_POST['student_id'] ?? 0);
if (!$studentId) {
    echo json_encode(['success' => false, 'message' => 'Missing student_id']);
    exit;
}

// Fetch supervisor assignment + student info
$stmt = $pdo->prepare("
    SELECT ss.supervisor_name, ss.supervisor_email, ss.eval_token,
           s.full_name AS student_name
    FROM student_supervisors ss
    JOIN students s ON s.id = ss.student_id
    WHERE ss.student_id = ?
");
$stmt->execute([$studentId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Supervisor not assigned']);
    exit;
}

// Build the public evaluation URL
$baseUrl = rtrim((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'], '/');
$evalUrl = $baseUrl . '/Students/CEE-IT-Connects/CEE_IT_Connects/PHP/supervisor-eval-form.php?token=' . urlencode($row['eval_token']);

$mail = new PHPMailer(true);

try {
    // SMTP config
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'youngtopian@gmail.com';
    $mail->Password   = 'dkmdblixyiálypmln';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Sender & recipient
    $mail->setFrom('youngtopian@gmail.com', 'PLV OJT System');
    $mail->addAddress($row['supervisor_email'], $row['supervisor_name']);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Student Intern Evaluation Request — ' . $row['student_name'];
    $mail->Body    = '
<!DOCTYPE html>
<html>
<body style="font-family:Arial,sans-serif;background:#f0f2f7;padding:30px;">
  <div style="max-width:560px;margin:auto;background:#fff;border-radius:12px;
              overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08);">
    <div style="background:linear-gradient(135deg,#065f46,#047857);padding:24px 28px;color:#fff;">
      <div style="font-size:11px;opacity:.7;margin-bottom:4px;">CEIT-OJTF-010 · PLV</div>
      <h2 style="margin:0;font-size:1.2rem;">Supervisor\'s Evaluation of Student Intern</h2>
    </div>
    <div style="padding:28px 32px;">
      <p>Dear <strong>' . htmlspecialchars($row['supervisor_name']) . '</strong>,</p>
      <p>
        You have been requested to evaluate student intern
        <strong>' . htmlspecialchars($row['student_name']) . '</strong> from the
        <strong>PLV College of Engineering and Information Technology</strong>.
      </p>
      <p>Please click the button below to fill out the evaluation form:</p>
      <div style="text-align:center;margin:28px 0;">
        <a href="' . $evalUrl . '"
           style="background:#065f46;color:#fff;padding:14px 32px;border-radius:8px;
                  text-decoration:none;font-weight:700;font-size:15px;display:inline-block;">
          Fill Out Evaluation Form
        </a>
      </div>
      <p style="font-size:12px;color:#888;">
        Or copy this link: <a href="' . $evalUrl . '" style="color:#065f46;">' . $evalUrl . '</a>
      </p>
      <p style="font-size:12px;color:#aaa;border-top:1px solid #eee;padding-top:16px;">
        This link is unique to this student. Please do not share it.
        If you have questions, contact the OJT Coordinator at PLV CEIT.
      </p>
    </div>
  </div>
</body>
</html>';

    $mail->send();

    // Record send timestamp
    $upd = $pdo->prepare("UPDATE student_supervisors SET eval_sent_at = NOW() WHERE student_id = ?");
    $upd->execute([$studentId]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Mailer error: ' . $mail->ErrorInfo]);
}