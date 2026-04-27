<?php $page = 'announcements';
require 'db.php';
require 'auth.php';

$stmt = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC");

$announcements = [
    'news' => [],
    'updates' => [],
    'FAQs' => []
];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (!isset($announcements[$row['category']])) {
        $announcements[$row['category']] = [];
    }
    $announcements[$row['category']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements | CEE IT Connects</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../CSS/index-style.css" rel="stylesheet">

    <style>
        body {
            background: #f4f4f4;
        }

        .border-primary:hover {
            --bs-btn-hover-bg: #c5d9f8;
            transform: scale(1.09);
        }

        /* Page height control */
        .announcement-wrapper {
            height: calc(100vh - 70px);
            padding: 25px;
        }

        /* Scrollable content */
        .announcement-scroll {
            height: 100%;
            overflow-y: auto;
            padding-right: 10px;
        }

        /* Section cards */
        .announcement-section {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .section-title {
            font-weight: 800;
            color: #272f54;
            margin-bottom: 15px;
        }

        /* News cards */
        .news-card {
            border: 1px solid #eee;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 10px;
        }

        .news-card h6 {
            color: #ff6a00;
            font-weight: 700;
        }

        /* Update cards with image */
        .update-card {
            display: flex;
            gap: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .update-card img {
            width: 120px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }

        /* Internship tips icons */
        .tip-box {
            text-align: center;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 6px;
        }

        .tip-box i {
            font-size: 28px;
            color: #ff6a00;
            margin-bottom: 8px;
        }

        /* FAQ */
        .faq-item {
            background: #ff6a00;
            color: #fff;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 8px;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>

    <section class="announcement-wrapper">
        <div class="container-fluid h-100">

            <h4 class="fw-bold mb-3">Announcements</h4>

            <div class="announcement-scroll">

                <!-- NEWS -->
                <div class="announcement-section">
                    <h5 class="section-title">News</h5>
                    <?php foreach ($announcements['news'] as $news): ?>
                        <div class="news-card">
                            <h6><?php echo htmlspecialchars($news['title']); ?></h6>
                            <p class="short-message" id="shortMessage<?php echo $news['id']; ?>"><?php
                               $message = htmlspecialchars($news['message']);
                               echo strlen($message) > 10 ? substr($message, 0, 10) . '...' : $message;
                               ?></p>

                            <div class="collapse" id="fullMessage<?php echo $news['id']; ?>">
                                <p><?php echo $message ?></p>
                            </div>
                            <!-- <p class="mb-1"><?php //echo htmlspecialchars($news['message']) ?></p> -->
                            <small class="text-muted">Posted <?php echo date(
                                'F j, y',
                                strtotime($news['created_at'])
                            ) ?></small>
                            <div class="d-flex justify-content-end mt-2">
                                <button class="btn border-primary" id="toggleButton<?php echo $news['id'] ?>"
                                    onclick="toggleMessage(<?php echo $news['id'] ?>)">
                                    <?php echo strlen($message) > 10 ? 'Read More' : 'Read Less'; ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- UPDATES -->
                <div class=" announcement-section">
                    <h5 class="section-title">Updates</h5>
                    <?php foreach ($announcements['updates'] as $update): ?>
                        <div class="update-card">
                            <img src="../Sources/suhay husay.png">
                            <div>
                                <h6 class="fw-bold"><?php echo htmlspecialchars($update['title']) ?></h6>
                                <p class="mb-1"><?php echo htmlspecialchars($update['message']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- INTERNSHIP TIPS -->
                <div class="announcement-section">
                    <h5 class="section-title">Internship Tips</h5>

                    <!-- <div class="row g-3">
            <div class="col-md-3 col-6">
                <div class="tip-box">
                    <i class="fa-solid fa-clock"></i>
                    <p class="mb-0">Submit On Time</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="tip-box">
                    <i class="fa-solid fa-user-tie"></i>
                    <p class="mb-0">Professional Resume</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="tip-box">
                    <i class="fa-solid fa-laptop"></i>
                    <p class="mb-0">Prepare Documents</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="tip-box">
                    <i class="fa-solid fa-users"></i>
                    <p class="mb-0">Attend Briefings</p>
                </div>
            </div> 
        </div> -->
                </div>

                <!-- FAQ -->
                <div class="announcement-section">
                    <h5 class="section-title">FAQs</h5>

                    <div class="accordion" id="faqAccordion">
                        <?php foreach ($announcements['FAQs'] as $faq): ?>
                            <div class="accordion-item mb-2">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed fw-semibold" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#faq">
                                        <?php echo htmlspecialchars($faq['title']) ?>
                                    </button>
                                </h2>
                                <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <?php echo htmlspecialchars($faq['message']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>


            </div>
        </div>
    </section>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/index-script.js"></script>

    <script>
        function toggleMessage(id) {
            const shortMsg = document.getElementById(`shortMessage${id}`);
            const fullMsg = document.getElementById(`fullMessage${id}`);
            const button = document.getElementById(`toggleButton${id}`);

            if (fullMsg.classList.contains('show')) {
                shortMsg.style.display = 'block';
                button.innerHTML = 'Read More'
            } else {
                shortMsg.style.display = 'none';
                button.innerHTML = 'Read Less'
            }

            fullMsg.classList.toggle('show');
        }
    </script>
</body>

</html>