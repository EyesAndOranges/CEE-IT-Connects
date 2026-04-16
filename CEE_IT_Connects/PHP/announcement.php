<?php $page = 'announcements';
require 'auth.php'; ?>

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

                <!-- NEWS & UPDATES -->
                <div class="announcement-section">
                    <h5 class="section-title">News & Updates</h5>

                    <div class="news-card">
                        <h6>CHED Internship Application Open</h6>
                        <p class="mb-1">Students may now apply for CHED partner internships.</p>
                        <small class="text-muted">Posted October 6, 2025</small>
                    </div>

                    <div class="news-card">
                        <h6>Scholarship Submission Reminder</h6>
                        <p class="mb-1">Deadline for scholarship documents is approaching.</p>
                        <small class="text-muted">Posted October 2, 2025</small>
                    </div>
                </div>

                <!-- UPDATES -->
                <div class="announcement-section">
                    <h5 class="section-title">Updates</h5>

                    <div class="update-card">
                        <img src="../Sources/suhay husay.png">
                        <div>
                            <h6 class="fw-bold">On-the-Job Training Orientation</h6>
                            <p class="mb-1">Mandatory orientation for incoming interns.</p>
                            <small class="text-muted">CEE IT Office</small>
                        </div>
                    </div>

                    <div class="update-card">
                        <img src="../Sources/suhay husay.png">
                        <div>
                            <h6 class="fw-bold">Partner Companies Expanded</h6>
                            <p class="mb-1">New industry partners added this semester.</p>
                            <small class="text-muted">CEE IT Connects</small>
                        </div>
                    </div>
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

                        <div class="accordion-item mb-2">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fw-semibold" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#faq1">
                                    Who can apply for internships?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    All enrolled CEE IT students who have met the required academic
                                    and departmental requirements may apply for internship opportunities.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item mb-2">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fw-semibold" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#faq2">
                                    What documents are required?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Students are required to submit a resume, application form,
                                    endorsement letter, and other documents specified by the partner institution.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item mb-2">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fw-semibold" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#faq3">
                                    How long is the internship period?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Internship duration depends on the program requirements
                                    and typically ranges from 300 to 600 hours.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item mb-2">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fw-semibold" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#faq4">
                                    Can I apply to multiple companies?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes. Students may apply to multiple internship listings,
                                    but acceptance is subject to approval and availability.
                                </div>
                            </div>
                        </div>

                    </div>
                </div>


            </div>
        </div>
    </section>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/index-script.js"></script>
</body>

</html>