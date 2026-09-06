<?php
// ojt-pdf-common.php
use setasign\Fpdi\Tcpdf\Fpdi;

const RATING_COLS_STUDENT = [159.55, 166.90, 174.19, 181.53];

function drawRating(Fpdi $pdf, array $cols, float $rowY, int $rating): void
{
    if ($rating < 1 || $rating > 4) {
        return;
    }
    $cx = $cols[$rating - 1];
    $pdf->SetLineWidth(0.55);
    $pdf->SetDrawColor(41, 51, 92);
    $pdf->Ellipse($cx, $rowY, 3.1, 2.3, 0, 0, 360, 'D');
    $pdf->SetLineWidth(0.2);
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

/**
 * Renders the CEIT-OJTF-011 (student) evaluation onto $pdf using $eval
 * (rating/text field values) and $meta (student/company/supervisor/coord
 * names + submitted date). Used by both the post-submission download
 * script and the pre-submission live-preview script, so both stay in
 * sync with a single set of coordinates.
 */
function renderStudentEvaluationPdf(Fpdi $pdf, string $templatePdf, array $eval, array $meta): void
{
    $pdf->setSourceFile($templatePdf);
    $cols = RATING_COLS_STUDENT;

    // ── Page 1 ───────────────────────────────────────────────────────
    $pdf->AddPage();
    $tpl1 = $pdf->importPage(1);
    $pdf->useTemplate($tpl1, 0, 0, 210, 297);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(0, 0, 0);

    $pdf->SetXY(60, 37.5);
    $pdf->Cell(80, 4, $meta['student_name'], 0, 0, 'L');
    $pdf->SetXY(148, 37.5);
    $pdf->Cell(55, 4, $meta['program'] . ' / ' . $meta['student_no'], 0, 0, 'L');
    $pdf->SetXY(60, 42.5);
    $pdf->Cell(160, 4, $meta['company_name'], 0, 0, 'L');
    $pdf->SetXY(70, 46.5);
    $pdf->Cell(148, 4, $meta['supervisor_name'], 0, 0, 'L');
    $pdf->SetXY(68, 50.5);
    $pdf->Cell(160, 4, $meta['coord_name'], 0, 0, 'L');

    $siteY = [104.5, 108.3, 112.1, 116.0];
    $siteKeys = ['site_secure', 'site_orientation', 'site_resources', 'site_colleagues'];
    foreach ($siteKeys as $i => $key)
        drawRating($pdf, $cols, $siteY[$i], (int) ($eval[$key] ?? 0));

    $supY = [127.6, 131.4, 135.2, 139.0, 142.8];
    $supKeys = ['sup_job_desc', 'sup_feedback', 'sup_learning', 'sup_duties', 'sup_schedule'];
    foreach ($supKeys as $i => $key)
        drawRating($pdf, $cols, $supY[$i], (int) ($eval[$key] ?? 0));

    $learnY = [154.5, 158.3, 162.1, 165.9, 169.7, 173.5, 177.3, 181.1];
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
        drawRating($pdf, $cols, $learnY[$i], (int) ($eval[$key] ?? 0));

    $heiY = [201.8, 205.6, 209.4, 213.2, 217.0, 224.5, 228.2, 232.0];
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
        drawRating($pdf, $cols, $heiY[$i], (int) ($eval[$key] ?? 0));

    $coordY = [243.7, 247.5, 251.3, 258.7, 266.2];
    $coordKeys = ['coord_instructions', 'coord_goals', 'coord_responsive', 'coord_feedback', 'coord_challenges'];
    foreach ($coordKeys as $i => $key)
        drawRating($pdf, $cols, $coordY[$i], (int) ($eval[$key] ?? 0));

    // ── Page 2 ───────────────────────────────────────────────────────
    $pdf->AddPage();
    $tpl2 = $pdf->importPage(2);
    $pdf->useTemplate($tpl2, 0, 0, 210, 297);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(0, 0, 0);

    drawRating($pdf, $cols, 54.0, (int) ($eval['overall_rating'] ?? 0));

    if (!empty($eval['was_paid'])) {
        drawCheck($pdf, 156.5, 61.5);
        $payTypeX = ['Hourly' => 94.5, 'Daily' => 116.0, 'Stipend/Allowance' => 133.0];
        if (isset($payTypeX[$eval['pay_type']]))
            drawCheck($pdf, $payTypeX[$eval['pay_type']], 67.0);
        if (!empty($eval['pay_amount'])) {
            $pdf->SetXY(120, 72.5);
            $pdf->Cell(40, 4, number_format((float) $eval['pay_amount'], 2), 0, 0, 'L');
        }
    } else {
        drawCheck($pdf, 174.5, 61.5);
    }

    $assessY = [69.2, 73.0, 76.8, 80.6];
    $assessKeys = ['recommend_internship', 'work_supervisor_again', 'work_coordinator_again', 'recommend_hte'];
    foreach ($assessKeys as $i => $key)
        drawRating($pdf, $cols, $assessY[$i], (int) ($eval[$key] ?? 0));

    $pdf->SetFont('Helvetica', '', 8.5);
    $pdf->SetXY(15, 103);
    $pdf->MultiCell(180, 4.5, $eval['most_valuable'] ?? '', 0, 'L');
    $pdf->SetXY(15, 133);
    $pdf->MultiCell(180, 4.5, $eval['least_valuable'] ?? '', 0, 'L');
    $pdf->SetXY(15, 168);
    $pdf->MultiCell(180, 4.5, $eval['concerns'] ?? '', 0, 'L');
    $pdf->SetXY(15, 203);
    $pdf->MultiCell(180, 4.5, $eval['suggestions'] ?? '', 0, 'L');

    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetXY(65, 239);
    $pdf->Cell(80, 4, $meta['student_name'], 0, 0, 'L');
    $pdf->SetXY(145, 239);
    $pdf->Cell(50, 4, $meta['submitted_at'], 0, 0, 'L');
}
function drawScale11(Fpdi $pdf, array $cols11, float $rowY, int $value): void
{
    if ($value < 0 || $value > 10)
        return;
    $cx = $cols11[$value];
    $pdf->SetLineWidth(0.5);
    $pdf->SetDrawColor(6, 95, 70); // #065f46
    $pdf->Ellipse($cx, $rowY, 3.0, 2.3, 0, 0, 360, 'D');
    $pdf->SetLineWidth(0.2);
}

const RATING_COLS_SUPERVISOR = [158.76, 166.10, 173.39, 180.72];

function renderSupervisorEvaluationPdf(Fpdi $pdf, string $templatePdf, array $eval, array $meta): void
{
    $pdf->setSourceFile($templatePdf);
    $cols = RATING_COLS_SUPERVISOR;

    // ── Page 1 ──────────────────────────────────────────────────────
    $pdf->AddPage();
    $tpl1 = $pdf->importPage(1);
    $pdf->useTemplate($tpl1, 0, 0, 210, 297);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(0, 0, 0);

    // Header — measured directly from "Name", "Intern:", "No.:", "Company:"
    // text positions on the real template. Row spacing here is ~3.8mm,
    // much tighter than the OJTF-011 form, which is why the old guessed
    // coordinates (borrowed from that form) overlapped everything.
    $pdf->SetXY(50, 39.42);
    $pdf->Cell(65, 4, $meta['intern_name'], 0, 0, 'L');
    $pdf->SetXY(152, 39.42);
    $pdf->Cell(50, 4, $meta['student_no'], 0, 0, 'L');
    $pdf->SetXY(55, 43.23);
    $pdf->Cell(150, 4, $meta['company_name'], 0, 0, 'L');
    $pdf->SetXY(80, 47.00);
    $pdf->Cell(120, 4, $meta['supervisor_name'], 0, 0, 'L');

    // Measured row centers for every rating item, in template order.
    $rowY = [
        92.87,
        96.68,
        100.49,     // A. Learning Skills
        112.14,
        115.95,
        119.76,  // B. Reading/Writing/Computational
        131.41,
        135.18,
        138.99,  // C. Listening/Verbal
        150.64,
        154.45,
        158.26,  // D. Creative/Problem-Solving
        169.91,
        173.72,
        177.53,  // E. Professional/Career Dev
        189.18,
        192.99,
        196.80,  // F. Interpersonal/Teamwork
        208.46,
        212.23,
        216.04,  // G. Organizational Effectiveness
        227.69,
        231.50,
        235.30,  // H. Work Habits
        246.96,
        250.76,
        254.57,  // I. Character
        266.22,
        270.04,          // J. Industry-Specific (1, 2 — item 3 is on page 2)
    ];
    $rowKeys = [
        'ls_questions',
        'ls_resources',
        'ls_accountability',
        'rw_written',
        'rw_express',
        'rw_math',
        'lv_listens',
        'lv_meetings',
        'lv_verbal',
        'ps_divides',
        'ps_brainstorm',
        'ps_solve',
        'pd_proactive',
        'pd_priorities',
        'pd_demeanor',
        'it_conflicts',
        'it_team',
        'it_assertive',
        'oe_endorse',
        'oe_adapts',
        'oe_channels',
        'wh_punctual',
        'wh_attitude',
        'wh_dress',
        'ca_ethics',
        'ca_principled',
        'ca_diversity',
        'is_proficiency',
        'is_willingness',
    ];
    foreach ($rowKeys as $i => $key) {
        drawRating($pdf, $cols, $rowY[$i], (int) ($eval[$key] ?? 0));
    }

    // ── Page 2 ──────────────────────────────────────────────────────
    $pdf->AddPage();
    $tpl2 = $pdf->importPage(2);
    $pdf->useTemplate($tpl2, 0, 0, 210, 297);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(0, 0, 0);

    // J.3 — carries over from page 1's rating columns/row style
    drawRating($pdf, $cols, 41.15, (int) ($eval['is_additional'] ?? 0));

    // K. Comments — measured gaps between K1/K2/K3 labels are only
    // ~15-20mm, much tighter than assumed before, so answers sit
    // right under each question rather than 25mm down.
    $pdf->SetFont('Helvetica', '', 8.5);
    $pdf->SetXY(15, 63.0);
    $pdf->MultiCell(180, 4, $eval['impact'] ?? '', 0, 'L');
    $pdf->SetXY(15, 79.0);
    $pdf->MultiCell(180, 4, $eval['strengths'] ?? '', 0, 'L');
    $pdf->SetXY(15, 98.5);
    $pdf->MultiCell(180, 4, $eval['improvements'] ?? '', 0, 'L');

    // L. Overall Performance — 0–10 scale, measured column x-positions
    $cols11 = [31.30, 45.92, 60.54, 75.13, 89.74, 104.32, 118.95, 133.56, 148.14, 162.76, 176.48];
    drawScale11($pdf, $cols11, 118.77, (int) ($eval['overall_intern'] ?? -1));

    // Part II
    $pdf->SetXY(15, 138.0);
    $pdf->MultiCell(180, 4, $eval['suggestions'] ?? '', 0, 'L');

    // B. Future supervision — template has no printed Yes/No checkbox
    // here (unlike the OJTF-011 pay question), so the answer is written
    // inline as text rather than checked against a fixed box.
    $futureAnswer = '';
    if (!empty($eval['supervise_future_yes'])) {
        $futureAnswer = 'Yes. ';
    } elseif (!empty($eval['supervise_future_no'])) {
        $futureAnswer = 'No. ';
    }
    $pdf->SetXY(15, 162.0);
    $pdf->MultiCell(180, 4, $futureAnswer . ($eval['supervise_future_reason'] ?? ''), 0, 'L');

    // C. Overall Experience — same 0–10 scale, different row
    drawScale11($pdf, $cols11, 189.71, (int) ($eval['overall_experience'] ?? -1));

    // Signature strip — measured end-of-label x positions (name: / Date /
    // Title/Position: / Contact Details:) plus a small gap.
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetXY(66, 211.41);
    $pdf->Cell(60, 4, $meta['supervisor_name'], 0, 0, 'L');
    $pdf->SetXY(133, 211.41);
    $pdf->Cell(40, 4, $meta['eval_date'], 0, 0, 'L');
    $pdf->SetXY(68, 215.22);
    $pdf->Cell(75, 4, $meta['title_position'], 0, 0, 'L');
    $pdf->SetXY(147, 215.22);
    $pdf->Cell(40, 4, $meta['contact_details'], 0, 0, 'L');
}

/** Category → its 3 (or 3, with an optional 4th for J) rating keys, in order. */
const SUPERVISOR_CATEGORIES = [
    'A' => ['ls_questions', 'ls_resources', 'ls_accountability'],
    'B' => ['rw_written', 'rw_express', 'rw_math'],
    'C' => ['lv_listens', 'lv_meetings', 'lv_verbal'],
    'D' => ['ps_divides', 'ps_brainstorm', 'ps_solve'],
    'E' => ['pd_proactive', 'pd_priorities', 'pd_demeanor'],
    'F' => ['it_conflicts', 'it_team', 'it_assertive'],
    'G' => ['oe_endorse', 'oe_adapts', 'oe_channels'],
    'H' => ['wh_punctual', 'wh_attitude', 'wh_dress'],
    'I' => ['ca_ethics', 'ca_principled', 'ca_diversity'],
    'J' => ['is_proficiency', 'is_willingness', 'is_additional'],
];

/** Average of whatever 1-4 ratings were actually answered in this category (blank/0 = skipped, not counted as 0). */
function categoryAverage(array $eval, array $keys): ?float
{
    $vals = [];
    foreach ($keys as $k) {
        $v = (int) ($eval[$k] ?? 0);
        if ($v >= 1 && $v <= 4)
            $vals[] = $v;
    }
    return $vals ? array_sum($vals) / count($vals) : null;
}

function drawSupervisorSummaryPage(Fpdi $pdf, array $eval, array $meta): void
{
    $pdf->AddPage(); // blank page — no template background needed for a chart page

    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->SetTextColor(6, 95, 70); // #065f46
    $pdf->SetXY(15, 15);
    $pdf->Cell(180, 8, 'Evaluation Summary', 0, 0, 'L');

    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->SetXY(15, 24);
    $pdf->Cell(180, 5, $meta['intern_name'] . ' — ' . $meta['company_name'], 0, 0, 'L');

    // ── Bar chart: average rating per category, scale 1–4 ──────────────
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(15, 34);
    $pdf->Cell(180, 6, 'Average Rating per Category (scale 1-4)', 0, 0, 'L');

    $chartX = 25;
    $chartRight = 190;
    $chartTop = 46;
    $chartBottom = 140;
    $chartWidth = $chartRight - $chartX;
    $chartHeight = $chartBottom - $chartTop;

    // Y-axis + gridlines at 1, 2, 3, 4
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->SetLineWidth(0.2);
    $pdf->SetFont('Helvetica', '', 8);
    $pdf->SetTextColor(120, 120, 120);
    for ($v = 0; $v <= 4; $v++) {
        $gy = $chartBottom - ($v / 4) * $chartHeight;
        $pdf->Line($chartX, $gy, $chartRight, $gy);
        $pdf->SetXY($chartX - 10, $gy - 2);
        $pdf->Cell(8, 4, (string) $v, 0, 0, 'R');
    }

    $categories = SUPERVISOR_CATEGORIES;
    $n = count($categories);
    $slot = $chartWidth / $n;
    $barWidth = $slot * 0.5;

    $allAverages = [];
    $i = 0;
    foreach ($categories as $letter => $keys) {
        $avg = categoryAverage($eval, $keys);
        $barX = $chartX + $i * $slot + ($slot - $barWidth) / 2;

        if ($avg !== null) {
            $allAverages[] = $avg;
            $barHeight = ($avg / 4) * $chartHeight;
            $barY = $chartBottom - $barHeight;
            $pdf->SetFillColor(6, 95, 70); // #065f46
            $pdf->Rect($barX, $barY, $barWidth, $barHeight, 'F');

            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->SetTextColor(6, 95, 70);
            $pdf->SetXY($barX - 3, $barY - 5);
            $pdf->Cell($barWidth + 6, 4, number_format($avg, 1), 0, 0, 'C');
        } else {
            $pdf->SetFont('Helvetica', '', 7);
            $pdf->SetTextColor(180, 180, 180);
            $pdf->SetXY($barX - 3, $chartBottom - 8);
            $pdf->Cell($barWidth + 6, 4, 'N/A', 0, 0, 'C');
        }

        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($chartX + $i * $slot, $chartBottom + 3);
        $pdf->Cell($slot, 5, $letter, 0, 0, 'C');

        $i++;
    }

    // Axis line
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth(0.3);
    $pdf->Line($chartX, $chartBottom, $chartRight, $chartBottom);

    // Legend (category letter -> full name), two columns
    $pdf->SetFont('Helvetica', '', 8);
    $pdf->SetTextColor(60, 60, 60);
    $legend = [
        'A' => 'Learning Skills',
        'B' => 'Reading/Writing/Computational',
        'C' => 'Listening/Verbal',
        'D' => 'Creative/Problem-Solving',
        'E' => 'Professional/Career Dev',
        'F' => 'Interpersonal/Teamwork',
        'G' => 'Organizational Effectiveness',
        'H' => 'Work Habits',
        'I' => 'Character Attributes',
        'J' => 'Industry-Specific Skills',
    ];
    $ly = 150;
    $col = 0;
    foreach ($legend as $letter => $name) {
        $lx = 15 + $col * 90;
        $pdf->SetXY($lx, $ly);
        $pdf->Cell(90, 4, "{$letter} — {$name}", 0, 0, 'L');
        $col++;
        if ($col === 2) {
            $col = 0;
            $ly += 4.5;
        }
    }

    // Grand average
    $grandAvg = $allAverages ? array_sum($allAverages) / count($allAverages) : null;
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetTextColor(6, 95, 70);
    $pdf->SetXY(15, 175);
    $pdf->Cell(180, 6, 'Overall Category Average: ' . ($grandAvg !== null ? number_format($grandAvg, 2) . ' / 4.00' : 'N/A'), 0, 0, 'L');

    // ── Overall Performance & Overall Experience gauges (0–10) ─────────
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(15, 190);
    $pdf->Cell(180, 6, 'Overall Scores (scale 0-10)', 0, 0, 'L');

    $gaugeX = 25;
    $gaugeWidth = 165;
    $gauges = [
        ['label' => 'Overall Performance (Intern, present time)', 'value' => (int) ($eval['overall_intern'] ?? -1), 'y' => 202],
        ['label' => 'Overall Internship Experience', 'value' => (int) ($eval['overall_experience'] ?? -1), 'y' => 218],
    ];
    foreach ($gauges as $g) {
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(60, 60, 60);
        $pdf->SetXY(15, $g['y'] - 5);
        $pdf->Cell(180, 4, $g['label'], 0, 0, 'L');

        // Track
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Rect($gaugeX, $g['y'], $gaugeWidth, 6, 'F');

        // Fill
        if ($g['value'] >= 0 && $g['value'] <= 10) {
            $fillWidth = ($g['value'] / 10) * $gaugeWidth;
            $pdf->SetFillColor(6, 95, 70);
            $pdf->Rect($gaugeX, $g['y'], $fillWidth, 6, 'F');

            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->SetTextColor(255, 255, 255);
            if ($fillWidth > 10) {
                $pdf->SetXY($gaugeX + $fillWidth - 10, $g['y'] + 1);
                $pdf->Cell(9, 4, (string) $g['value'], 0, 0, 'R');
            } else {
                $pdf->SetTextColor(6, 95, 70);
                $pdf->SetXY($gaugeX + $fillWidth + 2, $g['y'] + 1);
                $pdf->Cell(9, 4, (string) $g['value'], 0, 0, 'L');
            }
        } else {
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->SetTextColor(150, 150, 150);
            $pdf->SetXY($gaugeX + $gaugeWidth / 2 - 10, $g['y'] + 1);
            $pdf->Cell(20, 4, 'N/A', 0, 0, 'C');
        }
    }

    $pdf->SetTextColor(0, 0, 0);
}