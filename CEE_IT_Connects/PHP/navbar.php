<?php
require 'db.php';
require_once 'auth.php';
$page = "";
$user_id = $_SESSION['user_id'];
$role = strtolower(trim($_SESSION['role']));
$hideStudentNav = in_array($role, [
    'admin',
    'superadmin',
    'internship_admin',
    'adviser',
    'hte adviser',
    'internship_adviser'
]);
$roleMap = [
    'student' => 'student',
    'internship_adviser' => 'adviser',
    'HTE_adviser' => 'adviser',
    'adviser' => 'adviser',
    'internship_admin' => 'admin',
    'superadmin' => 'admin'
];

$userType = $roleMap[$role] ?? 'student';

// Fetch
$stmt = $pdo->prepare("
    SELECT * FROM notifications
    WHERE user_id = ? and user_type = ?
    ORDER BY created_at DESC
");

$stmt->execute([$user_id, $userType]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

//Insert Notif
/* $stmt = $pdo->prepare("
    INSERT INTO notifications (user_id, title, message, is_read, created_at)
    VALUES (?, ?, ?, FALSE, NOW())
");
$stmt->execute([$user_id, "Welcome back to CEE IT Connects!", "Check out the latest opportunities and updates."]);
*/
// check unread Notifs
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM notifications
    WHERE user_id = ? AND user_type = ?
    AND is_read = FALSE
");
$stmt->execute([$user_id, $userType]);
$unread_count = $stmt->fetchColumn();

// Time
function timeAgo($datetime)
{
    $time = time() - strtotime($datetime);

    if ($time < 60)
        return "Just now";
    if ($time < 3600)
        return floor($time / 60) . " min ago";
    if ($time < 86400)
        return floor($time / 3600) . " hr ago";
    return floor($time / 86400) . " day ago";
}
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CEE IT CONNECTS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/CSS/index-style.css">
    <style>
        /* NAVBAR BACKGROUND */

        .navbar-custom {
            background: #2c3e67;
            padding: 12px 0;

            position: fixed;
            top: 0;
            left: 0;
            width: 100%;

            height: 70px;
            /* IMPORTANT */
            z-index: 1000;
            /* IMPORTANT */
        }

        /* LOGO */
        .nav-logo {
            width: 38px;
        }

        /* BRAND TEXT */
        .brand-text {
            color: #ff6b2c;
            font-weight: 700;
            letter-spacing: 1px;
            font-size: 18px;
        }

        /* MENU LINKS */
        .navbar-nav .nav-link {
            color: white;
            font-weight: 500;
            font-size: 15px;
            transition: 0.2s;
        }

        /* HOVER */
        .navbar-nav .nav-link:hover {
            color: #00cfff;
        }

        /* ACTIVE LINK */
        .navbar-nav .active {
            color: white;
            font-weight: 600;
        }

        /* RIGHT ICONS */
        .navbar-icons i {
            color: white;
            font-size: 20px;
        }

        .navbar-icons i:hover {
            color: #00cfff;
        }

        /* BADGE */
        .notif-badge {
            position: absolute;
            top: -5px;
            right: -8px;
            background: red;
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 50%;
        }

        /* POPUP */
        .notif-popup {
            display: none;
            position: absolute;
            right: 0;
            top: 35px;
            width: 320px;
            background: #faf7f7;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            z-index: 999;
        }

        .notif-title {
            font-size: 1.1rem;
            font-weight: bold;
            color: #FF673A;
            margin-bottom: 2px;
        }

        /* SUBTITLE */
        .notif-subtitle {
            color: #8a92a6;
            margin-bottom: 10px;
        }

        /* ITEM */
        .notif-item {
            display: flex;
            gap: 10px;
            padding: 10px 8px;
            /* border-top: 1px solid #bbb; */
        }

        .notif-item:hover {
            background: #ffd280;
            cursor: pointer;
            border-radius: 8px;
            gap: 10px;
            margin-left: -15px;
            margin-right: -15px;
            padding-left: 15px;
            padding-right: 15px;
            border-radius: 8px;
        }

        /* DOT */
        .dot {
            width: 8px;
            height: 8px;
            background: #ff3c00;
            border-radius: 50%;
            margin-top: 6px;
        }

        /* SCROLL */
        .notif-popup {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>

<nav class="navbar navbar-expand-lg navbar-custom fixed-top">
    <div class="container-fluid px-5">

        <!-- Logo + Brand -->
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="../Sources/CEE IT Connects Logo.png" class="nav-logo">
            <span class="brand-text ms-2">CEE IT CONNECTS</span>
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Center Menu -->
        <?php if (!$hideStudentNav): ?>
            <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
                <ul class="navbar-nav gap-4">

                    <li class="nav-item">
                        <a class="nav-link <?= ($page == 'home') ? 'active' : '' ?>" href="index.php">
                            Home
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?= ($page == 'opportunity') ? 'active' : '' ?>"
                            href="applied-internship-programs.php">
                            Internships
                        </a>
                    </li>

                    <li class=" nav-item">
                        <a class="nav-link <?= ($page == 'announcements') ? 'active' : '' ?>" href="announcement.php">
                            Announcements
                        </a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Right Icons -->
        <div class="navbar-icons d-flex align-items-center gap-3 position-relative">
            <div>
                <a href="Message.php">
                    <i class="fa-<?= ($page == 'messages') ? 'solid' : 'regular' ?> fa-comment"></i>
                </a>
            </div>
            <!-- BELL -->
            <div class="position-relative">
                <i class="fa-regular fa-bell" id="notifBell" style="cursor:pointer; "></i>

                <?php if ($unread_count > 0): ?>
                    <span class="notif-badge"><?= $unread_count ?></span>
                <?php endif; ?>

                <!-- POPUP -->
                <div id="notifPopup" class="notif-popup">
                    <h5><strong>Notifications</strong></h5>
                    <p class="notif-subtitle">
                        You have <?= $unread_count ?> new notifications
                    </p>

                    <hr>

                    <!-- TODAY -->
                    <h6><strong>Today</strong></h6>

                    <?php
                    $today = date('Y-m-d');
                    $hasToday = false;

                    foreach ($notifications as $notif):
                        if (date('Y-m-d', strtotime($notif['created_at'])) == $today):
                            $hasToday = true;
                            ?>
                            <div class="notif-item"
                                onclick="window.location.href='notification-detail.php?id=<?= $notif['id'] ?>'">
                                <?php if (!$notif['is_read']): ?>
                                    <div class="dot"></div>
                                <?php endif; ?>
                                <div>
                                    <strong><?= htmlspecialchars($notif['title']) ?></strong>
                                    <p class="mb-0 small text-muted">
                                        <?= htmlspecialchars($notif['message']) ?>
                                    </p>
                                    <small><?= timeAgo($notif['created_at']) ?></small>
                                </div>
                            </div>
                        <?php endif; endforeach; ?>

                    <?php if (!$hasToday): ?>
                        <p class="text-muted small">No notifications today</p>
                    <?php endif; ?>

                    <hr>

                    <!-- THIS WEEK -->
                    <h6><strong>This week</strong></h6>

                    <?php
                    $weekAgo = date('Y-m-d', strtotime('-7 days'));
                    $hasWeek = false;

                    foreach ($notifications as $notif):
                        $date = date('Y-m-d', strtotime($notif['created_at']));
                        if ($date < $today && $date >= $weekAgo):
                            $hasWeek = true;
                            ?>
                            <div class="notif-item"
                                onclick="window.location.href='applied-internship-programs.php?id=<?= $notif['id'] ?>'">
                                <div class="dot"></div>
                                <div>
                                    <strong><?= htmlspecialchars($notif['title']) ?></strong>
                                    <p class="mb-0 small text-muted">
                                        <?= htmlspecialchars($notif['message']) ?>
                                    </p>
                                    <small><?= timeAgo($notif['created_at']) ?></small>
                                </div>
                            </div>
                        <?php endif; endforeach; ?>

                    <?php if (!$hasWeek): ?>
                        <p class="text-muted small">No notifications this week</p>
                    <?php endif; ?>

                    <hr>
                    <!-- Anything Older-->
                    <h6><strong>Older</strong></h6>

                    <?php
                    $hasOlder = false;

                    foreach ($notifications as $notif):
                        $date = date('Y-m-d', strtotime($notif['created_at']));

                        if ($date < $weekAgo):
                            $hasOlder = true;
                            ?>
                            <div class="notif-item"
                                onclick="window.location.href='applied-internship-programs.php?id=<?= $notif['id'] ?>'">

                                <?php if (!$notif['is_read']): ?>
                                    <div class="dot"></div>
                                <?php endif; ?>

                                <div>
                                    <strong>
                                        <?= htmlspecialchars($notif['title']) ?>
                                    </strong>
                                    <p class="mb-0 small text-muted">
                                        <?= htmlspecialchars($notif['message']) ?>
                                    </p>
                                    <small>
                                        <?= timeAgo($notif['created_at']) ?>
                                    </small>
                                </div>
                            </div>
                        <?php endif; endforeach; ?>

                    <?php if (!$hasOlder): ?>
                        <p class="text-muted small">No older notifications</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- User Icon -->
            <a href="personal-information.php">
                <i class="fa-<?= ($page == 'profile') ? 'solid' : 'regular' ?> fa-user"></i>
            </a>
        </div>

    </div>
</nav>
<script>
    const bell = document.getElementById("notifBell");
    const popup = document.getElementById("notifPopup");

    bell.addEventListener("click", function (e) {
        e.stopPropagation();

        let isOpen = popup.style.display === "block";
        popup.style.display = isOpen ? "none" : "block";

        // Mark as read
        if (!isOpen) {
            fetch("mark-as-read.php")
                .then(res => res.text())
                .then(data => {
                    // remove badge
                    let badge = document.querySelector(".notif-badge");
                    if (badge) badge.remove();

                    // remove all dots
                    document.querySelectorAll(".dot").forEach(dot => dot.remove());
                });
        }
    });

    // close popup
    document.addEventListener("click", function (e) {
        if (!popup.contains(e.target) && !bell.contains(e.target)) {
            popup.style.display = "none";
        }
    });
</script>