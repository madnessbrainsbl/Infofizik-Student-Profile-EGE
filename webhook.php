<?php

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/src/bootstrap.php');


function writeLogFile($string, $clear = false){
    $log_file_name = __DIR__."/message.txt";
    if($clear == false) {
        $now = date("Y-m-d H:i:s");
        file_put_contents($log_file_name, $now." ".print_r($string, true)."\r\n", FILE_APPEND);
    }
    else {
        file_put_contents($log_file_name, '');
        file_put_contents($log_file_name, $now." ".print_r($string, true)."\r\n", FILE_APPEND);
    }
}

$data = file_get_contents('php://input');

writeLogFile($data);

$dataJson = json_decode($data, true);

if ($dataJson === null) {
    writeLogFile('[ERROR] failed parsing sent content: ' . $data);
    header('Status: 200');
    echo '200 OK';
    exit;
}

if (!array_key_exists('message', $dataJson)) {
    // NOTE: non message types are ignored
    header('Status: 200');
    echo '200 OK';
    exit;
}

try {
    $msg = $dataJson['message'];

    $fromUsername = $msg['from']['username'];
    $chatId = $msg['chat']['id'];

    $botSettings = SettingsRepository::GetBotSettings();
    $allowedUsernames = SettingsRepository::GetTelegramAdminList();

    $tbot = new TelegramBotManager($botSettings);

    if (in_array($fromUsername, $allowedUsernames->admins)) {

        $text = $msg['text'];

        $cmd = null;
        $connectionUrl = null;

        foreach ($msg['entities'] as $entity) {
            if ($entity['type'] == 'bot_command') {
                if ($cmd == null) {
                    $value = mb_substr($text, $entity['offset'], $entity['length']);
                    $cmd = array_shift(explode('@', $value));
                }
            }

            if ($entity['type'] == 'url') {
                if ($connectionUrl == null) {
                    $connectionUrl = mb_substr($text, $entity['offset'], $entity['length']);
                }
            }
        }

        if ($cmd == '/connect') {
            if ($connectionUrl !== null) {
                $queryVariables = [];

                parse_str(parse_url($connectionUrl, PHP_URL_QUERY), $queryVariables);

                if (array_key_exists('tag', $queryVariables)) {
                    $tag = $queryVariables['tag'];
                    $data = decrypt_user_from_url_tag($tag);
    
                    $userId = $data['id'];
                    $courseId = $data['course_id'];

                    $foundUser = $DB->get_record("user", ["id" => $userId]);
                    $foundCourse = $DB->get_record("course", ["id" => $courseId]);

                    if ($foundUser && $foundCourse) {
                        // success
                        $userName = $foundUser->firstname . ' ' . $foundUser->lastname;
                        $courseName = $foundCourse->fullname;

                        $connection = TelegramStudentChatRepository::GetConnection($userId, $courseId);

                        TelegramStudentChatRepository::RemoveChatIfExists($chatId);

                        if ($connection) {
                            $connection->group_chat_id = $chatId;
                            TelegramStudentChatRepository::UpdateConnection($connection);
                        } else {
                            TelegramStudentChatRepository::CreateConnection($userId, $courseId, $chatId);
                        }
                        
                        $tbot->sendMessage($chatId, "Данные профиля $userName по курсу $courseName подключены к этой группе");
                    } else {
                        $tbot->sendMessage($chatId, 'Не правильный формат тега');
                    }
                } else {
                    $tbot->sendMessage($chatId, 'Указанная ссылка имеет не правильный формат');
                }
            } else {
                $tbot->sendMessage($chatId, 'Для подключения нужна ссылка на профиль');
            }
        } else {
            // $tbot->sendMessage($chatId, 'Комманда не найдена');
        }
    } else {
        // writeLogFile('testing:' . $chatId);
        //$tbot->sendMessage($chatId, 'Ошибка! У Вас нет прав для работы с ботом');
    }
} catch (Exception $e) {
    writeLogFile('Error: ' . $e->getMessage());
}

header('Status: 200');
echo '200 OK';
exit;
/*
  
{"update_id":348493811,
"message":{"message_id":12,"from":{"id":175342062,"is_bot":false,"first_name":"Vlad","last_name":"Pereskokov","username":"vladpereskokov","language\
_code":"en","is_premium":true},"chat":{"id":-4164094820,"title":"Testing Notifications","type":"group","all_members_are_administrators":true},"date\
":1724689642,"text":"/connect@RemfilsGroupNotifier_bot \n\nhttps://tmp1.vpereskokov.com/local/studentprofile/?tag=Td4dWIybHA7krcJeZhza6OTeIORMj1NS%\
2BIM%3D","entities":[{"offset":0,"length":33,"type":"bot_command"},{"offset":36,"length":95,"type":"url"}],"link_preview_options":{"is_disabled":tr\
ue}}}
*/
