<?php
session_start();
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'internship_admin') {
    header("Location: login-ui.php?role=admin");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: internship-ui.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $form_type = $_POST['form_type'] ?? '';

    if ($form_type === 'internship_posting') {
        // Handle internship posting
        $title = $_POST['title'] ?? '';
        $company = $_POST['company'] ?? '';
        $email = $_POST['email'] ?? '';
        $location = $_POST['location'] ?? '';
        $description = $_POST['description'] ?? '';
        $program = $_POST['program'] ?? '';
        $latitude = $_POST['latitude'] ?? null;
        $longitude = $_POST['longitude'] ?? null;
        $phonenumber = $_POST['phonenumber'] ?? '';
        $deadline = $_POST['deadline'] ?? null;
        $openTime = $_POST['openTime'] ?? null;
        $closeTime = $_POST['closeTime'] ?? null;

        $available = 'true';
        $allowed_programs = ['Information Technology', 'Civil Engineering', 'Electrical Engineering'];

        if (empty($title) || empty($company) || empty($description) || empty($program)) {
            die("Title, Company, Description, and Program are required.");
        }
        if (!in_array($program, $allowed_programs)) {
            die("Invalid program selection.");
        }


        try {
            $stmt = $pdo->prepare(
                "INSERT INTO internships (title, company, email, location, description, program, latitude, longtitude, phone_numbers, deadline, available, time_open, time_close) 
        VALUES (:title, :company, :email, :location, :description, :program, :latitude, :longtitude, :phonenumber, :deadline, :available, :openTime, :closeTime)"
            );

            $stmt->execute([
                'title' => $title,
                'company' => $company,
                'email' => $email,
                'location' => $location,
                'description' => $description,
                'program' => $program,
                'latitude' => $latitude,
                'longtitude' => $longitude,
                'phonenumber' => $phonenumber,
                'deadline' => $deadline,
                'available' => $available,
                'openTime' => $openTime,
                'closeTime' => $closeTime
            ]);

            header("Location: internship-ui.php?success=1");
            exit();
        } catch (PDOException $e) {
            die("Database error: " . $e->getMessage());
        }
    } elseif ($form_type === 'announcement_posting') {
        // Handle announcement posting
        $title = $_POST['title'] ?? '';
        $message = $_POST['message'] ?? '';
        try {
            $stmt = $pdo->prepare("INSERT INTO announcements (title, message, created_at, category) VALUES (:title, :message, NOW(), :category)");
            $stmt->execute([
                'title' => $title,
                'message' => $message,
                'category' => $_POST['category'] ?? ''
            ]);
        } catch (PDOException $e) {
            die("Database error: " . $e->getMessage());
        }

        header("Location: internship-ui.php?success=1");
        exit();
    }

    //for bookmark reject
    if (isset($_POST['reject'])) {

        $bookmark_id = $_POST['bookmark_id'];

        $stmt = $pdo->prepare("
        DELETE FROM internship_bookmarks
        WHERE id = ?
    ");

        $stmt->execute([$bookmark_id]);

        header("Location: internship-ui.php?removed=1");
        exit;
    }

    if (isset($_POST['delete_announcement'])) {
        $announcement_id = $_POST['announcement_id'];

        $stmt = $pdo->prepare("
        DELETE FROM announcements
        WHERE id = ?
        ");
        $stmt->execute([$announcement_id]);

        header("location: internship-ui.php?removed=1");
        exit;
    }
}





?>