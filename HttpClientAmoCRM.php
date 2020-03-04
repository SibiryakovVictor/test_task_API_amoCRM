<?php
spl_autoload_register( function( $className ) {
    require_once( "./$className.php" );
} );

/**
 * class HttpClientAmoCRM
 *
 * Класс для отправки GET и POST запросов к API amoCRM
 */
class HttpClientAmoCRM {

    /**
     * @var string Полный домен amoCRM с именем пользователя
     */
    private $domain;

    use CurlHttpClient {
        configCurlOptions as private;
        sendRequest as private;
    }

    /**
     * Создаёт объект HttpClientAmoCRM, задаёт конфигурацию CurlHttpClient
     * 
     * @param string $subdomain имя пользователя для получения полного домена
     * @param string $method метод запроса HTTP
     */
    public function __construct( string $subdomain, string $method ) {

        if ( ! defined( "AMOCRM_DEFAULT_OPTIONS" ) ) {
            define( "AMOCRM_DEFAULT_OPTIONS", [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERAGENT => 'amoCRM-API-client/1.0',
                CURLOPT_HEADER => false,
                CURLOPT_COOKIEFILE => dirname(__FILE__).'/cookie.txt',
                CURLOPT_COOKIEJAR => dirname(__FILE__).'/cookie.txt',
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_SSL_VERIFYHOST => 0
            ] );
        }

        $this->configCurlOptions( $method, AMOCRM_DEFAULT_OPTIONS );

        $this->domain = 'https://' . $subdomain . '.amocrm.ru';        
    }

    /**
     * Посылает HTTP-запрос 
     * 
     * @param string $path путь требуемого API метода
     * @param string|array $data данные требуемого API метода
     * @return HttpResponse ответ API метода на запрос
     */
    public function request( string $path, $data ) {

        $fullUrl = $this->domain . $path;

        $httpResponse = $this->sendRequest( $fullUrl, $data );
        
        return $httpResponse;
    }
}


?>