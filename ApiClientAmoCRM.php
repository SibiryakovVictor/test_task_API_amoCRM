<?php
spl_autoload_register( function( $className ) {
    require_once( "./$className.php" );
} );


/**
 * class ApiClientAmoCRM
 *
 * Класс, реализующий API методы amoCRM
 */
class ApiClientAmoCRM {

    /**
     * @var array Массив с ключами USER_LOGIN и USER_HASH для авторизации
     */
    private $userData;

    /**
     * @var array Логин пользователя для составления полного домена и отправки API методов
     */
    private $subdomain;

    /**
     * Адреса для соответствующих API-методов
     */
    const AUTH_ADDRESS = "/private/api/auth.php?type=json";
    const LEADS_ADDRESS = "/api/v2/leads";
    const TASKS_ADDRESS = "/api/v2/tasks";

    /**
     * Создаёт объект ApiClientAmoCRM
     * Проводит авторизацию пользователя для работы API методов
     * 
     * @param string $userEmail Электронная почта пользователя
     * @param string $userHashApi Ключ для доступа к API из профиля пользователя
     */
    public function __construct( string $userEmail, string $userHashApi ) {

        $this->checkUserDataCorrect( $userEmail, $userHashApi );

        $this->checkUserEmailCorrect( $userEmail );

        $this->userData[ 'USER_LOGIN' ] = $userEmail;

        $this->userData[ 'USER_HASH' ] = $userHashApi;

        $this->subdomain = strtok( $userEmail, '@' );

        $this->authorize();
    }

    /**
     * Отправляет API-запрос:
     * GET /api/v2/leads с параметром filter/tasks
     * 
     * @link https://www.amocrm.ru/developers/content/api/leads
     * @param int $filterCode Выбор соответствующих сделок
     * @return HttpResponse Объект ответа amoCRM API
     * @throws Exception
     */
    public function leadsFilterTasks( int $filterCode ) {

        static $parameter = 'filter/tasks'; 
        static $urlParameter = 'filter[tasks]';

        $this->checkInputCorrect( $parameter, $filterCode );

        $httpClient = new HttpClientAmoCRM( $this->subdomain, 'GET' );

        $response = $httpClient->request( self::LEADS_ADDRESS, $urlParameter . "=" . $filterCode );

        $this->checkRequestSuccessful( $response );

        return $response;
    }

    /**
     * Отправляет API-запрос:
     * POST /api/v2/tasks с параметром add
     * Добавляет задачи
     * 
     * @link https://www.amocrm.ru/developers/content/api/tasks
     * @param array $taskData Массив с ключом 'add', который содержит массив задач
     * @return HttpResponse Объект ответа amoCRM API
     * @throws Exception
     */
    public function tasksAdd( array $taskData ) {

        foreach ( $taskData[ 'add' ] as $task ) {
            $this->checkTaskFields( $task );

            foreach ( $task as $field => $value ) {
                $this->checkInputCorrect( 'task/add/' . $field, $value );
            }
        }

        $httpClient = new HttpClientAmoCRM( $this->subdomain, 'POST' );

        $response = $httpClient->request( self::TASKS_ADDRESS, $taskData );

        $this->checkRequestSuccessful( $response );

        return $response;
    }

    /**
     * Отправляет API-запрос:
     * GET /api/v2/leads с параметром id
     * Возвращает сделку по id
     * 
     * @link https://www.amocrm.ru/developers/content/api/leads
     * @param int $id id искомой сделки
     * @return HttpResponse Объект ответа amoCRM API
     * @throws Exception
     */
    public function leadsGetById( int $id ) {

        static $parameter = 'id'; 
        static $urlParameter = 'id';

        $this->checkInputCorrect( $parameter, $id );

        $httpClient = new HttpClientAmoCRM( $this->subdomain, 'GET' );

        $response = $httpClient->request( self::LEADS_ADDRESS, $urlParameter . "=" . $id );

        $this->checkRequestSuccessful( $response );

        return $response;
    }    

    /**
     * Отправляет API-запрос:
     * GET /api/v2/tasks с параметром element_id
     * Возвращает задачи по element_id
     * 
     * @link https://www.amocrm.ru/developers/content/api/tasks
     * @param int $id id искомой задчи
     * @return HttpResponse Объект ответа amoCRM API
     * @throws Exception
     */
    public function tasksGetByElementId( int $elementId ) {

        static $parameter = 'element_id'; 
        static $urlParameter = 'element_id';

        $this->checkInputCorrect( $parameter, $elementId );

        $httpClient = new HttpClientAmoCRM( $this->subdomain, 'GET' );

        $response = $httpClient->request( self::TASKS_ADDRESS, $urlParameter . "=" . $elementId );

        $this->checkRequestSuccessful( $response );

        return $response;
    }

