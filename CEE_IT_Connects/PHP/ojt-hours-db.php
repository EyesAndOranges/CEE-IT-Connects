<?php
session_start();
require 'db.php';
require 'auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$userType = getUserType($_SESSION['role']);
$action = $_POST['action'] ?? '';

function jsonError($message, $code = 400)
{
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

function ensureOjtTables($pdo)
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS ojt_weeks (
        user_id INTEGER NOT NULL,
        user_type VARCHAR(32) NOT NULL,
        week_index INTEGER NOT NULL,
        week_label VARCHAR(255) NOT NULL DEFAULT '',
        created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
        updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
        PRIMARY KEY (user_id, user_type, week_index)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS ojt_hours (
        user_id INTEGER NOT NULL,
        user_type VARCHAR(32) NOT NULL,
        week_index INTEGER NOT NULL,
        row_index INTEGER NOT NULL,
        date DATE,
        m_in TIME,
        m_out TIME,
        a_in TIME,
        a_out TIME,
        created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
        updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
        PRIMARY KEY (user_id, user_type, week_index, row_index)
    )");
}

ensureOjtTables($pdo);

if ($action === 'save_row') {
    $weekIndex = isset($_POST['week_index']) ? intval($_POST['week_index']) : null;
    $rowIndex = isset($_POST['row_index']) ? intval($_POST['row_index']) : null;
    $field = $_POST['field'] ?? '';
    $value = $_POST['value'] ?? null;

    $allowed = ['date' => 'date', 'mIn' => 'm_in', 'mOut' => 'm_out', 'aIn' => 'a_in', 'aOut' => 'a_out'];
    if ($weekIndex === null || $rowIndex === null || !array_key_exists($field, $allowed)) {
        jsonError('Invalid request data.');
    }

    $column = $allowed[$field];
    $defaultLabel = 'Week ' . ($weekIndex + 1);

    $stmt = $pdo->prepare("INSERT INTO ojt_weeks (user_id, user_type, week_index, week_label, created_at, updated_at)
        VALUES (:user_id, :user_type, :week_index, :week_label, NOW(), NOW())
        ON CONFLICT (user_id, user_type, week_index) DO NOTHING");
    $stmt->execute([
        ':user_id' => $userId,
        ':user_type' => $userType,
        ':week_index' => $weekIndex,
        ':week_label' => $defaultLabel
    ]);

    $insertSql = "INSERT INTO ojt_hours (user_id, user_type, week_index, row_index, {$column}, created_at, updated_at)
        VALUES (:user_id, :user_type, :week_index, :row_index, :value, NOW(), NOW())
        ON CONFLICT (user_id, user_type, week_index, row_index)
        DO UPDATE SET {$column} = EXCLUDED.{$column}, updated_at = NOW()";

    $stmt = $pdo->prepare($insertSql);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':user_type', $userType, PDO::PARAM_STR);
    $stmt->bindValue(':week_index', $weekIndex, PDO::PARAM_INT);
    $stmt->bindValue(':row_index', $rowIndex, PDO::PARAM_INT);

    if ($value === '' || $value === null) {
        $stmt->bindValue(':value', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':value', $value, PDO::PARAM_STR);
    }

    $stmt->execute();
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'save_week_label') {
    $weekIndex = isset($_POST['week_index']) ? intval($_POST['week_index']) : null;
    $weekLabel = trim($_POST['week_label'] ?? '');
    if ($weekIndex === null) {
        jsonError('Invalid week.');
    }

    $stmt = $pdo->prepare("INSERT INTO ojt_weeks (user_id, user_type, week_index, week_label, created_at, updated_at)
        VALUES (:user_id, :user_type, :week_index, :week_label, NOW(), NOW())
        ON CONFLICT (user_id, user_type, week_index)
        DO UPDATE SET week_label = EXCLUDED.week_label, updated_at = NOW()");

    $stmt->execute([
        ':user_id' => $userId,
        ':user_type' => $userType,
        ':week_index' => $weekIndex,
        ':week_label' => $weekLabel !== '' ? $weekLabel : 'Week ' . ($weekIndex + 1)
    ]);

    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'delete_week') {
    $weekIndex = isset($_POST['week_index']) ? intval($_POST['week_index']) : null;
    if ($weekIndex === null) {
        jsonError('Invalid week.');
    }

    $stmt = $pdo->prepare("DELETE FROM ojt_hours WHERE user_id = :user_id AND user_type = :user_type AND week_index = :week_index");
    $stmt->execute([':user_id' => $userId, ':user_type' => $userType, ':week_index' => $weekIndex]);

    $stmt = $pdo->prepare("DELETE FROM ojt_weeks WHERE user_id = :user_id AND user_type = :user_type AND week_index = :week_index");
    $stmt->execute([':user_id' => $userId, ':user_type' => $userType, ':week_index' => $weekIndex]);

    echo json_encode(['success' => true]);
    exit;
}

jsonError('Action not recognized.');



if ($action === 'notify_completion') {
    // Prevent duplicate within 24 hours
    $check = $pdo->prepare("
        SELECT id FROM notifications
        WHERE user_id = ? AND user_type = ? AND title = 'OJT Hours Completed'
        AND created_at > NOW() - INTERVAL '24 hours'
    ");
    $check->execute([$_SESSION['user_id'], $userType]);
    if ($check->fetch()) {
        echo json_encode(['success' => true, 'message' => 'Already notified']);
        exit;
    }

    // Get student name
    $sStmt = $pdo->prepare("SELECT full_name FROM students WHERE id = ?");
    $sStmt->execute([$_SESSION['user_id']]);
    $student = $sStmt->fetch(PDO::FETCH_ASSOC);
    $studentName = $student['full_name'] ?? 'A student';

    // Get adviser linked to student's room
    $adviserStmt = $pdo->prepare("
        SELECT DISTINCT a.id
        FROM advisers a
        JOIN rooms r ON r.adviser_id = a.id
        JOIN room_members rm ON rm.room_id = r.id
        WHERE rm.user_id = ?
        LIMIT 1
    ");
    $adviserStmt->execute([$_SESSION['user_id']]);
    $adviser = $adviserStmt->fetch(PDO::FETCH_ASSOC);

    try {
        $pdo->beginTransaction();

        // Notify student
        $pdo->prepare("
            INSERT INTO notifications (user_id, user_type, title, message, is_read, created_at)
            VALUES (?, 'student', 'OJT Hours Completed',
                'Congratulations! You have completed your required OJT hours. Please submit your student evaluation.',
                FALSE, NOW())
        ")->execute([$_SESSION['user_id']]);

        // Notify adviser
        if ($adviser) {
            $pdo->prepare("
                INSERT INTO notifications (user_id, user_type, title, message, is_read, created_at)
                VALUES (?, 'adviser', 'Student Completed OJT Hours',
                    ?,
                    FALSE, NOW())
            ")->execute([
                $adviser['id'],
                "$studentName has completed their required OJT hours. Please input your remarks in the supervisor evaluation."
            ]);
        }

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
