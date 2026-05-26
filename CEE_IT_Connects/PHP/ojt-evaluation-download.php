<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'db.php';
require 'auth.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Unauthorized — session dump: ' . print_r($_SESSION, true));
}

// Also check role
$role = $_SESSION['role'] ?? 'MISSING';
if (!in_array($role, ['internship_adviser', 'hte_adviser', 'supervisor', 'student'])) {
    exit('Unauthorized — role is: ' . $role);
}

// require_once __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/vendor/autoload.php';
use setasign\Fpdi\Tcpdf\Fpdi;

$role = $_SESSION['role'];

// ── Determine mode ────────────────────────────────────────────────────────
// Supervisors/advisers pass ?student_id=X; students use their own session ID
$isSupervisor = in_array($role, ['internship_adviser', 'hte_adviser', 'supervisor'])
    && isset($_GET['student_id']);

if ($isSupervisor) {
    $studentId = (int) $_GET['student_id'];
} elseif ($role === 'student') {
    $studentId = (int) $_SESSION['user_id'];
} else {
    http_response_code(403);
    exit('Unauthorized');
}

// ── Fetch student ─────────────────────────────────────────────────────────
$sStmt = $pdo->prepare("SELECT full_name, student_id, program FROM students WHERE id = ?");
$sStmt->execute([$studentId]);
$student = $sStmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    http_response_code(404);
    exit('Student not found.');
}

// ── Fetch evaluation (different table per role) ───────────────────────────
if ($isSupervisor) {
    $stmt = $pdo->prepare("SELECT * FROM ojt_evaluations_supervisor WHERE student_id = ?");
} else {
    $stmt = $pdo->prepare("SELECT * FROM ojt_evaluations_student WHERE student_id = ?");
}
$stmt->execute([$studentId]);
$eval = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$eval) {
    http_response_code(404);
    exit('No evaluation found.');
}

