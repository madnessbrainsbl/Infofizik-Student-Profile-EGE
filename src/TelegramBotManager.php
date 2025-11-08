<?php

class TelegramBotManager
{
    public function __construct($botSettings)
    {
        $this->bot_key = $botSettings->bot_key;
        $this->is_active = $botSettings->is_active;

        $this->last_error = '';
    }

    public function registerWebhook()
    {
        global $CFG;

        // TODO: check https

        $getQuery = [
            "url" => $CFG->wwwroot . "/local/studentprofile/webhook.php",
            'drop_pending_updates' => 'True',
            'allowed_updates' => json_encode(['message'])
        ];

        $ch = curl_init("https://api.telegram.org/bot". $this->bot_key ."/setWebhook?" . http_build_query($getQuery));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $resultQuery = curl_exec($ch);
        curl_close($ch);
    }

    public function deleteWebhook()
    {
        $getQuery = [
            'drop_pending_updates' => 'True'
        ];
        $ch = curl_init("https://api.telegram.org/bot". $this->bot_key ."/deleteWebhook?" . http_build_query($getQuery));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $resultQuery = curl_exec($ch);
        curl_close($ch);
    }

    public function getWebhookInfo()
    {
        $ch = curl_init("https://api.telegram.org/bot". $this->bot_key ."/getWebhookInfo");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function sendMessage($chatId, $message)
    {
        if (!$this->is_active) {
            return;
        }
        
        $messageData = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => "html",
        ];

        // TODO: test for responses

        $url = "https://api.telegram.org/bot". $this->bot_key ."/sendMessage";
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        $payload = json_encode($messageData);
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
        $response = curl_exec($ch);
        curl_close($ch);

        return [
            'response' => $response,
            'url' => $url
        ];
    }
}
