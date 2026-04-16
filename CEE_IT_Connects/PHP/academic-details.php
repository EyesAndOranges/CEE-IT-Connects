<?php require 'auth.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Details | CEE IT Connects</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../CSS/index-style.css">

    <style>
        body {
            background: #f4f4f4;
        }

        .page-title {
            font-weight: 600;
            color: #1f2a44;
        }

        .academic-container {
            background: #fff;
            border-radius: 30px;
            border: 2px solid #ddd;
            padding: 40px;
        }

        .section-header {
            font-weight: 700;
            color: #1f2a44;
            margin-bottom: 15px;
        }

        .school-level {
            color: #ff6b00;
            font-weight: 700;
            font-size: 14px;
        }

        .school-name {
            font-weight: 600;
            font-size: 14px;
        }

        .detail-text {
            font-size: 13px;
            margin-bottom: 2px;
        }

        .awards li {
            font-size: 13px;
            margin-bottom: 4px;
        }

        hr {
            margin: 25px 0;
        }
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <i class="fa fa-arrow-left"></i>
            <h5 class="page-title mb-0">Academic Details</h5>
        </div>

        <div class="academic-container">
            <div class="row">
                <div class="col-md-8">
                    <h6 class="section-header">Educational Background</h6>

                    <div class="mb-3">
                        <div class="school-level">Elementary</div>
                        <div class="school-name">Maysan Elementary School</div>
                        <div class="detail-text">Address: Maysan Road, Valenzuela City, Metro Manila, Philippines</div>
                        <div class="detail-text">AY: 2011 – 2016</div>
                    </div>

                    <div class="mb-3">
                        <div class="school-level">Junior High School</div>
                        <div class="school-name">Maysan National High School</div>
                        <div class="detail-text">Address: Maysan Road, Valenzuela City, Metro Manila, Philippines</div>
                        <div class="detail-text">AY: 2017 – 2020</div>
                    </div>

                    <div class="mb-3">
                        <div class="school-level">Senior High School</div>
                        <div class="school-name">Maysan National High School</div>
                        <div class="detail-text">Strand: Information Communication Technology (ICT)</div>
                        <div class="detail-text">Address: Maysan Road, Valenzuela City, Metro Manila, Philippines</div>
                        <div class="detail-text">AY: 2021 – 2022</div>
                    </div>

                    <div class="mb-3">
                        <div class="school-level">College</div>
                        <div class="school-name">Pamantasan ng Lungsod ng Valenzuela (PLV)</div>
                        <div class="detail-text">Program: BS Information Technology</div>
                        <div class="detail-text">Year Level: 3rd Year</div>
                        <div class="detail-text">AY: 2023 – Present</div>
                        <div class="detail-text">Student Type: Regular</div>
                        <div class="detail-text">GWA/GPA: GWA/GPA</div>
                        <div class="detail-text">Academic Standing: Good Standing</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <h6 class="section-header">Awards / Honors</h6>
                    <ul class="awards list-unstyled">
                        <li>Perfect Attendance</li>
                        <li>Public Speaker Award</li>
                        <li>With Honors?</li>
                        <br>
                        <li>Perfect Attendance</li>
                        <li>Award for Work Immersion</li>
                        <li>Award for Research</li>
                        <li>With Honors</li>
                    </ul>
                </div>
            </div>

            <hr>

            <h6 class="section-header">Major</h6>
            <p class="detail-text">Major: N/A</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>