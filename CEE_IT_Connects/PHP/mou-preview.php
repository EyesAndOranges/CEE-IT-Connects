<?php
require 'db.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/../../vendor/autoload.php';


use setasign\Fpdi\Fpdi;

$student_id = $_SESSION['user_id'];
$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if (!$id) {
    die("Invalid request.");
}

if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}

// Get internship data
$stmt = $pdo->prepare("
    SELECT 
        i.*, 
        s.full_name AS student_name,
        s.year_level AS student_year,
        s.program AS student_program,
        a.name AS admin_name,
        a.title AS admin_title,
        adv.full_name AS adviser_name,
        adv.title AS adviser_title
    FROM internships i
    LEFT JOIN admins a ON i.admin_id = a.id
    LEFT JOIN advisers adv ON i.adviser_id = adv.id
    JOIN students s ON s.id = ?
    WHERE i.id = ?
");
$stmt->execute([$student_id, $id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("Internship not found.");
}

$title = !empty($data['title'])
    ? $data['title']
    : $data['admin_title'];
switch ($action) {
    case 'mou':
        $pdf = new FPDI();
        $file = __DIR__ . "/../Sources/MOU_template.pdf";
        $pageCount = $pdf->setSourceFile($file);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {

            $templateId = $pdf->importPage($pageNo);
            $pdf->AddPage();
            $pdf->useTemplate($templateId);

            if ($pageNo == 1) {

                $pdf->SetFont('Arial', '', 11);

                $pdf->SetXY(40, 60);
                $pdf->Write(0, $data['company']);

                $pdf->SetXY(40, 70);
                $pdf->Write(0, $data['student_name']);

                $pdf->SetXY(87, 209);
                $pdf->Write(0, $data['duration']);
            } elseif ($pageNo == 2) {
                //$pdf->SetXY(40, 80);
                //$pdf->Write(0, $data['location']);
                $pdf->SetXY(40, 67);
                $pdf->Write(0, date("F d, Y"));

                $pdf->SetXY(40, 83);
                $pdf->Write(0, $data['title']);

                $pdf->SetXY(40, 100);
                $pdf->Write(0, date("F d, Y"));

                // Representative (Admin or Adviser)
                $pdf->SetXY(40, 50);
                $pdf->Write(0, $data['admin_name'] ?? $data['adviser_name']);
            }
        }
        $pdf->Output("I", "MOU_preview.pdf");
        exit;

        break;
    case 'rl':
        $pdf = new FPDI();
        $file = __DIR__ . "/../Sources/recom_letter_template.pdf";
        $pageCount = $pdf->setSourceFile($file);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {

            $templateId = $pdf->importPage($pageNo);
            $pdf->AddPage();
            $pdf->useTemplate($templateId);

            if ($pageNo == 1) {

                $pdf->SetFont('Arial', '', 11);

                $pdf->SetXY(40, 37);
                $pdf->Write(0, date("F d, Y"));

                $pdf->SetXY(73, 58);
                $pdf->Write(0, $data['student_name']);

                $pdf->SetXY(135, 58);
                $pdf->Write(0, $data['student_year'] . " Year");

                $pdf->SetXY(78, 64);
                $pdf->Write(0, $data['student_program']);

                $pdf->SetXY(130, 64);
                $pdf->Write(0, 'PLV');

                $pdf->SetXY(82, 76);
                $pdf->Write(0, $data['student_name']);

                //$pdf->SetXY(40, 80);
                //$pdf->Write(0, $data['location']);
                $pdf->SetXY(60, 93);
                $pdf->Write(0, $data['student_name']);

                $pdf->SetXY(40, 117);
                $pdf->Write(0, $data['admin_name'] ?? $data['adviser_name']);

                $pdf->SetXY(40, 127);
                $pdf->Write(0, $data['admin_title'] ?? $data['adviser_title']);

                $pdf->SetXY(40, 134);
                $pdf->Write(0, 'PLV');

                // Representative (Admin or Adviser)


            }
        }
        $pdf->Output("I", "MOU_preview.pdf");
        exit;

        break;
    case 'waiver':
        $pdf = new FPDI();
        $file = __DIR__ . "/../Sources/waiver_template.pdf";
        $pageCount = $pdf->setSourceFile($file);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {

            $templateId = $pdf->importPage($pageNo);
            $pdf->AddPage();
            $pdf->useTemplate($templateId);

            if ($pageNo == 1) {

                $pdf->SetFont('Arial', '', 11);

                $pdf->SetXY(37, 38);
                $pdf->Write(0, $data['student_name']);

                $pdf->SetXY(120, 38);
                $pdf->Write(0, 'PLV');

                $pdf->SetXY(90, 63);
                $pdf->Write(0, 'PLV');

                $pdf->SetXY(95, 42);
                $pdf->Write(0, $data['company']);

                $pdf->SetXY(40, 67);
                $pdf->Write(0, $data['company']);
                //$pdf->SetXY(40, 80);
                //$pdf->Write(0, $data['location']);

                $pdf->SetXY(52, 89);
                $pdf->Write(0, $data['student_name']);

                $pdf->SetXY(60, 97);
                $pdf->Write(0, date("F d, Y"));

                $pdf->SetXY(60, 115);
                $pdf->Write(0, date("F d, Y"));


                // Representative (Admin or Adviser)
                $pdf->SetXY(40, 50);
                $pdf->Write(0, $data['admin_name'] ?? $data['adviser_name']);


            }
        }
        $pdf->Output("I", "MOU_preview.pdf");
        exit;

        break;
}
//
