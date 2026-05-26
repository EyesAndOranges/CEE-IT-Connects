<?php
session_start();
require 'db.php';

// only superadmin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    $_SESSION['error'] = "Invalid role. Please log in using a system admin account.";
    header("Location: login-ui.php");
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
        $newAdminId = $pdo->lastInsertId();

        $notifStmt = $pdo->prepare("
            INSERT INTO notifications (user_id, user_type, title, message, link, is_read, created_at)
            VALUES (?, 'admin', ?, ?, ?, FALSE, NOW())
        ");

        $notifStmt->execute([
            $newAdminId,
            'Welcome to CEE IT Connects',
            'Your admin account has been created. Role: ' . $role . '.',
            'internship-ui.php'
        ]);

        $allAdmins = $pdo->query("SELECT id FROM admins WHERE id != " . $newAdminId)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($allAdmins as $admin) {
            $notifStmt->execute([
                $admin['id'],
                'New Admin Account Created',
                'A new ' . $role . ' account has been created for ' . $name . '.',
                'superadmin.php'
            ]);
        }

        $stmtActivity = $pdo->prepare("INSERT INTO audits (user_id, roles, activity, activity_date) VALUES (:user_id, :roles, :activity, NOW())");
        $stmtActivity->execute([
            ':user_id' => $_SESSION['user_id'],
            ':roles' => $_SESSION['role'],
            ':activity' => 'Created new admin: ' . $name
        ]);

        $_SESSION['success'] = "Successfully created an account.";
        header("Location: superadmin.php?success=1");
        exit();
    }

    //adviser
    if (isset($_POST['create-adviser'])) {
        $id = $_POST['id'];
        $source = $_POST['source'];
        $internship_id = null;

        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        $title = $_POST['title'];

        if ($role === 'HTE_adviser') {
            $internship_id = $_POST['internship_id'] ?? null;

            if (!$internship_id) {
                die("Please select an internship.");
            }
        }


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

        $newAdviserId = $pdo->lastInsertId();
        $notifStmt = $pdo->prepare("
            INSERT INTO notifications (user_id, user_type, title, message, link, is_read, created_at)
            VALUES (?, 'adviser', ?, ?, ?, FALSE, NOW())
        ");
        if ($role === 'HTE_adviser') {
            $notifStmt->execute([
                $newAdviserId,
                'Welcome to CEE IT Connects!',
                'Your adviser account has been created. Role: ' . $role . '.',
                'hte-ui.php'
            ]);
        } else {
            $notifStmt->execute([
                $newAdviserId,
                'Welcome to CEE IT Connects!',
                'Your adviser account has been created. Role: ' . $role . '.',
                'ojt-rooms.php'
            ]);
        }

        $adminNotif = $pdo->prepare("
            INSERT INTO notifications (user_id, user_type, title, message, link, is_read, created_at)
            VALUES (?, 'admin', ?, ?, ?, FALSE, NOW())
        ");
        $allAdmins = $pdo->query("SELECT id FROM admins WHERE role = 'superadmin'")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($allAdmins as $admin) {
            $adminNotif->execute([
                $admin['id'],
                'New Adviser Account Created',
                'A new ' . $role . ' account has been created for ' . $name . '.',
                'superadmin.php'
            ]);
        }

        $allAdmins = $pdo->query("SELECT id FROM admins WHERE role = 'internship_admin'")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($allAdmins as $admin) {
            $adminNotif->execute([
                $admin['id'],
                'New Adviser Account Created',
                'A new ' . $role . ' account has been created for ' . $name . '.',
                'intership-ui.php'
            ]);
        }

        $stmtActivity = $pdo->prepare("INSERT INTO audits (user_id, roles, activity, activity_date) VALUES (:user_id, :roles, :activity, NOW())");
        $stmtActivity->execute([
            ':user_id' => $_SESSION['user_id'],
            ':roles' => $_SESSION['role'],
            ':activity' => 'Created new adviser: ' . $name
        ]);

        $_SESSION['success'] = "Adviser successfully created!";
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
        if ($source === 'admins') {
            $fetchStmt = $pdo->prepare("SELECT name, role FROM admins WHERE id = ?");
        } elseif ($source === 'advisers') {
            $fetchStmt = $pdo->prepare("SELECT full_name AS name, role FROM advisers WHERE id = ?");
        } elseif ($source === 'students') {
            $fetchStmt = $pdo->prepare("SELECT full_name AS name, 'student' AS role FROM students WHERE id = ?");
            $cleanStmt = $pdo->prepare("DELETE FROM room_members WHERE user_id = ?");
            $cleanStmt->execute([$id]);
        }

        $fetchStmt->execute([$id]);
        $deletedUser = $fetchStmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("DELETE FROM $source WHERE id = ?");
        $stmt->execute([$id]);

        $stmtActivity = $pdo->prepare("INSERT INTO audits (user_id, roles, activity, activity_date) VALUES (:user_id, :roles, :activity, NOW())");
        $stmtActivity->execute([
            ':user_id' => $_SESSION['user_id'],
            ':roles' => $_SESSION['role'],
            ':activity' => 'Deleted ' . $deletedUser['role'] . ' account: ' . $deletedUser['name']
        ]);

        // Notify superadmin(s)
        $superAdmins = $pdo->query("SELECT id FROM admins WHERE role = 'superadmin'")->fetchAll(PDO::FETCH_ASSOC);
        $superNotif = $pdo->prepare("
            INSERT INTO notifications (user_id, user_type, title, message, link, is_read, created_at)
            VALUES (?, 'admin', ?, ?, ?, FALSE, NOW())
        ");
        foreach ($superAdmins as $admin) {
            $superNotif->execute([
                $admin['id'],
                'Account Deleted',
                'The ' . $deletedUser['role'] . ' account of ' . $deletedUser['name'] . ' has been deleted.',
                'superadmin.php'
            ]);
        }

        // Notify internship_admin(s)
        $internshipAdmins = $pdo->query("SELECT id FROM admins WHERE role = 'internship_admin'")->fetchAll(PDO::FETCH_ASSOC);
        $internshipNotif = $pdo->prepare("
            INSERT INTO notifications (user_id, user_type, title, message, link, is_read, created_at)
            VALUES (?, 'admin', ?, ?, ?, FALSE, NOW())
        ");
        foreach ($internshipAdmins as $admin) {
            $internshipNotif->execute([
                $admin['id'],
                'Account Deleted',
                'The ' . $deletedUser['role'] . ' account of ' . $deletedUser['name'] . ' has been removed from the system.',
                'internship-ui.php'
            ]);
        }

        $_SESSION['success'] = "User " . $deletedUser['name'] . " has been deleted.";
        header("Location: superadmin.php?deleted=1");
        exit();
    }

    // update required hours for CE/EE/IT programs
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_program_hours'])) {
        $allowed = ['CE', 'EE', 'IT'];
        $stmt = $pdo->prepare("
            UPDATE required_hours SET required_hours = ? WHERE program = ?
        ");

        foreach ($_POST['hours'] as $program => $hours) {
            if (!in_array($program, $allowed)) continue;
            $stmt->execute([(int)$hours, $program]);
        }

        $auditStmt = $pdo->prepare("
            INSERT INTO audits (user_id, roles, activity, activity_date) 
            VALUES (?, 'superadmin', ?, NOW())
        ");
        $auditStmt->execute([
            $_SESSION['user_id'],
            'Updated required OJT hours for CE/EE/IT programs'
        ]);

        $_SESSION['success'] = "Required hours updated successfully.";
        // header("Location: superadmin.php?section=hours_rendering");
        // exit;
        header("Location: superadmin.php#hours_rendering");
        exit;
    }
}

?>