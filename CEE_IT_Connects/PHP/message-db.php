<?php
session_start();
require 'db.php';
require_once 'auth.php';

$sender_id = $_SESSION['user_id'];
$sender_type = getUserType($_SESSION['role']);

$receiver_id = $_POST['receiver_id'];
$receiver_type = getUserType($_POST['receiver_type']);
$message = $_POST['message'];

$stmt = $pdo->prepare("
    INSERT INTO messages 
    (sender_id, sender_type, receiver_id, receiver_type, message, created_at)
    VALUES (?, ?, ?, ?, ?, NOW())
");

$stmt->execute([
    $sender_id,
    $sender_type,
    $receiver_id,
    $receiver_type,
    $message
]);

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;