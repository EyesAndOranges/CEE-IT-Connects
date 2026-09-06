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

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/ojt-pdf-common.php';
use setasign\Fpdi\Tcpdf\Fpdi;

$role = $_SESSION['role'];

// Determine mode 
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

// Fetch student 
$sStmt = $pdo->prepare("SELECT full_name, student_id, program FROM students WHERE id = ?");
$sStmt->execute([$studentId]);
$student = $sStmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    http_response_code(404);
    exit('Student not found.');
}

// Fetch evaluation (different table per role
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

//lookup
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

$RATING_COLS_STUDENT = [159.55, 166.90, 174.19, 181.53];

// function drawRating(Fpdi $pdf, array $cols, float $rowY, int $rating): void
// {
//     if ($rating < 1 || $rating > 4) {
//         return;
//     }
//     $cx = $cols[$rating - 1];
//     $pdf->SetLineWidth(0.55);
//     $pdf->SetDrawColor(41, 51, 92); // #29335C
//     $pdf->Ellipse($cx, $rowY, 3.1, 2.3, 0, 0, 360, 'D');
//     $pdf->SetLineWidth(0.2);
// }

// function drawCheck(Fpdi $pdf, float $x, float $y): void
// {
//     $pdf->SetFont('Helvetica', 'B', 10);
//     $pdf->SetTextColor(41, 51, 92);
//     $pdf->SetXY($x, $y);
//     $pdf->Cell(5, 5, chr(10003), 0, 0, 'C');
//     $pdf->SetTextColor(0, 0, 0);
//     $pdf->SetFont('Helvetica', '', 9);
// }

$pdf->setSourceFile($templatePdf);

if ($isSupervisor):
    // ════════════════════════════════════════════════════════════════════
    // ⚠️ UNVERIFIED — same class of bug as the student form is very likely
    // present here (flat 5mm row-height assumption, unmeasured column X).
    // These coordinates have NOT been re-measured against the actual
    // CEIT-OJTF-010 template. Upload that template and re-run the same
    // extraction used for the student form before trusting this output.
    // ════════════════════════════════════════════════════════════════════
    $ratingX = 177; // ⚠️ unverified — same placeholder as before

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

    // ⚠️ Unverified rating positions below — old filled-box helper kept
    // temporarily so this branch doesn't break, but it still has the
    // original bug. Do not treat this as fixed.
    $legacyRatingCols = [];
    for ($i = 1; $i <= 4; $i++) {
        $legacyRatingCols[] = $ratingX + ($i - 1) * 5.2;
    }

    // Learning Skills
    $lsY = [75.0, 80.0, 85.0];
    $lsKeys = ['learn_questions', 'learn_resources', 'learn_accountability'];
    foreach ($lsKeys as $i => $key)
        drawRating($pdf, $legacyRatingCols, $lsY[$i], (int) ($eval[$key] ?? 0));

    // Reading/Writing
    $rwY = [96.0, 101.0, 106.0];
    $rwKeys = ['rw_written', 'rw_communication', 'rw_math'];
    foreach ($rwKeys as $i => $key)
        drawRating($pdf, $legacyRatingCols, $rwY[$i], (int) ($eval[$key] ?? 0));

    // Verbal
    $vbY = [117.0, 122.0, 127.0];
    $vbKeys = ['verbal_listens', 'verbal_meetings', 'verbal_proficiency'];
    foreach ($vbKeys as $i => $key)
        drawRating($pdf, $legacyRatingCols, $vbY[$i], (int) ($eval[$key] ?? 0));

    // Creative / Problem Solving
    $crY = [138.0, 143.0, 148.0];
    $crKeys = ['creative_divides', 'creative_brainstorm', 'creative_solves'];
    foreach ($crKeys as $i => $key)
        drawRating($pdf, $legacyRatingCols, $crY[$i], (int) ($eval[$key] ?? 0));

    // Career / Professional Dev
    $pdY = [159.0, 164.0, 169.0];
    $pdKeys = ['career_proactive', 'career_priorities', 'career_demeanor'];
    foreach ($pdKeys as $i => $key)
        drawRating($pdf, $legacyRatingCols, $pdY[$i], (int) ($eval[$key] ?? 0));

    // Teamwork
    $tmY = [180.0, 185.0, 190.0];
    $tmKeys = ['team_conflicts', 'team_collaborative', 'team_assertiveness'];
    foreach ($tmKeys as $i => $key)
        drawRating($pdf, $legacyRatingCols, $tmY[$i], (int) ($eval[$key] ?? 0));

    // Organization
    $ogY = [201.0, 206.0, 211.0];
    $ogKeys = ['org_objectives', 'org_standards', 'org_channels'];
    foreach ($ogKeys as $i => $key)
        drawRating($pdf, $legacyRatingCols, $ogY[$i], (int) ($eval[$key] ?? 0));

    // Work Habits
    $whY = [222.0, 227.0, 232.0];
    $whKeys = ['work_punctual', 'work_attitude', 'work_dresscode'];
    foreach ($whKeys as $i => $key)
        drawRating($pdf, $legacyRatingCols, $whY[$i], (int) ($eval[$key] ?? 0));

    // Character
    $caY = [243.0, 248.0, 253.0];
    $caKeys = ['char_ethics', 'char_principled', 'char_diversity'];
    foreach ($caKeys as $i => $key)
        drawRating($pdf, $legacyRatingCols, $caY[$i], (int) ($eval[$key] ?? 0));

    // Industry Skills
    $isY = [264.0, 269.0, 274.0];
    $isKeys = ['industry_proficiency', 'industry_willingness', 'industry_additional'];
    foreach ($isKeys as $i => $key)
        drawRating($pdf, $legacyRatingCols, $isY[$i], (int) ($eval[$key] ?? 0));

    // ── Page 2 ────────────────────────────────────────────────────────────
    $pdf->AddPage();
    $tpl2 = $pdf->importPage(2);
    $pdf->useTemplate($tpl2, 0, 0, 210, 297);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(0, 0, 0);

    // Overall intern rating
    drawRating($pdf, $legacyRatingCols, 45.0, (int) ($eval['overall_intern_rating'] ?? 0));

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
    drawRating($pdf, $legacyRatingCols, 215.0, (int) ($eval['overall_internship_rating'] ?? 0));

    // Signature line
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetXY(20, 261);
    $pdf->Cell(80, 4, $supervisorName, 0, 0, 'L');
    $pdf->SetXY(135, 261);
    $pdf->Cell(50, 4, $submittedAt, 0, 0, 'L');
else:
    renderStudentEvaluationPdf($pdf, $templatePdf, $eval, [
        'student_name' => $studentName,
        'student_no' => $studentNo,
        'program' => $program,
        'company_name' => $companyName,
        'supervisor_name' => $supervisorName,
        'coord_name' => $coordName,
        'submitted_at' => $submittedAt,
    ]);
endif;

// ── Output ────────────────────────────────────────────────────────────────
header('Content-Type: application/pdf');
$safeName = preg_replace('/[^a-zA-Z0-9_]/', '_', $studentName);
$filename = $filenamePrefix . '_' . $safeName . '.pdf';
$pdf->Output($filename, 'D');
exit;