<?php
require 'db.php';
require_once 'auth.php';
$page = $page ?? "";
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
    'superadmin' => 'admin',
    'admin' => 'admin'
];

$userType = $roleMap[$role] ?? 'student';

// Fetch notifications
$stmt = $pdo->prepare("
    SELECT * FROM notifications
    WHERE user_id = ? AND user_type = ?
    ORDER BY created_at DESC
");
$stmt->execute([$user_id, $userType]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count unread
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM notifications
    WHERE user_id = ? AND user_type = ? AND is_read = FALSE
");
$stmt->execute([$user_id, $userType]);
$unread_count = $stmt->fetchColumn();

// Fetch profile info
if ($userType === 'student') {
    $profileStmt = $pdo->prepare("SELECT full_name, email FROM students WHERE id = ?");
} elseif ($userType === 'admin') {
    $profileStmt = $pdo->prepare("SELECT name AS full_name, email FROM admins WHERE id = ?");
} else {
    $profileStmt = $pdo->prepare("SELECT full_name, email FROM advisers WHERE id = ?");
}
$profileStmt->execute([$user_id]);
$profileUser = $profileStmt->fetch(PDO::FETCH_ASSOC);
$displayName = $profileUser['full_name'] ?? 'User';
$displayEmail = $profileUser['email'] ?? '';
$parts = explode(' ', $displayName);
$initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));

