<?php
session_start();
require 'db.php';
require 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['room_id'])) {
    $stmt = $pdo->prepare("UPDATE rooms SET is_archived = TRUE WHERE id = :id");
    $stmt->execute([':id' => $_POST['room_id']]);
}

header("Location: ojt-rooms.php");
exit();