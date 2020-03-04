<?php

/**
 * Class HttpResponse
 *
 * Класс для хранения HTTP-ответа 
 */
class HttpResponse {

    private $statusCode;
    private $data;

    public function __construct( $statusCode, $data ) {
        $this->statusCode = $statusCode;
        $this->data = $data;
    }

    public function getStatusCode() {
        return $this->statusCode;
    }

    public function getData() {
        return $this->data;
    }
}

/**
 * Trait CurlHttpClient
 *
 * Трейт для отправки GET и POST запросов с помощью cURL
 * Для корректной работы перед отправкой запроса
 * проводится конфигурация общих параметров сеанса cURL 
 */
trait CurlHttpClient {

    /**
     * @var array Список параметров сеанса cURL
     */
    private $curlCommonOptions;

    /**
     * Задаёт конфигурацию cURL для конкретного метода запроса HTTP 
     * 
     * @param string $method метод запроса HTTP
     * @param array $options массив с cURL-параметрами
     * @throws Exception
     */
    public function configCurlOptions( string $method, array $options ) {
       
        try {
            if ( ( $method !== 'GET' ) && ( $method !== 'POST' ) ) {
                throw new Exception( "CurlHttpClient: can't perform '{$method}' method\n" );
            }
        }
        catch ( Exception $err ) {
            die( $err->getMessage() );
        }
        
        $this->curlCommonOptions = $options;

        if ( $method === 'POST' ) {
            $this->curlCommonOptions[ CURLOPT_POST ] = true;
            $this->curlCommonOptions[ CURLOPT_HTTPHEADER ] = array('Content-Type: application/json');
        }
    }

    /**
     * Посылает HTTP-запрос 
     * 
     * @param string $url адрес запроса
     * @param string|array $data данные запроса
     * @return HttpResponse ответ на посланный HTTP запрос
     */
    public function sendRequest( string $url, $data ) {

        $curl = curl_init();
        
        curl_setopt_array( $curl, $this->curlCommonOptions );

        if ( isset( $this->curlCommonOptions[ CURLOPT_POST ] ) ) {
            curl_setopt( $curl, CURLOPT_URL, $url );

            curl_setopt( $curl, CURLOPT_POSTFIELDS, json_encode( $data ) );
        }
        else {
            curl_setopt( $curl, CURLOPT_URL, $url . '?' . $data );
        }

        $response_data = curl_exec( $curl );
        $response_code = curl_getinfo( $curl, CURLINFO_RESPONSE_CODE );

        curl_close( $curl );

        return new HttpResponse( $response_code, json_decode( $response_data, true ) );
    }
}

?>