// Time helper
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/CSS/index-style.css">
    <style>
        /* ── NAVBAR ── */
        .navbar-custom {
            background: #2c3e67;
            padding: 12px 0;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 70px;
            z-index: 1000;
        }

        .nav-logo {
            height: 60px;
            width: auto;
        }

        .brand-text {
            color: #ff6b2c;
            font-weight: 700;
            letter-spacing: 1px;
            font-size: 22px;
            font-family: 'GeogrotSharp TRIAL', sans-serif;
        }

        .navbar-nav .nav-link {
            color: white;
            font-weight: 600;
            font-size: 16px;
            transition: 0.2s;
            font-family: 'Poppins', sans-serif;
            letter-spacing: .5px;
        }

        .navbar-nav .nav-link:hover {
            color: #00cfff;
        }

        .navbar-nav .nav-link.active {
            background: #ff6b2c;
            color: white;
            border-radius: 10px;
            padding: 10px 18px;
        }

        /* ── THREE-COLUMN LAYOUT ── */
        .navbar-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            padding-left: 18px;
            padding-right: 18px;
        }

        .navbar-brand {
            flex: 1;
        }

        .navbar-center {
            flex: 1;
            display: flex;
            justify-content: center;
        }

        /* ── RIGHT ICONS ── */
        .navbar-icons {
            flex: 1;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 18px;
            padding-right: 10px;
        }

        .navbar-icons i {
            color: white;
            font-size: 20px;
        }

        .navbar-icons i:hover {
            color: #00cfff;
        }

        .icon-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            color: white;
            font-size: 20px;
            display: flex;
            align-items: center;
        }

        .icon-btn:hover {
            color: #00cfff;
        }

        /* ── NOTIF BADGE ── */
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

        /* ── NOTIF POPUP ── */
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
            max-height: 400px;
            overflow-y: auto;
        }

        .notif-subtitle {
            color: #8a92a6;
            margin-bottom: 10px;
        }

        .notif-item {
            display: flex;
            gap: 10px;
            padding: 10px 8px;
        }

        .notif-item:hover {
            background: #ffd280;
            cursor: pointer;
            border-radius: 8px;
            margin-left: -15px;
            margin-right: -15px;
            padding-left: 15px;
            padding-right: 15px;
        }

        .dot {
            width: 8px;
            height: 8px;
            background: #ff3c00;
            border-radius: 50%;
            margin-top: 6px;
            flex-shrink: 0;
        }

        /* ── PROFILE DROPDOWN ── */
        .profile-wrapper {
            position: relative;
        }

        .profile-drop {
            display: none;
            position: absolute;
            right: 0;
            top: 40px;
            width: 220px;
            background: white;
            border-radius: 14px;
            padding: 20px 16px 14px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
            z-index: 999;
            text-align: center;
            flex-direction: column;
            align-items: center;
        }

        .profile-drop.open {
            display: flex;
        }

        .p-name {
            font-size: 14px;
            font-weight: 700;
            color: #272f54;
            margin-bottom: 2px;
        }

        .p-email {
            font-size: 12px;
            color: #888;
            margin-bottom: 14px;
            word-break: break-all;
        }

        .btn-edit {
            width: 100%;
            background: linear-gradient(135deg, #FFB62F, #E4572E);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-bottom: 10px;
        }

        .btn-edit:hover {
            opacity: 0.9;
        }

        .logout {
            font-size: 13px;
            color: #e74c3c;
            text-decoration: none;
            font-weight: 500;
        }

        .logout:hover {
            text-decoration: underline;
        }

        /* ── MOBILE ── */
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

        <!-- Center Menu (students only) -->
        <?php if (!$hideStudentNav): ?>
            <div class="navbar-center d-none d-lg-flex">
                <ul class="navbar-nav gap-5">
                    <li class="nav-item">
                        <a class="nav-link <?= ($page == 'home') ? 'active' : '' ?>" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($page == 'opportunity') ? 'active' : '' ?>"
                            href="applied-internship-programs.php">Internships</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($page == 'announcements') ? 'active' : '' ?>"
                            href="announcement.php">Announcements</a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Right Icons -->
        <div class="navbar-icons d-flex align-items-center position-relative">

            <!-- Messages (students only) -->
            <?php if (!$hideStudentNav): ?>
                <div>
                    <a href="Message.php">
                        <i class="fa-solid fa-desktop"></i>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Bell + Notification popup -->
            <div class="position-relative">
                <i class="fa-regular fa-bell" id="notifBell" style="cursor:pointer;"></i>

                <?php if ($unread_count > 0): ?>
                    <span class="notif-badge" id="notifBadge"><?= $unread_count ?></span>
                <?php else: ?>
                    <span class="notif-badge" id="notifBadge" style="display:none;">0</span>
                <?php endif; ?>

                <div id="notifPopup" class="notif-popup">
                    <h5><strong>Notifications</strong></h5>
                    <p class="notif-subtitle">You have <?= $unread_count ?> new notifications</p>
                    <hr>

                    <!-- TODAY -->
                    <h6><strong>Today</strong></h6>
                    <?php
                    $today = date('Y-m-d');
                    $hasToday = false;
                    foreach ($notifications as $notif):
                        if (date('Y-m-d', strtotime($notif['created_at'])) == $today):
                            $hasToday = true; ?>
                            <div class="notif-item"
                                onclick="window.location.href='<?= htmlspecialchars($notif['link'] ?? 'index.php') ?>'">
                                <?php if (!$notif['is_read']): ?>
                                    <div class="dot"></div><?php endif; ?>
                                <div>
                                    <strong><?= htmlspecialchars($notif['title']) ?></strong>
                                    <p class="mb-0 small text-muted"><?= htmlspecialchars($notif['message']) ?></p>
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
                            $hasWeek = true; ?>
                            <div class="notif-item"
                                onclick="window.location.href='<?= htmlspecialchars($notif['link'] ?? 'index.php') ?>'">
                                <?php if (!$notif['is_read']): ?>
                                    <div class="dot"></div><?php endif; ?>
                                <div>
                                    <strong><?= htmlspecialchars($notif['title']) ?></strong>
                                    <p class="mb-0 small text-muted"><?= htmlspecialchars($notif['message']) ?></p>
                                    <small><?= timeAgo($notif['created_at']) ?></small>
                                </div>
                            </div>
                        <?php endif; endforeach; ?>
                    <?php if (!$hasWeek): ?>
                        <p class="text-muted small">No notifications this week</p>
                    <?php endif; ?>
                    <hr>

                    <!-- OLDER -->
                    <h6><strong>Older</strong></h6>
                    <?php
                    $hasOlder = false;
                    foreach ($notifications as $notif):
                        $date = date('Y-m-d', strtotime($notif['created_at']));
                        if ($date < $weekAgo):
                            $hasOlder = true; ?>
                            <div class="notif-item"
                                onclick="window.location.href='<?= htmlspecialchars($notif['link'] ?? 'index.php') ?>'">
                                <?php if (!$notif['is_read']): ?>
                                    <div class="dot"></div><?php endif; ?>
                                <div>
                                    <strong><?= htmlspecialchars($notif['title']) ?></strong>
                                    <p class="mb-0 small text-muted"><?= htmlspecialchars($notif['message']) ?></p>
                                    <small><?= timeAgo($notif['created_at']) ?></small>
                                </div>
                            </div>
                        <?php endif; endforeach; ?>
                    <?php if (!$hasOlder): ?>
                        <p class="text-muted small">No older notifications</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Profile dropdown -->
            <div class="profile-wrapper">
                <button class="icon-btn" id="profileBtn" title="Profile">
                    <i class="fa-<?= ($page == 'profile') ? 'solid' : 'regular' ?> fa-user"></i>
                </button>

                <div class="profile-drop" id="profileDrop">
                    <div style="width:52px;height:52px;border-radius:50%;background:#eef1ff;color:#272f54;
                                display:flex;align-items:center;justify-content:center;
                                font-size:18px;font-weight:700;margin-bottom:8px;">
                        <?= htmlspecialchars($initials) ?>
                    </div>
                    <div class="p-name"><?= htmlspecialchars($displayName) ?></div>
                    <div class="p-email"><?= htmlspecialchars($displayEmail) ?></div>
                    <button class="btn-edit" onclick="window.location.href='personal-information.php'">
                        <i class="bi bi-pencil-square"></i> Edit Profile
                    </button>
                    <a href="logout.php" class="logout">Log Out?</a>
                </div>
            </div>

            <!-- Mobile menu toggle (students only) -->
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
            top: 70px; left: 0;
            width: 100%;
            background: #2c3e67;
            z-index: 999;
            padding: 10px 0;
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);">
            <a href="index.php" style="display:block;padding:14px 24px;color:white;text-decoration:none;font-weight:600;
                <?= ($page == 'home') ? 'background:#ff6b2c;border-radius:8px;margin:4px 12px;' : '' ?>">
                <i class="fa-solid fa-house me-2"></i> Home
            </a>
            <a href="applied-internship-programs.php" style="display:block;padding:14px 24px;color:white;text-decoration:none;font-weight:600;
                <?= ($page == 'opportunity') ? 'background:#ff6b2c;border-radius:8px;margin:4px 12px;' : '' ?>">
                <i class="fa-solid fa-briefcase me-2"></i> Internships
            </a>
            <a href="announcement.php" style="display:block;padding:14px 24px;color:white;text-decoration:none;font-weight:600;
                <?= ($page == 'announcements') ? 'background:#ff6b2c;border-radius:8px;margin:4px 12px;' : '' ?>">
                <i class="fa-solid fa-bullhorn me-2"></i> Announcements
            </a>
        </div>
    <?php endif; ?>
