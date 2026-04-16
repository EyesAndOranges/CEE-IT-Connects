<?php
session_start();
require_once 'db.php';

if (isset($_SESSION['role']) && $_SESSION['role'] === 'internship_admin') {
    header("Location: internship-ui.php");
    exit();
} else {
    header("Location: login-ui.php?role=admin");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = $_POST['title'];
    $company = $_POST['company'];
    $email = $_POST['email'];
    $location = $_POST['location'];
    $description = $_POST['description'];
    $program = $_POST['program'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
}
?>