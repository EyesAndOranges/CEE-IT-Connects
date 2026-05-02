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
        $duration = $_POST['year'] ?? '';
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

        $admin_id = $_SESSION['user_id'];
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
                "INSERT INTO internships (title, company, duration, email, location, description, program, latitude, longtitude, phone_numbers, deadline, available, time_open, time_close, admin_id) 
                VALUES (:title, :company, :duration, :email, :location, :description, :program, :latitude, :longtitude, :phonenumber, :deadline, :available, :openTime, :closeTime, :admin_id)"
            );

            $stmt->execute([
                'title' => $title,
                'company' => $company,
                'duration' => $duration,
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
                'closeTime' => $closeTime,
                'admin_id' => $admin_id
            ]);

            // This is for notifying students about the new internship posting
            $stmtStudents = $pdo->query("SELECT id FROM students");
            $students = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);

            $notifStmt = $pdo->prepare("
                INSERT INTO notifications (user_id, user_type, title, message, is_read, created_at)
                VALUES (?, 'student', ?, ?, FALSE, NOW())
            ");

            // MESSAGE CONTENT
            $notifTitle = "New Internship Posted";
            $notifMessage = "$title at $company is now available. Apply now!";

            foreach ($students as $student) {
                $notifStmt->execute([
                    $student['id'],
                    $notifTitle,
                    $notifMessage
                ]);
            }

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
    if (isset($_POST['edit_announcement'])) {
        $announcement_id = $_POST['announcement_id'];

        $stmt = $pdo->prepare("UPDATE announcements SET title = ?, message = ?, category = ? WHERE id = ?");
        $stmt->execute([
            $_POST['title'] ?? '',
            $_POST['message'] ?? '',
            $_POST['category'] ?? '',
            $announcement_id
        ]);

        header("location: internship-ui.php?updated=1");
        exit;
    }
}





?>