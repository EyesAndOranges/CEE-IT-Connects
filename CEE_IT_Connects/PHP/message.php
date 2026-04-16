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

            /* prevent content shift when navbar appears */
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
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <a href="#" class="active"><i class="fa-solid fa-house"></i> Home</a>
        <a href="#"><i class="fa-solid fa-comments"></i> Chats</a>
        <a href="#"><i class="fa-solid fa-user-group"></i> Connect</a>

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

            <!-- DEFAULT DASHBOARD VIEW -->
            <div class="d-flex justify-content-between align-items-center">
                <h3><strong>Virtual Rooms</strong></h3>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#joinRoomModal">
                    + Join a Room
                </button>
            </div>

            <div class="row mt-4">
                <?php foreach ($rooms as $room): ?>
                    <div class="col-md-4">
                        <div class="card shadow-sm">

                            <div class="room-card" style="background: <?= $color ?>">
                                <h5 style="text-color <? $color ?>"><?= $room['room_name'] ?></h5>
                                <small><?= $room['full_name'] ?> (<?= $room['role'] ?>)</small>
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
</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</html>