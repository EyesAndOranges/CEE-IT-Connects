<?php
require 'db.php';
require 'auth.php';

$params = [];
$conditions = [];

if (!empty($_GET['program'])) {
    $conditions[] = "program = :program";
    $params['program'] = $_GET['program'];
}

if (!empty($_GET['year'])) {
    $conditions[] = "year_level = :year";
    $params['year'] = $_GET['year'];
}

if (!empty($_GET['gpa'])) {
    $conditions[] = "min_gpa <= :gpa";
    $params['gpa'] = $_GET['gpa'];
}

if (!empty($_GET['deadline'])) {

    if ($_GET['deadline'] == "week") {
        $conditions[] = "deadline BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '7 days'";
    }

    if ($_GET['deadline'] == "month") {
        $conditions[] = "deadline BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '30 days'";
    }

    if ($_GET['deadline'] == "future") {
        $conditions[] = "deadline >= CURRENT_DATE";
    }

}

if (!empty($_GET['internship_type'])) {
    $conditions[] = "internship_type = :internship_type";
    $params['internship_type'] = $_GET['internship_type'];
}

if (!empty($_GET['company_classification'])) {
    $conditions[] = 'company_classification = :company_classification';
    $params['company_classification'] = $_GET['company_classification'];
}
$sql = "SELECT * FROM internships";

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$internships = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php $page = 'opportunity'; ?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internships | CEE IT Connects</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../CSS/index-style.css">

    <style>
        .listing-wrapper {
            background: #f4f4f4;
            padding: 40px 0;
        }

        .filter-box {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 16px;
        }

        .filter-box h6 {
            font-weight: 700;
            margin-bottom: 10px;
        }

        .listing-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 18px;
            margin-bottom: 15px;
        }

        .listing-card h6 {
            color: #ff3d00;
            font-weight: 800;
        }

        .badge-date {
            font-size: 12px;
            color: #777;
        }

        .btn-read {
            background: #ff6a00;
            color: #fff;
            font-weight: 600;
        }

        .btn-apply {
            background: #272f54;
            color: #fff;
            font-weight: 600;
        }

        .btn-clear {
            background: #6c757d;
            color: #fff;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>

    <section class="listing-wrapper">
        <div class="container-fluid px-5">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold">Intership</h4>


                <!-- Put a textfield here dumbass -->
                <!-- <button class="btn btn-dark">Search</button> -->
            </div>

            <div class="row">

                <div class="col-lg-3">
                    <form method="GET">
                        <!-- <div class="search-box">
                            <input type="text" id="searchInput" placeholder="Search for an internship listing"
                                onkeyup="filterListings()">
                        </div> -->
                        <div class="filter-box">

                            <h6>Filters</h6>

                            <div class="d-grid gap-2 mb-3">
                                <button type="submit" class="btn btn-dark">Apply Filters</button>
                                <a href="applied-Internship-programs.php" class="btn btn-dark">Clear Filters</a>
                            </div>

                            <div class="mb-3">
                                <strong>Program</strong>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="program"
                                        value="Information Technology" <?php if (isset($_GET['program']) && $_GET['program'] == "Information Technology")
                                            echo "checked"; ?>>
                                    Information Technology
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="program"
                                        value="Civil Engineering" <?php if (isset($_GET['program']) && $_GET['program'] == "Civil Engineering")
                                            echo "checked"; ?>>
                                    Civil Engineering
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="program"
                                        value="Electrical Engineering" <?php if (isset($_GET['program']) && $_GET['program'] == "Electrical Engineering")
                                            echo "checked"; ?>>
                                    Electrical Engineering
                                </div>
                            </div>

                            <div class="mb-3">
                                <strong>Deadline</strong>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="deadline" value="week" <?php if (isset($_GET['deadline']) && $_GET['deadline'] == "week")
                                        echo "checked"; ?>>
                                    Due this week
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="deadline" value="month" <?php if (isset($_GET['deadline']) && $_GET['deadline'] == "month")
                                        echo "checked"; ?>>
                                    Due this month
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="deadline" value="future" <?php if (isset($_GET['deadline']) && $_GET['deadline'] == "future")
                                        echo "checked"; ?>>
                                    Upcoming
                                </div>
                            </div>

                            <div class="mb-3">
                                <strong>Internship Type</strong>
                                <div>
                                    <input class="form-check-input" type="radio" name="internship_type" value="All"
                                        <?php if (isset($_GET['internship_type']) && $_GET['internship_type'] == "All")
                                            echo "checked"; ?>>
                                    All internship types
                                </div>
                                <div>
                                    <input class="form-check-input" type="radio" name="internship_type" value="paid"
                                        <?php if (isset($_GET['internship_type']) && $_GET['internship_type'] == "paid")
                                            echo "checked"; ?>>
                                    With stipend
                                </div>
                                <div>
                                    <input class="form-check-input" type="radio" name="internship_type" value="unpaid"
                                        <?php if (isset($_GET['internship_type']) && $_GET['internship_type'] == "unpaid")
                                            echo "checked"; ?>>
                                    Without stipend
                                </div>
                            </div>

                            <div class="mb-3">
                                <strong>Company Classification</strong>
                                <div>
                                    <input class="form-check-input" type="radio" name="company_classification"
                                        value="All" <?php if (
                                            isset($_GET['company_classification']) &&
                                            $_GET['company_classification'] == "All"
                                        )
                                            echo "checked" ?>>
                                        All organization
                                    </div>
                                    <div>
                                        <input class="form-check-input" type="radio" name="company_classification"
                                            value="Engineering" <?php if (
                                            isset($_GET['company_classification']) &&
                                            $_GET['company_classification'] == "Engineering"
                                        )
                                            echo "checked" ?>>
                                        Engineering Firm
                                    </div>
                                    <div>
                                        <input class="form-check-input" type="radio" name="company_classification"
                                            value="IT" <?php if (
                                            isset($_GET['company_classification']) &&
                                            $_GET['company_classification'] == "IT"
                                        )
                                            echo "checked" ?>>
                                        IT Company
                                    </div>
                                    <div>
                                        <input class="form-check-input" type="radio" name="company_classification"
                                            value="Industrial company" <?php if (
                                            isset($_GET['company_classification']) &&
                                            $_GET['company_classification'] == "industrial"
                                        )
                                            echo "checked" ?>>
                                        Industrial company
                                    </div>
                                </div>
                            </div>

                        </form>
                    </div>

                    <div class="col-lg-9">

                        <small class="text-muted">
                            Showing <?php echo count($internships); ?> internship listings
                    </small>

                    <?php foreach ($internships as $internship): ?>

                        <div class="listing-card mt-3">

                            <h6><?php echo htmlspecialchars($internship['title']); ?></h6>

                            <p class="badge-date">
                                <?php echo htmlspecialchars($internship['company']); ?> |
                                <?php echo htmlspecialchars($internship['location']); ?>
                            </p>

                            <p class="mb-3">
                                <?php echo nl2br(htmlspecialchars($internship['description'])); ?>
                            </p>

                            <?php if (!empty($internship['program'])): ?>
                                <p><strong>Program:</strong> <?php echo htmlspecialchars($internship['program']); ?></p>
                            <?php endif; ?>

                            <?php if (!empty($internship['internship_type'])): ?>
                                <p><strong>Internship type:</strong>
                                    <?php echo htmlspecialchars($internship['internship_type']); ?>
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($internship['company_classification'])): ?>
                                <p><strong>Company classification:</strong>
                                    <?php echo htmlspecialchars($internship['company_classification']); ?></p>
                            <?php endif; ?>

                            <?php if (!empty($internship['deadline'])): ?>
                                <p><strong>Deadline:</strong> <?php echo htmlspecialchars($internship['deadline']); ?></p>
                            <?php endif; ?>

                            <?php if (!empty($internship['phone_numbers'])): ?>
                                <p><strong>Contact:</strong> <?php echo htmlspecialchars($internship['phone_numbers']); ?></p>
                            <?php endif; ?>

                            <div class="d-flex gap-2">
                                <a href="internship-detail.php?id=<?php echo $internship['id']; ?>" class="btn btn-read">
                                    Read More
                                </a>
                                <form method="POST" action="applied-internship-programs-db.php">
                                    <input type="hidden" name="internship_id" value="<?= $internship['id'] ?>">
                                    <button type="submit" class="btn btn-apply">Interested</button>
                                </form>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>
        </div>
    </section>
    <script>
        function filterListings() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            document.querySelectorAll('.listing').forEach(listing => {
                const title = listing.dataset.title;
                listing.style.display = title.includes(input) ? 'block' : 'none';
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/index-script.js"></script>

</body>

</html>