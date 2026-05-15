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

$form_type = $_POST['form_type'] ?? '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

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
            echo "<script>alert('Title, Company, Description, and Program are required.'); window.history.back();</script>";
            exit;
        }
        if (!in_array($program, $allowed_programs)) {
            echo "<script>alert('Invalid program selection.'); window.history.back();</script>";
            exit;
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
                INSERT INTO notifications (user_id, user_type, title, message, link, is_read, created_at)
                VALUES (?, 'student', ?, ?, ?, FALSE, NOW())
            ");

            // MESSAGE CONTENT
            $notifTitle = "New Internship Posted";
            $notifMessage = "$title at $company is now available. Apply now!";

            foreach ($students as $student) {
                $notifStmt->execute([
                    $student['id'],
                    $notifTitle,
                    $notifMessage,
                    'applied-internship-programs.php'
                ]);
            }

            $stmtAudit = $pdo->prepare("
                INSERT INTO audits (user_id, roles, activity)
                VALUES (:user_id, :roles, :activity)
            ");

            $stmtAudit->execute([
                ':user_id' => $admin_id,
                ':roles' => $_SESSION['role'],
                ':activity' => 'Posted a new internship: ' . $title . ' at ' . $company
            ]);
            header("Location: internship-ui.php?success=1");
            exit();
        } catch (PDOException $e) {
            echo "<script>alert('Database error: '); </script>" . $e->getMessage() . "<script>window.history.back();</script>";
            exit;
        }
    }
    if ($form_type === 'announcement_posting') {
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

            $stmtActivity = $pdo->prepare("INSERT INTO audits (user_id, roles, activity, activity_date) VALUES (:user_id, :roles, :activity, NOW())");
            $stmtActivity->execute([
                ':user_id' => $_SESSION['user_id'],
                ':roles' => $_SESSION['role'],
                ':activity' => 'Posted a new announcement: ' . $title
            ]);
        } catch (PDOException $e) {
            echo "<script>alert('Database error: '); </script>" . $e->getMessage() . "<script>window.history.back();</script>";
            exit;
        }

        $stmtStudents = $pdo->query("SELECT id FROM students");
        $students = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);

        $notifTitle = "New Announcement Posted";
        $notifMessage = "A new announcement has been posted: $title";
        $notifStmt = $pdo->prepare("
            INSERT INTO notifications (user_id, user_type, title, message, link, is_read, created_at)
            VALUES (?, 'student', ?, ?, ?, FALSE, NOW())
        ");

        foreach ($students as $student) {
            $notifStmt->execute([
                $student['id'],
                $notifTitle,
                $notifMessage,
                'announcement.php'
            ]);
        }

        $_SESSION['success'] = "New Announcement '$title' has been created.";
        header("Location: internship-ui.php?success=1");
        exit();
    }
    if ($form_type === 'send_feedback') {

        $bookmark_id = $_POST['bookmark_id'] ?? null;
        $feedback = trim($_POST['feedback'] ?? '');

        if (!$bookmark_id || !$feedback) {
            http_response_code(400);
            echo "Missing required fields.";
            exit;
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
            SELECT ib.student_id, i.title, i.company, s.full_name
            FROM internship_bookmarks ib
            JOIN internships i ON i.id = ib.internship_id
            JOIN students s ON s.id = ib.student_id
            WHERE ib.id = ?
        ");
            $stmt->execute([$bookmark_id]);
            $bookmark = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$bookmark) {
                throw new Exception("Bookmark not found.");
            }

            $notifTitle = "Internship Interested Update";
            $notifMessage = "Your interest in {$bookmark['title']} at {$bookmark['company']} was rejected. Feedback: $feedback";
            $notifStmt = $pdo->prepare("
            INSERT INTO notifications (user_id, user_type, title, message, link, is_read, created_at)
            VALUES (?, 'student', ?, ?, ?, FALSE, NOW())
            ");
            $notifStmt->execute([
                $bookmark['student_id'],
                $notifTitle,
                $notifMessage,
                'applied-internship-program.php'
            ]);


            $_SESSION['success'] = "Feedback to " . $bookmark['full_name'] . " has been sent.";
            $stmtDelete = $pdo->prepare("DELETE FROM internship_bookmarks WHERE id = ?");
            $stmtDelete->execute([$bookmark_id]);



            $stmtActivity = $pdo->prepare("INSERT INTO audits (user_id, roles, activity, activity_date) VALUES (:user_id, :roles, :activity, NOW())");
            $stmtActivity->execute([
                ':user_id' => $_SESSION['user_id'],
                ':roles' => $_SESSION['role'],
                ':activity' => 'Sent feedback for student ' . $bookmark['student_id'] .
                    ' regarding internship ' . $bookmark['title'] . ' at ' . $bookmark['company']
            ]);

            $pdo->commit();

            echo "success";
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo $e->getMessage();
            exit;
        }
    }



    /*$stmt = $pdo->prepare("
    DELETE FROM internship_bookmarks
    WHERE id = ?
");

    $stmt->execute([$bookmark_id]);

    header("Location: internship-ui.php?removed=1");
    exit;
} */

    if (isset($_POST['delete_announcement'])) {
        $title = $_POST['title'] ?? '';
        $announcement_id = $_POST['announcement_id'];

        $stmt = $pdo->prepare("
    DELETE FROM announcements
    WHERE id = ?
    ");
        $stmt->execute([$announcement_id]);

        $stmtActivity = $pdo->prepare("INSERT INTO audits (user_id, roles, activity, activity_date) VALUES (:user_id, :roles, :activity, NOW())");
        $stmtActivity->execute([
            ':user_id' => $_SESSION['user_id'],
            ':roles' => $_SESSION['role'],
            ':activity' => 'Deleted the announcement: ' . $title
        ]);

        $stmtStudents = $pdo->query("SELECT id FROM students");
        $students = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);

        $notifTitle = "Announcement Delete";
        $notifMessage = "The announcement {$_POST['title']} has been deleted.";
        $notifStmt = $pdo->prepare("
            INSERT INTO notifications (user_id, user_type, title, message, link, is_read, created_at)
            VALUES (?, 'student', ?, ?, ?, FALSE, NOW())
            ");

        foreach ($students as $student) {
            $notifStmt->execute([
                $student['id'],
                $notifTitle,
                $notifMessage,
                'announcement.php'
            ]);
        }

        $_SESSION['success'] = "Announcement " . $_POST['title'] . " has been deleted.";
        header("location: internship-ui.php?removed=1");
        exit;
    }

    if (isset($_POST['edit_announcement'])) {
        $title = $_POST['title'] ?? '';
        $announcement_id = $_POST['announcement_id'];

        $stmt = $pdo->prepare("UPDATE announcements SET title = ?, message = ?, category = ? WHERE id = ?");
        $stmt->execute([
            $_POST['title'] ?? '',
            $_POST['message'] ?? '',
            $_POST['category'] ?? '',
            $announcement_id
        ]);

        $stmtStudents = $pdo->query("SELECT id FROM students");
        $students = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);

        $notifTitle = "Announcement Update";
        $notifMessage = "The announcement {$_POST['title']} has been updated.";
        $notifStmt = $pdo->prepare("
            INSERT INTO notifications (user_id, user_type, title, message, link, is_read, created_at)
            VALUES (?, 'student', ?, ?, ?, FALSE, NOW())
            ");

        foreach ($students as $student) {
            $notifStmt->execute([
                $student['id'],
                $notifTitle,
                $notifMessage,
                'announcement.php'
            ]);
        }

        $stmtActivity = $pdo->prepare("INSERT INTO audits (user_id, roles, activity, activity_date) VALUES (:user_id, :roles, :activity, NOW())");
        $stmtActivity->execute([
            ':user_id' => $_SESSION['user_id'],
            ':roles' => $_SESSION['role'],
            ':activity' => 'Edited the announcement: ' . $title
        ]);

        $_SESSION['success'] = "Announcement " . $_POST['title'] . " has been updated succesfully!";
        header("location: internship-ui.php?updated=1");
        exit;
    }
}
?>