    /**
     * Получает id сделок из следующего массива сделок, 
     * полученного ответом от API в объекте HttpResponse:
     * response->getData()[ '_embedded' ][ 'items' ]
     * 
     * @link https://www.amocrm.ru/developers/content/api/leads
     * @param array $leads Массив задач, описанный выше
     * @return array Массив с id сделок
     * @throws Exception
     */
    public function leadsParseId( array $leads ) {

        $this->checkLeadsArrayCorrect( $leads, 'id' );

        $idList = array();

        foreach ( $leads as $lead ) {
            $idList[] = $lead[ 'id' ];
        }

        return $idList;
    }

    /**
     * Получает имена сделок (значение поля name) из следующего массива сделок, 
     * полученного ответом от API в объекте HttpResponse:
     * response->getData()[ '_embedded' ][ 'items' ]
     * 
     * @link https://www.amocrm.ru/developers/content/api/leads
     * @param array $leads Массив задач, описанный выше
     * @return array Массив с именами сделок
     * @throws Exception
     */
    public function leadsParseName( array $leads ) {

        $this->checkLeadsArrayCorrect( $leads, 'name' );

        $nameList = array();

        foreach ( $leads as $lead ) {
            $nameList[] = $lead[ 'name' ];
        }

        return $nameList;
    }

    /**
     * Получает текст задач (значение поля text) из следующего массива задач, 
     * полученного ответом от API в объекте HttpResponse:
     * response->getData()[ '_embedded' ][ 'items' ]
     * 
     * @link https://www.amocrm.ru/developers/content/api/leads
     * @param array $leads Массив задач, описанный выше
     * @return array Массив с именами сделок
     * @throws Exception
     */
    public function tasksParseText( array $tasks ) {

        $this->checkLeadsArrayCorrect( $tasks, 'text' );

        $textList = array();

        foreach ( $tasks as $task ) {
            $textList[] = $task[ 'text' ];
        }

        return $textList;
    }

    /**
     * Проводит авторизацию пользователя
     * 
     * @link https://www.amocrm.ru/developers/content/oauth/old
     * @throws Exception
     */
    private function authorize() {

        $httpClient = new HttpClientAmoCRM( $this->subdomain, 'POST' );

        $response = $httpClient->request( self::AUTH_ADDRESS, $this->userData );

        $this->checkAuthSuccessful( $response );
    }


    /**
     * Проверяет массив на соответствие с требуемым массивом методов tasksParse
     * 
     * @throws Exception
     */
    private function checkTasksArrayCorrect( array $tasks, string $parameter ) {

        try {
            if ( ! isset( $tasks ) || empty( $tasks ) ) {
                throw new Exception(
                    "from: ApiClientAmoCRM->tasksParse->checkTasksArrayCorrect\n" .
                    "Передан некорректный массив задач\n"
                );
            }
        }
        catch ( Exception $err ) {
            die( $err->getMessage() );
        }

        foreach ( $tasks as $task ) {
            $this->checkInputCorrect( 'tasks/' . $parameter, $task[ 'text' ] );
        }

    } 

    /**
     * Проверяет массив на соответствие с требуемым массивом методов leadsParse
     * 
     * @throws Exception
     */
    private function checkLeadsArrayCorrect( array $leads, string $parameter ) {

        try {
            if ( ! isset( $leads ) || empty( $leads ) ) {
                throw new Exception(
                    "from: ApiClientAmoCRM->leadsParse->checkLeadsArrayCorrect\n" .
                    "Передан некорректный массив сделок\n"
                );
            }
        }
        catch ( Exception $err ) {
            die( $err->getMessage() );
        }

        foreach ( $leads as $lead ) {
            $this->checkInputCorrect( 'task/add/' . $parameter, $lead[ 'id' ] );
        }

    } 

    /**
     * Проверяет переданные пользователем данные
     * 
     * @throws Exception
     */
    private function checkUserDataCorrect( $userEmail, $userHashApi ) {

        try {
            if ( ! $userEmail || ! $userHashApi ) {

                new Exception(
                    "from: ApiClientAmoCRM->checkUserDataCorrect\n" .
                    "Отсутствует почта пользователя или хэш для доступа к API\n"
                );

            }
        }
        catch( Exception $error ) {
            die( $error->getMessage() );
        }

    } 

    /**
     * Проверяет корректность переданной электронной почты
     * 
     * @throws Exception
     */
    private function checkUserEmailCorrect( $userEmail ) {

        try {
            if ( strpos( $userEmail, '@' ) === false ) {

                new Exception(
                    "from: ApiClientAmoCRM->checkUserEmailCorrect\n" . 
                    "Введенный адрес электронной почты некорректен: $userEmail\n"
                );

            }
        }
        catch( Exception $error ) {
            die( $error->getMessage() );
        }

    }

