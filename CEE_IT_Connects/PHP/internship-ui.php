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
        .internship-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
            width: 50vw;
            max-width: 500px;
        }
    </style>
</head>

<body data-page="rooms">

    <?php include 'navbar.php'; ?>

    <div class="page-body">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <a href="dashboard.php" class="active">
                <i class="bi bi-person-fill-lock"></i>
                Dashboard
            </a>
            <a href="postings.php">
                <i class="bi bi-pencil-fill"></i>
                Postings
            </a>
            <a href="applicants.php">
                <i class="bi bi-people-fill"></i>
                Applicants
            </a>
            <a href="documents.php">
                <i class="bi bi-file-earmark-text-fill"></i>
                Documents
            </a>
            <a href="bookmarks.php">
                <i class="bi bi-bookmarks-fill"></i>
                Bookmarks
            </a>

            </a>
        </aside>
        <div class="main-content">
            <div class="postings">
                <h2>Postings</h2>
                <form action="internship-db.php" method="POST" class="internship-form">
                    <input type="text" name="title" placeholder="Title">
                    <input type="text" name="company" placeholder="Company Name">
                    <input type="email" name="email" placeholder="Contact Email">
                    <input type="text" name="location" placeholder="Location">
                    <textarea name="description" placeholder="Description"></textarea>
                    <input type="text" name="program" placeholder="Program">
                    <input type="text" inputmode="decimal"
                        pattern="^(\+|-)?(?:90(?:(?:\.0{1,8})?)|(?:[0-8]?\d(?:(?:\.\d{1,8})?)))$"
                        placeholder="Latitude e.g 24.0123912" name="latitude">
                    <input type="text" inputmode="decimal"
                        pattern="^(\+|-)?(?:180(?:(?:\.0{1,8})?)|(?:1[0-7]\d(?:(?:\.\d{1,8})?)|(?:[1-9]?\d(?:(?:\.\d{1,8})?))))$"
                        placeholder="Longitude e.g 120.0123912" name="longitude">
                    <button type="submit">Submit</button>
                </form>
            </div>
        </div>
    </div>

    <script src="../JS/script.js"></script>
</body>

</html>