// ── Shared lookups ────────────────────────────────────────────────────────
$coordStmt = $pdo->prepare("
    SELECT a.full_name FROM advisers a
    JOIN rooms r ON r.adviser_id = a.id
    JOIN room_members rm ON rm.room_id = r.id
    WHERE rm.user_id = ? AND rm.user_type = 'student' AND r.is_archived = FALSE
    LIMIT 1
");
$coordStmt->execute([$studentId]);
$coordName = $coordStmt->fetchColumn() ?: '';

$companyStmt = $pdo->prepare("
    SELECT i.company
    FROM internships i
    JOIN internship_bookmarks ib ON ib.internship_id = i.id
    WHERE ib.student_id = ?
    ORDER BY ib.created_at DESC
    LIMIT 1
");
$companyStmt->execute([$studentId]);
$companyName = $companyStmt->fetchColumn() ?: '';

$supervisorStmt = $pdo->prepare("
    SELECT a.full_name
    FROM advisers a
    JOIN room_members rm ON rm.user_id = a.id
    JOIN room_members rm_s ON rm_s.room_id = rm.room_id
    WHERE rm_s.user_id = ?
      AND rm_s.user_type = 'student'
      AND rm.user_type = 'hte_adviser'
      AND a.role = 'HTE_adviser'
    LIMIT 1
");
$supervisorStmt->execute([$studentId]);
$supervisorName = $supervisorStmt->fetchColumn() ?: '';

$studentName = $student['full_name'] ?? '';
$studentNo = $student['student_id'] ?? '';
$program = $student['program'] ?? '';
$submittedAt = date('m/d/Y', strtotime($eval['submitted_at']));

// ── Template path (swap based on mode) ───────────────────────────────────
if ($isSupervisor) {
    $templatePdf = __DIR__ . '/../Sources/forms/CEIT-OJTF-010_Supervisors_Evaluation_of_Student_Intern.pdf';
    $filenamePrefix = 'CEIT-OJTF-010';
} else {
    $templatePdf = __DIR__ . '/../Sources/forms/CEIT-OJTF-011_Students_Evaluation_of_Internship.pdf';
    $filenamePrefix = 'CEIT-OJTF-011';
}

if (!file_exists($templatePdf)) {
    http_response_code(500);
    exit('Template PDF not found: ' . $templatePdf);
}

// ── Shared helpers ────────────────────────────────────────────────────────
$pdf = new Fpdi('P', 'mm', 'A4');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false);
$pdf->SetFont('Helvetica', '', 9);

function drawRating(Fpdi $pdf, float $x, float $y, int $rating): void
{
    $pdf->SetFont('Helvetica', '', 8.5);
    for ($i = 1; $i <= 4; $i++) {
        $pdf->SetXY($x + ($i - 1) * 5.2, $y);
        if ($i === $rating) {
            $pdf->SetFillColor(41, 51, 92);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell(4.5, 4.5, (string) $i, 0, 0, 'C', true);
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('Helvetica', '', 8.5);
        }
    }
}

function drawCheck(Fpdi $pdf, float $x, float $y): void
{
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetTextColor(41, 51, 92);
    $pdf->SetXY($x, $y);
    $pdf->Cell(5, 5, chr(10003), 0, 0, 'C');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Helvetica', '', 9);
}

$pdf->setSourceFile($templatePdf);
$ratingX = 177;

// ══════════════════════════════════════════════════════════════════════════
if ($isSupervisor):
    // ══════════════════════════════════════════════════════════════════════════

    // ── Page 1 ────────────────────────────────────────────────────────────
    $pdf->AddPage();
    $tpl1 = $pdf->importPage(1);
    $pdf->useTemplate($tpl1, 0, 0, 210, 297);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(0, 0, 0);

    // Header
    $pdf->SetXY(42, 46.5);
    $pdf->Cell(80, 4, $studentName, 0, 0, 'L');
    $pdf->SetXY(148, 46.5);
    $pdf->Cell(55, 4, $program . ' / ' . $studentNo, 0, 0, 'L');
    $pdf->SetXY(42, 51.5);
    $pdf->Cell(160, 4, $companyName, 0, 0, 'L');
    $pdf->SetXY(55, 56.5);
    $pdf->Cell(148, 4, $supervisorName, 0, 0, 'L');
    $pdf->SetXY(42, 61.5);
    $pdf->Cell(160, 4, $coordName, 0, 0, 'L');

    // Learning Skills
    $lsY = [75.0, 80.0, 85.0];
    $lsKeys = ['learn_questions', 'learn_resources', 'learn_accountability'];
    foreach ($lsKeys as $i => $key)
        drawRating($pdf, $ratingX, $lsY[$i], (int) ($eval[$key] ?? 0));

    // Reading/Writing
    $rwY = [96.0, 101.0, 106.0];
    $rwKeys = ['rw_written', 'rw_communication', 'rw_math'];
    foreach ($rwKeys as $i => $key)
        drawRating($pdf, $ratingX, $rwY[$i], (int) ($eval[$key] ?? 0));

    // Verbal
    $vbY = [117.0, 122.0, 127.0];
    $vbKeys = ['verbal_listens', 'verbal_meetings', 'verbal_proficiency'];
    foreach ($vbKeys as $i => $key)
        drawRating($pdf, $ratingX, $vbY[$i], (int) ($eval[$key] ?? 0));

    // Creative / Problem Solving
    $crY = [138.0, 143.0, 148.0];
    $crKeys = ['creative_divides', 'creative_brainstorm', 'creative_solves'];
    foreach ($crKeys as $i => $key)
        drawRating($pdf, $ratingX, $crY[$i], (int) ($eval[$key] ?? 0));

    // Career / Professional Dev
    $pdY = [159.0, 164.0, 169.0];
    $pdKeys = ['career_proactive', 'career_priorities', 'career_demeanor'];
    foreach ($pdKeys as $i => $key)
        drawRating($pdf, $ratingX, $pdY[$i], (int) ($eval[$key] ?? 0));

    // Teamwork
    $tmY = [180.0, 185.0, 190.0];
    $tmKeys = ['team_conflicts', 'team_collaborative', 'team_assertiveness'];
    foreach ($tmKeys as $i => $key)
        drawRating($pdf, $ratingX, $tmY[$i], (int) ($eval[$key] ?? 0));

    // Organization
    $ogY = [201.0, 206.0, 211.0];
    $ogKeys = ['org_objectives', 'org_standards', 'org_channels'];
    foreach ($ogKeys as $i => $key)
        drawRating($pdf, $ratingX, $ogY[$i], (int) ($eval[$key] ?? 0));

    // Work Habits
    $whY = [222.0, 227.0, 232.0];
    $whKeys = ['work_punctual', 'work_attitude', 'work_dresscode'];
    foreach ($whKeys as $i => $key)
        drawRating($pdf, $ratingX, $whY[$i], (int) ($eval[$key] ?? 0));

    // Character
    $caY = [243.0, 248.0, 253.0];
    $caKeys = ['char_ethics', 'char_principled', 'char_diversity'];
    foreach ($caKeys as $i => $key)
        drawRating($pdf, $ratingX, $caY[$i], (int) ($eval[$key] ?? 0));

    // Industry Skills
    $isY = [264.0, 269.0, 274.0];
    $isKeys = ['industry_proficiency', 'industry_willingness', 'industry_additional'];
    foreach ($isKeys as $i => $key)
        drawRating($pdf, $ratingX, $isY[$i], (int) ($eval[$key] ?? 0));

    // ── Page 2 ────────────────────────────────────────────────────────────
    $pdf->AddPage();
    $tpl2 = $pdf->importPage(2);
    $pdf->useTemplate($tpl2, 0, 0, 210, 297);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(0, 0, 0);

    // Overall intern rating
    drawRating($pdf, $ratingX, 45.0, (int) ($eval['overall_intern_rating'] ?? 0));

    // Comments
    $pdf->SetFont('Helvetica', '', 8.5);
    $pdf->SetXY(15, 65);
    $pdf->MultiCell(180, 4.5, $eval['comment_impact'] ?? '', 0, 'L');
    $pdf->SetXY(15, 95);
    $pdf->MultiCell(180, 4.5, $eval['comment_strengths'] ?? '', 0, 'L');
    $pdf->SetXY(15, 125);
    $pdf->MultiCell(180, 4.5, $eval['comment_improvements'] ?? '', 0, 'L');

    // Suggestions
    $pdf->SetXY(15, 160);
    $pdf->MultiCell(180, 4.5, $eval['suggestions'] ?? '', 0, 'L');

    // Would supervise again — Yes/No
    if ($eval['would_supervise_again']) {
        drawCheck($pdf, 156.5, 180.0); // Yes
    } else {
        drawCheck($pdf, 174.5, 180.0); // No
    }
    $pdf->SetFont('Helvetica', '', 8.5);
    $pdf->SetXY(15, 195);
    $pdf->MultiCell(180, 4.5, $eval['would_supervise_reason'] ?? '', 0, 'L');

    // Overall internship rating
    drawRating($pdf, $ratingX, 215.0, (int) ($eval['overall_internship_rating'] ?? 0));

    // Signature line
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetXY(20, 261);
    $pdf->Cell(80, 4, $supervisorName, 0, 0, 'L');
    $pdf->SetXY(135, 261);
    $pdf->Cell(50, 4, $submittedAt, 0, 0, 'L');

    // ══════════════════════════════════════════════════════════════════════════
else: // student download — your original logic unchanged
// ══════════════════════════════════════════════════════════════════════════

    // ── Page 1 ────────────────────────────────────────────────────────────
    $pdf->AddPage();
    $tpl1 = $pdf->importPage(1);
    $pdf->useTemplate($tpl1, 0, 0, 210, 297);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(0, 0, 0);

    $pdf->SetXY(42, 46.5);
    $pdf->Cell(80, 4, $studentName, 0, 0, 'L');
    $pdf->SetXY(148, 46.5);
    $pdf->Cell(55, 4, $program . ' / ' . $studentNo, 0, 0, 'L');
    $pdf->SetXY(42, 51.5);
    $pdf->Cell(160, 4, $companyName, 0, 0, 'L');
    $pdf->SetXY(55, 56.5);
    $pdf->Cell(148, 4, $supervisorName, 0, 0, 'L');
    $pdf->SetXY(42, 61.5);
    $pdf->Cell(160, 4, $coordName, 0, 0, 'L');

    $siteY = [108.2, 113.2, 118.2, 123.2];
    $siteKeys = ['site_secure', 'site_orientation', 'site_resources', 'site_colleagues'];
    foreach ($siteKeys as $i => $key)
        drawRating($pdf, $ratingX, $siteY[$i], (int) ($eval[$key] ?? 0));

    $supY = [134.5, 139.5, 144.5, 149.5, 154.5];
    $supKeys = ['sup_job_desc', 'sup_feedback', 'sup_learning', 'sup_duties', 'sup_schedule'];
    foreach ($supKeys as $i => $key)
        drawRating($pdf, $ratingX, $supY[$i], (int) ($eval[$key] ?? 0));

    $learnY = [166.0, 171.0, 176.5, 181.5, 186.5, 191.5, 196.5, 201.5];
    $learnKeys = [
        'learn_aligned',
        'learn_verbal',
        'learn_interpersonal',
        'learn_creativity',
        'learn_problem',
        'learn_critical',
        'learn_writing',
        'learn_career'
    ];
    foreach ($learnKeys as $i => $key)
        drawRating($pdf, $ratingX, $learnY[$i], (int) ($eval[$key] ?? 0));

    $heiY = [218.5, 223.5, 228.5, 233.5, 240.0, 246.0, 251.0, 256.0];
    $heiKeys = [
        'hei_prepared',
        'hei_guidance',
        'hei_supported',
        'hei_communication',
        'hei_coursework',
        'hei_goals',
        'hei_valuable',
        'hei_satisfied'
    ];
    foreach ($heiKeys as $i => $key)
        drawRating($pdf, $ratingX, $heiY[$i], (int) ($eval[$key] ?? 0));

    $coordY = [268.5, 273.5, 279.0, 284.5, 290.0];
    $coordKeys = ['coord_instructions', 'coord_goals', 'coord_responsive', 'coord_feedback', 'coord_challenges'];
    foreach ($coordKeys as $i => $key)
        drawRating($pdf, $ratingX, $coordY[$i], (int) ($eval[$key] ?? 0));

    // ── Page 2 ────────────────────────────────────────────────────────────
    $pdf->AddPage();
    $tpl2 = $pdf->importPage(2);
    $pdf->useTemplate($tpl2, 0, 0, 210, 297);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(0, 0, 0);

    drawRating($pdf, $ratingX, 57.0, (int) ($eval['overall_rating'] ?? 0));

    if ($eval['was_paid']) {
        drawCheck($pdf, 156.5, 61.5);
        $payTypeX = ['Hourly' => 94.5, 'Daily' => 116.0, 'Stipend/Allowance' => 133.0];
        if (isset($payTypeX[$eval['pay_type']]))
            drawCheck($pdf, $payTypeX[$eval['pay_type']], 67.0);
        if ($eval['pay_amount']) {
            $pdf->SetXY(120, 72.5);
            $pdf->Cell(40, 4, number_format((float) $eval['pay_amount'], 2), 0, 0, 'L');
        }
    } else {
        drawCheck($pdf, 174.5, 61.5);
    }

    $assessY = [77.0, 82.0, 87.0, 92.5];
    $assessKeys = ['recommend_internship', 'work_supervisor_again', 'work_coordinator_again', 'recommend_hte'];
    foreach ($assessKeys as $i => $key)
        drawRating($pdf, $ratingX, $assessY[$i], (int) ($eval[$key] ?? 0));

    $pdf->SetFont('Helvetica', '', 8.5);
    $pdf->SetXY(15, 113);
    $pdf->MultiCell(180, 4.5, $eval['most_valuable'] ?? '', 0, 'L');
    $pdf->SetXY(15, 148);
    $pdf->MultiCell(180, 4.5, $eval['least_valuable'] ?? '', 0, 'L');
    $pdf->SetXY(15, 183);
    $pdf->MultiCell(180, 4.5, $eval['concerns'] ?? '', 0, 'L');
    $pdf->SetXY(15, 218);
    $pdf->MultiCell(180, 4.5, $eval['suggestions'] ?? '', 0, 'L');

    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetXY(20, 261);
    $pdf->Cell(80, 4, $studentName, 0, 0, 'L');
    $pdf->SetXY(135, 261);
    $pdf->Cell(50, 4, $submittedAt, 0, 0, 'L');

endif;

// ── Output ────────────────────────────────────────────────────────────────
header('Content-Type: application/pdf');
$safeName = preg_replace('/[^a-zA-Z0-9_]/', '_', $studentName);
$filename = $filenamePrefix . '_' . $safeName . '.pdf';
$pdf->Output($filename, 'D');
exit;