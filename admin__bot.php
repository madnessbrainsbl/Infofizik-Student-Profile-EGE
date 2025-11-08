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
$PAGE->requires->js(new moodle_url($CFG->wwwroot . "/local/studentprofile/assets/AdminBotPage.js") );


$courses = $DB->get_records("course");

$users = [];

echo $OUTPUT->header();


$botSettings = SettingsRepository::GetBotSettings();
$telegramAdminList = SettingsRepository::GetTelegramAdminList();
$telegramAdminList = implode(';', $telegramAdminList->admins);
?>

<style>
 .nav-pills .nav-link {
     border-radius: 100px;
 }
</style>

<style>
 .highlight-separator {
     padding: 2px 4px;
     background: lightgray;
     color: red;
     font-weight: bold;
     border-radius: 8px;
 }
</style>

<div>

<?php render_navigation('bot'); ?>    
    
</div>

<div id="AdminBotPageContainer" style="margin-top: 25px">

    <div class="row">

        <div class="col-md-6">

            <h4>
                Общие настроки
            </h4>

            <form autocomplete="off">
                <div class="form-group">
                    <label for="Form_BotKey">
                        API ключ для бота
                    </label>
                    <input id="Form_BotKey" class="form-control" name="bot_key" type="text" value="<?= $botSettings->bot_key ?>"/>
                </div>

                <div class="form-group form-check">
                    <?php
                    $attrs = '';

                    if ($botSettings->is_active) {
                        $attrs .= ' checked=checked';
                    }
                    
                    ?>
                    <input id="Form_BotIsActive" name="is_active" type="checkbox" <?= $attrs ?> class="form-check-input" />
                    
                    <label for="Form_BotIsActive" class="form-check-label">
                        включить общую рассылку в группы
                    </label>
                </div>

                <div class="form-group">
                    <button class="btn btn-primary" data-action="SaveBotSettings">
                        <i class="fa fa-save"></i>
                        Сохранить
                    </button>
                </div>
            </form>
            
        </div>

        <div class="col-md-6">

            <div class="form-group">

                <h4>
                    Установить webhook для бота
                </h4>

                <small>
                    Установить ссылку для бота, в которую бот будет отправлять уведомления с командами. Является первоначальной настройкой.
                </small>

            </div>

            <div class="form-group">
                <button class="btn btn-primary" data-action="SetBotWebhook">
                    <i class="fa fa-link"></i>
                    Установить webhook для бота
                </button>

                <button class="btn btn-secondary" data-action="InfoWebhook">
                    <i class="fa fa-info"></i>
                    Статус webhook
                </button>
                
                <button class="btn btn-danger" data-action="RemoveWebhook">
                    <i class="fa fa-trash"></i>
                    Удалить webhook
                </button>

            </div>
        </div>
        
    </div>

    <div class="row">
        <div class="col-md-6">
            <h4>Периодичность рассылки</h4>

            <?php

            $hourInput = '';
            $cbAttrs_mon = '';
            $cbAttrs_tue = '';
            $cbAttrs_wed = '';
            $cbAttrs_thu = '';
            $cbAttrs_fri = '';
            $cbAttrs_sat = '';
            $cbAttrs_sun = '';

            $input = get_task_scedule_for_simple_form();
            if ($input != null) {
                $hourInput = $input['hour'] . ':' . $input['minute'];

                $cbAttrs_mon = in_array('1', $input['days']) ? 'checked=checked' : '';
                $cbAttrs_tue = in_array('2', $input['days']) ? 'checked=checked' : '';
                $cbAttrs_wed = in_array('3', $input['days']) ? 'checked=checked' : '';
                $cbAttrs_thu = in_array('4', $input['days']) ? 'checked=checked' : '';
                $cbAttrs_fri = in_array('5', $input['days']) ? 'checked=checked' : '';
                $cbAttrs_sat = in_array('6', $input['days']) ? 'checked=checked' : '';
                $cbAttrs_sun = in_array('0', $input['days']) ? 'checked=checked' : '';
                
            }

            ?>

            <form autocomplete="off">
                <small>
                    Время на сервере: <?= date("Y-m-d H:i:s"); ?>
                </small>


                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="">Дни недели</label>

                            <div style="display: flex; gap: 15px">
                                <div>
                                    <input name="DayOfWeek_Mon" type="checkbox" <?=$cbAttrs_mon?> value="1" />
                                    <label for="">Пн</label>
                                </div>
                                <div>
                                    <input name="DayOfWeek_Tue" type="checkbox" <?=$cbAttrs_tue?> value="2" />
                                    <label for="">Вт</label>
                                </div>
                                <div>
                                    <input name="DayOfWeek_Wed" type="checkbox" <?=$cbAttrs_wed?> value="3" />
                                    <label for="">Ср</label>
                                </div>
                                <div>
                                    <input name="DayOfWeek_Thu" type="checkbox" <?=$cbAttrs_thu?> value="4" />
                                    <label for="">Чт</label>
                                </div>
                                <div>
                                    <input name="DayOfWeek_Fri" type="checkbox" <?=$cbAttrs_fri?> value="5" />
                                    <label for="">Пт</label>
                                </div>
                                <div>
                                    <input name="DayOfWeek_Sat" type="checkbox" <?=$cbAttrs_sat?> value="6" />
                                    <label for="">Сб</label>
                                </div>
                                <div>
                                    <input name="DayOfWeek_Sun" type="checkbox" <?=$cbAttrs_sun?> value="0" />
                                    <label for="">Вс</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">

                        <div class="form-group">
                            <label for="">Время (в формате 23:59)</label>
                            <input class="form-control" name="time" type="text" value="<?= $hourInput ?>"/>
                        </div>
                        
                    </div>

                </div>

                <div class="form-group">
                    <button class="btn btn-primary" data-action="SetBotSchedule">
                        <i class="fa fa-save"></i>
                        Установить расписание
                    </button>
                </div>
                
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <h4>Администраторы бота</h4>
            <small>Список пользователей telegram которые могут подключать бота в группы. В поле нужно вписать список
                имен пользователей через <span class="highlight-separator">;</span>
            </small>

            <div class="form-group">
                <label for="">Администраторы бота</label>
                <input class="form-control" id="Bot_BotAdmins" name="Test_ChatId" type="text" value="<?=$telegramAdminList?>"/>
            </div>

            <button class="btn btn-primary" data-action="SaveBotAdministrators">
                <i class="fa fa-save"></i>
                Сохранить список администраторов бота
            </button>
            
        </div>
    </div>

    <div class="row hidden">

        <div class="col-md-6">
            <h4>Отправка тестового сообщения</h4>

            <form autocomplete="off">

                <div class="form-group">
                    <label for="">ChatId</label>
                    <input class="form-control" id="Test_ChatId" name="Test_ChatId" type="text" value=""/>
                </div>
                <div class="form-group">
                    <label for="">CourseId</label>
                    <input class="form-control" id="Test_CourseId" name="Test_CourseId" type="text" value=""/>
                </div>
                <div class="form-group">
                    <label for="">UserId</label>
                    <input class="form-control" id="Test_UserId" name="Test_UserId" type="text" value=""/>
                </div>

                <div class="form-group">
                    <label for="">Message</label>
                    <textarea class="form-control" id="Test_Message" cols="30" id="" name="" rows="10"></textarea>
                </div>

                <button class="btn btn-secondary" data-action="SendTestMessage">
                    Отправить тестовое сообщение
                </button>
            </form>
        </div>

        <div class="col-md-6">
            <h4>users to notify</h4>


            <?php

            // NOTE: this is a copy 

            $botSettings = \SettingsRepository::GetBotSettings();
            if (
                strlen($botSettings->bot_key) == 0
                || !$botSettings->is_active
            )
            {
                echo 'bot is not set';
            }
            else
            {
                $tbot = new \TelegramBotManager($botSettings);

                // get all users
                
                $chatsToNotify = \TelegramStudentChatRepository::ListChats(true);

                foreach ($chatsToNotify as $userChatGroup)
                {
                    $foundUser = $DB->get_record("user", ["id" => $userChatGroup->user_id]);
                    $foundCourse = $DB->get_record("course", ["id" => $userChatGroup->course_id]);

                    if ($foundUser && $foundCourse) {
                        
                        echo '<p> user';
                        echo $userChatGroup->user_id;
                        echo ' // course:';
                        echo $userChatGroup->course_id;
                        echo ' // chat:';
                        echo $userChatGroup->group_chat_id;
                        echo '</p>';
                        // NOTE: message is not sent
                        // $message = \generate_telegram_bot_profile_stat_message($foundUser, $foundCourse);
                        //$response = $tbot->sendMessage($userChatGroup->group_chat_id, $message);
                    }
                }   
            }
            ?>
            
        </div>
    </div>

</div>

<script type="module">

 require(['core/toast'], (Toast) => {
     AdminBotPage.Init(Toast, AdminBotPageContainer);
 });
</script>

<?php
echo $OUTPUT->footer();
