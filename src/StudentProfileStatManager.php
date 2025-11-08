<?php

require_once($CFG->dirroot. '/course/format/weeks/lib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');

use core_privacy\local\request\transform;

function quiz_get_never_answered_questions_in_quiz($userid, $quizid) {
    global $DB;
    $attempts = $DB->get_records('quiz_attempts', ['quiz' => $quizid, 'userid' => $userid, 'state' => 'finished']);
    $all = [];
    $correct = [];
    foreach ($attempts as $a) {
        $quba = question_engine::load_questions_usage_by_activity($a->uniqueid);
        foreach ($quba->get_slots() as $slot) {
            $all[$slot] = true;
            $qa = $quba->get_question_attempt($slot);
            if ($qa->get_fraction() >= 0.999) {
                $correct[$slot] = true;
            }
        }
    }
    if (empty($all)) {
        $count = count_questions_in_quiz($quizid);
        return implode(', ', range(1, $count));
    }
    $wrong = array_diff(array_keys($all), array_keys($correct));
    sort($wrong);
    return implode(', ', $wrong);
}

class StudentProfileStatManager
{
    public $user = null;
    public $course = null;
    public $completionInfo = null;
    public $courseFormat = null;
    
    public function __construct($user, $course)
    {
        global $DB;
        
        $this->user = $user;
        $this->course = $course;

        $this->courseFormat = format_weeks::instance($this->course->id);
        $this->completionInfo = new completion_info($foundCourse);


        $usedQuizIds = [];
        $this->idToNumMap = [];
        foreach ($this->courseFormat->get_sections() as $num => $s) {
            $usedQuizIds = array_merge($usedQuizIds, explode(',', $s->sequence));
            
            $this->idToNumMap[$s->id] = $num;
        }

        $usedQuizIds = array_map(function($item){ return intval($item); }, $usedQuizIds);

        $weeks = course_get_course_dates_for_user_id($course, $user->id);
        $this->userEntrollmentStart = $weeks['start'];

        $sql = "SELECT
    mq.*,
    mcm.`id` AS course_module_section_id,
    mcm.`section` AS course_section_id
FROM
    {quiz} mq 
INNER JOIN
    {course_modules} mcm
ON mq.id = mcm.`instance`
    AND mcm.module = 18
WHERE mcm.course = :courseid
AND deletioninprogress = 0";
        $quizes = $DB->get_records_sql($sql, ['courseid' => $course->id]);
        $quizGrades = quiz_grades_for_user_in_course($course->id, $user->id);

        $egePointMap = $DB->get_records_sql("select from_point, to_point from {ege_point_map} where course_id=:course_id", ['course_id' => $course->id]);

        $attempts = get_attempts_for_user_in_course($course->id, $user->id);
        $quizLastAttempts = [];
        
        foreach ($attempts as $a) {
            if (empty($a->timefinish)) {
                continue;
            }

            if (array_key_exists($a->quiz, $quizLastAttempts)) {
                $currentAttempt = $quizLastAttempts[$a->quiz];
                if ($currentAttempt['attempt'] < $a->attempt) {
                    $quizLastAttempts[$a->quiz]['id'] = $a->id;
                    $quizLastAttempts[$a->quiz]['attempt'] = $a->attempt;
                    $quizLastAttempts[$a->quiz]['timestart'] = $a->timestart;
                }
            } else {
                $quizLastAttempts[$a->quiz] = [
                    'id' => $a->id,
                    'attempt' => $a->attempt,
                    'timestart' => $a->timestart,
                ];
            }
            
        }
        unset($attempts);

        $this->quizes = [];
        $this->selfStudyTasks = [];

        $this->thisWeekQuizes = [];
        $this->prevWeeksQuizes = [];
        $this->pendingWeeksQuizes = [];

        $this->examWeeks = [];
        $now = time();

        $testWithTestNumberRegex = [
            '[Тт]ест[#№ ]+([0-9]+)',
            '[Дд]домашнее [Зз]адание[#№ ]+([0-9]+)',
            '([0-9]+)$',
        ];

        $selfStudyTaskRegex = [
            '[Сс]амостоятельная',
        ];

        $examRegex = [
            '[Ээ]кзамен',
            '[Кк]онтрольн',
        ];
        
        $classWorkRegex = [
            '[Кк]лассная',
        ];
        
        foreach ($quizes as $q) {

            if (!in_array($q->course_module_section_id, $usedQuizIds)) {
                continue;
            }
            
            $weeks = $this->courseFormat->get_section_dates(
                $this->courseFormat->get_section($this->idToNumMap[$q->course_section_id], $this->userEntrollmentStart)
            );
            $q->week_start = $weeks->start;
            $q->week_end = $weeks->end;

            $q->test_number = 0;
            foreach ($testWithTestNumberRegex as $re) {
                $matches = [];
                if (mb_ereg($re, $q->name, $matches)) {
                    $q->test_number = intval($matches[1]);
                    break;
                }
            }

            if ($q->test_number == 0) {
                $q->test_number = $q->name;
            }

            $q->question_count = count_questions_in_quiz($q->id);

            $matches = [];
            if (mb_ereg('[Нн]омер задания в ЕГЭ: +([0-9]+)', $q->intro, $matches)) {
                $q->ege_number = intval($matches[1]);
            } else {
                $q->ege_number = 0;
            }

            if ($q->ege_number == 0) {
                $q->ege_number = $q->intro;
            }

            $q->is_self_study = is_match_in_regex($selfStudyTaskRegex, $q->name);
            
            $q->is_exam = is_match_in_regex($examRegex, $q->name);

            $q->is_class_work = is_match_in_regex($classWorkRegex, $q->name);

            if ($q->is_exam) {
                if (!array_key_exists($q->week_start, $this->examWeeks)) {
                    $this->examWeeks[$q->week_start] = [
                        'start' => $q->week_start,
                        'end' => $q->week_end,
                        'quizes' => []
                    ];
                }

                $this->examWeeks[$q->week_start]['quizes'][] = $q->id;
            }

            $q->has_attempts = array_key_exists($q->id, $quizLastAttempts);

            if ($q->has_attempts) {
                $arr = $quizLastAttempts[$q->id];
                $q->last_attempt_id = $arr['id'];
                $q->last_attempt_number = $arr['attempt'];
                $q->last_attempt_stamp = $arr['timestart'];
            } else {
                $q->last_attempt_id = 0;
                $q->last_attempt_number = 0;
                $q->last_attempt_stamp = 0;
            }

            $q->grade_percent = 0;
            $q->grade_percent_display = 'попыток не было';
            $q->grade = 0;
            $q->grade_max = 0;
            $q->grade_ege = 0;
            $q->grade_ege_display = '';
            $q->grade_display = '';
            $q->is_completed = false;
            $q->mistake_numbers = '';

            if (array_key_exists($q->id, $quizGrades)) {
                $grade = $quizGrades[$q->id];
                
                if ($q->has_attempts) {
                    $q->mistake_numbers = quiz_get_never_answered_questions_in_quiz($user->id, $q->id);
                }

                if ($grade->itemgrademax != 0) {
                    $q->grade_percent = $grade->finalgrade / $grade->itemgrademax * 100;
                    $q->grade_max = $grade->itemgrademax;
                    $q->grade = $grade->finalgrade;
                    $q->is_completed = $grade->finalgrade == $grade->itemgrademax;
                    
                    if ($q->has_attempts) {
                        $q->grade_percent_display = round($q->grade_percent);
                        $q->grade_display = round($q->grade);
                    }
                    
                    if ($q->is_completed) {
                        $q->mistake_numbers = '';
                    }
                    
                    $gradeInt = strval(round($grade->finalgrade));

                    if (array_key_exists($gradeInt, $egePointMap)) {
                        $q->grade_ege = $egePointMap[$gradeInt]->to_point;
                        if ($q->has_attempts) {
                            $q->grade_ege_display = $q->grade_ege;
                        }
                    }
                } else {
                    $q->grade_max = $grade->itemgrademax;
                }
            }
            
            $this->quizes[$q->id] = $q;

            if ($weeks->start <= $now) {
                if ($weeks->end < $now) {
                    if (!$q->is_completed) {
                        $this->prevWeeksQuizes[] = $q->id;
                    }
                } else {
                    if (!$q->is_completed) {
                        $this->thisWeekQuizes[] = $q->id;
                    }
                }
            } else {
                $this->pendingWeeksQuizes = [];
            }
        }
        unset($quizes);
    }

