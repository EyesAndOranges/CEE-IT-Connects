<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    exit();
}

$user_id = $_SESSION['user_id'];

// Mark as read
$stmt = $pdo->prepare("
    UPDATE notifications
    SET is_read = TRUE
    WHERE user_id = ?
");
$stmt->execute([$user_id]);

echo "success";
?>