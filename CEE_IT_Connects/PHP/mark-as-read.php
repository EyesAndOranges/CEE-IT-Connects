<?php
session_start();
require 'db.php';
require_once 'auth.php';

$roleMap = [
    'student' => 'student',
    'internship_adviser' => 'adviser',
    'HTE_adviser' => 'adviser',
    'adviser' => 'adviser',
    'internship_admin' => 'admin',
    'superadmin' => 'admin'
];
$userType = $roleMap[strtolower(trim($_SESSION['role']))] ?? 'student';

// Poll mode — just return unread count as JSON
if (isset($_GET['count_only'])) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM notifications
        WHERE user_id = ? AND user_type = ? AND is_read = FALSE
    ");
    $stmt->execute([$_SESSION['user_id'], $userType]);
    header('Content-Type: application/json');
    echo json_encode(['count' => (int)$stmt->fetchColumn()]);
    exit;
}

// Mark single notif as read
if (isset($_GET['id'])) {
    $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?")
        ->execute([$_GET['id'], $_SESSION['user_id']]);
    exit;
}

// Mark ALL as read (existing behavior)
$pdo->prepare("
    UPDATE notifications SET is_read = TRUE
    WHERE user_id = ? AND user_type = ? AND is_read = FALSE
")->execute([$_SESSION['user_id'], $userType]);




// changes added above for notif and ojt hours update

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