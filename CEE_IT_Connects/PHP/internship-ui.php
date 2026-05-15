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
        .main-content {
            flex: 1;
            display: auto;
            justify-content: center;
            align-items: center;
            padding: 40px;
        }

        .internship-form {
            max-width: 800px;
            margin: auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
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
            background: #FFB62F;
            color: #272f54;
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

        event.target.classList.add('active');
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
        <aside class="sidebar">
            <a href="#" class="active" onclick="showSection('dashboard')">
                <i class="bi bi-person-fill-lock"></i>
                Dashboard
            </a>
            <a href="#" onclick="showSection('postings')">
                <i class="bi bi-pencil-fill"></i>
                Postings
            </a>
            <a href="#" onclick="showSection('applicants')">
                <i class="bi bi-people-fill"></i>
                Applicants
            </a>
            <a href="#" onclick="showSection('documents')">
                <i class="bi bi-file-earmark-text-fill"></i>
                Documents
            </a>
            <a href="#" onclick="showSection('interested')">
                <i class="bi bi-bookmarks-fill"></i>
                Interested
            </a>
            <a href="#" onclick="showSection('announcements')">
                <i class="bi bi-bell-fill"></i>
                Announcements
            </a>
            <a href="#" onclick="showSection('manage_announcement')">
                <i class="bi bi-bookmark"></i>
                Manage Announcement
            </a>
            <a href="#" onclick="showSection('student_register')">
                <i class="bi bi-file-earmark-person"></i>
                Student Register
            </a>
        </aside>
        <div class="main-content">
            <div id="dashboard" class="section active">
                <div class="monitor-header">
                    <h2>Dashboard</h2>
                    <p>For the status and statistics</p>
                </div>

                <div class="log-container">
                    <!-- 
                    <table class="monitoring-table">
                        <thead>
                            <tr style="text-align: center;">
                                <th>Name</th>
                                <th>Email</th>
                                <th>Activity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="text-align: center;">
                                <td>Maria Carmela Alfonso</td>
                                <td>macarmelaalfonso@gmail.com</td>
                                <td>Reserved a slot</td>
                                <td>
                                    <button class="btn-action">
                                        <img src="../Sources/history.png" alt="History" class="table-icon">
                                    </button>
                                    <button class="btn-action">
                                        <img src="../Sources/delete.png" alt="Delete" class="table-icon">
                                    </button>
                                </td>
                            </tr>
                            <tr style="text-align: center;">
                                <td>Juan Leonardo Seleno</td>
                                <td>leoseleno@gmail.com</td>
                                <td>Updated listing detail</td>
                                <td>
                                    <button class="btn-action">
                                        <img src="../Sources/history.png" alt="History" class="table-icon">
                                    </button>
                                    <button class="btn-action">
                                        <img src="../Sources/delete.png" alt="Delete" class="table-icon">
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
-->
                    <div class="row g-4">
                        <!-- Recently Interested Students -->
                        <div class="col-lg-7">
                            <div class="card border-0 rounded-4 shadow-sm h-100">
                                <div
                                    class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex align-items-center gap-2">
                                    <i class="bi bi-people" style="color:#272f54;"></i>
                                    <h6 class="fw-bold mb-0" style="color:#272f54;">Recently Interested Students</h6>
                                    <span class="badge ms-auto rounded-pill"
                                        style="background:#fff8e1; color:#7a5200; font-size:11px;">
                                        Latest 5
                                    </span>
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
                        <div class="col-lg-5">
                            <div class="card border-0 rounded-4 shadow-sm h-100">
                                <div
                                    class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex align-items-center gap-2">
                                    <i class="bi bi-bell" style="color:#272f54;"></i>
                                    <h6 class="fw-bold mb-0" style="color:#272f54;">Recent Announcements</h6>
                                    <span class="badge ms-auto rounded-pill"
                                        style="background:#fdecea; color:#7f1d1d; font-size:11px;">
                                        Latest 5
                                    </span>
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
                        <div class="card border-0 rounded-4 shadow-sm mb-4">
                            <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex align-items-center gap-2">
                                <i class="bi bi-briefcase" style="color:#272f54;"></i>
                                <h6 class="fw-bold mb-0" style="color:#272f54;">Recent Internship Postings</h6>
                                <span class="badge ms-auto rounded-pill"
                                    style="background:#eef1ff; color:#272f54; font-size:11px;">
                                    Latest 5
                                </span>
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
                                                        <td>
                                                            <?= htmlspecialchars($ri['company']) ?>
                                                        </td>
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
                    </div>
                </div>
            </div>
            <div id="postings" class="section">
                <h2>Intership Posting</h2>
                <form method="POST" action="internship-db.php" class="internship-form"
                    onsubmit="return confirm('You are creating a new internship posting. Are you sure?');">
                    <input type="hidden" name="form_type" value="internship_posting">
                    <div class="form-card">
                        <h3>Basic Information</h3>
                        <div class="form-grid">
                            <input type="text" name="title" placeholder="Title" required>
                            <input type="text" name="company" placeholder="Company Name" required>
                            <input type="text" name="location" placeholder="Location" required>
                            <select name="program" id="program" required>
                                <option value="" disabled selected>Select Program</option>
                                <option value="Information Technology">Information Technology</option>
                                <option value="Civil Engineering">Civil Engineering</option>
                                <option value="Electrical Engineering">Electrical Engineering</option>
                            </select>
                            <select name="year" id="year" required>
                                <option value="" disabled selected>Select Contract Duration</option>
                                <option value="1">1 year</option>
                                <option value="2">2 years</option>
                                <option value="3">3 years</option>
                                <option value="4">4 years</option>
                                <option value="5">5 years</option>
                            </select>

                        </div>
                    </div>

                    <div class="form-card">
                        <h3>Contact Information</h3>
                        <div class="form-grid">
                            <input type="email" name="email" placeholder="Contact Email">
                            <input type="tel" name="phonenumber" placeholder="Contact Number">
                            <input type="date" name="deadline" placeholder="Application Deadline">

                            <textarea name="description" placeholder="Description" required></textarea>
                        </div>
                        <label for="openTime">Opening Time</label>
                        <input type="time" name="openTime" placeholder="Opening Time">
                        <label for="closeTime">Closing Time</label>
                        <input type="time" name="closeTime" placeholder="Closing Time">
                    </div>

                    <div class="form-card">
                        <h3>Location Information</h3>
                        <p class="text-muted" style="font-size:13px;">Click on the map to pin the internship location.
                        </p>

                        <div id="posting-map"
                            style="width:100%; height:350px; border-radius:10px; border:1px solid #dee2e6;"></div>

                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label">Latitude</label>
                                <input type="text" name="latitude" id="post-lat" class="form-control"
                                    placeholder="Click map to set" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Longitude</label>
                                <input type="text" name="longitude" id="post-lng" class="form-control"
                                    placeholder="Click map to set" readonly>
                            </div>
                        </div>

                        <div id="pin-label" class="d-none mt-2 p-2">
                            <span class="p-1 rounded text-bg-success">
                                <i class="bi bi-geo-alt-fill"></i> Location pinned - drag the marker or click to a new
                                location to adjust
                            </span>
                        </div>
                    </div>


                    <button type="submit" class="submit-btn">Create Internship Postings</button>
                </form>
            </div>

            <div id="applicants" class="section">
                <h2 style="margin-bottom: 30px;">Applicants</h2>
                <div class="table-controls">
                    <div class="filters">
                        <select class="filter-select">
                            <option>Status</option>
                            <option>New Application</option>
                            <option>Placement Confirmed</option>
                            <option>Interviewing</option>
                            <option>Waitlisted</option>
                        </select>
                        <select class="filter-select">
                            <option>Requirements</option>
                            <option>Complete</option>
                            <option>Incomplete</option>
                        </select>
                        <select class="filter-select">
                            <option>Programs</option>
                            <option>Information Technology</option>
                            <option>Civil Engineering</option>
                            <option>Electrical Engineering</option>
                        </select>
                    </div>
                    <div class="search-box">
                        <input type="text" placeholder="Search">
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
                                <td><span class="clickable-text">Placements Confirmed</span></td>
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

            <div id="documents" class="section">
                <h2 style="margin-bottom: 30px;">Documents</h2>
                <div class="table-controls">
                    <div class="filters">
                        <select class="filter-select">
                            <option>Document Type</option>
                            <option>Resume</option>
                            <option>Portfolio</option>
                            <option>Recommendation Letter</option>
                        </select>
                        <select class="filter-select">
                            <option>Programs</option>
                            <option>Information Technology</option>
                            <option>Civil Engineering</option>
                            <option>Electrical Engineering</option>
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

            <div id="interested" class="section">
                <h2>Interested</h2>

                <div class="form-card">

                    <div class="mb-3">
                        <input type="text" id="search-interested" class="form-control"
                            placeholder="Search by name, email, company...">
                    </div>
                    <table style="width:100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align:left; border-bottom:1px solid #ddd;">
                                <th>Student</th>
                                <th>Email</th>
                                <th>Company</th>
                                <th>Internship</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody id="interested-tbody">
                            <?php foreach ($interests as $i): ?>
                                <tr style="border-bottom:1px solid #eee;">

                                    <td>
                                        <?= htmlspecialchars($i['full_name']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($i['email']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($i['company']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($i['title']) ?>
                                    </td>

                                    <td>
                                        <form method="POST" action="internship-db.php">
                                            <button type="button" class="btn btn-sm btn-danger"
                                                onclick="openFeedbackModal(<?= $i['interest_id'] ?>)"
                                                style="background:#FF5C5C;color:white;border:none;padding:6px 10px;border-radius:6px;font-weight:600; font-size:13px;cursor:pointer;">
                                                <i class="bi bi-x-circle me-1"></i>
                                                Reject with Feedback
                                            </button>
                                        </form>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        </tbody>

                    </table>

                </div>
            </div>
            <div id="announcements" class="section">
                <h2>Post Announcements</h2>

                <form action="internship-db.php" method="POST" class="internship-form">
                    <input type="hidden" name="form_type" value="announcement_posting">
                    <div class="form-card">
                        <h3>Announcement Details</h3>
                        <div class="form-grid">
                            <input type="text" name="title" placeholder="Title" required>
                        </div>
                        <div class="form-grid mt-3">
                            <textarea name="message" placeholder="Message" required></textarea>
                        </div>
                        <div class="form-grid mt-3">
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
            <div id="manage_announcement" class="section">
                <h2 class="mb-4">Manage Announcements</h2>

                <div class="card shadow-sm">
                    <div class="card-body">

                        <div class="mb-3">
                            <input type="text" id="search-announcements" class="form-control"
                                placeholder="Search announcements...">
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">

                                <thead class="table-dark">
                                    <tr>
                                        <th>Title</th>
                                        <th>Message</th>
                                        <th>Category</th>
                                        <th>Date</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>

                                <tbody id="manage-announcements-tbody">
                                    <?php if (empty($announcements)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4">
                                                No announcements yet.
                                            </td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php foreach ($announcements as $a): ?>
                                        <tr>
                                            <form method="POST" action="internship-db.php">
                                                <td class="fw-bold">
                                                    <input type="text" name="title" value="<?= $a['title'] ?>" required>
                                                </td>

                                                <td>
                                                    <input type="text" name="message" value="<?= $a['message'] ?>" required>
                                                </td>

                                                <td>
                                                    <select name="category" id="category" required>
                                                        <option value="news" <?= $a['category'] === 'news' ? 'selected' : '' ?>>News</option>
                                                        <option value="updates" <?= $a['category'] === 'updates' ? 'selected' : '' ?>>Updates</option>
                                                        <option value="FAQs" <?= $a['category'] === 'FAQs' ? 'selected' : '' ?>>FAQs</option>
                                                    </select>
                                                </td>

                                                <td>
                                                    <?= date("M d, Y", strtotime($a['created_at'])) ?>
                                                </td>

                                                <td class="text-center">

                                                    <!-- DELETE -->
                                                    <input type="hidden" name="announcement_id" value="<?= $a['id'] ?>">
                                                    <button type="submit" name="delete_announcement"
                                                        class="btn btn-sm btn-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>

                                                    <!-- EDIT -->
                                                    <input type="hidden" name="announcement_id" value="<?= $a['id'] ?>">
                                                    <button type="submit" name="edit_announcement"
                                                        class="btn btn-sm btn-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                            </form>
                                            </td>

                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>

                            </table>
                        </div>

                    </div>
                </div>
            </div>
            <div id="student_register" class="section">
                <h2>Student Register</h2>
                <div class="form-card">

                    <!-- View/Edit CSV -->
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

                    <hr style="border-color:#eee;">

                    <!-- Import CSV -->
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

                    <hr style="border-color:#eee;">

                    <!-- Download CSV -->
                    <div class="mt-3">
                        <h5 class="fw-semibold mb-1" style="color:#272f54;">Download Current CSV</h5>
                        <p class="text-muted small mb-2">Download the current student registration file.</p>
                        <div class="text-end">
                            <a href="download-csv.php"
                                style="display:inline-flex; align-items:center; gap:6px; background: linear-gradient(135deg, #FFB62F, #E4572E); color:white; border:none; padding:8px 16px; border-radius:8px; font-weight:600; font-size:13px; text-decoration:none;">
                                <i class="bi bi-download"></i> Download CSV
                            </a>
                        </div>
                    </div>

                </div>
            </div>
            <div id="edit_csv" class="section">
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
                            <button type="submit" class="submit-btn">Save CSV</button>
                            <button type="button" class="submit-btn btn-danger"
                                onclick="showSection('student_register')">
                                Back
                            </button>
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

            <h5 style="color:white; text-align:center; margin-bottom:25px; font-weight:400;">
                Reject with Feedback
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

        // Announcements search (manage_announcement)
        document.getElementById('search-announcements').addEventListener('input', function () {
            const query = this.value.toLowerCase();
            document.querySelectorAll('#manage-announcements-tbody tr').forEach(row => {
                // collect text from inputs, selects, and plain td text
                const text = Array.from(row.querySelectorAll('input, select, td'))
                    .map(el => {
                        if (el.tagName === 'INPUT' || el.tagName === 'SELECT') {
                            return el.value;
                        }
                        return el.innerText;
                    })
                    .join(' ')
                    .toLowerCase();

                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
        // Interested search
        document.getElementById('search-interested').addEventListener('input', function () {
            const query = this.value.toLowerCase();
            document.querySelectorAll('#interested-tbody tr').forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(query) ? '' : 'none';
            });
        });

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