    /**
     * Проверяет успешность авторизации
     * 
     * @throws Exception
     */
    private function checkAuthSuccessful( $responseData ) {

        try {

            if ( ( ! $responseData->getData()[ 'response' ][ 'auth' ] ) && 
            ( $responseData->getStatusCode() !== 200 ) && ( $responseData->getStatusCode() !== 204 ) ) {

                throw new Exception(
                    "from: ApiClientAmoCRM->checkAuthSuccessful\n" .
                    ( $responseData->getData()[ 'response' ][ 'error' ] ?? 
                    "Авторизация не удалась." ),
                    ( $responseData->getData()[ 'response' ][ 'error_code' ] ?? $responseData->getStatusCode() ) 
                );
            }
        }
        catch ( Exception $error ) {
            die( $error->getMessage() . "\nКод ошибки: " . $error->getCode() );
        }
    }

    /**
     * Проверяет успешность отправки запроса
     * 
     * @throws Exception
     */
    private function checkRequestSuccessful( $responseData ) {

        try {

            if ( ( $responseData->getStatusCode() !== 200 ) && ( $responseData->getStatusCode() !== 204 ) ) {

                print_r( $responseData->getData() );

                throw new Exception(
                    "from: ApiClientAmoCRM->checkRequestSuccessful\n" .
                    ( $responseData->getData()[ 'response' ][ 'error' ] ?? 
                    "Запрос не удался." ),
                    ( $responseData->getData()[ 'response' ][ 'error_code' ] ?? $responseData->getStatusCode() ) 
                );
            }
        }
        catch ( Exception $error ) {
            die( $error->getMessage() . "\nКод ошибки: " . $error->getCode() );
        }
    }

    /**
     * Проверяет корректность переданных параметров соответствующим API методам
     * 
     * @throws Exception
     */
    private function checkInputCorrect( string $parameter, $value ) {

        if ( ! defined( "PERMISSIBLE_VALUES" ) ) {
            define( "PERMISSIBLE_VALUES", [
                'filter/tasks' => [ 'value' => [ 1, 2 ] ],
                'task/add/element_id' => [ 'type' => 'integer', 'value' => 'any' ],
                'task/add/element_type' => [ 'type' => 'integer', 'value' => [ 1, 2, 3, 12 ] ],
                'task/add/task_type' => [ 'type' => 'integer', 'value' => [1, 2, 3 ] ],
                'task/add/text' => [ 'type' => 'string', 'value' => 'any' ],
                'task/add/responsible_user_id' => [ 'type' => 'integer', 'value' => 'any' ],
                'leads/id' => [ 'type' => 'integer', 'value' => 'any' ],
                'leads/name' => [ 'type' => 'string', 'value' => 'any' ],
                'tasks/text' => [ 'type' => 'string', 'value' => 'any' ]
            ] );
        }

        if ( ! isset( PERMISSIBLE_VALUES[ $parameter ] ) ) {
            return;
        }

        if ( isset( PERMISSIBLE_VALUES[ $parameter ][ 'type' ] ) ) {

            $type = PERMISSIBLE_VALUES[ $parameter ][ 'type' ];
            $typeValue = gettype( $value );

            try {

                if ( $typeValue !== $type ) {
                    new Exception(
                        "from: ApiClientAmoCRM->checkInputCorrect\n" .
                        "Некорректный тип для параметра '$parameter': $typeValue\n"
                    );
                }
    
            }
            catch ( Exception $error ) {
                die( $error->getMessage() );
            }

        }

        if ( PERMISSIBLE_VALUES[ $parameter ][ 'value' ] === 'any' ) {
            return;
        }

        try {

            if ( array_search( $value, PERMISSIBLE_VALUES[ $parameter ][ 'value' ] ) === false ) {
                new Exception(
                    "from: ApiClientAmoCRM->checkInputCorrect\n" .
                    "Некорректное значение для параметра '$parameter': $value\n"
                );
            }

        }
        catch ( Exception $error ) {
            die( $error->getMessage() );
        }
    }

    /**
     * Проверяет наличие полей в переданном массиве задачи
     * 
     * @throws Exception
     */
    private function checkTaskFields( array $taskData ) {

        $taskFields = [
            'element_id',
            'element_type',
            'task_type',
            'text',
            'responsible_user_id',
            'complete_till_at'
        ];

        foreach ( $taskFields as $field ) {

            if ( ! array_key_exists( $field, $taskData ) ) {
                die(
                    "from: ApiCientAmoCRM\n" .
                    "Массив задачи не содержит обязательного поля '$field'\n"
                );
            }
        }
    }

}


?>