<?php
$page = 'profile';
require 'auth.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Personal Information | CEE IT Connects</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../CSS/index-style.css">

    <style>
        body {
            padding-top: 80px;
            z-index: 1000;
        }

        .page-title {
            font-weight: 600;
            color: #1f2a44;
        }

        .profile-container {
            background: #fff;
            border-radius: 30px;
            border: 2px solid #ffb703;
            padding: 80px 40px 40px;
            position: relative;
        }

        .profile-pic {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            background: #fff;
            border: 3px solid #ffb703;
            position: absolute;
            top: -65px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: #999;
        }

        .form-label {
            font-size: 13px;
            color: #ff6b00;
            font-weight: 600;
        }

        .form-control {
            border-radius: 4px;
            border: 1px solid #ff6b00;
            font-size: 14px;
        }

        .btn-save {
            background: #1f2a44;
            color: #fff;
            padding: 8px 20px;
            border-radius: 6px;
        }
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <a href="index.php"><i class="fa fa-arrow-left"></i></a>
            <h5 class="page-title mb-0">Edit Personal Information</h5>
        </div>

        <div class="profile-container mx-auto">
            <div class="profile-pic">
                <i class="fa fa-user"></i>
            </div>

            <form>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">First Name</label>
                        <input type="text" class="form-control" value="FIRST NAME">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Middle Name</label>
                        <input type="text" class="form-control" value="MIDDLE NAME">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control" value="LAST NAME">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Suffix</label>
                        <input type="text" class="form-control" value="SUFFIX">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Student ID</label>
                        <input type="text" class="form-control" value="23-3333">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Contact</label>
                        <input type="text" class="form-control" value="09123456789">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" value="studentName@gmail.com">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Gender</label>
                        <input type="text" class="form-control" value="MALE">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Age</label>
                        <input type="text" class="form-control" value="21">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Program</label>
                        <input type="text" class="form-control" value="BS INFORMATION TECHNOLOGY">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Address</label>
                        <input type="text" class="form-control" value="123 Maysan, Tongo St., Valenzuela City">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Birth Month</label>
                        <input type="text" class="form-control" value="December">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Day</label>
                        <input type="text" class="form-control" value="03">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Year</label>
                        <input type="text" class="form-control" value="2004">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Guardian / Parent Name</label>
                        <input type="text" class="form-control" value="FULL NAME">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Relationship</label>
                        <input type="text" class="form-control" value="MOTHER">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Guardian Contact</label>
                        <input type="text" class="form-control" value="09987654321">
                    </div>
                </div>

                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-save">Apply Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>