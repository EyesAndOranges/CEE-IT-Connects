<?php
session_start();
require 'db.php';

// only superadmin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $allowed = ['internship_admin', 'cma'];

    if (!in_array($role, $allowed)) {
        die("Invalid role");
    }

    $stmt = $pdo->prepare("
        INSERT INTO admins (name, email, password, role)
        VALUES (:name, :email, :password, :role)
    ");

    $stmt->execute([
        'name' => $name,
        'email' => $email,
        'password' => $password,
        'role' => $role
    ]);

    header("Location: superadmin.php?success=1");
    exit();
}
?>