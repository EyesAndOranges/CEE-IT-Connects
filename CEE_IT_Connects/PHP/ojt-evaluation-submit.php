<?php
session_start();
require 'db.php';
require 'auth.php';

header('Content-Type: application/json');

// Fixed: parentheses added so the comparison works correctly
$action = $_POST['action'] ?? '';

if (
    !in_array($action, [
        'submit_evaluation',
        'submit_supervisor_evaluation'
    ])
) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action'
    ]);
    exit;
}

if ($action === 'submit_supervisor_evaluation') {

    try {

        $studentId = (int) ($_POST['student_id'] ?? 0);

        if (!$studentId) {
            echo json_encode([
                'success' => false,
                'message' => 'Missing student'
            ]);
            exit;
        }

        try {

            // Get adviser ID from session
            $adviserId = $_SESSION['user_id'];

            // Get internship id
            $stmt = $pdo->prepare("
            SELECT internship_id
            FROM internship_bookmarks
            WHERE student_id=?
            LIMIT 1
        ");
            $stmt->execute([$studentId]);

            $internshipId = $stmt->fetchColumn();

            $ratings = [

                // Learning Skills
                'learn_questions' => (int) ($_POST['ls_questions'] ?? 0),
                'learn_resources' => (int) ($_POST['ls_resources'] ?? 0),
                'learn_accountability' => (int) ($_POST['ls_accountability'] ?? 0),

                // Reading/Writing
                'rw_written' => (int) ($_POST['rw_written'] ?? 0),
                'rw_communication' => (int) ($_POST['rw_express'] ?? 0),
                'rw_math' => (int) ($_POST['rw_math'] ?? 0),

                // Verbal
                'verbal_listens' => (int) ($_POST['lv_listens'] ?? 0),
                'verbal_meetings' => (int) ($_POST['lv_meetings'] ?? 0),
                'verbal_proficiency' => (int) ($_POST['lv_verbal'] ?? 0),

                // Creative
                'creative_divides' => (int) ($_POST['ps_divides'] ?? 0),
                'creative_brainstorm' => (int) ($_POST['ps_brainstorm'] ?? 0),
                'creative_solves' => (int) ($_POST['ps_solve'] ?? 0),

                // Career
                'career_proactive' => (int) ($_POST['pd_proactive'] ?? 0),
                'career_priorities' => (int) ($_POST['pd_priorities'] ?? 0),
                'career_demeanor' => (int) ($_POST['pd_demeanor'] ?? 0),

                // Teamwork
                'team_conflicts' => (int) ($_POST['it_conflicts'] ?? 0),
                'team_collaborative' => (int) ($_POST['it_team'] ?? 0),
                'team_assertiveness' => (int) ($_POST['it_assertive'] ?? 0),

                // Organization
                'org_objectives' => (int) ($_POST['oe_endorse'] ?? 0),
                'org_standards' => (int) ($_POST['oe_adapts'] ?? 0),
                'org_channels' => (int) ($_POST['oe_channels'] ?? 0),

                // Work habits
                'work_punctual' => (int) ($_POST['wh_punctual'] ?? 0),
                'work_attitude' => (int) ($_POST['wh_attitude'] ?? 0),
                'work_dresscode' => (int) ($_POST['wh_dress'] ?? 0),

                // Character
                'char_ethics' => (int) ($_POST['ca_ethics'] ?? 0),
                'char_principled' => (int) ($_POST['ca_principled'] ?? 0),
                'char_diversity' => (int) ($_POST['ca_diversity'] ?? 0),

                // Industry
                'industry_proficiency' => (int) ($_POST['is_proficiency'] ?? 0),
                'industry_willingness' => (int) ($_POST['is_willingness'] ?? 0),
                'industry_additional' => (int) ($_POST['is_additional'] ?? 0),
            ];

            $sql = "

        INSERT INTO ojt_evaluations_supervisor (

            student_id,
            adviser_id,
            internship_id,

            learn_questions,
            learn_resources,
            learn_accountability,

            rw_written,
            rw_communication,
            rw_math,

            verbal_listens,
            verbal_meetings,
            verbal_proficiency,

            creative_divides,
            creative_brainstorm,
            creative_solves,

            career_proactive,
            career_priorities,
            career_demeanor,

            team_conflicts,
            team_collaborative,
            team_assertiveness,

            org_objectives,
            org_standards,
            org_channels,

            work_punctual,
            work_attitude,
            work_dresscode,

            char_ethics,
            char_principled,
            char_diversity,

            industry_proficiency,
            industry_willingness,
            industry_additional,

            comment_impact,
            comment_strengths,
            comment_improvements,

            overall_intern_rating,

            suggestions,

            would_supervise_again,
            would_supervise_reason,

            overall_internship_rating,

            submitted_at

        )

        VALUES(

            :student_id,
            :adviser_id,
            :internship_id,

            :learn_questions,
            :learn_resources,
            :learn_accountability,

            :rw_written,
            :rw_communication,
            :rw_math,

            :verbal_listens,
            :verbal_meetings,
            :verbal_proficiency,

            :creative_divides,
            :creative_brainstorm,
            :creative_solves,

            :career_proactive,
            :career_priorities,
            :career_demeanor,

            :team_conflicts,
            :team_collaborative,
            :team_assertiveness,

            :org_objectives,
            :org_standards,
            :org_channels,

            :work_punctual,
            :work_attitude,
            :work_dresscode,

            :char_ethics,
            :char_principled,
            :char_diversity,

            :industry_proficiency,
            :industry_willingness,
            :industry_additional,

            :comment_impact,
            :comment_strengths,
            :comment_improvements,

            :overall_intern_rating,

            :suggestions,

            :would_supervise_again,
            :would_supervise_reason,

            :overall_internship_rating,

            NOW()

        )

        ON CONFLICT(student_id)
        DO UPDATE SET

            submitted_at=NOW()

        ";

            $superviseValue = $_POST['supervise_future'] ?? '';
            $superviseBool = ($superviseValue === 'yes' || $superviseValue === 'no')
                ? ($superviseValue === 'yes')
                : null;

            $params = array_merge(
                $ratings,
                [
                    'student_id' => $studentId,
                    'adviser_id' => $adviserId,
                    'internship_id' => $internshipId,
                    'comment_impact' => $_POST['impact'] ?? '',
                    'comment_strengths' => $_POST['strengths'] ?? '',
                    'comment_improvements' => $_POST['improvements'] ?? '',
                    'overall_intern_rating' => (int) ($_POST['overall_intern'] ?? 0),
                    'suggestions' => $_POST['suggestions'] ?? '',
                    'would_supervise_again' => null, // placeholder, overridden below
                    'would_supervise_reason' => $_POST['supervise_future_reason'] ?? '',
                    'overall_internship_rating' => (int) ($_POST['overall_experience'] ?? 0),
                ]
            );

            $stmt = $pdo->prepare($sql);

            // Bind all params
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }

            // Override would_supervise_again with explicit bool type
            $stmt->bindValue(':would_supervise_again', $superviseBool, PDO::PARAM_BOOL);

            $stmt->execute();



            $pdo->prepare($sql)->execute($params);

            echo json_encode([
                'success' => true
            ]);

        } catch (Exception $e) {

            error_log($e->getMessage());

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

    } catch (Exception $e) {

        error_log(
            'Supervisor error: ' .
            $e->getMessage()
        );

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);

        exit;
    }
}
if ($action === 'submit_evaluation') {
    $studentId = (int) $_SESSION['user_id'];

    // Integer rating fields (must be 1–4)
    $intFields = [
        'site_secure',
        'site_orientation',
        'site_resources',
        'site_colleagues',
        'sup_job_desc',
        'sup_feedback',
        'sup_learning',
        'sup_duties',
        'sup_schedule',
        'learn_aligned',
        'learn_verbal',
        'learn_interpersonal',
        'learn_creativity',
        'learn_problem',
        'learn_critical',
        'learn_writing',
        'learn_career',
        'hei_prepared',
        'hei_guidance',
        'hei_supported',
        'hei_communication',
        'hei_coursework',
        'hei_goals',
        'hei_valuable',
        'hei_satisfied',
        'coord_instructions',
        'coord_goals',
        'coord_responsive',
        'coord_feedback',
        'coord_challenges',
        'overall_rating',
        'recommend_internship',
        'work_supervisor_again',
        'work_coordinator_again',
        'recommend_hte',
    ];

    $vals = [];
    foreach ($intFields as $f) {
        $v = isset($_POST[$f]) ? (int) $_POST[$f] : null;
        $vals[$f] = ($v >= 1 && $v <= 4) ? $v : null;
    }

    $wasPaid = ($_POST['was_paid'] ?? '') === 'yes';
    $payType = in_array($_POST['pay_type'] ?? '', ['Hourly', 'Daily', 'Stipend/Allowance'])
        ? $_POST['pay_type'] : null;
    $payAmount = ($wasPaid && is_numeric($_POST['pay_amount'] ?? ''))
        ? (float) $_POST['pay_amount'] : null;

    $mostValuable = trim($_POST['most_valuable'] ?? '');
    $leastValuable = trim($_POST['least_valuable'] ?? '');
    $concerns = trim($_POST['concerns'] ?? '');
    $suggestions = trim($_POST['suggestions'] ?? '');

    // company_name and supervisor_name are sourced from the DB in the download script
// (internships.company and advisers.full_name where role = 'HTE_adviser')

    // Get the student's active internship id (if any)
    $stmt = $pdo->prepare("
    SELECT ib.internship_id FROM internship_bookmarks ib
    JOIN student_progress sp ON sp.student_id = ib.student_id
    WHERE ib.student_id = ? AND sp.is_done = TRUE
    LIMIT 1
");
    $stmt->execute([$studentId]);
    $internshipId = $stmt->fetchColumn() ?: null;

    try {
        $sql = "
        INSERT INTO ojt_evaluations_student (
            student_id, internship_id,
            site_secure, site_orientation, site_resources, site_colleagues,
            sup_job_desc, sup_feedback, sup_learning, sup_duties, sup_schedule,
            learn_aligned, learn_verbal, learn_interpersonal, learn_creativity,
            learn_problem, learn_critical, learn_writing, learn_career,
            hei_prepared, hei_guidance, hei_supported, hei_communication,
            hei_coursework, hei_goals, hei_valuable, hei_satisfied,
            coord_instructions, coord_goals, coord_responsive, coord_feedback, coord_challenges,
            overall_rating, was_paid, pay_type, pay_amount,
            recommend_internship, work_supervisor_again, work_coordinator_again, recommend_hte,
            most_valuable, least_valuable, concerns, suggestions,
            submitted_at
        ) VALUES (
            :student_id, :internship_id,
            :site_secure, :site_orientation, :site_resources, :site_colleagues,
            :sup_job_desc, :sup_feedback, :sup_learning, :sup_duties, :sup_schedule,
            :learn_aligned, :learn_verbal, :learn_interpersonal, :learn_creativity,
            :learn_problem, :learn_critical, :learn_writing, :learn_career,
            :hei_prepared, :hei_guidance, :hei_supported, :hei_communication,
            :hei_coursework, :hei_goals, :hei_valuable, :hei_satisfied,
            :coord_instructions, :coord_goals, :coord_responsive, :coord_feedback, :coord_challenges,
            :overall_rating, :was_paid, :pay_type, :pay_amount,
            :recommend_internship, :work_supervisor_again, :work_coordinator_again, :recommend_hte,
            :most_valuable, :least_valuable, :concerns, :suggestions,
            NOW()
        )
        ON CONFLICT (student_id) DO UPDATE SET
            internship_id          = EXCLUDED.internship_id,
            site_secure            = EXCLUDED.site_secure,
            site_orientation       = EXCLUDED.site_orientation,
            site_resources         = EXCLUDED.site_resources,
            site_colleagues        = EXCLUDED.site_colleagues,
            sup_job_desc           = EXCLUDED.sup_job_desc,
            sup_feedback           = EXCLUDED.sup_feedback,
            sup_learning           = EXCLUDED.sup_learning,
            sup_duties             = EXCLUDED.sup_duties,
            sup_schedule           = EXCLUDED.sup_schedule,
            learn_aligned          = EXCLUDED.learn_aligned,
            learn_verbal           = EXCLUDED.learn_verbal,
            learn_interpersonal    = EXCLUDED.learn_interpersonal,
            learn_creativity       = EXCLUDED.learn_creativity,
            learn_problem          = EXCLUDED.learn_problem,
            learn_critical         = EXCLUDED.learn_critical,
            learn_writing          = EXCLUDED.learn_writing,
            learn_career           = EXCLUDED.learn_career,
            hei_prepared           = EXCLUDED.hei_prepared,
            hei_guidance           = EXCLUDED.hei_guidance,
            hei_supported          = EXCLUDED.hei_supported,
            hei_communication      = EXCLUDED.hei_communication,
            hei_coursework         = EXCLUDED.hei_coursework,
            hei_goals              = EXCLUDED.hei_goals,
            hei_valuable           = EXCLUDED.hei_valuable,
            hei_satisfied          = EXCLUDED.hei_satisfied,
            coord_instructions     = EXCLUDED.coord_instructions,
            coord_goals            = EXCLUDED.coord_goals,
            coord_responsive       = EXCLUDED.coord_responsive,
            coord_feedback         = EXCLUDED.coord_feedback,
            coord_challenges       = EXCLUDED.coord_challenges,
            overall_rating         = EXCLUDED.overall_rating,
            was_paid               = EXCLUDED.was_paid,
            pay_type               = EXCLUDED.pay_type,
            pay_amount             = EXCLUDED.pay_amount,
            recommend_internship   = EXCLUDED.recommend_internship,
            work_supervisor_again  = EXCLUDED.work_supervisor_again,
            work_coordinator_again = EXCLUDED.work_coordinator_again,
            recommend_hte          = EXCLUDED.recommend_hte,
            most_valuable          = EXCLUDED.most_valuable,
            least_valuable         = EXCLUDED.least_valuable,
            concerns               = EXCLUDED.concerns,
            suggestions            = EXCLUDED.suggestions,
            submitted_at           = NOW()
    ";

        $params = array_merge(
            ['student_id' => $studentId, 'internship_id' => $internshipId],
            $vals,
            [
                'was_paid' => $wasPaid ? 'true' : 'false',
                'pay_type' => $payType,
                'pay_amount' => $payAmount,
                'most_valuable' => $mostValuable,
                'least_valuable' => $leastValuable,
                'concerns' => $concerns,
                'suggestions' => $suggestions,
            ]
        );

        $pdo->prepare($sql)->execute($params);

        // ── Notify adviser & HTE ──────────────────────────────────────────────
        $studentName = $_SESSION['full_name'] ?? 'A student';
        $notifTitle = 'OJT Hours Completed';
        $notifMsg = "$studentName has completed their OJT hours and submitted the Student Evaluation (CEIT-OJTF-011).";
        $notifLink = 'student-records.php?student_id=' . $studentId;

        $insertNotif = $pdo->prepare("
        INSERT INTO notifications (user_id, user_type, title, message, is_read, link, created_at)
        VALUES (?, ?, ?, ?, FALSE, ?, NOW())
    ");

        // OJT coordinator/adviser
        $adviserStmt = $pdo->prepare("
        SELECT a.id FROM advisers a
        JOIN rooms r ON r.adviser_id = a.id
        JOIN room_members rm ON rm.room_id = r.id
        WHERE rm.user_id = ? AND rm.user_type = 'student' AND r.is_archived = FALSE
        LIMIT 1
    ");
        $adviserStmt->execute([$studentId]);
        $adviserId = $adviserStmt->fetchColumn();
        if ($adviserId) {
            $insertNotif->execute([$adviserId, 'adviser', $notifTitle, $notifMsg, $notifLink]);
        }

        // HTE advisers in the same room
        $hteStmt = $pdo->prepare("
        SELECT DISTINCT rm2.user_id FROM room_members rm2
        JOIN room_members rm_s ON rm_s.room_id = rm2.room_id
        WHERE rm_s.user_id = ? AND rm_s.user_type = 'student'
          AND rm2.user_type = 'hte_adviser'
    ");
        $hteStmt->execute([$studentId]);
        foreach ($hteStmt->fetchAll(PDO::FETCH_COLUMN) as $hteId) {
            $insertNotif->execute([$hteId, 'hte_adviser', $notifTitle, $notifMsg, $notifLink]);
        }

        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        error_log('OJT eval submit error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}



    // ── PHPMailer setup ──────────────────────────────────────────────────────────
    // Adjust the path below to match where PHPMailer lives in your project.
    // Common locations:  vendor/autoload.php  OR  PHPMailer/PHPMailer.php
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    require 'vendor/autoload.php';
    
    // ── SMTP credentials — edit these ────────────────────────────────────────────
    // Use a Gmail App Password (not your regular password).
    // Generate one at: https://myaccount.google.com/apppasswords
    define('SMTP_HOST',     'smtp.gmail.com');
    define('SMTP_USERNAME', 'your-system-email@gmail.com');  // ← change
    define('SMTP_PASSWORD', 'your-app-password-here');       // ← change
    define('SMTP_PORT',     587);
    define('SITE_URL',      'https://yoursite.com');          // ← change, no trailing slash
    
    // ────────────────────────────────────────────────────────────────────────────
    $action = $_POST['action'] ?? '';
    
    // ============================================================
    // ACTION 1: Assign supervisor (HTE adviser → student)
    // ============================================================
    if ($action === 'assign_supervisor') {
    
        // Only logged-in HTE advisers may assign
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'HTE_adviser') {
            http_response_code(403);
            exit('Unauthorized');
        }
    
        $studentId       = (int) ($_POST['student_id']       ?? 0);
        $supervisorName  = trim($_POST['supervisor_name']     ?? '');
        $supervisorEmail = trim($_POST['supervisor_email']    ?? '');
    
        // Basic validation
        if (!$studentId || !$supervisorEmail) {
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Student and supervisor email are required.'];
            header('Location: hte-ui.php');
            exit;
        }
    
        // Check: does a pending (not yet submitted) assignment already exist?
        // If it does, we re-send the email instead of creating a duplicate row.
        $checkStmt = $pdo->prepare("
            SELECT id, access_code
            FROM supervisor_evaluations
            WHERE student_id = ? AND submitted_at IS NULL
            LIMIT 1
        ");
        $checkStmt->execute([$studentId]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
        if ($existing) {
            // Re-use existing access code, just update supervisor info and re-send email
            $accessCode = $existing['access_code'];
            $upd = $pdo->prepare("
                UPDATE supervisor_evaluations
                SET supervisor_name  = ?,
                    supervisor_email = ?
                WHERE id = ?
            ");
            $upd->execute([$supervisorName, $supervisorEmail, $existing['id']]);
    
        } else {
            // Generate a new unique access code (64 hex characters = 32 random bytes)
            $accessCode = bin2hex(random_bytes(32));
    
            // Pre-fill intern_name, student_no, company_name from DB so supervisor-eval.php
            // can show them automatically without extra queries at form-open time.
            $preStmt = $pdo->prepare("
                SELECT
                    s.full_name  AS full_name,
                    s.student_no AS student_no,
                    COALESCE(i.company, '') AS company
                FROM students s
                LEFT JOIN student_internships si ON si.student_id = s.id
                LEFT JOIN internships         i  ON i.id = si.internship_id
                WHERE s.id = ?
                LIMIT 1
            ");
            $preStmt->execute([$studentId]);
            $pre = $preStmt->fetch(PDO::FETCH_ASSOC);
    
            $ins = $pdo->prepare("
                INSERT INTO supervisor_evaluations
                    (student_id, supervisor_name, supervisor_email, access_code,
                    intern_name, student_no, company_name)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $ins->execute([
                $studentId,
                $supervisorName,
                $supervisorEmail,
                $accessCode,
                $pre['full_name']  ?? '',
                $pre['student_no'] ?? '',
                $pre['company']    ?? '',
            ]);
        }
    
        // Build the evaluation link
        $evalLink = SITE_URL . '/supervisor-eval.php?code=' . urlencode($accessCode);
    
        // Get student name for the email body
        $nameStmt = $pdo->prepare("SELECT full_name FROM students WHERE id = ?");
        $nameStmt->execute([$studentId]);
        $studentName = $nameStmt->fetchColumn() ?: 'a student intern';
    
        // Send email via PHPMailer
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
    
            // Sender & recipient
            $mail->setFrom(SMTP_USERNAME, 'PLV CEIT OJT System');
            $mail->addAddress($supervisorEmail, $supervisorName);
    
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Evaluation Request: ' . $studentName . ' — PLV CEIT';
            $mail->Body    = '
                <div style="font-family:system-ui,sans-serif;max-width:560px;margin:0 auto;padding:24px;">
                    <div style="background:#065f46;color:#fff;padding:20px 24px;border-radius:10px 10px 0 0;">
                        <h2 style="margin:0;font-size:1.1rem;">PLV College of Engineering and Information Technology</h2>
                        <p style="margin:4px 0 0;opacity:.8;font-size:13px;">OJT Supervisor Evaluation Request</p>
                    </div>
                    <div style="border:1px solid #e2e8f0;border-top:none;padding:24px;border-radius:0 0 10px 10px;">
                        <p>Dear <strong>' . htmlspecialchars($supervisorName ?: 'Supervisor') . '</strong>,</p>
                        <p>You have been requested to evaluate the internship performance of
                        <strong>' . htmlspecialchars($studentName) . '</strong>,
                        a student intern from PLV CEIT.</p>
                        <p>Please click the button below to fill out the evaluation form. The form is pre-filled
                        with the intern\'s information — you may edit any field before submitting.</p>
                        <div style="text-align:center;margin:28px 0;">
                            <a href="' . $evalLink . '"
                            style="background:#065f46;color:#fff;padding:12px 28px;border-radius:8px;
                                    text-decoration:none;font-weight:600;font-size:14px;">
                                Open Evaluation Form
                            </a>
                        </div>
                        <p style="font-size:12px;color:#64748b;">
                            Or copy this link into your browser:<br>
                            <a href="' . $evalLink . '" style="color:#065f46;">' . $evalLink . '</a>
                        </p>
                        <hr style="border:none;border-top:1px solid #f1f5f9;margin:20px 0;">
                        <p style="font-size:12px;color:#94a3b8;">
                            This link is unique to this evaluation request. Please do not share it.
                            Thank you for your cooperation.
                        </p>
                    </div>
                </div>
            ';
            // Plain-text fallback
            $mail->AltBody = "Dear {$supervisorName},\n\n"
                . "You have been asked to evaluate {$studentName} from PLV CEIT.\n\n"
                . "Open the evaluation form here:\n{$evalLink}\n\n"
                . "Thank you.";
    
            $mail->send();
    
            $_SESSION['flash'] = [
                'type' => 'success',
                'msg'  => "Evaluation link sent to {$supervisorEmail}.",
            ];
    
        } catch (Exception $e) {
            // Email failed — still keep the DB row so adviser can retry
            $_SESSION['flash'] = [
                'type' => 'danger',
                'msg'  => 'Supervisor assigned but email failed: ' . $mail->ErrorInfo,
            ];
        }
    
        header('Location: hte-ui.php?section=status');
        exit;
    }
    
    
    // ============================================================
    // ACTION 2: Supervisor submits the evaluation form
    // ============================================================
    if ($action === 'submit_supervisor_eval') {
    
        $code = trim($_POST['access_code'] ?? '');
    
        if (!$code) {
            http_response_code(400);
            die('Invalid access code.');
        }
    
        // Verify the code exists and has NOT been submitted yet
        $rowStmt = $pdo->prepare("
            SELECT id FROM supervisor_evaluations
            WHERE access_code = ? AND submitted_at IS NULL
        ");
        $rowStmt->execute([$code]);
        $row = $rowStmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$row) {
            // Either invalid code or already submitted
            header('Location: supervisor-eval.php?code=' . urlencode($code) . '&done=1');
            exit;
        }
    
        // Collect all rated fields (SMALLINT 1-4, nullable if left blank)
        $ratedFields = [
            'ls_questions', 'ls_resources', 'ls_accountability',
            'rw_written',   'rw_express',   'rw_math',
            'lv_listens',   'lv_meetings',  'lv_verbal',
            'ps_divides',   'ps_brainstorm','ps_solve',
            'pd_proactive', 'pd_priorities','pd_demeanor',
            'it_conflicts', 'it_team',      'it_assertive',
            'oe_endorse',   'oe_adapts',    'oe_channels',
            'wh_punctual',  'wh_attitude',  'wh_dress',
            'ca_ethics',    'ca_principled','ca_diversity',
            'is_proficiency','is_willingness','is_additional',
        ];
    
        // Helper: returns int if set & numeric, else null
        $int = fn($k) => isset($_POST[$k]) && is_numeric($_POST[$k]) ? (int) $_POST[$k] : null;
        $str = fn($k) => isset($_POST[$k]) ? trim($_POST[$k]) : null;
    
        // Build SET clause dynamically for the rated fields
        $setClauses = [];
        $params     = [];
    
        foreach ($ratedFields as $f) {
            $setClauses[] = "{$f} = ?";
            $params[]     = $int($f);
        }
    
        // Text fields
        $textFields = [
            'intern_name', 'student_no', 'company_name',
            'impact', 'strengths', 'improvements',
            'suggestions', 'supervise_future', 'supervise_future_reason',
            'title_position', 'contact_details',
        ];
        foreach ($textFields as $f) {
            $key = $f === 'intern_name'  ? $f
                : ($f === 'company_name'  ? $f
                : $f);
            // supervisor fills "supervisor_name_field" input, maps to supervisor_name column
            if ($f === 'title_position') {
                $setClauses[] = "title_position = ?";
                $params[]     = $str('title_position');
                continue;
            }
            $setClauses[] = "{$f} = ?";
            $params[]     = $str($f);
        }
    
        // supervisor_name comes from the "supervisor_name_field" input
        $setClauses[] = "supervisor_name = ?";
        $params[]     = $str('supervisor_name_field');
    
        // Numeric: overall_intern, overall_experience (0-10)
        $setClauses[] = "overall_intern = ?";
        $params[]     = $int('overall_intern');
        $setClauses[] = "overall_experience = ?";
        $params[]     = $int('overall_experience');
    
        // eval_date
        $setClauses[] = "eval_date = ?";
        $params[]     = $str('eval_date') ?: date('Y-m-d');
    
        // Mark as submitted
        $setClauses[] = "submitted_at = NOW()";
    
        // WHERE clause uses the row id (safer than access_code in UPDATE)
        $params[] = $row['id'];
    
        $sql = "UPDATE supervisor_evaluations SET "
            . implode(', ', $setClauses)
            . " WHERE id = ?";
    
        $upd = $pdo->prepare($sql);
        $upd->execute($params);
    
        // Redirect to the "Thank you" screen
        header('Location: supervisor-eval.php?code=' . urlencode($code) . '&done=1');
        exit;
    }
    
    // ── Unknown action ────────────────────────────────────────────────────────────
    http_response_code(400);
    echo 'Unknown action.';
