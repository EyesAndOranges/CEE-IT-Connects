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

$studentstmt = $pdo->prepare("SELECT program FROM students WHERE id = ?");
$studentstmt->execute([$_SESSION['user_id']]);
$student = $studentstmt->fetch(PDO::FETCH_ASSOC);
$studentProgram = $student['program'] ?? '';
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


        /* for the download thingy */
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
                            <div class="mb-3">
                                <input type="text" id="search-internship" class="form-control"
                                    placeholder="Search by name, email, company...">
                            </div>
                            <h6>Filters</h6>
                            <!--
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
                            </div> -->

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
                                    <select class="form-select" name="company_classification">
                                        <option value="" selected disabled>Select an option</option>

                                        <option value="private" <?= (($_GET['company_classification'] ?? '') === 'private') ? 'selected' : '' ?>>Private Sector</option>

                                        <option value="public" <?= (($_GET['company_classification'] ?? '') === 'public') ? 'selected' : '' ?>>Public Sector (Government)</option>

                                        <option value="institution" <?= (($_GET['company_classification'] ?? '') === 'institution') ? 'selected' : '' ?>>Academic & Research Institutions
                                        </option>

                                        <option value="NGO" <?= (($_GET['company_classification'] ?? '') === 'NGO') ? 'selected' : '' ?>>Nonprofit & Civil Society
                                        </option>

                                        <option value="multilateral_org" <?= (($_GET['company_classification'] ?? '') === 'multilateral_org') ? 'selected' : '' ?>>International & Multilateral
                                            Organizations
                                        </option>

                                        <option value="media" <?= (($_GET['company_classification'] ?? '') === 'media') ? 'selected' : '' ?>>Creative & Media Sector
                                        </option>

                                        <option value="technology" <?= (($_GET['company_classification'] ?? '') === 'technology') ? 'selected' : '' ?>>Technology & Innovation Sector
                                        </option>

                                        <option value="healthcare" <?= (($_GET['company_classification'] ?? '') === 'healthcare') ? 'selected' : '' ?>>Healthcare & Social Services
                                        </option>

                                        <option value="industrial" <?= (($_GET['company_classification'] ?? '') === 'industrial') ? 'selected' : '' ?>>Industrial & Manufacturing
                                        </option>

                                        <option value="financial" <?= (($_GET['company_classification'] ?? '') === 'financial') ? 'selected' : '' ?>>Financial & Business Services
                                        </option>

                                        <option value="tourism" <?= (($_GET['company_classification'] ?? '') === 'tourism') ? 'selected' : '' ?>>Hospitality & Tourism
                                        </option>

                                        <option value="freelance" <?= (($_GET['company_classification'] ?? '') === 'freelance') ? 'selected' : '' ?>>Freelance / Independent & Gig-Based
                                        </option>

                                        <option value="religious" <?= (($_GET['company_classification'] ?? '') === 'religious') ? 'selected' : '' ?>>Religious & Faith-Based
                                            Organizations
                                        </option>

                                        <option value="hybrid" <?= (($_GET['company_classification'] ?? '') === 'hybrid') ? 'selected' : '' ?>>Hybrid / Public-Private Partnerships
                                        </option>
                                    </select>
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
                        <?php if ($internship['program'] !== $studentProgram)
                            continue; ?>
                        <div class="listing-card mt-3"
                            data-type="<?= htmlspecialchars($internship['internship_type'] ?? '') ?>"
                            data-classification="<?= htmlspecialchars($internship['company_classification'] ?? '') ?>">

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
                                <p data-deadline="<?= htmlspecialchars($internship['deadline']) ?>">
                                    <strong>Deadline:</strong> <?= htmlspecialchars($internship['deadline']) ?>
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($internship['phone_numbers'])): ?>
                                <p><strong>Contact:</strong> <?php echo htmlspecialchars($internship['phone_numbers']); ?></p>
                            <?php endif; ?>

                            <div class="d-flex gap-2">
                                <button class="btn btn-read" onclick="toggleFiles(<?= $internship['id'] ?>)">
                                    Read More
                                </button>
                                <form method="POST" action="applied-internship-programs-db.php">
                                    <input type="hidden" name="internship_id" value="<?= $internship['id'] ?>">
                                    <button type="submit" class="btn btn-apply">Interested</button>
                                </form>

                            </div>
                            <!-- ── Download Files for Application Panel ── -->
                            <div class="download-files-panel  mt-2" style="display:none;"
                                id="files-<?= $internship['id'] ?>">
                                <div class=" download-files-header" id="downloadFilesToggle">
                                    <span>Download files for application</span>
                                    <i class="fa fa-chevron-up toggle-icon"></i>
                                </div>
                                <div class="download-files-body" id="downloadFilesBody">

                                    <div class="download-file-row">
                                        <span class="file-label">Memorandum of Understanding (MOU)</span>
                                        <div class="file-actions">
                                            <a href="mou-preview.php?id=<?= $internship['id'] ?>&student_id=
                                            <?= $_SESSION['user_id'] ?>&action=mou" class="btn-show-preview"
                                                target="_blank">
                                                Show Preview
                                            </a>
                                            <a href="download-mou.php?id=<?= $internship['id'] ?>&action=mou"
                                                class="btn-download-pdf">
                                                Download PDF
                                            </a>
                                        </div>
                                    </div>

                                    <div class="download-file-row">
                                        <span class="file-label">Recommendation Letter</span>
                                        <div class="file-actions">
                                            <a href="mou-preview.php?id=<?= $internship['id'] ?>&student_id=
                                            <?= $_SESSION['user_id'] ?>&action=rl" class="btn-show-preview"
                                                target="_blank">
                                                Show Preview
                                            </a>
                                            <a href="download-mou.php?id=<?= $internship['id'] ?>&action=rl"
                                                class="btn-download-pdf">Download
                                                PDF</a>
                                        </div>
                                    </div>

                                    <div class="download-file-row">
                                        <span class="file-label">Waiver</span>
                                        <div class="file-actions">
                                            <a href="mou-preview.php?id=<?= $internship['id'] ?>&student_id=
                                            <?= $_SESSION['user_id'] ?>&action=waiver" class="btn-show-preview"
                                                target="_blank">
                                                Show Preview
                                            </a>
                                            <a href="download-mou.php?id=<?= $internship['id'] ?>&action=waiver"
                                                class="btn-download-pdf">Download
                                                PDF</a>
                                        </div>
                                    </div>

                                </div>
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
        function toggleFiles(id) {
            const panel = document.getElementById("files-" + id);
            panel.style.display = panel.style.display === "none" ? "block" : "none";
        }

        function applyFilters() {
            const search = document.getElementById('search-internship').value.toLowerCase();
            const deadline = document.querySelector('input[name="deadline"]:checked')?.value ?? '';
            const internshipType = document.querySelector('input[name="internship_type"]:checked')?.value ?? '';
            const companyClass = document.querySelector('select[name="company_classification"]').value ?? '';

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            document.querySelectorAll('.listing-card').forEach(card => {
                const text = card.innerText.toLowerCase();

                // search filter
                const matchSearch = search === '' || text.includes(search);

                // deadline filter
                let matchDeadline = true;
                if (deadline) {
                    const deadlineEl = card.querySelector('[data-deadline]');
                    const deadlineStr = deadlineEl ? deadlineEl.dataset.deadline : '';
                    const deadlineDate = deadlineStr ? new Date(deadlineStr) : null;

                    if (deadlineDate) {
                        const week = new Date(today); week.setDate(today.getDate() + 7);
                        const month = new Date(today); month.setDate(today.getDate() + 30);

                        if (deadline === 'week') matchDeadline = deadlineDate >= today && deadlineDate <= week;
                        if (deadline === 'month') matchDeadline = deadlineDate >= today && deadlineDate <= month;
                        if (deadline === 'future') matchDeadline = deadlineDate >= today;
                    } else {
                        matchDeadline = false;
                    }
                }

                // internship type filter
                const matchType = internshipType === '' || internshipType === 'All'
                    || card.dataset.type === internshipType;

                // company classification filter
                const matchClass = companyClass === '' || card.dataset.classification === companyClass;

                card.style.display = (matchSearch && matchDeadline && matchType && matchClass) ? '' : 'none';
            });
        }

        // attach listeners
        document.getElementById('search-internship').addEventListener('input', applyFilters);
        document.querySelectorAll('input[name="deadline"]').forEach(r => r.addEventListener('change', applyFilters));
        document.querySelectorAll('input[name="internship_type"]').forEach(r => r.addEventListener('change', applyFilters));
        document.querySelector('select[name="company_classification"]').addEventListener('change', applyFilters);

        document.getElementById('search-internship').addEventListener('input', function () {
            const query = this.value.toLowerCase();
            document.querySelectorAll('#internship-tbody tr').forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(query) ? '' : 'none';
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/index-script.js"></script>

</body>

</html>