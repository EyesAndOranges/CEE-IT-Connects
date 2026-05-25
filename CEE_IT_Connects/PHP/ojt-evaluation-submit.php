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