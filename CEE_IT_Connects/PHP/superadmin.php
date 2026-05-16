<?php
require 'db.php';
require 'auth.php';

$statePath = __DIR__ . '/register_toggle.txt';
$registerVisible = file_exists($statePath) ? trim(file_get_contents($statePath)) : 'show';

if (isset($_POST['toggle_register'])) {
    $newState = $registerVisible === 'show' ? 'hide' : 'show';
    file_put_contents($statePath, $newState);
    header("Location: superadmin.php");
    exit();
}

/* var_dump($_SESSION);
exit(); */
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

    if (!is_array($roles)) {
        $roles = [$roles];
    }

    if (!is_array($admin_ids)) {
        $admin_ids = [$admin_ids];
    }
    $superadmincount = 0;
    foreach ($roles as $role) {
        if ($role === 'superadmin') {
            $superadmincount++;
        }
    }

    if ($superadmincount > 3) {
        die("Only three superadmins are allowed buckooo.");
    }

    $stmt = $pdo->prepare("UPDATE admins SET role = ? WHERE id = ?");

    for ($i = 0; $i < count($admin_ids); $i++) {
        $stmt->execute([$roles[$i], $admin_ids[$i]]);
    }

    $stmtActivity = $pdo->prepare("INSERT INTO audits (user_id, roles, activity, activity_date) VALUES (:user_id, :roles, :activity, NOW())");
    $stmtActivity->execute([
        ':user_id' => $_SESSION['user_id'],
        ':roles' => 'superadmin',
        ':activity' => 'Updated admin roles'
    ]);
    header("Location: superadmin.php?updated=1");
    exit;
}

