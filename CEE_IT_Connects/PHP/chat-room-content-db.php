<?php
session_start();
require 'db.php';
require 'chat-room-content.php';

if (!isset($_SESSION['user_id'])) {
    exit("Unauthorized");
}

$room_id = $_POST['room_id'];

// ADD PARTICIPANT ──────────────────────────────────────────
if (isset($_POST['add_participant'])) {
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
        $check->execute([$room_id, $user['id'], $user['type']]);
        if (!$check->fetch()) {
            $insert->execute([$room_id, $user['id'], $user['type']]);
        }
    }

    echo "success";
    exit;
}

// POST ANNOUNCEMENT ────────────────────────────────────────
if (isset($_POST['post_announcement'])) {

    // only advisers can post
    if ($_SESSION['role'] !== 'internship_adviser') {
        http_response_code(403);
        exit("Unauthorized");
    }

    $content = trim($_POST['content'] ?? '');
    if ($content === '') {
        exit("Content is empty");
    }

    // fetch adviser name
    $stmt = $pdo->prepare("SELECT full_name FROM advisers WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $adviser = $stmt->fetch(PDO::FETCH_ASSOC); // BUG FIX: was $userId->fetch()

    $stmt = $pdo->prepare("
        INSERT INTO room_posts (room_id, sender_name, sender_role, content, created_at)
        VALUES (:room_id, :sender_name, :sender_role, :content, NOW())
    ");
    $stmt->execute([
        'room_id'     => $room_id,
        'sender_name' => $adviser['full_name'],
        'sender_role' => $_SESSION['role'], // BUG FIX: was $_SESSION['user_id']
        'content'     => $content,
    ]);

    // redirect back to updates tab
    header("Location: chat-room-content.php?room_id={$room_id}&tab=updates");
    exit;
}