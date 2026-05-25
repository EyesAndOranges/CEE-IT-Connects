<?php
session_start();
require 'db.php';
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ojt-rooms.php");
    exit;
}

$source = $_POST['source'] ?? '';

// ── CASE 1: Superadmin editing the CSV file directly ──
if (isset($_POST['edit_csv'])) {
    $headers = $_POST['headers'] ?? [];
    $rows = $_POST['csv'] ?? [];

    $sourceDir = __DIR__ . '/../Sources/';
    $activeFile = file_exists($sourceDir . 'active_csv.txt')
        ? trim(file_get_contents($sourceDir . 'active_csv.txt'))
        : 'students.csv';
    $csvPath = $sourceDir . $activeFile;

    $handle = fopen($csvPath, 'w');
    fputcsv($handle, $headers);
    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }
    fclose($handle);

    $_SESSION['success'] = "CSV saved successfully.";
    header("Location: superadmin.php?section=student_register");
    exit;
}

// Ojt rooms
if ($source === 'ojt-rooms') {
    $headers = $_POST['headers'] ?? [];
    $rows = $_POST['csv'] ?? [];
    $room_id = $_POST['room_id'] ?? null;

    if (!$room_id) {
        $_SESSION['error'] = "No room specified.";
        header("Location: ojt-rooms.php");
        exit;
    }

    $normalized = array_map(fn($h) => strtolower(trim($h)), $headers);
    $sidIdx = array_search('student_id', $normalized);
    if ($sidIdx === false) {
        $sidIdx = array_search('student id', $normalized);
    }

    if ($sidIdx === false) {
        $_SESSION['error'] = "Column 'student_id' not found in CSV.";
        header("Location: ojt-rooms.php?room_id=" . $room_id . "&tab=members");
        exit;
    }

    $added = 0;
    $notFound = [];
    $skipped = 0;

    $findStmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ?");
    $checkStmt = $pdo->prepare("
        SELECT id FROM room_members 
        WHERE room_id = ? AND user_id = ? AND user_type = 'student'
    ");
    $insertStmt = $pdo->prepare("
        INSERT INTO room_members (room_id, user_id, user_type) 
        VALUES (?, ?, 'student')
    ");

    foreach ($rows as $row) {
        $student_id = trim($row[$sidIdx] ?? '');
        if ($student_id === '')
            continue;

        $findStmt->execute([$student_id]);
        $student = $findStmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            $notFound[] = $student_id;
            continue;
        }

        $checkStmt->execute([$room_id, $student['id']]);
        if ($checkStmt->fetch()) {
            $skipped++;
            continue;
        }

        $insertStmt->execute([$room_id, $student['id']]);
        $added++;
    }

    if (!empty($notFound)) {
        $_SESSION['warning'] = "Added {$added} student(s). "
            . ($skipped ? "{$skipped} already in room. " : "")
            . "Not found: " . implode(', ', $notFound);
    } elseif ($skipped > 0 && $added === 0) {
        $_SESSION['info'] = "No new students added — all {$skipped} are already in this room.";
    } else {
        $_SESSION['success'] = "Successfully added {$added} student(s) to the room."
            . ($skipped ? " ({$skipped} already in room, skipped.)" : "");
    }

    header("Location: ojt-rooms.php?room_id=" . $room_id . "&tab=members");
    exit;
}

// put here the upload

$_SESSION['error'] = "Unknown action.";
header("Location: superadmin.php");
exit;