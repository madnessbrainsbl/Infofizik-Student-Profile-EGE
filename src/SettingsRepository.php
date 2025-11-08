<?php

class SettingsRepository
{
    public static function GetTelegramAdminList()
    {
        $settings = self::GetJsonByKey('bot_admin');

        if ($settings === null) {
            $settings = new \stdClass();
            $settings->id = null;
            $settings->admins = [];
        }
        return $settings;
    }

    public static function SaveTelegramAdminList($users)
    {
        $key = 'bot_admin';
        $settings = self::GetJsonByKey($key);

        if ($settings == null) {
            $settings = new \stdClass();
            $settings->id = null;
        }

        $settings->admins = $users;

        if ($settings->id === null) {
            self::CreateJsonByKey($key, $settings);
        } else {
            self::UpdateJsonByKey($key, $settings->id, $settings);
        }
    }
    
    public static function GetBotSettings()
    {
        $settings = self::GetJsonByKey('bot_settings');

        if ($settings == null) {
            $settings = new \stdClass();
            $settings->id = null;
            $settings->bot_key = '';
            $settings->is_active = false;
        }
        
        return $settings;
    }

    public static function SaveBotSettings($botKey, $isActive=true)
    {
        $key = 'bot_settings';
        $settings = self::GetJsonByKey($key);

        if ($settings == null) {
            $settings = new \stdClass();
            $settings->id = null;
        }

        $settings->bot_key = $botKey;
        $settings->is_active = $isActive;

        if ($settings->id === null) {
            self::CreateJsonByKey($key, $settings);
        } else {
            self::UpdateJsonByKey($key, $settings->id, $settings);
        }
    }

    public static function GetJsonByKey($key)
    {
        global $DB;

        $record = $DB->get_record('studentprofile_settings', ['setting_key' => $key]);

        if ($record === false) {
            return null;
        }

        $data = json_decode($record->json_value, false);
        $data->id = $record->id;
        return $data;
    }

    public static function CreateJsonByKey($key, $settings)
    {
        global $DB;
        
        $value = new \stdClass();
        $value->setting_key = $key;
        $value->json_value = json_encode($settings);
        $id = $DB->insert_record('studentprofile_settings', $value);
        return $id;
    }
    
    public static function UpdateJsonByKey($key, $id, $settings)
    {
        global $DB;
        
        $value = new \stdClass();
        $value->id = $id;
        $value->setting_key = $key;
        $value->json_value = json_encode($settings);
        $id = $DB->update_record('studentprofile_settings', $value);
        return $id;
    }
}
 
