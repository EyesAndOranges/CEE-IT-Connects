<?php
session_start();
require 'db.php';
$stmtinterest = $pdo->prepare("
    SELECT 
        ib.id AS interest_id,
        ib.student_id,
        ib.created_at,
        s.full_name,
        s.email,
        i.company,
        i.title
    FROM internship_bookmarks ib
    JOIN students s ON s.id = ib.student_id
    JOIN internships i ON i.id = ib.internship_id
    ORDER BY ib.created_at DESC
");

$stmtinterest->execute();
$interests = $stmtinterest->fetchAll(PDO::FETCH_ASSOC);

$stmtannouncement = $pdo->prepare("
SELECT id, title, message, created_at, category 
FROM announcements
ORDER BY created_at DESC");
$stmtannouncement->execute();
$announcements = $stmtannouncement->fetchAll(PDO::FETCH_ASSOC);

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
            /* width: 220px;
            background: #272f54;
            color: white;
            padding: 20px;
            display: flex;
            flex-direction: column; */

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
            gap: 6px;
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

        /* .section-header {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
        } */

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

        /* FOCUS EFFECT */
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #FFB62F;
            box-shadow: 0 0 5px rgba(255, 182, 47, 0.5);
        }

        /* BUTTON */
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

        @media (max-width: 768px) {
            body { overflow: auto !important; }

            .page-body { display: flex !important; }

            aside.sidebar, .sidebar {
                width: 60px !important;
                padding: 10px 0 70px 0 !important;
                overflow: visible !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                z-index: 1050 !important;
                position: fixed !important;
                top: 70px !important;
                left: 0 !important;
                height: calc(100vh - 70px) !important;
            }

            aside.sidebar h3 { display: none !important; }

            .sidebar a {
                width: 44px !important;
                height: 44px !important;
                min-width: 44px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                border-radius: 12px !important;
                margin: 0 0 8px 0 !important;
                padding: 0 !important;
                position: relative;
                gap: 0 !important;
                font-size: 0 !important;
                color: white !important;
            }

            .sidebar a:hover { 
                background: rgba(255, 255, 255, 0.1) !important; 
                color: white !important; 
            }

            .sidebar a:hover i { 
                color: #E4572E !important; 
            }

            .sidebar a.active {
                background: #FFB62F !important;
                color: #272f54 !important;
            }

            .sidebar a i {
                font-size: 18px !important;
                line-height: 1 !important;
                margin: 0 !important;
                color: inherit !important;
                display: block !important;
                opacity: 1 !important;
            }

            .sidebar a.active i {
                color: var(--accent) !important;
            }

            /* Tooltip */
            .sidebar a::after {
                content: attr(data-tooltip);
                position: absolute;
                left: 54px;
                top: 50%;
                transform: translateY(-50%);
                background: #1a1a2e;
                color: #fff !important;
                font-size: 12px !important;
                font-weight: 500;
                padding: 5px 10px;
                border-radius: 6px;
                white-space: nowrap;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.2s ease;
                z-index: 99999 !important;
            }

            aside.sidebar a:hover::after { opacity: 1; }

            .main-content {
                margin-left: 60px !important;
                padding: 15px !important;
                height: auto !important;
                overflow-y: auto !important;
            }

            .summary-container { 
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 8px !important;
                margin-bottom: 20px !important;

            }

            .summary-card {
                padding: 12px !important;
                border-radius: 10px !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 4px !important;
                position:relative !important;
                overflow: visible !important;
            }

            .card-content {
                display: flex !important;
                flex-direction: column !important;
                width: 100% !important;
            }
           
            .card-content .label { 
                order: 1 !important;
                font-size: 8px !important;
                margin-bottom: 4px !important;
                word-break: break-word !important;
                line-height: 1.2 !important;
            }

            .card-content .count { 
                order: 2 !important;
                font-size: 1.4rem !important;
            }

            .gold-icon {
                position:absolute !important;
                top: 12px !important;
                right: 12px !important;
                font-size: 1.1rem !important;
                z-index: 1 !important;
                display: block !important;
                opacity: 1 !important;
            }

            .table-controls {
                display: flex !important;
                flex-direction: column !important;
                gap: 8px !important;
            }

            .filters {
                display: flex !important;
                flex-direction: row !important;
                flex-wrap: nowrap !important;
                gap: 6px !important;
                width: 100% !important;
            }

            .filter-select {
                flex: 1 !important;
                min-width: 0 !important;
                padding: 8px !important;
                font-size: 11px !important;
            }

            .table-controls > div:last-child {
                order: 1 !important;
                display: flex !important;
                flex-direction: row !important;
                gap: 8px !important;
                width: 100% !important;
            }

            .table-controls > .filters {
                order: 2 !important;
                display: flex !important;
                flex-direction: row !important;
                flex-wrap: nowrap !important;
                gap: 6px !important;
                width: 100% !important;
            }

            .search-box {
                flex: 1 !important;
            }

            .search-box input {
                width: 100% !important;
                font-size: 13px !important;
                padding: 8px 36px 8px 12px !important;
            }

            .btn-button {
                white-space: nowrap !important;
                font-size: 11px !important;
                padding: 8px 10px !important;
            }

            .sidebar {
                z-index: 99999 !important;
            }

            .col-lg-7, .col-lg-5 {
                width: 100% !important;
                max-width: 100% !important;
                flex: 0 0 100% !important;
            }

            .form-grid { grid-template-columns: 1fr !important; }
            .form-grid [style*="grid-column:span 2"] {
                grid-column: span 1 !important;
            }

            .table-container { overflow-x: auto !important; }            
        }
    </style>
</head>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>

    function showSection(sectionID) {
        //for hiding every sections
        document.querySelectorAll('.section').forEach(section => {
            section.classList.remove('active');

        });
        //show the selected section
        document.getElementById(sectionID).classList.add('active');

        //update sidebar active state
        document.querySelectorAll('.sidebar a').forEach(link => {
            link.classList.remove('active');
        });

        event.currentTarget.classList.add('active');
    }
</script>

<body data-page="rooms">

    <?php include 'navbar.php'; ?>

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

    <div class="page-body">
        <!-- SIDEBAR -->
        <aside class="sidebar" style="padding-bottom: 70px;">
            <a href="#" class="active" onclick="showSection('dashboard')" data-tooltip="Dashboard">
                <i class="bi bi-person-fill-lock"></i>
                Dashboard
            </a>
            <a href="#" onclick="showSection('postings')" data-tooltip="Internship Postings">
                <i class="bi bi-pencil-fill"></i>
                Postings
            </a>
            <a href="#" onclick="showSection('applicants')" data-tooltip="Applicants">
                <i class="bi bi-people-fill"></i>
                Applicants
            </a>
            <a href="#" onclick="showSection('documents')" data-tooltip="Documents">
                <i class="bi bi-file-earmark-text-fill"></i>
                Documents
            </a>
            <a href="#" onclick="showSection('interested')" data-tooltip="Interested Students">
                <i class="bi bi-bookmarks-fill"></i>
                Interested
            </a>
            <a href="#" onclick="showSection('announcements')" data-tooltip="Announcements">
                <i class="bi bi-bell-fill"></i>
                Announcements
            </a>
            <a href="#" onclick="showSection('manage_announcement')" data-tooltip="Manage Announcement">
                <i class="bi bi-bookmark"></i>
                Manage Announcement
            </a>
            <a href="#" onclick="showSection('student_register')" data-tooltip="Student Register">
                <i class="bi bi-file-earmark-person"></i>
                Student Register
            </a>
        </aside>
        <div class="main-content">
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

        <!-- ROW 1: Application List + Recent Documents -->
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
                            <tr style="color:#aaa; font-size:12px; text-transform:uppercase; letter-spacing:.04em;">
                                <th style="padding:8px 10px; border-bottom:1px solid #f0f2f7; font-weight:600;">Student</th>
                                <th style="padding:8px 10px; border-bottom:1px solid #f0f2f7; font-weight:600;">Company</th>
                                <th style="padding:8px 10px; border-bottom:1px solid #f0f2f7; font-weight:600;">Status</th>
                                <th style="padding:8px 10px; border-bottom:1px solid #f0f2f7; font-weight:600;">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentInterested as $ri): ?>
                            <tr>
                                <td style="padding:10px; border-bottom:1px solid #f0f2f7;">
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <div style="width:30px; height:30px; min-width:30px; border-radius:50%; background:#eef1ff; color:#272f54; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:600;">
                                            <?= strtoupper(substr($ri['full_name'], 0, 1)) ?>
                                        </div>
                                        <span style="font-weight:500; color:#272f54;"><?= htmlspecialchars($ri['full_name']) ?></span>
                                    </div>
                                </td>
                                <td style="padding:10px; border-bottom:1px solid #f0f2f7; color:#888; font-size:12px;"><?= htmlspecialchars($ri['company']) ?></td>
                                <td style="padding:10px; border-bottom:1px solid #f0f2f7;">
                                    <span style="background:#fff8e1; color:#633806; font-size:11px; padding:3px 10px; border-radius:6px; font-weight:500;">Interested</span>
                                </td>
                                <td style="padding:10px; border-bottom:1px solid #f0f2f7; color:#aaa; font-size:12px;">
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

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-arrow-up" style="color:#272f54;"></i>
                    <h6 class="fw-bold mb-0" style="color:#272f54;">Recently Uploaded Documents</h6>
                </div>
                <div class="card-body px-4 pb-4 pt-2">
                    <p class="text-muted small mb-3">Latest student document submissions.</p>
                    <div class="d-flex flex-column gap-3">
                        <div style="display:flex; align-items:center; gap:10px; padding-bottom:12px; border-bottom:1px solid #f0f2f7;">
                            <div style="width:32px; height:32px; min-width:32px; border-radius:50%; background:#EAF3DE; color:#27500A; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:600;">H</div>
                            <div style="flex:1; min-width:0;">
                                <p style="margin:0; font-size:13px; font-weight:600; color:#272f54;">Herold James Elisterio</p>
                                <p style="margin:0; font-size:11px; color:#888;">Resume</p>
                            </div>
                            <span style="font-size:11px; color:#aaa; white-space:nowrap;">May 14</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:10px; padding-bottom:12px; border-bottom:1px solid #f0f2f7;">
                            <div style="width:32px; height:32px; min-width:32px; border-radius:50%; background:#FAECE7; color:#712B13; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:600;">S</div>
                            <div style="flex:1; min-width:0;">
                                <p style="margin:0; font-size:13px; font-weight:600; color:#272f54;">Suzane Mikyla Escatron</p>
                                <p style="margin:0; font-size:11px; color:#888;">Portfolio</p>
                            </div>
                            <span style="font-size:11px; color:#aaa; white-space:nowrap;">May 13</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div style="width:32px; height:32px; min-width:32px; border-radius:50%; background:#E6F1FB; color:#0C447C; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:600;">R</div>
                            <div style="flex:1; min-width:0;">
                                <p style="margin:0; font-size:13px; font-weight:600; color:#272f54;">Riva Mae Boongaling</p>
                                <p style="margin:0; font-size:11px; color:#888;">Recommendation Letter</p>
                            </div>
                            <span style="font-size:11px; color:#aaa; white-space:nowrap;">May 12</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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
                            <tr style="color:#aaa; font-size:12px; text-transform:uppercase; letter-spacing:.04em;">
                                <th style="padding:8px 10px; border-bottom:1px solid #f0f2f7; font-weight:600;">Title</th>
                                <th style="padding:8px 10px; border-bottom:1px solid #f0f2f7; font-weight:600;">Company</th>
                                <th style="padding:8px 10px; border-bottom:1px solid #f0f2f7; font-weight:600;">Location</th>
                                <th style="padding:8px 10px; border-bottom:1px solid #f0f2f7; font-weight:600;">Posted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentInternships as $ri): ?>
                            <tr>
                                <td style="padding:10px; border-bottom:1px solid #f0f2f7; font-weight:500; color:#272f54;">
                                    <?= htmlspecialchars($ri['title']) ?>
                                </td>
                                <td style="padding:10px; border-bottom:1px solid #f0f2f7; color:#888; font-size:12px;">
                                    <?= htmlspecialchars($ri['company']) ?>
                                </td>
                                <td style="padding:10px; border-bottom:1px solid #f0f2f7; color:#888; font-size:12px;">
                                    <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($ri['location']) ?>
                                </td>
                                <td style="padding:10px; border-bottom:1px solid #f0f2f7;">
                                    <span style="background:#f0f4ff; color:#272f54; font-size:11px; padding:3px 10px; border-radius:6px; font-weight:500;">
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


        <!-- ROW 2: Recent Announcements + Recent Postings -->
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
                                'news'    => ['bg' => '#e6f1fb', 'color' => '#0c447c'],
                                'updates' => ['bg' => '#eaf3de', 'color' => '#27500a'],
                                'FAQs'    => ['bg' => '#faeeda', 'color' => '#633806'],
                            ];
                            $c = $catColors[$a['category']] ?? ['bg' => '#f0f0f0', 'color' => '#444'];
                        ?>
                        <div class="d-flex align-items-start gap-3">
                            <span style="background:<?= $c['bg'] ?>; color:<?= $c['color'] ?>; font-size:11px; padding:3px 10px; border-radius:6px; font-weight:600; white-space:nowrap;">
                                <?= htmlspecialchars(ucfirst($a['category'])) ?>
                            </span>
                            <div style="flex:1; min-width:0;">
                                <p style="font-weight:600; margin:0; color:#272f54; font-size:13px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                    <?= htmlspecialchars($a['title']) ?>
                                </p>
                                <p style="color:#aaa; margin:0; font-size:11px;">
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

    </div>
