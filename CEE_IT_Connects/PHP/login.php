<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // STUDENT LOGIN
    if ($role === 'student') {

        $stmt = $pdo->prepare("SELECT * FROM students WHERE email = :email");
        $stmt->execute(['email' => $email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = 'student';

            header("Location: ../PHP/index.php");
            exit;

        } else {
            echo "<script>alert('Invalid student credentials!'); window.history.back();</script>";
            exit;
        }
    }

    // ADMIN LOGIN
    elseif ($role === 'admin') {

        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = :email");
        $stmt->execute(['email' => $email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo "<script>alert('Admin not found!'); window.history.back();</script>";
            exit;
        }

        if (empty($user['role'])) {
            echo "<script>alert('No role assigned. Contact superadmin.'); window.history.back();</script>";
            exit;
        }

        if (password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            // centralized redirect (cleaner)
            $dashboards = [
                'superadmin' => '../PHP/superadmin.php',
                'internship_admin' => '../PHP/internship-ui.php',
                'cma' => '../PHP/bsbsasdadw.php'
            ];

            header("Location: " . ($dashboards[$user['role']] ?? 'no-access.php'));
            exit;

        } else {
            echo "<script>alert('Invalid admin password!'); window.history.back();</script>";
            exit;
        }
    }

    // ADVISER LOGIN
    elseif ($role === 'adviser') {

        $stmt = $pdo->prepare("SELECT * FROM advisers WHERE email = :email");
        $stmt->execute(['email' => $email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo "<script>alert('Adviser not found!'); window.history.back();</script>";
            exit;
        }

        if (password_verify($password, $user['password_hash'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = 'adviser';

            header("Location: ../PHP/index.php");
            exit;

        } else {
            echo "<script>alert('Invalid adviser credentials!'); window.history.back();</script>";
            exit;
        }
    }
}
?>