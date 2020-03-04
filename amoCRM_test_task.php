<?php
spl_autoload_register( function( $className ) {
    require_once( "./$className.php" );
} );



$requiredTaskText = 'Сделка без задачи';

$userLogin = 'МЕСТО ДЛЯ ЭЛЕКТРОННОЙ ПОЧТЫ ПОЛЬЗОВАТЕЛЯ'; 
$userHash = 'МЕСТО ДЛЯ КЛЮЧА К API ИЗ ПРОФИЛЯ ПОЛЬЗОВАТЕЛЯ';

$api = new ApiClientAmoCRM(
    $userLogin,
    $userHash
);

//получаю сделки без открытых задач:
$filteredLeads = $api->leadsFilterTasks( 1 );
$filteredLeads = $filteredLeads->getData()[ '_embedded' ][ 'items' ];

//проверяю, есть ли сделки без открытых задач:
if ( ! isset( $filteredLeads ) ) {
    die( "Нет сделок без открытых задач\n" );
}

//получаю список из ID полученных сделок:
$leadsId = $api->leadsParseId( $filteredLeads );

//для каждой сделки создаю задачу с текстом "Сделка без задачи":
foreach ( $leadsId as $id ) {

    $taskData = array(
        'add' => array( [
            'element_id' => $id, 
            'element_type' => 2, 
            'task_type' => 1, 
            'text' => $requiredTaskText,
            'responsible_user_id' => 5885773,
            'complete_till_at' => date( 'U' ) + 3600,
        ] )
    );

    $api->tasksAdd( $taskData );

    //получаю только что добавленную задачу 
    //(будет единственной у сделки, так как изначально брались сделки без задач):
    $taskObject = $api->tasksGetByElementId( $id );

    //определяю текстовое содержание задачи:
    $taskText = $api->tasksParseText( $taskObject->getData()[ '_embedded' ][ 'items' ] );

    //сравниваю с требуемым значением: 
    if ( $taskText[ 0 ] !== $requiredTaskText ) {
        die( "Something's going wrong... :\\" );
    }
}

echo "\n--------------------------------\n";
echo "Задачи с текстом 'Сделка без задачи' добавлены успешно.\n";
    



?>