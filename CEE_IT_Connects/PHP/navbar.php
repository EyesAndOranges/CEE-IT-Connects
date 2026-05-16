<?php
require 'db.php';
require_once 'auth.php';
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
    <!--<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
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
            height: 60px;
            width: auto;
        }

        /* BRAND TEXT */
        .brand-text {
            color: #ff6b2c;
            font-weight: 700;
            letter-spacing: 1px;
            font-size: 22px;
            font-family: 'GeogrotSharp TRIAL', sans-serif;
        }

        /* MENU LINKS */
        .navbar-nav .nav-link {
            color: white;
            font-weight: 600;
            font-size: 16px;
            transition: 0.2s;
            font-family: 'Poppins', sans-serif;
            letter-spacing: .5px;
        }

        /* HOVER */
        .navbar-nav .nav-link:hover {
            color: #00cfff;
        }

        /* ACTIVE LINK */
        .navbar-nav .nav-link.active {
            background: #ff6b2c;
            color: white;
            border-radius: 10px;
            padding: 10px 18px;
        }

        /* RIGHT ICONS */
        .navbar-icons i {
            color: white;
            font-size: 20px;
        }

        .navbar-icons i:hover {
            color: #00cfff;
        }

        .navbar-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;

            width: 100%;

            padding-left: 18px;
            padding-right: 18px;
        }

        /* LEFT SIDE */
        .navbar-brand {
            flex: 1;
        }

        /* CENTER MENU */
        .navbar-center {
            flex: 1;
            display: flex;
            justify-content: center;
        }

        /* RIGHT ICONS */
        .navbar-icons {
            flex: 1;
            display: flex;
            justify-content: flex-end;
            align-items: center;

            gap: 18px;

            padding-right: 10px;
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

        /*susu*/
        @media (max-width: 768px) {
        .brand-text {
            font-size: 13px;
        }

        .nav-logo {
            height: 30px;
        }

        .navbar-icons {
            gap: 15px;
        }

        .navbar-icons i {
            font-size: 15px;
        }

        .notif-popup {
            width: 260px;
            right: -60px;
        }
    }
    </style>
</head>

<nav class="navbar navbar-expand-lg navbar-custom fixed-top">
    <div class="container-fluid px-3">

        <!-- Logo + Brand -->
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="../Sources/CEE IT Connects Logo.png" class="nav-logo">
            <span class="brand-text ms-1">CEE IT CONNECTS</span>
        </a>

        <!-- Center Menu -->
        <?php if (!$hideStudentNav): ?>
            <div class="navbar-center d-none d-lg-flex">
                <ul class="navbar-nav gap-5">
                    <li class="nav-item">
                        <a class="nav-link <?= ($page == 'home') ? 'active' : '' ?>" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($page == 'opportunity') ? 'active' : '' ?>" href="applied-internship-programs.php">Internships</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($page == 'announcements') ? 'active' : '' ?>" href="announcement.php">Announcements</a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Right Icons -->

        <div class="navbar-icons d-flex align-items-center position-relative">
            <?php if (!$hideStudentNav): ?>
                <div>
                    <a href="Message.php">
                        <i class="fa-solid fa-desktop"></i>
                    </a>
                </div>
            <?php endif; ?>
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
                                onclick="window.location.href='applied-internship-programs.php?id=<?= $notif['id'] ?>'">
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
            <?php if (!$hideStudentNav): ?>
                <button id="mobileMenuToggle" class="d-lg-none"
                    style="border:none; background:transparent; cursor:pointer;">
                    <i class="bi bi-list" style="color:white; font-size:24px;"></i>
                </button>
            <?php endif; ?>
        </div>

    </div>
    <!-- MOBILE DROPDOWN MENU -->
    <?php if (!$hideStudentNav): ?>
        <div id="mobileMenu" style="
            display: none;
            position: absolute;
            top: 70px;
            left: 0;
            width: 100%;
            background: #2c3e67;
            z-index: 999;
            padding: 10px 0;
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        ">
            <a href="index.php" style="display:block; padding:14px 24px; color:white; text-decoration:none; font-weight:600;
                <?= ($page == 'home') ? 'background:#ff6b2c; border-radius:8px; margin:4px 12px;' : '' ?>">
                <i class="fa-solid fa-house me-2"></i> Home
            </a>
            <a href="applied-internship-programs.php" style="display:block; padding:14px 24px; color:white; text-decoration:none; font-weight:600;
                <?= ($page == 'opportunity') ? 'background:#ff6b2c; border-radius:8px; margin:4px 12px;' : '' ?>">
                <i class="fa-solid fa-briefcase me-2"></i> Internships
            </a>
            <a href="announcement.php" style="display:block; padding:14px 24px; color:white; text-decoration:none; font-weight:600;
                <?= ($page == 'announcements') ? 'background:#ff6b2c; border-radius:8px; margin:4px 12px;' : '' ?>">
                <i class="fa-solid fa-bullhorn me-2"></i> Announcements
            </a>
        </div>
    <?php endif; ?>
</nav>
<script>
    // Mobile menu toggle
    const mobileToggle = document.getElementById('mobileMenuToggle');
    const mobileMenu = document.getElementById('mobileMenu');

    if (mobileToggle && mobileMenu) {
        mobileToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            mobileMenu.style.display = mobileMenu.style.display === 'block' ? 'none' : 'block';
        });

        document.addEventListener('click', function(e) {
            if (!mobileMenu.contains(e.target) && !mobileToggle.contains(e.target)) {
                mobileMenu.style.display = 'none';
            }
        });
    }
</script>