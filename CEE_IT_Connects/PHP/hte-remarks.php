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

        .active-room {
            background: #ffe5d9;
            color: #ff6b2c;
            font-weight: bold;
            cursor: default;
        }

        .main {
            margin-left: 260px;
            padding: 20px;
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
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>
    <div class="sidebar">
        <a href="hte-rooms.php"><i class="fa-solid fa-house"></i> Home</a>
        <a href="hte-status.php"><i class="fa-solid fa-calendar-check"></i> Status</a>
        <a href="hte-remarks.php" class="active"><i class="fa-solid fa-star"></i> Remarks</a>

        <div class="rooms-list">
            <h6>ROOMS</h6>

            <?php foreach ($rooms as $room): ?>

                <?php if ($current_room_id == $room['id']): ?>

                    <div class="room-item active-room">
                        <?= $room['room_name'] ?>
                    </div>

                <?php else: ?>

                    <a href="?room_id=<?= $room['id'] ?>" class="room-link">
                        <div class="room-item">
                            <?= $room['room_name'] ?>
                        </div>
                    </a>

                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <main class="main">
        <div>
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
                        <option value="<?= htmlspecialchars($r['room_name']) ?>"><?= htmlspecialchars($r['room_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="student-list">

                <div class="student-card" data-room="Room 4A" data-name="riva mae boongaling">
                    <div class="log-row">
                        <div class="avatar" style="background:#ff2c8f;"><strong>R</strong></div>
                        <div class="student-info">
                            <strong>Riva Mae Boongaling</strong>
                            <span>TechCorp PH</span>
                        </div>
                        <select>
                            <option>Present</option>
                            <option>Absent</option>
                            <option>Late</option>
                        </select>
                        <input type="number" placeholder="hrs" min="0" max="24">
                        <span class="total-hrs">364 hrs</span>
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
    </main>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterCards() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const room = document.getElementById('roomFilter').value.toLowerCase();

            document.querySelectorAll('.student-card').forEach(card => {
                const name = card.dataset.name ?? '';
                const cardRoom = (card.dataset.room ?? '').toLowerCase();

                const matchSearch = name.includes(search);
                const matchRoom = room === '' || cardRoom === room;

                card.style.display = (matchSearch && matchRoom) ? 'block' : 'none';
            });
        }

        document.querySelectorAll('.btn-log').forEach(btn => {
            btn.addEventListener('click', function () {
                const row = this.closest('.log-row');
                const hrs = row.querySelector('input[type="number"]').value;

                if (!hrs) {
                    alert('Please enter hours before logging.');
                    return;
                }

                alert('Hours logged successfully!');
            });
        });

        document.querySelectorAll('.btn-submit').forEach(btn => {
            btn.addEventListener('click', function () {
                const section = this.closest('.remarks-section');
                const remarks = section.querySelector('textarea').value;

                if (!remarks) {
                    alert('Please enter remarks before submitting.');
                    return;
                }

                alert('Remarks submitted successfully!');
            });
        });
    </script>

</body>

</html>