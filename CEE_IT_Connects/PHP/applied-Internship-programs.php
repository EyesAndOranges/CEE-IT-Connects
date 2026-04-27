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

        /* ── Download Files Dropdown ── */
        .download-files-panel {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .download-files-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 18px;
            cursor: pointer;
            user-select: none;
            background: #fff;
        }

        .download-files-header span {
            font-weight: 700;
            font-size: 15px;
            color: #1a1a2e;
        }

        .download-files-header .toggle-icon {
            font-size: 13px;
            color: #555;
            transition: transform 0.25s ease;
        }

        .download-files-header.collapsed .toggle-icon {
            transform: rotate(180deg);
        }

        .download-files-body {
            padding: 0 18px 14px 18px;
            border-top: 1px solid #eee;
        }

        .download-file-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .download-file-row:last-child {
            border-bottom: none;
        }

        .download-file-row .file-label {
            font-weight: 600;
            font-size: 14px;
            color: #1a1a2e;
        }

        .download-file-row .file-actions {
            display: flex;
            gap: 8px;
        }

        .btn-show-preview {
            background: #fff;
            color: #272f54;
            border: 1px solid #272f54;
            font-size: 13px;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 4px;
            text-decoration: none;
            transition: background 0.2s, color 0.2s;
        }

        .btn-show-preview:hover {
            background: #272f54;
            color: #fff;
        }

        .btn-download-pdf {
            background: #272f54;
            color: #fff;
            border: none;
            font-size: 13px;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 4px;
            text-decoration: none;
            transition: background 0.2s;
        }

        .btn-download-pdf:hover {
            background: #1a2040;
            color: #fff;
        }
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>

    <section class="listing-wrapper">
        <div class="container-fluid px-5">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold">Internship</h4>
            </div>

            <div class="row">

                <div class="col-lg-3">
                    <form method="GET">
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
                                <strong>Minimum GPA</strong>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="gpa" value="2.00" <?php if (isset($_GET['gpa']) && $_GET['gpa'] == "2.00")
                                        echo "checked"; ?>>
                                    2.0 and above
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="gpa" value="1.50" <?php if (isset($_GET['gpa']) && $_GET['gpa'] == "1.50")
                                        echo "checked"; ?>>
                                    1.5 and above
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="gpa" value="0" <?php if (isset($_GET['gpa']) && $_GET['gpa'] == "0")
                                        echo "checked"; ?>>
                                    No GPA requirement
                                </div>
                            </div>

                            <div class="mb-3">
                                <strong>Year Level</strong>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="year" value="1" <?php if (isset($_GET['year']) && $_GET['year'] == "1")
                                        echo "checked"; ?>>
                                    First Year
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="year" value="2" <?php if (isset($_GET['year']) && $_GET['year'] == "2")
                                        echo "checked"; ?>>
                                    Second Year
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="year" value="3" <?php if (isset($_GET['year']) && $_GET['year'] == "3")
                                        echo "checked"; ?>>
                                    Third Year
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="year" value="4" <?php if (isset($_GET['year']) && $_GET['year'] == "4")
                                        echo "checked"; ?>>
                                    Fourth Year
                                </div>

                            </div>

                        </div>

                    </form>
                </div>

                <div class="col-lg-9">

                    <!-- ── Download Files for Application Panel ── -->
                    <div class="download-files-panel">
                        <div class="download-files-header" id="downloadFilesToggle">
                            <span>Download files for application</span>
                            <i class="fa fa-chevron-up toggle-icon"></i>
                        </div>
                        <div class="download-files-body" id="downloadFilesBody">

                            <div class="download-file-row">
                                <span class="file-label">Memorandum of Understanding (MOU)</span>
                                <div class="file-actions">
                                    <a href="mou-preview.php" class="btn-show-preview">Show Preview</a>
                                    <a href="downloads/mou.pdf" class="btn-download-pdf" download>Download PDF</a>
                                </div>
                            </div>

                            <div class="download-file-row">
                                <span class="file-label">Recommendation Letter</span>
                                <div class="file-actions">
                                    <a href="recommendation-letter-preview.php" class="btn-show-preview">Show Preview</a>
                                    <a href="downloads/recommendation-letter.pdf" class="btn-download-pdf" download>Download PDF</a>
                                </div>
                            </div>

                            <div class="download-file-row">
                                <span class="file-label">Waiver</span>
                                <div class="file-actions">
                                    <a href="waiver-preview.php" class="btn-show-preview">Show Preview</a>
                                    <a href="downloads/waiver.pdf" class="btn-download-pdf" download>Download PDF</a>
                                </div>
                            </div>

                        </div>
                    </div>
                    <!-- ── End Download Files Panel ── -->

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

                            <?php if (!empty($internship['year_level'])): ?>
                                <p><strong>Year Level:</strong> <?php echo htmlspecialchars($internship['year_level']); ?></p>
                            <?php endif; ?>

                            <?php if (!empty($internship['min_gpa'])): ?>
                                <p><strong>Minimum GPA:</strong> <?php echo htmlspecialchars($internship['min_gpa']); ?></p>
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
                                <button class="btn btn-apply">Interested</button>
                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>
        </div>
    </section>

    <script>
        // Toggle Download Files panel
        const toggleBtn = document.getElementById('downloadFilesToggle');
        const toggleBody = document.getElementById('downloadFilesBody');

        toggleBtn.addEventListener('click', function () {
            const isOpen = !toggleBody.classList.contains('d-none');
            if (isOpen) {
                toggleBody.classList.add('d-none');
                toggleBtn.classList.add('collapsed');
            } else {
                toggleBody.classList.remove('d-none');
                toggleBtn.classList.remove('collapsed');
            }
        });

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
