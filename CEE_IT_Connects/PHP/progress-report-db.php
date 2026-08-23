<?php

require 'auth.php';
require 'db.php';

$student_id = (int) ($_SESSION['user_id'] ?? 0);
$current_room_id = $_POST['room_id'] ?? null;

if (!$student_id) {
    $_SESSION['error'] = 'You must be logged in.';
    header('Location: login-ui.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: message.php?section=progress_report&room_id=' . urlencode($current_room_id));
    exit;
}

$week_number = (int) ($_POST['week_number'] ?? 0);

if (!$week_number) {
    $_SESSION['error'] = 'Please enter a valid week number.';
    header('Location: message.php?section=progress_report&room_id=' . urlencode($current_room_id));
    exit;
}

if (
    !isset($_FILES['report_file']) ||
    empty($_FILES['report_file']['tmp_name'])
) {
    $_SESSION['error'] = 'Please attach your report file.';
    header('Location: message.php?section=progress_report&room_id=' . urlencode($current_room_id));
    exit;
}

$file = $_FILES['report_file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error'] = 'Upload error. Please try again.';
    header('Location: message.php?section=progress_report&room_id=' . urlencode($current_room_id));
    exit;
}

$max_size = 10 * 1024 * 1024; // 10 MB

if ($file['size'] > $max_size) {
    $_SESSION['error'] = 'File must be under 10MB.';
    header('Location: message.php?section=progress_report&room_id=' . urlencode($current_room_id));
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime_type = $finfo->file($file['tmp_name']);

$allowed_types = [
    'application/pdf' => 'pdf',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
];

if (!isset($allowed_types[$mime_type])) {
    $_SESSION['error'] = 'Only PDF or DOCX files are allowed.';
    header('Location: message.php?section=progress_report&room_id=' . urlencode($current_room_id));
    exit;
}

$extension = $allowed_types[$mime_type];

$upload_dir = __DIR__ . '/uploads/weekly_reports/';

if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0775, true)) {
        $_SESSION['error'] = 'Unable to create upload directory.';
        header('Location: message.php?section=progress_report&room_id=' . urlencode($current_room_id));
        exit;
    }
}

$filename =
    $student_id .
    '_week' .
    $week_number .
    '_' .
    bin2hex(random_bytes(8)) .
    '.' .
    $extension;

$destination = $upload_dir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    $_SESSION['error'] = 'File upload failed. Please try again.';
    header('Location: message.php?section=progress_report&room_id=' . urlencode($current_room_id));
    exit;
}

$wr_filepath = 'uploads/weekly_reports/' . $filename;

try {

    $insert = $pdo->prepare("
        INSERT INTO weekly_reports
            (student_id, week_number, wr_filepath, created_at)
        VALUES
            (?, ?, ?, CURRENT_DATE)
    ");

    $insert->execute([
        $student_id,
        $week_number,
        $wr_filepath
    ]);

    $_SESSION['success'] = 'Weekly report submitted successfully.';

} catch (PDOException $e) {

    if (file_exists($destination)) {
        unlink($destination);
    }

    $_SESSION['error'] = 'Unable to save your report. Please try again.';
}

header('Location: message.php?section=progress_report&room_id=' . urlencode($current_room_id));
exit;