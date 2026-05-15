<?php
session_start();
require 'db.php';

$csvPath = __DIR__ . '/../Sources/students.csv';

if (!isset($_POST['csv']) || !is_array($_POST['csv'])) {
    die("No CSV data received.");
}

if (!isset($_POST['headers']) || !is_array($_POST['headers'])) {
    die("No headers received.");
}

$rows = $_POST['csv'];
ksort($rows);

/* map headers from hidden inputs */
$headers = [];
foreach ($_POST['headers'] as $colIndex => $value) {
    $h = strtolower(trim($value));
    $headers[$colIndex] = match ($h) {
        'student id' => 'student_id',
        'full name' => 'full_name',
        'e-mail', 'email' => 'email',
        'program' => 'program',
        'year' => 'year_level',
        'section' => 'section',
        'contact no.', 'contact number' => 'contact_number',
        default => $h
    };
}

/* write CSV */
$fp = fopen($csvPath, 'w');
if (!$fp)
    die("Cannot open CSV.");

fwrite($fp, implode("\t", array_values($headers)) . "\n");

foreach ($rows as $row) {
    if (!is_array($row))
        continue;

    $cleanRow = [];
    foreach ($headers as $colIndex => $colName) {
        $cleanRow[] = $row[$colIndex] ?? '';
    }

    if (count(array_filter($cleanRow)) === 0)
        continue;

    fwrite($fp, implode("\t", $cleanRow) . "\n");
}

fclose($fp);

/* save to database */
$checkStudent = $pdo->prepare("
    SELECT id, email, full_name, program, year_level, section, contact_number
    FROM students WHERE student_id = :student_id
");

$insertStudent = $pdo->prepare("
    INSERT INTO students (email, full_name, student_id, program, year_level, section, contact_number, password_hash)
    VALUES (:email, :full_name, :student_id, :program, :year_level, :section, :contact_number, :password_hash)
    ON CONFLICT (student_id) DO UPDATE SET
        email          = EXCLUDED.email,
        full_name      = EXCLUDED.full_name,
        program        = EXCLUDED.program,
        year_level     = EXCLUDED.year_level,
        section        = EXCLUDED.section,
        contact_number = EXCLUDED.contact_number
");

$insertAudit = $pdo->prepare("
    INSERT INTO audits (user_id, roles, activity, activity_date)
    VALUES (:user_id, :roles, :activity, NOW())
");

$inserted = 0;
$updated = 0;
$skipped = 0;
$errors = [];

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

        try {
            $checkStudent->execute([':student_id' => $cleanRow['student_id']]);
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

            // No changes — count as skipped
            if (!$isNew && !$hasChanged) {
                $skipped++;
                continue;
            }

            $insertStudent->execute([
                ':email' => $cleanRow['email'] ?? '',
                ':full_name' => $cleanRow['full_name'] ?? '',
                ':student_id' => $cleanRow['student_id'],
                ':program' => $cleanRow['program'] ?? '',
                ':year_level' => (int) ($cleanRow['year_level'] ?? 0),
                ':section' => $cleanRow['section'] ?? '',
                ':contact_number' => $cleanRow['contact_number'] ?? '',
                ':password_hash' => password_hash($cleanRow['student_id'], PASSWORD_DEFAULT)
            ]);

            if ($isNew) {
                $inserted++;
                $newStudentId = $pdo->lastInsertId();
                $insertAudit->execute([
                    ':user_id' => $newStudentId,
                    ':roles' => 'student',
                    ':activity' => 'Student registered with school ID ' . $cleanRow['student_id']
                ]);
            } else {
                $updated++;
                $insertAudit->execute([
                    ':user_id' => $existing['id'],
                    ':roles' => 'student',
                    ':activity' => 'Student record updated for school ID ' . $cleanRow['student_id']
                ]);
            }

        } catch (PDOException $e) {
            // Log the bad row but keep going
            $errors[] = "Row {$rowIndex} (ID: {$cleanRow['student_id']}): " . $e->getMessage();
        }
    }

    $pdo->commit();
    $insertAudit->execute([
        ':user_id' => $_SESSION['user_id'],
        ':roles' => $_SESSION['role'],
        ':activity' => 'Imported student CSV — ' . $inserted . ' added, ' . $updated . ' updated, ' . $skipped . ' skipped'
            . (!empty($errors) ? ', ' . count($errors) . ' error(s)' : '')
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Fatal database error: " . $e->getMessage();
    header("Location: internship-ui.php");
    exit();
}

// Build the success message
$summary = "$inserted new student(s) added, $updated updated, $skipped unchanged and skipped.";

if (!empty($errors)) {
    $_SESSION['error'] = "CSV saved with " . count($errors) . " error(s): " . implode(" | ", $errors);
    $_SESSION['info'] = $summary;
} else {
    $_SESSION['success'] = "CSV saved successfully. $summary";
}

header("Location: internship-ui.php");
exit();