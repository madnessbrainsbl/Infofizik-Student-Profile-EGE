<?php

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/src/bootstrap.php');

global $OUTPUT;
global $PAGE;
global $CFG;

require_login();

if (!is_siteadmin()) {
    throw new require_login_exception('');
}

$PAGE->set_pagelayout('frontpage');
$PAGE->requires->jquery();
$PAGE->requires->js(new moodle_url($CFG->wwwroot . "/local/studentprofile/assets/util.js") );
$PAGE->requires->js(new moodle_url($CFG->wwwroot . "/local/studentprofile/assets/AdminPage.js") );

$chatsByCourse = [];
$allConnectedChats = TelegramStudentChatRepository::ListChats(null);
foreach ($allConnectedChats as $connectedChat) {
    if (!array_key_exists($connectedChat->course_id, $chatsByCourse)) {
        $chatsByCourse[$connectedChat->course_id] = [];
    }

    $chatsByCourse[$connectedChat->course_id][$connectedChat->user_id] = $connectedChat;
}

$courses = $DB->get_records("course");

$users = [];

echo $OUTPUT->header();

?>

<style>
 .nav-pills .nav-link {
     border-radius: 100px;
 }
</style>

<div>

<?php render_navigation('course'); ?>    
    
</div>

<div class="secondary-navigation d-print-none admin-course-nav">
    <nav class="moremenu navigation observed">
        <ul class="nav more-nav nav-tabs">

            <?php
            foreach ($courses as $course) {
                if ($course->id == 1) {
                    continue;
                }
                echo '<li class="nav-item"><a class="nav-link" href="" data-course_id="' . $course->id. '" data-action="OpenCourseTab">' . $course->shortname. '</a></li>';
            }

            ?>
            
            
        </ul>
    </nav>
</div>

<div class="course-content">

    <?php
    foreach ($courses as $course) {
        if ($course->id == 1) {
            continue;
        }
        $enrolledUsers = enrol_get_course_users($course->id);

        $connectedUsers = [];
        if (array_key_exists($course->id, $chatsByCourse)) {
            $connectedUsers = $chatsByCourse[$course->id];
        }

        echo "<div class='course-tab-content' data-course_id='".$course->id."'>";

        echo "<div class='course-tab-content__course-point-map' data-course_id='".$course->id."'>";

        // TODO: add course checks?


        $pointMap = get_ege_point_map_for_course($course->id, 20);

        ?>

        <div class="form-group">
            <button class="btn btn-primary" data-action="StartEditPointMap" data-course_id="<?=$course->id?>">
                Редактировать словарь Первичный/Вторичный балл
            </button>

            <button class="btn btn-primary hidden" data-action="SavePointMap" data-course_id="<?=$course->id?>">
                Сохранить данные
            </button>
        </div>

        <table class="flexible table table-striped table-hover generaltable generalbox hidden" style="max-width: 200px">
            <thead>
                <tr>
                    <th>Первичный балл</th>
                    <th>Вторичный бал</th>
                </tr>
            </thead>

            <tbody>
                <?php 
                foreach ($pointMap as $fromPoint => $toPoint) {
                    echo "<tr>
<td>$fromPoint</td>
<td style='max-width: 80px'><input value='$toPoint' data-from_point='$fromPoint' name='point[$fromPoint]' type=text class=form-control autocomplete=off /></td>
                    </tr>";
                }
                 ?>

            </tbody>
        </table>
        <?php 

        echo '</div>';

        echo "<div class='course-tab-content__user-grades' data-course_id='".$course->id."'>";
        
        echo '<h4>Пользователи на курсе: ' . $course->fullname . '</h4>';


        if (count($enrolledUsers) == 0) {
            echo '<p>На курс не зарегистрировано ни одного пользователя</p>';
        } else {
    ?>
        <div class="form-group">
            <button class="btn btn-primary" data-action="SaveCourseEgePoints" data-course_id="<?=$course->id?>">
                Сохранить данные журнала
            </button>
        </div>
        
        <table class="flexible table table-striped table-hover generaltable generalbox">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Пользователь</th>
                    <th>Последний вход в систему</th>
                    <th>Бал ЕГЭ</th>
                    <th style='max-width: 80px'>Колво вариантов</th>
                    <th>Ссылка на профиль</th>
                    <th>Подключен к боту</th>
                </tr>
            </thead>

            <tbody>
                <?php

                $userGrades = get_user_grades_map_for_course($course->id);
                $userWeeklyVariantCounts = get_user_weekly_variant_counts_map_for_course($course->id);

                foreach ($enrolledUsers as $u) {
                    $id = $u->id;
                    $fio = $u->firstname . ' ' . $u->lastname;
                    $loginDate = format_stamp($u->currentlogin);
                    $url = '/local/studentprofile/?tag=' . urlencode(encrypt_user_url($u->id, $course->id));

                    $connectionStatus = 'Не подключен';
                    if (array_key_exists($id, $connectedUsers)) {
                        $connection = $connectedUsers[$id];

                        if ($connection->active > 0) {
                            $connectionStatus = 'Активен';
                        } else {
                            $connectionStatus = 'Не активен';
                        }

                        $connectionStatus = '<span style="text-decoration: underline; cursor: pointer" data-action="ToggleTelegramConnectionStatus" data-user_id="'.$u->id.'" data-course_id="'.$course->id.'">' . $connectionStatus . '</span>';
                    }

                    $grade = '0.00';
                    if (array_key_exists($u->id, $userGrades)) {
                        $grade = $userGrades[$u->id];
                    }
                    $weeklyVariantCount = '0';
                    if (array_key_exists($u->id, $userWeeklyVariantCounts)) {
                        $weeklyVariantCount = $userWeeklyVariantCounts[$u->id];
                    }
                    
                    echo "<tr>
            <td>$id</td>
            <td>$fio</td>
            <td>$loginDate</td>
            <td style='max-width: 80px'><input value='$grade' data-user_id='$id' name='grades' type=text class=form-control autocomplete=off /></td>
            <td style='max-width: 80px'><input value='$weeklyVariantCount' data-user_id='$id' name='weekly_variant_count' type=text class=form-control autocomplete=off /></td>
            <td><a href='$url'>Ссылка</a></td>
            <td>$connectionStatus</td>
        </tr>";
                }
                
                ?>
            </tbody>
        </table>        

        <?php 
        }

        echo '</div>'; // .course-tab-content__user_grades

        echo '</div>'; // .course-tab-content
        }
    ?>
    
</div>

<script>
 document.addEventListener("DOMContentLoaded", function() {
     AdminPage.Init();  
 });
</script>

<?php
echo $OUTPUT->footer();
