<?php

namespace local_studentprofile\task;

global $CFG;

require_once(dirname(__FILE__) . '/../../src/bootstrap.php');

class student_telegram_notification extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name()
    {
        return 'Уведомление пользователей через телеграмм [studentprofile]';
    }

    /**
     * Execute the task.
     */
    public function execute()
    {
        global $DB;

        // init bot and check is active
        $botSettings = \SettingsRepository::GetBotSettings();
        if (
            strlen($botSettings->bot_key) == 0
                || !$botSettings->is_active
        )
        {
            return;
        }

        $tbot = new \TelegramBotManager($botSettings);

        // get all users
        
        $chatsToNotify = \TelegramStudentChatRepository::ListChats(true);

        // iterate over all users and send messages

        foreach ($chatsToNotify as $userChatGroup)
        {
            $foundUser = $DB->get_record("user", ["id" => $userChatGroup->user_id]);
            $foundCourse = $DB->get_record("course", ["id" => $userChatGroup->course_id]);

            if ($foundUser && $foundCourse) {
                $message = \generate_telegram_bot_profile_stat_message($foundUser, $foundCourse);
                $response = $tbot->sendMessage($userChatGroup->group_chat_id, $message);
            }
        }
    }
}
