<?php
session_start();
require 'auth.php';
require 'db.php';
$current_room_id = $_GET['room_id'] ?? null;

$isAdviser = isset($_SESSION['role']) && $_SESSION['role'] === 'internship_adviser';
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

$stmt = $pdo->prepare("
    SELECT s.id, s.full_name, si.internship_id, i.company
    FROM students s
    JOIN student_internships si ON s.id = si.student_id
    JOIN internships i ON si.internship_id = i.id
");
$stmt->execute();

$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT 
        s.id,
        s.full_name,
        r.room_name,
        i.company,
        COALESCE(SUM(l.hours_worked), 0) AS total_hours,
        MAX(m.remarks) AS latest_remarks
    FROM students s
    JOIN room_members rm ON s.id = rm.user_id
    JOIN rooms r ON rm.room_id = r.id
    LEFT JOIN student_internships si ON s.id = si.student_id
    LEFT JOIN internships i ON si.internship_id = i.id
    LEFT JOIN ojt_logs l ON s.id = l.student_id
    LEFT JOIN (
    SELECT DISTINCT ON (student_id)
    student_id, remarks
    FROM ojt_remarks
    ORDER BY student_id, updated_at DESC
    ) m ON s.id = m.student_id
    GROUP BY s.id, s.full_name, r.room_name, i.company
");
$stmt->execute();
$statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

//$progressWidth = ($statuses['total_hours'] / 486) * 100;
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

    <title>HTE Adviser | CEE IT Connects</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body {
            background: #f5f6fa;
            margin: 0;
            padding-top: 70px;
            min-height: 100vh;   /* change height to min-height */
            overflow-y: auto;
        }

        .section-panel {
            display: none;
        }

        .section-panel.active {
            display: block;
        }

        /* SIDEBAR */
        .sidebar {
            width: 240px;
            background: #fff;
            position: fixed;
            /* padding: 20px; */
            padding: 20px 0px 20px 20px;
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
            background: #ffdac8;
            color: #ff6b2c;
        }

        .sidebar::-webkit-scrollbar {
            display: none;
        }

        .rooms-list {
            font-size: 11px;
            color: #585858;
            margin-top: 20px;
        }

        .room-item {
            padding: 8px 10px;
            /* margin-bottom: 2px; */
            border-radius: 10px;
            /* background: #f1f1f1; */
            font-size: 13px;
            
        }

        .room-link {
            text-decoration: none;
            display: block;
            /* padding: 0; */
            margin: 4px;
            /* gap: 2px; */
        }

        .room-link .room-item:hover {
            cursor: pointer;
        }

        /* ACTIVE ROOM */
        .active-room {
            background: #ffdac8;
            color: #ff6b2c;
            font-weight: bold;
            cursor: default;
            /* background: #ff6b2c;
            color: #fff;
            font-weight: 600;
            border-radius: 8px;
            cursor: default; */
        }

        /* MAIN */
        .main {
            margin-left: 260px;
            padding: 20px;
            background-color: #f0f2f7;
            min-height: calc(100vh - 70px);
        }

        .room-card {
            border-radius: 12px 12px 0px 0px;
            color: white;
            padding: 15px;
            min-height: 110px;
        }

        .room-footer {
            background: #fff;
            padding: 10px;
            border-radius: 0 0 12px 12px;
            text-align: center;
            border: 1px solid #bbb;
            border-top: none;
        }

        .enter-btn {
            background: #f4a62a;
            border: none;
            color: #fff;
            font-weight: 600;
            padding: 5px 15px;
            border-radius: 8px;
        }

        .search-box {
            display: flex;
            align-items: center;
            gap: 8px;
            background: white;
            border: 1px solid #bbb;
            border-radius: 24px;
            padding: 7px 14px;
            flex: 1;
            max-width: 300px;
        }

        .search-box input {
            border: none;
            outline: none;
            font-size: 13px;
            width: 100%;
            color: #333;
        }

        .avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 8px;
            min-width: 34px;
            min-height: 34px;
            flex-shrink: 0;
        }

        .student-cell {
            display: flex;
            align-items: center;
        }

        .student-card {
            background: white;
            border: 1px solid #bbb;
            border-radius: 8px;
            margin-bottom: 16px;
            overflow: hidden;
        }

        .log-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 18px;
            border-bottom: 1px solid #e0e0e0;
        }

        /*susu*/
        .log-row-top {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }

        .log-row-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .student-info {
            flex: 1;
            min-width: 0;
        }

        .student-info strong {
            display: block;
            font-size: 14px;
        }

        .student-info span {
            font-size: 12px;
            color: #888;
        }

        .log-row select,
        .log-row input[type="number"] {
            padding: 6px 10px;
            border: 1px solid #bbb;
            border-radius: 6px;
            font-size: 13px;
            outline: none;
        }

        .log-row input[type="number"] {
            width: 70px;
        }

        .total-hrs {
            font-weight: 700;
            font-size: 13px;
            white-space: nowrap;
        }

        .btn-log {
            background: #0ea5c8;
            color: white;
            border: none;
            padding: 7px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
        }

        .btn-log:hover {
            background: #0c8fad;
        }

        .remarks-section {
            padding: 14px 18px;
            /* overflow-y: scroll; */
            /* scrollbar-width: none; */
            background-color: #fbfbfb;
        }

        .remarks-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #555;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .remarks-section textarea {
            width: 100%;
            border: 1px solid #bbb;
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 13px;
            resize: vertical;
            min-height: 80px;
            outline: none;
            background: #fbfbfb;
            box-sizing: border-box;
        }

        .remarks-footer {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 10px;
        }

        .remarks-footer select {
            padding: 6px 10px;
            border: 1px solid #bbb;
            border-radius: 6px;
            font-size: 13px;
            outline: none;
        }

        .remarks-footer label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            cursor: pointer;
        }

        .btn-submit {
            margin-left: auto;
            background: #0ea5c8;
            color: white;
            border: none;
            padding: 7px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-submit:hover {
            background: #0c8fad;
        }

        .page-section h2 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .page-section p {
            font-size: 0.85rem;
            color: #888;
            margin-bottom: 16px;
        }

        .search-box {
            display: flex;
            align-items: center;
            gap: 8px;
            background: white;
            border: 1px solid #bbb;
            border-radius: 24px;
            padding: 7px 14px;
            flex: 1;
            max-width: 300px;
        }

        .search-box input {
            border: none;
            outline: none;
            font-size: 13px;
            width: 100%;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        table th {
            padding: 10px 14px;
            text-align: left;
            font-size: 14px;
            font-weight: 600;
        }

        table td {
            padding: 12px 14px;
            vertical-align: middle;
        }

        .avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 8px;
        }

        .student-cell {
            display: flex;
            align-items: center;
        }

        .progress-bar-bg {
            width: 80px;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 4px;
            background: #ff6b2c;
        }

        /*susu*/
        .room-initial { display: none; }
        .room-name-text { display: inline; }
        
        /*===MEDIA QUERY===*/
        @media (max-width: 768px) {
            .sidebar {
                position: fixed !important;
                top: 70px !important;
                left: 0 !important;
                bottom: 0 !important;
                width: 60px !important;
                height: calc(100vh - 70px) !important;
                padding: 10px 0 !important;
                border-radius: 0 !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                overflow-x: hidden !important;
                z-index: 100;
            }

            .sidebar .sidebar-text,
            .rooms-list h6,
            .rooms-list hr {
                display: none !important;
            }

            .sidebar > a {
                width: 44px !important;
                height: 44px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                border-radius: 12px !important;
                margin: 0 auto 8px auto !important;
                font-size: 1.2rem !important;
                padding: 0 !important;
            }

            .sidebar > a i {
                margin: 0 !important;
            }

            .rooms-list {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                margin-top: 0 !important;
                width: 100% !important;
                max-height: unset !important;
                overflow-y: auto;
                overflow-x: hidden !important;
                scrollbar-width: none;
                gap: 8px;
            }

            .room-link {
                display: flex !important;
                justify-content: center !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .room-item {
                width: 44px !important;
                height: 44px !important;
                min-width: 44px !important;
                min-height: 44px !important;
                border-radius: 12px !important;
                background: #e8e8e8 !important;
                color: #555 !important;
                font-weight: bold !important;
                font-size: 1.1rem !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                padding: 0 !important;
                margin: 0 !important;
                overflow: hidden !important;
            }

            .room-item.active-room {
                background: #ffdac8 !important;
                color: #ff6b2c !important;
                margin: 0 auto;
            }

            .room-name-text { display: none !important; }
            .room-initial { display: flex !important; }

            .main {
                margin-left: 70px !important;
                padding: 15px !important;
            }

            /* Room cards — clamp title to 2 lines */
            .room-card h5 {
                display: -webkit-box !important;
                -webkit-line-clamp: 2 !important;
                line-clamp: 2 !important;
                -webkit-box-orient: vertical !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
            }

            .room-card {
                min-height: 110px !important;
                max-height: 110px !important;
                overflow: hidden !important;
            }

            /* Table horizontal scroll */
            #status .search-box {
                max-width: 100% !important;
            }

            .avatar {
                width: 34px !important;
                height: 34px !important;
                min-width: 34px !important;
                min-height: 34px !important;
                border-radius: 50% !important;
                flex-shrink: 0 !important;
            }

            .chat-container {
                position: fixed !important;
                top: 70px !important;
                left: 60px !important;
                right: 0 !important;
                bottom: 0 !important;
                height: calc(100vh - 70px) !important;
                border-radius: 0 !important;
                border: none !important;
            }

            .chat-container .chat-messages {
                flex: 1 !important;
            }

            .log-row {
                flex-wrap: wrap !important;
                gap: 10px !important;
                padding: 12px !important;
            }

            /* Student info takes full width on its own row */
            .log-row .student-info {
                flex: 1 1 auto !important;
                min-width: 0 !important;
            }

            /* Avatar stays circular and doesn't shrink */
            .log-row .avatar {
                flex-shrink: 0 !important;
                align-self: center !important;
            }

            /* Status select and hours input go to their own row */
            .log-row select,
            .log-row input[type="number"] {
                flex: 1 !important;
                min-width: 80px !important;
            }

            /* Make the top part (avatar + name) take full row */
            .log-row-top {
                width: 100% !important;
            }

            /* Controls (select + input) on second row */
            .log-row-controls {
                width: 100% !important;
            }

            .log-row-controls select, .log-row-controls[type="number"] {
                flex: 1;
            }

            /* Remarks footer wraps on mobile */
            .remarks-footer {
                flex-wrap: wrap !important;
                gap: 8px !important;
            }

            .remarks-footer select {
                width: 100% !important;
            }

            .remarks-footer label {
                flex: 1 !important;
            }

            .btn-submit {
                margin-left: auto !important;
            }
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <!-- CHANGES -->
        <a href="#" onclick="showSection('rooms')" class="active"><i class="fa-solid fa-house me-1"></i><span class="sidebar-text">Virtual Rooms</span></a>
        <a href="#" onclick="showSection('status')"><i class="fa-solid fa-calendar-check me-2"></i><span class="sidebar-text">Status</span></a>
        <a href="#" onclick="showSection('remarks')"><i class="fa-solid fa-star me-1"></i><span class="sidebar-text">Remarks</span></a>

        <div class="rooms-list" style="overflow-y: auto; overflow-x: hidden; max-height: 400px; scrollbar-width: none;">
            <hr><br>
            <h6>ROOMS</h6>

            <?php foreach ($rooms as $room): ?>

                <?php if ($current_room_id == $room['id']): ?>

                    <!-- CURRENT ROOM (NOT CLICKABLE) -->
                    <div class="room-item active-room">
                        <span class="room-initial"><?= strtoupper(substr(trim($room['room_name']), 0, 1)) ?></span>
                        <span class="room-name-text"><?= htmlspecialchars($room['room_name']) ?></span>
                    </div>

                <?php else: ?>

                    <!-- CLICKABLE ROOM -->
                    <a href="?room_id=<?= $room['id'] ?>" class="room-link">
                        <div class="room-item">
                            <span class="room-initial"><?= strtoupper(substr(trim($room['room_name']), 0, 1)) ?></span>
                            <span class="room-name-text"><?= htmlspecialchars($room['room_name']) ?></span>
                        </div>
                    </a>

                <?php endif; ?>

            <?php endforeach; ?>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        <!-- Room Section -->
        <div id="rooms" class="section-panel active">
            <?php if ($current_room_id): ?>

                <?php include 'chat-room-content.php'; ?>

            <?php else: ?>

                <!-- DEFAULT DASHBOARD VIEW -->
                
                <div class="d-flex justify-content-between align-items-center">
                    <h3><strong>Virtual Rooms</strong></h3>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#joinRoomModal">
                        + Join a Room
                    </button>
                </div>

                <div class="row mt-1 g-4">
                    <?php foreach ($rooms as $room): ?>
                        <div class="col-md-4">
                            <div class="card shadow-sm border-0" style="border-radius: 12px;">

                                <div class="room-card" style="background: <?= $color ?>">
                                    <h5 style="color <?= $color ?>">
                                        <?= $room['room_name'] ?>
                                    </h5>
                                    <small>
                                        <?= $room['full_name'] ?> (
                                        <?= $room['role'] ?>)
                                    </small>
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


        <div id="status" class="section-panel">
            <h4><strong>Monitor OJT progress and HTE remarks</strong></h4><br>

            <div style="display:flex;gap:10px;margin-bottom:16px;align-items:center;">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchInput" placeholder="Search student" oninput="filterTable()">
                </div>
                <select id="roomFilter" onchange="filterTable()"
                    style="padding:7px 14px;border:1px solid #bbb;border-radius:24px;font-size:12px;">
                    <option value="">All Rooms</option>
                    <?php foreach ($rooms as $r): ?>
                        <option value="<?= htmlspecialchars($r['room_name']) ?>">
                            <?= htmlspecialchars($r['room_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="background: #fbfbfb;border:1px solid #bbb;border-radius:8px;overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>STUDENT</th>
                            <th>ROOM</th>
                            <th>COMPANY</th>
                            <th>HOURS</th>
                            <th>PROGRESS</th>
                            <th>HTE REMARKS</th>
                        </tr>
                    </thead>
                    <tbody id="all-students-tbody">
                        <?php foreach ($statuses as $status): ?>
                            <tr data-room="<?= htmlspecialchars($status['room_name']) ?>">
                                <td>
                                    <div class="student-cell">
                                        <div class="avatar" style="background: #ff2c8f;"><strong>R</strong></div>
                                        <h6><?= htmlspecialchars($status['full_name']) ?></h6>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($status['room_name']) ?></td>
                                <td><?= htmlspecialchars($status['company']) ?></td>
                                <td><strong><?= $status['total_hours'] ?></strong> / 486</td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <div class="progress-bar-bg">
                                            <?php $progressWidth = round(($status['total_hours'] / 486) * 100, 2) ?>
                                            <div class="progress-bar-fill" style="width:<?= $progressWidth; ?>%">
                                            </div>
                                        </div>
                                        <span><?= $progressWidth; ?>%</span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($status['latest_remarks'] ?? 'No remarks') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <!--
                        <tr data-room="Room 2C">
                            <td>
                                <div class="student-cell">
                                    <div class="avatar" style="background: #2c6fff;"><strong>M</strong></div>
                                    <h6>Mark Anthony Dela Cruz</h6>
                                </div>
                            </td>
                            <td>Room 2C</td>
                            <td>TechCorp PH</td>
                            <td><strong>268</strong> / 486</td>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div class="progress-bar-bg">
                                        <div class="progress-bar-fill" style="width:55%"></div>
                                    </div>
                                    <span>55%</span>
                                </div>
                            </td>
                            <td>Satisfactory</td>
                        </tr> -->
                    </tbody>
                </table>
            </div>
        </div>

        <div id="remarks" class="section-panel">
            <h4><strong>Monitor OJT progress and HTE remarks</strong></h4><br>

            <div style="display:flex;gap:10px;margin-bottom:16px;align-items:center;">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchInput" placeholder="Search student" oninput="filterCards()">
                </div>
                <select id="roomFilter" onchange="filterCards()"
                    style="padding:7px 14px;border:1px solid #bbb;border-radius:24px;font-size:12px;">
                    <option value="">All Rooms</option>
                    <?php foreach ($rooms as $r): ?>
                        <option value="<?= htmlspecialchars($r['room_name']) ?>">
                            <?= htmlspecialchars($r['room_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="student-list">
                <?php foreach ($students as $student): ?>
                    <form method="POST" action="hte-db.php">

                        <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                        <input type="hidden" name="internship_id" value="<?= $student['internship_id'] ?>">

                        <div class="student-card">

                            <div class="log-row">
                                <div class="log-row-top">
                                    <div class="avatar" style="background:#ff2c8f;">
                                        <strong><?= strtoupper(substr($student['full_name'], 0, 1)) ?></strong>
                                    </div>
                                    <div class="student-info">
                                        <strong><?= $student['full_name'] ?></strong>
                                        <span><?= $student['company'] ?></span>
                                    </div>
                                </div>
                                <div class="log-row-controls">
                                    <select name="status" required>
                                        <option value="Present">Present</option>
                                        <option value="Absent">Absent</option>
                                        <option value="Late">Late</option>
                                    </select>
                                    <input type="number" name="hours" placeholder="hrs" min="0" max="24" required>
                                </div>
                            </div>

                            <div class="remarks-section">

                                <textarea name="remarks" placeholder="Enter remarks..." required></textarea>

                                <div class="remarks-footer">

                                    <select name="rating" required>
                                        <option>Outstanding</option>
                                        <option>Very Satisfactory</option>
                                        <option>Satisfactory</option>
                                        <option>Fairly Satisfactory</option>
                                        <option>Did Not Meet Expectations</option>
                                    </select>

                                    <label>
                                        <input type="checkbox" name="completed"> Mark complete
                                    </label>

                                    <!-- ONLY ONE BUTTON -->
                                    <button type="submit" name="save_all" class="btn-submit">
                                        Save
                                    </button>

                                </div>

                            </div>

                        </div>

                    </form>
                <?php endforeach; ?>
            </div>

            <div class="student-card" data-room="Room 2C" data-name="mark anthony dela cruz">
                <div class="log-row">
                    <div class="log-row-top">
                        <div class="avatar" style="background:#2c6fff;"><strong>M</strong></div>
                        <div class="student-info">
                            <strong>Mark Anthony Dela Cruz</strong>
                            <span>TechCorp PH</span>
                        </div>
                    </div>
                    <div class="log-row-controls">
                        <select>
                            <option>Present</option>
                            <option>Absent</option>
                            <option>Late</option>
                        </select>
                        <input type="number" placeholder="hrs" min="0" max="24">
                    </div>
                </div>
                <div class="remarks-section">
                    <textarea placeholder="Enter remarks..."></textarea>
                    <div class="remarks-footer">
                        <select>
                            <option>Outstanding</option>
                            <option>Very Satisfactory</option>
                            <option>Satisfactory</option>
                            <option>Fairly Satisfactory</option>
                            <option>Did Not Meet Expectations</option>
                        </select>
                        <label>
                            <input type="checkbox"> Mark complete
                        </label>
                        <button class="btn-submit">Save</button>
                    </div>
                </div>
            </div>
        </div>
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
</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function showSection(sectionId) {

        // hide all sections
        document.querySelectorAll('.section-panel').forEach(sec => {
            sec.classList.remove('active');
        });

        // show selected
        document.getElementById(sectionId).classList.add('active');

        // update sidebar active 
        document.querySelectorAll('.sidebar a').forEach(link => {
            link.classList.remove('active');
        });

        event.target.closest('a').classList.add('active');
    }
</script>

</html>