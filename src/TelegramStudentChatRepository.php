<?php

class TelegramStudentChatRepository
{
    public static function ListChats($isActive=null)
    {
        global $DB;

        $where = [];
        if ($isActive !== null) {
            if ($isActive) {
                $where['active'] = 1;
            } else {
                $where['active'] = 0;
            }
        }
        $records = $DB->get_records('telegram_student_chat', $where);
        return $records;
    }

    public static function RemoveChatIfExists($groupChatId)
    {
        global $DB;
        $DB->delete_records('telegram_student_chat', ['group_chat_id' => $groupChatId]);
    }

    public static function GetConnection($userId, $courseId)
    {
        global $DB;

        $record = $DB->get_record('telegram_student_chat', ['user_id' => $userId, 'course_id' => $courseId]);

        if ($record === false) {
            $record = null;
        }
        return $record;
    }

    public static function CreateConnection($userId, $courseId, $groupChatId)
    {
        global $DB;

        $value = new \stdClass();
        $value->user_id = $userId;
        $value->course_id = $courseId;
        $value->group_chat_id = $groupChatId;
        
        $value->time_created = time();
        $value->time_last_message = 0;
        $value->active = 1;
        
        $id = $DB->insert_record('telegram_student_chat', $value);
        return $id;
    }

    public static function UpdateConnection($record)
    {
        global $DB;
        $id = $DB->update_record('telegram_student_chat', $record);
    }
}