</div>
            <div id="postings" class="section">
                <h2>Internship Postings</h2>
                <p>The administrative module for publishing, modifying, and monitoring active internship listings.</p>

                <!-- FILTERS ROW -->
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
                            <input type="text" id="search-postings" placeholder="Search by company or title..." oninput="filterPostings()">
                            <i class="bi bi-search"></i>
                        </div>
                        <button class="btn-button" onclick="showPostingForm()">
                            <i class="bi bi-plus-circle me-1"></i> Add Internship Post
                        </button>
                    </div>
                </div>

                <!-- TABLE -->
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
                        <tr data-program="Information Technology">
                            <td style="padding: 14px 15px;"><input type="text" value="TechCorp PH" class="form-control form-control-sm"></td>
                            <td style="padding: 14px 15px;"><input type="text" value="Web Developer Intern" class="form-control form-control-sm"></td>
                            <td style="padding: 14px 15px;">
                                <select class="form-select form-select-sm">
                                    <option value="Information Technology" selected>IT</option>
                                    <option value="Civil Engineering">CE</option>
                                    <option value="Electrical Engineering">EE</option>
                                    <option value="Information Technology, Civil Engineering">IT, CE</option>
                                    <option value="Information Technology, Electrical Engineering">IT, EE</option>
                                    <option value="Civil Engineering, Electrical Engineering">CE, EE</option>
                                    <option value="Information Technology, Civil Engineering, Electrical Engineering">IT, CE, EE</option>
                                </select>
                            </td>
                            <td style="padding: 14px 15px;"><input type="text" value="Quezon City" class="form-control form-control-sm"></td>
                            <td style="text-align:center;">
                                <button class="btn btn-sm btn-primary" title="Save" onclick="saveRow(this)"><i class="bi bi-floppy"></i></button>
                                <button class="btn btn-sm btn-danger" title="Delete" onclick="deleteRow(this)"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                        <tr data-program="Civil Engineering">
                            <td style="padding: 14px 15px;"><input type="text" value="Dept. of Public Works" class="form-control form-control-sm"></td>
                            <td style="padding: 14px 15px;"><input type="text" value="Site Engineer Intern" class="form-control form-control-sm"></td>
                            <td style="padding: 14px 15px;">
                                <select class="form-select form-select-sm">
                                    <option value="Information Technology">IT</option>
                                    <option value="Civil Engineering" selected>CE</option>
                                    <option value="Electrical Engineering">EE</option>
                                    <option value="Information Technology, Civil Engineering">IT, CE</option>
                                    <option value="Information Technology, Electrical Engineering">IT, EE</option>
                                    <option value="Civil Engineering, Electrical Engineering">CE, EE</option>
                                    <option value="Information Technology, Civil Engineering, Electrical Engineering">IT, CE, EE</option>
                                </select>
                            </td>
                            <td style="padding: 14px 15px;"><input type="text" value="Manila" class="form-control form-control-sm"></td>
                            <td style="text-align:center;">
                                <button class="btn btn-sm btn-primary" title="Save" onclick="saveRow(this)"><i class="bi bi-floppy"></i></button>
                                <button class="btn btn-sm btn-danger" title="Delete" onclick="deleteRow(this)"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                        <tr data-program="Electrical Engineering">
                            <td style="padding: 14px 15px;"><input type="text" value="Meralco" class="form-control form-control-sm"></td>
                            <td style="padding: 14px 15px;"><input type="text" value="Electrical Intern" class="form-control form-control-sm"></td>
                            <td style="padding: 14px 15px;">
                                <select class="form-select form-select-sm">
                                    <option value="Information Technology">IT</option>
                                    <option value="Civil Engineering">CE</option>
                                    <option value="Electrical Engineering" selected>EE</option>
                                    <option value="Information Technology, Civil Engineering">IT, CE</option>
                                    <option value="Information Technology, Electrical Engineering">IT, EE</option>
                                    <option value="Civil Engineering, Electrical Engineering">CE, EE</option>
                                    <option value="Information Technology, Civil Engineering, Electrical Engineering">IT, CE, EE</option>
                                </select>
                            </td>
                            <td style="padding: 14px 15px;"><input type="text" value="Pasig City" class="form-control form-control-sm"></td>
                            <td style="text-align:center;">
                                <button class="btn btn-sm btn-primary" title="Save" onclick="saveRow(this)"><i class="bi bi-floppy"></i></button>
                                <button class="btn btn-sm btn-danger" title="Delete" onclick="deleteRow(this)"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    </tbody>
                    </table>
                </div>

                <!-- ADD FORM (hidden by default) -->
                <div id="posting-form-panel" style="display:none; margin-top:20px;">
                    <div class="form-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 style="margin:0;">New Internship Posting</h3>
                            <button type="button" onclick="hidePostingForm()"
                                style="background:none; border:none; font-size:20px; cursor:pointer; color:#888;">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <form method="POST" action="internship-db.php"
                            onsubmit="return confirm('Create this internship posting?');">
                            <input type="hidden" name="form_type" value="internship_posting">
                            <div class="form-grid">
                                <div><label>Title</label><input type="text" name="title" placeholder="Job Title" required></div>
                                <div><label>Company</label><input type="text" name="company" placeholder="Company Name" required></div>
                                <div><label>Location</label><input type="text" name="location" placeholder="Location" required></div>
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
                                        <option value="Information Technology, Civil Engineering, Electrical Engineering">IT, CE, EE</option>
                                    </select>
                                </div>
                                <div><label>Deadline</label><input type="date" name="deadline"></div>
                                <div><label>Contact Email</label><input type="email" name="email" placeholder="Contact Email"></div>
                                <div><label>Contact Number</label><input type="tel" name="phonenumber" placeholder="Contact Number"></div>
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
                            <div class="d-flex justify-content-end gap-2 mt-3">
                                <button type="button" onclick="hidePostingForm()"
                                    style="background:#888; color:white; border:none; padding:11px 24px; border-radius:8px; font-weight:600; cursor:pointer;">
                                    Cancel
                                </button>
                                <button type="submit" class="submit-btn" style="width:auto; padding:11px 24px;">
                                    Create Posting
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div id="applicants" class="section sysAdm-header">
                <h2>Applicants</h2>
                <p>A place to review student credentials and track candidate progress through the hiring pipeline.</p>
                <div class="table-controls">
                    <div class="filters">
                        <select class="filter-select" onchange="filterApplicants()">
                            <option value="All Statuses">All Statuses</option>
                            <option value="New Application">New Application</option>
                            <option value="Placement Confirmed">Placement Confirmed</option>
                            <option value="Interviewing">Interviewing</option>
                            <option value="Waitlisted">Waitlisted</option>
                        </select>
                        <select class="filter-select" onchange="filterApplicants()">
                            <option value="All Requirements">All Requirements</option>
                            <option value="Complete">Complete</option>
                            <option value="Incomplete">Incomplete</option>
                        </select>
                        <select class="filter-select" onchange="filterApplicants()">
                            <option value="All Programs">All Programs</option>
                            <option value="Information Technology">Information Technology</option>
                            <option value="Civil Engineering">Civil Engineering</option>
                            <option value="Electrical Engineering">Electrical Engineering</option>
                        </select>
                    </div>
                    <div class="search-box">
                        <input type="text" placeholder="Search" oninput="filterApplicants()">
                        <i class="bi bi-search"></i>
                    </div>
                </div>

                <div class="table-container">
                    <table class="custom-table" id="applicants-table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Status</th>
                                <th>Program</th>
                                <th>Requirements</th>
                                <th>Number Applied</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="clickable-text">John Doe</span></td>
                                <td><span class="clickable-text">Placement Confirmed</span></td>
                                <td>Information Technology</td>
                                <td><span class="clickable-text">Complete</span></td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><span class="clickable-text">John Doe</span></td>
                                <td><span class="clickable-text">Interviewing</span></td>
                                <td>Information Technology</td>
                                <td><span class="clickable-text">Complete</span></td>
                                <td><span class="clickable-text">10</span></td>
                            </tr>
                            <tr>
                                <td><span class="clickable-text">John Doe</span></td>
                                <td><span class="clickable-text">New Application</span></td>
                                <td>Information Technology</td>
                                <td><span class="clickable-text">Incomplete</span></td>
                                <td>0</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="documents" class="section sysAdm-header">
                <h2>Documents</h2>
                <p>A secure repository for managing, verifying, and storing mandatory internship documentation.</p>
                <div class="table-controls">
                    <div class="filters">
                        <select class="filter-select">
                            <option value="All Document Types">All Document Types</option>
                            <option value="Resume">Resume</option>
                            <option value="Portfolio">Portfolio</option>
                            <option value="Recommendation Letter">Recommendation Letter</option>
                        </select>
                        <select class="filter-select">
                            <option value="All Programs">All Programs</option>
                            <option value="Information Technology">Information Technology</option>
                            <option value="Civil Engineering">Civil Engineering</option>
                            <option value="Electrical Engineering">Electrical Engineering</option>
                        </select>
                    </div>
                    <div class="search-box">
                        <input type="text" placeholder="Search by student name or company...">
                        <i class="bi bi-search"></i>
                    </div>
                </div>

                <div class="table-container">
                    <table class="custom-table" id="documents-table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Program</th>
                                <th>Company</th>
                                <th>Document Type</th>
                                <th>Submission Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>John Doe</td>
                                <td>Information Technology</td>
                                <td>Company XYZ</td>
                                <td>Resume</td>
                                <td>01/25/2026</td>
                                <td style="text-align: center;">
                                    <button class="view-btn">View</button>
                                </td>
                            </tr>
                            <tr>
                                <td>John Doe</td>
                                <td>Information Technology</td>
                                <td>Company XYZ</td>
                                <td>Resume</td>
                                <td>01/25/2026</td>
                                <td style="text-align: center;">
                                    <button class="view-btn">View</button>
                                </td>
                            </tr>
                            <tr>
                                <td>John Doe</td>
                                <td>Information Technology</td>
                                <td>Company XYZ</td>
                                <td>Resume</td>
                                <td>01/25/2026</td>
                                <td style="text-align: center;">
                                    <button class="view-btn">View</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="interested" class="section sysAdm-header">
                <h2>Interested</h2>
                <p>A tracking section for monitoring student engagement and preliminary interest in companies and internships.</p>

                <!-- SEARCH ROW -->
                <div class="table-controls">
                    <div class="filters">
                        <!-- no dropdowns for this section -->
                    </div>
                    <div class="search-box">
                        <input type="text" id="search-interested" placeholder="Search by name, email, company...">
                        <i class="bi bi-search"></i>
                    </div>
                </div>

                <!-- TABLE -->
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
                                    <td style="padding: 14px 15px;"><?= htmlspecialchars($i['full_name']) ?></td>
                                    <td style="padding: 14px 15px;"><?= htmlspecialchars($i['email']) ?></td>
                                    <td style="padding: 14px 15px;"><?= htmlspecialchars($i['company']) ?></td>
                                    <td style="padding: 14px 15px;"><?= htmlspecialchars($i['title']) ?></td>
                                    <td style="text-align:center;">
                                        <button type="button" class="btn btn-sm btn-danger"
                                            onclick="openFeedbackModal(<?= $i['interest_id'] ?>)"
                                            style="padding:4px 8px; border-radius:16px; font-size:12px;">
                                            Return with Feedback
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>


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
                            <select name="category" id="category">
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
            <div id="manage_announcement" class="section sysAdm-header">
                <h2>Manage Announcements</h2>
                <p>The content management utility for drafting, scheduling, and distributing official notifications</p>

                <!-- FILTERS ROW - matches Applicants style -->
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
                        <input type="text" id="search-announcements" placeholder="Search announcements..." oninput="filterAnnouncements()">
                        <i class="bi bi-search"></i>
                    </div>
                </div>

                <!-- TABLE -->
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
                                        <td class="fw-bold" style="border: none; padding: 14px 15px">
                                            <input type="text" name="title" value="<?= htmlspecialchars($a['title']) ?>" required>
                                        </td>
                                        <td>
                                            <input type="text" name="message" value="<?= htmlspecialchars($a['message']) ?>" required>
                                        </td>
                                        <td>
                                            <select name="category" required>
                                                <option value="news" <?= $a['category'] === 'news' ? 'selected' : '' ?>>News</option>
                                                <option value="updates" <?= $a['category'] === 'updates' ? 'selected' : '' ?>>Updates</option>
                                                <option value="FAQs" <?= $a['category'] === 'FAQs' ? 'selected' : '' ?>>FAQs</option>
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

            <!-- STUDENT REGISTER SECTION -->
            <div id="student_register" class="section sysAdm-header">
                <h2>Student Register</h2>
                <p>The master list for keeping tabs on every student in the system.</p>

                <!-- View/Edit CSV -->
                <!-- <div class="form-card">
                    <div class="mb-4">
                        <h5 class="fw-semibold mb-1" style="color:#272f54;">Excel Sheet for Student Registration</h5>
                        <p class="text-muted small mb-2">View and edit the current student CSV file.</p>
                            <div class="text-end">
                            <button type="button"
                                style="background: linear-gradient(135deg, #FFB62F, #E4572E); color:white; border:none; padding:8px 16px; border-radius:8px; font-weight:600; font-size:13px; cursor:pointer;"
                                onclick="showSection('edit_csv')">
                                <i class="bi bi-pencil-square me-1"></i> Edit Current CSV
                            </button>
                            </div>
                    </div>
                </div> -->

                <div class="form-card">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <h5 class="fw-semibold mb-1" style="color:#272f54;">Excel Sheet for Student Registration</h5>
                            <p class="text-muted small mb-0">View and edit the current student CSV file.</p>
                        </div>
                        <button class="btn-button align-self-center" onclick="showSection('edit_csv')">
                            <i class="bi bi-pencil-square me-1"></i>Edit Current CSV
                        </button>
                    </div>
                </div>

                <hr style="border-color:#eee;">
                
                <!-- Import CSV -->
                <!-- <div class="form-card">
                    <div class="mb-4 mt-3">
                        <h5 class="fw-semibold mb-1" style="color:#272f54;">Import New CSV File</h5>
                        <p class="text-muted small mb-2">Replaces the existing CSV with the uploaded file.</p>
                        <form action="auto-register-csv.php" method="POST" enctype="multipart/form-data">
                            <div class="d-flex gap-2">
                                <input type="file" name="students_csv" accept=".csv" required
                                    style="border:1px solid #ddd; border-radius:8px; padding:6px 10px; font-size:13px;">
                                <div class="text-end">
                                    <button type="submit"
                                        style="background: linear-gradient(135deg, #FFB62F, #E4572E); color:white; border:none; padding:8px 16px; border-radius:8px; font-weight:600; font-size:13px; cursor:pointer;">
                                        <i class="bi bi-upload me-1"></i> Replace CSV
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div> -->

                <div class="form-card">
                    <div class="mb-3">
                        <h5 class="fw-semibold mb-1" style="color:#272f54;">Import New CSV File</h5>
                        <p class="text-muted small mb-0">Replaces the existing CSV with the uploaded file.</p>
                    </div>
                    <form action="auto-register-csv.php" method="POST" enctype="multipart/form-data">
                        <div class="d-flex gap-2 align-items-center">
                            <input type="file" name="students_csv" accept=".csv" required class="form-control" style="flex:1; font-size:13px;">
                            <button class="btn-button" type="submit">
                                <i class="bi bi-upload me-1"></i>Replace CSV
                            </button>
                        </div>
                    </form>
                </div>
                
                <hr style="border-color:#eee;">
                    
                <!-- Download CSV -->
                <div class="form-card">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <h5 class="fw-semibold mb-1" style="color:#272f54;">Download Current CSV</h5>
                            <p class="text-muted small mb-0">Download the current student registration file.</p>
                        </div>
                        <!-- <a href="download-csv.php" class="btn-button align-self-center">
                                <i class="bi bi-download"></i> Download CSV
                            </a> -->
                        <button class="btn-button align-self-center" onclick="window.location.href='download-csv.php'">
                            <i class="bi bi-download me-2"></i>Download CSV
                        </button>
                    </div>
                </div>


            </div>
            <div id="edit_csv" class="section sysAdm-header">
                <h2>Edit Student CSV</h2>

                <div class="form-card">
                    <?php
                    $csvPath = __DIR__ . '/../Sources/students.csv';
                    $csvRows = [];

                    if (($handle = fopen($csvPath, 'r')) !== false) {
                        while (($row = fgetcsv($handle, 1000, "\t")) !== false) {
                            $csvPath = __DIR__ . '/../Sources/students.csv';
                            $csvRows = [];

                            $content = file_get_contents($csvPath);
                            $content = str_replace("\r\n", "\n", $content);
                            $content = str_replace("\r", "\n", $content);

                            $lines = explode("\n", $content);
                            $csvRows = [];

                            foreach ($lines as $line) {
                                $line = trim($line, " \t\n\r\0\x0B\""); // strip surrounding quotes
                                if ($line === '')
                                    continue;

                                // detect delimiter per row
                                $row = str_contains($line, "\t")
                                    ? explode("\t", $line)
                                    : str_getcsv($line, ",");

                                // clean each cell of extra quotes
                                $row = array_map(fn($cell) => trim($cell, '"'), $row);

                                $csvRows[] = $row;
                            }

                            // Remove duplicate header if present
                            if (count($csvRows) > 1 && $csvRows[0] === $csvRows[1]) {
                                array_shift($csvRows);
                            }
                        }
                        fclose($handle);
                    }
                    ?>
                    <pre><?php //print_r($csvRows); ?></pre>
                    <form method="POST" action="auto-register-save-csv.php">
                        <?php foreach ($csvRows[0] as $colIndex => $headerCell): ?>
                            <input type="hidden" name="headers[<?= $colIndex ?>]"
                                value="<?= htmlspecialchars($headerCell) ?>">
                        <?php endforeach; ?>

                        <table class="table table-bordered" id="csv-table">
                            <thead>
                                <tr>
                                    <?php foreach ($csvRows[0] as $headerCell): ?>
                                        <th><?= htmlspecialchars($headerCell) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody id="csv-tbody">
                                <?php foreach ($csvRows as $rowIndex => $row): ?>
                                    <?php if ($rowIndex === 0)
                                        continue; // skip header row ?>
                                    <tr>
                                        <?php foreach ($row as $colIndex => $cell): ?>
                                            <td>
                                                <input type="text" name="csv[<?= $rowIndex ?>][<?= $colIndex ?>]"
                                                    value="<?= htmlspecialchars($cell) ?>" class="form-control">
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- store col count for JS -->
                        <input type="hidden" id="col-count" value="<?= count($csvRows[0]) ?>">
                        <input type="hidden" id="row-count" value="<?= count($csvRows) ?>">

                        <div class="d-flex gap-2 mt-3">
                            <button type="button" class="btn btn-success" onclick="addRow()">
                                <i class="bi bi-plus-circle"></i> Add Row
                            </button>
                            <div class="text-end" style="flex:1;">
                            <button type="submit" class="submit-btn">Save CSV</button>
                            <button type="button" class="submit-btn btn-danger"
                                onclick="showSection('student_register')">
                                Back
                            </button></div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>


    <!-- Feedback Modal -->
    <div id="feedbackModal"
        style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;">
        <div style="background:#29335C; border-radius:5px; padding:30px; width:500px; max-width:90%;">

            <h5 style="color:white; text-align:center; margin-bottom:0px; font-weight:400;">
                Return with Feedback
            </h5>

            <textarea id="feedbackText" placeholder="Enter feedback here.."
                style="width:100%; height:130px; border-radius:5px; border:none; padding:14px; font-size:14px; resize:none; outline:none;"></textarea>

            <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:20px;">
                <button onclick="closeFeedbackModal()"
                    style="background:transparent; color:white; border:1px solid white; padding:8px 20px; border-radius:20px; cursor:pointer; font-size:14px;">
                    Cancel
                </button>
                <button onclick="sendFeedback()"
                    style="background:white; color:#29335C; border:none; padding:8px 20px; border-radius:20px; cursor:pointer; font-size:14px; font-weight:600;">
                    Send Feedback
                </button>

                
            </div>

        </div>
    </div>
    <script>
        setTimeout(() => {
            const alert = document.getElementById('flashAlert');
            if (alert) {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 300);
            }
        }, 3000);

        function addRow() {
            const tbody = document.getElementById('csv-tbody');
            const colCount = parseInt(document.getElementById('col-count').value);
            const rowCount = parseInt(document.getElementById('row-count').value);

            // use current row count as new index to avoid collisions
            const newRowIndex = rowCount;
            document.getElementById('row-count').value = rowCount + 1;

            const tr = document.createElement('tr');

            for (let col = 0; col < colCount; col++) {
                const td = document.createElement('td');
                const input = document.createElement('input');
                input.type = 'text';
                input.name = `csv[${newRowIndex}][${col}]`;
                input.className = 'form-control';
                input.placeholder = '—';
                td.appendChild(input);
                tr.appendChild(td);
            }

            tbody.appendChild(tr);

            // scroll to new row
            tr.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
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

            if (!bookmarkId) {
                alert("No bookmark selected.");
                return;
            }

            if (!feedback) {
                alert('Please enter feedback before sending.');
                return;
            }

            const formData = new FormData();
            formData.append('bookmark_id', bookmarkId);
            formData.append('feedback', feedback);
            formData.append('send_feedback', 1);
            formData.append('form_type', 'send_feedback');

            fetch('internship-db.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.text())
                .then(text => {
                    console.log(text);

                    if (text.trim() !== "success") {
                        alert(text);
                        return;
                    }

                    closeFeedbackModal();
                    location.reload();
                })
                .catch(err => {
                    console.error(err);
                    alert('Something went wrong.');
                });
        }

        // Close modal when clicking outside
        document.getElementById('feedbackModal').addEventListener('click', function (e) {
            if (e.target === this) closeFeedbackModal();
        });

        // APPLICANTS
        // function filterApplicants() {
        //     const selects      = document.querySelectorAll('#applicants .filter-select');
        //     const status       = selects[0].value;
        //     const requirements = selects[1].value;
        //     const program      = selects[2].value;
        //     const search       = document.querySelector('#applicants .search-box input').value.toLowerCase();

        //     document.querySelectorAll('#applicants-table tbody tr').forEach(row => {
        //         const cells           = row.querySelectorAll('td');
        //         const rowStatus       = cells[1]?.innerText.trim() ?? '';
        //         const rowProgram      = cells[2]?.innerText.trim() ?? '';
        //         const rowRequirements = cells[3]?.innerText.trim() ?? '';
        //         const rowName         = cells[0]?.innerText.toLowerCase() ?? '';

        //         const matchStatus       = status === 'All Statuses' || rowStatus === status;
        //         const matchRequirements = requirements === 'All Requirements' || rowRequirements === requirements;
        //         const matchProgram      = program === 'All Programs' || rowProgram === program;
        //         const matchSearch       = search === '' || rowName.includes(search);

        //         row.style.display = (matchStatus && matchRequirements && matchProgram && matchSearch) ? '' : 'none';
        //     });
        // }

        function filterApplicants() {
            const selects      = document.querySelectorAll('#applicants .filter-select');
            const status       = selects[0].value;
            const requirements = selects[1].value;
            const program      = selects[2].value;
            const search       = document.querySelector('#applicants .search-box input').value.toLowerCase();

            document.querySelectorAll('#applicants-table tbody tr').forEach(row => {
                const cells           = row.querySelectorAll('td');
                const rowStatus       = cells[1]?.innerText.trim() ?? '';
                const rowProgram      = cells[2]?.innerText.trim() ?? '';
                const rowRequirements = cells[3]?.innerText.trim() ?? '';
                const rowName         = cells[0]?.innerText.toLowerCase() ?? '';

                const matchStatus       = status === 'All Statuses'          || rowStatus === status;
                const matchRequirements = requirements === 'All Requirements'    || rowRequirements === requirements;
                const matchProgram      = program === 'All Programs' || rowProgram === program;
                const matchSearch       = search === ''             || rowName.includes(search);

                row.style.display = (matchStatus && matchRequirements && matchProgram && matchSearch) ? '' : 'none';
            });
        }

        // DOCUMENTS
        document.querySelectorAll('#documents .filter-select').forEach(select => {
            select.addEventListener('change', filterDocuments);
        });

        function filterDocuments() {
            const selects = document.querySelectorAll('#documents .filter-select');
            const docType  = selects[0].value;
            const program  = selects[1].value;
            const search   = document.querySelector('#documents .search-box input').value.toLowerCase();

            document.querySelectorAll('#documents-table tbody tr').forEach(row => {
                const cells      = row.querySelectorAll('td');
                const rowName    = cells[0]?.innerText.toLowerCase() ?? '';
                const rowProgram = cells[1]?.innerText.trim() ?? '';
                const rowCompany = cells[2]?.innerText.toLowerCase() ?? '';
                const rowDocType = cells[3]?.innerText.trim() ?? '';

                const matchDocType = docType === 'All Document Types' || rowDocType === docType;
                const matchProgram = program === 'All Programs'      || rowProgram === program;
                const matchSearch  = search === ''               || rowName.includes(search) || rowCompany.includes(search);

                row.style.display = (matchDocType && matchProgram && matchSearch) ? '' : 'none';
            });
        }

        // Announcements search (manage_announcement)
        // document.getElementById('search-announcements').addEventListener('input', function () {
        //     const query = this.value.toLowerCase();
        //     document.querySelectorAll('#manage-announcements-tbody tr').forEach(row => {
        //         // collect text from inputs, selects, and plain td text
        //         const text = Array.from(row.querySelectorAll('input, select, td'))
        //             .map(el => {
        //                 if (el.tagName === 'INPUT' || el.tagName === 'SELECT') {
        //                     return el.value;
        //                 }
        //                 return el.innerText;
        //             })
        //             .join(' ')
        //             .toLowerCase();

        //         row.style.display = text.includes(query) ? '' : 'none';
        //     });
        // });

        function filterAnnouncements() {
            const search = document.getElementById('search-announcements').value.toLowerCase();
            const category = document.getElementById('category-filter').value.toLowerCase();

            document.querySelectorAll('#manage-announcements-tbody tr').forEach(row => {
                const rowCategory = (row.dataset.category ?? '').toLowerCase();
                const text = Array.from(row.querySelectorAll('input, select, td'))
                    .map(el => el.tagName === 'INPUT' || el.tagName === 'SELECT' ? el.value : el.innerText)
                    .join(' ').toLowerCase();

                const matchSearch = text.includes(search);
                const matchCategory = category === '' || rowCategory === category;

                row.style.display = (matchSearch && matchCategory) ? '' : 'none';
            });
        }

        // Interested search
        document.getElementById('search-interested').addEventListener('input', function () {
            const query = this.value.toLowerCase();
            document.querySelectorAll('#interested-tbody tr').forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(query) ? '' : 'none';
            });
        });

