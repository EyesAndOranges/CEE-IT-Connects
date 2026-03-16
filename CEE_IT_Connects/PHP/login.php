<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Select table based on role
    if ($role === 'admin') {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = :email");
    } elseif ($role === 'student') {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE email = :email");
    } else {
        die("Invalid role selected.");
    }

    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {

    $valid = false;

    if ($role === 'admin') {
        // temporary
        $valid = ($password === $user['password']);
    }

    if ($role === 'student') {
        $valid = password_verify($password, $user['password_hash']);
    }

    if ($valid) {
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role'] = $role;

        header("Location: ../PHP/index.php");
        exit;
    } else {
        echo "<script>alert('Incorrect password!'); window.history.back();</script>";
    }
    } else {
        echo "<script>alert('Email not found!'); window.history.back();</script>";
    }

}
?>
