<?php
require 'auth.php';
require 'db.php';

$student_id = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: application-progress.php');
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'mark_step') {
    $step_key = $_POST['step_key'] ?? '';
    $allowed_steps = [
        'hte_form',
        'addendum',
        'reco_letter',
        'waiver',
        'medical_cert',
        'internship_plan',
        'vicinity_map',
        'oath'
    ];

    if (in_array($step_key, $allowed_steps)) {
        $upsert = $pdo->prepare("
            INSERT INTO student_progress (student_id, step_key, is_done, updated_at)
            VALUES (:sid, :key, TRUE, NOW())
            ON CONFLICT (student_id, step_key)
            DO UPDATE SET is_done = TRUE, updated_at = NOW()
        ");
        $upsert->execute([':sid' => $student_id, ':key' => $step_key]);
        $_SESSION['success'] = "Step marked as complete.";
    } else {
        $_SESSION['error'] = "Invalid step.";
    }

} elseif ($action === 'confirm_ojt') {
    $company_name = trim($_POST['company_name'] ?? '');
    if ($company_name !== '') {
        $checkStmt = $pdo->prepare("
            SELECT id, status FROM ojt_applications
            WHERE student_id = ? AND internship_id = ?
        ");
        $checkStmt->execute([$student_id, $_POST['internship_id']]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            $insertStmt = $pdo->prepare("
                INSERT INTO ojt_applications (student_id, internship_id, company_name)
                VALUES (?, ?, ?)
            ");
            $insertStmt->execute([$student_id, $_POST['internship_id'], $company_name]);
            $_SESSION['success'] = "Your OJT application has been submitted for adviser approval.";
        } else {
            $_SESSION['error'] = "You have already submitted an application (status: {$existing['status']}).";
        }
    } else {
        $_SESSION['error'] = "Please enter your company name.";
    }

} elseif ($action === 'submit_hte_supervisor') {
    $full_name = trim($_POST['sup_full_name'] ?? '');
    $email = trim($_POST['sup_email'] ?? '');
    $contact = trim($_POST['sup_contact'] ?? '');
    $company = trim($_POST['sup_company'] ?? '');
    $int_id = (int) ($_POST['internship_id'] ?? 0);

    if (!$full_name) {
        $_SESSION['error'] = 'Supervisor full name is required.';
    } else {
        $upsert = $pdo->prepare("
            INSERT INTO student_hte_supervisor_submissions
                (student_id, internship_id, full_name, email, contact_number, company_name, status)
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
            ON CONFLICT (student_id) DO UPDATE SET
                full_name      = EXCLUDED.full_name,
                email          = EXCLUDED.email,
                contact_number = EXCLUDED.contact_number,
                company_name   = EXCLUDED.company_name,
                status         = 'pending',
                submitted_at   = NOW()
        ");
        $upsert->execute([$student_id, $int_id, $full_name, $email, $contact, $company]);
        $_SESSION['success'] = 'Supervisor details submitted. Your coordinator will review shortly.';
    }

} elseif ($action === 'cancel_application') {
    $bookmark_id = (int) ($_POST['bookmark_id'] ?? 0);

    // Verify the bookmark belongs to this student
    $checkStmt = $pdo->prepare("
        SELECT id FROM internship_bookmarks
        WHERE id = ? AND student_id = ?
    ");
    $checkStmt->execute([$bookmark_id, $student_id]);

    if (!$checkStmt->fetch()) {
        $_SESSION['error'] = 'Bookmark not found.';
        header('Location: application-progress.php');
        exit;
    }

    // Delete the bookmark
    $pdo->prepare("DELETE FROM internship_bookmarks WHERE id = ? AND student_id = ?")
        ->execute([$bookmark_id, $student_id]);

    // Reset internship-specific checklist steps
    $pdo->prepare("
        DELETE FROM student_progress
        WHERE student_id = ?
        AND step_key IN (
            'hte_form', 'addendum', 'reco_letter', 'waiver',
            'internship_plan', 'vicinity_map', 'oath', 'ojt_started'
        )
    ")->execute([$student_id]);

    // Reset HTE supervisor submission
    $pdo->prepare("DELETE FROM student_hte_supervisor_submissions WHERE student_id = ?")
        ->execute([$student_id]);

    $_SESSION['success'] = 'Application cancelled and progress reset.';
    header('Location: applied-Internship-programs.php');
    exit;

} else {
    $_SESSION['error'] = "Unknown action.";
}

header('Location: application-progress.php');
exit;