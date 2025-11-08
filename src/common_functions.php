<?php

function format_stamp($timestamp)
{
    return date('Y-m-d H:i:s', $timestamp);
}

function format_user_last_login_date($foundUser)
{
    if ($foundUser->currentlogin != 0) {
        return format_stamp($foundUser->currentlogin);
    }
    return  'Вход не был выполнен';
}

function format_interval_seconds_to_human($interval)
{
    if ($interval < 60) {
        return $interval . ' сек';
    }

    $secs = $interval - floor($interval / 60) * 60;

    return floor($interval / 60) . ' мин ' . $secs . ' сек';
}

function encrypt_user_url($id, $courseId)
{
    $input = json_encode(['id' => $id, 'course_id' => $courseId]);
    $ciphering = "AES-128-CTR";
    $iv_length = openssl_cipher_iv_length($ciphering);

    $key = 'hello98he==llo98h`ello_98hello98hell*o98';
    $iv = '1234567891011121';
    $options = 0;
    
    $output = openssl_encrypt($input, $ciphering, $key, $options, $iv);
    
    return $output;
}

function decrypt_user_from_url_tag($string)
{
    $ciphering = "AES-128-CTR";
    $key = 'hello98he==llo98h`ello_98hello98hell*o98';
    $options = 0;
    $iv = '1234567891011121';
    
    $json = openssl_decrypt($string, $ciphering, $key, $options, $iv);

    $output = json_decode($json, true);

    if (is_array($output)) {
        return $output;
    }

    return false;
}

function debug_area($object)
{
    echo '<textarea style="border-left: 3px solid red; width: 100%">';
    echo json_encode($object, JSON_PRETTY_PRINT);
    echo '</textarea>';
}


function send_json($isOk, $msg, $data = null)
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => $isOk,
        'msg' => $msg,
        'data' => $data
    ]);
    die();
}

function quiz_attempt_correct_answer_stat($quizAttemptId)
{
    global $DB;

    $sql = "SELECT
    SUM(case when qas.state = 'gradedwrong' then 1 else 0 END) as wrong_answer_count,
    SUM(case when qas.state = 'gradedright' OR qas.state = 'gradedpartial' then 1  else 0 END) as correct_answer_count
FROM mdl_quiz_attempts quiza
JOIN mdl_question_usages qu ON qu.id = quiza.uniqueid
JOIN mdl_question_attempts qa ON qa.questionusageid = qu.id
JOIN mdl_question_attempt_steps qas ON qas.questionattemptid = qa.id
WHERE quiza.id = :quiz";

    $record = $DB->get_record_sql($sql, ['quiz' => $quizAttemptId]);
    
    $result = [
        'total' => 0,
        'correct' => 0,
        'wrong' => 0,
    ];

    if ($record) {
        $result['correct'] = $record->correct_answer_count;
        $result['wrong'] = $record->wrong_answer_count;
        $result['total'] = $result['correct'] + $result['wrong'];
    }
    return $result;
}

function get_user_grades_map_for_course($course_id)
{
    global $DB;
    $records = $DB->get_records('ege_student_grade', ['course_id' => $course_id]);

    $result = [];
    foreach ($records as $rec) {
        $result[$rec->user_id] = $rec->grade;
    }
    return $result;
}

function get_user_weekly_variant_counts_map_for_course($course_id)
{
    global $DB;
    $records = $DB->get_records('weekly_variant_counts', ['course_id' => $course_id]);

    $result = [];
    foreach ($records as $rec) {
        $result[$rec->user_id] = $rec->value;
    }
    return $result;
}


function get_ege_point_map_for_course($course_id, $minFill=20)
{
    global $DB;
    $records = $DB->get_records('ege_point_map', ['course_id' => $course_id]);

    $result = [];
    foreach ($records as $rec) {
        $result[$rec->from_point] = $rec->to_point;
    }

    if ($minFill !== null) {
        if (count($result) < $minFill) {
            for ($i = count($result); $i <= $minFill; $i++) {
                $result[$i] = '0.00';
            }
        }
    }
    
    return $result;
}


function get_current_week_time_label_spend_on_quizes($userId)
{
    $timeStart = strtotime("this week");
    $timeEnd = strtotime("next week");
    
    $result = calculate_user_quiz_activity_attempts_in_period($userId, $timeStart, $timeEnd);
    return $result;
}