    public function getLatestEnrolement($courseId, $userId)
    {
        $enrolments = enrol_get_course_users($courseId, false, [$userId]);

        $enrolment = array_reduce(array_values($enrolments), function($carry, $enrolment) use ($userId) {
            if (
                $enrolment->uestatus == ENROL_USER_ACTIVE &&
                    $enrolment->estatus == ENROL_INSTANCE_ENABLED &&
                    $enrolment->id == $userId
            ) {
                if (is_null($carry)) {
                    $carry = $enrolment;
                } else {
                    $carry = $carry->uetimestart < $enrolment->uetimestart ? $carry : $enrolment;
                }
            }

            return $carry;
        }, null);

        return $enrolment;
    }

    public function getCourseFullName()
    {
        return $this->course->fullname;
    }

    public function getNotCompletedOnThisWeekQuizes()
    {
        $result = [];

        foreach ($this->thisWeekQuizes as $quizId) {
            if ($this->quizes[$quizId]->is_exam || $this->quizes[$quizId]->is_self_study || $this->quizes[$quizId]->is_class_work) {
                continue;
            }
            $result[] = $this->quizes[$quizId];
        }

        return $result;
    }

    public function getNotCompletedOnPrevWeeksQuizes()
    {
        $result = [];

        foreach ($this->prevWeeksQuizes as $quizId) {
            if ($this->quizes[$quizId]->is_exam || $this->quizes[$quizId]->is_self_study || $this->quizes[$quizId]->is_class_work) {
                continue;
            }
            $result[] = $this->quizes[$quizId];
        }

        return $result;
    }

