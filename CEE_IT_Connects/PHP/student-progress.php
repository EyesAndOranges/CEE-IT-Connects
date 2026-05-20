<?php
session_start();
require 'db.php';

$student_id = $_SESSION['user_id'] ?? null;
if (!$student_id) {
    $_SESSION['error'] = "You must be logged in.";
    header("Location: login.php");
    exit;
}

function markStep(PDO $pdo, int $student_id, string $step_key): void
{
    $pdo->prepare("
        INSERT INTO student_progress (student_id, step_key, is_done)
        VALUES (:id, :step, TRUE)
        ON CONFLICT (student_id, step_key)
        DO UPDATE SET is_done = TRUE, updated_at = NOW()
    ")->execute(['id' => $student_id, 'step' => $step_key]);
}

// ── RESUME UPLOAD ─────────────────────────────────────────────────────────────
if (isset($_FILES['resume']) && $_FILES['resume']['error'] !== UPLOAD_ERR_NO_FILE) {
    try {
        if ($_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException("Upload error code: " . $_FILES['resume']['error']);
        }
        $ext = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            throw new RuntimeException("Resume must be a PDF.");
        }
        if ($_FILES['resume']['size'] > 5 * 1024 * 1024) {
            throw new RuntimeException("File exceeds 5MB limit.");
        }

        $uploadDir = __DIR__ . '/../uploads/resumes/';
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0755, true);

        $newName = 'resume_' . $student_id . '_' . time() . '.pdf';
        if (!move_uploaded_file($_FILES['resume']['tmp_name'], $uploadDir . $newName)) {
            throw new RuntimeException("Failed to save file.");
        }

        $pdo->prepare("
            INSERT INTO student_documents (student_id, resume_path, uploaded_at)
            VALUES (:id, :path, NOW())
            ON CONFLICT (student_id)
            DO UPDATE SET resume_path = EXCLUDED.resume_path, uploaded_at = NOW()
        ")->execute(['id' => $student_id, 'path' => $newName]);

        markStep($pdo, $student_id, 'resume');
        $_SESSION['success'] = "Resume uploaded successfully.";
    } catch (RuntimeException $e) {
        $_SESSION['error'] = "Resume upload failed: " . $e->getMessage();
    }

    header("Location: application-progress.php");
    exit;
}

// ── CREDENTIAL UPLOAD ─────────────────────────────────────────────────────────
if (isset($_FILES['credential']) && $_FILES['credential']['error'] !== UPLOAD_ERR_NO_FILE) {
    try {
        if ($_FILES['credential']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException("Upload error code: " . $_FILES['credential']['error']);
        }
        $ext = strtolower(pathinfo($_FILES['credential']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
            throw new RuntimeException("Only PDF, JPG, PNG allowed.");
        }
        if ($_FILES['credential']['size'] > 5 * 1024 * 1024) {
            throw new RuntimeException("File exceeds 5MB limit.");
        }

        $uploadDir = __DIR__ . '/../uploads/credentials/';
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0755, true);

        $credName = 'credential_' . $student_id . '_' . time() . '.' . $ext;
        if (!move_uploaded_file($_FILES['credential']['tmp_name'], $uploadDir . $credName)) {
            throw new RuntimeException("Failed to save file.");
        }

        $pdo->prepare("
            INSERT INTO student_credentials (student_id, credential_path, uploaded_at)
            VALUES (:id, :path, NOW())
        ")->execute(['id' => $student_id, 'path' => $credName]);

        markStep($pdo, $student_id, 'credential');
        $_SESSION['success'] = "Credential uploaded successfully.";
    } catch (RuntimeException $e) {
        $_SESSION['error'] = "Credential upload failed: " . $e->getMessage();
    }

    header("Location: application-progress.php");
    exit;
}

// ── APPLICATION SUBMITTED ─────────────────────────────────────────────────────
if (isset($_POST['application_submitted'])) {
    markStep($pdo, $student_id, 'application');
    $_SESSION['success'] = "Application marked as submitted.";
    header("Location: application-progress.php");
    exit;
}

// ── MARK DOCUMENTS COMPLETE ───────────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'mark_documents') {
    markStep($pdo, $student_id, 'documents');
    $_SESSION['success'] = "Documents marked as complete.";
    header("Location: application-progress.php");
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'confirm_ojt') {
    $company = trim($_POST['company_name'] ?? '');
    if (empty($company)) {
        $_SESSION['error'] = "Please enter the company name.";
        header("Location: application-progress.php");
        exit;
    }

    $pdo->prepare("
        INSERT INTO student_progress (student_id, step_key, is_done)
        VALUES (:id, 'ojt_accepted', TRUE)
        ON CONFLICT (student_id, step_key)
        DO UPDATE SET is_done = TRUE, updated_at = NOW()
    ")->execute(['id' => $student_id]);

    $_SESSION['success'] = "Internship confirmed at $company.";
    header("Location: application-progress.php");
    exit;
}

$_SESSION['error'] = "No valid action was submitted.";
header("Location: application-progress.php");
exit;