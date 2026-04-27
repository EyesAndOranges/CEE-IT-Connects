<?php
session_start();
require 'db.php';
require 'auth.php';
$current_room_id = $_GET['room_id'] ?? null;

$stmt = $pdo->prepare("
    SELECT r.*, a.full_name, a.title, a.role
    FROM rooms r
    LEFT JOIN advisers a ON r.adviser_id = a.id
    JOIN room_members rm ON r.id = rm.room_id
    WHERE rm.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);

$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            margin: 0;
            padding-top: 70px;
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

        /* SECTION PANELS */
        .section-panel {
            display: none;
        }

        .section-panel.active {
            display: block;
        }

        .panel-card {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            margin-bottom: 20px;
        }

        .panel-card h5 {
            font-weight: 700;
            margin-bottom: 16px;
            color: #333;
        }

        .badge-on-track {
            background: #d4edda;
            color: #155724;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
        }

        .badge-submitted {
            background: #cce5ff;
            color: #004085;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
        }

        .badge-missing {
            background: #f8d7da;
            color: #721c24;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
        }

        .req-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .req-item:last-child {
            border-bottom: none;
        }

        .req-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: #333;
        }

        .req-sub {
            font-size: 0.78rem;
            color: #888;
            margin-top: 2px;
        }

        /* FROM HTE-STATUS */
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
        <a href="#" onclick="showSection('home')" class="active" id="nav-home"><i class="fa-solid fa-house"></i>
            Home</a>
        <a href="#" onclick="showSection('status')" id="nav-status"><i class="fa-solid fa-calendar-check"></i>
            Status</a>
        <a href="#" onclick="showSection('requirements')" id="nav-requirements"><i class="fa-solid fa-list"></i>
            Requirements</a>
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
        <?php if ($current_room_id): ?>

            <?php include 'chat-room-content.php'; ?>

        <?php else: ?>

            <!-- HOME SECTION -->
            <div class="section-panel active" id="section-home">
                <div class="d-flex justify-content-between align-items-center">
                    <h3><strong>Virtual Rooms</strong></h3>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#joinRoomModal">
                        + Create a Room
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
            </div>

            <!-- STATUS SECTION (from the-status) -->
            <div class="section-panel" id="section-status">
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
                            <tr data-room="Room 4A">
                                <td>
                                    <div class="student-cell">
                                        <div class="avatar" style="background: #ff2c8f;"><strong>R</strong></div>
                                        <h6>Riva Mae Boongaling</h6>
                                    </div>
                                </td>
                                <td>Room 4A</td>
                                <td>TechCorp PH</td>
                                <td><strong>364</strong> / 486</td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <div class="progress-bar-bg">
                                            <div class="progress-bar-fill" style="width:75%"></div>
                                        </div>
                                        <span>75%</span>
                                    </div>
                                </td>
                                <td>Very Satisfactory</td>
                            </tr>
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
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- REQUIREMENTS SECTION -->
            <div class="section-panel" id="section-requirements">

            </div>

        <?php endif; ?>
    </div>

    <div class="modal fade" id="joinRoomModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content rounded-4">

                <!-- HEADER -->
                <div class="modal-header">
                    <h5 class="modal-title"><strong>Create a Room</strong></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <!-- BODY -->
                <div class="modal-body">

                    <form method="POST" action="ojt-rooms-db.php">

                        <!-- ROOM NAME -->
                        <div class="mb-3">
                            <label class="form-label">Room Name</label>
                            <input type="text" name="room_name" class="form-control" placeholder="Enter room name"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Section</label>
                            <input type="text" name="section" class="form-control" placeholder="e.g. 3-4"
                                pattern="^[0-9]+-[0-9]+$" title="Format must be like 3-4" required>
                        </div>
                        <!-- BUTTON -->
                        <div class="text-end">
                            <button type="submit" class="btn btn-warning px-4">
                                Create
                            </button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showSection(name) {
            document.querySelectorAll('.section-panel').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.sidebar a').forEach(a => a.classList.remove('active'));
            document.getElementById('section-' + name).classList.add('active');
            document.getElementById('nav-' + name).classList.add('active');
        }

        function filterTable() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const room = document.getElementById('roomFilter').value.toLowerCase();

            document.querySelectorAll('#all-students-tbody tr').forEach(row => {
                const name = row.querySelector('.student-cell h6')?.textContent.toLowerCase() ?? '';
                const rowRoom = (row.dataset.room ?? '').toLowerCase();

                const matchSearch = name.includes(search);
                const matchRoom = room === '' || rowRoom === room;

                row.style.display = (matchSearch && matchRoom) ? '' : 'none';
            });
        }
    </script>
</body>

</html>