    public function getAttemptStatistics()
    {
        $result = [];
        foreach ($this->quizes as $q) {
            if ($q->is_exam) {
                continue;
            }
            
            if ($q->is_self_study) {
                continue;
            }
            
            if ($q->is_class_work) {
                continue;
            }
            
            if ($q->last_attempt_number !== 0) {
                $result[] = $q;
            }
        }
        return $result;
    }

    public function listSelfStudyWork()
    {
        $result = [];
        foreach ($this->quizes as $q) {
            if (!$q->is_self_study) {
                continue;
            }
            
            $result[] = $q;
        }
        return $result;
    }

    public function listExamWeeks()
    {
        $result = [];

        $idx = 1;
        foreach ($this->examWeeks as $week) {
            $item = [
                'title' => 'Зачетная неделя №' . $idx,
                'quizes' => []
            ];

            foreach ($week['quizes'] as $quizId) {
                if (array_key_exists($quizId, $this->quizes)) {
                    $item['quizes'][] = $this->quizes[$quizId];
                }
            }

            $result[] = $item;

            $idx++;
        }

        return $result;
    }

    public function quizCompletion()
    {
        if (count($this->quizes) == 0) {
            return 0 .  '%' ;
        }
        
        $total = 0;
        $current = 0;
        foreach ($this->quizes as $q) {
            if ($q->is_class_work) {
                continue;
            }
            $total += $q->grade_max;
            $current += $q->grade;
        }
        if ($total == 0) {
            return '0 %';
        }

        return (round($current / $total * 100)) . ' %';
    }

    public function getUserEgeGrade()
    {
        global $DB;

        $rec = $DB->get_record(
            'ege_student_grade',
            ['course_id' => $this->course->id, 'user_id' => $this->user->id]);

        if ($rec) {
            return $rec->grade;
        }
        return '0';
    }

    public function getWeeklyVariantCount()
    {
        global $DB;

        $rec = $DB->get_record(
            'weekly_variant_counts',
            ['course_id' => $this->course->id, 'user_id' => $this->user->id]);

        if ($rec) {
            return $rec->grade;
        }
        return '0';
    }
}