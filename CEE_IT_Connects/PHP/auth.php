<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login-ui.php");
    exit();
}

$user_id = $_SESSION['user_id'];
function getUserType($role)
{
    $role = strtolower(trim($role));

    $roleMap = [
        'student' => 'student',
        'internship_adviser' => 'adviser',
        'hte adviser' => 'adviser',
        'adviser' => 'adviser',
        'internship_admin' => 'admin',
        'superadmin' => 'admin'
    ];

    return $roleMap[$role] ?? 'student';
}
function getUserProfile($pdo, $id, $type)
{
    switch (strtolower($type)) {

        case 'student':
            $stmt = $pdo->prepare("SELECT full_name, email FROM students WHERE id=?");
            break;

        case 'adviser':
            $stmt = $pdo->prepare("SELECT full_name, email FROM advisers WHERE id=?");
            break;

        case 'admin':
            $stmt = $pdo->prepare("SELECT name AS full_name, email FROM admins WHERE id=?");
            break;

        default:
            return [
                'full_name' => 'Unknown',
                'email' => 'Unknown'
            ];
    }

    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>