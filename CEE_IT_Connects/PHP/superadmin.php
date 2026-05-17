<?php
require 'db.php';
require 'auth.php';

$statePath = __DIR__ . '/register_toggle.txt';
$registerVisible = file_exists($statePath) ? trim(file_get_contents($statePath)) : 'show';

if (isset($_POST['toggle_register'])) {
    $newState = $registerVisible === 'show' ? 'hide' : 'show';
    file_put_contents($statePath, $newState);
    $registerVisible = $newState;
}

$internshipStmt = $pdo->query("
    SELECT id, company, title
    FROM internships
    ORDER BY company ASC, title ASC
");
$internships = $internshipStmt->fetchAll(PDO::FETCH_ASSOC);

// Stats for dashboard
$statsStmt = $pdo->query("SELECT COUNT(*) AS total FROM internships");
$totalInternships = $statsStmt->fetchColumn();

$interestedStmt = $pdo->query("SELECT COUNT(*) AS total FROM internship_bookmarks");
$totalInterested = $interestedStmt->fetchColumn();

$announcementsStmt = $pdo->query("SELECT COUNT(*) AS total FROM announcements");
$totalAnnouncements = $announcementsStmt->fetchColumn();

$recentInternshipsStmt = $pdo->query("
    SELECT title, company, location, created_at 
    FROM internships 
    ORDER BY created_at DESC 
    LIMIT 5
");
$recentInternships = $recentInternshipsStmt->fetchAll(PDO::FETCH_ASSOC);

$recentInterestedStmt = $pdo->query("
    SELECT s.full_name, s.email, i.title AS internship_title, i.company, ib.created_at
    FROM internship_bookmarks ib
    JOIN students s ON s.id = ib.student_id
    JOIN internships i ON i.id = ib.internship_id
    ORDER BY ib.created_at DESC
    LIMIT 5
");
$recentInterested = $recentInterestedStmt->fetchAll(PDO::FETCH_ASSOC);

$recentAnnouncementsStmt = $pdo->query("
    SELECT title, category, created_at 
    FROM announcements 
    ORDER BY created_at DESC 
    LIMIT 5
");
$recentAnnouncements = $recentAnnouncementsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php
$stmt = $pdo->query("SELECT id, name, email, role FROM admins ORDER BY id ASC");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {

    $admin_ids = $_POST['admin_id'];
    $roles = $_POST['role'];

    if (!is_array($roles))
        $roles = [$roles];
    if (!is_array($admin_ids))
        $admin_ids = [$admin_ids];

    $superadmincount = 0;
    foreach ($roles as $role) {
        if ($role === 'superadmin')
            $superadmincount++;
    }

    if ($superadmincount > 3) {
        $_SESSION['error'] = "Only three superadmins are allowed.";
        header("Location: superadmin.php");
        exit;
    }

    $fetchOldRole = $pdo->prepare("SELECT role FROM admins WHERE id = ?");
    $updateStmt = $pdo->prepare("UPDATE admins SET role = ? WHERE id = ?");
    $notifStmt = $pdo->prepare("
        INSERT INTO notifications (user_id, user_type, title, message, link, is_read, created_at)
        VALUES (?, 'admin', ?, ?, ?, FALSE, NOW())
    ");

    $changedCount = 0;

    for ($i = 0; $i < count($admin_ids); $i++) {
        $fetchOldRole->execute([$admin_ids[$i]]);
        $oldRole = $fetchOldRole->fetchColumn();

        $updateStmt->execute([$roles[$i], $admin_ids[$i]]);

        if ($oldRole !== $roles[$i]) {
            $changedCount++;
            $notifStmt->execute([
                $admin_ids[$i],
                'Role Updated',
                'Your role has been changed from ' . $oldRole . ' to ' . $roles[$i] . ' by the superadmin.',
                $roles[$i] === 'superadmin' ? 'superadmin.php' : 'internship-ui.php'
            ]);
        }
    }

    $stmtActivity = $pdo->prepare("INSERT INTO audits (user_id, roles, activity, activity_date) VALUES (:user_id, :roles, :activity, NOW())");
    $stmtActivity->execute([
        ':user_id' => $_SESSION['user_id'],
        ':roles' => 'superadmin',
        ':activity' => 'Updated admin roles'
    ]);

    if ($changedCount > 0) {
        $_SESSION['success'] = "Successfully updated {$changedCount} role(s).";
    } else {
        $_SESSION['info'] = "No roles were changed.";
    }

    header("Location: superadmin.php?section=roles");
    exit;
}

$stmt = $pdo->query("
    SELECT id, full_name AS name, email, 'student' AS role, 'students' AS source FROM students
    UNION ALL
    SELECT id, name, email, role, 'admins' AS source FROM admins WHERE role != 'superadmin'
    UNION ALL
    SELECT id, full_name AS name, email, role::text AS role, 'advisers' AS source FROM advisers
    ORDER BY name ASC
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT 
        a.id,
        a.user_id,
        a.roles,
        a.activity,
        a.activity_date,
        COALESCE(u.name, 'Unknown (#' || a.user_id || ')') AS name
    FROM audits a
    LEFT JOIN (
        SELECT id, full_name AS name, 'student' AS role FROM students
        UNION ALL
        SELECT id, name, role FROM admins
        UNION ALL
        SELECT id, full_name AS name, role::text AS role FROM advisers
    ) u
        ON a.user_id = u.id
        AND LOWER(a.roles) = LOWER(u.role)
    ORDER BY a.activity_date DESC
    LIMIT 30
");
$activityLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>System Admin | CEE IT Connects</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <style>
        :root {
            --gradient-start: #FFB62F;
            --gradient-end: #E4572E;
            --primary-dark-blue: #272f54;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: linear-gradient(135deg, #f5f7ff, #eef1ff);
            min-height: 100vh;
            padding-top: 70px;
            overflow-x: hidden;
        }

        .dashboard-container {
            width: 100%;
            max-width: 600px;
            background: #fff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            animation: fadeIn 0.8s ease-in-out;
        }

        .success-box {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
            opacity: 1;
            transition: opacity 0.5s ease;
        }

        .admin-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .admin-form input,
        .admin-form select {
            padding: 12px 14px;
            border-radius: 10px;
            border: 1px solid #ddd;
            font-size: 14px;
            outline: none;
            transition: 0.3s;
        }

        .admin-form input:focus,
        .admin-form select:focus {
            border-color: var(--gradient-end);
            box-shadow: 0 0 8px rgba(228, 87, 46, 0.3);
        }

        .layout {
            display: flex;
            height: 100vh;
            width: 100vw;
        }

        /* SIDEBAR */
        .sidebar {
            width: 220px;
            margin-top: 10px;
            background: #272f54;
            color: white;
            padding: 25px;
            display: flex;
            flex-direction: column;
            height: 100%;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: scroll;
            scrollbar-width: none;
            padding-bottom: 70px;
        }

        .sidebar h3 {
            margin-bottom: 20px;
        }

        .sidebar a {
            text-decoration: none;
            color: white;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 10px;
            transition: 0.3s;
        }

        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #E4572E;
        }

        .sidebar a.active {
            background: #ffbd41;
            color: #272f54;
        }

        /* MAIN CONTENT */
        .main-content {
            flex: 1;
            padding: 40px;
            background: #f5f7ff;
            margin-left: 220px;
            min-width: 0;
            scrollbar-width: none;
        }

        /* SECTIONS */
        .section {
            display: none;
        }

        .section.active {
            display: block;
        }

        /* Section card wrapper */
        .sysAdm-section {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            width: 100%;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .sysAdm-header h2 {
            font-size: 26px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .sysAdm-header p {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 20px;
        }

        /* Styled tables */
        .sysAdm-table {
            width: 100%;
            border-collapse: collapse;
            overflow: hidden;
        }

        .sysAdm-table thead {
            background: #eaedef;
        }

        .sysAdm-table th {
            text-align: left;
            padding: 14px 18px;
            font-size: 13px;
            font-weight: 700;
            color: #475569;
            border-bottom: 2px solid #dbe1ea;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .sysAdm-table td {
            padding: 14px 18px;
            font-size: 14px;
            color: #334155;
            border-bottom: 1px solid #edf2f7;
        }

        .sysAdm-table tbody tr {
            transition: background 0.2s ease;
        }

        .sysAdm-table tbody tr:hover {
            background: #f8fafc;
        }

        /* Buttons */
        .btn-update {
            margin-top: 18px;
            padding: 11px 24px;
            font-size: 15px;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            background: #4f51a8;
            color: #fff;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.15s;
        }

        .btn-update:hover {
            background: #3A3B7B;
        }

        .btn-create {
            padding: 14px;
            border-radius: 12px;
            border: none;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            font-weight: 700;
            cursor: pointer;
            font-size: 15px;
            transition: box-shadow 0.2s;
        }

        .btn-create:hover {
            box-shadow: 0 8px 20px rgba(228, 87, 46, 0.3);
        }

        .btn-delete {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 8px;
            border: 1px solid #f7c1c1;
            background: #fcebeb;
            color: #a32d2d;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-delete:hover {
            background: #f09595;
            color: #791f1f;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /*susu*/

        @media (max-width: 768px) {
            .layout {
                flex-direction: row;
            }

            .sidebar {
                top: 60px;
                width: 60px !important;
                padding: 10px 0 !important;
                align-items: center;
                overflow: visible !important;
                z-index: 1050;
            }

            .sidebar h3 {
                display: none !important;
            }

            .sidebar a {
                width: 44px !important;
                height: 44px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                border-radius: 12px !important;
                margin: 0 auto 8px auto !important;
                padding: 0 !important;
                position: relative;
            }

            .sidebar a i {
                margin: 0 !important;
            }

            /* Hide text labels */
            .sidebar a .nav-label {
                display: none !important;
            }

            /* Tooltip */
            .sidebar a::after {
                content: attr(data-tooltip);
                position: absolute;
                left: 56px;
                top: 50%;
                transform: translateY(-50%);
                background: #1a1a2e;
                color: #fff;
                font-size: 12px;
                font-weight: 500;
                padding: 5px 10px;
                border-radius: 6px;
                white-space: nowrap;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.2s ease;
            }

            .sidebar a:hover::after {
                opacity: 1;
            }

            .main-content {
                margin-left: 60px !important;
                margin-right: 15px;
                padding: 15px !important;
            }

            /* STAT CARDS — 1 row, 3 columns */
            .row.g-3.mb-4 {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 4px !important;
            }

            .row.g-3.mb-4 .col-md-4 {
                width: 100% !important;
                padding: 0 !important;
            }

            .row.g-3.mb-4 .card {
                border-radius: 10px !important;
            }

            .row.g-3.mb-4 .card-body {
                padding: 8px !important;
            }

            .row.g-3.mb-4 .card-body p {
                font-size: 10px !important;
                margin-bottom: 4px !important;
                letter-spacing: 0 !important;
            }

            .row.g-3.mb-4 .card-body .d-flex {
                flex-wrap: nowrap !important;
                align-items: flex-start !important;
                justify-content: space-between !important;
                gap: 4px !important;
            }

            .row.g-3.mb-4 .card-body .d-flex > div:first-child {
                flex: 1 !important;
            }

            .row.g-3.mb-4 .card-body h2 {
                font-size: 1.3rem !important;
                margin-bottom: 0 !important;
            }

            /* Hide icon box to save space */
            .row.g-3.mb-4 .card-body .rounded-3 {
                width: 24px !important;
                height: 24px !important;
                flex-shrink: 0;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                align-self: flex-start !important;
                margin-top: 2px !important;
            }
            .row.g-3.mb-4 .card-body .rounded-3 i {
                font-size: 10px !important;
            }

            /* TABLES — allow horizontal scroll */
            .sysAdm-table {
                font-size: 12px !important;
            }

            .sysAdm-table th,
            .sysAdm-table td {
                padding: 10px 10px !important;
                font-size: 12px !important;
            }

            /* DASHBOARD CONTAINER (forms) */
            .dashboard-container {
                padding: 20px !important;
                max-width: 100% !important;
            }

            /* SECTION HEADERS */
            .sysAdm-header h2 {
                font-size: 20px !important;
            }

            /* DOWNLOAD BUTTONS */
            .d-flex.justify-content-end.gap-2.mt-4 {
                flex-direction: row !important;
                align-items: center !important;
                justify-content: center;
            }

            .d-flex.justify-content-end.gap-2.mt-4 button {
                flex: 1;
                font-size: 12px;
                padding: 8px;
                justify-content: center !important;
            }

            .stat-card-title {
                font-size: 9px !important;
                letter-spacing: 0 !important;
                margin-bottom: 4px !important;
                white-space: normal !important;
                line-height: 1.1 !important;	
                word-break: break-word !important;
            }

            .stat-card-number {
                font-size: 1.3rem !important;
            }
        }

        @media (max-width: 480px) {
            .row.g-3.mb-4 {
                gap: 3px !important;
            }

            .row.g-3.mb-4 .card-body {
                padding: 6px !important;
            }

            .row.g-3.mb-4 .card-body p {
                font-size: 8px !important;
                letter-spacing: 0 !important;
                margin-bottom: 2px !important;
                line-height: 1.2 !important;
                word-break: break-word !important;
            }

            .row.g-3.mb-4 .card-body h2 {
                font-size: 1rem !important;
                margin-bottom: 0 !important;
            }

            .row.g-3.mb-4 .card-body .rounded-3 {
                width: 24px !important;
                height: 24px !important;
            }

            .row.g-3.mb-4 .card-body .rounded-3 i {
                font-size: 10px !important;
            }

            .stat-card-title {
                font-size: 7px !important;
                line-height: 1.1 !important;
                word-break: break-word !important;
            }

            .stat-card-number {
                font-size: 1rem !important;
            }
        }
    </style>
</head>

<script>
    function showSection(event, sectionId) {
        document.querySelectorAll('.section').forEach(sec => sec.classList.remove('active'));
        document.getElementById(sectionId).classList.add('active');
        document.querySelectorAll('.sidebar a').forEach(link => link.classList.remove('active'));
        event.currentTarget.classList.add('active');
    }

    function confirmSuperadminGlobal() {
        const roles = document.querySelectorAll("select[name='role[]']");
        let superadminCount = 0;
        let hasChangeToSuperadmin = false;
        let hasRemovalFromSuperadmin = false;

        roles.forEach(select => {
            const original = select.dataset.original;
            const current = select.value;
            if (current === 'superadmin') superadminCount++;
            if (current === 'superadmin' && original !== 'superadmin') hasChangeToSuperadmin = true;
            if (original === 'superadmin' && current !== 'superadmin') hasRemovalFromSuperadmin = true;
        });

        if (superadminCount > 3) { alert("Only 3 superadmins are allowed."); return false; }
        if (hasRemovalFromSuperadmin) return confirm("You are removing a Superadmin. Continue?");
        if (hasChangeToSuperadmin) return confirm("You are assigning a Superadmin. Are you sure?");
        return true;
    }
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

<body>

    <?php include 'navbar.php'; ?>

    <!-- Flash messages (Doc 2 logic) -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3"
            style="z-index:9999; min-width:350px;" role="alert" id="flashAlert">
            <i class="bi bi-check-circle-fill me-2"></i>
                <?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['info'])): ?>
                <div class="alert alert-info alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3"
                    style="z-index:9999; min-width:350px;" role="alert" id="flashAlert">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <?= $_SESSION['info'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['info']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3"
                    style="z-index:9999; min-width:350px;" role="alert" id="flashAlert">
                    <i class="bi bi-x-circle-fill me-2"></i>
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="layout">

        <!-- SIDEBAR (Doc 1 design: icons + styled active state) -->
        <div class="sidebar">
            <h3>Superadmin</h3>
            <a href="#" onclick="showSection(event, 'dashboard')" class="active"
                data-tooltip="Dashboard">
                <i class="bi bi-person-fill-lock me-2"></i>
                <span class="nav-label">Dashboard</span>
            </a>
            <a href="#" onclick="showSection(event, 'add-admin')"
                data-tooltip="Add Admin">
                <i class="bi bi-person-plus me-2"></i> 
                <span class="nav-label">Admin</span>
            </a>
            <a href="#" onclick="showSection(event, 'add-adviser')"
                data-tooltip="Add Adviser">
                <i class="bi bi-person-vcard me-2"></i>
                <span class="nav-label">Add Adviser</span>
            </a>
            <a href="#" onclick="showSection(event, 'delete')"
                data-tooltip="Delete Account">
                <i class="bi bi-person-x me-2"></i>
                <span class="nav-label">Delete Account</span>
            </a>
            <a href="#" onclick="showSection(event, 'roles')"
                data-tooltip="Change Roles">
                <i class="bi bi-shuffle me-2"></i> 
                <span class="nav-label">Change Roles</span>
            </a>
            <a href="#" onclick="showSection(event, 'monitor')"
                data-tooltip="Monitor">
                <i class="bi bi-binoculars me-2"></i> 
                <span class="nav-label">Monitor</span>
            </a>
        </div>

        <!-- MAIN CONTENT -->
        <div class="main-content">

            <!-- DASHBOARD SECTION -->
            <div id="dashboard" class="section active">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h4 class="fw-bold mb-0" style="color:#272f54;">System Admin Overview</h4>
                        <p class="text-muted small mb-0">Live summary from the internship admin panel</p>
                    </div>
                    <span class="badge rounded-pill px-3 py-2" style="background:green; font-size:12px;">
                        <i class="bi bi-circle-fill me-1" style="color:#4cff91; font-size:8px;"></i> Live
                    </span>
                </div>

                <!-- Stat Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card border-0 rounded-4 h-100" style="background:#272f54;">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <p class="text-white-50 small mb-1 fw-semibold text-uppercase stat-card-title"
                                            style="letter-spacing:.05em;">Internship Postings</p>
                                        <h2 class="fw-bold text-white mb-0 stat-card-number"><?= (int) $totalInternships ?></h2>
                                    </div>
                                    <div class="rounded-3 d-flex align-items-center justify-content-center"
                                        style="width:44px;height:44px;background:rgba(255,255,255,0.1);">
                                        <i class="bi bi-briefcase-fill text-white fs-5"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 rounded-4 h-100" style="background:#FFB62F;">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <p class="small mb-1 fw-semibold text-uppercase stat-card-title"
                                            style="letter-spacing:.05em;color:#7a5200;">Students Interested</p>
                                        <h2 class="fw-bold mb-0 stat-card-number" style="color:#3b2600;"><?= (int) $totalInterested ?></h2>
                                    </div>
                                    <div class="rounded-3 d-flex align-items-center justify-content-center"
                                        style="width:44px;height:44px;background:rgba(0,0,0,0.1);">
                                        <i class="bi bi-bookmarks-fill fs-5" style="color:#3b2600;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 rounded-4 h-100" style="background:#E4572E;">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <p class="text-white-50 small mb-1 fw-semibold text-uppercase stat-card-title"
                                            style="letter-spacing:.05em;">Announcements</p>
                                        <h2 class="fw-bold text-white mb-0 stat-card-number"><?= (int) $totalAnnouncements ?></h2>
                                    </div>
                                    <div class="rounded-3 d-flex align-items-center justify-content-center"
                                        style="width:44px;height:44px;background:rgba(255,255,255,0.15);">
                                        <i class="bi bi-bell-fill text-white fs-5"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                

                <!-- Recent Internship Postings -->
                <div class="card border-0 rounded-4 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex align-items-center gap-2">
                        <i class="bi bi-briefcase" style="color:#272f54;"></i>
                        <h6 class="fw-bold mb-0" style="color:#272f54;">Recent Internship Postings</h6>
                        <span class="badge ms-auto rounded-pill" style="background:#eef1ff;color:#272f54;font-size:11px;">Latest 5</span>
                    </div>
                    <div class="card-body px-4 pb-4 pt-2">
                        <?php if (empty($recentInternships)): ?>
                                    <p class="text-muted small mb-0">No internships posted yet.</p>
                        <?php else: ?>
                                    <div class="table-responsive">
                                        <table id="table-internships" class="table table-hover align-middle mb-0" style="font-size:14px;">
                                            <thead>
                                                <tr style="color:#aaa;font-size:12px;text-transform:uppercase;letter-spacing:.04em;">
                                                    <th class="border-0 pb-2 fw-semibold">Title</th>
                                                    <th class="border-0 pb-2 fw-semibold">Company</th>
                                                    <th class="border-0 pb-2 fw-semibold">Location</th>
                                                    <th class="border-0 pb-2 fw-semibold">Posted</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentInternships as $ri): ?>
                                                            <tr>
                                                                <td class="fw-semibold" style="color:#272f54;"><?= htmlspecialchars($ri['title']) ?></td>
                                                                <td><?= htmlspecialchars($ri['company']) ?></td>
                                                                <td class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($ri['location']) ?></td>
                                                                <td>
                                                                    <span class="badge rounded-pill px-3"
                                                                        style="background:#f0f4ff;color:#272f54;font-weight:500;font-size:12px;">
                                                                        <?= date("M d, Y", strtotime($ri['created_at'])) ?>
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Bottom Row: Interested Students + Announcements -->
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="card border-0 rounded-4 shadow-sm h-100">
                            <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex align-items-center gap-2">
                                <i class="bi bi-people" style="color:#272f54;"></i>
                                <h6 class="fw-bold mb-0" style="color:#272f54;">Recently Interested Students</h6>
                                <span class="badge ms-auto rounded-pill" style="background:#fff8e1;color:#7a5200;font-size:11px;">Latest 5</span>
                            </div>
                            <div id="table-interested" class="card-body px-4 pb-4 pt-2">
                                <?php if (empty($recentInterested)): ?>
                                            <p class="text-muted small mb-0">No student interest recorded yet.</p>
                                <?php else: ?>
                                            <div class="d-flex flex-column gap-3">
                                                <?php foreach ($recentInterested as $ri): ?>
                                                            <div class="d-flex align-items-center gap-3">
                                                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 fw-bold"
                                                                    style="width:38px;height:38px;background:#eef1ff;color:#272f54;font-size:13px;">
                                                                    <?= strtoupper(substr($ri['full_name'], 0, 1)) ?>
                                                                </div>
                                                                <div class="flex-grow-1 overflow-hidden">
                                                                    <p class="fw-semibold mb-0 text-truncate" style="color:#272f54;font-size:14px;">
                                                                        <?= htmlspecialchars($ri['full_name']) ?>
                                                                    </p>
                                                                    <p class="text-muted mb-0 text-truncate" style="font-size:12px;">
                                                                        <?= htmlspecialchars($ri['email']) ?>
                                                                    </p>
                                                                </div>
                                                                <div class="text-end flex-shrink-0">
                                                                    <span class="badge rounded-pill px-2"
                                                                        style="background:#f5f5f5;color:#555;font-size:11px;font-weight:500;">
                                                                        <?= htmlspecialchars($ri['company']) ?>
                                                                    </span>
                                                                    <p class="text-muted mb-0 mt-1" style="font-size:11px;">
                                                                        <?= date("M d", strtotime($ri['created_at'])) ?>
                                                                    </p>
                                                                </div>
                                                            </div>
                                                <?php endforeach; ?>
                                            </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card border-0 rounded-4 shadow-sm h-100">
                            <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex align-items-center gap-2">
                                <i class="bi bi-bell" style="color:#272f54;"></i>
                                <h6 class="fw-bold mb-0" style="color:#272f54;">Recent Announcements</h6>
                                <span class="badge ms-auto rounded-pill" style="background:#fdecea;color:#7f1d1d;font-size:11px;">Latest 5</span>
                            </div>
                            <div id="table-announcements" class="card-body px-4 pb-4 pt-2">
                                <?php if (empty($recentAnnouncements)): ?>
                                            <p class="text-muted small mb-0">No announcements yet.</p>
                                <?php else: ?>
                                            <div class="d-flex flex-column gap-3">
                                                <?php foreach ($recentAnnouncements as $a):
                                                    $catColors = [
                                                        'news' => ['bg' => '#e6f1fb', 'color' => '#0c447c'],
                                                        'updates' => ['bg' => '#eaf3de', 'color' => '#27500a'],
                                                        'FAQs' => ['bg' => '#faeeda', 'color' => '#633806'],
                                                    ];
                                                    $c = $catColors[$a['category']] ?? ['bg' => '#f0f0f0', 'color' => '#444'];
                                                    ?>
                                                            <div class="d-flex align-items-start gap-3">
                                                                <span class="badge rounded-pill px-3 py-2 flex-shrink-0"
                                                                    style="background:<?= $c['bg'] ?>;color:<?= $c['color'] ?>;font-size:11px;font-weight:600;">
                                                                    <?= htmlspecialchars(ucfirst($a['category'])) ?>
                                                                </span>
                                                                <div class="flex-grow-1 overflow-hidden">
                                                                    <p class="fw-semibold mb-0 text-truncate" style="color:#272f54;font-size:14px;">
                                                                        <?= htmlspecialchars($a['title']) ?>
                                                                    </p>
                                                                    <p class="text-muted mb-0" style="font-size:12px;">
                                                                        <?= date("M d, Y", strtotime($a['created_at'])) ?>
                                                                    </p>
                                                                </div>
                                                            </div>
                                                <?php endforeach; ?>
                                            </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Download buttons -->
                    <div class="d-flex justify-content-end gap-2 mt-4 mb-3">
                        <button onclick="downloadDashboardCSV()" class="btn btn-sm btn-outline-secondary"
                            style="background:#272f54;color:white;">
                            <i class="bi bi-filetype-csv me-1"></i> Download CSV
                        </button>
                        <button onclick="downloadDashboardPDF()" class="btn btn-sm"
                            style="background:#272f54;color:white;">
                            <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
                        </button>
                    </div>
                </div>

                <!-- ACTIVITY FEED PREVIEW -->
                <div class="card border-0 rounded-4 shadow-sm h-100">
                    <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex align-items-center gap-2">
                        <i class="bi bi-activity" style="color:#272f54;"></i>
                        <h6 class="fw-bold mb-0" style="color:#272f54;">Recent Activity</h6>
                        <span class="badge ms-auto rounded-pill" 
                            style="background:#f0f4ff;color:#272f54;font-size:11px;">Latest 5</span>
                    </div>
                    <div class="card-body px-4 pb-4 pt-2">
                        <?php if (empty($activityLogs)): ?>
                            <p class="text-muted small mb-0">No recent activity.</p>
                        <?php else: ?>
                            <div class="d-flex flex-column">
                                <?php 
                                $recentLogs = array_slice($activityLogs, 0, 5);
                                foreach ($recentLogs as $i => $log): 
                                    $roleColors = [
                                        'superadmin'        => ['bg' => '#eef1ff', 'color' => '#272f54', 'icon' => 'bi-shield-fill'],
                                        'internship_admin'  => ['bg' => '#fff8e1', 'color' => '#7a5200', 'icon' => 'bi-person-fill-gear'],
                                        'student'           => ['bg' => '#e8f5e9', 'color' => '#1b5e20', 'icon' => 'bi-person-fill'],
                                        'adviser'           => ['bg' => '#fdecea', 'color' => '#7f1d1d', 'icon' => 'bi-person-badge-fill'],
                                    ];
                                    $rc = $roleColors[strtolower($log['roles'])] ?? 
                                        ['bg' => '#f0f0f0', 'color' => '#444', 'icon' => 'bi-person-fill'];

                                    // Format date
                                    $date = new DateTime($log['activity_date']);
                                    $now  = new DateTime();
                                    $diff = $now->diff($date);
                                    if ($diff->days == 0) {
                                        if ($diff->h == 0)
                                            $timeAgo = $diff->i . ' min ago';
                                        else
                                            $timeAgo = $diff->h . ' hr ago';
                                    } elseif ($diff->days == 1) {
                                        $timeAgo = 'Yesterday';
                                    } else {
                                        $timeAgo = $diff->days . ' days ago';
                                    }
                                ?>
                                <div class="d-flex align-items-center gap-3 py-3 
                                    <?= $i < count($recentLogs) - 1 ? 'border-bottom' : '' ?>">
                                    
                                    <!-- Icon -->
                                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                        style="width:38px;height:38px;background:<?= $rc['bg'] ?>;">
                                        <i class="bi <?= $rc['icon'] ?>" style="color:<?= $rc['color'] ?>;font-size:16px;"></i>
                                    </div>

                                    <!-- Info -->
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="fw-semibold mb-0 text-truncate" style="color:#272f54;font-size:14px;">
                                            <?= htmlspecialchars($log['name']) ?>
                                        </p>
                                        <p class="text-muted mb-0 text-truncate" style="font-size:12px;">
                                            <?= htmlspecialchars($log['activity']) ?>
                                        </p>
                                    </div>

                                    <!-- Role + Time -->
                                    <div class="text-end flex-shrink-0">
                                        <span class="badge rounded-pill px-2 mb-1 d-block"
                                            style="background:<?= $rc['bg'] ?>;color:<?= $rc['color'] ?>;font-size:10px;font-weight:600;">
                                            <?= htmlspecialchars(ucfirst($log['roles'])) ?>
                                        </span>
                                        <p class="text-muted mb-0" style="font-size:11px;">
                                            <?= $timeAgo ?>
                                        </p>
                                    </div>

                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Registration Toggle -->
                <div id="register-toggle" class="mt-4">
                    <div class="sysAdm-header">
                        <h2>Registration Link</h2>
                    </div>
                    <div class="dashboard-container">
                        <p>Control whether the registration link is visible on the login page.</p>
                        <form method="POST">
                            <p>Current Status:
                                <strong style="color: <?= $registerVisible === 'show' ? 'green' : 'red' ?>">
                                    <?= $registerVisible === 'show' ? 'Visible' : 'Hidden' ?>
                                </strong>
                            </p>
                            <button type="submit" name="toggle_register" class="btn-update">
                                <?= $registerVisible === 'show' ? 'Hide Registration Link' : 'Show Registration Link' ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ADD ADMIN ACCOUNT SECTION -->
            <!-- ADD ADMIN ACCOUNT SECTION -->
            <div id="add-admin" class="section">
                <div class="sysAdm-header">
                    <h2>Create New Admin Account</h2>
                    <p>Admins can be assigned to an internship administration role.</p>
                </div>
                <div style="display:flex; justify-content:center;">
                    <div class="dashboard-container">
                        <form method="POST" action="superadmin-db.php" class="admin-form">
                            <input type="text"     name="name"     placeholder="Full Name"      required>
                            <input type="email"    name="email"    placeholder="Email Address"  required>
                            <input type="password" name="password" placeholder="Password"       required>
                            <select name="role" required>
                                <option value="" disabled selected>Select Role</option>
                                <option value="internship_admin">Internship Admin</option>
                            </select>
                            <div style="display:flex;width:100%;justify-content:flex-end;margin-top:12px;">
                                <button type="submit" name="create-admin" class="btn-create">Create Admin</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ADD ADVISER ACCOUNT SECTION -->
            <!-- ADD ADVISER ACCOUNT SECTION -->
            <div id="add-adviser" class="section">
                <div class="sysAdm-header" style="padding: 0 0 10px 0;">
                    <h2>Create New Adviser Account</h2>
                    <p>Advisers can be assigned to either HTE or Internship advising roles.</p>
                </div>
                <div style="display:flex; justify-content:center;">
                    <div class="dashboard-container">
                        <form method="POST" action="superadmin-db.php" class="admin-form">
                            <input type="text" name="name" placeholder="Full Name" required>
                            <input type="email" name="email" placeholder="Email Address" required>
                            <input type="password" name="password" placeholder="Password" required>
                            <select name="role" id="adviserRole" required>
                                <option value="" disabled selected>Select Role</option>
                                <option value="HTE_adviser">HTE Adviser</option>
                                <option value="internship_adviser">Internship Adviser</option>
                            </select>
                            <div id="internshipWrapper" style="display:none;">
                                <select name="internship_id" id="internshipSelect">
                                    <option value="" selected>Select Internship</option>
                                    <?php foreach ($internships as $internship): ?>
                                        <option value="<?= $internship['id'] ?>">
                                            <?= htmlspecialchars($internship['company']) ?> — <?= htmlspecialchars($internship['title']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div style="display:flex;width:100%;justify-content:flex-end;margin-top:12px;">
                                <button type="submit" name="create-adviser" class="btn-create">Create Adviser</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- DELETE USER SECTION -->
            <div id="delete" class="section sysAdm-section">
                <div class="sysAdm-header">
                    <h2>Delete Account</h2>
                    <p>Permanently remove users from the system.</p>
                </div>
                <div style="overflow-x: auto;">
                <table class="sysAdm-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($u['name']) ?></td>
                                        <td><?= htmlspecialchars($u['email']) ?></td>
                                        <td><?= htmlspecialchars($u['role']) ?></td>
                                        <td>
                                            <form method="POST" action="superadmin-db.php"
                                                onsubmit="return confirm('Are you sure you want to delete this user?')">
                                                <input type="hidden" name="id"     value="<?= $u['id'] ?>">
                                                <input type="hidden" name="source" value="<?= $u['source'] ?>">
                                                <button type="submit" name="delete" class="btn-delete">
                                                    <i class="bi bi-trash-fill"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>

            <!-- CHANGE ROLES SECTION -->
            <div id="roles" class="section sysAdm-section">
                <div class="sysAdm-header" style="overflow-x: auto;">
                    <h2>Admin Management</h2>
                    <p>Assign and update roles for admin users.</p>
                </div>

                <form method="POST" onsubmit="return confirmSuperadminGlobal()">
                    <div style="overflow-x: auto;">
                    <table class="sysAdm-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $admin): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($admin['name']) ?></td>
                                            <td><?= htmlspecialchars($admin['email']) ?></td>
                                            <td>
                                                <input type="hidden" name="admin_id[]" value="<?= $admin['id'] ?>">
                                                <select name="role[]" data-original="<?= $admin['role'] ?>" required>
                                                    <option value="null" disabled
                                                        <?= $admin['role'] !== 'superadmin' && $admin['role'] !== 'internship_admin' ? 'selected' : '' ?>>
                                                        None
                                                    </option>
                                                    <option value="superadmin"       <?= $admin['role'] == 'superadmin' ? 'selected' : '' ?>>Superadmin</option>
                                                    <option value="internship_admin" <?= $admin['role'] == 'internship_admin' ? 'selected' : '' ?>>Internship Admin</option>
                                                </select>
                                            </td>
                                        </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                    <div class="text-end">
                        <button type="submit" name="update_role" class="btn-update">
                            <i class="bi bi-floppy2"></i> Update Roles
                        </button>
                    </div>
                </form>
            </div>

            <!-- MONITOR SECTION -->
            <div id="monitor" class="section sysAdm-section">
                <div class="sysAdm-header">
                    <h2>System Monitoring</h2>
                    <p>Recent user activities and actions.</p>
                </div>
                <div style="overflow-x: auto;">
                <table class="sysAdm-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Activity</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activityLogs as $log): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($log['name']) ?></td>
                                        <td><?= htmlspecialchars($log['roles']) ?></td>
                                        <td><?= htmlspecialchars($log['activity']) ?></td>
                                        <td><?= htmlspecialchars($log['activity_date']) ?></td>
                                    </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>

        </div><!-- /.main-content -->
    </div><!-- /.layout -->

    <script>
        // Auto-dismiss flash alerts after 3 seconds
        setTimeout(() => {
            const alert = document.getElementById('flashAlert');
            if (alert) {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 300);
            }
        }, 3000);

        // Adviser role → show/hide internship dropdown
        document.addEventListener('DOMContentLoaded', function () {
            const role       = document.getElementById('adviserRole');
            const wrapper    = document.getElementById('internshipWrapper');
            const internship = document.getElementById('internshipSelect');

            function toggleInternshipDropdown() {
                if (role.value === 'HTE_adviser') {
                    wrapper.style.display  = 'block';
                    internship.required    = true;
                } else {
                    wrapper.style.display  = 'none';
                    internship.required    = false;
                    internship.value       = '';
                }
            }

            role.addEventListener('change', toggleInternshipDropdown);
            toggleInternshipDropdown();
        });

        function downloadDashboardCSV() {
            let csv = '';
            csv += 'Recent Internship Postings\n';
            csv += '"Title","Company","Location","Posted"\n';
            document.querySelectorAll('#table-internships tbody tr').forEach(row => {
                const cells = [...row.querySelectorAll('td')].map(td => `"${td.innerText.trim().replace(/\n/g,' ').replace(/"/g,'""')}"`);
                if (cells.length) csv += cells.join(',') + '\n';
            });

            csv += '\nRecently Interested Students\n';
            csv += '"Name","Email","Company","Date"\n';
            document.querySelectorAll('#table-interested .d-flex.align-items-center').forEach(row => {
                const name    = row.querySelector('.fw-semibold')?.innerText.trim() ?? '';
                const email   = row.querySelectorAll('p')[1]?.innerText.trim() ?? '';
                const company = row.querySelector('.badge')?.innerText.trim() ?? '';
                const date    = row.querySelector('.text-muted.mb-0.mt-1')?.innerText.trim() ?? '';
                csv += `"${name}","${email}","${company}","${date}"\n`;
            });

            csv += '\nRecent Announcements\n';
            csv += '"Category","Title","Date"\n';
            document.querySelectorAll('#table-announcements .d-flex.align-items-start').forEach(row => {
                const category = row.querySelector('.badge')?.innerText.trim() ?? '';
                const title    = row.querySelector('.fw-semibold')?.innerText.trim() ?? '';
                const date     = row.querySelector('.text-muted')?.innerText.trim() ?? '';
                csv += `"${category}","${title}","${date}"\n`;
            });

            const blob = new Blob([csv], { type: 'text/csv' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'dashboard_summary.csv';
            a.click();
        }

        function downloadDashboardPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            let y = 15;

            doc.setFontSize(16);
            doc.setTextColor(39, 47, 84);
            doc.text('Dashboard Summary', 14, y);
            y += 10;

            doc.setFontSize(12);
            doc.setTextColor(0, 0, 0);
            doc.text('Recent Internship Postings', 14, y);
            y += 2;

            const internshipRows = [];
            document.querySelectorAll('#table-internships tbody tr').forEach(row => {
                const cells = [...row.querySelectorAll('td')].map(td => td.innerText.trim().replace(/\n/g,' '));
                if (cells.length) internshipRows.push(cells);
            });
            doc.autoTable({
                head: [['Title','Company','Location','Posted']],
                body: internshipRows,
                startY: y,
                styles: { fontSize: 10 },
                headStyles: { fillColor: [39,47,84], textColor: [255,255,255] },
            });
            y = doc.lastAutoTable.finalY + 10;

            doc.text('Recently Interested Students', 14, y);
            y += 2;
            const studentRows = [];
            document.querySelectorAll('#table-interested .d-flex.align-items-center').forEach(row => {
                const name    = row.querySelector('.fw-semibold')?.innerText.trim() ?? '';
                const email   = row.querySelectorAll('p')[1]?.innerText.trim() ?? '';
                const company = row.querySelector('.badge')?.innerText.trim() ?? '';
                const date    = row.querySelector('.text-muted.mb-0.mt-1')?.innerText.trim() ?? '';
                studentRows.push([name, email, company, date]);
            });
            doc.autoTable({
                head: [['Name','Email','Company','Date']],
                body: studentRows,
                startY: y,
                styles: { fontSize: 10 },
                headStyles: { fillColor: [255,182,47], textColor: [39,47,84] },
            });
            y = doc.lastAutoTable.finalY + 10;

            doc.text('Recent Announcements', 14, y);
            y += 2;
            const announcementRows = [];
            document.querySelectorAll('#table-announcements .d-flex.align-items-start').forEach(row => {
                const category = row.querySelector('.badge')?.innerText.trim() ?? '';
                const title    = row.querySelector('.fw-semibold')?.innerText.trim() ?? '';
                const date     = row.querySelector('.text-muted')?.innerText.trim() ?? '';
                if (title) announcementRows.push([category, title, date]);
            });
            doc.autoTable({
                head: [['Category','Title','Date']],
                body: announcementRows,
                startY: y,
                styles: { fontSize: 10 },
                headStyles: { fillColor: [228,87,46], textColor: [255,255,255] },
            });

            doc.save('dashboard_summary.pdf');
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>