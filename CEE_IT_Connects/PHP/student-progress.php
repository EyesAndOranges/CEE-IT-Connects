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
        'medical_cert',
        'hte_form',
        'acceptance_letter',
        'company_profile',
        'addendum',
        'reco_letter',
        'waiver',
        'internship_plan',
        'vicinity_map',
        'oath',
    ];

    if (!in_array($step_key, $allowed_steps)) {
        $_SESSION['error'] = "Invalid step.";
        header('Location: application-progress.php');
        exit;
    }

    // ── Handle file upload ────────────────────────────────────────────────────
    $file_path = null;
    $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];

    if (empty($_FILES['proof_file']['tmp_name'])) {
        $_SESSION['error'] = "Please attach proof before marking as done.";
        header('Location: application-progress.php');
        exit;
    }

    $file = $_FILES['proof_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = "Upload error. Please try again.";
        header('Location: application-progress.php');
        exit;
    }

    if (!in_array($file['type'], $allowed_types)) {
        $_SESSION['error'] = "Only PNG, JPG, or PDF files are allowed.";
        header('Location: application-progress.php');
        exit;
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        $_SESSION['error'] = "File must be under 5MB.";
        header('Location: application-progress.php');
        exit;
    }

    $upload_dir = __DIR__ . '/uploads/checklist/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0775, true);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = $student_id . '_' . $step_key . '_' . time() . '.' . $ext;
    $dest = $upload_dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        $_SESSION['error'] = "Upload failed. Please try again.";
        header('Location: application-progress.php');
        exit;
    }

    $file_path = 'uploads/checklist/' . $filename;

    // ── Upsert progress with file path ────────────────────────────────────────
    $upsert = $pdo->prepare("
        INSERT INTO student_progress (student_id, step_key, is_done, file_path, updated_at)
        VALUES (:sid, :key, TRUE, :fp, NOW())
        ON CONFLICT (student_id, step_key)
        DO UPDATE SET
            is_done    = TRUE,
            file_path  = EXCLUDED.file_path,
            updated_at = NOW()
    ");
    $upsert->execute([
        ':sid' => $student_id,
        ':key' => $step_key,
        ':fp' => $file_path,
    ]);

    $_SESSION['success'] = "Step marked as complete.";

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

    $pdo->prepare("DELETE FROM internship_bookmarks WHERE id = ? AND student_id = ?")
        ->execute([$bookmark_id, $student_id]);

    // Delete uploaded proof files from disk before resetting progress
    $filesStmt = $pdo->prepare("
        SELECT file_path FROM student_progress
        WHERE student_id = ?
        AND step_key IN (
            'hte_form', 'addendum', 'reco_letter', 'waiver',
            'internship_plan', 'vicinity_map', 'oath', 'ojt_started'
        )
        AND file_path IS NOT NULL
    ");
    $filesStmt->execute([$student_id]);
    foreach ($filesStmt->fetchAll(PDO::FETCH_COLUMN) as $fp) {
        $full = __DIR__ . '/' . $fp;
        if (file_exists($full)) {
            unlink($full);
        }
    }

    $pdo->prepare("
        DELETE FROM student_progress
        WHERE student_id = ?
        AND step_key IN (
            'hte_form', 'addendum', 'reco_letter', 'waiver',
            'internship_plan', 'vicinity_map', 'oath', 'ojt_started'
        )
    ")->execute([$student_id]);

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