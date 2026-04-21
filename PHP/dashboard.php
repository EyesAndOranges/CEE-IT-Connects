<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>CEE IT Connects | INTERNSHIP ADMIN</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
    <link rel="stylesheet" href="../CSS/style.css"/>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="page-body">
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
        </aside>
        <main class="main-content">
        </main>
    </div>
    <script src="../JS/script.js"></script>
</body>
</html>