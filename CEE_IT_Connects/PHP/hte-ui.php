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
$page = 'messages'
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
        body {
            background: #f5f6fa;

            /* prevent content shift when navbar appears */
            margin: 0;
            padding-top: 70px;
        }

        .section {
            display: none;
        }

        .section.active {
            display: block;
        }

        /* SIDEBAR */
        .sidebar {
            width: 240px;
            background: #fff;
            position: fixed;
            padding: 20px;
            border-right: 1px solid #ddd;
        }

        .sidebar a {
            display: block;
            padding: 10px;
            color: #333;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 5px;
        }

        .sidebar a.active {
            background: #ffe5d9;
            color: #ff6b2c;
        }

        .rooms-list {
            margin-top: 20px;
        }

        .room-item {
            padding: 8px;
            border-radius: 6px;
            background: #f1f1f1;
            margin-bottom: 5px;
        }

        .room-link {
            text-decoration: none;
        }

        .room-link .room-item:hover {
            background: #e0e0e0;
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
        }

        .room-card {
            border-radius: 12px;
            color: white;
            padding: 15px;
        }

        .room-footer {
            background: #eee;
            padding: 10px;
            border-radius: 0 0 12px 12px;
            text-align: center;
        }

        .enter-btn {
            background: #f4a62a;
            border: none;
            padding: 5px 15px;
            border-radius: 8px;
        }

        .search-box {
            display: flex;
            align-items: center;
            gap: 8px;
            background: white;
            border: 1px solid;
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
        }

        .student-cell {
            display: flex;
            align-items: center;
        }

        .student-card {
            background: white;
            border: 1px solid #00000060;
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
            border: 1px solid #ccc;
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
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 13px;
            resize: vertical;
            min-height: 80px;
            outline: none;
            background: #fafafa;
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
            border: 1px solid #ccc;
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
            border: 1px solid;
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
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <a href="#" onclick="showSection('rooms')" class="active"><i class="fa-solid fa-house"></i> Home</a>
        <a href="#" onclick="showSection('status')"><i class="fa-solid fa-calendar-check"></i> Status</a>
        <a href="#" onclick="showSection('remarks')"><i class="fa-solid fa-star"></i> Remarks</a>

        <div class="rooms-list">
            <h6>ROOMS</h6>

            <?php foreach ($rooms as $room): ?>

                <?php if ($current_room_id == $room['id']): ?>

                    <!-- CURRENT ROOM (NOT CLICKABLE) -->
                    <div class="room-item active-room">
                        <?= $room['room_name'] ?>
                    </div>

                <?php else: ?>

                    <!-- CLICKABLE ROOM -->
                    <a href="?room_id=<?= $room['id'] ?>" class="room-link">
                        <div class="room-item">
                            <?= $room['room_name'] ?>
                        </div>
                    </a>

                <?php endif; ?>

            <?php endforeach; ?>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        <!-- Room Section -->
        <div id="rooms" class="section active">
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

                <div class="row mt-4">
                    <?php foreach ($rooms as $room): ?>
                        <div class="col-md-4">
                            <div class="card shadow-sm">

                                <div class="room-card" style="background: <?= $color ?>">
                                    <h5 style="text-color <? $color ?>">
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


        <div id="status" class="section">
            <h4><strong>Monitor OJT progress and HTE remarks</strong></h4><br>

            <div style="display:flex;gap:10px;margin-bottom:16px;align-items:center;">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchInput" placeholder="Search student" oninput="filterTable()">
                </div>
                <select id="roomFilter" onchange="filterTable()"
                    style="padding:7px 14px;border:1px solid;border-radius:24px;font-size:12px;">
                    <option value="">All Rooms</option>
                    <?php foreach ($rooms as $r): ?>
                        <option value="<?= htmlspecialchars($r['room_name']) ?>">
                            <?= htmlspecialchars($r['room_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="background:white;border:1px solid #00000060;border-radius:8px;overflow:hidden;">
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
                            <tr data-room="<?php htmlspecialchars($status['room_name']) ?>">
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

        <div id="remarks" class="section">
            <h4><strong>Monitor OJT progress and HTE remarks</strong></h4><br>

            <div style="display:flex;gap:10px;margin-bottom:16px;align-items:center;">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchInput" placeholder="Search student" oninput="filterCards()">
                </div>
                <select id="roomFilter" onchange="filterCards()"
                    style="padding:7px 14px;border:1px solid;border-radius:24px;font-size:12px;">
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

                                <div class="avatar" style="background:#ff2c8f;">
                                    <strong><?= strtoupper(substr($student['full_name'], 0, 1)) ?></strong>
                                </div>

                                <div class="student-info">
                                    <strong><?= $student['full_name'] ?></strong>
                                    <span><?= $student['company'] ?></span>
                                </div>

                                <select name="status" required>
                                    <option value="Present">Present</option>
                                    <option value="Absent">Absent</option>
                                    <option value="Late">Late</option>
                                </select>

                                <input type="number" name="hours" placeholder="hrs" min="0" max="24" required>

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
                    <div class="avatar" style="background:#2c6fff;"><strong>M</strong></div>
                    <div class="student-info">
                        <strong>Mark Anthony Dela Cruz</strong>
                        <span>TechCorp PH</span>
                    </div>
                    <select>
                        <option>Present</option>
                        <option>Absent</option>
                        <option>Late</option>
                    </select>
                    <input type="number" placeholder="hrs" min="0" max="24">
                    <span class="total-hrs">268 hrs</span>
                    <button class="btn-log">Log</button>
                </div>
                <div class="remarks-section">
                    <div class="remarks-label">Remarks</div>
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
                        <button class="btn-submit">Submit</button>
                    </div>
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
        document.querySelectorAll('.section').forEach(sec => {
            sec.classList.remove('active');
        });

        // show selected
        document.getElementById(sectionId).classList.add('active');

        // update sidebar active 
        document.querySelectorAll('.sidebar a').forEach(link => {
            link.classList.remove('active');
        });

        event.target.classList.add('active');
    }
</script>

</html>