<?php
require 'auth.php';
require 'db.php';

$student_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT step_key, is_done
    FROM student_progress
    WHERE student_id = :student_id
");

$stmt->execute([':student_id' => $student_id]);

$progress = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $progress[$row['step_key']] = $row['is_done'];
}

function isDone($progress, $key)
{
    return !empty($progress[$key]) && (int) $progress[$key] === 1;
}
?>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --brand: #f97316;
            --brand-bg: #e18e2f;
            --gray: #cdcdcd;
            --gray-dark: #6b7280;
            --green: #16a34a;
            --green-border: #40cd71;
            --amber: #d97706;
            --amber-bg: #fef9ec;
            --blue-bg: #eff6ff;
            --blue-border: #bfdbfe;
        }

        body {
            background: #eeecea;
            min-height: 100vh;
        }

        .container {
            max-width: 720px;
            margin: 0 auto;
            padding: 28px 16px 60px;
            margin-top: 70px
        }

        .progress-wrap {
            background: white;
            border: 1px solid var(--gray);
            border-radius: 10px;
            padding: 20px 24px 14px;
            margin-bottom: 16px;
        }

        .progress-bar-steps {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            position: relative;
            margin-bottom: 10px;
        }

        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
            z-index: 1;
        }

        .progress-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 17px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: var(--gray);
            z-index: 0;
        }

        .progress-step.done:not(:last-child)::after {
            background: var(--brand);
        }

        .progress-circle {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            z-index: 1;
            position: relative;
        }

        .is-done {
            background: var(--brand);
            color: white;
        }

        .is-active {
            background: white;
            border: 2px solid var(--brand);
            color: var(--brand);
        }

        .is-locked {
            background: white;
            border: 2px solid var(--gray);
            color: var(--gray-dark);
        }

        .progress-label {
            font-size: 14px;
            margin-top: 20px;
            text-align: center;
        }

        .progress-step.active .progress-label {
            color: var(--brand);
            font-weight: 500;
        }

        .steps {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .step {
            background: white;
            border: 1px solid var(--gray);
            border-radius: 10px;
            overflow: hidden;
            transition: border-color 0.2s;
        }

        .step.is-active {
            border-color: var(--brand);
        }

        .step.is-locked {
            opacity: 0.55;
        }

        .step-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            cursor: pointer;
        }

        .step-header:hover {
            background: var(--gray)
        }

        .step-num {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 500;
            flex-shrink: 0;
        }

        .sn-done {
            background: var(--brand);
            color: white;
        }

        .sn-active {
            background: white;
            border: 2px solid var(--brand);
            color: var(--brand);
        }

        .sn-locked {
            background: var(--gray-);
            color: #374151
        }

        .step-meta {
            flex: 1;
            min-width: 0;
        }

        .step-meta h3 {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 1px;
        }

        .step-meta p {
            font-size: 12px;
            color: var(--gray-dark);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .step-right {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .step.done .step-num {
            background: var(--green);
            color: white;
            border: none;
        }

        .step.done .badge {
            background: var(--green);
            color: white;
        }

        .step.done .step-meta h3 {
            color: var(--green);
        }

        .badge {
            padding: 3px 9px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .b-done {
            background-color: var(--green);
            color: white;
        }

        .b-active {
            background: var(--brand-bg);
            color: var(--brand);
            border: 1px solid var(--brand-bg);
        }

        .b-locked {
            background: var(--gray-dark);
        }

        .b-waiting {
            background: var(--amber-bg);
            color: var(--amber);
        }

        .dropdown {
            font-size: 16px;
            color: var(--gray-dark);
            transition: transform 0.2s;
            display: inline-block;
            line-height: 1;
        }

        .step.is-open .dropdown {
            transform: rotate(180deg);
        }

        .step-body {
            display: none;
            border-top: 1px solid var(--gray);
            padding: 14px 16px;
            background: #fdfdfd;
        }

        .step.is-open .step-body {
            display: block;
        }

        .confirmed-row {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 12px;
            color: var(--green);
            margin-bottom: 6px;
        }

        .screening-info {
            font-size: 12px;
            color: #374151;
            line-height: 1.7;
        }

        .screening-info strong {
            font-weight: 500;
        }

        .info-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 8px;
        }

        .info-row.green {
            border: 1px solid var(--green-border);
        }

        .info-row.blue {
            background: var(--blue-bg);
            border: 1px solid var(--blue-border);
        }

        .info-row-icon {
            font-size: 18px;
            margin-top: 1px;
            flex-shrink: 0;
        }

        .info-row.green .info-row-icon {
            color: var(--green);
        }

        .info-row.blue .info-row-icon {
            color: #2563eb;
        }

        .info-row-text p {
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 2px;
        }

        .info-row-text small {
            font-size: 11px;
            color: var(--gray-dark);
        }

        .info-row.blue .info-row-text p {
            font-size: 12px;
            font-weight: 400;
            color: #1e40af;
            line-height: 1.5;
        }

        .doc-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 12px;
        }

        .doc-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            border: 1px solid var(--gray);
            border-radius: 8px;
            background: white;
        }

        .doc-icon {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }

        .di-done {
            color: var(--green);
        }

        .di-wait {
            background: var(--amber-bg);
            color: var(--amber);
        }

        .di-lock {
            background: var(--gray);
            color: var(--gray-dark);
        }

        .doc-name {
            flex: 1;
            font-size: 13px;
        }

        .doc-status {
            font-size: 11px;
        }

        .ds-done {
            color: var(--green);
        }

        .ds-wait {
            color: var(--amber);
        }

        .ds-lock {
            color: var(--gray-dark);
        }

        .upload-zone {
            border: 1.5px dashed var(--gray);
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.15s, background 0.15s;
        }

        .upload-zone:hover {
            border-color: var(--brand);
            background: var(--gray);
        }

        .upload-zone p {
            font-size: 12px;
            color: var(--gray-dark);
        }

        .upload-zone small {
            font-size: 11px;
            color: var(--gray-dark);
        }

        @media (max-width: 480px) {
            .step-meta p {
                display: none;
            }
        }
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>

    <div class="container">

        <div class="progress-bar-steps">
            <div class="progress-step done">
                <div class="progress-circle is-done"><i class="fa fa-check"></i></div>
                <span class="progress-label">Prepare<br>Resume</span>
            </div>
            <div class="progress-step done">
                <div class="progress-circle is-done"><i class="fa fa-check"></i></div>
                <span class="progress-label">Submit<br>Application</span>
            </div>
            <div class="progress-step done">
                <div class="progress-circle is-done"><i class="fa fa-check"></i></div>
                <span class="progress-label">Attend<br>Screening</span>
            </div>
            <div class="progress-step done">
                <div class="progress-circle is-done"><i class="fa fa-check"></i></div>
                <span class="progress-label">Receive<br>Acceptance</span>
            </div>
            <div class="progress-step active">
                <div class="progress-circle is-active"><i class="fa fa-circle" style="font-size:8px;"></i></div>
                <span class="progress-label">Submit<br>Documents</span>
            </div>
            <div class="progress-step">
                <div class="progress-circle is-locked"></div>
                <span class="progress-label">Confirm<br>Internship</span>
            </div>
        </div>

        <!-- data step 1 -->
        <div class="steps">
            <?php $resumeDone = isDone($progress, 'resume'); ?>
            <div class="step <?= $resumeDone ? 'done is-locked' : '' ?>">
                <div class="step-header" onclick="toggle(this)">
                    <div class="step-num sn-done"><i class="fa fa-check"></i></div>

                    <div class="step-meta">
                        <h3>Prepare resume</h3>
                        <p>Upload your resume to your profile</p>
                    </div>

                    <div class="step-right">
                        <?php if (!empty($progress['application'])): ?>
                            <span class="badge b-done">
                                <i class="fa fa-check"></i> Complete
                            </span>
                        <?php else: ?>
                            <span class="badge b-active">Pending</span>
                        <?php endif; ?>
                        <span class="dropdown"><i class="fa fa-chevron-down"></i></span>
                    </div>
                </div>

                <!-- ONLY ONE BODY -->
                <div class="step-body">

                    <!-- upload trigger -->
                    <div class="upload-zone" <?= $resumeDone ? 'style="pointer-events:none;opacity:0.6;cursor:not-allowed;"' : '' ?> onclick="
                        <?= $resumeDone ? '' : "document.getElementById('resumeInput').click()" ?>">
                        <i class="fa fa-upload"
                            style="font-size:24px;display:block;margin:0 auto 6px;color:#9ca3af;"></i>
                        <p>Click to upload resume</p>
                        <small>PDF only (max 5MB)</small>
                    </div>

                    <!-- hidden actual input -->
                    <form action="student-progress.php" method="POST" enctype="multipart/form-data">
                        <input type="file" id="resumeInput" name="resume" accept=".pdf" hidden required <?= $resumeDone ? 'disabled' : '' ?>>

                        <!-- optional preview -->
                        <div id="resumePreview" class="mt-2 text-sm text-gray-600"></div>

                        <button type="submit" class="btn btn-primary mt-2">
                            Upload Resume
                        </button>
                    </form>

                    <!-- uploaded file preview (optional)
                    <div class="confirmed-row mt-3">
                        <i class="fa fa-file-alt"></i>
                        resume_juan_dela_cruz_v3.pdf uploaded · Apr 10
                    </div>
                    -->
                </div>
            </div>

            <!-- data step 2 -->
            <div class="step <?= !empty($progress['application']) ? 'done' : '' ?>">
                <div class=" step-header" onclick="toggle(this)">
                    <div class="step-num sn-done"><i class="fa fa-check"></i></div>
                    <div class="step-meta">
                        <h3>Submit application</h3>
                        <p>Applied via the onsite application</p>
                    </div>
                    <div class="step-right">
                        <?php if (!empty($progress['application'])): ?>
                            <span class="badge b-done">
                                <i class="fa fa-check"></i> Complete
                            </span>
                        <?php else: ?>
                            <span class="badge b-active">Pending</span>
                        <?php endif; ?>
                        <span class="dropdown"><i class="fa fa-chevron-down"></i></span>
                    </div>
                </div>
                <div class="step-body">

                    <form onsubmit="markStep2Done(event)">

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="application_submitted" value="1"
                                id="appCheck">
                            <label class="form-check-label" for="appCheck">
                                I have submitted my application (physical/email)
                            </label>
                        </div>

                        <button type="submit" class="btn btn-success mt-2">
                            Mark as Done
                        </button>

                    </form>

                </div>
            </div>
            <?php print_r($_SESSION); ?>
            <!-- data step 3 -->
            <div class="step">
                <div class="step-header" onclick="toggle(this)">
                    <div class="step-num sn-done"><i class="fa fa-check"></i></div>
                    <div class="step-meta">
                        <h3>Attend screening</h3>
                        <p>Initial interview / assessment with DevTech</p>
                    </div>
                    <div class="step-right">
                        <?php if (!empty($progress['application'])): ?>
                            <span class="badge b-done">
                                <i class="fa fa-check"></i> Complete
                            </span>
                        <?php else: ?>
                            <span class="badge b-active">Pending</span>
                        <?php endif; ?>
                        <span class="dropdown"><i class="fa fa-chevron-down"></i></span>
                    </div>
                </div>
                <div class="step-body">
                    <div class="screening-info">
                        <strong>Interview date:</strong> Apr 22, 2026 · 2:00 PM via Google Meet<br>
                        <strong>Interviewer:</strong> Ms. Reyes, HR — DevTech
                    </div>
                    <div class="confirmed-row" style="margin-top:10px;"><i class="fa fa-check-circle"></i> Marked
                        attended by Adviser Santos · Apr 22</div>
                </div>
            </div>

            <!-- data step 4 -->
            <div class="step">
                <div class="step-header" onclick="toggle(this)">
                    <div class="step-num sn-done"><i class="fa fa-check"></i></div>
                    <div class="step-meta">
                        <h3>Receive acceptance</h3>
                        <p>Company confirms you are accepted</p>
                    </div>
                    <div class="step-right">
                        <?php if (!empty($progress['application'])): ?>
                            <span class="badge b-done">
                                <i class="fa fa-check"></i> Complete
                            </span>
                        <?php else: ?>
                            <span class="badge b-active">Pending</span>
                        <?php endif; ?>
                        <span class="dropdown"><i class="fa fa-chevron-down"></i></span>
                    </div>
                </div>
                <div class="step-body">
                    <div class="info-row green">
                        <span class="info-row-icon"><i class="fa fa-envelope-open-text"></i></span>
                        <div class="info-row-text">
                            <p>Acceptance letter received from DevTech</p>
                            <small>Confirmed by company contact · Apr 28 · acceptance_devtech.pdf</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- data step 5 -->
            <div class="step is-active is-open">
                <div class="step-header" onclick="toggle(this)">
                    <div class="step-num sn-active"><i class="fa fa-circle"></i></div>
                    <div class="step-meta">
                        <h3>Submit documents</h3>
                        <p>Upload your required internship documents</p>
                    </div>
                    <div class="step-right">
                        <?php if (!empty($progress['application'])): ?>
                            <span class="badge b-done">
                                <i class="fa fa-check"></i> Complete
                            </span>
                        <?php else: ?>
                            <span class="badge b-active">Pending</span>
                        <?php endif; ?>
                        <span class="dropdown"><i class="fa fa-chevron-down"></i></span>
                    </div>
                </div>
                <div class="step-body">
                    <div class="doc-list">
                        <div class="doc-row">
                            <div class="doc-icon di-done"><i class="fa fa-check"></i></div>
                            <span class="doc-name">Recommendation letter</span>
                            <span class="doc-status ds-done">Verified</span>
                        </div>
                        <div class="doc-row">
                            <div class="doc-icon di-done"><i class="fa fa-check"></i></div>
                            <span class="doc-name">MOA — Memorandum of Agreement</span>
                            <span class="doc-status ds-done">Verified</span>
                        </div>
                        <div class="doc-row">
                            <div class="doc-icon di-wait"><i class="fa fa-clock"></i></div>
                            <span class="doc-name">MOU — Memorandum of Understanding</span>
                            <span class="doc-status ds-wait">Under review</span>
                        </div>
                        <div class="doc-row">
                            <div class="doc-icon di-lock"><i class="fa fa-minus"></i></div>
                            <span class="doc-name">Waiver form</span>
                            <span class="doc-status ds-lock">Not uploaded</span>
                        </div>
                    </div>
                    <div class="upload-zone" onclick="handleUpload()">
                        <i class="fa fa-upload"
                            style="font-size:24px;display:block;margin:0 auto 6px;color:#9ca3af;"></i>
                        <p>Upload files here</p>
                        <small>PDF only (max 5 MB)</small>
                    </div>
                </div>
            </div>

            <!-- data step 6 -->
            <div class="step is-locked">
                <div class="step-header">
                    <div class="step-num sn-locked"><i class="fa fa-lock"></i></div>
                    <div class="step-meta">
                        <h3>Confirm internship</h3>
                        <p>Ready to start internship</p>
                    </div>
                    <div class="step-right">
                        <span class="badge b-locked">Locked</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        function toggle(header) {
            const step = header.closest('.step');
            if (step.classList.contains('is-locked')) return;
            step.classList.toggle('is-open');
        }

        function handleUpload() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = '.pdf';
            input.onchange = function (e) {
                const file = e.target.files[0];
                if (!file) return;
                if (file.size > 5 * 1024 * 1024) { alert('File exceeds 5 MB limit.'); return; }
                const zone = document.querySelector('.upload-zone');
                zone.innerHTML = '<p style="color:#16a34a;font-size:13px;font-weight:500;">&#10003; ' + file.name + ' uploaded</p><small style="color:#6b7280;">Awaiting adviser review</small>';
                zone.style.borderColor = '#bbf7d0';
                zone.style.background = '#f0fdf4';
                zone.style.cursor = 'default';
                zone.onclick = null;
                const lockRow = document.querySelector('.doc-list .doc-row:last-child');
                lockRow.querySelector('.doc-icon').textContent = '●';
                lockRow.querySelector('.doc-icon').className = 'doc-icon di-wait';
                lockRow.querySelector('.doc-status').textContent = 'Under review';
                lockRow.querySelector('.doc-status').className = 'doc-status ds-wait';
            };
            input.click();
        }
        document.getElementById('resumeInput').addEventListener('change', function () {
            const file = this.files[0];
            const preview = document.getElementById('resumePreview');

            if (!file) return;

            preview.innerHTML = "Selected file: <b>" + file.name + "</b>";

            const step = this.closest('.step');
            step.classList.add('done');

            const badge = step.querySelector('.badge');
            badge.innerHTML = '<i class="fa fa-check"></i> Complete';
        });
        function markStep2Done(e) {
            e.preventDefault();

            const step = e.target.closest('.step');
            const checkbox = step.querySelector('#appCheck');

            if (!checkbox.checked) {
                alert("Please confirm submission first.");
                return;
            }

            // mark visually done
            step.classList.add('done');

            const badge = step.querySelector('.badge');
            if (badge) {
                badge.innerHTML = '<i class="fa fa-check"></i> Complete';
            }

            // optional: disable form after completion
            step.querySelector('button').disabled = true;
        }
    </script>
</body>

</html>