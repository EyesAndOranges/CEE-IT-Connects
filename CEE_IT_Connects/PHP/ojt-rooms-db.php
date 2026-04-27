<?php
session_start();
require 'db.php';
require_once 'auth.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'internship_adviser') {
    echo '<script>alert("You are not authorized to access this page.");</script>';
    header("Location: login-ui.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $room_name = trim($_POST['room_name']);
    $adviser_id = $_SESSION['user_id'];
    $section = $_POST['section'];

    //to check if the user has a brain and actually inputted the correct shiz
    if (!preg_match('/^[0-9]+-[0-9]+$/', $section)) {
        die("Invalid section format. Use format like 3-4.");
    }

    $room_code = generateUniqueRoomCode($pdo);
    // For Create Room
    $stmt = $pdo->prepare("
        INSERT INTO rooms (room_name, room_code, adviser_id)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$room_name, $room_code, $adviser_id]);

    $room_id = $pdo->lastInsertId();

    // for adding creator to the room
    $stmt = $pdo->prepare("
        INSERT INTO room_members (room_id, user_id)
        VALUES (?, ?)
    ");
    $stmt->execute([$room_id, $user_id]);

    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}
function generateRoomCode($length = 6)
{
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';

    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, strlen($characters) - 1)];
    }

    return $code;
}

function generateUniqueRoomCode($pdo)
{
    do {
        $code = generateRoomCode(9);

        $stmt = $pdo->prepare("SELECT id FROM rooms WHERE room_code = ?");
        $stmt->execute([$code]);

        $exists = $stmt->fetch();

    } while ($exists);

    return $code;
}
?>