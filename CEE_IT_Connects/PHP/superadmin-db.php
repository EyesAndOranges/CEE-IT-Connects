<?php
session_start();
require 'db.php';

// only superadmin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // for create admin and adviser, we will check if the delete button was clicked first to avoid conflicts
    //admin 
    if (isset($_POST['create-admin'])) {

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

    //adviser
    if (isset($_POST['create-adviser'])) {
        $id = $_POST['id'];
        $source = $_POST['source'];

        if ($source === 'advisers') {
            $stmt = $pdo->prepare("DELETE FROM advisers WHERE id = ?");
            $stmt->execute([$id]);
        }

        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        $title = $_POST['title'];

        $allowed = ['internship_adviser', 'HTE_adviser'];

        if (!in_array($role, $allowed)) {
            die("Invalid role");
        }

        $stmt = $pdo->prepare("
        INSERT INTO advisers (full_name, email, password_hash, role, title, created_at)
        VALUES (:name, :email, :password, :role, :title, NOW())
        ");

        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
            'title' => $title
        ]);

        header("Location: superadmin.php?success=1");
        exit();
    }

    if (isset($_POST['delete'])) {
        $id = $_POST['id'];
        $source = $_POST['source'];

        $allowedTables = ['students', 'admins', 'advisers'];
        if (!in_array($source, $allowedTables)) {
            die('invalid table');
        }

        if ($source === 'admins') {
            $checkstmt = $pdo->prepare("SELECT role FROM admins WHERE id = ?");
            $checkstmt->execute([$id]);
            $user = $checkstmt->fetch();

            if ($user && $user['role'] === 'superadmin') {
                die("Cannot delete another superadmin");
            }
        }

        $stmt = $pdo->prepare("DELETE FROM $source WHERE id = ?");
        $stmt->execute([$id]);

        header("Location: superadmin.php?deleted=1");
        exit();
    }
}

?>