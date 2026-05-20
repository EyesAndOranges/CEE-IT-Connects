<?php
require 'auth.php';
require 'db.php';

$filter_internship_id = isset($_GET['internship_id']) ? (int) $_GET['internship_id'] : null;
$student_id = (int) $_SESSION['user_id'];

// Fetch the interested
$bookmarkStmt = $pdo->prepare("
    SELECT 
        ib.internship_id,
        i.title,
        i.company,
        i.id
    FROM internship_bookmarks ib
    JOIN internships i ON i.id = ib.internship_id
    WHERE ib.student_id = ?
    " . ($filter_internship_id ? "AND ib.internship_id = $filter_internship_id" : "ORDER BY ib.created_at DESC LIMIT 1") . "
");
$bookmarkStmt->execute([$student_id]);
$bookedInternship = $bookmarkStmt->fetch(PDO::FETCH_ASSOC);
$internship_id = $bookedInternship['id'] ?? null;


// ── Fetch all progress rows for this student 
$stmt = $pdo->prepare("
    SELECT step_key, is_done, file_path, updated_at
    FROM student_progress
    WHERE student_id = :sid
");
$stmt->execute([':sid' => $student_id]);

$progress = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $progress[$row['step_key']] = [
        'done' => (bool) $row['is_done'],
        'file_path' => $row['file_path'],
        'updated_at' => $row['updated_at'],
    ];
}

// ── Fetch uploaded resume 
$resumeStmt = $pdo->prepare("
    SELECT resume_path, uploaded_at
    FROM student_documents
    WHERE student_id = ?
    ORDER BY uploaded_at DESC
    LIMIT 1
");
$resumeStmt->execute([$student_id]);
$uploadedResume = $resumeStmt->fetch(PDO::FETCH_ASSOC);

// Fetch uploaded credential
// Replace the single credential fetch with this
$credStmt = $pdo->prepare("
    SELECT credential_path, uploaded_at
    FROM student_credentials
    WHERE student_id = ?
    ORDER BY uploaded_at DESC
");
$credStmt->execute([$student_id]);
$uploadedCredentials = $credStmt->fetchAll(PDO::FETCH_ASSOC);
$uploadedCredential = !empty($uploadedCredentials);


function isDone(array $progress, string $key): bool
{
    return !empty($progress[$key]['done']);
}

function stepDate(array $progress, string $key): string
{
    if (empty($progress[$key]['updated_at']))
        return '';
    return date("M d, Y", strtotime($progress[$key]['updated_at']));
}

function stepFilePath(array $progress, string $key): string
{
    return $progress[$key]['file_path'] ?? '';
}

// Lock/unlock Funct
// Step 1: Resume — always unlocked
$resumeDone = !empty($uploadedResume);

// Step 2: Application — unlocked after resume
$applicationDone = isDone($progress, 'application');
$applicationLock = !$resumeDone;

// Step 3: Screening — marked by admin, locked until application done
// $screeningDone = isDone($progress, 'screening');
// $screeningLock = !$applicationDone;

// Step 4: Acceptance — marked by admin, locked until screening done
// $acceptanceDone = isDone($progress, 'acceptance');
// $acceptanceLock = !$screeningDone;

// Step 5: Documents — unlocked after acceptance
$documentsDone = isDone($progress, 'documents');
$documentsLock = !$applicationDone;

// Step 6: OJT Confirmed — unlocked after documents
$ojtDone = isDone($progress, 'ojt_accepted');
$ojtLock = !$documentsDone;

function stepClasses(bool $done, bool $locked, bool $forceOpen = false): string
{
    $c = [];
    if ($done)
        $c[] = 'done';
    if ($locked)
        $c[] = 'is-locked';
    if (!$done && !$locked)
        $c[] = 'is-active';
    if ($forceOpen)
        $c[] = 'is-open';
    return implode(' ', $c);
}

function numClass(bool $done, bool $locked): string
{
    if ($done)
        return 'sn-done';
    if ($locked)
        return 'sn-locked';
    return 'sn-active';
}

function numIcon(bool $done, bool $locked): string
{
    if ($done)
        return 'fa-check';
    if ($locked)
        return 'fa-lock';
    return 'fa-circle';
}

function badgeClass(bool $done, bool $locked): string
{
    if ($done)
        return 'b-done';
    if ($locked)
        return 'b-locked';
    return 'b-active';
}

function badgeText(bool $done, bool $locked): string
{
    if ($done)
        return '<i class="fa fa-check"></i> Complete';
    if ($locked)
        return 'Locked';
    return 'Pending';
}

$barSteps = [
    ['label' => 'Prepare<br>Resume', 'done' => $resumeDone, 'active' => !$resumeDone],
    ['label' => 'Submit<br>Application', 'done' => $applicationDone, 'active' => $resumeDone && !$applicationDone],
    ['label' => 'Submit<br>Documents', 'done' => $documentsDone, 'active' => $applicationDone && !$documentsDone],
    ['label' => 'Confirm<br>Internship', 'done' => $ojtDone, 'active' => $documentsDone && !$ojtDone],
];
$flashSuccess = $_SESSION['success'] ?? null;
unset($_SESSION['success']);
$flashError = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Progress</title>
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
            margin-top: 70px;
        }

        /* ── Progress bar ── */
        .progress-bar-steps {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            position: relative;
            margin-bottom: 24px;
            background: white;
            border: 1px solid var(--gray);
            border-radius: 10px;
            padding: 20px 24px 14px;
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
            font-size: 11px;
            margin-top: 8px;
            text-align: center;
            color: #555;
        }

        .progress-step.active .progress-label {
            color: var(--brand);
            font-weight: 500;
        }

        .progress-step.done .progress-label {
            color: var(--green);
        }

        /* ── Step cards ── */
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
            pointer-events: none;
        }

        .step.done {
            border-color: #bbf7d0;
        }

        .step-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            cursor: pointer;
            user-select: none;
        }

        .step-header:hover {
            background: #f9fafb;
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
            background: var(--green);
            color: white;
        }

        .sn-active {
            background: white;
            border: 2px solid var(--brand);
            color: var(--brand);
        }

        .sn-locked {
            background: var(--gray);
            color: #374151;
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
            margin: 0;
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

        .step.done .step-meta h3 {
            color: var(--green);
        }

        /* badges */
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
            background: var(--green);
            color: white;
        }

        .b-active {
            background: #fff7ed;
            color: var(--brand);
            border: 1px solid #fed7aa;
        }

        .b-locked {
            background: var(--gray);
            color: white;
        }

        .b-waiting {
            background: var(--amber-bg);
            color: var(--amber);
        }

        /* chevron */
        .chevron {
            font-size: 14px;
            color: var(--gray-dark);
            transition: transform 0.2s;
        }

        .step.is-open .chevron {
            transform: rotate(180deg);
        }

        /* step body */
        .step-body {
            display: none;
            border-top: 1px solid var(--gray);
            padding: 14px 16px;
            background: #fdfdfd;
        }

        .step.is-open .step-body {
            display: block;
        }

        /* upload zone */
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
            background: #fff7ed;
        }

        .upload-zone p {
            font-size: 12px;
            color: var(--gray-dark);
            margin: 4px 0 0;
        }

        .upload-zone small {
            font-size: 11px;
            color: var(--gray-dark);
        }

        /* confirmed row */
        .confirmed-row {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 12px;
            color: var(--green);
            margin-bottom: 8px;
        }

        /* info rows */
        .info-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 8px;
            margin-bottom: 8px;
        }

        .info-row.green {
            border: 1px solid var(--green-border);
        }

        .info-row.amber {
            background: var(--amber-bg);
            border: 1px solid #fcd34d;
        }

        .info-row-icon {
            font-size: 18px;
            margin-top: 1px;
            flex-shrink: 0;
        }

        .info-row.green .info-row-icon {
            color: var(--green);
        }

        .info-row.amber .info-row-icon {
            color: var(--amber);
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

        /* doc list */
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

        @media (max-width:480px) {
            .step-meta p {
                display: none;
            }

            .progress-label {
                font-size: 9px;
            }
        }
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>

    <div class="container">

        <?php if ($flashSuccess): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa fa-check-circle me-2"></i><?= htmlspecialchars($flashSuccess) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($flashError): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa fa-exclamation-circle me-2"></i><?= htmlspecialchars($flashError) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <div class="progress-bar-steps">
            <?php foreach ($barSteps as $bs): ?>
                <div class="progress-step <?= $bs['done'] ? 'done' : ($bs['active'] ? 'active' : '') ?>">
                    <div
                        class="progress-circle <?= $bs['done'] ? 'is-done' : ($bs['active'] ? 'is-active' : 'is-locked') ?>">
                        <?php if ($bs['done']): ?>
                            <i class="fa fa-check"></i>
                        <?php elseif ($bs['active']): ?>
                            <i class="fa fa-circle" style="font-size:8px;"></i>
                        <?php endif; ?>
                    </div>
                    <span class="progress-label"><?= $bs['label'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="steps">

            <!-- PHASE 1 -->
            <?php
            $s1done = $resumeDone;
            $s1locked = false;
            $s1open = !$s1done;
            ?>
            <div class="step <?= stepClasses($s1done, $s1locked, $s1open) ?>">
                <div class="step-header" onclick="toggle(this)">
                    <div class="step-num <?= numClass($s1done, $s1locked) ?>">
                        <i class="fa <?= numIcon($s1done, $s1locked) ?>"></i>
                    </div>
                    <div class="step-meta">
                        <h3>Prepare resume</h3>
                        <p>
                            <?php if ($uploadedResume): ?>
                                Uploaded: <?= htmlspecialchars(basename($uploadedResume['resume_path'])) ?>
                                &middot; <?= date("M d", strtotime($uploadedResume['uploaded_at'])) ?>
                            <?php else: ?>
                                Upload your resume to your profile
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="step-right">
                        <span class="badge <?= badgeClass($s1done, $s1locked) ?>">
                            <?= badgeText($s1done, $s1locked) ?>
                        </span>
                        <span class="chevron"><i class="fa fa-chevron-down"></i></span>
                    </div>
                </div>
                <div class="step-body">
                    <?php if ($uploadedResume): ?>
                        <div class="confirmed-row">
                            <i class="fa fa-file-pdf"></i>
                            <?= htmlspecialchars(basename($uploadedResume['resume_path'])) ?>
                            &middot; <?= date("M d, Y", strtotime($uploadedResume['uploaded_at'])) ?>
                            <a href="../uploads/resumes/<?= htmlspecialchars($uploadedResume['resume_path']) ?>"
                                target="_blank" class="ms-2 btn btn-sm btn-outline-success" style="font-size:11px;">
                                <i class="fa fa-eye"></i> View
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if (!$s1done): ?>
                        <div class="upload-zone" onclick="document.getElementById('resumeInput').click()">
                            <i class="fa fa-upload" style="font-size:24px;color:#9ca3af;"></i>
                            <p>Click to upload resume</p>
                            <small>PDF only · max 5MB</small>
                        </div>
                        <div id="resumePreview" class="mt-2" style="font-size:13px;color:#374151;"></div>
                        <form action="student-progress.php" method="POST" enctype="multipart/form-data" id="resumeForm">
                            <input type="hidden" name="action" value="upload_resume">
                            <input type="file" id="resumeInput" name="resume" accept=".pdf" hidden required>
                            <button type="submit" id="resumeSubmitBtn" class="btn btn-primary mt-2" style="display:none;">
                                <i class="fa fa-upload me-1"></i> Upload Resume
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="info-row green mt-2">
                            <span class="info-row-icon"><i class="fa fa-check-circle"></i></span>
                            <div class="info-row-text">
                                <p>Resume uploaded successfully</p>
                                <small>You can re-upload to replace your current resume</small>
                            </div>
                        </div>
                        <form action="student-progress.php" method="POST" enctype="multipart/form-data" class="mt-2">
                            <input type="hidden" name="action" value="upload_resume">
                            <div class="d-flex align-items-center gap-2">
                                <input type="file" name="resume" accept=".pdf" class="form-control form-control-sm"
                                    required>
                                <button type="submit" class="btn btn-sm btn-outline-primary text-nowrap">
                                    <i class="fa fa-refresh me-1"></i> Replace
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- PHASE 2  -->
            <?php
            $s2done = $applicationDone;
            $s2locked = $applicationLock;
            $s2open = $resumeDone && !$applicationDone;
            ?>
            <div class="step <?= stepClasses($s2done, $s2locked, $s2open) ?>">
                <div class="step-header" onclick="toggle(this)">
                    <div class="step-num <?= numClass($s2done, $s2locked) ?>">
                        <i class="fa <?= numIcon($s2done, $s2locked) ?>"></i>
                    </div>
                    <div class="step-meta">
                        <h3>Submit application</h3>
                        <p>
                            <?php if ($s2done): ?>
                                Marked submitted &middot; <?= stepDate($progress, 'application') ?>
                            <?php else: ?>
                                Confirm you have submitted your application
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="step-right">
                        <span class="badge <?= badgeClass($s2done, $s2locked) ?>">
                            <?= badgeText($s2done, $s2locked) ?>
                        </span>
                        <span class="chevron"><i class="fa fa-chevron-down"></i></span>
                    </div>
                </div>
                <div class="step-body">
                    <?php if ($s2done): ?>
                        <div class="confirmed-row">
                            <i class="fa fa-check-circle"></i>
                            Application marked as submitted
                            &middot; <?= stepDate($progress, 'application') ?>
                        </div>
                    <?php elseif (!$s2locked): ?>
                        <form action="student-progress.php" method="POST">
                            <input type="hidden" name="application_submitted" value="1">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="appCheck" required>
                                <label class="form-check-label" for="appCheck" style="font-size:13px;">
                                    I have submitted my application (physical or via email)
                                </label>
                            </div>
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fa fa-check me-1"></i> Mark as Done
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Phase 3 -->
            <?php
            $s5done = $documentsDone;
            $s5locked = $documentsLock;
            $s5open = $applicationDone && !$documentsDone;
            ?>
            <div class="step <?= stepClasses($s5done, $s5locked, $s5open) ?>">
                <div class="step-header" onclick="toggle(this)">
                    <div class="step-num <?= numClass($s5done, $s5locked) ?>">
                        <i class="fa <?= numIcon($s5done, $s5locked) ?>"></i>
                    </div>
                    <div class="step-meta">
                        <h3>Submit documents</h3>
                        <p>
                            <?php if ($s5done): ?>
                                All documents submitted &middot; <?= stepDate($progress, 'documents') ?>
                            <?php else: ?>
                                Upload your required internship documents
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="step-right">
                        <span class="badge <?= badgeClass($s5done, $s5locked) ?>">
                            <?= badgeText($s5done, $s5locked) ?>
                        </span>
                        <span class="chevron"><i class="fa fa-chevron-down"></i></span>
                    </div>
                </div>
                <div class="step-body">

                    <?php if (!$bookedInternship): ?>
                        <!-- No internship bookmarked yet -->
                        <div class="info-row amber">
                            <span class="info-row-icon"><i class="fa fa-exclamation-triangle"></i></span>
                            <div class="info-row-text">
                                <p>No internship selected yet</p>
                                <small>Go to the <a href="applied-Internship-programs.php">Internship Listings</a>
                                    and click "Interested" on a posting first.</small>
                            </div>
                        </div>

                    <?php else: ?>

                        <!-- Show which internship they selected -->
                        <div class="info-row green mb-3">
                            <span class="info-row-icon"><i class="fa fa-briefcase"></i></span>
                            <div class="info-row-text">
                                <p><?= htmlspecialchars($bookedInternship['title']) ?></p>
                                <small><?= htmlspecialchars($bookedInternship['company']) ?></small>
                            </div>
                        </div>

                        <!-- ── Download/Preview letters for this internship ── -->
                        <p style="font-size:13px; font-weight:600; color:#374151; margin-bottom:8px;">
                            Download your application letters
                        </p>

                        <div class="doc-list mb-3">

                            <!-- MOU -->
                            <div class="doc-row">
                                <div class="doc-icon di-wait"><i class="fa fa-file-pdf"></i></div>
                                <span class="doc-name">Memorandum of Understanding (MOU)</span>
                                <div class="d-flex gap-2">
                                    <a href="mou-preview.php?id=<?= $internship_id ?>&student_id=<?= $student_id ?>&action=mou"
                                        target="_blank" class="btn btn-sm btn-outline-secondary" style="font-size:11px;">
                                        <i class="fa fa-eye"></i> Preview
                                    </a>
                                    <a href="download-mou.php?id=<?= $internship_id ?>&action=mou"
                                        class="btn btn-sm btn-outline-primary" style="font-size:11px;">
                                        <i class="fa fa-download"></i> Download
                                    </a>
                                </div>
                            </div>

                            <!-- Recommendation Letter -->
                            <div class="doc-row">
                                <div class="doc-icon di-wait"><i class="fa fa-file-pdf"></i></div>
                                <span class="doc-name">Recommendation Letter</span>
                                <div class="d-flex gap-2">
                                    <a href="mou-preview.php?id=<?= $internship_id ?>&student_id=<?= $student_id ?>&action=rl"
                                        target="_blank" class="btn btn-sm btn-outline-secondary" style="font-size:11px;">
                                        <i class="fa fa-eye"></i> Preview
                                    </a>
                                    <a href="download-mou.php?id=<?= $internship_id ?>&action=rl"
                                        class="btn btn-sm btn-outline-primary" style="font-size:11px;">
                                        <i class="fa fa-download"></i> Download
                                    </a>
                                </div>
                            </div>

                            <!-- Waiver -->
                            <div class="doc-row">
                                <div class="doc-icon di-wait"><i class="fa fa-file-pdf"></i></div>
                                <span class="doc-name">Waiver</span>
                                <div class="d-flex gap-2">
                                    <a href="mou-preview.php?id=<?= $internship_id ?>&student_id=<?= $student_id ?>&action=waiver"
                                        target="_blank" class="btn btn-sm btn-outline-secondary" style="font-size:11px;">
                                        <i class="fa fa-eye"></i> Preview
                                    </a>
                                    <a href="download-mou.php?id=<?= $internship_id ?>&action=waiver"
                                        class="btn btn-sm btn-outline-primary" style="font-size:11px;">
                                        <i class="fa fa-download"></i> Download
                                    </a>
                                </div>
                            </div>

                        </div>

                        <hr style="border-color:#eee; margin:16px 0;">

                        <!-- ── Already uploaded documents status ── -->
                        <p style="font-size:13px; font-weight:600; color:#374151; margin-bottom:8px;">
                            Uploaded documents
                        </p>

                        <div class="doc-list">

                            <!-- Resume -->
                            <div class="doc-row">
                                <div class="doc-icon <?= $uploadedResume ? 'di-done' : 'di-lock' ?>">
                                    <i class="fa <?= $uploadedResume ? 'fa-check' : 'fa-minus' ?>"></i>
                                </div>
                                <span class="doc-name">Resume</span>
                                <span class="doc-status <?= $uploadedResume ? 'ds-done' : 'ds-lock' ?>">
                                    <?= $uploadedResume ? 'Uploaded' : 'Not uploaded' ?>
                                </span>
                                <?php if ($uploadedResume): ?>
                                    <a href="../uploads/resumes/<?= htmlspecialchars(basename($uploadedResume['resume_path'])) ?>"
                                        target="_blank" style="font-size:11px; color:var(--brand);">
                                        <i class="fa fa-eye"></i> View
                                    </a>
                                <?php endif; ?>
                            </div>

                            <!-- Credential -->
                            <?php if (!empty($uploadedCredentials)): ?>

                                <?php foreach ($uploadedCredentials as $cred): ?>
                                    <div class="doc-row">
                                        <div class="doc-icon di-done">
                                            <i class="fa fa-check"></i>
                                        </div>
                                        <span class="doc-name">
                                            <?= htmlspecialchars(basename($cred['credential_path'])) ?>
                                        </span>
                                        <span class="doc-status ds-done">Uploaded</span>
                                        <a href="../uploads/credentials/<?= htmlspecialchars($cred['credential_path']) ?>"
                                            target="_blank" style="font-size:11px; color:var(--brand);">
                                            <i class="fa fa-eye"></i> View
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="doc-row">
                                    <div class="doc-icon di-lock">
                                        <i class="fa fa-minus"></i>
                                    </div>
                                    <span class="doc-name">Supporting Credential</span>
                                    <span class="doc-status ds-lock">Not uploaded</span>
                                </div>
                            <?php endif; ?>

                        </div>

                        <?php if (!$s5locked): ?>

                            <!-- Always show credential upload zone -->
                            <div class="upload-zone mb-3" onclick="document.getElementById('credInput').click()">
                                <i class="fa fa-upload" style="font-size:24px;color:#9ca3af;"></i>
                                <p><?= $uploadedCredential ? 'Upload another credential' : 'Upload supporting credential' ?></p>
                                <small>PDF, JPG, PNG · max 5MB</small>
                            </div>
                            <div id="credPreview" class="mb-2" style="font-size:13px;color:#374151;"></div>
                            <form action="student-progress.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="upload_credential">
                                <input type="file" id="credInput" name="credential" accept=".pdf,.jpg,.jpeg,.png" hidden
                                    required>
                                <button type="submit" id="credSubmitBtn" class="btn btn-primary btn-sm" style="display:none;">
                                    <i class="fa fa-upload me-1"></i> Upload Credential
                                </button>
                            </form>

                            <!-- Mark complete button once both are uploaded -->
                            <?php if ($uploadedResume && $uploadedCredential && !$s5done): ?>
                                <form action="student-progress.php" method="POST" class="mt-3">
                                    <input type="hidden" name="action" value="mark_documents">
                                    <div class="info-row green mb-2">
                                        <span class="info-row-icon"><i class="fa fa-check-circle"></i></span>
                                        <div class="info-row-text">
                                            <p>All documents uploaded</p>
                                            <small>Click below to mark this step complete.</small>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fa fa-check me-1"></i> Mark Documents Complete
                                    </button>
                                </form>
                            <?php elseif ($s5done): ?>
                                <div class="confirmed-row mt-2">
                                    <i class="fa fa-check-circle"></i>
                                    Documents marked complete &middot; <?= stepDate($progress, 'documents') ?>
                                </div>
                            <?php endif; ?>

                        <?php endif; ?>

                    <?php endif; ?>

                </div>
            </div>

            <!-- ─── STEP 6: CONFIRM INTERNSHIP ────────────────────────────────── -->
            <?php
            $s6done = $ojtDone;
            $s6locked = $ojtLock;
            $s6open = $documentsDone && !$ojtDone;
            ?>
            <div class="step <?= stepClasses($s6done, $s6locked, $s6open) ?>">
                <div class="step-header" onclick="toggle(this)">
                    <div class="step-num <?= numClass($s6done, $s6locked) ?>">
                        <i class="fa <?= numIcon($s6done, $s6locked) ?>"></i>
                    </div>
                    <div class="step-meta">
                        <h3>Confirm internship</h3>
                        <p>
                            <?php if ($s6done): ?>
                                Confirmed &middot; <?= stepDate($progress, 'ojt_accepted') ?>
                            <?php else: ?>
                                Ready to start your internship
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="step-right">
                        <span class="badge <?= badgeClass($s6done, $s6locked) ?>">
                            <?= badgeText($s6done, $s6locked) ?>
                        </span>
                        <span class="chevron"><i class="fa fa-chevron-down"></i></span>
                    </div>
                </div>
                <div class="step-body">
                    <?php if ($s6done): ?>
                        <div class="info-row green">
                            <span class="info-row-icon"><i class="fa fa-briefcase"></i></span>
                            <div class="info-row-text">
                                <p>Internship confirmed — you're all set!</p>
                                <small><?= stepDate($progress, 'ojt_accepted') ?></small>
                            </div>
                        </div>
                    <?php elseif (!$s6locked): ?>
                        <form action="student-progress.php" method="POST">
                            <input type="hidden" name="action" value="confirm_ojt">
                            <div class="mb-3">
                                <label style="font-size:13px; font-weight:500;" class="form-label">
                                    Company / HTE Name
                                </label>
                                <input type="text" name="company_name" class="form-control form-control-sm"
                                    placeholder="e.g. DevTech Solutions Inc." required>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="ojtCheck" required>
                                <label class="form-check-label" for="ojtCheck" style="font-size:13px;">
                                    I confirm I have been officially accepted and will begin my internship.
                                </label>
                            </div>
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fa fa-check me-1"></i> Confirm Internship
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /.steps -->
    </div><!-- /.container -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle step open/close
        function toggle(header) {
            const step = header.closest('.step');
            if (step.classList.contains('is-locked')) return;
            step.classList.toggle('is-open');
        }

        // Resume file picker → show preview + reveal submit button
        document.getElementById('resumeInput')?.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;
            if (file.size > 5 * 1024 * 1024) {
                alert('File exceeds 5MB limit.');
                this.value = '';
                return;
            }
            document.getElementById('resumePreview').innerHTML =
                '<i class="fa fa-file-pdf me-1" style="color:#f97316;"></i>' +
                '<b>' + file.name + '</b> (' + (file.size / 1024).toFixed(1) + ' KB)';
            document.getElementById('resumeSubmitBtn').style.display = 'inline-block';
        });

        // Credential file picker → show preview + reveal submit button
        document.getElementById('credInput')?.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;
            if (file.size > 5 * 1024 * 1024) {
                alert('File exceeds 5MB limit.');
                this.value = '';
                return;
            }
            document.getElementById('credPreview').innerHTML =
                '<i class="fa fa-file me-1" style="color:#f97316;"></i>' +
                '<b>' + file.name + '</b> (' + (file.size / 1024).toFixed(1) + ' KB)';
            document.getElementById('credSubmitBtn').style.display = 'inline-block';
        });

        // Click upload zone → trigger hidden file input
        document.querySelectorAll('.upload-zone').forEach(zone => {
            zone.addEventListener('dragover', e => {
                e.preventDefault();
                zone.style.borderColor = '#f97316';
                zone.style.background = '#fff7ed';
            });
            zone.addEventListener('dragleave', () => {
                zone.style.borderColor = '';
                zone.style.background = '';
            });
            zone.addEventListener('drop', e => {
                e.preventDefault();
                zone.style.borderColor = '';
                zone.style.background = '';
                const input = document.getElementById(
                    zone.onclick?.toString().includes('resumeInput') ? 'resumeInput' : 'credInput'
                );
                if (input && e.dataTransfer.files.length) {
                    input.files = e.dataTransfer.files;
                    input.dispatchEvent(new Event('change'));
                }
            });
        });

        // Auto-dismiss flash alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(a => {
                a.classList.remove('show');
                setTimeout(() => a.remove(), 300);
            });
        }, 4000);
    </script>
</body>

</html>