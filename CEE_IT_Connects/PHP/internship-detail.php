<?php
require 'db.php';
require 'auth.php';

if (!isset($_GET['id'])) {
    die("No internship selected.");
}
$page = 'opportunity';
$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM internships WHERE id = ?");
$stmt->execute([$id]);
$internship = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$internship) {
    die("Internship not found.");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>
        <?php echo htmlspecialchars($internship['title']); ?>
    </title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        .back-btn {
            border-radius: 50px;
            padding: 8px 18px;
            font-weight: 500;
            transition: all 0.2s ease-in-out;
        }

        .back-btn:hover {
            background-color: #2c3e67;
            color: white;
            border-color: #2c3e67;
            transform: translateX(-3px);
        }

        .job-card {
            background: #ffffff;
            border: none;
        }

        .info-badge {
            background-color: #eef2ff;
            color: #2c3e67;
            margin-right: 6px;
            margin-bottom: 6px;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 13px;
        }

        .job-description {
            font-size: 16px;
            line-height: 1.7;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-5 pt-5">
        <div class="job-card shadow-lg p-4 rounded-4">

            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h2 class="fw-bold">
                        <?php echo htmlspecialchars($internship['title']); ?>
                    </h2>

                    <p class="text-muted mb-1">
                        <?php echo htmlspecialchars($internship['company']); ?>
                    </p>

                    <small class="text-secondary">
                        <?php echo htmlspecialchars($internship['location']); ?>
                    </small>
                </div>
            </div>

            <hr>

            <div class="job-description">
                <p><?php echo nl2br(htmlspecialchars($internship['description'])); ?></p>
            </div>

            <div class="mt-3">
                <?php if (!empty($internship['program'])): ?>
                    <span class="badge bg-success">Program: <?php echo htmlspecialchars($internship['program']); ?></span>
                <?php endif; ?>

                <?php if (!empty($internship['year_level'])): ?>
                    <span class="badge bg-info">Year: <?php echo htmlspecialchars($internship['year_level']); ?></span>
                <?php endif; ?>

                <?php if (!empty($internship['min_gpa'])): ?>
                    <span class="badge bg-info">Min GPA: <?php echo htmlspecialchars($internship['min_gpa']); ?></span>
                <?php endif; ?>

                <?php if (!empty($internship['deadline'])): ?>
                    <span class="badge bg-danger">Deadline: <?php echo htmlspecialchars($internship['deadline']); ?></span>
                <?php endif; ?>
            </div>

            <a href="applied-internship-programs.php" class="btn btn-dark mt-3 back-btn">
                Back to Listings
            </a>
            <a href="map.php" class="btn btn-dark mt-3 back-btn">
                Go to the map
            </a>
        </div>

</body>

</html>