<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>CEE IT Connects | OJT ADVISER</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
    <link rel="stylesheet" href="../CSS/style.css"/>
</head>
<body data-page="rooms">

    <?php include 'navbar.php'; ?>

    <div class="page-body">

        <aside class="sidebar">
            <a href="rooms.php" class="active">
                <i class="bi bi-house-fill"></i>
                Rooms
            </a>
            <a href="students.php">
                <i class="bi bi-people-fill"></i>
                Students
            </a>
            <a href="requirements.php">
                <i class="bi bi-file-earmark-text-fill"></i>
                Requirements
            </a>

            <hr/>
            <div class="sidebar-label">My Rooms</div>

            <a class="room-pill" href="room-detail.php?id=1">
                <span class="room-dot purple"></span>
                <div>
                    <div class="room-pill-name">Room 4A</div>
                    <div class="room-pill-sub">14 students</div>
                </div>
            </a>
            <a class="room-pill" href="room-detail.php?id=2">
                <span class="room-dot teal"></span>
                <div>
                    <div class="room-pill-name">Room 2C</div>
                    <div class="room-pill-sub">10 students</div>
                </div>
            </a>
        </aside>

        <main class="main-content">

            <div class="rooms-header">
                <h1 class="rooms-title">Virtual Rooms</h1>
                <button class="btn-join">
                    <i class="bi bi-plus"></i> Join a Room
                </button>
            </div>

            <div class="rooms-grid">

                <div class="room-card">
                    <div class="room-card-banner purple-grad">
                        <div class="room-card-top">
                            <div>
                                <div class="room-card-name">Room 4A</div>
                                <div class="room-card-adviser">OJT Adviser Name</div>
                                <div class="room-card-adviser">HTE Adviser Name</div>
                            </div>
                            <button class="room-card-menu">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                        </div>
                    </div>
                    <div class="room-card-body">
                        <p class="room-card-activity">OJT Adviser Name published a new post</p>
                        <a href="room-detail.php?id=1" class="btn-enter">Enter Room</a>
                    </div>
                </div>

                <div class="room-card">
                    <div class="room-card-banner teal-grad">
                        <div class="room-card-top">
                            <div>
                                <div class="room-card-name">Room 2C</div>
                                <div class="room-card-adviser">OJT Adviser Name</div>
                                <div class="room-card-adviser">HTE Adviser Name</div>
                            </div>
                        </div>
                    </div>
                    <div class="room-card-body">
                        <p class="room-card-activity">OJT Adviser Name published a new post</p>
                        <a href="room-detail.php?id=2" class="btn-enter">Enter Room</a>
                    </div>
                </div>

            </div>
        </main>

    </div>

    <script src="../JS/script.js"></script>
    <script src="../JS/ojt.js"></script>
</body>
</html>