// changes in internship postings
        function showPostingForm() {
            document.getElementById('posting-form-panel').style.display = 'block';
            document.getElementById('posting-form-panel').scrollIntoView({ behavior:'smooth' });
        }

        function hidePostingForm() {
            document.getElementById('posting-form-panel').style.display = 'none';
        }

        function filterPostings() {
            const program = document.getElementById('postings-program-filter').value;
            const search  = document.getElementById('search-postings').value.toLowerCase();

            document.querySelectorAll('#postings-tbody tr').forEach(row => {
                const rowProgram = row.dataset.program ?? '';
                const rowText    = row.innerText.toLowerCase();

                const matchProgram = program === 'All' || rowProgram.includes(program);
                const matchSearch  = search === ''     || rowText.includes(search);

                row.style.display = (matchProgram && matchSearch) ? '' : 'none';
            });
        }

        function saveRow(btn) {
            const row = btn.closest('tr');
            const company  = row.querySelector('td:nth-child(1) input').value;
            const title    = row.querySelector('td:nth-child(2) input').value;
            const program  = row.querySelector('td:nth-child(3) select').value;
            const location = row.querySelector('td:nth-child(4) input').value;

            if (!company || !title || !location) {
                alert('Please fill in all fields before saving.');
                return;
            }

            // update data-program for filter to work
            row.dataset.program = program;

            // visual feedback
            btn.innerHTML = '<i class="bi bi-check-lg"></i>';
            btn.classList.replace('btn-primary', 'btn-success');
            setTimeout(() => {
                btn.innerHTML = '<i class="bi bi-floppy"></i>';
                btn.classList.replace('btn-success', 'btn-primary');
            }, 1500);
        }

        function deleteRow(btn) {
            if (!confirm('Delete this internship posting?')) return;
            btn.closest('tr').remove();
        }

        // FOr The Map Pinning function
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
                        position: e.latLng,
                        map: postingMap,
                        title: 'Internship Location',
                        draggable: true
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
