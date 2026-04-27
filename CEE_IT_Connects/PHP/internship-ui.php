<?php session_start();
require 'db.php';

$stmtbookmark = $pdo->prepare("
    SELECT 
        ib.id AS bookmark_id,
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

$stmtbookmark->execute();
$bookmarks = $stmtbookmark->fetchAll(PDO::FETCH_ASSOC);

$stmtannouncement = $pdo->prepare("
SELECT id, title, message, created_at, category 
FROM announcements
ORDER BY created_at DESC");
$stmtannouncement->execute();
$announcements = $stmtannouncement->fetchAll(PDO::FETCH_ASSOC);
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
            <a href="#" onclick="showSection('bookmarks')">
                <i class="bi bi-bookmarks-fill"></i>
                Bookmarks
            </a>
            <a href="#" onclick="showSection('announcements')">
                <i class="bi bi-bell-fill"></i>
                Announcements
            </a>
            <a href="#" onclick="showSection('manage_announcement')">
                <i class="bi bi-bookmark"></i>
                Manage Announcement
            </a>
        </aside>
        <div class="main-content">
            <div id="dashboard" class="section active">

            </div>
            <di id="postings" class="section">
                <h2>Intership Posting</h2>
                <form method="POST" action="internship-db.php" class="internship-form">
                    <input type="hidden" name="form_type" value="internship_posting">
                    <div class="form-card">
                        <h3>Basic Information</h3>
                        <div class="form-grid">
                            <input type="text" name="title" placeholder="Title" required>
                            <input type="text" name="company" placeholder="Company Name" required>
                            <input type="text" name="location" placeholder="Location">
                            <select name="program" id="program" required>
                                <option value="" disabled selected>Select Program</option>
                                <option value="Information Technology">Information Technology</option>
                                <option value="Civil Engineering">Civil Engineering</option>
                                <option value="Electrical Engineering">Electrical Engineering</option>
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
                        <div class="form-grid">
                            <input type="text" inputmode="decimal"
                                pattern="^(\+|-)?(?:90(?:(?:\.0{1,8})?)|(?:[0-8]?\d(?:(?:\.\d{1,8})?)))$"
                                placeholder="Latitude e.g 24.0123912" name="latitude">
                            <input type="text" inputmode="decimal"
                                pattern="^(\+|-)?(?:180(?:(?:\.0{1,8})?)|(?:1[0-7]\d(?:(?:\.\d{1,8})?)|(?:[1-9]?\d(?:(?:\.\d{1,8})?))))$"
                                placeholder="Longitude e.g 120.0123912" name="longitude">
                        </div>
                    </div>


                    <button type="submit" class="submit-btn">Create Internship Postings</button>
                </form>
            </di v>

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

            <div id="bookmarks" class="section">
                <h2>Bookmarks</h2>

                <div class="form-card">

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

                        <tbody>
                            <?php foreach ($bookmarks as $b): ?>
                                <tr style="border-bottom:1px solid #eee;">

                                    <td>
                                        <?= htmlspecialchars($b['full_name']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($b['email']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($b['company']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($b['title']) ?>
                                    </td>

                                    <td>
                                        <form method="POST" action="internship-db.php">
                                            <input type="hidden" name="bookmark_id" value="<?= $b['bookmark_id'] ?>">
                                            <button type="submit" name="reject"
                                                style="background:red;color:white;border:none;padding:6px 10px;border-radius:6px;">
                                                Reject
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

                <for m action="internship-db.php" method="POST" class="internship-form">
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

                </for>
            </div>
            <div id="manage_announcement" class="section">
                <h2 class="mb-4">Manage Announcements</h2>

                <div class="card shadow-sm">
                    <div class="card-body">

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

                                <tbody>
                                    <?php if (empty($announcements)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4">
                                                No announcements yet.
                                            </td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php foreach ($announcements as $a): ?>
                                        <tr>

                                            <td class="fw-bold">
                                                <?= htmlspecialchars($a['title']) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars(substr($a['message'], 0, 60)) ?>...
                                            </td>

                                            <td>
                                                <span class="badge bg-warning text-dark p-2">
                                                    <?= htmlspecialchars($a['category']) ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?= date("M d, Y", strtotime($a['created_at'])) ?>
                                            </td>

                                            <td class="text-center">

                                                <!-- DELETE -->
                                                <form method="POST" action="internship-db.php" class="d-inline">
                                                    <input type="hidden" name="announcement_id" value="<?= $a['id'] ?>">
                                                    <button type="submit" name="delete_announcement"
                                                        class="btn btn-sm btn-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>

                                                <!-- EDIT -->
                                                <button class="btn btn-sm btn-primary"
                                                    onclick="editAnnouncement(<?= $a['id'] ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>

                                            </td>

                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>

                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../JS/script.js"></script>
</body>

</html>