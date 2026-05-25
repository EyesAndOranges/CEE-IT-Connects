<?php
session_start();
require 'db.php';

// Second code's real queries
$applicantsStmt = $pdo->query("
    SELECT 
        s.id AS student_id,
        s.full_name,
        s.program,
        i.title AS internship_title,
        i.company,
        CASE
            WHEN sp_ojt.is_done = TRUE THEN 'Internship Confirmed'
            WHEN sp_docs.is_done = TRUE THEN 'Documents Submitted'
            WHEN sp_app.is_done = TRUE THEN 'Application Submitted'
            WHEN sd.student_id IS NOT NULL THEN 'Resume Uploaded'
            ELSE 'No Progress'
        END AS current_phase,
        CASE 
            WHEN sd.student_id IS NOT NULL AND sc.student_id IS NOT NULL THEN 'Complete'
            ELSE 'Incomplete'
        END AS requirements,
        ib.created_at
    FROM internship_bookmarks ib
    JOIN students s ON s.id = ib.student_id
    JOIN internships i ON i.id = ib.internship_id
    LEFT JOIN student_documents sd ON sd.student_id = s.id
    LEFT JOIN (SELECT DISTINCT student_id FROM student_credentials) sc ON sc.student_id = s.id
    LEFT JOIN student_progress sp_app ON sp_app.student_id = s.id AND sp_app.step_key = 'application'
    LEFT JOIN student_progress sp_docs ON sp_docs.student_id = s.id AND sp_docs.step_key = 'documents'
    LEFT JOIN student_progress sp_ojt ON sp_ojt.student_id = s.id AND sp_ojt.step_key = 'ojt_accepted'
    ORDER BY ib.created_at DESC
");
$applicants = $applicantsStmt->fetchAll(PDO::FETCH_ASSOC);

$resumeStmt = $pdo->query("
    SELECT sd.id, sd.resume_path, sd.uploaded_at, s.full_name, s.program, s.student_id AS student_number
    FROM student_documents sd
    JOIN students s ON s.id = sd.student_id
    ORDER BY sd.uploaded_at DESC
");
$resumes = $resumeStmt->fetchAll(PDO::FETCH_ASSOC);

$credentialStmt = $pdo->query("
    SELECT sc.id, sc.credential_path, sc.uploaded_at, s.full_name, s.program, s.student_id AS student_number
    FROM student_credentials sc
    JOIN students s ON s.id = sc.student_id
    ORDER BY sc.uploaded_at DESC
");
$credentials = $credentialStmt->fetchAll(PDO::FETCH_ASSOC);

$stmtinterest = $pdo->prepare("
    SELECT ib.id AS interest_id, ib.student_id, ib.created_at,
           s.full_name, s.email, i.company, i.title
    FROM internship_bookmarks ib
    JOIN students s ON s.id = ib.student_id
    JOIN internships i ON i.id = ib.internship_id
    ORDER BY ib.created_at DESC
");
$stmtinterest->execute();
$interests = $stmtinterest->fetchAll(PDO::FETCH_ASSOC);

$stmtannouncement = $pdo->prepare("
    SELECT id, title, message, created_at, category 
    FROM announcements ORDER BY created_at DESC
");
$stmtannouncement->execute();
$announcements = $stmtannouncement->fetchAll(PDO::FETCH_ASSOC);

$internshipStmt = $pdo->query("
    SELECT id, company, title FROM internships ORDER BY company ASC, title ASC
");
$internships = $internshipStmt->fetchAll(PDO::FETCH_ASSOC);

$statsStmt = $pdo->query("SELECT COUNT(*) AS total FROM internships");
$totalInternships = $statsStmt->fetchColumn();

$interestedStmt = $pdo->query("SELECT COUNT(*) AS total FROM internship_bookmarks");
$totalInterested = $interestedStmt->fetchColumn();

$announcementsStmt = $pdo->query("SELECT COUNT(*) AS total FROM announcements");
$totalAnnouncements = $announcementsStmt->fetchColumn();

$recentInternshipsStmt = $pdo->query("
    SELECT title, company, location, created_at FROM internships ORDER BY created_at DESC LIMIT 5
");
$recentInternships = $recentInternshipsStmt->fetchAll(PDO::FETCH_ASSOC);

$recentInterestedStmt = $pdo->query("
    SELECT s.full_name, s.email, i.title AS internship_title, i.company, ib.created_at
    FROM internship_bookmarks ib
    JOIN students s ON s.id = ib.student_id
    JOIN internships i ON i.id = ib.internship_id
    ORDER BY ib.created_at DESC LIMIT 5
");
$recentInterested = $recentInterestedStmt->fetchAll(PDO::FETCH_ASSOC);

$recentAnnouncementsStmt = $pdo->query("
    SELECT title, category, created_at FROM announcements ORDER BY created_at DESC LIMIT 5
");
$recentAnnouncements = $recentAnnouncementsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CEE IT Connects</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="../CSS/intern-admin.css" />

    <style>
        body {
            margin: 0;
            overflow: hidden;
        }

        .main-content {
            margin-left: 220px;
            flex: 1;
            overflow-y: scroll;
            scrollbar-width: none;
            height: calc(100vh - 70px);
            padding: 40px;
            background: #f5f7ff;
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

        .internship-form {
            max-width: 800px;
            margin: auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-card {
            background: #fff;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(67, 67, 67, 0.08);
        }

        .form-card h3 {
            margin-bottom: 15px;
            color: #272f54;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .sidebar {
            width: 220px;
            background: #272f54;
            color: white;
            padding: 20px;
            display: flex;
            flex-direction: column;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 70px;
        }

        .sidebar h3 {
            margin-bottom: 20px;
        }

        .sidebar a {
            text-decoration: none;
            color: white;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 5px;
            transition: 0.3s;
            font-weight: 400;
        }

        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar a.active {
            background: #FFB62F;
            color: #272f54;
            font-weight: 600;
        }

        .sidebar a.active i {
            color: #272f54;
        }

        .btn-button {
            padding: 8px 18px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            background: #4f51a8;
            color: #fff;
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-button:hover {
            background: #3A3B7B;
        }

        .section {
            display: none;
            width: 100%;
        }

        .section.active {
            display: block;
        }

        label {
            font-size: 13px;
            font-weight: 600;
            color: #555;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 14px;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #FFB62F;
            box-shadow: 0 0 5px rgba(255, 182, 47, 0.5);
        }

        .submit-btn {
            background: linear-gradient(135deg, #FFB62F, #E4572E);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            font-size: 16px;
        }

        .submit-btn:hover {
            opacity: 0.9;
        }
    </style>
</head>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function showSection(sectionID) {
        document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
        document.getElementById(sectionID).classList.add('active');
        document.querySelectorAll('.sidebar a').forEach(l => l.classList.remove('active'));
        event.target.classList.add('active');
    }
</script>

<body data-page="rooms">

    <?php include 'navbar.php'; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3"
            style="z-index:9999; min-width:350px;" role="alert" id="flashAlert">
            <i class="bi bi-check-circle-fill me-2"></i><?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['warning'])): ?>
        <div class="alert alert-warning alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3"
            style="z-index:9999; min-width:400px;" role="alert" id="flashAlert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $_SESSION['warning'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['warning']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['info'])): ?>
        <div class="alert alert-info alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3"
            style="z-index:9999; min-width:350px;" role="alert" id="flashAlert">
            <i class="bi bi-info-circle-fill me-2"></i><?= $_SESSION['info'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['info']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3"
            style="z-index:9999; min-width:350px;" role="alert" id="flashAlert">
            <i class="bi bi-x-circle-fill me-2"></i><?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="page-body">
        <!-- SIDEBAR -->
        <aside class="sidebar" style="padding-bottom:70px;">
            <a href="#" class="active" onclick="showSection('dashboard')">
                <i class="bi bi-person-fill-lock"></i> Dashboard
            </a>
            <a href="#" onclick="showSection('postings')">
                <i class="bi bi-pencil-fill"></i> Postings
            </a>
            <a href="#" onclick="showSection('applicants')">
                <i class="bi bi-people-fill"></i> Applicants
            </a>
            <a href="#" onclick="showSection('documents')">
                <i class="bi bi-file-earmark-text-fill"></i> Documents
            </a>
            <a href="#" onclick="showSection('interested')">
                <i class="bi bi-bookmarks-fill"></i> Interested
            </a>
            <a href="#" onclick="showSection('announcements')">
                <i class="bi bi-bell-fill"></i> Announcements
            </a>
            <a href="#" onclick="showSection('manage_announcement')">
                <i class="bi bi-bookmark"></i> Manage Announcement
            </a>
        </aside>

        <div class="main-content">

            <!-- ── DASHBOARD ── -->
            <div id="dashboard" class="section active">
                <div class="sysAdm-header">
                    <h2>Dashboard</h2>
                    <p>A centralized overview of status, pending tasks, and real-time administrative insights.</p>
                </div>

                <!-- SUMMARY CARDS -->
                <div class="summary-container">
                    <div class="summary-card">
                        <div class="card-content">
                            <span class="count"><?= $totalInternships ?></span>
                            <span class="label">Internship Postings</span>
                        </div>
                        <i class="bi bi-briefcase-fill gold-icon"></i>
                    </div>
                    <div class="summary-card">
                        <div class="card-content">
                            <span class="count"><?= $totalInterested ?></span>
                            <span class="label">Interested Students</span>
                        </div>
                        <i class="bi bi-bookmarks-fill gold-icon"></i>
                    </div>
                    <div class="summary-card">
                        <div class="card-content">
                            <span class="count"><?= $totalAnnouncements ?></span>
                            <span class="label">Announcements</span>
                        </div>
                        <i class="bi bi-bell-fill gold-icon"></i>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Application List -->
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-text" style="color:#272f54;"></i>
                                <h6 class="fw-bold mb-0" style="color:#272f54;">Application List</h6>
                            </div>
                            <div class="card-body px-4 pb-4 pt-2">
                                <?php if (empty($recentInterested)): ?>
                                    <p class="text-muted small mb-0">No applications yet.</p>
                                <?php else: ?>
                                    <table style="width:100%; border-collapse:collapse; font-size:13px;">
                                        <thead>
                                            <tr
                                                style="color:#aaa; font-size:12px; text-transform:uppercase; letter-spacing:.04em;">
                                                <th
                                                    style="padding:8px 10px; border-bottom:1px solid #f0f2f7; font-weight:600;">
                                                    Student</th>
                                                <th
                                                    style="padding:8px 10px; border-bottom:1px solid #f0f2f7; font-weight:600;">
                                                    Company</th>
                                                <th
                                                    style="padding:8px 10px; border-bottom:1px solid #f0f2f7; font-weight:600;">
                                                    Status</th>
                                                <th
                                                    style="padding:8px 10px; border-bottom:1px solid #f0f2f7; font-weight:600;">
                                                    Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentInterested as $ri): ?>
                                                <tr>
                                                    <td style="padding:10px; border-bottom:1px solid #f0f2f7;">
                                                        <div style="display:flex; align-items:center; gap:8px;">
                                                            <div
                                                                style="width:30px;height:30px;min-width:30px;border-radius:50%;background:#eef1ff;color:#272f54;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;">
                                                                <?= strtoupper(substr($ri['full_name'], 0, 1)) ?>
                                                            </div>
                                                            <span
                                                                style="font-weight:500;color:#272f54;"><?= htmlspecialchars($ri['full_name']) ?></span>
                                                        </div>
                                                    </td>
                                                    <td
                                                        style="padding:10px; border-bottom:1px solid #f0f2f7; color:#888; font-size:12px;">
                                                        <?= htmlspecialchars($ri['company']) ?>
                                                    </td>
                                                    <td style="padding:10px; border-bottom:1px solid #f0f2f7;">
                                                        <span
                                                            style="background:#fff8e1;color:#633806;font-size:11px;padding:3px 10px;border-radius:6px;font-weight:500;">Interested</span>
                                                    </td>
                                                    <td
                                                        style="padding:10px; border-bottom:1px solid #f0f2f7; color:#aaa; font-size:12px;">
                                                        <?= date("M d", strtotime($ri['created_at'])) ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Announcements -->
                    <div class="col-lg-5">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex align-items-center gap-2">
                                <i class="bi bi-bell" style="color:#272f54;"></i>
                                <h6 class="fw-bold mb-0" style="color:#272f54;">Recent Announcements</h6>
                            </div>
                            <div class="card-body px-4 pb-4 pt-2">
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
                                                <span
                                                    style="background:<?= $c['bg'] ?>;color:<?= $c['color'] ?>;font-size:11px;padding:3px 10px;border-radius:6px;font-weight:600;white-space:nowrap;">
                                                    <?= htmlspecialchars(ucfirst($a['category'])) ?>
                                                </span>
                                                <div style="flex:1;min-width:0;">
                                                    <p
                                                        style="font-weight:600;margin:0;color:#272f54;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                                        <?= htmlspecialchars($a['title']) ?>
                                                    </p>
                                                    <p style="color:#aaa;margin:0;font-size:11px;">
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

                    <!-- Recent Internship Postings -->
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex align-items-center gap-2">
                                <i class="bi bi-briefcase" style="color:#272f54;"></i>
                                <h6 class="fw-bold mb-0" style="color:#272f54;">Recent Internship Postings</h6>
                            </div>
                            <div class="card-body px-4 pb-4 pt-2">
                                <?php if (empty($recentInternships)): ?>
                                    <p class="text-muted small mb-0">No internships posted yet.</p>
                                <?php else: ?>
                                    <table style="width:100%; border-collapse:collapse; font-size:13px;">
                                        <thead>
                                            <tr
                                                style="color:#aaa; font-size:12px; text-transform:uppercase; letter-spacing:.04em;">
                                                <th
                                                    style="padding:8px 10px; border-bottom:1px solid #f0f2f7; font-weight:600;">
                                                    Title</th>
                                                <th
                                                    style="padding:8px 10px; border-bottom:1px solid #f0f2f7; font-weight:600;">
                                                    Company</th>
                                                <th
                                                    style="padding:8px 10px; border-bottom:1px solid #f0f2f7; font-weight:600;">
                                                    Location</th>
                                                <th
                                                    style="padding:8px 10px; border-bottom:1px solid #f0f2f7; font-weight:600;">
                                                    Posted</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentInternships as $ri): ?>
                                                <tr>
                                                    <td
                                                        style="padding:10px; border-bottom:1px solid #f0f2f7; font-weight:500; color:#272f54;">
                                                        <?= htmlspecialchars($ri['title']) ?>
                                                    </td>
                                                    <td
                                                        style="padding:10px; border-bottom:1px solid #f0f2f7; color:#888; font-size:12px;">
                                                        <?= htmlspecialchars($ri['company']) ?>
                                                    </td>
                                                    <td
                                                        style="padding:10px; border-bottom:1px solid #f0f2f7; color:#888; font-size:12px;">
                                                        <i
                                                            class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($ri['location']) ?>
                                                    </td>
                                                    <td style="padding:10px; border-bottom:1px solid #f0f2f7;">
                                                        <span
                                                            style="background:#f0f4ff;color:#272f54;font-size:11px;padding:3px 10px;border-radius:6px;font-weight:500;">
                                                            <?= date("M d, Y", strtotime($ri['created_at'])) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recently Uploaded Documents (static placeholder from first code) -->
                    <div class="col-lg-5">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-arrow-up" style="color:#272f54;"></i>
                                <h6 class="fw-bold mb-0" style="color:#272f54;">Recently Uploaded Documents</h6>
                            </div>
                            <div class="card-body px-4 pb-4 pt-2">
                                <p class="text-muted small mb-3">Latest student document submissions.</p>
                                <div class="d-flex flex-column gap-3">
                                    <?php
                                    $allDocs = [];
                                    foreach ($resumes as $r) {
                                        $allDocs[] = [
                                            'name' => $r['full_name'],
                                            'type' => 'Resume',
                                            'date' => $r['uploaded_at'],
                                            'bg' => '#EAF3DE',
                                            'color' => '#27500A'
                                        ];
                                    }
                                    foreach ($credentials as $c) {
                                        $allDocs[] = [
                                            'name' => $c['full_name'],
                                            'type' => 'Credential',
                                            'date' => $c['uploaded_at'],
                                            'bg' => '#E6F1FB',
                                            'color' => '#0C447C'
                                        ];
                                    }
                                    usort($allDocs, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
                                    $recentDocs = array_slice($allDocs, 0, 3);
                                    ?>
                                    <?php if (empty($recentDocs)): ?>
                                        <p class="text-muted small">No documents uploaded yet.</p>
                                    <?php else: ?>
                                        <?php foreach ($recentDocs as $doc): ?>
                                            <div
                                                style="display:flex;align-items:center;gap:10px;padding-bottom:12px;border-bottom:1px solid #f0f2f7;">
                                                <div
                                                    style="width:32px;height:32px;min-width:32px;border-radius:50%;background:<?= $doc['bg'] ?>;color:<?= $doc['color'] ?>;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;">
                                                    <?= strtoupper(substr($doc['name'], 0, 1)) ?>
                                                </div>
                                                <div style="flex:1;min-width:0;">
                                                    <p style="margin:0;font-size:13px;font-weight:600;color:#272f54;">
                                                        <?= htmlspecialchars($doc['name']) ?>
                                                    </p>
                                                    <p style="margin:0;font-size:11px;color:#888;"><?= $doc['type'] ?></p>
                                                </div>
                                                <span
                                                    style="font-size:11px;color:#aaa;white-space:nowrap;"><?= date("M d", strtotime($doc['date'])) ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── POSTINGS ── -->
            <div id="postings" class="section">
                <div class="sysAdm-header">
                    <h2>Internship Postings</h2>
                    <p>The administrative module for publishing, modifying, and monitoring active internship listings.
                    </p>
                </div>

                <div class="table-controls">
                    <div class="filters">
                        <select class="filter-select" id="postings-program-filter" onchange="filterPostings()">
                            <option value="All">Program</option>
                            <option value="Information Technology">IT</option>
                            <option value="Civil Engineering">CE</option>
                            <option value="Electrical Engineering">EE</option>
                        </select>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div class="search-box">
                            <input type="text" id="search-postings" placeholder="Search by company or title..."
                                oninput="filterPostings()">
                            <i class="bi bi-search"></i>
                        </div>
                        <button class="btn-button" onclick="showPostingForm()">
                            <i class="bi bi-plus-circle me-1"></i> Add Internship Post
                        </button>
                    </div>
                </div>

                <div class="table-container">
                    <table class="custom-table" id="postings-table">
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Job Title</th>
                                <th>Program</th>
                                <th>Location</th>
                                <th style="text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="postings-tbody">
                            <?php foreach ($internships as $p): ?>
                                <tr data-program="<?= htmlspecialchars($p['company']) ?>">
                                    <td style="padding:14px 15px;"><?= htmlspecialchars($p['company']) ?></td>
                                    <td style="padding:14px 15px;"><?= htmlspecialchars($p['title']) ?></td>
                                    <td style="padding:14px 15px;">—</td>
                                    <td style="padding:14px 15px;">—</td>
                                    <td style="text-align:center;">
                                        <button class="btn btn-sm btn-danger" title="Delete" onclick="deleteRow(this)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- ADD FORM -->
                <div id="posting-form-panel" style="display:none; margin-top:20px;">
                    <div class="form-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 style="margin:0;">New Internship Posting</h3>
                            <button type="button" onclick="hidePostingForm()"
                                style="background:none;border:none;font-size:20px;cursor:pointer;color:#888;">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <form method="POST" action="internship-db.php"
                            onsubmit="return confirm('Create this internship posting?');">
                            <input type="hidden" name="form_type" value="internship_posting">
                            <div class="form-grid">
                                <div><label>Title</label><input type="text" name="title" placeholder="Job Title"
                                        required></div>
                                <div><label>Company</label><input type="text" name="company" placeholder="Company Name"
                                        required></div>
                                <div><label>Location</label><input type="text" name="location" placeholder="Location"
                                        required></div>
                                <div>
                                    <label>Program</label>
                                    <select name="program" required>
                                        <option value="" disabled selected>Select Program</option>
                                        <option value="Information Technology">IT</option>
                                        <option value="Civil Engineering">CE</option>
                                        <option value="Electrical Engineering">EE</option>
                                        <option value="Information Technology, Civil Engineering">IT, CE</option>
                                        <option value="Information Technology, Electrical Engineering">IT, EE</option>
                                        <option value="Civil Engineering, Electrical Engineering">CE, EE</option>
                                        <option
                                            value="Information Technology, Civil Engineering, Electrical Engineering">
                                            IT, CE, EE</option>
                                    </select>
                                </div>
                                <div><label>Deadline</label><input type="date" name="deadline"></div>
                                <div><label>Contact Email</label><input type="email" name="email"
                                        placeholder="Contact Email"></div>
                                <div><label>Contact Number</label><input type="tel" name="phonenumber"
                                        placeholder="Contact Number"></div>
                                <div>
                                    <label>Contract Duration</label>
                                    <select name="year">
                                        <option value="" disabled selected>Select Duration</option>
                                        <option value="1">1 year</option>
                                        <option value="2">2 years</option>
                                        <option value="3">3 years</option>
                                        <option value="4">4 years</option>
                                        <option value="5">5 years</option>
                                    </select>
                                </div>
                                <div style="grid-column:span 2;">
                                    <label>Description</label>
                                    <textarea name="description" placeholder="Description" required></textarea>
                                </div>
                                <div><label>Opening Time</label><input type="time" name="openTime"></div>
                                <div><label>Closing Time</label><input type="time" name="closeTime"></div>
                            </div>

                            <!-- Map Pin -->
                            <div class="mt-3">
                                <label>Pin Location on Map</label>
                                <p class="text-muted" style="font-size:13px;">Click on the map to pin the internship
                                    location.</p>
                                <div id="posting-map"
                                    style="width:100%;height:350px;border-radius:10px;border:1px solid #dee2e6;"></div>
                                <div class="row g-3 mt-2">
                                    <div class="col-md-6">
                                        <label>Latitude</label>
                                        <input type="text" name="latitude" id="post-lat" placeholder="Click map to set"
                                            readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Longitude</label>
                                        <input type="text" name="longitude" id="post-lng" placeholder="Click map to set"
                                            readonly>
                                    </div>
                                </div>
                                <div id="pin-label" class="d-none mt-2">
                                    <span class="p-1 rounded text-bg-success">
                                        <i class="bi bi-geo-alt-fill"></i> Location pinned — drag or click to adjust
                                    </span>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-3">
                                <button type="button" onclick="hidePostingForm()"
                                    style="background:#888;color:white;border:none;padding:11px 24px;border-radius:8px;font-weight:600;cursor:pointer;">
                                    Cancel
                                </button>
                                <button type="submit" class="submit-btn" style="width:auto;padding:11px 24px;">
                                    Create Posting
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ── APPLICANTS ── -->
            <div id="applicants" class="section sysAdm-header">
                <h2>Applicants</h2>
                <p>A place to review student credentials and track candidate progress through the hiring pipeline.</p>

                <div class="table-controls">
                    <div class="filters">
                        <select class="filter-select" id="app-phase-filter" onchange="filterApplicants()">
                            <option value="all">Status</option>
                            <option value="Internship Confirmed">Internship Confirmed</option>
                            <option value="Documents Submitted">Documents Submitted</option>
                            <option value="Application Submitted">Application Submitted</option>
                            <option value="Resume Uploaded">Resume Uploaded</option>
                            <option value="No Progress">No Progress</option>
                        </select>
                        <select class="filter-select" id="app-req-filter" onchange="filterApplicants()">
                            <option value="all">Requirements</option>
                            <option value="Complete">Complete</option>
                            <option value="Incomplete">Incomplete</option>
                        </select>
                        <select class="filter-select" id="app-program-filter" onchange="filterApplicants()">
                            <option value="all">Programs</option>
                            <option value="Information Technology">Information Technology</option>
                            <option value="Civil Engineering">Civil Engineering</option>
                            <option value="Electrical Engineering">Electrical Engineering</option>
                        </select>
                    </div>
                    <div class="search-box">
                        <input type="text" id="search-applicants" oninput="filterApplicants()" placeholder="Search">
                        <i class="bi bi-search"></i>
                    </div>
                </div>

                <div class="table-container">
                    <table class="custom-table" id="applicants-table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Program</th>
                                <th>Internship</th>
                                <th>Company</th>
                                <th>Phase</th>
                                <th>Requirements</th>
                            </tr>
                        </thead>
                        <tbody id="applicants-tbody">
                            <?php if (empty($applicants)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No applicants yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($applicants as $a):
                                    $phaseColors = [
                                        'Internship Confirmed' => ['bg' => '#d1fae5', 'color' => '#065f46'],
                                        'Documents Submitted' => ['bg' => '#dbeafe', 'color' => '#1e40af'],
                                        'Application Submitted' => ['bg' => '#fef9c3', 'color' => '#854d0e'],
                                        'Resume Uploaded' => ['bg' => '#fce7f3', 'color' => '#9d174d'],
                                        'No Progress' => ['bg' => '#f3f4f6', 'color' => '#6b7280'],
                                    ];
                                    $pc = $phaseColors[$a['current_phase']] ?? ['bg' => '#f3f4f6', 'color' => '#6b7280'];
                                    ?>
                                    <tr data-name="<?= strtolower(htmlspecialchars($a['full_name'])) ?>"
                                        data-program="<?= htmlspecialchars($a['program']) ?>"
                                        data-phase="<?= htmlspecialchars($a['current_phase']) ?>"
                                        data-req="<?= htmlspecialchars($a['requirements']) ?>">
                                        <td><?= htmlspecialchars($a['full_name']) ?></td>
                                        <td><?= htmlspecialchars($a['program']) ?></td>
                                        <td><?= htmlspecialchars($a['internship_title']) ?></td>
                                        <td><?= htmlspecialchars($a['company']) ?></td>
                                        <td>
                                            <span
                                                style="background:<?= $pc['bg'] ?>;color:<?= $pc['color'] ?>;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:600;">
                                                <?= htmlspecialchars($a['current_phase']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span
                                                style="color:<?= $a['requirements'] === 'Complete' ? '#16a34a' : '#dc2626' ?>;font-weight:600;font-size:13px;">
                                                <?= htmlspecialchars($a['requirements']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ── DOCUMENTS ── -->
            <div id="documents" class="section sysAdm-header">
                <h2>Documents</h2>
                <p>A secure repository for managing, verifying, and storing mandatory internship documentation.</p>

                <div class="table-controls">
                    <div class="filters">
                        <select class="filter-select" id="doc-type-filter" onchange="filterDocs()">
                            <option value="all">Document Type</option>
                            <option value="resume">Resume</option>
                            <option value="credential">Credential</option>
                        </select>
                        <select class="filter-select" id="doc-program-filter" onchange="filterDocs()">
                            <option value="programs">Programs</option>
                            <option value="Information Technology">Information Technology</option>
                            <option value="Civil Engineering">Civil Engineering</option>
                            <option value="Electrical Engineering">Electrical Engineering</option>
                        </select>
                    </div>
                    <div class="search-box">
                        <input type="text" id="search-documents" oninput="filterDocs()"
                            placeholder="Search by student name...">
                        <i class="bi bi-search"></i>
                    </div>
                </div>

                <div class="table-container">
                    <table class="custom-table" id="documents-table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Student No.</th>
                                <th>Program</th>
                                <th>Document Type</th>
                                <th>Submission Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resumes as $doc): ?>
                                <tr data-type="resume" data-name="<?= strtolower(htmlspecialchars($doc['full_name'])) ?>"
                                    data-program="<?= strtolower(htmlspecialchars($doc['program'])) ?>">
                                    <td><?= htmlspecialchars($doc['full_name']) ?></td>
                                    <td><?= htmlspecialchars($doc['student_number']) ?></td>
                                    <td><?= htmlspecialchars($doc['program']) ?></td>
                                    <td><span>Resume</span></td>
                                    <td><?= date("M d, Y", strtotime($doc['uploaded_at'])) ?></td>
                                    <td style="text-align:center;">
                                        <a href="../uploads/resumes/<?= htmlspecialchars($doc['resume_path']) ?>"
                                            target="_blank" class="view-btn">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php foreach ($credentials as $doc): ?>
                                <tr data-type="credential"
                                    data-name="<?= strtolower(htmlspecialchars($doc['full_name'])) ?>"
                                    data-program="<?= strtolower(htmlspecialchars($doc['program'])) ?>">
                                    <td><?= htmlspecialchars($doc['full_name']) ?></td>
                                    <td><?= htmlspecialchars($doc['student_number']) ?></td>
                                    <td><?= htmlspecialchars($doc['program']) ?></td>
                                    <td><span>Credentials</span></td>
                                    <td><?= date("M d, Y", strtotime($doc['uploaded_at'])) ?></td>
                                    <td style="text-align:center;">
                                        <a href="../uploads/credentials/<?= htmlspecialchars($doc['credential_path']) ?>"
                                            target="_blank" class="view-btn">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ── INTERESTED ── -->
            <div id="interested" class="section sysAdm-header">
                <h2>Interested</h2>
                <p>A tracking section for monitoring student engagement and preliminary interest in companies and
                    internships.</p>

                <div class="table-controls">
                    <div class="filters"></div>
                    <div class="search-box">
                        <input type="text" id="search-interested" placeholder="Search by name, email, company...">
                        <i class="bi bi-search"></i>
                    </div>
                </div>

                <div class="table-container">
                    <table class="custom-table" id="interested-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Email</th>
                                <th>Company</th>
                                <th>Internship</th>
                                <th style="text-align:center; width:180px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="interested-tbody">
                            <?php foreach ($interests as $i): ?>
                                <tr>
                                    <td style="padding:14px 15px;"><?= htmlspecialchars($i['full_name']) ?></td>
                                    <td style="padding:14px 15px;"><?= htmlspecialchars($i['email']) ?></td>
                                    <td style="padding:14px 15px;"><?= htmlspecialchars($i['company']) ?></td>
                                    <td style="padding:14px 15px;"><?= htmlspecialchars($i['title']) ?></td>
                                    <td style="text-align:center;">
                                        <button type="button" class="btn btn-sm btn-danger"
                                            onclick="openFeedbackModal(<?= $i['interest_id'] ?>)"
                                            style="padding:4px 8px;border-radius:16px;font-size:12px;">
                                            Return with Feedback
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ── ANNOUNCEMENTS ── -->
            <div id="announcements" class="section sysAdm-header">
                <h2>Post Announcements</h2>
                <p>Start a new post to display official institutional updates and information to users.</p>

                <form action="internship-db.php" method="POST" class="internship-form">
                    <input type="hidden" name="form_type" value="announcement_posting">
                    <div class="form-card">
                        <h3>Announcement Details</h3>
                        <div class="mb-3">
                            <input type="text" name="title" placeholder="Title" required>
                        </div>
                        <div class="mb-3">
                            <textarea name="message" placeholder="Message" required></textarea>
                        </div>
                        <div class="mb-3">
                            <select name="category">
                                <option value="" disabled selected>Select Category</option>
                                <option value="news">News</option>
                                <option value="updates">Updates</option>
                                <option value="FAQs">FAQs</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="submit-btn">Post Announcement</button>
                </form>
            </div>

            <!-- ── MANAGE ANNOUNCEMENTS ── -->
            <div id="manage_announcement" class="section sysAdm-header">
                <h2>Manage Announcements</h2>
                <p>The content management utility for drafting, scheduling, and distributing official notifications.</p>

                <div class="table-controls">
                    <div class="filters">
                        <select class="filter-select" id="category-filter" onchange="filterAnnouncements()">
                            <option value="">All Categories</option>
                            <option value="news">News</option>
                            <option value="updates">Updates</option>
                            <option value="FAQs">FAQs</option>
                        </select>
                    </div>
                    <div class="search-box">
                        <input type="text" id="search-announcements" placeholder="Search announcements..."
                            oninput="filterAnnouncements()">
                        <i class="bi bi-search"></i>
                    </div>
                </div>

                <div class="table-container">
                    <table class="custom-table" id="manage-announcements-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Message</th>
                                <th>Category</th>
                                <th>Date</th>
                                <th style="text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="manage-announcements-tbody">
                            <?php if (empty($announcements)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">No announcements yet.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($announcements as $a): ?>
                                <tr data-category="<?= strtolower($a['category']) ?>">
                                    <form method="POST" action="internship-db.php">
                                        <td class="fw-bold" style="border:none;padding:14px 15px;">
                                            <input type="text" name="title" value="<?= htmlspecialchars($a['title']) ?>"
                                                required>
                                        </td>
                                        <td>
                                            <input type="text" name="message" value="<?= htmlspecialchars($a['message']) ?>"
                                                required>
                                        </td>
                                        <td>
                                            <select name="category" required>
                                                <option value="news" <?= $a['category'] === 'news' ? 'selected' : '' ?>>News
                                                </option>
                                                <option value="updates" <?= $a['category'] === 'updates' ? 'selected' : '' ?>>
                                                    Updates</option>
                                                <option value="FAQs" <?= $a['category'] === 'FAQs' ? 'selected' : '' ?>>FAQs
                                                </option>
                                            </select>
                                        </td>
                                        <td><?= date("M d, Y", strtotime($a['created_at'])) ?></td>
                                        <td class="text-center">
                                            <input type="hidden" name="announcement_id" value="<?= $a['id'] ?>">
                                            <button type="submit" name="edit_announcement" class="btn btn-sm btn-primary">
                                                <i class="bi bi-floppy2"></i>
                                            </button>
                                            <button type="submit" name="delete_announcement" class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </form>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div><!-- /.main-content -->
    </div><!-- /.page-body -->


    <!-- FEEDBACK MODAL -->
    <div id="feedbackModal"
        style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;justify-content:center;align-items:center;">
        <div style="background:#29335C;border-radius:5px;padding:30px;width:500px;max-width:90%;">
            <h5 style="color:white;text-align:center;margin-bottom:0;font-weight:400;">Return with Feedback</h5>
            <textarea id="feedbackText" placeholder="Enter feedback here.."
                style="width:100%;height:130px;border-radius:5px;border:none;padding:14px;font-size:14px;resize:none;outline:none;margin-top:15px;"></textarea>
            <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:20px;">
                <button onclick="closeFeedbackModal()"
                    style="background:transparent;color:white;border:1px solid white;padding:8px 20px;border-radius:20px;cursor:pointer;font-size:14px;">
                    Cancel
                </button>
                <button onclick="sendFeedback()"
                    style="background:white;color:#29335C;border:none;padding:8px 20px;border-radius:20px;cursor:pointer;font-size:14px;font-weight:600;">
                    Send Feedback
                </button>
            </div>
        </div>
    </div>

    <script>
        // Flash alert auto-dismiss
        setTimeout(() => {
            const alert = document.getElementById('flashAlert');
            if (alert) { alert.classList.remove('show'); setTimeout(() => alert.remove(), 300); }
        }, 3000);

        // CSV add row
        function addRow() {
            const tbody = document.getElementById('csv-tbody');
            const colCount = parseInt(document.getElementById('col-count').value);
            const rowCount = parseInt(document.getElementById('row-count').value);
            document.getElementById('row-count').value = rowCount + 1;
            const tr = document.createElement('tr');
            for (let col = 0; col < colCount; col++) {
                const td = document.createElement('td');
                const input = document.createElement('input');
                input.type = 'text';
                input.name = `csv[${rowCount}][${col}]`;
                input.className = 'form-control';
                input.placeholder = '—';
                td.appendChild(input);
                tr.appendChild(td);
            }
            tbody.appendChild(tr);
            tr.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        // Feedback modal
        function openFeedbackModal(bookmarkId) {
            document.getElementById('feedbackModal').dataset.id = bookmarkId;
            document.getElementById('feedbackModal').style.display = 'flex';
        }
        function closeFeedbackModal() {
            document.getElementById('feedbackModal').style.display = 'none';
            document.getElementById('feedbackModal').dataset.id = null;
        }
        function sendFeedback() {
            const modal = document.getElementById('feedbackModal');
            const bookmarkId = modal.dataset.id;
            const feedback = document.getElementById('feedbackText').value.trim();
            if (!bookmarkId) { alert("No bookmark selected."); return; }
            if (!feedback) { alert('Please enter feedback before sending.'); return; }

            const formData = new FormData();
            formData.append('bookmark_id', bookmarkId);
            formData.append('feedback', feedback);
            formData.append('send_feedback', 1);
            formData.append('form_type', 'send_feedback');

            fetch('internship-db.php', { method: 'POST', body: formData })
                .then(res => res.text())
                .then(text => {
                    if (text.trim() !== "success") { alert(text); return; }
                    closeFeedbackModal();
                    location.reload();
                })
                .catch(err => { console.error(err); alert('Something went wrong.'); });
        }
        document.getElementById('feedbackModal').addEventListener('click', function (e) {
            if (e.target === this) closeFeedbackModal();
        });

        // Interested search
        document.getElementById('search-interested').addEventListener('input', function () {
            const q = this.value.toLowerCase();
            document.querySelectorAll('#interested-tbody tr').forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(q) ? '' : 'none';
            });
        });

        // Announcements filter
        function filterAnnouncements() {
            const search = document.getElementById('search-announcements').value.toLowerCase();
            const category = document.getElementById('category-filter').value.toLowerCase();
            document.querySelectorAll('#manage-announcements-tbody tr').forEach(row => {
                const rowCat = (row.dataset.category ?? '').toLowerCase();
                const text = Array.from(row.querySelectorAll('input, select, td'))
                    .map(el => el.tagName === 'INPUT' || el.tagName === 'SELECT' ? el.value : el.innerText)
                    .join(' ').toLowerCase();
                row.style.display = (text.includes(search) && (category === '' || rowCat === category)) ? '' : 'none';
            });
        }

        // Documents filter
        function filterDocs() {
            const search = document.getElementById('search-documents').value.toLowerCase();
            const type = document.getElementById('doc-type-filter').value.toLowerCase();
            const program = document.getElementById('doc-program-filter').value.toLowerCase();
            document.querySelectorAll('#documents-table tbody tr').forEach(row => {
                const nameMatch = row.dataset.name?.includes(search) ?? true;
                const typeMatch = type === 'all' || row.dataset.type === type;
                const progMatch = program === 'programs' || (row.dataset.program ?? '').toLowerCase() === program;
                row.style.display = (nameMatch && typeMatch && progMatch) ? '' : 'none';
            });
        }

        // Applicants filter
        function filterApplicants() {
            const search = document.getElementById('search-applicants').value.toLowerCase();
            const phase = document.getElementById('app-phase-filter').value;
            const req = document.getElementById('app-req-filter').value;
            const program = document.getElementById('app-program-filter').value;
            document.querySelectorAll('#applicants-tbody tr').forEach(row => {
                const nameMatch = row.dataset.name?.includes(search) ?? true;
                const phaseMatch = phase === 'all' || row.dataset.phase === phase;
                const reqMatch = req === 'all' || row.dataset.req === req;
                const programMatch = program === 'all' || row.dataset.program === program;
                row.style.display = (nameMatch && phaseMatch && reqMatch && programMatch) ? '' : 'none';
            });
        }

        // Postings
        function showPostingForm() {
            document.getElementById('posting-form-panel').style.display = 'block';
            document.getElementById('posting-form-panel').scrollIntoView({ behavior: 'smooth' });
        }
        function hidePostingForm() {
            document.getElementById('posting-form-panel').style.display = 'none';
        }
        function filterPostings() {
            const program = document.getElementById('postings-program-filter').value;
            const search = document.getElementById('search-postings').value.toLowerCase();
            document.querySelectorAll('#postings-tbody tr').forEach(row => {
                const rowProgram = row.dataset.program ?? '';
                const rowText = row.innerText.toLowerCase();
                const matchP = program === 'All' || rowProgram.includes(program);
                const matchS = search === '' || rowText.includes(search);
                row.style.display = (matchP && matchS) ? '' : 'none';
            });
        }
        function deleteRow(btn) {
            if (!confirm('Delete this internship posting?')) return;
            btn.closest('tr').remove();
        }

        // Map
        let postingMap, postingMarker;
        function initPostingMap() {
            postingMap = new google.maps.Map(document.getElementById('posting-map'), {
                zoom: 12,
                center: { lat: 14.7011, lng: 120.9830 }
            });
            postingMap.addListener('click', function (e) {
                const lat = e.latLng.lat();
                const lng = e.latLng.lng();
                document.getElementById('post-lat').value = lat.toFixed(7);
                document.getElementById('post-lng').value = lng.toFixed(7);
                document.getElementById('pin-label').classList.remove('d-none');
                if (postingMarker) {
                    postingMarker.setPosition(e.latLng);
                } else {
                    postingMarker = new google.maps.Marker({
                        position: e.latLng, map: postingMap,
                        title: 'Internship Location', draggable: true
                    });
                    postingMarker.addListener('dragend', function () {
                        const pos = postingMarker.getPosition();
                        document.getElementById('post-lat').value = pos.lat().toFixed(7);
                        document.getElementById('post-lng').value = pos.lng().toFixed(7);
                    });
                }
            });
        }
    </script>

    <script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDITrnTUmS0AwxqZCE8cfYI3d5kjtzg7RY&callback=initPostingMap"
        async defer></script>
    <script src="../JS/script.js"></script>

</body>

</html>