$stmt = $pdo->query("
    SELECT id, full_name AS name, email, 'student' AS role, 'students' AS source FROM students

    UNION ALL

    SELECT id, name, email, role, 'admins' AS source FROM admins WHERE role != 'superadmin'

    UNION ALL

    SELECT id, full_name AS name, email, 'adviser' AS role, 'advisers' AS source FROM advisers

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
        u.name
    FROM audits a
    LEFT JOIN (
        SELECT id, full_name AS name, 'student' AS role FROM students
        UNION ALL
        SELECT id, name, role FROM admins
        UNION ALL
        SELECT id, full_name AS name, role::text AS role FROM advisers
    ) u
        ON a.user_id = u.id
       AND a.roles = u.role
    ORDER BY a.activity_date DESC
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
        }

        .dashboard-container {
            width: 100%;
            /* max-width: 600px; */
            background: #fff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            animation: fadeIn 0.8s ease-in-out;
        }

        .main-heading {
            text-align: center;
            font-size: 28px;
            font-weight: 800;
            color: var(--primary-dark-blue);
            margin-bottom: 25px;
        }

        .sub-text {
            text-align: center;
            color: #777;
            font-size: 14px;
            margin-bottom: 25px;
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

        .btn-find {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-find:hover {

            box-shadow: 0 8px 20px rgba(228, 87, 46, 0.3);
        }

        .top-bar {
            text-align: center;
            margin-bottom: 20px;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            background: var(--primary-dark-blue);
            color: white;
            font-size: 12px;
            border-radius: 20px;
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
            /* width: 50vw; */
            margin-left: 220px;
            min-width: 0;
        }

        /* SECTIONS */
        .section {
            display: none;
            /* width: 50vw; */

        }

        .section.active {
            display: block;
        }

        .sysAdm-section{
            background:#fff;
            border-radius:12px;
            padding:24px;
            width: 100%;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .sysAdm-header h2{
            font-size: 26px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .sysAdm-table{
            width:100%;
            border-collapse:collapse;
            overflow:hidden;
        }

        .sysAdm-table thead{
            background: #eaedef;
        }

        .sysAdm-table th{
            text-align: left;
            padding: 14px 18px;
            font-size: 18px;
            font-weight: 700;
            color: #475569;
            border-bottom: 2px solid #dbe1ea;
        }

        .sysAdm-table td{
            padding:16px 18px;
            font-size:16px;
            color:#334155;
            border-bottom:1px solid #edf2f7;
        }

        .sysAdm-table tbody tr{
            transition:background 0.2s ease;
        }

        .sysAdm-table tbody tr:hover{
            background:#f8fafc;
        }

        .btn-update {
            margin-top: 18px;
            padding: 11px 24px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            border: 1px solid #dbe1ea;
            background: #4f51a8;
            color: #fff;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.15s;
        }

        .btn-update:hover { background: #3A3B7B; }

        .btn-delete {
            display: inline-flex;
            align-items: center;
            border: 1px solid #f7c1c1;
            background: #fcebeb;
            color: #a32d2d;
            cursor: pointer;
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
    </style>
</head>
<script>
    function showSection(event, sectionId) {

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
    function confirmSuperadminGlobal() {
        const roles = document.querySelectorAll("select[name='role[]']");

        let superadminCount = 0;
        let hasChangeToSuperadmin = false;
        let hasRemovalFromSuperadmin = false;

        roles.forEach(select => {
            const original = select.dataset.original;
            const current = select.value;

            if (current === 'superadmin') {
                superadminCount++;
            }

            // ANY upgrade to superadmin
            if (current === 'superadmin' && original !== 'superadmin') {
                hasChangeToSuperadmin = true;
            }

            // ANY downgrade from superadmin
            if (original === 'superadmin' && current !== 'superadmin') {
                hasRemovalFromSuperadmin = true;
            }
        });

        if (superadminCount > 3) {
            alert("Only 3 superadmins are allowed.");
            return false;
        }

        if (hasRemovalFromSuperadmin) {
            return confirm("You are removing a Superadmin. Continue?");
        }

        if (hasChangeToSuperadmin) {
            return confirm("You are assigning a Superadmin. Are you sure?");
        }

        return true;
    }
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
</body>

<body>

    <?php include 'navbar.php'; ?>

    <div class="layout">

        <!-- SIDEBAR -->
        <div class="sidebar" style="">
            <h3>Superadmin</h3>
            <a href="#" onclick="showSection(event, 'dashboard')" class="active"><i class="bi bi-person-fill-lock me-2"></i> Dashboard</a>
            <a href="#" onclick="showSection(event, 'add-admin')"><i class="bi bi-person-plus me-2"></i> Add Admin</a>
            <a href="#" onclick="showSection(event, 'add-adviser')"><i class="bi bi-person-vcard me-2"></i> Add Adviser</a>
            <a href="#" onclick="showSection(event, 'delete')"><i class="bi bi-person-x me-2"></i> Delete Account</a>
            <a href="#" onclick="showSection(event, 'roles')"><i class="bi bi-shuffle me-2"></i> Change Roles</a>
            <a href="#" onclick="showSection(event, 'monitor')"><i class="bi bi-binoculars me-2"></i> Monitor</a>
        </div>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <div id="dashboard" class="section active">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h4 class="fw-bold mb-0" style="color:#272f54;">System Admin Overview</h4>
                        <p class="text-muted small mb-0">Live summary from the internship admin panel</p>
                    </div>
                </div>

                <!-- Stat Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card border-0 rounded-4 h-100" style="background:#272f54;">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <p class="text-white-50 small mb-1 fw-semibold text-uppercase"
                                            style="letter-spacing:.05em; font-size:11px;">Internship Postings</p>
                                        <h2 class="fw-bold text-white mb-0"><?= (int) $totalInternships ?></h2>
                                    </div>
                                    <div class="rounded-3 d-flex align-items-center justify-content-center"
                                        style="width:44px; height:44px; background:rgba(255,255,255,0.1);">
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
                                        <p class="small mb-1 fw-semibold text-uppercase"
                                            style="letter-spacing:.05em; font-size:11px; color:#7a5200;">Students
                                            Interested</p>
                                        <h2 class="fw-bold mb-0" style="color:#3b2600;"><?= (int) $totalInterested ?>
                                        </h2>
                                    </div>
                                    <div class="rounded-3 d-flex align-items-center justify-content-center"
                                        style="width:44px; height:44px; background:rgba(0,0,0,0.1);">
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
                                        <p class="text-white-50 small mb-1 fw-semibold text-uppercase"
                                            style="letter-spacing:.05em; font-size:11px;">Announcements</p>
                                        <h2 class="fw-bold text-white mb-0"><?= (int) $totalAnnouncements ?></h2>
                                    </div>
                                    <div class="rounded-3 d-flex align-items-center justify-content-center"
                                        style="width:44px; height:44px; background:rgba(255,255,255,0.15);">
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
                        
                    </div>
                    <div class="card-body px-4 pb-4 pt-2">
                        <?php if (empty($recentInternships)): ?>
                            <p class="text-muted small mb-0">No internships posted yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table id="table-internships" class="table table-hover align-middle mb-0"
                                    style="font-size:14px;">
                                    <thead>
                                        <tr
                                            style="color:#aaa; font-size:12px; text-transform:uppercase; letter-spacing:.04em;">
                                            <th class="border-0 pb-2 fw-semibold">Title</th>
                                            <th class="border-0 pb-2 fw-semibold">Company</th>
                                            <th class="border-0 pb-2 fw-semibold">Location</th>
                                            <th class="border-0 pb-2 fw-semibold">Posted</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentInternships as $ri): ?>
                                            <tr>
                                                <td class="fw-semibold" style="color:#272f54;">
                                                    <?= htmlspecialchars($ri['title']) ?>
                                                </td>
                                                <td><?= htmlspecialchars($ri['company']) ?></td>
                                                <td class="text-muted">
                                                    <i class="bi bi-geo-alt me-1"></i>
                                                    <?= htmlspecialchars($ri['location']) ?>
                                                </td>
                                                <td>
                                                    <span class="badge rounded-pill px-3"
                                                        style="background:#f0f4ff; color:#272f54; font-weight:500; font-size:12px;">
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
                    <!-- Recently Interested Students -->
                    <div class="col-lg-6">
                        <div class="card border-0 rounded-4 shadow-sm h-100">
                            <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex align-items-center gap-2">
                                <i class="bi bi-people" style="color:#272f54;"></i>
                                <h6 class="fw-bold mb-0" style="color:#272f54;">Recently Interested Students</h6>
                                
                            </div>
                            <div id="table-interested" class="card-body px-4 pb-4 pt-2">
                                <?php if (empty($recentInterested)): ?>
                                    <p class="text-muted small mb-0">No student interest recorded yet.</p>
                                <?php else: ?>
                                    <div class="d-flex flex-column gap-3">
                                        <?php foreach ($recentInterested as $ri): ?>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 fw-bold"
                                                    style="width:38px; height:38px; background:#eef1ff; color:#272f54; font-size:13px;">
                                                    <?= strtoupper(substr($ri['full_name'], 0, 1)) ?>
                                                </div>
                                                <div class="flex-grow-1 overflow-hidden">
                                                    <p class="fw-semibold mb-0 text-truncate"
                                                        style="color:#272f54; font-size:14px;">
                                                        <?= htmlspecialchars($ri['full_name']) ?>
                                                    </p>
                                                    <p class="text-muted mb-0 text-truncate" style="font-size:12px;">
                                                        <?= htmlspecialchars($ri['email']) ?>
                                                    </p>
                                                </div>
                                                <div class="text-end flex-shrink-0">
                                                    <span class="badge rounded-pill px-2"
                                                        style="background:#f5f5f5; color:#555; font-size:11px; font-weight:500;">
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

                    <!-- Recent Announcements -->
                    <div class="col-lg-6">
                        <div class="card border-0 rounded-4 shadow-sm h-100">
                            <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex align-items-center gap-2">
                                <i class="bi bi-bell" style="color:#272f54;"></i>
                                <h6 class="fw-bold mb-0" style="color:#272f54;">Recent Announcements</h6>
                                
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
                                                    style="background:<?= $c['bg'] ?>; color:<?= $c['color'] ?>; font-size:11px; font-weight:600;">
                                                    <?= htmlspecialchars(ucfirst($a['category'])) ?>
                                                </span>
                                                <div class="flex-grow-1 overflow-hidden">
                                                    <p class="fw-semibold mb-0 text-truncate"
                                                        style="color:#272f54; font-size:14px;">
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
                    <div class="d-flex justify-content-end gap-2 mt-4 mb-3">
                        <button onclick="downloadDashboardCSV()" class="btn btn-sm btn-outline-secondary"
                        style="background:#272f54; color:white;">
                            <i class="bi bi-filetype-csv me-1"></i> Download CSV
                        </button>
                        <button onclick="downloadDashboardPDF()" class="btn btn-sm"
                            style="background:#272f54; color:white;">
                            <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
                        </button>
                    </div>
                </div>
                <div id="register-toggle">
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
            <div id="add-admin" class="section sysAdm-section">
                <div class="sysAdm-header">
                    <h2>Create New Admin Account</h2>
                    <p>Admins can be assigned to an internship administration role</p>
                </div>

                <div>
                    <form method="POST" action="superadmin-db.php" class="admin-form">
                        <input type="text" name="name" placeholder="Full Name" required>
                        <input type="email" name="email" placeholder="Email Address" required>
                    <input type="password" name="password" placeholder="Password" required>

                    <select name="role" required>
                        <option value="" disabled selected>Select Role</option>
                        <option value="internship_admin">Internship Admin</option>
                        <!-- <option value="cma">Content Management Admin</option> -->
                    </select>
                    <div style="display:flex; width:100%; justify-content:flex-end; margin-top:12px;">
                        <button type="submit" name="create-admin" class="btn-update">Create Admin</button>
                    </div>
                </form>
                </div>
            </div>
            <!-- Create Adviser Account Section-->
            <div id="add-adviser" class="section sysAdm-section">
                <div class="sysAdm-header">
                    <h2>Create New Adviser Account</h2>
                    <p>Advisers can be assigned to either HTE or Internship advising roles</p>
                </div>
                <div>
                <form method="POST" action="superadmin-db.php" class="admin-form">
                    <input type="text" name="name" placeholder="Full Name" required>
                    <input type="email" name="email" placeholder="Email Address" required>
                    <input type="password" name="password" placeholder="Password" required>

                    <select name="role" id="adviserRole" required>
                        <option value="" disabled selected>Select Role</option>
                        <option value="HTE_adviser">HTE Adviser</option>
                        <option value="internship_adviser">Internship Adviser</option>
                        <!-- <option value="cma">Content Management Admin</option> -->
                    </select>
                    <div id="internshipWrapper" style="display:none;">
                        <select name="internship_id" id="internshipSelect">
                            <option value="" selected>Select Internship</option>

                            <?php foreach ($internships as $internship): ?>
                                <option value="<?= $internship['id'] ?>">
                                    <?= htmlspecialchars($internship['company']) ?> —
                                    <?= htmlspecialchars($internship['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display:flex; width:100%; justify-content:flex-end; margin-top:12px;">
                        <button type="submit" name="create-adviser" class="btn-update">Create Adviser</button>
                    </div>
                </form></div>
            </div>
            <!-- DELETE USER SECTION -->
            <div id="delete" class="section sysAdm-section">
                <div class="sysAdm-header">
                    <h2>Delete user</h2>
                    <p>Permanently remove users from the system</p>
                </div>

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
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <input type="hidden" name="source" value="<?= $u['source'] ?>">
                                        <button type="submit" name="delete" class="btn-delete">
                                            <i class="bi bi-trash-fill"></i> 
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <!-- CHANGE ROLES SECTION -->
            <div id="roles" class="section sysAdm-section">
                <div class="sysAdm-header">
                    <h2>Admin Management</h2>
                    <p>Assign and update roles for admin users</p>
                </div>

                <?php if (isset($_GET['updated'])): ?>
                    <p class="success-box" id="successMsg">
                        <i class="fa fa-circle-check"></i> Role updated successfully!
                    </p>
                <?php endif; ?>

                <form method="POST" onsubmit="return confirmSuperadminGlobal()">
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
                                            <option value="null" disabled <?= $admin['role'] !== 'superadmin' && $admin['role'] !== 'internship_admin' ? 'selected' : '' ?>>None</option>
                                            <option value="superadmin" <?= $admin['role'] == 'superadmin' ? 'selected' : '' ?>>Superadmin</option>
                                            <option value="internship_admin" <?= $admin['role'] == 'internship_admin' ? 'selected' : '' ?>>Internship Admin</option>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                                <div class="text-end">
                    <button type="submit" name="update_role" class="btn-update">
                        <i class="bi bi-floppy2"></i> Update roles
                    </button></div>
                </form>
            </div>

            <!-- MONITOR SECTION -->
            

            <div id="monitor" class="section sysAdm-section">
                <div class="sysAdm-header">
                    <h2>System Monitoring</h2>
                    <p>Recent user activities and actions</p>
                </div>
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
    </div>
    <script>
        setTimeout(() => {
            const msg = document.getElementById("successMsg");
            if (msg) {
                msg.style.transition = "0.5s";
                msg.style.opacity = "0";
                setTimeout(() => msg.remove(), 500);
            }
        }, 2000); // disappears after 3 seconds

        document.addEventListener('DOMContentLoaded', function () {
            const role = document.getElementById('adviserRole');
            const wrapper = document.getElementById('internshipWrapper');
            const internship = document.getElementById('internshipSelect');

            function toggleInternshipDropdown() {
                if (role.value === 'HTE_adviser') {
                    wrapper.style.display = 'block';
                    internship.required = true;
                } else {
                    wrapper.style.display = 'none';
                    internship.required = false;
                    internship.value = '';
                }
            }

            role.addEventListener('change', toggleInternshipDropdown);
            toggleInternshipDropdown();
        });

        function downloadDashboardCSV() {
            let csv = '';

            // --- Internship Postings (HTML table) ---
            csv += 'Recent Internship Postings\n';
            csv += '"Title","Company","Location","Posted"\n';
            document.querySelectorAll('#table-internships tbody tr').forEach(row => {
                const cells = [...row.querySelectorAll('td')].map(td => `"${td.innerText.trim().replace(/\n/g, ' ').replace(/"/g, '""')}"`);
                if (cells.length) csv += cells.join(',') + '\n';
            });

            // --- Interested Students ---
            csv += '\nRecently Interested Students\n';
            csv += '"Name","Email","Company","Date"\n';
            document.querySelectorAll('#table-interested .d-flex.align-items-center').forEach(row => {
                const name = row.querySelector('.fw-semibold')?.innerText.trim() ?? '';
                const email = row.querySelectorAll('p')[1]?.innerText.trim() ?? '';
                const company = row.querySelector('.badge')?.innerText.trim() ?? '';
                const date = row.querySelector('.text-muted.mb-0.mt-1')?.innerText.trim() ?? '';
                csv += `"${name}","${email}","${company}","${date}"\n`;
            });

            // --- Announcements ---
            csv += '\nRecent Announcements\n';
            csv += '"Category","Title","Date"\n';
            document.querySelectorAll('#table-announcements .d-flex.align-items-start').forEach(row => {
                const cells = row.querySelectorAll('p, .badge');
                const category = row.querySelector('.badge')?.innerText.trim() ?? '';
                const title = row.querySelector('.fw-semibold')?.innerText.trim() ?? '';
                const date = row.querySelector('.text-muted')?.innerText.trim() ?? '';
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

            // --- Internship Postings ---
            doc.setFontSize(12);
            doc.setTextColor(0, 0, 0);
            doc.text('Recent Internship Postings', 14, y);
            y += 2;

            const internshipRows = [];
            document.querySelectorAll('#table-internships tbody tr').forEach(row => {
                const cells = [...row.querySelectorAll('td')].map(td => td.innerText.trim().replace(/\n/g, ' '));
                if (cells.length) internshipRows.push(cells);
            });

            doc.autoTable({
                head: [['Title', 'Company', 'Location', 'Posted']],
                body: internshipRows,
                startY: y,
                styles: { fontSize: 10 },
                headStyles: { fillColor: [39, 47, 84], textColor: [255, 255, 255] },
            });
            y = doc.lastAutoTable.finalY + 10;

            // --- Interested Students ---
            doc.text('Recently Interested Students', 14, y);
            y += 2;

            const studentRows = [];
            document.querySelectorAll('#table-interested .d-flex.align-items-center').forEach(row => {
                const name = row.querySelector('.fw-semibold')?.innerText.trim() ?? '';
                const email = row.querySelectorAll('p')[1]?.innerText.trim() ?? '';
                const company = row.querySelector('.badge')?.innerText.trim() ?? '';
                const date = row.querySelector('.text-muted.mb-0.mt-1')?.innerText.trim() ?? '';
                studentRows.push([name, email, company, date]);
            });

            doc.autoTable({
                head: [['Name', 'Email', 'Company', 'Date']],
                body: studentRows,
                startY: y,
                styles: { fontSize: 10 },
                headStyles: { fillColor: [255, 182, 47], textColor: [39, 47, 84] },
            });
            y = doc.lastAutoTable.finalY + 10;

            // --- Announcements ---
            doc.text('Recent Announcements', 14, y);
            y += 2;

            const announcementRows = [];
            document.querySelectorAll('#table-announcements .d-flex.align-items-start').forEach(row => {
                const category = row.querySelector('.badge')?.innerText.trim() ?? '';
                const title = row.querySelector('.fw-semibold')?.innerText.trim() ?? '';
                const date = row.querySelector('.text-muted')?.innerText.trim() ?? '';
                if (title) announcementRows.push([category, title, date]);
            });

            doc.autoTable({
                head: [['Category', 'Title', 'Date']],
                body: announcementRows,
                startY: y,
                styles: { fontSize: 10 },
                headStyles: { fillColor: [228, 87, 46], textColor: [255, 255, 255] },
            });

            doc.save('dashboard_summary.pdf');
        }   
    </script>
</body>

</html>