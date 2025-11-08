<?php

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/src/bootstrap.php');

global $DB;

if (!isset($_POST['action'])) {
    send_json(false, 'server error: action is not set', $_POST);
}

$action = $_POST['action'];

if ($action == 'save-course-ege-grades') {
    if (empty($_POST['data'])){
        send_json(false, 'server error: data not set', $_POST);
    }
    
    $courseId = $_POST['data']['course_id'];
    $grades = $_POST['data']['grades'];
    $weeklyVariantCounts = $_POST['data']['weekly_variant_count'];

    if (!isset($courseId)) {
        send_json(false, 'not valid post', $_POST);
    }
    if (!is_array($grades)) {
        send_json(false, 'not valid post', $_POST);
    }
    if (!is_array($weeklyVariantCounts)) {
        send_json(false, 'not valid post', $_POST);
    }

    foreach ($grades as $userId => $grade) {
        $obj = new \stdClass();
        $obj->user_id = $userId;
        $obj->course_id = $courseId;
        $obj->grade = $grade;
        $obj->timemodified = time();

        $inDb = $DB->get_record('ege_student_grade', ['user_id' => $obj->user_id, 'course_id' => $obj->course_id]);
        if ($inDb !== false) {
            $inDb->grade = $obj->grade;
            $inDb->timemodified = $obj->timemodified;
            $id = $DB->update_record('ege_student_grade', $inDb);
        } else {
            $id = $DB->insert_record('ege_student_grade', $obj);
        }
    }

    foreach ($weeklyVariantCounts as $userId => $value) {
        $obj = new \stdClass();
        $obj->user_id = $userId;
        $obj->course_id = $courseId;
        $obj->value = $value;
        $obj->timemodified = time();

        $inDb = $DB->get_record('weekly_variant_counts', ['user_id' => $obj->user_id, 'course_id' => $obj->course_id]);
        if ($inDb !== false) {
            $inDb->value = $obj->value;
            $inDb->timemodified = $obj->timemodified;
            $id = $DB->update_record('weekly_variant_counts', $inDb);
        } else {
            $id = $DB->insert_record('weekly_variant_counts', $obj);
        }
    }
    
    send_json(true, 'ok');
} else if ($action == 'save-course-ege-map') {
    if (empty($_POST['data'])){
        send_json(false, 'server error: data not set', $_POST);
    }
    
    $courseId = $_POST['data']['course_id'];
    $map = $_POST['data']['map'];

    if (!isset($courseId)) {
        send_json(false, 'not valid post', $_POST);
    }
    if (!is_array($map)) {
        send_json(false, 'not valid post', $_POST);
    }

    foreach ($map as $fromPoint => $toPoint) {
        $obj = new \stdClass();
        $obj->course_id = $courseId;
        $obj->from_point = $fromPoint;
        $obj->to_point = $toPoint;
        $obj->timemodified = time();

        $inDb = $DB->get_record('ege_point_map', ['course_id' => $obj->course_id, 'from_point' => $fromPoint]);
        if ($inDb !== false) {
            $inDb->to_point = $obj->to_point;
            $inDb->timemodified = $obj->timemodified;
            $id = $DB->update_record('ege_point_map', $inDb);
        } else {
            $id = $DB->insert_record('ege_point_map', $obj);
        }
    }
    
    send_json(true, 'ok');
} else if ($action == 'telegram-bot__save-settings') {
    $botKey = $_POST['data']['bot_key'];
    $isActive = $_POST['data']['is_active'];

    if (!isset($botKey) || !isset($isActive)) {
        send_json(false, 'not valid post', $_POST);
    }

    $isActive = $isActive == 'yes';

    SettingsRepository::SaveBotSettings($botKey, $isActive);
    
    send_json(true, 'ok');
} else if ($action == 'telegram-bot__set-bot-webhook') {
    $botSettings = SettingsRepository::GetBotSettings();

    if (strlen($botSettings->bot_key) == 0) {
        send_json(false, 'Для подключения webhook бот должен быть подключен', $_POST);
    }

    $tbot = new TelegramBotManager($botSettings);

    $serverName = $_SERVER['SERVER_NAME'];
    if ($tbot->registerWebhook($serverName) === false) {
        send_json(false, $tbot->last_error, $_POST);
    }
    
    send_json(true, 'Установлен webhook для домена: ' . $serverName);
} else if ($action == 'telegram-bot__delete-bot-webhook') {
    $botSettings = SettingsRepository::GetBotSettings();

    if (strlen($botSettings->bot_key) == 0) {
        send_json(false, 'Для подключения webhook бот должен быть подключен', $_POST);
    }

    $tbot = new TelegramBotManager($botSettings);
    $tbot->deleteWebhook();
    send_json(true, 'webhook удален');
} else if ($action == 'telegram-bot__info-bot-webhook') {
    $botSettings = SettingsRepository::GetBotSettings();

    if (strlen($botSettings->bot_key) == 0) {
        send_json(false, 'Для подключения webhook бот должен быть подключен', $_POST);
    }

    $tbot = new TelegramBotManager($botSettings);
    $response = $tbot->getWebhookInfo();
    send_json(true, 'Info: ' . $response);
} else if ($action == 'telegram-bot__set-bot-schedule') {

    $time = $_POST['data']['time'];
    $days = $_POST['data']['days'];

    if (!isset($time)) send_json(false, 'Поле время должно быть заполненно', $_POST);
    if (!isset($days)) send_json(false, 'Поле дни должно быть заполненно', $_POST);

    $time = trim($time);
    if (!preg_match('/^\d\d:\d\d$/', $time)) send_json(false, 'Время в не правильном формате', $_POST);

    if (!is_array($days)) send_json(false, 'Дни в не правильном формате', $_POST);
    if (count($days) == 0) send_json(false, 'Должен быть установлен хотябы один день', $_POST);

    $time = explode(':', $time);
    set_task_scedule_for_simple_form($time[0], $time[1], $days);

    send_json(true, 'Расписание установлено', $_POST);
} else if ($action == 'telegram-bot__set-admin-list') {
    $admins = $_POST['data']['admins'];

    if (!isset($admins) || !is_string($admins)) {
        send_json(false, 'not valid post', $_POST);
    }

    $admins = array_map(function($item) {
        $item = rtrim(trim($item), '/');
        $item = str_replace('https://t.me/', '', $item);
        return $item;
    }, explode(';', $admins));

    SettingsRepository::SaveTelegramAdminList($admins);
    
    send_json(true, 'ok');
} else if ($action == 'telegram-bot__send-test-message') {
    $chatId = $_POST['data']['chat_id'];
    $userId = $_POST['data']['user_id'];
    $courseId = $_POST['data']['course_id'];
    $message = $_POST['data']['message'];

    if (!isset($chatId) || !isset($message)) {
        send_json(false, 'not valid post', $_POST);
    }

    $botSettings = SettingsRepository::GetBotSettings();
    if (strlen($botSettings->bot_key) == 0) {
        send_json(false, 'Для подключения webhook бот должен быть подключен', $_POST);
    }

    $tbot = new TelegramBotManager($botSettings);

    $foundUser = $DB->get_record("user", ["id" => $userId]);
    $foundCourse = $DB->get_record("course", ["id" => $courseId]);

    if ($foundUser && $foundCourse) {
        $message = \generate_telegram_bot_profile_stat_message($foundUser, $foundCourse);
        $response = $tbot->sendMessage($chatId, $message);
    }

    send_json(true, 'Тестовое сообщение отправлено' , [$chatId, $message, $response]);
} else if ($action == 'profile__toggle-bot-update-state') {
    $userId = $_POST['data']['user_id'];
    $courseId = $_POST['data']['course_id'];

    if (!isset($userId) || !isset($courseId)) {
        send_json(false, 'not valid post', $_POST);
    }

    $connection = TelegramStudentChatRepository::GetConnection($userId, $courseId);

    if ($connection) {
        $connection->active = $connection->active == 1 ? 0 : 1;
        TelegramStudentChatRepository::UpdateConnection($connection);

        $newStatus = $connection->active == 1 ? 'Активен' : 'Не активен';

        send_json(true, $newStatus);
    } else {
        send_json(false, 'connection not found for user');
    }
} else {
    send_json(false, 'server error: action not found: ' . $action);
}

