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
            $stmt = $pdo->prepare("INSERT INTO announcements (title, message) VALUES (:title, :message)");
            $stmt->execute([
                'title' => $title,
                'message' => $message
            ]);
        } catch (PDOException $e) {
            die("Database error: " . $e->getMessage());
        }

        header("Location: internship-ui.php?success=1");
        exit();
    } else {
        die("Invalid form submission.");
    }
}



?>