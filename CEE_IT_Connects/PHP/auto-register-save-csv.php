<?php
session_start();
require 'db.php';

if (!isset($_POST['csv']) || !is_array($_POST['csv'])) {
    die("No CSV data received.");
}
if (!isset($_POST['headers']) || !is_array($_POST['headers'])) {
    die("No headers received.");
}

$rows = $_POST['csv'];
ksort($rows);
ksort($_POST['headers']);

$source = $_POST['source'] ?? '';

// ── Shared: Map headers ──────────────────────────────────────
$headers = [];
foreach ($_POST['headers'] as $colIndex => $value) {
    $h = strtolower(trim($value));
    $h = ltrim($h, "\xEF\xBB\xBF");
    $headers[$colIndex] = match ($h) {
        'student id', 'student_id' => 'student_id',
        'full name', 'full_name' => 'full_name',
        'e-mail', 'email' => 'email',
        'program' => 'program',
        'year', 'year_level' => 'year_level',
        'section' => 'section',
        'contact no.', 'contact number', 'contact_number' => 'contact_number',
        'company', 'hte', 'institution' => 'company',
        default => $h
    };
}

// ── Shared: Find student_id column ───────────────────────────
$studentIdColIndex = null;
foreach ($headers as $colIndex => $fieldName) {
    if ($fieldName === 'student_id') {
        $studentIdColIndex = $colIndex;
        break;
    }
}

if ($studentIdColIndex === null) {
    $_SESSION['error'] = "Import failed: Could not find a 'Student ID' column in the CSV headers.";
    header("Location: " . ($source === 'ojt-rooms' ? 'ojt-rooms.php' : 'internship-ui.php'));
    exit();
}

// ── Shared: Deduplicate rows ─────────────────────────────────
$seenIds = [];
$duplicateWarnings = [];
$cleanedRows = [];

foreach ($rows as $rowIndex => $row) {
    if (!is_array($row))
        continue;
    $sid = trim($row[$studentIdColIndex] ?? '');
    if (empty($sid))
        continue;
    if (isset($seenIds[$sid])) {
        $duplicateWarnings[] = "Duplicate Student ID '{$sid}' on row {$rowIndex} — removed.";
        continue;
    }
    $seenIds[$sid] = true;
    $cleanedRows[$rowIndex] = $row;
}
$rows = $cleanedRows;


