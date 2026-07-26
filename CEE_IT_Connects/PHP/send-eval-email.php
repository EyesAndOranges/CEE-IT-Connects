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
if (!$studentId) {
  echo json_encode(['success' => false, 'message' => 'Missing student_id']);
  exit;
}

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

$baseUrl = rtrim((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'], '/');
$evalUrl = $baseUrl . '/supervisor-eval-form.php?token=' . urlencode($row['eval_token']);

$to = $row['supervisor_email'];
$supName = $row['supervisor_name'];
$stuName = $row['student_name'];
$subject = "Student Intern Evaluation Request — {$stuName}";

// ── PDF attachment ──
$pdfPath = realpath(__DIR__ . '/../Sources/forms/CEIT-OJTF-010_Supervisors_Evaluation_of_Student_Intern.pdf');

if (!$pdfPath || !file_exists($pdfPath)) {
  echo json_encode(['success' => false, 'message' => 'PDF form not found. Path: ' . __DIR__ . '/../Sources/forms/CEIT-OJTF-010_Supervisors_Evaluation_of_Student_Intern.pdf']);
  exit;
}

$pdfFilename = 'CEIT-OJTF-010_Supervisors_Evaluation.pdf';

// ── HTML email body ──
// $htmlBody = <<<HTML
// <!DOCTYPE html>
// <html>
// <body style="font-family:Arial,sans-serif;background:#f0f2f7;padding:30px;">
//   <div style="max-width:560px;margin:auto;background:#fff;border-radius:12px;
//               overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08);">
//     <div style="background:linear-gradient(135deg,#065f46,#047857);padding:24px 28px;color:#fff;">
//       <div style="font-size:11px;opacity:.7;margin-bottom:4px;">CEIT-OJTF-010 · PLV</div>
//       <h2 style="margin:0;font-size:1.2rem;">Supervisor's Evaluation of Student Intern</h2>
//     </div>
//     <div style="padding:28px 32px;">
//       <p>Dear <strong>{$supName}</strong>,</p>
//       <p>
//         You have been requested to evaluate student intern
//         <strong>{$stuName}</strong> from the
//         <strong>PLV College of Engineering and Information Technology</strong>.
//       </p>
//       <p>
//         The official evaluation form (CEIT-OJTF-010) is attached to this email as a PDF.
//         Please print, fill out, and return it in a sealed envelope to the OJT Coordinator.
//       </p>
//       <p>You may also fill out the form online by clicking the button below:</p>
//       <div style="text-align:center;margin:28px 0;">
//         <a href="{$evalUrl}"
//            style="background:#065f46;color:#fff;padding:14px 32px;border-radius:8px;
//                   text-decoration:none;font-weight:700;font-size:15px;display:inline-block;">
//           Fill Out Evaluation Online
//         </a>
//       </div>
//       <p style="font-size:12px;color:#888;">
//         Or copy this link: <a href="{$evalUrl}" style="color:#065f46;">{$evalUrl}</a>
//       </p>
//       <p style="font-size:12px;color:#aaa;border-top:1px solid #eee;padding-top:16px;">
//         This link is unique to this student. Please do not share it.
//         If you have questions, contact the OJT Coordinator at PLV CEIT.
//       </p>
//     </div>
//   </div>
// </body>
// </html>
// HTML

// ── Load PHPMailer ──
require __DIR__ . '/../phpmailer-master/src/PHPMailer.php';
require __DIR__ . '/../phpmailer-master/src/SMTP.php';
require __DIR__ . '/../phpmailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
  $mail->isSMTP();
  $mail->Host = 'smtp.gmail.com';
  $mail->SMTPAuth = true;
  $mail->Username = 'jamesherold25@gmail.com';   // ← replace with your Gmail
  $mail->Password = 'vyfc kawx ctvz cwqf';      // ← replace with your Gmail App Password
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  $mail->Port = 587;

  $mail->setFrom('jamesherold25@gmail.com', 'PLV OJT System');
  $mail->addAddress($to, $supName);

  $mail->Subject = $subject;
  $mail->isHTML(true);
  $mail->Body = $htmlBody;

  // Attach the PDF
  $mail->addAttachment($pdfPath, $pdfFilename);

  $mail->send();

  // Mark email as sent in DB
  $upd = $pdo->prepare("UPDATE student_supervisors SET eval_sent_at = NOW() WHERE student_id = ?");
  $upd->execute([$studentId]);

  echo json_encode(['success' => true]);

} catch (Exception $e) {
  file_put_contents(
    __DIR__ . '/mail_debug.log',
    date('Y-m-d H:i:s') . " | StudentID: $studentId | Error: " . $mail->ErrorInfo . "\n",
    FILE_APPEND
  );
  echo json_encode(['success' => false, 'message' => $mail->ErrorInfo]);
}