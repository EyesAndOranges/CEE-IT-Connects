<?php
session_start();
require 'db.php';
require 'auth.php';

header('Content-Type: application/json');

$query = trim($_GET['q'] ?? '');
$currentId = $_SESSION['user_id'];
$currentType = getUserType($_SESSION['role']);

if (strlen($query) < 1) {
    echo json_encode([]);
    exit;
}

$results = [];
$like = "%$query%";

// Search students
$stmt = $pdo->prepare("
    SELECT id, full_name, 'student' AS type 
    FROM students 
    WHERE full_name ILIKE ? 
    AND NOT (id = ? AND ? = 'student')
    LIMIT 20
");
$stmt->execute([$like, $currentId, $currentType]);
$results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));

// Search advisers
$stmt = $pdo->prepare("
    SELECT id, full_name, 'adviser' AS type 
    FROM advisers 
    WHERE full_name ILIKE ? 
    AND NOT (id = ? AND ? = 'adviser')
    LIMIT 20
");
$stmt->execute([$like, $currentId, $currentType]);
$results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));

echo json_encode(array_values($results));