</nav>

<script>
    // ── Notification bell ──
    const bell = document.getElementById("notifBell");
    const popup = document.getElementById("notifPopup");

    bell.addEventListener("click", function (e) {
        e.stopPropagation();
        const isOpen = popup.style.display === "block";
        popup.style.display = isOpen ? "none" : "block";

        if (!isOpen) {
            fetch("mark-as-read.php")
                .then(res => res.text())
                .then(() => {
                    const badge = document.querySelector(".notif-badge");
                    if (badge) badge.remove();
                    document.querySelectorAll(".dot").forEach(d => d.remove());
                });
        }
    });

    document.addEventListener("click", function (e) {
        if (!popup.contains(e.target) && !bell.contains(e.target)) {
            popup.style.display = "none";
        }
    });

    // ── Profile dropdown ──
    const profileBtn = document.getElementById('profileBtn');
    const profileDrop = document.getElementById('profileDrop');

    profileBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        profileDrop.classList.toggle('open');
    });

    document.addEventListener('click', function (e) {
        if (!profileDrop.contains(e.target) && !profileBtn.contains(e.target)) {
            profileDrop.classList.remove('open');
        }
    });

    // ── Mobile menu toggle ──
    const mobileToggle = document.getElementById('mobileMenuToggle');
    const mobileMenu = document.getElementById('mobileMenu');

    if (mobileToggle && mobileMenu) {
        mobileToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            mobileMenu.style.display = mobileMenu.style.display === 'block' ? 'none' : 'block';
        });

        document.addEventListener('click', function (e) {
            if (!mobileMenu.contains(e.target) && !mobileToggle.contains(e.target)) {
                mobileMenu.style.display = 'none';
            }
        });
    }

    // ── Poll for new notifications every 60s ──
    (function pollNotifications() {
        fetch('mark-as-read.php?count_only=1')
            .then(r => r.json())
            .then(data => {
                const badge = document.getElementById('notifBadge');
                if (!badge) return;
                if (data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            })
            .catch(() => {});
        setTimeout(pollNotifications, 60000);
    })();
</script>