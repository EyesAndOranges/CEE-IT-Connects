<?php
session_start();
require 'db.php';
require_once 'auth.php';
header('Content-Type: application/json');

$sender_id = $_SESSION['user_id'];
$sender_type = getUserType($_SESSION['role']);
$message_id = $_POST['message_id'] ?? null;

if (!$message_id) {
    echo json_encode(['success' => false, 'message' => 'No message specified.']);
    exit;
}

// Confirm ownership and grab the attachment path (if any) before deleting
$check = $pdo->prepare("
    SELECT attachment_path FROM messages
    WHERE id = ? AND sender_id = ? AND sender_type = ?
");
$check->execute([$message_id, $sender_id, $sender_type]);
$row = $check->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Message not found or not yours to delete.']);
    exit;
}

$stmt = $pdo->prepare("
    DELETE FROM messages
    WHERE id = ? AND sender_id = ? AND sender_type = ?
");
$stmt->execute([$message_id, $sender_id, $sender_type]);

if ($stmt->rowCount() > 0) {
    // Clean up the uploaded file from disk, if it existed
    if (!empty($row['attachment_path'])) {
        $filePath = __DIR__ . '/' . $row['attachment_path'];
        if (is_file($filePath)) {
            @unlink($filePath);
        }
    }
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Delete failed.']);
}