function get_today_time_label_spend_on_quizes($userId)
{
    $timeStart = (new DateTime((new DateTime())->format('Y-m-d')))->getTimestamp();
    $timeEnd = (new DateTime((new DateTime('tomorrow'))->format('Y-m-d')))->getTimestamp();

    $result = calculate_user_quiz_activity_attempts_in_period($userId, $timeStart, $timeEnd);
    return $result;
}

function calculate_user_quiz_activity_attempts_in_period($userId, $timeStart, $timeEnd)
{
    global $DB;
    
    $sql = "SELECT *
FROM {quiz_attempts}
WHERE
    userid = :userid AND timestart >= :timestart AND timefinish < :timefinish";

    $records = $DB->get_records_sql($sql, [
        'userid' => $userId,
        'timestart' => $timeStart,
        'timefinish' => $timeEnd
    ]);


    $deltasecs = 0;
    foreach ($records as $rec) {
        $deltasecs += $rec->timefinish - $rec->timestart;
    }

    if ($deltasecs < 0) {
        $deltasecs = 0;
    }

    return $deltasecs;
}

function count_questions_in_quiz($quizId)
{
    global $DB;

    $sql = "select count(id) as count from {quiz_slots} where quizid = :quizid";
    // 
    
    // $sql = "SELECT COUNT(q.id) as count
// FROM {quiz_slots} slot
// LEFT JOIN {question_references} qr ON qr.component = 'mod_quiz'
// AND qr.questionarea = 'slot' AND qr.itemid = slot.id
// LEFT JOIN {question_bank_entries} qbe ON qbe.id = qr.questionbankentryid
// LEFT JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
// LEFT JOIN {question} q ON q.id = qv.questionid
// WHERE slot.quizid = :quizid";

    $record = $DB->get_record_sql($sql, ['quizid' => $quizId]);
    return $record->count;
}

