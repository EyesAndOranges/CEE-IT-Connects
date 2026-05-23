<?php
session_start();
require 'db.php';
require 'auth.php';
$current_room_id = $_GET['room_id'] ?? null;

$isAdviser = isset($_SESSION['role']) && $_SESSION['role'] === 'internship_adviser';

//for the application section
$bookmarksStmt = $pdo->prepare("
    SELECT 
        ib.id AS bookmark_id,
        ib.internship_id,
        ib.created_at,
        i.title,
        i.company,
        i.location,
        i.program,
        i.deadline,
        i.internship_type,
        -- current phase
        CASE
            WHEN sp_ojt.is_done = TRUE  THEN 'Internship Confirmed'
            WHEN sp_doc.is_done = TRUE  THEN 'Documents Submitted'
            WHEN sp_app.is_done = TRUE  THEN 'Application Submitted'
            WHEN sd.student_id IS NOT NULL THEN 'Resume Uploaded'
            ELSE 'No Progress'
        END AS current_phase
    FROM internship_bookmarks ib
    JOIN internships i ON i.id = ib.internship_id
    LEFT JOIN student_documents sd 
        ON sd.student_id = ib.student_id
    LEFT JOIN student_progress sp_app 
        ON sp_app.student_id = ib.student_id AND sp_app.step_key = 'application'
    LEFT JOIN student_progress sp_doc 
        ON sp_doc.student_id = ib.student_id AND sp_doc.step_key = 'documents'
    LEFT JOIN student_progress sp_ojt 
        ON sp_ojt.student_id = ib.student_id AND sp_ojt.step_key = 'ojt_accepted'
    WHERE ib.student_id = ?
    ORDER BY ib.created_at DESC
");
$bookmarksStmt->execute([$_SESSION['user_id']]);
$bookmarkedInternships = $bookmarksStmt->fetchAll(PDO::FETCH_ASSOC);

//for rooms
$stmt = $pdo->prepare("
    SELECT r.*, a.full_name, a.title, a.role
    FROM rooms r
    LEFT JOIN advisers a ON r.adviser_id = a.id
    JOIN room_members rm ON r.id = rm.room_id
    WHERE rm.user_id = ?
    " . (!$isAdviser ? "AND r.is_archived = FALSE" : "") . "
");
$stmt->execute([$_SESSION['user_id']]);

$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

$current_section = $_GET['section'] ?? 'home';
$current_chat_id = $_GET['chat_id'] ?? null;
$current_chat_type = $_GET['chat_type'] ?? null;
$current_user_type = getUserType($_SESSION['role']);

$stmt = $pdo->prepare("
    SELECT 
    CASE 
        WHEN sender_id = :uid THEN receiver_id
        ELSE sender_id
    END as chat_user_id,

    CASE 
        WHEN sender_id = :uid THEN receiver_type
        ELSE sender_type
    END as chat_user_type,

    CONCAT(
        LEAST(sender_id, receiver_id), '-',
        GREATEST(sender_id, receiver_id), '-',
        CASE 
            WHEN sender_id = :uid THEN receiver_type
            ELSE sender_type
        END
    ) AS chat_key

FROM messages
WHERE sender_id = :uid OR receiver_id = :uid

GROUP BY chat_key, chat_user_id, chat_user_type
ORDER BY MAX(created_at) DESC
");
$stmt->execute(['uid' => $_SESSION['user_id']]);
$chatUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$messages = [];

if ($current_chat_id) {
    $stmt = $pdo->prepare("
    SELECT * FROM messages
    WHERE 
        (sender_id = ? AND sender_type = ? AND receiver_id = ? AND receiver_type = ?)
        OR
        (sender_id = ? AND sender_type = ? AND receiver_id = ? AND receiver_type = ?)
    ORDER BY created_at ASC
");

    $stmt->execute([
        $_SESSION['user_id'],
        getUserType($_SESSION['role']),

        $current_chat_id,
        $current_chat_type,

        $current_chat_id,
        $current_chat_type,

        $_SESSION['user_id'],
        getUserType($_SESSION['role'])
    ]);

    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUserName($pdo, $id, $type)
{
    switch (strtolower($type)) {

        case 'student':
            $stmt = $pdo->prepare("SELECT full_name FROM students WHERE id=?");
            break;

        case 'adviser':
            $stmt = $pdo->prepare("SELECT full_name FROM advisers WHERE id=?");
            break;

        case 'admin':
            $stmt = $pdo->prepare("SELECT name FROM admins WHERE id=?");
            break;

        default:
            return "Unknown ($type)";
    }

    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user['full_name'] ?? $user['name'] ?? "Unknown";
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

function loadOjtWeeks($pdo, $userId, $userType)
{
    ensureOjtTables($pdo);

    $stmt = $pdo->prepare("SELECT w.week_index, w.week_label, h.row_index, h.date, h.m_in, h.m_out, h.a_in, h.a_out
        FROM ojt_weeks w
        LEFT JOIN ojt_hours h
            ON h.user_id = w.user_id
            AND h.user_type = w.user_type
            AND h.week_index = w.week_index
        WHERE w.user_id = ? AND w.user_type = ?
        ORDER BY w.week_index, h.row_index");
    $stmt->execute([$userId, $userType]);

    $weeks = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $wi = (int)$row['week_index'];
        if (!isset($weeks[$wi])) {
            $weeks[$wi] = [
                'week_index' => $wi,
                'week_label' => $row['week_label'] !== '' ? $row['week_label'] : 'Week ' . ($wi + 1),
                'rows' => []
            ];
        }

        if ($row['row_index'] !== null) {
            $weeks[$wi]['rows'][(int)$row['row_index']] = [
                'date'  => $row['date'] ?? '',
                'm_in'  => $row['m_in'] ?? '',
                'm_out' => $row['m_out'] ?? '',
                'a_in'  => $row['a_in'] ?? '',
                'a_out' => $row['a_out'] ?? ''
            ];
        }
    }

    if (empty($weeks)) {
        return [
            [
                'week_index' => 0,
                'week_label' => 'Week 1',
                'rows' => array_map(fn($i) => ['date' => '', 'm_in' => '', 'm_out' => '', 'a_in' => '', 'a_out' => ''], range(0, 5))
            ]
        ];
    }

    foreach ($weeks as &$week) {
        for ($i = 0; $i < 6; $i++) {
            if (!isset($week['rows'][$i])) {
                $week['rows'][$i] = ['date' => '', 'm_in' => '', 'm_out' => '', 'a_in' => '', 'a_out' => ''];
            }
        }
        ksort($week['rows']);
        $week['rows'] = array_values($week['rows']);
    }

    return array_values($weeks);
}

$ojtWeeks = loadOjtWeeks($pdo, $_SESSION['user_id'], $current_user_type);
?>

<?php
$colors = ['#d63ba5', '#1abc9c', '#3498db', '#9b59b6'];
$color = $colors[array_rand($colors)];
$page = 'messages';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Virtual Rooms</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
        }

        body {
            background: #f5f6fa;

            /* prevent content shift when navbar appears */
            margin: 0;
            padding-top: 70px;
        height: 100vh;
            overflow: auto;
        }

        /* SIDEBAR */
        .sidebar {
            width: 240px;
            background: #fff;
            position: fixed;
            padding: 20px;
            border-right: 1px solid #ddd;
            overflow-y: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .sidebar a {
            display: block;
            align-items: center;
            padding: 10px 12px;
            color: #333;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 6px;
            font-size: 16px;
            font-weight: 500;
        }

        .sidebar a:hover {
            background: #f0f0f0;
        }

        .sidebar a.active {
            background: #ffe5d9;
            color: #ff6b2c;
        }

        .rooms-list {
            font-size: 11px;
            color: #585858;
            margin-top: 20px;
        }

        .room-item {
            padding: 8px 10px;
            border-radius: 10px;
            font-size: 13px;
        }

        .room-link {
            text-decoration: none;
            display: block;
            margin: 4px;
        }

        .room-link .room-item:hover {
            cursor: pointer;
        }

        /* ACTIVE ROOM */
        .active-room {
            background: #ffe5d9;
            color: #ff6b2c;
            font-weight: bold;
            cursor: default;
        }

        /* MAIN */
        .main {
            margin-left: 260px;
            padding: 20px;
            background-color: #fff;
            height: 100%;
        }

        .room-card {
            border-radius: 12px 12px 0px 0px;
            color: white;
            padding: 15px;
        }

        .room-footer {
            background: #fff;
            padding: 10px;
            border-radius: 0 0 12px 12px;
            text-align: center;
        }

        .enter-btn {
            background: #f4a62a;
            border: none;
            color: #fff;
            font-weight: 600;
            padding: 5px 15px;
            border-radius: 8px;
        }

        /* SECTION  */
        .section {
            display: none;

        }

        .section.active {
            display: block;
        }

        /* CHAT DESIGN */

        .chat-container {
            margin: -20px;
            display: flex !important;
            height: calc(100vh - 70px);
            width: calc(100% + 40px);
            overflow: hidden;
        }

        .chat-list,
        .profile-sidebar {
            width: 280px;
            overflow-y: auto;
            flex-shrink: 0;
        }

        .chat-list {
            border-right: 1px solid #eee;
        }

        .message-area {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
        }

        .chat-inner-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 15px;
            background: #F9F9F9;
        }

        #text {
            font-size: 14px;
        }

        .fw-bold {
            font-size: 15px;
        }

        .message-content {
            flex-grow: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            background: #FCFCFC;
        }

        .chat-input-section {
            padding: 15px 25px;
            display: flex;
            align-items: center;
            gap: 25px;
            border-top: 1px solid #eee;
            background: #fff;
            margin-top: auto;
        }

        .message-input-box {
            flex-grow: 1;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 25px;
            padding: 8px 20px;
            outline: none;
        }

        /* COLUMN 3: PROFILE SIDEBAR (Fixed Width) */
        .profile-sidebar {
            width: 300px;
            flex-shrink: 0;
            overflow-y: auto;
        }

        /* TAB CONTAINER */
        .tab-item {
            flex: 1;
            padding-bottom: 10px;
            cursor: pointer;
            font-weight: 600;
            color: #888;
            text-align: center;
        }

        .tab-item.active-tab {
            color: #29335C;
        }

        .avatar-circle {
            width: 45px;
            height: 45px;
            background-color: #e0e0e0;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .big-avatar {
            width: 100px;
            height: 100px;
            background-color: #e0e0e0;
            border-radius: 50%;
            margin-bottom: 15px;
        }

        /* TAB SLIDER SYSTEM */
        .media-tabs {
            display: flex;
            width: 100%;
            position: relative;
            border-bottom: 2px solid #eee;
            margin-top: 20px;
        }

        .content-pane {
            display: none;
            width: 100%;
        }

        .active-pane {
            display: block;
        }

        /* THE SLIDING LINE */
        .tab-indicator {
            position: absolute;
            bottom: -2px;
            left: 0;
            height: 3px;
            width: 50%;
            background: #29335C;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .tab-content-area {
            position: relative;
            width: 100%;
            align-self: stretch;
        }

        .content-pane.active-pane {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ICONS & BUBBLES */
        .icon-btn {
            font-size: 1.3rem;
            color: #29335C;
            cursor: pointer;
        }

        .send-btn {
            color: #29335C;
            font-size: 1.3rem;
            transform: rotate(0deg);
        }

        .bubble {
            max-width: 70%;
            padding: 7px 15px;
            border-radius: 20px;
            margin-bottom: 8px;
        }

        .incoming {
            background: #ffcc80;
            color: #333;
            align-self: flex-start;
        }

        .outgoing {
            background: #f8d7da;
            color: #333;
            align-self: flex-end;
        }

        .chat-entry-hidden {
            display: none !important;
        }

        /*susu*/
        /* chat */
        .chat-slide-track { display: contents; }

        .chat-panel-screen { display: contents; }

        .mobile-chat-header, .mobile-profile-header { display: none; }


        /* TRACK RENDERED HOURS */
        /* Whole week card */
        .ojt-week-block {
            background: #fafafa;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 24px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: 1px solid #c0c0c0;
            text-align: center;
        }

        /* Table */
        .ojt-table {
            width: 100%;
            /* border-collapse: separate;
            border-spacing: 0 8px; */
            /* border-collapse: collapse;
            border-spacing: 0; */
            align-items: center;
        }

        /* Headers */
        .ojt-table th {
            padding: 10px;
            font-size: 14px;
            font-weight: 600;
            color: #29303b;
            /* background: #eef1f9; */
            /* border: 1px solid #e5e7eb; */
        }

        /* Cells */
        .ojt-table td {
            padding: 4px;
        }

        /* Inputs */
        .ojt-table input[type="date"],
        .ojt-table input[type="time"],
        .ojt-week-label {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            text-align: center;
            background: #fff;
        }

        /* Focus effect */
        .ojt-table input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.15);
        }

        .ojt-group {
            background: rgb(185, 186, 237);
            border: 1px solid #e5e7eb;
        }

        /* Morning section */
        .th-morning,
        .sub-morning,
        .td-morning {
            background: #ffe9c6;
            /* border: 1px solid #ffcc80; */
        }

        /* Afternoon section */
        .th-afternoon,
        .sub-afternoon,
        .td-afternoon {
            background: #ffdec4;
            /* border: 1px solid #ffb74d; */
        }

        /* Hours display */
        .ojt-hrs-val,
        .ojt-daily-val {
            font-weight: 600;
            color: #111827;
        }

        /* Total chip */
        .ojt-total-chip {
            background: #2563eb;
            color: white;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
        }

        /* Week title */
        .ojt-week-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        /* Add week button */
        .ojt-add-btn {
            border: 2px dashed #cbd5e1;
            background: white;
            padding: 14px;
            border-radius: 12px;
            width: 100%;
            cursor: pointer;
            transition: 0.2s;
        }

        .ojt-add-btn:hover {
            background: #f8fafc;
        }

        /* END OF TRACK RENDERED HOURS */

        /*MOBILE SIDEBAR & LAYOUT*/
        @media (max-width: 768px) {

            .sidebar {
                width: 60px;
                padding: 10px 0;
                display: flex;
                flex-direction: column;
                align-items: center;
                overflow: hidden;
            }

            /* Hide all text labels */
            .sidebar .sidebar-text,
            .rooms-list h6 {
                display: none !important;
            }

            /* Icon-only links */
            .sidebar a {
                width: 44px;
                height: 44px;
                display: flex !important;
                align-items: center;
                justify-content: center;
                border-radius: 12px;
                margin-bottom: 8px;
                font-size: 1.2rem;
                padding: 0;
            }

            /* Rooms list centering */
            .rooms-list {
                display: flex;
                flex-direction: column;
                align-items: center;
                margin-top: 10px;
                width: 100%;
            }

            .room-link {
                display: flex !important;
                justify-content: center;
                width: 100%;
                text-decoration: none;
            }

            /* Room initial bubble */
            .room-item {
                width: 44px !important;
                height: 44px !important;
                border-radius: 12px !important;
                background: #e8e8e8 !important;
                color: #555 !important;
                font-weight: bold !important;
                font-size: 1.1rem !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                margin-bottom: 8px !important;
                padding: 0 !important;
            }

            /* Hide the letter text inside .room-item directly (only show .room-initial) */
            .room-item .sidebar-text {
                display: none !important;
            }

            .room-initial {
                display: flex !important;
                align-items: center;
                justify-content: center;
            }

            /* Active room same style */
            .active-room {
                width: 44px !important;
                height: 44px !important;
                border-radius: 12px !important;
                background: #ffdac8 !important;
                color: #ff6b2c !important;
                font-weight: bold !important;
                font-size: 1.1rem !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                margin: 0 auto 8px auto !important;
            }

            .btn-join-top {
                display: none !important;
            }

            .fab-join {
                display: flex !important;
            }

            /*===MOBILE CHAT PANEL===*/
            .chat-container {
                position: fixed;
                top: 70px;
                left: 60px;
                right: 0;
                bottom: 0;
                width: auto;
                margin: 0;
                overflow: hidden;
                display: flex !important;
                flex-direction: row;
            }

            .chat-slide-track {
                display: flex !important;
                width: 300%;
                height: 100%;
                transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
                transform: translateX(0%); /* Panel 1 default */
            }

            /* slide to panel 2 */
            .chat-slide-track.show-chat {
                transform: translateX(calc(-100% / 3));
            }

            /* slide to panel 3 */
            .chat-slide-track.show-chat.show-profile {
                transform: translateX(calc(-200% / 3));
            }

            .chat-panel-screen {
                width: calc((100vw - 60px));
                flex-shrink: 0;
                height: 100%;
                overflow-y: auto;
                background: #fff;
                display: flex !important;
                flex-direction: column;
            }

            .chat-panel-screen:nth-child(2) {
                overflow: hidden;
            }

            .chat-panel-screen .chat-list {
                width: 100% !important;
                overflow-y: auto;
                height: 100%;
                border: none !important;
            }

            .chat-panel-screen .message-area {
                height: 100%;
                width: 100% !important;
                border: none;
                overflow: hidden;
            }

            .chat-panel-screen .profile-sidebar {
                width: 100% !important;
                padding: 20px;
                display: flex;
                flex-direction: column;
                align-items: center;
                border: none !important;
            }

            .message-area > .p-3.border-bottom {
                display: none;
            }

            .mobile-chat-header {
                display: flex !important;
                align-items: center;
                padding: 12px 16px;
                border-bottom: 1px solid #eee;
                background: #fff;
                gap: 12px;
                flex-shrink: 0;
            }

            .mobile-profile-header {
                display: flex !important;
                align-items: center;
                padding: 12px 16px;
                border-bottom: 1px solid #eee;
                gap: 12px;
                flex-shrink: 0;
                width: 100%;
            }

            .mobile-back-btn {
                background: none;
                border: none;
                font-size: 1.2rem;
                color: #333;
                cursor: pointer;
                padding: 4px 8px;
            }

            .mobile-chat-header .chat-name {
                flex-grow: 1;
                font-weight: 700;
                font-size: 1rem;
            }

            .mobile-info-btn {
                background: none;
                border: none;
                font-size: 1.2rem;
                color: #ff6b2c;
                cursor: pointer;
            }
        }

        /*room*/
        .room-initial {
            display: none;
        }

        /* FAB */
        .fab-join {
            display: none;
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 56px;
            height: 56px;
            background: #0B5ED7;
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 4rem;
            padding-bottom: 16px;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 16px rgba(0,0,0,0.25);
            cursor: pointer;
            z-index: 999;
        }
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <a href="?section=home<?php if ($current_room_id)
            echo "&room_id=$current_room_id"; ?>"
            class="sidebar-link <?= $current_section === 'home' ? 'active' : '' ?>">
            <i class="fa-solid fa-house me-1"></i> <span class="sidebar-text">Home</span>
        </a>

        <a href="?section=chats<?php if ($current_room_id)
            echo "&room_id=$current_room_id"; ?>" class="sidebar-link 
            <?= $current_section === 'chats' ? 'active' : '' ?>">
            <i class="fa-solid fa-comments me-1"></i> <span class="sidebar-text">Chats</span>
        </a>

        <a href="?section=application<?php if ($current_room_id)
            echo "&room_id=$current_room_id"; ?>" class="sidebar-link
            <?= $current_section === 'application' ? 'active' : '' ?>">
            <i class="fa-solid fa-user-group me-1"></i> <span class="sidebar-text">Application</span>
        </a>

        <a href="?section=hours<?php if ($current_room_id)
            echo "&room_id=$current_room_id"; ?>" class="sidebar-link
            <?= $current_section === 'hours' ? 'active' : '' ?>">
            <i class="fa-solid fa-clock me-2"></i> <span class="sidebar-text">Hours</span>
        </a>

        <div class="rooms-list">
            <hr><br>
            <h6>ROOMS</h6>

            <?php foreach ($rooms as $room): ?>

                    <?php if ($current_room_id == $room['id']): ?>

                            <!-- CURRENT ROOM (NOT CLICKABLE) -->
                            <div class="room-item active-room">
                                <span class="room-initial"><?= strtoupper(substr(trim($room['room_name']), 0, 1)) ?></span>
                                <span class="sidebar-text"><?= $room['room_name'] ?></span>
                            </div>

                    <?php else: ?>

                            <!-- CLICKABLE ROOM -->
                            <a href="?room_id=<?= $room['id'] ?>" class="room-link">
                                <div class="room-item">
                                    <span class="room-initial"><?= strtoupper(substr(trim($room['room_name']), 0, 1)) ?></span>
                                    <span class="sidebar-text"><?= $room['room_name'] ?></span>
                                </div>
                            </a>

                    <?php endif; ?>

            <?php endforeach; ?>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div id="home" class="section <?= $current_section === 'home' ? 'active' : '' ?>">
        <div class="main">
            <?php if ($current_room_id): ?>

                    <?php include 'chat-room-content.php'; ?>

            <?php else: ?>

                    <!-- DEFAULT DASHBOARD VIEW -->
                    <div class="d-flex justify-content-between align-items-center">
                        <h3><strong>Virtual Rooms</strong></h3>
                        <button class="btn btn-primary btn-join-top" data-bs-toggle="modal" data-bs-target="#joinRoomModal">
                            + Join a Room
                        </button>
                    </div>

                    <div class="row mt-4">
                        <?php foreach ($rooms as $room): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card shadow-sm" style="border-radius: 12px; border: 1px solid #ccc;">

                                        <div class="room-card" style="background: <?= $color ?>">
                                            <div>
                                                <h5 style="text-color <? $color ?>">
                                                    <?= $room['room_name'] ?>
                                                </h5>
                                                <small>
                                                    <?= $room['full_name'] ?> (
                                                    <?= $room['role'] ?>)
                                                </small>
                                            </div>

                                        </div>

                                        <div class="room-footer">
                                            <form method="GET">
                                                <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                                                <button class="enter-btn">Enter Room</button>
                                            </form>
                                        </div>

                                    </div>
                                </div>
                        <?php endforeach; ?>
                    </div>
            <?php endif; ?>
        </div>

        <div class="modal fade" id="joinRoomModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content rounded-4">

                    <!-- HEADER -->
                    <div class="modal-header">
                        <h5 class="modal-title"><strong>Join a Room</strong></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <!-- BODY -->
                    <div class="modal-body">

                        <form method="POST" action="join-room.php">

                            <!-- ROOM CODE -->
                            <div class="mb-3">
                                <label class="form-label">Room Code</label>
                                <input type="text" name="room_code" class="form-control" placeholder="Enter room code"
                                    required>
                            </div>

                            <!-- ROOM NAME
                        <div class="mb-3">
                            <label class="form-label">Room Name</label>
                            <input type="text" name="room_name" class="form-control" placeholder="Enter room name">
                        </div>
                         -->
                            <!-- BUTTON -->
                            <div class="text-end">
                                <button type="submit" class="btn btn-warning px-4">
                                    Join
                                </button>
                            </div>

                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="chats" class="section <?= $current_section === 'chats' ? 'active' : '' ?>">
        <div class="main">
            <div class="chat-container">

                <div class="chat-slide-track" id="chatSlideTrack">
                    <div class="chat-panel-screen">
                        <div class="chat-list">
                            <div class="p-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h3><strong>Chats</strong></h3>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="toggleNewChat()" title="New Chat"
                                        id="new-chat-btn">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                </div>

                                <!-- Existing chat filter -->
                                <input type="text" id="chat-search" class="form-control form-control-sm mt-2"
                                    placeholder="Search conversations..." oninput="filterChats()">

                                <!-- New chat search (hidden by default) -->
                                <div id="new-chat-panel" style="display:none; margin-top: 10px;">
                                    <input type="text" id="user-search-input" class="form-control form-control-sm"
                                        placeholder="Search all users..." oninput="searchAllUsers()">
                                    <div id="user-search-results" class="mt-1 border rounded"
                                        style="background:#fff; max-height:220px; overflow-y:auto; font-size:13px;">
                                    </div>
                                </div>

                                <div id="chat-users-list">
                                    <div>
                                        <?php foreach ($chatUsers as $chat): ?>
                                                <?php
                                                $other_id = $chat['chat_user_id'];
                                                $other_type = $chat['chat_user_type'];

                                                $name = getUserName($pdo, $other_id, $other_type);
                                                ?>
                                                <a href="?chat_id=<?= $other_id ?>&chat_type=<?= $other_type ?>&section=chats"
                                                    class="d-block text-decoration-none text-dark mb-2 chat-user-entry"
                                                    data-name="<?= strtolower(htmlspecialchars($name)) ?>">
                                                    <div class="chat-user-item d-flex align-items-center p-3 border-bottom
                                                    <?= ($current_chat_id == $other_id) ? 'bg-light' : '' ?>">

                                                        <div class="avatar-circle me-3"></div>
                                                        <div>
                                                            <div class="fw-bold"><?= htmlspecialchars($name) ?></div>
                                                            <small class="text-muted">Click to open chat</small>
                                                        </div>
                                                    </div>
                                                </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!--susu (message)-->
                    <div class="chat-panel-screen">
                        <!-- Mobile header with back + info -->
                        <div class="mobile-chat-header">
                            <button class="mobile-back-btn" onclick="mobileChatBack()">
                                <i class="fa-solid fa-arrow-left"></i>
                            </button>
                            <span class="chat-name" id="mobileChatName">
                                <?php if ($current_chat_id): ?>
                                        <?= getUserName($pdo, $current_chat_id, $current_chat_type) ?>
                                <?php endif; ?>
                            </span>
                            <button class="mobile-info-btn" onclick="mobileChatInfo()">
                                <i class="fa-solid fa-circle-info"></i>
                            </button>
                        </div>

                        <div class="message-area">
                            <!-- Desktop header (hidden on mobile via CSS) -->
                            <div class="p-3 border-bottom">
                                <strong id="desktopChatName">
                                    <?php if ($current_chat_id): ?>
                                            <?= getUserName($pdo, $current_chat_id, $current_chat_type) ?>
                                    <?php endif; ?>
                                </strong>
                            </div>

                            <div class="message-content">
                                <?php foreach ($messages as $msg): ?>
                                        <?php
                                        $isMe = $msg['sender_id'] == $_SESSION['user_id'];
                                        ?>
                                        <div class="bubble <?= $isMe ? 'outgoing' : 'incoming' ?>">
                                            <small>
                                                <?= getUserName($pdo, $msg['sender_id'], $msg['sender_type']) ?>
                                            </small><br>
                                            <?= htmlspecialchars($msg['message']) ?>
                                        </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Send form -->
                            <form method="POST" action="message-db.php" class="p-3 border-top d-flex">
                                <input type="hidden" name="receiver_id" value="<?= $current_chat_id ?>">
                                <input type="hidden" name="receiver_type" value="<?= $current_chat_type ?>">
                                <input type="text" name="message" class="form-control me-2" placeholder="Type message..." required>
                                <button class="btn btn-primary">Send</button>
                            </form>
                        </div>
                    </div>

                    <!--susu(profile sidebar) -->
                    <div class="chat-panel-screen">
                        <div class="mobile-profile-header">
                            <button class="mobile-back-btn" onclick="mobileProfileBack()">
                                <i class="fa-solid fa-arrow-left"></i>
                            </button>
                            <span class="fw-bold">Profile Info</span>
                        </div>

                        <div class="profile-sidebar">
                            <div class="big-avatar"></div>

                            <?php if ($current_chat_id): ?>
                                    <?php
                                    $profile = getUserProfile($pdo, $current_chat_id, $current_chat_type);
                                    ?>

                                    <h5 class="fw-bold">
                                        <?= htmlspecialchars($profile['full_name']) ?>
                                    </h5>

                                    <div class="w-100 text-start mt-3" style="font-size: 0.85rem;">
                                        <div class="mb-3">
                                            <i class="fa-solid fa-envelope me-2 text-muted"></i>
                                            <?= htmlspecialchars($profile['email'] ?? 'No email') ?>
                                        </div>
                                    </div>

                            <?php else: ?>
                                    <h5 class="fw-bold">No chat selected</h5>
                            <?php endif; ?>

                            <div class="media-tabs" id="profileTabs">
                                <div class="tab-item active-tab" onclick="switchTab('media')">Media</div>
                                <div class="tab-item" onclick="switchTab('files')">Files</div>
                                <div class="tab-indicator" id="tabIndicator"></div>
                            </div>

                            <div class="tab-content-area">
                                <div id="mediaPane" class="content-pane active-pane">
                                    <div class="media-grid">
                                        <div class="media-box"></div>
                                        <div class="media-box" style="background:#666;"></div>
                                        <div class="media-box" style="background:#999;"></div>
                                    </div>
                                </div>

                                <div id="filesPane" class="content-pane">
                                    <div class="mt-2" style="width: 100%; align-self: stretch">
                                        <div class="py-2 border-bottom small d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-file-pdf text-danger"></i> Internship_Form.pdf
                                        </div>
                                        <div class="py-2 border-bottom small d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-file-word text-primary"></i> Resume_Draft.docx
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="application" class="section <?= $current_section === 'application' ? 'active' : '' ?>">
        <div class="main">
            <h3 class="fw-bold mb-4">My Applications</h3>

            <?php if (empty($bookmarkedInternships)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fa fa-bookmark fa-2x mb-3 d-block"></i>
                        You haven't expressed interest in any internship yet.<br>
                        <a href="applied-Internship-programs.php" class="btn btn-sm btn-warning mt-3">
                            Browse Internships
                        </a>
                    </div>
            <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($bookmarkedInternships as $b):
                            $phaseColors = [
                                'Internship Confirmed' => ['bg' => '#d1fae5', 'color' => '#065f46'],
                                'Documents Submitted' => ['bg' => '#dbeafe', 'color' => '#1e40af'],
                                'Application Submitted' => ['bg' => '#fef9c3', 'color' => '#854d0e'],
                                'Resume Uploaded' => ['bg' => '#fce7f3', 'color' => '#9d174d'],
                                'No Progress' => ['bg' => '#f3f4f6', 'color' => '#6b7280'],
                            ];
                            $pc = $phaseColors[$b['current_phase']] ?? ['bg' => '#f3f4f6', 'color' => '#6b7280'];
                            ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card border-0 shadow-sm rounded-4 h-100">
                                        <div class="card-body d-flex flex-column gap-2 p-4">

                                            <!-- Title & Company -->
                                            <h6 class="fw-bold mb-0" style="color:#272f54;">
                                                <?= htmlspecialchars($b['title']) ?>
                                            </h6>
                                            <p class="text-muted mb-0" style="font-size:13px;">
                                                <i class="fa fa-building me-1"></i>
                                                <?= htmlspecialchars($b['company']) ?>
                                            </p>

                                            <!-- Location -->
                                            <?php if (!empty($b['location'])): ?>
                                                    <p class="text-muted mb-0" style="font-size:12px;">
                                                        <i class="fa fa-map-marker-alt me-1"></i>
                                                        <?= htmlspecialchars($b['location']) ?>
                                                    </p>
                                            <?php endif; ?>

                                            <!-- Program -->
                                            <?php if (!empty($b['program'])): ?>
                                                    <p class="text-muted mb-0" style="font-size:12px;">
                                                        <i class="fa fa-graduation-cap me-1"></i>
                                                        <?= htmlspecialchars($b['program']) ?>
                                                    </p>
                                            <?php endif; ?>

                                            <!-- Deadline -->
                                            <?php if (!empty($b['deadline'])): ?>
                                                    <p class="text-muted mb-0" style="font-size:12px;">
                                                        <i class="fa fa-calendar me-1"></i>
                                                        Deadline: <?= htmlspecialchars($b['deadline']) ?>
                                                    </p>
                                            <?php endif; ?>

                                            <!-- Phase badge -->
                                            <div class="mt-1">
                                                <span style="background:<?= $pc['bg'] ?>; color:<?= $pc['color'] ?>;
                                    padding:3px 12px; border-radius:99px; font-size:11px; font-weight:600;">
                                                    <?= htmlspecialchars($b['current_phase']) ?>
                                                </span>
                                            </div>

                                            <!-- Interested date -->
                                            <p class="text-muted mb-0 mt-auto" style="font-size:11px;">
                                                Interested since <?= date("M d, Y", strtotime($b['created_at'])) ?>
                                            </p>

                                            <!-- Open button -->
                                            <a href="application-progress.php?internship_id=<?= $b['internship_id'] ?>"
                                                class="btn btn-sm fw-semibold mt-2"
                                                style="background:#272f54; color:white; border-radius:8px;">
                                                <i class="fa fa-arrow-right me-1"></i> Open
                                            </a>
                                            <form method="POST" action="cancel-application.php"
                                                onsubmit="return confirm('Are you sure you want to cancel this application?')">
                                                <input type="hidden" name="bookmark_id" value="<?= $b['bookmark_id'] ?>">
                                                <button type="submit" class="btn btn-sm fw-semibold mt-1 w-100"
                                                    style="background:#fee2e2; color:#991b1b; border-radius:8px; border:none;">
                                                    <i class="fa fa-times me-1"></i> Cancel Application
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                        <?php endforeach; ?>
                    </div>
            <?php endif; ?>
        </div>
    </div>


            <!-- TRACK RENDERED HOURS  -->
    <div id="hours" class="section <?= $current_section === 'hours' ? 'active' : '' ?>">
        <div class="main">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h3>Rendered Hours</h3>
                    <p class="text-muted mb-0">OJT Daily Time Record</p>
                </div>
                <div class="d-flex align-items-center gap-2 text-end">
                    <label style="font-size:16px; color:#555; font-weight:500;">OJT Hours Required:</label>
                    <input type="number" id="ojt-required-hrs" value="486" min="1" oninput="ojtRequiredHoursChanged(this)"
                        style="width:70px; border:0.5px solid; border-radius:6px;
                            padding:4px 8px; font-size:14px; font-weight:500; color:#29335C; text-align:center;">
                    <span>hrs</span>
                    <!-- <button onclick="window.print()" class="btn btn-sm ms-2"
                        style="background:#29335C; color:#fff; border-radius:6px; font-size:13px;">
                        <i class="fa-solid fa-print me-1"></i> Print
                    </button> -->
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="card border-0 shadow-sm rounded-3 p-3 mb-2" style="background-color: #29335C;">
                <div class="d-flex justify-content-between" style="color:#fff; margin-bottom:6px;">
                    <span>Progress</span>
                    <span id="ojt-pct-label">0%</span>
                </div>
                <div style="height:8px; background: #ddd; border-radius:99px; overflow:hidden;">
                    <div id="ojt-progress-fill" style="height:100%; width:0%; border-radius:99px; background:#1abc9c;"></div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row g-2 mb-2" style="margin-top: 4px;">
                <div class="col-md-4">
                    <div style="background: #d9d9d9; border: 1px solid #ababab; border-radius:8px; padding: 1rem; margin-right: 5px;">
                        <div style="letter-spacing:.05em; color:#29335C;">MONTHLY OJT HOURS</div>
                        <div id="ojt-sum-monthly" style="font-size:20px; font-weight:500; color:#29335C;">0h 0m</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div style="background: #d9d9d9; border: 1px solid #ababab; border-radius:8px; padding: 1rem; margin-right: 5px; margin-left: 5px;">
                        <div style="letter-spacing:.05em; color:#29335C;">HOURS COMPLETED</div>
                        <div id="ojt-sum-completed" style="font-size:20px; font-weight:500; color:#29335C;">0h 0m</div>
                   </div>
                </div>
                <div class="col-md-4">
                    <div style="background: #d9d9d9; border: 1px solid #ababab; border-radius:8px; padding: 1rem; margin-left: 5px;">
                        <div style="letter-spacing:.05em; color:#29335C;">REMAINING HOURS</div>
                        <div id="ojt-sum-remaining" style="font-size:20px; font-weight:500; color:#29335C;">486h 0m</div>
                    </div>
                </div>
            </div>

            

            <!-- Weeks Container -->
            <div id="ojt-weeks-container">
                <?php foreach ($ojtWeeks as $week): ?>
                    <div class="ojt-week-block" data-week-id="<?= $week['week_index'] ?>" id="ojt-week-block-<?= $week['week_index'] ?>">
                        <div class="ojt-week-header">
                            <div class="d-flex align-items-center gap-2">
                                <input class="ojt-week-label" type="text" data-field="week_label"
                                    value="<?= htmlspecialchars($week['week_label']) ?>"
                                    placeholder="Week label"
                                    onchange="ojtOnFieldChange(this)">
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="ojt-total-chip ojt-week-total" id="ojt-wtotal-<?= $week['week_index'] ?>">0h</span>
                                <?php if ($week['week_index'] > 0): ?>
                                    <button class="ojt-remove-btn" type="button" onclick="ojtRemoveWeek(<?= $week['week_index'] ?>)" title="Remove">×</button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="ojt-table-scroll">
                            <table class="ojt-table">
                                <thead>
                                    <tr>
                                        <th rowspan="2" class="ojt-group">Date</th>
                                        <th rowspan="2" class="ojt-group">Day</th>
                                        <th class="th-morning" colspan="3" style="background: #FFB62F; border: 1px solid #e5e7eb;">Morning</th>
                                        <th class="th-afternoon" colspan="3" style="background: #FF673A; border: 1px solid #e5e7eb;">Afternoon</th>
                                        <th rowspan="2" class="ojt-group">Daily<br>Hours</th>
                                    </tr>
                                    <tr class="ojt-sub">
                                        <th class="sub-morning" style="background: #f9c565; border: 1px solid #e5e7eb;">In</th>
                                        <th class="sub-morning" style="background: #f9c565; border: 1px solid #e5e7eb;">Out</th>
                                        <th class="sub-morning" style="background: #f9c565; border: 1px solid #e5e7eb;">Hrs</th>
                                        <th class="sub-afternoon" style="background: #f49679; border: 1px solid #e5e7eb;">In</th>
                                        <th class="sub-afternoon" style="background: #f49679; border: 1px solid #e5e7eb;">Out</th>
                                        <th class="sub-afternoon" style="background: #f49679; border: 1px solid #e5e7eb;">Hrs</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($week['rows'] as $ri => $row): ?>
                                        <tr data-row-index="<?= $ri ?>">
                                            <td style="min-width:120px">
                                                <input type="date" data-field="date" value="<?= htmlspecialchars($row['date']) ?>" onchange="ojtOnFieldChange(this)">
                                            </td>
                                            <td style="min-width:50px;text-align:center">
                                                <span class="ojt-day-badge" data-display="day" id="ojt-day-<?= $week['week_index'] ?>-<?= $ri ?>"></span>
                                            </td>
                                            <td class="td-morning" style="min-width:105px">
                                                <input type="time" data-field="mIn" value="<?= htmlspecialchars($row['m_in']) ?>" onchange="ojtOnFieldChange(this)">
                                            </td>
                                            <td class="td-morning" style="min-width:105px">
                                                <input type="time" data-field="mOut" value="<?= htmlspecialchars($row['m_out']) ?>" onchange="ojtOnFieldChange(this)">
                                            </td>
                                            <td class="td-morning" style="min-width:55px">
                                                <span class="ojt-hrs-val" data-display="mhrs" id="ojt-mhrs-<?= $week['week_index'] ?>-<?= $ri ?>">—</span>
                                            </td>
                                            <td class="td-afternoon" style="min-width:105px">
                                                <input type="time" data-field="aIn" value="<?= htmlspecialchars($row['a_in']) ?>" onchange="ojtOnFieldChange(this)">
                                            </td>
                                            <td class="td-afternoon" style="min-width:105px">
                                                <input type="time" data-field="aOut" value="<?= htmlspecialchars($row['a_out']) ?>" onchange="ojtOnFieldChange(this)">
                                            </td>
                                            <td class="td-afternoon" style="min-width:55px">
                                                <span class="ojt-hrs-val" data-display="ahrs" id="ojt-ahrs-<?= $week['week_index'] ?>-<?= $ri ?>">—</span>
                                            </td>
                                            <td style="min-width:62px">
                                                <span class="ojt-daily-val" data-display="daily" id="ojt-daily-<?= $week['week_index'] ?>-<?= $ri ?>">—</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <template id="ojt-week-template">
                <div class="ojt-week-block" data-week-id="">
                    <div class="ojt-week-header">
                        <div class="d-flex align-items-center gap-2">
                            <input class="ojt-week-label" type="text" data-field="week_label" placeholder="Week label" onchange="ojtOnFieldChange(this)">
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="ojt-total-chip ojt-week-total">0h</span>
                            <button class="ojt-remove-btn" type="button" style="display:none;" title="Remove">×</button>
                        </div>
                    </div>
                    <div class="ojt-table-scroll">
                        <table class="ojt-table">
                            <thead>
                                <tr class="ojt-group">
                                    <th rowspan="2" class="ojt-group" style="padding-left:10px;">Date</th>
                                    <th rowspan="2" class="ojt-group">Day</th>
                                    <th class="th-morning" colspan="3" style="background: #FFB62F; border: 1px solid #e5e7eb;">Morning</th>
                                    <th class="th-afternoon" colspan="3" style="background: #FF673A; border: 1px solid #e5e7eb;">Afternoon</th>
                                    <th rowspan="2" class="ojt-group">Daily<br>Hours</th>
                                </tr>
                                <tr class="ojt-sub">
                                    <th class="sub-morning" style="background: #f9c565; border: 1px solid #e5e7eb;">In</th>
                                    <th class="sub-morning" style="background: #f9c565; border: 1px solid #e5e7eb;">Out</th>
                                    <th class="sub-morning" style="background: #f9c565; border: 1px solid #e5e7eb;">Hrs</th>
                                    <th class="sub-afternoon" style="background: #f49679; border: 1px solid #e5e7eb;">In</th>
                                    <th class="sub-afternoon" style="background: #f49679; border: 1px solid #e5e7eb;">Out</th>
                                    <th class="sub-afternoon" style="background: #f49679; border: 1px solid #e5e7eb;">Hrs</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($ri = 0; $ri < 6; $ri++): ?>
                                    <tr data-row-index="<?= $ri ?>">
                                        <td style="min-width:120px">
                                            <input type="date" data-field="date" onchange="ojtOnFieldChange(this)">
                                        </td>
                                        <td style="min-width:50px;text-align:center">
                                            <span class="ojt-day-badge" data-display="day"></span>
                                        </td>
                                        <td class="td-morning" style="min-width:105px">
                                            <input type="time" data-field="mIn" onchange="ojtOnFieldChange(this)">
                                        </td>
                                        <td class="td-morning" style="min-width:105px">
                                            <input type="time" data-field="mOut" onchange="ojtOnFieldChange(this)">
                                        </td>
                                        <td class="td-morning" style="min-width:55px">
                                            <span class="ojt-hrs-val" data-display="mhrs">—</span>
                                        </td>
                                        <td class="td-afternoon" style="min-width:105px">
                                            <input type="time" data-field="aIn" onchange="ojtOnFieldChange(this)">
                                        </td>
                                        <td class="td-afternoon" style="min-width:105px">
                                            <input type="time" data-field="aOut" onchange="ojtOnFieldChange(this)">
                                        </td>
                                        <td class="td-afternoon" style="min-width:55px">
                                            <span class="ojt-hrs-val" data-display="ahrs">—</span>
                                        </td>
                                        <td style="min-width:62px">
                                            <span class="ojt-daily-val" data-display="daily">—</span>
                                        </td>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>

            <!-- Add Week -->
            <div>
                <button onclick="ojtAddWeek()" class="btn btn-outline-secondary w-100 mb-2 rounded-3"
                    style="border-style:dashed; font-size:13px; border-width:0.5px;">
                    <i class="fa-solid fa-plus me-1"></i> Add another week
                </button>
            </div>

            <!-- Save Status & Button -->
            <div id="ojt-save-bar" style="display:none; margin-top:16px; padding:12px; background:#f0f9ff; border-radius:8px; border:1px solid #0ea5e9;">
                <div class="d-flex justify-content-between align-items-center gap-2">
                    <span id="ojt-save-status" style="font-size:13px; color:#0369a1; font-weight:500;">
                        <i class="fa-solid fa-circle me-1" style="color:#f59e0b;"></i>Unsaved changes
                    </span>
                    <button type="button" id="ojt-save-btn" onclick="ojtSaveAll()" class="btn btn-sm" 
                        style="background:#0ea5e9; color:white; border-radius:6px; font-size:12px; font-weight:600;">
                        <i class="fa-solid fa-floppy-disk me-1"></i>Save Changes
                    </button>
                </div>
                <div id="ojt-save-message" style="margin-top:8px; font-size:12px; display:none;"></div>
            </div>
        </div>
    </div>

    <!-- susu (mobile FAB) -->
    <?php if ($current_section === 'home' && !$current_room_id): ?>
            <button class="fab-join" data-bs-toggle="modal" data-bs-target="#joinRoomModal">+</button>
    <?php endif; ?>
</body>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function showSection(event, sectionId, el) {
        if (event) event.preventDefault();

        document.querySelectorAll('.section').forEach(sec => {
            sec.classList.remove('active');
        });

        document.getElementById(sectionId)?.classList.add('active');

        document.querySelectorAll('.sidebar a').forEach(link => {
            link.classList.remove('active');
        });

        if (el) el.classList.add('active');
    }

    function switchTab(type) {
        const indicator = document.getElementById('tabIndicator');
        const mediaPane = document.getElementById('mediaPane');
        const filesPane = document.getElementById('filesPane');
        const tabs = document.querySelectorAll('.tab-item');

        if (type === 'media') {
            indicator.style.transform = 'translateX(0%)';
            tabs[0].classList.add('active-tab');
            tabs[1].classList.remove('active-tab');
            mediaPane.classList.add('active-pane');
            filesPane.classList.remove('active-pane');
        } else {
            indicator.style.transform = 'translateX(100%)';
            tabs[1].classList.add('active-tab');
            tabs[0].classList.remove('active-tab');
            filesPane.classList.add('active-pane');
            mediaPane.classList.remove('active-pane');
        }
    }

    function filterChats() {
        const query = document.getElementById('chat-search').value.toLowerCase().trim();
        document.querySelectorAll('#chat-users-list .chat-user-entry').forEach(entry => {
            const name = entry.dataset.name ?? '';
            if (name.includes(query)) {
                entry.style.setProperty('display', 'block', 'important');
            } else {
                entry.style.setProperty('display', 'none', 'important');
            }
        });
    }

    // Auto-scroll messages to bottom on load
    const msgContent = document.querySelector('.message-content');
    if (msgContent) {
        msgContent.scrollTop = msgContent.scrollHeight;
    }

    function toggleNewChat() {
        const panel = document.getElementById('new-chat-panel');
        const isHidden = panel.offsetParent === null || panel.style.display === 'none' || panel.style.display === '';
        panel.style.display = isHidden ? 'block' : 'none';
        if (isHidden) {
            document.getElementById('user-search-input').focus();
            document.getElementById('user-search-input').value = '';
            document.getElementById('user-search-results').innerHTML = '';
        }
    }

    function searchAllUsers() {
        const q = document.getElementById('user-search-input').value.trim();
        const resultsBox = document.getElementById('user-search-results');

        if (q.length < 1) {
            resultsBox.innerHTML = '';
            return;
        }

        fetch(`search-users.php?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(users => {
                if (users.length === 0) {
                    resultsBox.innerHTML = '<div class="p-2 text-muted">No users found</div>';
                    return;
                }

                resultsBox.innerHTML = users.map(u => `
                <a href="?chat_id=${u.id}&chat_type=${u.type}&section=chats"
                   class="d-flex align-items-center gap-2 p-2 text-dark text-decoration-none border-bottom"
                   style="cursor:pointer;"
                   onmouseover="this.style.background='#f5f5f5'"
                   onmouseout="this.style.background=''">
                    <div class="avatar-circle" style="width:32px;height:32px;"></div>
                    <div>
                        <div class="fw-semibold">${u.full_name}</div>
                        <small class="text-muted text-capitalize">${u.type}</small>
                    </div>
                </a>
            `).join('');
            });
    }

    //susu
    // ── MOBILE CHAT SLIDE NAVIGATION ──
    const track = document.getElementById('chatSlideTrack'); // declared ONCE

    function isMobile() {
        return window.innerWidth <= 768;
    }

    // On page load: if a chat is already open, jump to panel 2
    document.addEventListener('DOMContentLoaded', () => {
        <?php if ($current_chat_id && $current_section === 'chats'): ?>
                if (isMobile() && track) {
                    track.classList.add('show-chat');
                }
        <?php endif; ?>

        // Intercept chat-user clicks on mobile
        document.querySelectorAll('.chat-user-link').forEach(link => {
            link.addEventListener('click', function(e) {
                if (isMobile()) {
                    e.preventDefault();
                    const href = this.href;
                    const name = this.dataset.name;
                    if (track) {
                        track.classList.add('show-chat');
                        track.classList.remove('show-profile');
                    }
                    const mobileNameEl = document.getElementById('mobileChatName');
                    if (mobileNameEl) mobileNameEl.textContent = name;
                    setTimeout(() => { window.location.href = href; }, 200);
                }
            });
        });
    });

    function mobileChatInfo() {
        if (track) {
            track.classList.add('show-chat'); track.classList.add('show-profile');
        }
    }

    function mobileChatBack() {
        if (track) track.classList.remove('show-chat', 'show-profile'); // only show-profile, NOT show-chat
    }

    function mobileProfileBack() {
        if (track) track.classList.remove('show-profile'); // go back to panel 2
    }

    function truncateRoomItems() {
        if (window.innerWidth <= 768) {
            document.querySelectorAll('.room-link .room-item, .active-room').forEach(el => {
                if (!el.dataset.original) el.dataset.original = el.textContent.trim();
                el.textContent = el.dataset.original.trim().charAt(0).toUpperCase();
            });
        } else {
            document.querySelectorAll('.room-link .room-item, .active-room').forEach(el => {
                if (el.dataset.original) el.textContent = el.dataset.original;
            });
        }
    }

    window.addEventListener('resize', truncateRoomItems);
    document.addEventListener('DOMContentLoaded', truncateRoomItems);


    // TRACK RENDERED HOURS
    const OJT_DAYS = ['SUN','MON','TUE','WED','THU','FRI','SAT'];
    const OJT_REQUIRED_HOURS_KEY = 'ojt_required_hours';
    let ojtWeeks = [];
    let ojtWeekCounter = 0;
    let ojtUnsavedChanges = {}; // track {weekId-rowIndex-field: value}
    
    function ojtToMins(t) {
        if (!t) return 0;
        const [h, m] = t.split(':').map(Number);
        return h * 60 + m;
    }
    function ojtCalcMins(inV, outV) {
        const diff = ojtToMins(outV) - ojtToMins(inV);
        return diff > 0 ? diff : 0;
    }
    function ojtFmtHM(mins) {
        if (!mins || mins <= 0) return '0h 0m';
        return Math.floor(mins / 60) + 'h ' + (mins % 60) + 'm';
    }
    function ojtFmtShort(mins) {
        if (!mins || mins <= 0) return '—';
        const h = Math.floor(mins / 60), m = mins % 60;
        return m === 0 ? h + 'h' : h + 'h ' + m + 'm';
    }
    function ojtGetDay(dateStr) {
        if (!dateStr) return '';
        return OJT_DAYS[new Date(dateStr + 'T00:00:00').getDay()];
    }
    function ojtIsWeekend(day) { return day === 'SUN' || day === 'SAT'; }
    
    function ojtRecalcAll() {
        let grand = 0;
        ojtWeeks.forEach(w => {
            let wTotal = 0;
            w.rows.forEach(r => {
                r.mHrs  = ojtCalcMins(r.mIn, r.mOut);
                r.aHrs  = ojtCalcMins(r.aIn, r.aOut);
                r.daily = r.mHrs + r.aHrs;
                if (r.date) r.day = ojtGetDay(r.date);
                wTotal += r.daily;
            });
            w.total = wTotal;
            grand  += wTotal;
        });
    
        const reqVal = parseFloat(document.getElementById('ojt-required-hrs').value);
        const req = ((reqVal > 0) ? reqVal : 486) * 60;
        const rem = Math.max(0, req - grand);
        const pct = Math.min(100, Math.round((grand / req) * 100));

        document.getElementById('ojt-sum-monthly').textContent = ojtFmtHM(grand);
        document.getElementById('ojt-sum-completed').textContent = ojtFmtHM(grand);
        document.getElementById('ojt-sum-remaining').textContent = ojtFmtHM(rem);
        document.getElementById('ojt-progress-fill').style.width = pct + '%';
        document.getElementById('ojt-pct-label').textContent = pct + '%';

        // Fire 100% notification (only once per session)
        if (pct >= 100 && !window.ojtCompletionNotified) {
            window.ojtCompletionNotified = true;
            ojtTriggerCompletion();
        }
        ojtWeeks.forEach((_, i) => ojtRenderWeek(i)); // <-- render all weeks
    
    }
    
    function ojtRenderWeek(wi) {
        const w = ojtWeeks[wi];
        if (!document.getElementById('ojt-week-block-' + w.id)) return;
        document.getElementById('ojt-wtotal-' + w.id).textContent = ojtFmtShort(w.total);
        w.rows.forEach((row, ri) => {
            const mhEl  = document.getElementById('ojt-mhrs-'  + w.id + '-' + ri);
            const ahEl  = document.getElementById('ojt-ahrs-'  + w.id + '-' + ri);
            const dhEl  = document.getElementById('ojt-daily-' + w.id + '-' + ri);
            const dayEl = document.getElementById('ojt-day-'   + w.id + '-' + ri);
            if (mhEl)  { mhEl.textContent  = ojtFmtShort(row.mHrs);  mhEl.className  = 'ojt-hrs-val'   + (row.mHrs  > 0 ? ' has-val' : ''); }
            if (ahEl)  { ahEl.textContent  = ojtFmtShort(row.aHrs);  ahEl.className  = 'ojt-hrs-val'   + (row.aHrs  > 0 ? ' has-val' : ''); }
            if (dhEl)  { dhEl.textContent  = ojtFmtShort(row.daily); dhEl.className  = 'ojt-daily-val' + (row.daily > 0 ? ' has-val' : ''); }
            if (dayEl && row.day) {
                dayEl.textContent = row.day;
                dayEl.className   = 'ojt-day-badge ' + (ojtIsWeekend(row.day) ? 'weekend' : 'weekday');
            }
        });
    }
    
    function ojtInit() {
        ojtWeeks = [];
        ojtWeekCounter = -1;

        ojtLoadRequiredHours();

        document.querySelectorAll('.ojt-week-block').forEach(block => {
            const weekId = parseInt(block.dataset.weekId, 10);
            const labelInput = block.querySelector('.ojt-week-label');
            const weekLabel = labelInput ? labelInput.value.trim() : `Week ${ojtWeeks.length + 1}`;

            const rows = Array.from(block.querySelectorAll('tbody tr')).map(tr => ({
                date: tr.querySelector('[data-field="date"]')?.value || '',
                mIn: tr.querySelector('[data-field="mIn"]')?.value || '',
                mOut: tr.querySelector('[data-field="mOut"]')?.value || '',
                aIn: tr.querySelector('[data-field="aIn"]')?.value || '',
                aOut: tr.querySelector('[data-field="aOut"]')?.value || '',
                mHrs: 0,
                aHrs: 0,
                daily: 0,
                day: ''
            }));

            ojtWeeks.push({ id: weekId, weekIndex: weekId, weekLabel, rows, total: 0 });
            if (weekId > ojtWeekCounter) ojtWeekCounter = weekId;
        });

        if (ojtWeeks.length === 0) {
            ojtAddWeek();
            return;
        }

        ojtRecalcAll();
    }

    function ojtRequiredHoursChanged(el) {
        let value = parseFloat(el.value);
        if (!value || value < 1) {
            value = 1;
            el.value = value;
        }
        localStorage.setItem(OJT_REQUIRED_HOURS_KEY, value);
        ojtRecalcAll();
    }

    function ojtLoadRequiredHours() {
        const saved = localStorage.getItem(OJT_REQUIRED_HOURS_KEY);
        if (saved) {
            const parsed = parseFloat(saved);
            if (parsed > 0) {
                const input = document.getElementById('ojt-required-hrs');
                if (input) input.value = parsed;
            }
        }
    }

    function ojtCloneWeekTemplate(id, wIdx) {
        const template = document.getElementById('ojt-week-template');
        const clone = template.content.firstElementChild.cloneNode(true);
        clone.dataset.weekId = id;
        clone.id = 'ojt-week-block-' + id;
        clone.querySelector('.ojt-week-label').value = `Week ${wIdx + 1}`;

        const rows = clone.querySelectorAll('tbody tr');
        rows.forEach((tr, ri) => {
            tr.dataset.rowIndex = ri;
            const dayEl = tr.querySelector('[data-display="day"]');
            const mhEl = tr.querySelector('[data-display="mhrs"]');
            const ahEl = tr.querySelector('[data-display="ahrs"]');
            const dhEl = tr.querySelector('[data-display="daily"]');

            if (dayEl) dayEl.id = `ojt-day-${id}-${ri}`;
            if (mhEl) mhEl.id = `ojt-mhrs-${id}-${ri}`;
            if (ahEl) ahEl.id = `ojt-ahrs-${id}-${ri}`;
            if (dhEl) dhEl.id = `ojt-daily-${id}-${ri}`;
        });

        const removeBtn = clone.querySelector('.ojt-remove-btn');
        if (removeBtn) {
            removeBtn.style.display = wIdx > 0 ? '' : 'none';
            removeBtn.onclick = () => ojtRemoveWeek(id);
        }

        return clone;
    }

    function ojtOnFieldChange(el) {
        const block = el.closest('.ojt-week-block');
        if (!block) return;

        const weekId = parseInt(block.dataset.weekId, 10);
        const field = el.dataset.field;
        if (!field) return;

        if (field === 'week_label') {
            const week = ojtWeeks.find(w => w.id === weekId);
            if (week) {
                week.weekLabel = el.value.trim();
                const key = `${weekId}-label`;
                ojtUnsavedChanges[key] = el.value.trim();
                ojtShowSaveBar();
            }
            return;
        }

        const tr = el.closest('tr');
        if (!tr) return;

        const rowIndex = parseInt(tr.dataset.rowIndex, 10);
        const week = ojtWeeks.find(w => w.id === weekId);
        if (!week || rowIndex < 0 || rowIndex >= week.rows.length) return;

        week.rows[rowIndex][field] = el.value;
        const key = `${weekId}-${rowIndex}-${field}`;
        ojtUnsavedChanges[key] = el.value;
        ojtShowSaveBar();
        ojtRecalcAll();
    }

    function ojtShowSaveBar() {
        const bar = document.getElementById('ojt-save-bar');
        if (bar && Object.keys(ojtUnsavedChanges).length > 0) {
            bar.style.display = 'block';
        }
    }

    function ojtHideSaveBar() {
        const bar = document.getElementById('ojt-save-bar');
        if (bar) bar.style.display = 'none';
        ojtUnsavedChanges = {};
    }

    function ojtShowMessage(msg, isError = false) {
        const msgEl = document.getElementById('ojt-save-message');
        if (!msgEl) return;
        msgEl.textContent = msg;
        msgEl.style.color = isError ? '#dc2626' : '#059669';
        msgEl.style.display = 'block';
        setTimeout(() => { msgEl.style.display = 'none'; }, 4000);
    }

    async function ojtSaveAll() {
        const btn = document.getElementById('ojt-save-btn');
        const statusEl = document.getElementById('ojt-save-status');
        if (!btn) return;

        const changes = ojtUnsavedChanges;
        if (Object.keys(changes).length === 0) {
            ojtShowMessage('No changes to save.');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Saving...';
        statusEl.innerHTML = '<i class="fa-solid fa-circle me-1" style="color:#f59e0b;"></i>Saving...';

        try {
            const saves = [];

            for (const [key, value] of Object.entries(changes)) {
                if (key.endsWith('-label')) {
                    const weekId = parseInt(key.split('-')[0], 10);
                    saves.push({
                        action: 'save_week_label',
                        week_index: weekId,
                        week_label: value
                    });
                } else {
                    const parts = key.split('-');
                    const weekId = parseInt(parts[0], 10);
                    const rowIndex = parseInt(parts[1], 10);
                    const field = parts[2];
                    saves.push({
                        action: 'save_row',
                        week_index: weekId,
                        row_index: rowIndex,
                        field,
                        value
                    });
                }
            }

            let allSuccess = true;
            for (const save of saves) {
                const body = new URLSearchParams(save);
                const response = await fetch('ojt-hours-db.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body
                });
                if (!response.ok) {
                    const text = await response.text();
                    throw new Error(`Server returned ${response.status}: ${text}`);
                }
                const contentType = response.headers.get('Content-Type') || '';
                if (!contentType.includes('application/json')) {
                    const text = await response.text();
                    throw new Error(`Expected JSON response, got: ${text}`);
                }
                const data = await response.json();
                if (!data.success) {
                    console.warn('Save error:', data.message || data.error);
                    allSuccess = false;
                }
            }

            btn.disabled = false;
            if (allSuccess) {
                btn.innerHTML = '<i class="fa-solid fa-check me-1"></i>Saved!';
                statusEl.innerHTML = '<i class="fa-solid fa-circle-check me-1" style="color:#059669;"></i>All changes saved';
                ojtShowMessage('All changes saved successfully!', false);
                setTimeout(() => {
                    ojtHideSaveBar();
                    btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i>Save Changes';
                }, 2000);
            } else {
                btn.innerHTML = '<i class="fa-solid fa-exclamation-triangle me-1"></i>Error';
                statusEl.innerHTML = '<i class="fa-solid fa-circle me-1" style="color:#dc2626;"></i>Save failed';
                ojtShowMessage('Some changes failed to save. Please try again.', true);
            }
        } catch (error) {
            console.error('Save failed:', error);
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-exclamation-triangle me-1"></i>Error';
            statusEl.innerHTML = '<i class="fa-solid fa-circle me-1" style="color:#dc2626;"></i>Save failed';
            ojtShowMessage(`Save failed: ${error.message}`, true);
        }
    }

    function saveOjtRow(weekId, rowIndex, field, value) {
        fetch('ojt-hours-db.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'save_row',
                week_index: weekId,
                row_index: rowIndex,
                field,
                value
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) console.warn('OJT save error:', data.message || data.error);
        })
        .catch(error => console.error('OJT save failed:', error));
    }

    function saveWeekLabel(weekId, label) {
        fetch('ojt-hours-db.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'save_week_label',
                week_index: weekId,
                week_label: label
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) console.warn('OJT week label save error:', data.message || data.error);
        })
        .catch(error => console.error('OJT week label failed:', error));
    }

    function deleteWeekFromBackend(weekId) {
        fetch('ojt-hours-db.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'delete_week',
                week_index: weekId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) console.warn('OJT delete error:', data.message || data.error);
        })
        .catch(error => console.error('OJT delete failed:', error));
    }

    function ojtAddWeek() {
        const id = ojtWeekCounter + 1;
        const weekLabel = `Week ${ojtWeeks.length + 1}`;
        const block = ojtCloneWeekTemplate(id, ojtWeeks.length);
        document.getElementById('ojt-weeks-container').appendChild(block);

        ojtWeeks.push({
            id,
            weekIndex: id,
            weekLabel,
            rows: Array.from({ length: 6 }, () => ({
                date: '', mIn: '', mOut: '', aIn: '', aOut: '',
                mHrs: 0, aHrs: 0, daily: 0, day: ''
            })),
            total: 0
        });

        ojtWeekCounter = id;
        saveWeekLabel(id, weekLabel);
        ojtRecalcAll();
    }

    function ojtRenderWeek(wi) {
        const w = ojtWeeks[wi];
        const block = document.querySelector(`.ojt-week-block[data-week-id="${w.id}"]`);
        if (!block) return;

        const totalEl = block.querySelector('.ojt-week-total');
        if (totalEl) totalEl.textContent = ojtFmtShort(w.total);

        w.rows.forEach((row, ri) => {
            const tr = block.querySelector(`tr[data-row-index="${ri}"]`);
            if (!tr) return;

            const mhEl = tr.querySelector('[data-display="mhrs"]');
            const ahEl = tr.querySelector('[data-display="ahrs"]');
            const dhEl = tr.querySelector('[data-display="daily"]');
            const dayEl = tr.querySelector('[data-display="day"]');

            if (mhEl) {
                mhEl.textContent = ojtFmtShort(row.mHrs);
                mhEl.className = 'ojt-hrs-val' + (row.mHrs > 0 ? ' has-val' : '');
            }
            if (ahEl) {
                ahEl.textContent = ojtFmtShort(row.aHrs);
                ahEl.className = 'ojt-hrs-val' + (row.aHrs > 0 ? ' has-val' : '');
            }
            if (dhEl) {
                dhEl.textContent = ojtFmtShort(row.daily);
                dhEl.className = 'ojt-daily-val' + (row.daily > 0 ? ' has-val' : '');
            }
            if (dayEl && row.day) {
                dayEl.textContent = row.day;
                dayEl.className = 'ojt-day-badge ' + (ojtIsWeekend(row.day) ? 'weekend' : 'weekday');
            }
        });
    }

    function ojtRemoveWeek(id) {
        const idx = ojtWeeks.findIndex(w => w.id === id);
        if (idx === -1) return;
        ojtWeeks.splice(idx, 1);
        const el = document.querySelector(`.ojt-week-block[data-week-id="${id}"]`);
        if (el) el.remove();
        deleteWeekFromBackend(id);
        if (ojtWeeks.length === 0) {
            ojtAddWeek();
            return;
        }
        ojtRecalcAll();
    }

    document.addEventListener('DOMContentLoaded', ojtInit);

    function ojtTriggerCompletion() {
        // Show in-page banner immediately
        ojtShowCompletionBanner();

        // Notify backend to create DB notifications
        fetch('ojt-completion-notify.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'notify_completion' })
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) console.warn('Completion notify failed:', data.message);
        })
        .catch(err => console.error('Completion notify error:', err));
    }

    function ojtShowCompletionBanner() {
        if (document.getElementById('ojt-completion-banner')) return;
        const banner = document.createElement('div');
        banner.id = 'ojt-completion-banner';
        banner.innerHTML = `
            <div style="
                background: linear-gradient(135deg, #065f46, #059669);
                color: white;
                border-radius: 12px;
                padding: 20px 24px;
                margin-bottom: 16px;
                display: flex;
                align-items: flex-start;
                gap: 16px;
                box-shadow: 0 4px 20px rgba(5,150,105,0.25);
            ">
                <div style="flex:1;">
                    <div style="font-size:17px; font-weight:700; margin-bottom:4px;">
                        Congratulations! You've completed your OJT hours!
                    </div>
                    <div style="font-size:13px; opacity:0.9; margin-bottom:14px;">
                        Your internship adviser and HTE supervisor have been notified. 
                        Please submit your evaluation to complete the process.
                    </div>
                    <a href="student-evaluation.php" style="
                        background: white;
                        color: #065f46;
                        padding: 8px 18px;
                        border-radius: 8px;
                        font-size: 13px;
                        font-weight: 600;
                        text-decoration: none;
                        display: inline-block;
                    ">
                        Submit Student Evaluation →
                    </a>
                </div>
                <button onclick="this.closest('#ojt-completion-banner').remove()" style="
                    background:none; border:none; color:white; font-size:1.3rem;
                    cursor:pointer; opacity:0.7; padding:0; line-height:1;
                ">×</button>
            </div>
        `;

        const hoursSection = document.getElementById('hours').querySelector('.main');
        hoursSection.insertBefore(banner, hoursSection.firstChild);
    }
</script>

</html>