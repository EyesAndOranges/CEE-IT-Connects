<?php
require 'auth.php';
require 'db.php';

$student_id = (int) $_SESSION['user_id'];

// ── Fetch bookmarked internship ───────────────────────────────────────────────
$bookmarkStmt = $pdo->prepare("
    SELECT
        ib.id AS bookmark_id,       
        i.id AS internship_id,
        i.title,
        i.company,
        i.company_classification,
        i.is_plv_internal,
        i.is_valenzuela_lgu
    FROM internship_bookmarks ib
    JOIN internships i ON i.id = ib.internship_id
    WHERE ib.student_id = ?
    ORDER BY ib.created_at DESC LIMIT 1
");
$bookmarkStmt->execute([$student_id]);
$selectedInternship = $bookmarkStmt->fetch(PDO::FETCH_ASSOC);
$internship_id = $selectedInternship['internship_id'] ?? null;

// ── Fetch HTE supervisor submission ──────────────────────────────────────────
try {
    $supStmt = $pdo->prepare("
        SELECT * FROM student_hte_supervisor_submissions
        WHERE student_id = ?
    ");
    $supStmt->execute([$student_id]);
    $supSubmission = $supStmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $supSubmission = null;
}

// ── Derive document requirements from classification ─────────────────────────
$classification = $selectedInternship['company_classification'] ?? 'private';
$is_plv = filter_var($selectedInternship['is_plv_internal'] ?? false, FILTER_VALIDATE_BOOLEAN);
$is_val_lgu = filter_var($selectedInternship['is_valenzuela_lgu'] ?? false, FILTER_VALIDATE_BOOLEAN);
$is_public = ($classification === 'public');

$needs_bir_dti_sec = !$is_public;
$needs_waiver = !$is_plv;
$needs_reco_letter = !$is_plv && !$is_val_lgu;

// ── Fetch all checklist rows ──────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT step_key, is_done, file_path, updated_at
    FROM student_progress
    WHERE student_id = :sid
");
$stmt->execute([':sid' => $student_id]);
$progress = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $progress[$row['step_key']] = [
        'done' => filter_var($row['is_done'], FILTER_VALIDATE_BOOLEAN),
        'file_path' => $row['file_path'],
        'updated_at' => $row['updated_at'],
    ];
}

// ── Fetch uploaded resume ─────────────────────────────────────────────────────
$resumeStmt = $pdo->prepare("
    SELECT resume_path, uploaded_at FROM student_documents
    WHERE student_id = ? ORDER BY uploaded_at DESC LIMIT 1
");
$resumeStmt->execute([$student_id]);
$uploadedResume = $resumeStmt->fetch(PDO::FETCH_ASSOC);

// ── Fetch uploaded credentials ────────────────────────────────────────────────
$credStmt = $pdo->prepare("
    SELECT credential_path, uploaded_at FROM student_credentials
    WHERE student_id = ? ORDER BY uploaded_at DESC
");
$credStmt->execute([$student_id]);
$uploadedCredentials = $credStmt->fetchAll(PDO::FETCH_ASSOC);

// ── Flash messages ────────────────────────────────────────────────────────────
$flashSuccess = $_SESSION['success'] ?? null;
unset($_SESSION['success']);
$flashError = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

// ── Helper functions ──────────────────────────────────────────────────────────
function isDone(array $p, string $k): bool
{
    if (!isset($p[$k]))
        return false;
    return !empty($p[$k]['done']);
}
function stepDate(array $p, string $k): string
{
    if (!isset($p[$k]) || empty($p[$k]['updated_at']))
        return '';
    return date("M d, Y", strtotime($p[$k]['updated_at']));
}

// ── Per-step done flags ───────────────────────────────────────────────────────
// Named $checklist (not $steps) to avoid collision with navbar.php's foreach variable
$checklist = [
    'internship' => false,
    'hte_form' => false,
    'addendum' => false,
    'reco_letter' => false,
    'waiver' => false,
    'medical_cert' => false,
    'internship_plan' => false,
    'vicinity_map' => false,
    'oath' => false,
    'ojt_started' => false,
];

$checklist['internship'] = is_array($selectedInternship) && !empty($selectedInternship['internship_id']);
$checklist['hte_form'] = isDone($progress, 'hte_form');
$checklist['addendum'] = isDone($progress, 'addendum');
$checklist['reco_letter'] = !$needs_reco_letter || isDone($progress, 'reco_letter');
$checklist['waiver'] = !$needs_waiver || isDone($progress, 'waiver');
$checklist['medical_cert'] = isDone($progress, 'medical_cert');
$checklist['internship_plan'] = isDone($progress, 'internship_plan');
$checklist['vicinity_map'] = isDone($progress, 'vicinity_map');
$checklist['oath'] = isDone($progress, 'oath');
$checklist['ojt_started'] = isDone($progress, 'ojt_started');

$total = count($checklist);
$doneCount = count(array_filter($checklist));
$pct = round(($doneCount / $total) * 100);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OJT Application Checklist</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Syne:wght@600;700&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --brand: #f97316;
            --brand-dark: #c2570c;
            --brand-light: #fff7ed;
            --brand-border: #fed7aa;
            --green: #16a34a;
            --green-light: #f0fdf4;
            --green-border: #bbf7d0;
            --gray-100: #f5f5f4;
            --gray-200: #e7e5e4;
            --gray-400: #a8a29e;
            --gray-600: #57534e;
            --gray-800: #292524;
            --amber: #d97706;
            --amber-light: #fffbeb;
            --amber-border: #fde68a;
            --blue: #2563eb;
            --blue-light: #eff6ff;
            --blue-border: #bfdbfe;
            --radius: 12px;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, .08), 0 1px 2px rgba(0, 0, 0, .06);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, .08);
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: #f0ede9;
            color: var(--gray-800);
            min-height: 100vh;
        }

        .page-wrap {
            max-width: 700px;
            margin: 0 auto;
            padding: 90px 16px 64px;
        }

        .page-header {
            margin-bottom: 24px;
        }

        .page-header h1 {
            font-family: 'Syne', sans-serif;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -.3px;
            color: var(--gray-800);
            margin-bottom: 4px;
        }

        .page-header p {
            font-size: 13px;
            color: var(--gray-600);
        }

        .progress-summary {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 16px 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .progress-ring-wrap {
            position: relative;
            width: 54px;
            height: 54px;
            flex-shrink: 0;
        }

        .progress-ring-wrap svg {
            transform: rotate(-90deg);
        }

        .progress-ring-bg {
            fill: none;
            stroke: var(--gray-200);
            stroke-width: 5;
        }

        .progress-ring-val {
            fill: none;
            stroke: var(--brand);
            stroke-width: 5;
            stroke-linecap: round;
            transition: stroke-dashoffset .5s ease;
        }

        .ring-pct {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            color: var(--gray-800);
        }

        .progress-text h3 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .progress-text p {
            font-size: 12px;
            color: var(--gray-600);
        }

        .progress-bar-thin {
            flex: 1;
            height: 6px;
            background: var(--gray-200);
            border-radius: 99px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--brand), var(--brand-dark));
            border-radius: 99px;
            transition: width .5s ease;
        }

        .checklist-note {
            background: var(--amber-light);
            border: 1px solid var(--amber-border);
            border-radius: var(--radius);
            padding: 12px 16px;
            font-size: 12.5px;
            color: var(--amber);
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }

        .checklist-note i {
            margin-top: 2px;
            flex-shrink: 0;
        }

        .checklist {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .step-card {
            background: white;
            border: 1.5px solid var(--gray-200);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: border-color .2s, box-shadow .2s;
        }

        .step-card.is-done {
            border-color: var(--green-border);
        }

        .step-card.is-active {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(249, 115, 22, .08);
        }

        .step-card.is-open {
            box-shadow: var(--shadow-md);
        }

        .step-card.is-na {
            border-color: var(--green-border);
            opacity: .75;
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
            background: var(--gray-100);
        }

        .step-num {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            flex-shrink: 0;
        }

        .sn-done {
            background: var(--green);
            color: white;
        }

        .sn-active {
            background: var(--brand);
            color: white;
        }

        .sn-na {
            background: var(--gray-200);
            color: var(--gray-600);
        }

        .step-meta {
            flex: 1;
            min-width: 0;
        }

        .step-meta h3 {
            font-size: 13.5px;
            font-weight: 600;
            margin-bottom: 1px;
        }

        .step-meta p {
            font-size: 11.5px;
            color: var(--gray-600);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin: 0;
        }

        .step-card.is-done .step-meta h3,
        .step-card.is-na .step-meta h3 {
            color: var(--green);
        }

        .step-right {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .pill {
            padding: 3px 9px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
        }

        .pill-done {
            background: var(--green);
            color: white;
        }

        .pill-active {
            background: var(--brand-light);
            color: var(--brand);
            border: 1px solid var(--brand-border);
        }

        .pill-na {
            background: var(--green-light);
            color: var(--green);
            border: 1px solid var(--green-border);
        }

        .pill-idle {
            background: var(--gray-100);
            color: var(--gray-600);
        }

        .chevron {
            font-size: 13px;
            color: var(--gray-400);
            transition: transform .2s;
        }

        .step-card.is-open .chevron {
            transform: rotate(180deg);
        }

        .step-body {
            display: none;
            border-top: 1px solid var(--gray-200);
            padding: 16px;
            background: #fdfcfb;
        }

        .step-card.is-open .step-body {
            display: block;
        }

        .info-box {
            border-radius: 8px;
            padding: 11px 14px;
            margin-bottom: 12px;
            font-size: 13px;
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }

        .info-box i {
            margin-top: 2px;
            flex-shrink: 0;
        }

        .info-box.green {
            background: var(--green-light);
            border: 1px solid var(--green-border);
            color: var(--green);
        }

        .info-box.amber {
            background: var(--amber-light);
            border: 1px solid var(--amber-border);
            color: var(--amber);
        }

        .info-box.blue {
            background: var(--blue-light);
            border: 1px solid var(--blue-border);
            color: var(--blue);
        }

        .info-box.orange {
            background: var(--brand-light);
            border: 1px solid var(--brand-border);
            color: var(--brand-dark);
        }

        .info-box p {
            font-weight: 600;
            margin-bottom: 2px;
            font-size: 13px;
        }

        .info-box span {
            font-size: 12px;
            color: var(--gray-600);
        }

        .body-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: var(--gray-400);
            margin-bottom: 8px;
        }

        .action-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: filter .15s, transform .1s;
            font-family: 'DM Sans', sans-serif;
        }

        .btn-action:hover {
            filter: brightness(.95);
            transform: translateY(-1px);
        }

        .btn-action:active {
            transform: translateY(0);
        }

        .btn-primary-action {
            background: var(--brand);
            color: white;
        }

        .btn-outline-action {
            background: white;
            color: var(--gray-800);
            border: 1.5px solid var(--gray-200);
        }

        .btn-green-action {
            background: var(--green);
            color: white;
        }

        .mark-done-block {
            border-top: 1px dashed var(--gray-200);
            padding-top: 12px;
            margin-top: 12px;
        }

        .mark-done-block .form-check-label {
            font-size: 13px;
        }

        .doc-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            background: white;
            margin-bottom: 6px;
            font-size: 13px;
        }

        .doc-row .doc-name {
            flex: 1;
        }

        .confirmed {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 12.5px;
            color: var(--green);
            margin-bottom: 8px;
        }

        .hte-card {
            border: 1px solid var(--blue-border);
            background: var(--blue-light);
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 12px;
            font-size: 13px;
        }

        .hte-card strong {
            display: block;
            margin-bottom: 2px;
        }

        .hte-card span {
            font-size: 12px;
            color: var(--gray-600);
        }

        /* Supervisor modal */
        .sup-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(0, 0, 0, 0.4);
            align-items: center;
            justify-content: center;
        }

        .sup-modal-overlay.is-open {
            display: flex;
        }

        .sup-modal-box {
            background: white;
            border-radius: 16px;
            padding: 28px;
            width: 100%;
            max-width: 460px;
            margin: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        @media (max-width: 480px) {
            .step-meta p {
                display: none;
            }

            .progress-bar-thin {
                display: none;
            }
        }
    </style>
</head>

<body>

    <?php include 'navbar.php'; ?>

    <div class="page-wrap">

        <?php if ($flashSuccess): ?>
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                <i class="fa fa-check-circle me-2"></i><?= htmlspecialchars($flashSuccess) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($flashError): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <i class="fa fa-exclamation-circle me-2"></i><?= htmlspecialchars($flashError) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="page-header">
            <h1>OJT Application Checklist</h1>
            <p>Complete each item below. Following the order is recommended, but you may work on them in any sequence.
            </p>
        </div>

        <!-- Progress summary -->
        <div class="progress-summary">
            <div class="progress-ring-wrap">
                <svg width="54" height="54" viewBox="0 0 54 54">
                    <?php
                    $r = 22;
                    $circ = 2 * M_PI * $r;
                    $offset = $circ - ($pct / 100) * $circ;
                    ?>
                    <circle class="progress-ring-bg" cx="27" cy="27" r="<?= $r ?>" />
                    <circle class="progress-ring-val" cx="27" cy="27" r="<?= $r ?>"
                        stroke-dasharray="<?= round($circ, 2) ?>" stroke-dashoffset="<?= round($offset, 2) ?>" />
                </svg>
                <div class="ring-pct"><?= $pct ?>%</div>
            </div>
            <div class="progress-text">
                <h3><?= $doneCount ?> of <?= $total ?> completed</h3>
                <p>Submit all items before starting your OJT</p>
            </div>
            <div class="progress-bar-thin">
                <div class="progress-bar-fill" style="width:<?= $pct ?>%"></div>
            </div>
        </div>

        <div class="checklist-note">
            <i class="fa fa-triangle-exclamation"></i>
            <div>
                <strong>Note on ordering:</strong> Steps are recommended to be completed in sequence, but you may work
                on them out of order — especially when waiting for forms or signatures from your HTE or PLV offices.
                Mark each item done as you complete it.
            </div>
        </div>

        <div class="checklist">

            <!-- STEP 1: Choose an internship -->
            <?php $s1done = $checklist['internship'] ?? false; ?>
            <div class="step-card <?= $s1done ? 'is-done' : 'is-active' ?> <?= !$s1done ? 'is-open' : '' ?>">
                <div class="step-header" onclick="toggle(this)">
                    <div class="step-num <?= $s1done ? 'sn-done' : 'sn-active' ?>">
                        <?= $s1done ? '<i class="fa fa-check"></i>' : '1' ?>
                    </div>
                    <div class="step-meta">
                        <h3>Choose an internship</h3>
                        <p><?= $s1done
                            ? htmlspecialchars($selectedInternship['title'] . ' — ' . $selectedInternship['company'])
                            : 'Browse listings and click Interested' ?></p>
                    </div>
                    <div class="step-right">
                        <span class="pill <?= $s1done ? 'pill-done' : 'pill-active' ?>">
                            <?= $s1done ? '<i class="fa fa-check"></i> Done' : 'Pending' ?>
                        </span>
                        <span class="chevron"><i class="fa fa-chevron-down"></i></span>
                    </div>
                </div>
                <div class="step-body">
                    <?php if ($s1done): ?>
                        <div class="hte-card">
                            <strong><?= htmlspecialchars($selectedInternship['title']) ?></strong>
                            <span><i
                                    class="fa fa-building me-1"></i><?= htmlspecialchars($selectedInternship['company']) ?></span>
                        </div>
                        <div class="info-box green">
                            <i class="fa fa-circle-check"></i>
                            <div>
                                <p>Internship selected</p>
                                <span>You can change your selection in the listings page before proceeding.</span>
                            </div>
                        </div>
                        <div class="action-row">
                            <a href="applied-Internship-programs.php" class="btn-action btn-outline-action">
                                <i class="fa fa-arrow-right-arrow-left"></i> Change selection
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="info-box amber">
                            <i class="fa fa-triangle-exclamation"></i>
                            <div>
                                <p>No internship selected yet</p>
                                <span>Go to the Internship Listings and click <strong>Interested</strong> on a posting to
                                    begin.</span>
                            </div>
                        </div>
                        <div class="action-row">
                            <a href="applied-Internship-programs.php" class="btn-action btn-primary-action">
                                <i class="fa fa-magnifying-glass"></i> Browse Internships
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- STEP 2: HTE Information Form + Supervisor -->
            <?php $s2done = $checklist['hte_form'] ?? false; ?>
            <div
                class="step-card <?= $s2done ? 'is-done' : 'is-active' ?> <?= (!$s2done && $s1done) ? 'is-open' : '' ?>">
                <div class="step-header" onclick="toggle(this)">
                    <div class="step-num <?= $s2done ? 'sn-done' : 'sn-active' ?>">
                        <?= $s2done ? '<i class="fa fa-check"></i>' : '2' ?>
                    </div>
                    <div class="step-meta">
                        <h3>HTE Information Form &amp; Registration</h3>
                        <p>Download CEIT-OJTF-001 and collect HTE's registration documents</p>
                    </div>
                    <div class="step-right">
                        <span class="pill <?= $s2done ? 'pill-done' : 'pill-active' ?>">
                            <?= $s2done ? '<i class="fa fa-check"></i> Done' : 'Pending' ?>
                        </span>
                        <span class="chevron"><i class="fa fa-chevron-down"></i></span>
                    </div>
                </div>
                <div class="step-body">
                    <?php if ($s2done): ?>
                        <div class="confirmed">
                            <i class="fa fa-check-circle"></i> Marked complete &middot;
                            <?= stepDate($progress, 'hte_form') ?>
                        </div>
                    <?php endif; ?>

                    <div class="body-label">What you need</div>
                    <div class="doc-row">
                        <i class="fa fa-file-lines" style="color:var(--brand);"></i>
                        <span class="doc-name">HTE Information Form (CEIT-OJTF-001)</span>
                        <?php if ($internship_id): ?>
                            <a href="mou-preview.php?id=<?= $internship_id ?>&student_id=<?= $student_id ?>&action=hte_info"
                                target="_blank" class="btn-action btn-outline-action"
                                style="padding:5px 10px;font-size:11.5px;">
                                <i class="fa fa-eye"></i> Preview
                            </a>
                            <a href="download-form.php?id=<?= $internship_id ?>&action=hte_info"
                                class="btn-action btn-outline-action" style="padding:5px 10px;font-size:11.5px;">
                                <i class="fa fa-download"></i> Download
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if ($needs_bir_dti_sec): ?>
                        <div class="info-box orange">
                            <i class="fa fa-circle-info"></i>
                            <div>
                                <p>Required: HTE's business registration</p>
                                <span>Obtain a photocopy of the HTE's BIR, SEC, and/or DTI registration.
                                    This is <strong>not required</strong> for national or local government offices.</span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="info-box green">
                            <i class="fa fa-circle-check"></i>
                            <div>
                                <p>Government office — no BIR/SEC/DTI needed</p>
                                <span>Your selected HTE is a government office. You only need to fill out the HTE
                                    Information Form.</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- HTE Supervisor subsection -->
                    <div style="margin-top:14px;">
                        <div class="body-label">HTE Supervisor Information</div>

                        <?php if ($supSubmission): ?>
                            <?php if ($supSubmission['status'] === 'pending'): ?>
                                <div class="info-box amber">
                                    <i class="fa fa-clock"></i>
                                    <div>
                                        <p>Awaiting coordinator review</p>
                                        <span>Your supervisor details have been submitted and are pending review.</span>
                                    </div>
                                </div>
                                <div class="doc-row">
                                    <i class="fa fa-user" style="color:var(--brand);"></i>
                                    <span class="doc-name">
                                        <strong><?= htmlspecialchars($supSubmission['full_name']) ?></strong><br>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($supSubmission['email'] ?? '—') ?> &middot;
                                            <?= htmlspecialchars($supSubmission['contact_number'] ?? '—') ?>
                                        </small>
                                    </span>
                                    <button type="button" onclick="openSupModal()" class="btn-action btn-outline-action"
                                        style="padding:5px 10px; font-size:11.5px;">
                                        <i class="fa fa-pen"></i> Edit
                                    </button>
                                </div>

                            <?php elseif ($supSubmission['status'] === 'approved'): ?>
                                <div class="info-box green">
                                    <i class="fa fa-circle-check"></i>
                                    <div>
                                        <p>Supervisor account created</p>
                                        <span>Your OJT Coordinator has verified and created an account for your HTE
                                            Supervisor.</span>
                                    </div>
                                </div>
                                <div class="doc-row">
                                    <i class="fa fa-user-tie" style="color:var(--green);"></i>
                                    <span class="doc-name">
                                        <strong><?= htmlspecialchars($supSubmission['full_name']) ?></strong><br>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($supSubmission['email'] ?? '—') ?> &middot;
                                            <?= htmlspecialchars($supSubmission['contact_number'] ?? '—') ?>
                                        </small>
                                    </span>
                                </div>

                            <?php elseif ($supSubmission['status'] === 'rejected'): ?>
                                <div class="info-box" style="background:#fef2f2; border:1px solid #fecaca; color:#dc2626;">
                                    <i class="fa fa-circle-xmark"></i>
                                    <div>
                                        <p>Submission returned — please update</p>
                                        <span>Your coordinator flagged an issue. Please correct and resubmit.</span>
                                    </div>
                                </div>
                                <button type="button" onclick="openSupModal()" class="btn-action btn-primary-action"
                                    style="margin-top:6px;">
                                    <i class="fa fa-pen"></i> Update Details
                                </button>
                            <?php endif; ?>

                        <?php else: ?>
                            <div class="info-box blue">
                                <i class="fa fa-circle-info"></i>
                                <div>
                                    <p>Provide your HTE Supervisor's details</p>
                                    <span>Your OJT Coordinator will review the info and set up their system access.</span>
                                </div>
                            </div>
                            <button type="button" onclick="openSupModal()" class="btn-action btn-primary-action"
                                style="margin-top:2px;">
                                <i class="fa fa-user-plus"></i> Add Supervisor Details
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if (!$s2done): ?>
                        <div class="mark-done-block">
                            <form action="student-progress.php" method="POST">
                                <input type="hidden" name="action" value="mark_step">
                                <input type="hidden" name="step_key" value="hte_form">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="chk_hte_form" required>
                                    <label class="form-check-label" for="chk_hte_form">
                                        I have filled out the HTE Information Form and collected the required registration
                                        documents.
                                    </label>
                                </div>
                                <button type="submit" class="btn-action btn-green-action">
                                    <i class="fa fa-check"></i> Mark as Done
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- STEP 3: Addendum -->
            <?php $s3done = $checklist['addendum'] ?? false; ?>
            <div class="step-card <?= $s3done ? 'is-done' : 'is-active' ?>">
                <div class="step-header" onclick="toggle(this)">
                    <div class="step-num <?= $s3done ? 'sn-done' : 'sn-active' ?>">
                        <?= $s3done ? '<i class="fa fa-check"></i>' : '3' ?>
                    </div>
                    <div class="step-meta">
                        <h3>Addendum for Student Intern Placement</h3>
                        <p>Auto-generated — download and submit to college</p>
                    </div>
                    <div class="step-right">
                        <span class="pill <?= $s3done ? 'pill-done' : 'pill-active' ?>">
                            <?= $s3done ? '<i class="fa fa-check"></i> Done' : 'Pending' ?>
                        </span>
                        <span class="chevron"><i class="fa fa-chevron-down"></i></span>
                    </div>
                </div>
                <div class="step-body">
                    <?php if ($s3done): ?>
                        <div class="confirmed">
                            <i class="fa fa-check-circle"></i> Marked complete &middot;
                            <?= stepDate($progress, 'addendum') ?>
                        </div>
                    <?php endif; ?>
                    <div class="info-box blue">
                        <i class="fa fa-circle-info"></i>
                        <div>
                            <p>Automatically generated for you</p>
                            <span>Your internship and student information has been pre-filled. You can also download a
                                CSV template to input data for yourself and groupmates to auto-populate the
                                addendum.</span>
                        </div>
                    </div>
                    <div class="action-row">
                        <?php if ($internship_id): ?>
                            <a href="mou-preview.php?id=<?= $internship_id ?>&student_id=<?= $student_id ?>&action=addendum"
                                target="_blank" class="btn-action btn-outline-action">
                                <i class="fa fa-eye"></i> Preview
                            </a>
                            <a href="download-form.php?id=<?= $internship_id ?>&action=addendum"
                                class="btn-action btn-primary-action">
                                <i class="fa fa-download"></i> Download Addendum
                            </a>
                            <a href="download-form.php?id=<?= $internship_id ?>&action=addendum_csv"
                                class="btn-action btn-outline-action">
                                <i class="fa fa-file-csv"></i> CSV Template
                            </a>
                        <?php else: ?>
                            <span class="pill pill-idle"><i class="fa fa-lock"></i> Select an internship first</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!$s3done): ?>
                        <div class="mark-done-block">
                            <form action="student-progress.php" method="POST">
                                <input type="hidden" name="action" value="mark_step">
                                <input type="hidden" name="step_key" value="addendum">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="chk_addendum" required>
                                    <label class="form-check-label" for="chk_addendum">
                                        I have downloaded the Addendum and submitted it to the college.
                                    </label>
                                </div>
                                <button type="submit" class="btn-action btn-green-action">
                                    <i class="fa fa-check"></i> Mark as Done
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- STEP 4: Recommendation Letter -->
            <?php
            $s4done = $checklist['reco_letter'] ?? false;
            $s4na = !$needs_reco_letter;
            $s4cls = $s4na ? 'is-na' : ($s4done ? 'is-done' : 'is-active');
            ?>
            <div class="step-card <?= $s4cls ?>">
                <div class="step-header" onclick="toggle(this)">
                    <div class="step-num <?= ($s4done || $s4na) ? 'sn-done' : 'sn-active' ?>">
                        <?= ($s4done || $s4na) ? '<i class="fa fa-check"></i>' : '4' ?>
                    </div>
                    <div class="step-meta">
                        <h3>Recommendation Letter</h3>
                        <p><?= $s4na ? 'Not required for your HTE type'
                            : ($s4done ? 'Signed by Chairperson &amp; submitted to HTE'
                                : 'Download, have Chairperson sign, submit to HTE') ?></p>
                    </div>
                    <div class="step-right">
                        <span class="pill <?= $s4na ? 'pill-na' : ($s4done ? 'pill-done' : 'pill-active') ?>">
                            <?= $s4na ? '<i class="fa fa-minus"></i> N/A'
                                : ($s4done ? '<i class="fa fa-check"></i> Done' : 'Pending') ?>
                        </span>
                        <span class="chevron"><i class="fa fa-chevron-down"></i></span>
                    </div>
                </div>
                <div class="step-body">
                    <?php if ($s4na): ?>
                        <div class="info-box green">
                            <i class="fa fa-circle-check"></i>
                            <div>
                                <p>Not required for your HTE type</p>
                                <span><?= $is_val_lgu
                                    ? 'Valenzuela LGU and its attached agencies do not require a Recommendation Letter per PLV policy.'
                                    : 'PLV internal offices do not require a Recommendation Letter.' ?></span>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php if ($s4done): ?>
                            <div class="confirmed">
                                <i class="fa fa-check-circle"></i> Marked complete &middot;
                                <?= stepDate($progress, 'reco_letter') ?>
                            </div>
                        <?php endif; ?>
                        <div class="info-box amber">
                            <i class="fa fa-signature"></i>
                            <div>
                                <p>Requires Chairperson's signature</p>
                                <span>Download the letter, have your Department Chairperson sign it, then submit it to your
                                    HTE.
                                    Note: the letter will only be issued once a MOA between your HTE and PLV exists.</span>
                            </div>
                        </div>
                        <div class="action-row">
                            <?php if ($internship_id): ?>
                                <a href="mou-preview.php?id=<?= $internship_id ?>&student_id=<?= $student_id ?>&action=rl"
                                    target="_blank" class="btn-action btn-outline-action">
                                    <i class="fa fa-eye"></i> Preview
                                </a>
                                <a href="download-form.php?id=<?= $internship_id ?>&action=rl"
                                    class="btn-action btn-primary-action">
                                    <i class="fa fa-download"></i> Download Letter
                                </a>
                            <?php else: ?>
                                <span class="pill pill-idle"><i class="fa fa-lock"></i> Select an internship first</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!$s4done): ?>
                            <div class="mark-done-block">
                                <form action="student-progress.php" method="POST">
                                    <input type="hidden" name="action" value="mark_step">
                                    <input type="hidden" name="step_key" value="reco_letter">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="chk_reco" required>
                                        <label class="form-check-label" for="chk_reco">
                                            The Recommendation Letter has been signed by the Chairperson and submitted to my
                                            HTE.
                                        </label>
                                    </div>
                                    <button type="submit" class="btn-action btn-green-action">
                                        <i class="fa fa-check"></i> Mark as Done
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- STEP 5: Waiver Form -->
            <?php
            $s5done = $checklist['waiver'] ?? false;
            $s5na = !$needs_waiver;
            $s5cls = $s5na ? 'is-na' : ($s5done ? 'is-done' : 'is-active');
            ?>
            <div class="step-card <?= $s5cls ?>">
                <div class="step-header" onclick="toggle(this)">
                    <div class="step-num <?= ($s5done || $s5na) ? 'sn-done' : 'sn-active' ?>">
                        <?= ($s5done || $s5na) ? '<i class="fa fa-check"></i>' : '5' ?>
                    </div>
                    <div class="step-meta">
                        <h3>Waiver Form</h3>
                        <p><?= $s5na ? 'Not required for PLV internal offices'
                            : ($s5done ? 'Signed by Chairperson &amp; OJT Adviser — submitted'
                                : 'Signed by Chairperson &amp; OJT Adviser — with Parent/Guardian ID copy') ?></p>
                    </div>
                    <div class="step-right">
                        <span class="pill <?= $s5na ? 'pill-na' : ($s5done ? 'pill-done' : 'pill-active') ?>">
                            <?= $s5na ? '<i class="fa fa-minus"></i> N/A'
                                : ($s5done ? '<i class="fa fa-check"></i> Done' : 'Pending') ?>
                        </span>
                        <span class="chevron"><i class="fa fa-chevron-down"></i></span>
                    </div>
                </div>
                <div class="step-body">
                    <?php if ($s5na): ?>
                        <div class="info-box green">
                            <i class="fa fa-circle-check"></i>
                            <div>
                                <p>Not required for PLV internal offices</p>
                                <span>Students interning within PLV do not need to submit a Waiver Form.</span>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php if ($s5done): ?>
                            <div class="confirmed">
                                <i class="fa fa-check-circle"></i> Marked complete &middot; <?= stepDate($progress, 'waiver') ?>
                            </div>
                        <?php endif; ?>
                        <div class="info-box amber">
                            <i class="fa fa-signature"></i>
                            <div>
                                <p>Requires two signatures</p>
                                <span>The Waiver Form (CEIT-OJTF-008) must be signed by both your
                                    <strong>Department Chairperson</strong> and your <strong>OJT Adviser</strong>,
                                    then submitted to the college.</span>
                            </div>
                        </div>
                        <div class="info-box orange">
                            <i class="fa fa-id-card"></i>
                            <div>
                                <p>Attach a copy of Parent's / Guardian's ID</p>
                                <span>Include a photocopy of your parent's or legal guardian's valid government-issued ID
                                    when submitting the signed waiver.</span>
                            </div>
                        </div>
                        <div class="action-row">
                            <?php if ($internship_id): ?>
                                <a href="mou-preview.php?id=<?= $internship_id ?>&student_id=<?= $student_id ?>&action=waiver"
                                    target="_blank" class="btn-action btn-outline-action">
                                    <i class="fa fa-eye"></i> Preview
                                </a>
                                <a href="download-form.php?id=<?= $internship_id ?>&action=waiver"
                                    class="btn-action btn-primary-action">
                                    <i class="fa fa-download"></i> Download Waiver
                                </a>
                            <?php else: ?>
                                <span class="pill pill-idle"><i class="fa fa-lock"></i> Select an internship first</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!$s5done): ?>
                            <div class="mark-done-block">
                                <form action="student-progress.php" method="POST">
                                    <input type="hidden" name="action" value="mark_step">
                                    <input type="hidden" name="step_key" value="waiver">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="chk_waiver" required>
                                        <label class="form-check-label" for="chk_waiver">
                                            The Waiver has been signed by the Chairperson and OJT Adviser, and submitted to the
                                            college with a photocopy of my parent/guardian's ID.
                                        </label>
                                    </div>
                                    <button type="submit" class="btn-action btn-green-action">
                                        <i class="fa fa-check"></i> Mark as Done
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- STEP 6: Medical Certificate -->
            <?php $s6done = $checklist['medical_cert'] ?? false; ?>
            <div class="step-card <?= $s6done ? 'is-done' : 'is-active' ?>">
                <div class="step-header" onclick="toggle(this)">
                    <div class="step-num <?= $s6done ? 'sn-done' : 'sn-active' ?>">
                        <?= $s6done ? '<i class="fa fa-check"></i>' : '6' ?>
                    </div>
                    <div class="step-meta">
                        <h3>Medical Certificate</h3>
                        <p>Fit-to-work cert from a DOH-accredited clinic — submit to PLV</p>
                    </div>
                    <div class="step-right">
                        <span class="pill <?= $s6done ? 'pill-done' : 'pill-active' ?>">
                            <?= $s6done ? '<i class="fa fa-check"></i> Done' : 'Pending' ?>
                        </span>
                        <span class="chevron"><i class="fa fa-chevron-down"></i></span>
                    </div>
                </div>
                <div class="step-body">
                    <?php if ($s6done): ?>
                        <div class="confirmed">
                            <i class="fa fa-check-circle"></i> Marked complete &middot;
                            <?= stepDate($progress, 'medical_cert') ?>
                        </div>
                    <?php endif; ?>
                    <div class="info-box blue">
                        <i class="fa fa-stethoscope"></i>
                        <div>
                            <p>Get a medical certificate from a DOH-accredited hospital or clinic</p>
                            <span>The certificate must be tagged <strong>"fit to work / fit for OJT"</strong> and signed
                                by a licensed physician. Submit a photocopy to PLV College of Engineering and IT.</span>
                        </div>
                    </div>
                    <div class="info-box amber">
                        <i class="fa fa-triangle-exclamation"></i>
                        <div>
                            <p>Physical submission required</p>
                            <span>You must personally submit a photocopy to the PLV CEIT office — this cannot be
                                submitted online.</span>
                        </div>
                    </div>
                    <?php if (!$s6done): ?>
                        <div class="mark-done-block">
                            <form action="student-progress.php" method="POST">
                                <input type="hidden" name="action" value="mark_step">
                                <input type="hidden" name="step_key" value="medical_cert">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="chk_med" required>
                                    <label class="form-check-label" for="chk_med">
                                        I have obtained my medical certificate (fit to work/OJT) and submitted a photocopy
                                        to PLV.
                                    </label>
                                </div>
                                <button type="submit" class="btn-action btn-green-action">
                                    <i class="fa fa-check"></i> Mark as Done
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- STEP 7: Internship Plan -->
            <?php $s7done = $checklist['internship_plan'] ?? false; ?>
            <div class="step-card <?= $s7done ? 'is-done' : 'is-active' ?>">
                <div class="step-header" onclick="toggle(this)">
                    <div class="step-num <?= $s7done ? 'sn-done' : 'sn-active' ?>">
                        <?= $s7done ? '<i class="fa fa-check"></i>' : '7' ?>
                    </div>
                    <div class="step-meta">
                        <h3>Internship Plan</h3>
                        <p>Fill out at the HTE site — signed by Coordinator, Supervisor &amp; you</p>
                    </div>
                    <div class="step-right">
                        <span class="pill <?= $s7done ? 'pill-done' : 'pill-active' ?>">
                            <?= $s7done ? '<i class="fa fa-check"></i> Done' : 'Pending' ?>
                        </span>
                        <span class="chevron"><i class="fa fa-chevron-down"></i></span>
                    </div>
                </div>
                <div class="step-body">
                    <?php if ($s7done): ?>
                        <div class="confirmed">
                            <i class="fa fa-check-circle"></i> Marked complete &middot;
                            <?= stepDate($progress, 'internship_plan') ?>
                        </div>
                    <?php endif; ?>
                    <div class="info-box blue">
                        <i class="fa fa-clipboard-list"></i>
                        <div>
                            <p>Complete this form at the HTE</p>
                            <span>Go to your HTE site and fill in the required data for the Internship Plan
                                (CEIT-OJTF-002).
                                It must be signed by your <strong>OJT Coordinator</strong>,
                                your <strong>HTE Supervisor</strong>, and <strong>yourself</strong>.</span>
                        </div>
                    </div>
                    <div class="info-box amber">
                        <i class="fa fa-clock"></i>
                        <div>
                            <p>Submit before completing 30% of your required hours</p>
                            <span>Per PLV policy, the Internship Plan must be submitted to your OJT Coordinator
                                before you render 30% of your total prescribed OJT hours.</span>
                        </div>
                    </div>
                    <div class="action-row">
                        <?php if ($internship_id): ?>
                            <a href="mou-preview.php?id=<?= $internship_id ?>&student_id=<?= $student_id ?>&action=internship_plan"
                                target="_blank" class="btn-action btn-outline-action">
                                <i class="fa fa-eye"></i> Preview
                            </a>
                            <a href="download-form.php?id=<?= $internship_id ?>&action=internship_plan"
                                class="btn-action btn-primary-action">
                                <i class="fa fa-download"></i> Download Internship Plan
                            </a>
                        <?php else: ?>
                            <span class="pill pill-idle"><i class="fa fa-lock"></i> Select an internship first</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!$s7done): ?>
                        <div class="mark-done-block">
                            <form action="student-progress.php" method="POST">
                                <input type="hidden" name="action" value="mark_step">
                                <input type="hidden" name="step_key" value="internship_plan">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="chk_plan" required>
                                    <label class="form-check-label" for="chk_plan">
                                        The Internship Plan has been completed, signed by all required parties,
                                        and submitted to the OJT Coordinator.
                                    </label>
                                </div>
                                <button type="submit" class="btn-action btn-green-action">
                                    <i class="fa fa-check"></i> Mark as Done
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- STEP 8: Vicinity Map -->
            <?php $s8done = $checklist['vicinity_map'] ?? false; ?>
            <div class="step-card <?= $s8done ? 'is-done' : 'is-active' ?>">
                <div class="step-header" onclick="toggle(this)">
                    <div class="step-num <?= $s8done ? 'sn-done' : 'sn-active' ?>">
                        <?= $s8done ? '<i class="fa fa-check"></i>' : '8' ?>
                    </div>
                    <div class="step-meta">
                        <h3>Vicinity Map</h3>
                        <p>Auto-generated PDF showing the route to your HTE</p>
                    </div>
                    <div class="step-right">
                        <span class="pill <?= $s8done ? 'pill-done' : 'pill-active' ?>">
                            <?= $s8done ? '<i class="fa fa-check"></i> Done' : 'Pending' ?>
                        </span>
                        <span class="chevron"><i class="fa fa-chevron-down"></i></span>
                    </div>
                </div>
                <div class="step-body">
                    <?php if ($s8done): ?>
                        <div class="confirmed">
                            <i class="fa fa-check-circle"></i> Marked complete &middot;
                            <?= stepDate($progress, 'vicinity_map') ?>
                        </div>
                    <?php endif; ?>
                    <div class="info-box blue">
                        <i class="fa fa-map-location-dot"></i>
                        <div>
                            <p>Automatically generated from your HTE address</p>
                            <span>Download the Vicinity Map PDF (CEIT-OJTF-003). It is auto-generated using your
                                selected HTE's location and must be submitted to the college.</span>
                        </div>
                    </div>
                    <div class="action-row">
                        <?php if ($internship_id): ?>
                            <a href="mou-preview.php?id=<?= $internship_id ?>&student_id=<?= $student_id ?>&action=vicinity"
                                target="_blank" class="btn-action btn-outline-action">
                                <i class="fa fa-eye"></i> Preview
                            </a>
                            <a href="download-form.php?id=<?= $internship_id ?>&action=vicinity"
                                class="btn-action btn-primary-action">
                                <i class="fa fa-download"></i> Download Vicinity Map
                            </a>
                        <?php else: ?>
                            <span class="pill pill-idle"><i class="fa fa-lock"></i> Select an internship first</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!$s8done): ?>
                        <div class="mark-done-block">
                            <form action="student-progress.php" method="POST">
                                <input type="hidden" name="action" value="mark_step">
                                <input type="hidden" name="step_key" value="vicinity_map">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="chk_map" required>
                                    <label class="form-check-label" for="chk_map">
                                        I have downloaded the Vicinity Map and submitted it to the college.
                                    </label>
                                </div>
                                <button type="submit" class="btn-action btn-green-action">
                                    <i class="fa fa-check"></i> Mark as Done
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- STEP 9: Oath of Undertaking -->
            <?php $s9done = $checklist['oath'] ?? false; ?>
            <div class="step-card <?= $s9done ? 'is-done' : 'is-active' ?>">
                <div class="step-header" onclick="toggle(this)">
                    <div class="step-num <?= $s9done ? 'sn-done' : 'sn-active' ?>">
                        <?= $s9done ? '<i class="fa fa-check"></i>' : '9' ?>
                    </div>
                    <div class="step-meta">
                        <h3>Oath of Undertaking</h3>
                        <p>Auto-generated — download, sign, and submit to college</p>
                    </div>
                    <div class="step-right">
                        <span class="pill <?= $s9done ? 'pill-done' : 'pill-active' ?>">
                            <?= $s9done ? '<i class="fa fa-check"></i> Done' : 'Pending' ?>
                        </span>
                        <span class="chevron"><i class="fa fa-chevron-down"></i></span>
                    </div>
                </div>
                <div class="step-body">
                    <?php if ($s9done): ?>
                        <div class="confirmed">
                            <i class="fa fa-check-circle"></i> Marked complete &middot; <?= stepDate($progress, 'oath') ?>
                        </div>
                    <?php endif; ?>
                    <div class="info-box blue">
                        <i class="fa fa-file-signature"></i>
                        <div>
                            <p>Automatically generated from your student profile</p>
                            <span>Download the Oath of Undertaking (CEIT-OJTF-007), sign it, and submit the original
                                to the college before your OJT begins.</span>
                        </div>
                    </div>
                    <div class="action-row">
                        <?php if ($internship_id): ?>
                            <a href="mou-preview.php?id=<?= $internship_id ?>&student_id=<?= $student_id ?>&action=oath"
                                target="_blank" class="btn-action btn-outline-action">
                                <i class="fa fa-eye"></i> Preview
                            </a>
                            <a href="download-form.php?id=<?= $internship_id ?>&action=oath"
                                class="btn-action btn-primary-action">
                                <i class="fa fa-download"></i> Download Oath
                            </a>
                        <?php else: ?>
                            <span class="pill pill-idle"><i class="fa fa-lock"></i> Select an internship first</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!$s9done): ?>
                        <div class="mark-done-block">
                            <form action="student-progress.php" method="POST">
                                <input type="hidden" name="action" value="mark_step">
                                <input type="hidden" name="step_key" value="oath">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="chk_oath" required>
                                    <label class="form-check-label" for="chk_oath">
                                        I have signed the Oath of Undertaking and submitted the original to the college.
                                    </label>
                                </div>
                                <button type="submit" class="btn-action btn-green-action">
                                    <i class="fa fa-check"></i> Mark as Done
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- STEP 10: Start OJT -->
            <?php $s10done = $checklist['ojt_started'] ?? false; ?>
            <div class="step-card <?= $s10done ? 'is-done' : 'is-active' ?>">
                <div class="step-header" onclick="toggle(this)">
                    <div class="step-num <?= $s10done ? 'sn-done' : 'sn-active' ?>">
                        <?= $s10done ? '<i class="fa fa-check"></i>' : '10' ?>
                    </div>
                    <div class="step-meta">
                        <h3>Start OJT</h3>
                        <p>Download and submit the OJT Approval document to begin your internship</p>
                    </div>
                    <div class="step-right">
                        <span class="pill <?= $s10done ? 'pill-done' : 'pill-active' ?>">
                            <?= $s10done ? '<i class="fa fa-check"></i> Done' : 'Pending' ?>
                        </span>
                        <span class="chevron"><i class="fa fa-chevron-down"></i></span>
                    </div>
                </div>
                <div class="step-body">
                    <div class="info-box blue">
                        <i class="fa fa-circle-info"></i>
                        <div>
                            <p>Download the OJT Approval document</p>
                            <span>Download and print the approval document, then have it signed by the required parties
                                and submitted to the CEIT OJT office before you begin your internship.</span>
                        </div>
                    </div>
                    <div class="action-row">
                        <a href="mou-preview.php?action=approval" target="_blank" class="btn-action btn-outline-action">
                            <i class="fa fa-eye"></i> Preview
                        </a>
                        <a href="download-form.php?action=approval" class="btn-action btn-primary-action">
                            <i class="fa fa-download"></i> Download Approval Document
                        </a>
                    </div>
                </div>
            </div>

        </div><!-- /.checklist -->
    </div><!-- /.page-wrap -->

    <!-- HTE Supervisor Modal -->
    <div id="supModal" class="sup-modal-overlay">
        <div class="sup-modal-box">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 style="font-family:'Syne',sans-serif; font-size:17px; font-weight:700; margin:0;">
                    HTE Supervisor Details
                </h2>
                <button onclick="closeSupModal()"
                    style="background:none; border:none; font-size:1.3rem; cursor:pointer; color:#888; line-height:1;">
                    &times;
                </button>
            </div>

            <form action="student-progress.php" method="POST">
                <input type="hidden" name="action" value="submit_hte_supervisor">
                <input type="hidden" name="internship_id" value="<?= $internship_id ?>">

                <div style="display:flex; flex-direction:column; gap:14px;">

                    <div>
                        <label
                            style="font-size:12px; font-weight:600; color:var(--gray-600); display:block; margin-bottom:5px;">
                            Full Name <span style="color:red;">*</span>
                        </label>
                        <input type="text" name="sup_full_name" required
                            value="<?= htmlspecialchars($supSubmission['full_name'] ?? '') ?>"
                            placeholder="e.g. Juan dela Cruz" style="width:100%; padding:9px 12px; border:1.5px solid var(--gray-200);
                                border-radius:8px; font-size:13px; font-family:'DM Sans',sans-serif;
                                outline:none; transition:border-color .2s;"
                            onfocus="this.style.borderColor='var(--brand)'"
                            onblur="this.style.borderColor='var(--gray-200)'">
                    </div>

                    <div>
                        <label
                            style="font-size:12px; font-weight:600; color:var(--gray-600); display:block; margin-bottom:5px;">
                            Email Address
                        </label>
                        <input type="email" name="sup_email"
                            value="<?= htmlspecialchars($supSubmission['email'] ?? '') ?>"
                            placeholder="e.g. supervisor@company.com" style="width:100%; padding:9px 12px; border:1.5px solid var(--gray-200);
                                border-radius:8px; font-size:13px; font-family:'DM Sans',sans-serif;
                                outline:none; transition:border-color .2s;"
                            onfocus="this.style.borderColor='var(--brand)'"
                            onblur="this.style.borderColor='var(--gray-200)'">
                    </div>

                    <div>
                        <label
                            style="font-size:12px; font-weight:600; color:var(--gray-600); display:block; margin-bottom:5px;">
                            Contact Number
                        </label>
                        <input type="text" name="sup_contact"
                            value="<?= htmlspecialchars($supSubmission['contact_number'] ?? '') ?>"
                            placeholder="e.g. 09XX-XXX-XXXX" style="width:100%; padding:9px 12px; border:1.5px solid var(--gray-200);
                                border-radius:8px; font-size:13px; font-family:'DM Sans',sans-serif;
                                outline:none; transition:border-color .2s;"
                            onfocus="this.style.borderColor='var(--brand)'"
                            onblur="this.style.borderColor='var(--gray-200)'">
                    </div>

                    <div>
                        <label
                            style="font-size:12px; font-weight:600; color:var(--gray-600); display:block; margin-bottom:5px;">
                            Company / HTE Name
                        </label>
                        <input type="text" name="sup_company" readonly
                            value="<?= htmlspecialchars($selectedInternship['company'] ?? '') ?>" style="width:100%; padding:9px 12px; border:1.5px solid var(--gray-200);
                                border-radius:8px; font-size:13px; font-family:'DM Sans',sans-serif;
                                background:var(--gray-100); color:var(--gray-600); outline:none;">
                        <small style="font-size:11px; color:var(--gray-400); margin-top:3px; display:block;">
                            Auto-filled from your selected internship
                        </small>
                    </div>

                    <div style="display:flex; gap:8px; justify-content:flex-end; margin-top:4px;">
                        <button type="button" onclick="closeSupModal()" class="btn-action btn-outline-action">
                            Cancel
                        </button>
                        <button type="submit" class="btn-action btn-green-action">
                            <i class="fa fa-paper-plane"></i> Submit
                        </button>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggle(header) {
            header.closest('.step-card').classList.toggle('is-open');
        }

        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(a => {
                a.classList.remove('show');
                setTimeout(() => a.remove(), 300);
            });
        }, 4000);

        function openSupModal() {
            document.getElementById('supModal').classList.add('is-open');
            document.body.style.overflow = 'hidden';
        }
        function closeSupModal() {
            document.getElementById('supModal').classList.remove('is-open');
            document.body.style.overflow = '';
        }
        document.getElementById('supModal').addEventListener('click', function (e) {
            if (e.target === this) closeSupModal();
        });

        <?php if ($supSubmission && $supSubmission['status'] === 'rejected'): ?>
            document.addEventListener('DOMContentLoaded', openSupModal);
        <?php endif; ?>
    </script>
</body>

</html>