// For Save CSV
if (isset($_POST['edit_csv'])) {

    $sourceDir = __DIR__ . '/../Sources/';
    $activeFile = file_exists($sourceDir . 'active_csv.txt')
        ? trim(file_get_contents($sourceDir . 'active_csv.txt'))
        : 'students.csv';
    $csvPath = $sourceDir . $activeFile;

    $handle = fopen($csvPath, 'w');
    if (!$handle) {
        $_SESSION['error'] = "Could not open CSV file for writing.";
        header("Location: internship-ui.php");
        exit();
    }
    $headerRow = array_values($_POST['headers']);
    $headerRow[0] = ltrim($headerRow[0], "\xEF\xBB\xBF");
    fputcsv($handle, $headerRow);
    foreach ($rows as $row) {
        if (!is_array($row))
            continue;
        $orderedRow = [];
        foreach (array_keys($_POST['headers']) as $colIndex) {
            $orderedRow[] = $row[$colIndex] ?? '';
        }
        fputcsv($handle, $orderedRow);
    }
    fclose($handle);

    $inserted = 0;
    $updated = 0;
    $skipped = 0;
    $errors = [];

    $checkStudent = $pdo->prepare("SELECT id, email, full_name, program, year_level, section, contact_number FROM students WHERE student_id = ?");
    $upsertStudent = $pdo->prepare("
        INSERT INTO students (email, full_name, student_id, program, year_level, section, contact_number, password_hash)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON CONFLICT (student_id) DO UPDATE SET
            email          = EXCLUDED.email,
            full_name      = EXCLUDED.full_name,
            program        = EXCLUDED.program,
            year_level     = EXCLUDED.year_level,
            section        = EXCLUDED.section,
            contact_number = EXCLUDED.contact_number
        RETURNING id
    ");

    try {
        $pdo->beginTransaction();

        foreach ($rows as $rowIndex => $row) {
            if (!is_array($row))
                continue;
            $cleanRow = [];
            foreach ($headers as $colIndex => $colName) {
                $cleanRow[$colName] = trim($row[$colIndex] ?? '');
            }
            if (empty($cleanRow['student_id']))
                continue;

            $pdo->exec("SAVEPOINT row_{$rowIndex}");
            try {
                $checkStudent->execute([$cleanRow['student_id']]);
                $existing = $checkStudent->fetch(PDO::FETCH_ASSOC);
                $isNew = $existing === false;
                $hasChanged = !$isNew && (
                    $existing['email'] !== ($cleanRow['email'] ?? '') ||
                    $existing['full_name'] !== ($cleanRow['full_name'] ?? '') ||
                    $existing['program'] !== ($cleanRow['program'] ?? '') ||
                    (int) $existing['year_level'] !== (int) ($cleanRow['year_level'] ?? 0) ||
                    $existing['section'] !== ($cleanRow['section'] ?? '') ||
                    $existing['contact_number'] !== ($cleanRow['contact_number'] ?? '')
                );

                if (!$isNew && !$hasChanged) {
                    $skipped++;
                } else {
                    $upsertStudent->execute([
                        $cleanRow['email'] ?? '',
                        $cleanRow['full_name'] ?? '',
                        $cleanRow['student_id'],
                        $cleanRow['program'] ?? '',
                        (int) ($cleanRow['year_level'] ?? 0),
                        $cleanRow['section'] ?? '',
                        $cleanRow['contact_number'] ?? '',
                        password_hash($cleanRow['student_id'], PASSWORD_DEFAULT),
                    ]);

                    $studentDbId = (int) $upsertStudent->fetchColumn(0);
                    if ($studentDbId === 0) {
                        $fb = $pdo->prepare("SELECT id FROM students WHERE student_id = ?");
                        $fb->execute([$cleanRow['student_id']]);
                        $studentDbId = (int) $fb->fetchColumn();
                    }
                    if ($studentDbId === 0) {
                        throw new Exception("Could not resolve DB ID for {$cleanRow['student_id']}");
                    }
                    $isNew ? $inserted++ : $updated++;
                }
                $pdo->exec("RELEASE SAVEPOINT row_{$rowIndex}");

            } catch (Exception $e) {
                $pdo->exec("ROLLBACK TO SAVEPOINT row_{$rowIndex}");
                $errors[] = "Row {$rowIndex} (ID: {$cleanRow['student_id']}): " . $e->getMessage();
            }
        }

        $pdo->commit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Fatal database error: " . $e->getMessage();
        header("Location: internship-ui.php");
        exit();
    }

    $summary = "$inserted new student(s) added, $updated updated, $skipped unchanged.";

    if (!empty($errors) && $inserted === 0 && $updated === 0) {
        $_SESSION['error'] = "Import failed: " . implode(" | ", $errors);
    } elseif (!empty($errors)) {
        $_SESSION['warning'] = count($errors) . " issue(s): " . implode(" | ", $errors);
        $_SESSION['success'] = "Partially saved. $summary";
    } elseif (!empty($duplicateWarnings)) {
        $_SESSION['success'] = "Saved. $summary";
        $_SESSION['warning'] = count($duplicateWarnings) . " duplicate(s) removed.";
    } else {
        $_SESSION['success'] = "CSV and student records saved. $summary";
    }

    header("Location: internship-ui.php");
    exit();
}

// OJT ROOMS CREATION
if ($source === 'ojt-rooms') {

    $adviser_id = (int) $_SESSION['user_id'];
    $inserted = 0;
    $updated = 0;
    $skipped = 0;
    $roomsCreated = 0;
    $membersAdded = 0;
    $errors = [];

    $checkStudent = $pdo->prepare("SELECT id, email, full_name, program, year_level, section, contact_number FROM students WHERE student_id = ?");
    $upsertStudent = $pdo->prepare("
        INSERT INTO students (email, full_name, student_id, program, year_level, section, contact_number, password_hash)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON CONFLICT (student_id) DO UPDATE SET
            email          = EXCLUDED.email,
            full_name      = EXCLUDED.full_name,
            program        = EXCLUDED.program,
            year_level     = EXCLUDED.year_level,
            section        = EXCLUDED.section,
            contact_number = EXCLUDED.contact_number
        RETURNING id
    ");
    $findRoom = $pdo->prepare("SELECT id FROM rooms WHERE room_name = ? AND adviser_id = ?");
    $createRoom = $pdo->prepare("INSERT INTO rooms (room_name, room_code, adviser_id, is_archived) VALUES (?, ?, ?, FALSE) RETURNING id");
    $findMember = $pdo->prepare("SELECT 1 FROM room_members WHERE room_id = ? AND user_id = ?");
    $addMember = $pdo->prepare("INSERT INTO room_members (room_id, user_id, user_type) VALUES (?, ?, 'student')");

    try {
        $pdo->beginTransaction();

        foreach ($rows as $rowIndex => $row) {
            if (!is_array($row))
                continue;
            $cleanRow = [];
            foreach ($headers as $colIndex => $colName) {
                $cleanRow[$colName] = trim($row[$colIndex] ?? '');
            }
            if (empty($cleanRow['student_id']))
                continue;

            $pdo->exec("SAVEPOINT row_{$rowIndex}");
            try {
                // -- Upsert student --
                $checkStudent->execute([$cleanRow['student_id']]);
                $existing = $checkStudent->fetch(PDO::FETCH_ASSOC);
                $isNew = $existing === false;
                $hasChanged = !$isNew && (
                    $existing['email'] !== ($cleanRow['email'] ?? '') ||
                    $existing['full_name'] !== ($cleanRow['full_name'] ?? '') ||
                    $existing['program'] !== ($cleanRow['program'] ?? '') ||
                    (int) $existing['year_level'] !== (int) ($cleanRow['year_level'] ?? 0) ||
                    $existing['section'] !== ($cleanRow['section'] ?? '') ||
                    $existing['contact_number'] !== ($cleanRow['contact_number'] ?? '')
                );

                if (!$isNew && !$hasChanged) {
                    $skipped++;
                    $studentDbId = (int) $existing['id'];
                } else {
                    $upsertStudent->execute([
                        $cleanRow['email'] ?? '',
                        $cleanRow['full_name'] ?? '',
                        $cleanRow['student_id'],
                        $cleanRow['program'] ?? '',
                        (int) ($cleanRow['year_level'] ?? 0),
                        $cleanRow['section'] ?? '',
                        $cleanRow['contact_number'] ?? '',
                        password_hash($cleanRow['student_id'], PASSWORD_DEFAULT),
                    ]);
                    $studentDbId = (int) $upsertStudent->fetchColumn(0);
                    if ($studentDbId === 0) {
                        $fb = $pdo->prepare("SELECT id FROM students WHERE student_id = ?");
                        $fb->execute([$cleanRow['student_id']]);
                        $studentDbId = (int) $fb->fetchColumn();
                    }
                    if ($studentDbId === 0) {
                        throw new Exception("Could not resolve DB ID for {$cleanRow['student_id']}");
                    }
                    $isNew ? $inserted++ : $updated++;
                }

                // -- Create room + add member --
                $company = trim($cleanRow['company'] ?? '');
                if ($company !== '' && $studentDbId > 0) {
                    $findRoom->execute([$company, $adviser_id]);
                    $room = $findRoom->fetch(PDO::FETCH_ASSOC);

                    if ($room) {
                        $roomId = $room['id'];
                    } else {
                        $roomCode = substr(strtoupper(preg_replace('/[^A-Z0-9]/i', '', $company)), 0, 6)
                            . '-' . substr(uniqid(), -4);
                        $createRoom->execute([$company, $roomCode, $adviser_id]);
                        $roomId = (int) $createRoom->fetchColumn();
                        $roomsCreated++;
                    }

                    $findMember->execute([$roomId, $studentDbId]);
                    if (!$findMember->fetch()) {
                        $addMember->execute([$roomId, $studentDbId]);
                        $membersAdded++;
                    }
                }

                $pdo->exec("RELEASE SAVEPOINT row_{$rowIndex}");

            } catch (Exception $e) {
                $pdo->exec("ROLLBACK TO SAVEPOINT row_{$rowIndex}");
                $errors[] = "Row {$rowIndex} (ID: {$cleanRow['student_id']}): " . $e->getMessage();
            }
        }

        $pdo->commit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Fatal database error: " . $e->getMessage();
        header("Location: ojt-rooms.php");
        exit();
    }

    $summary = "$inserted new student(s) added, $updated updated, $skipped unchanged. "
        . "$roomsCreated room(s) created, $membersAdded student(s) assigned to rooms.";

    if (!empty($errors) && $inserted === 0 && $updated === 0 && $roomsCreated === 0) {
        $_SESSION['error'] = "Import failed: " . implode(" | ", $errors);
    } elseif (!empty($errors)) {
        $_SESSION['warning'] = count($errors) . " issue(s): " . implode(" | ", $errors);
        $_SESSION['success'] = "Partially imported. $summary";
    } else {
        $_SESSION['success'] = "Import complete. $summary";
    }

    header("Location: ojt-rooms.php");
    exit();
}