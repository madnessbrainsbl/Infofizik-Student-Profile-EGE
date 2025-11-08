<?php

function generate_telegram_bot_profile_stat_message($foundUser, $foundCourse)
{
    $stat = new StudentProfileStatManager($foundUser, $foundCourse);

    /****************************************************************************************************
     * basic stats
     ****************************************************************************************************/

    ###############$userName = $foundUser->lastname . ' ' . $foundUser->firstname;

    /****************************************************************************************************
     * variables 
     ****************************************************************************************************/

    /* Имя Пользователя */
    $userName =   $foundUser->firstname;
    /* Количество еженедельных вариантов, (с учетом долга), которые необходимо выполнить до воскресения 23:59 мск */
    $weeklyVariantCounts = $stat->getWeeklyVariantCount(); // TODO: use this

    $numbers = [];
    foreach ($stat->getNotCompletedOnThisWeekQuizes() as $quiz)
    {
        $numbers[] = $quiz->test_number;
    }
    
    $taskNotCompleted = '';

    $message = "Привет, $userName! 


";

    if (count($numbers) > 0) {
        $tasksTodo = implode(', ', $numbers);
        $message .= "Вот, что предстоит выполнить на этой неделе: 

$tasksTodo

";
    }

    $numbers = [];
    foreach ($stat->getNotCompletedOnPrevWeeksQuizes() as $quiz)
    {
        $numbers[] = $quiz->test_number;
    }

    if (count($numbers) > 0) {
        $taskNotCompleted = implode(', ', $numbers);
         
        $message .= "Не забудь сделать долги: 

$taskNotCompleted

";
    }

    $message .= "Более подробную информацию можно посмотреть по ссылке на индивидуальную статистику в закрёпленном сообщении";

    return $message;
}
