<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CEE IT Connects</title>
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
            </a>
        </aside>
        <div class="main-content">
            <div id="dashboard" class="section active">

            </div>
            <div id="postings" class="section">
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
            </div>

            <div id="applicants" class="section">
                <h2>Applicants</h2>

            </div>

            <div id="documents" class="section">
                <h2>Documents</h2>

            </div>

            <div id="bookmarks" class="section">
                <h2>Bookmarks</h2>

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
                    </div>

                    <button type="submit" class="submit-btn">Post Announcement</button>

                </form>
            </div>
        </div>
    </div>

    <script src="../JS/script.js"></script>
</body>

</html>