<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    exit("Unauthorized");
}

$room_id = $_POST['room_id'];
$users = json_decode($_POST['users'], true);

if (!$users || !is_array($users)) {
    exit("Invalid data");
}

$check = $pdo->prepare("
    SELECT 1 FROM room_members 
    WHERE room_id = ? AND user_id = ? AND user_type = ?
");

$insert = $pdo->prepare("
    INSERT INTO room_members (room_id, user_id, user_type)
    VALUES (?, ?, ?)
");

foreach ($users as $user) {

    $user_id = $user['id'];
    $user_type = $user['type'];

    // prevent duplicate insert
    $check->execute([$room_id, $user_id, $user_type]);

    if (!$check->fetch()) {
        $insert->execute([$room_id, $user_id, $user_type]);
    }
}

echo "success";
?>