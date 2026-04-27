<?php
require 'db.php';
require 'auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MOU Draft | CEE IT Connects</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #dcdcdc;
            font-family: "Times New Roman", serif;
        }

        .paper {
            background: #fff;
            max-width: 750px;
            margin: 60px auto;
            padding: 60px 70px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            line-height: 1.7;
        }

        .title {
            text-align: center;
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 20px;
            letter-spacing: 1px;
        }

        .subtitle {
            text-align: center;
            font-size: 14px;
            margin-bottom: 40px;
        }

        .center-text {
            text-align: center;
        }

        .mou-input {
            border: none;
            border-bottom: 1px solid #000;
            text-align: center;
            font-style: italic;
            min-width: 220px;
            padding: 2px 6px;
            outline: none;
        }

        .mou-inline {
            text-align: center;
            margin: 20px 0;
        }

        .paragraph {
            text-align: justify;
            font-size: 14px;
            margin-top: 30px;
        }

        .signature-section {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
        }

        .signature {
            width: 45%;
            text-align: center;
        }

        .signature input {
            border: none;
            border-bottom: 1px solid #000;
            width: 100%;
            text-align: center;
            margin-bottom: 5px;
        }

        .actions {
            text-align: right;
            margin-top: 30px;
        }

        .btn-custom {
            font-size: 13px;
            padding: 6px 16px;
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="paper">

    <div class="title">MEMORANDUM OF UNDERSTANDING</div>
    <div class="subtitle">ON-THE-JOB TRAINING PROGRAM</div>

    <p class="center-text">This Memorandum of Understanding is entered into between</p>

    <p class="center-text">
        <input type="text" class="mou-input" value="Herold James Elisterio">
    </p>

    <p class="center-text">and</p>

    <p class="center-text">
        <input type="text" class="mou-input" value="XYZ Institution">
    </p>

    <div class="mou-inline">
        <input type="text" class="mou-input" value="Herold James Elisterio">
        and
        <input type="text" class="mou-input" value="XYZ Institution">
        hereby agree to the following:
    </div>

    <p class="paragraph">
        This Memorandum of Understanding establishes a formal agreement regarding the 
        implementation of an On-the-Job Training (OJT) Program. The host company agrees 
        to accept qualified students from Pamantasan ng Lungsod ng Valenzuela as trainees. 
        The program aims to provide practical experience, enhance technical skills, and 
        prepare students for professional employment in their respective fields.
    </p>

    <div class="signature-section">
        <div class="signature">
            <input type="text" value="Herold James Elisterio">
            <small>University Student Representative</small>
        </div>

        <div class="signature">
            <input type="text" value="XYZ Institution">
            <small>Company Representative</small>
        </div>
    </div>

    <div class="actions">
        <button onclick="history.back()" class="btn btn-outline-secondary btn-custom">Back</button>
        <a href="downloads/mou.pdf" class="btn btn-dark btn-custom" download>Download PDF</a>
    </div>

</div>

</body>
</html>