function get_attempts_for_user_in_course($courseId, $userId)
{
    global $DB;
    
    $attempts = $DB->get_records_sql('SELECT
    *
FROM
    {quiz_attempts}
WHERE userid = :userid AND quiz IN (select id from {quiz} mq where course = :courseid)', ['userid' => $userId, 'courseid' => $courseId]);

    return $attempts;
}

function quiz_grades_for_user_in_course($courseId, $userId)
{
    global $DB;

    $sql = "SELECT 
    gi.iteminstance as quiz,
    u.id AS userid,
    u.username AS studentid,
    gi.grademax AS itemgrademax,
    g.finalgrade AS finalgrade
FROM {user} u
    JOIN {grade_grades} g ON g.userid = u.id 
    JOIN {grade_items} gi ON g.itemid =  gi.id AND gi.itemmodule = 'quiz'
    JOIN {course} c ON c.id = gi.courseid
    WHERE gi.courseid = :courseid AND u.id = :userid";
    
    $records = $DB->get_records_sql($sql, ['courseid' => $courseId, 'userid' => $userId]);

    $result = [];
    foreach ($records as $r) {
        $result[$r->quiz] = $r;
    }
    
    return $result;
}

function quiz_grades_user_rating($courseId)
{
    global $DB;

    $sql = "
SELECT
u.id,
u.firstname,
u.lastname,
case when user_rating.grade_rating is null then 0 else user_rating.grade_rating end as grade_rating
FROM
{user} as u
LEFT JOIN (
SELECT 
    u.id AS user_id,
    SUM(g.finalgrade) as grade_rating
FROM {user} u
    JOIN {grade_grades} g ON g.userid = u.id 
    JOIN {grade_items} gi ON g.itemid =  gi.id AND gi.itemmodule = 'quiz'
    JOIN {course} c ON c.id = gi.courseid
    WHERE gi.courseid = :courseid
GROUP BY u.id
) AS user_rating on user_rating.user_id = u.id
ORDER BY
CASE WHEN user_rating.grade_rating is null THEN 0 ELSE user_rating.grade_rating END DESC, u.lastname ASC
LIMIT 10";
    
    $records = $DB->get_records_sql($sql, ['courseid' => $courseId]);

    
    
    return $records;
}

function quiz_wrong_attempts_number($quizAttemptId)
{
    global $DB;

    $sql = "SELECT
    qa.slot
FROM {quiz_attempts} quiza
JOIN {question_usages} qu ON qu.id = quiza.uniqueid
JOIN {question_attempts} qa ON qa.questionusageid = qu.id
JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id
WHERE quiza.id = :quiz AND qas.state IN ('gradedwrong', 'gaveup')";

    $records = $DB->get_records_sql($sql, ['quiz' => $quizAttemptId]);

    $slots = array_map(function($i){ return $i->slot; }, $records);
    
    return implode(', ', $slots);
}

function quiz_get_not_answered_questions_in_quiz($userId, $quizId)
{
    global $DB;
    
    $sql = "SELECT
    qas.id, qa.slot as slot_id, qas.state as state
FROM mdl3v_quiz_attempts quiza
JOIN mdl3v_question_usages qu ON qu.id = quiza.uniqueid
JOIN mdl3v_question_attempts qa ON qa.questionusageid = qu.id
JOIN mdl3v_question_attempt_steps qas ON qas.questionattemptid = qa.id
WHERE quiza.quiz = :quizid AND quiza.userid = :userid";

    $records = $DB->get_records_sql($sql, ['quizid' => $quizId, 'userid' => $userId]);

    $completed = [];
    $bad = [];
    foreach ($records as $record) {
        $isCompleted = in_array($record->state, ['complete', 'gradedright']);
        if ($isCompleted) {
            $completed[] = $record->slot_id;
        } else {
            $bad[] = $record->slot_id;
        }

/*
        if ($isCompleted) {
            $markedBadIdx = array_search(, $result);
            if ($markedBadIdx !== false) {
                unset($result[$markedBadIdx]);
            }
            if (!in_array($record->slot_id, $completedSlotIds)) {
                $completedSlotIds[] = $record->slot_id;
            }
        } else {
            if (in_array($record->slot_id, $completedSlotIds)) {
                $markedBadIdx = array_search($record->slot_id, $result);
                if ($markedBadIdx !== false) {
                    unset($result[$markedBadIdx]);
                }
            } else {
                $result[] = $record->slot_id;
            }
        }
        */
    }
    
    $completed = array_unique($completed);
    $bad = array_unique($bad);

    return implode(', ', array_diff($bad, $completed));
}

function render_navigation($currentPage)
{
    ?>

    <nav class="navigation">
        <ul class="nav nav-pills">
            <li class="nav-item">
                <?php
                $class = 'nav-link';
                if ($currentPage == 'course') {
                    $class .= ' active';
                }
                ?>
                <a href="/local/studentprofile/admin.php" class="<?= $class ?>">
                    <i class="fa fa-book"></i>
                    Курсы
                </a>
            </li>
            <li class="nav-item">
                <?php
                $class = 'nav-link';
                if ($currentPage == 'bot') {
                    $class .= ' active';
                }
                ?>
                <a href="/local/studentprofile/admin__bot.php" class="<?= $class ?>">
                    <i class="fa fa-telegram"></i>
                    Телеграм бот
                </a>
            </li>
        </ul>
    </nav>
    <?php
}


function get_task_scedule_for_simple_form()
{
    global $DB;

    $taskClass = '\local_studentprofile\task\student_telegram_notification';
    $task = $DB->get_record('task_scheduled', ['classname' => $taskClass]);

    $result = null;
    if ($task !== false) {
        if ($task->day == '*' && $task->month == '*') {
            $result = [
                'hour' => $task->hour,
                'minute' => $task->minute,
                'days' => explode(',', $task->dayofweek)
            ];
        }
    }

    return $result;
}
function set_task_scedule_for_simple_form($hour, $minute, $days)
{
    global $DB;

    $taskClass = '\local_studentprofile\task\student_telegram_notification';
    $task = $DB->get_record('task_scheduled', ['classname' => $taskClass]);

    $result = null;
    if ($task !== false) {
        $task->day = '*';
        $task->month = '*';
        $task->hour = $hour;
        $task->minute = $minute;
        $task->dayofweek = implode(',', $days);

        $oTask = \core\task\manager::scheduled_task_from_record($task);

        $task->nextruntime = $oTask->get_next_scheduled_time();

        $DB->update_record('task_scheduled', $task);
    }

    return $result;
}

function is_match_in_regex($regexes, $str)
{
    $result = false;
    foreach ($regexes as $re) {
        $matches = [];
        if (mb_eregi($re, $str, $matches)) {
            $result = true;
            break;
        }
    }
    return $result;
}
