<?php

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/src/bootstrap.php');

use core_completion\privacy\provider;

global $OUTPUT;
global $PAGE;
global $CFG;


$foundUser = null;
$foundCourse = null;

$tag = $_GET['tag'];
if (strlen($tag) > 0) {
    $data = decrypt_user_from_url_tag($tag);
    
    $userId = $data['id'];
    $courseId = $data['course_id'];

    $foundUser = $DB->get_record("user", ["id" => $userId]);
    $foundCourse = $DB->get_record("course", ["id" => $courseId]);
}

$PAGE->set_pagelayout('frontpage');

if ($foundUser && $foundCourse) {
    $PAGE->set_title('Статистика пользователя: ' . $foundUser->firstname . ' ' . $foundUser->lastname);
    $PAGE->set_heading('Статистика пользователя: ' . $foundUser->firstname . ' ' . $foundUser->lastname);
} else {
    $PAGE->set_title('Профиль не найден');
    $PAGE->set_heading('Профиль не найден');
}

?>

<?php

echo $OUTPUT->header();

if ($foundUser && $foundCourse) {
?>
    <style>

     .drawer-toggles {
         display: none !important;
     }

     div[data-region="footer-container-popover"] {
         display: none !important;
     }

     #page {
         margin-top: 0px !important;
         height: 100% !important;
     }

     nav.navbar {
         display: none;
     }
     
     .stat-report {
         display: flex;
         flex-direction: column;
     }
     .stat-report__line {
         display: flex;
         max-width: 400px;
         flex-direction: column;
     }

     .stat_report__line__label {
         text-align: left;
         font-size: 0.8rem;
     }

     .stat_report__line__value {
         font-size: 2rem;
         
     }

     .text-right {
         text-align: right;
     }

     h2, h3, h4 {
         margin-top: 45px;
     }

     

     @media (max-width: 700px) {
         .min-width-table {
             overflow-x: scroll;
         }
     }
    </style>

    <?php

    // check  enrolment on course
    $isUserEnrolled = false;
    $userCourses = enrol_get_users_courses($foundUser->id, true, '*');
    foreach ($userCourses as $userCourse) {
        if ($userCourse->id == $foundCourse->id) {
            $isUserEnrolled = true;
            break;
        }
    }

    if ($isUserEnrolled) {

        $stat = new StudentProfileStatManager($foundUser, $foundCourse);
    ?>

        <?php
        echo '<h2>Курс: ' . $stat->getCourseFullName() .  '</h2>';
        
        /****************************************************************************************************
         * basic stats
         ****************************************************************************************************/


        $courseCompletionPercent = $stat->quizCompletion();
        $weekTimeSpendOnQuizes = format_interval_seconds_to_human(get_current_week_time_label_spend_on_quizes($foundUser->id));
        $todayTimeSpendOnQuizes = format_interval_seconds_to_human(get_today_time_label_spend_on_quizes($foundUser->id));
        
        ?>

        <div class="block__basic-stats min-width-table">

            <table class="flexible table table-bordered generaltable generalbox" style="max-width: 1200px">
                <thead>
                    <tr>
                        <th>id пользователя </th>
                        <th>Фамилия</th>
                        <th>Имя</th>
                        <th style="max-width: 200px">Дата и Время первого входа на плаформу (внутри календарных суток)</th>
                        <th style="max-width: 200px">Суммарное время активностиa за текущую неделю</th>
                        <th style="max-width: 200px">Суммарное время активностиa за текущие сутки</th>
                        <th style="max-width: 200px">Общий прогресс прохождения курса (в %) </th>
                    </tr>
                </thead>

                <tbody>

                    <tr>
                        <td><?= $foundUser->id?></td>
                        <td><?= $foundUser->lastname?></td>
                        <td><?= $foundUser->firstname?></td>
                        <td class="text-right" style="max-width: 200px">
                            <?= format_user_last_login_date($foundUser); ?>
                        </td>
                        <td class="text-right" style="max-width: 200px"><?= $weekTimeSpendOnQuizes ?></td>
                        <td class="text-right" style="max-width: 200px"><?= $todayTimeSpendOnQuizes ?></td>
                        <td class="text-right" style="max-width: 200px"><?= $courseCompletionPercent ?></td>
                    </tr>
                    
                </tbody>
                
            </table>
        </div>


        <?php
        /****************************************************************************************************
         * to complete on current week
         ****************************************************************************************************/
        ?>

        <h3>
            <?php
            $weekStartLabel = ''; // TODO: 
            $weekEndLabel = ''; // TODO: 
            ?>
            Необходимый объем выполнения домашнего задания на текущей неделе:
        </h3>

        <div class="min-width-table">
        <table class="flexible table table-bordered generaltable generalbox" style="max-width: 600px">
            <thead>
                <tr>
                    <th>№ домашнего задания</th>
                    <th>Количество вопросов в тесте</th>
                    <th>Номер задания в ЕГЭ</th>
                </tr>
            </thead>

            <tbody>

                <?php

                foreach ($stat->getNotCompletedOnThisWeekQuizes() as $quiz)
                {
                ?>
                    <tr>
                        <td class="text-right"><?= $quiz->test_number?></td>
                        <td class="text-right"><?= $quiz->question_count ?></td>
                        <td class="text-right"><?= $quiz->ege_number ?></td>
                    </tr>

                    <?php
                }
                ?>
                
            </tbody>
            
        </table>

        </div>
        

        <?php
        /****************************************************************************************************
         * prev weeks
         ****************************************************************************************************/
        ?>

        <h3>Долги по домашним заданиям  за прошлые недели</h3>

        <div class="min-width-table">
        <table class="flexible table table-bordered generaltable generalbox" style="max-width: 600px">
            <thead>
                <tr>
                    <th>№ домашнего задания</th>
                    <th>Количество вопросов в тесте</th>
                    <th>Номер задания в ЕГЭ</th>
                </tr>
            </thead>

            <tbody>

                <?php
                foreach ($stat->getNotCompletedOnPrevWeeksQuizes() as $quiz)
                {
                ?>
                    <tr>
                        <td class="text-right"><?= $quiz->test_number?></td>
                        <td class="text-right"><?= $quiz->question_count ?></td>
                        <td class="text-right"><?= $quiz->ege_number ?></td>
                    </tr>

                    <?php
                }
                ?>
                
            </tbody>
            
        </table>
        </div>

        <?php
        /****************************************************************************************************
         * stat on for self study workd
         ****************************************************************************************************/

        $quizes = $stat->listSelfStudyWork();

        ?>

        <?php
        if (count($quizes) > 0) {
        ?>

            <h3>Самостоятельные работы по курсу</h3>

            <div class="min-width-table">

                <table class="flexible table table-bordered generaltable generalbox" style="max-width: 800px">
                    <thead>
                        <tr>
                            <th>Тип испытания </th>
                            <th>Количество заданий </th>
                            <th>% выполнения </th>
                            <th>Номера ошибочных заданий</th>
                            <th>Первичный балл ЕГЭ </th>
                            <th>Вторичный Балл ЕГЭ</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php
                        foreach ($quizes as $quiz)
                        {
                        ?>
                            <tr>
                                <td><?= $quiz->name ?></td>
                                <td><?= $quiz->question_count ?></td>
                                <td><?= $quiz->grade_percent_display ?></td>
                                <td class="text-right"><?= $quiz->mistake_numbers ?></td>
                                <td class="text-right"><?= $quiz->grade_display ?></td>
                                <td class="text-right"><?= $quiz->grade_ege_display ?></td>
                            </tr>

                        <?php
                        }
                        ?>

                    </tbody>
                </table>
                
            </div>
            
            <?php 
        }
        ?>

        
        

        <?php
        /****************************************************************************************************
         * stat on each test
         ****************************************************************************************************/
        ?>

        <h3>Статистика выполнения каждого домашнего задания</h3>

        <div class="min-width-table">
        <table class="flexible table table-bordered generaltable generalbox" style="max-width: 800px">
            <thead>
                <tr>
                    <th>№ домашнего задания</th>
                    <th>Номер задания ЕГЭ</th>
                    <th>Количество вопросов </th>
                    <th>% выполения теста текущей попытки</th>
                    <th>№ попытки выполнения теста</th>
                    <th>Дата последней попытки</th>
                    <th>Номера неправильных заданий</th>
                </tr>
            </thead>

            <tbody>
                <?php
                foreach ($stat->getAttemptStatistics() as $quiz)
                {
                ?>
                    <tr>
                        <td class="text-right"><?= $quiz->test_number ?></td>
                        <td class="text-right"><?= $quiz->ege_number ?></td>
                        <td class="text-right"><?= $quiz->question_count ?></td>
                        <td class="text-right"><?= round($quiz->grade_percent, 2) ?></td>
                        <td class="text-right"><?= $quiz->last_attempt_number ?></td>
                        <td class="text-right"><?= !empty($quiz->last_attempt_stamp) ? format_stamp($quiz->last_attempt_stamp) : '' ?></td>
                        <td class="text-right"><?= $quiz->mistake_numbers ?></td>
                    </tr>

                    <?php
                }
                ?>
            </tbody>
        </table>
        </div>

        <?php
        /****************************************************************************************************
         * exam weeks
         ****************************************************************************************************/
        ?>

        <div class="flex" style="display: flex; align-items: center; gap: 25px; margin-bottom: 2rem">
            <strong style="max-width: 300px">Текущий номинальный балл ЕГЭ по пройденным темам  без учета пробных испытаний: </strong>
            <span>
                <?= round($stat->getUserEgeGrade()) ?>
            </span>
        </div>

        <div class="flex" style="display: flex; align-items: center; gap: 25px">
            <strong style="max-width: 300px">Количество еженедельных вариантов, (с учетом долга), которые необходимо выполнить до воскресения 23:59 мск: </strong>
            <span>
                <?= round($stat->getWeeklyVariantCount()) ?>
            </span>
        </div>


        <?php

        foreach ($stat->listExamWeeks() as $examWeek)
        {
            ?>

            <h4><?= $examWeek['title'] ?></h4>

            <div class="min-width-table">

            <table class="flexible table table-bordered generaltable generalbox" style="max-width: 800px">
                <thead>
                    <tr>
                        <th>Тип испытания </th>
                        <th>Количество заданий </th>
                        <th>% выполнения </th>
                        <th>Номера ошибочных заданий</th>
                        <th>Первичный балл ЕГЭ </th>
                        <th>Вторичный Балл ЕГЭ</th>
                    </tr>
                </thead>

                <tbody>

                    <?php
                    foreach ($examWeek['quizes'] as $quiz)
                    {
                    ?>
                        <tr>
                            <td><?= $quiz->name ?></td>
                            <td><?= $quiz->question_count ?></td>
                            <td><?= $quiz->grade_percent_display ?></td>
                            <td class="text-right"><?= $quiz->mistake_numbers ?></td>
                            <td class="text-right"><?= $quiz->grade_display ?></td>
                            <td class="text-right"><?= $quiz->grade_ege_display ?></td>
                        </tr>

                    <?php
                    }
                    ?>

                </tbody>
            </table>
            </div>

            <?php
        }

        ?>

        <?php
        /****************************************************************************************************
         * rating
         ****************************************************************************************************/
        ?>

            <h3>
                Рейтинг учеников  по кол-ву решенных задач
            </h3>

            <div class="min-width-table">

            <table class="flexible table table-bordered generaltable generalbox" style="max-width: 800px">
                <thead>
                    <tr>
                        <th>Место в рейтинге</th>
                        <th>Имя </th>
                        <th>Фамилия </th>
                        <th>Кол-во баллов = кол-ву решенных задач</th>
                    </tr>
                </thead>

                <tbody>

                    <?php

                    $ratings = quiz_grades_user_rating($foundCourse->id);
                    $idx = 1;
                    foreach ($ratings as $r) {
                        ?>

                        <tr>
                            <td><?= $idx ?></td>
                            <td><?= $r->firstname ?></td>
                            <td><?= $r->lastname ?></td>
                            <td><?= round($r->grade_rating) ?></td>
                        </tr>
                        <?php
                        $idx++;
                    }

                    ?>
                    
                </tbody>
            </table>

            </div>


        <?php 
    } else {
        echo 'Пользователь не найден';
    }

    

 
} else {
    echo 'Пользователь не найден';
}

echo $OUTPUT->footer();
