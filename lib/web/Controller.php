<?php
/* usage:
class AppController extends BaseAPIController {
    // esempio azione
    function loginAction() {
        return $this->respond($isOK = true, $message='ok');
    }
}
ok('/mobile/login', 'loginAction');
$controller = new XxxController();
die( $controller->run($uri = $_SERVER['REQUEST_URI']) );
*/
// funzione: controller con nomenclatura simile a ZF
class BaseAPIController {
    public function run($uri) {
        self::setOnException();
        $method = self::resolveToControllerMethod($uri);
        if (method_exists($this, $method)) {
            return $this->$action();
        } else {
            return "unimplemented action:$method \n";
        }
    }
    // logica di composizioe della action
    public static function resolveToControllerMethod($uri) {
        $action = self::getAction($uri);
        if (empty($action)) {
            return '';
        }
        if( empty($action) ) {
            $action = 'index';
        }
        $action .= 'Action'; // /mobile/login => loginAction
        return $action;
    }
    // riscrivere con lo schema di url dell'applicazione corrente
    public static function getAction($uri) {
        $action = str_replace([ '/mobile/', '/'], '', $uri);
        // toglie caratteri non alfanumerici
        $action = preg_replace('/[^a-zA-Z0-9_]/', '_', $action);
        // limite alla lunghezza, previene possibili attacchi
        if (strlen($action) > 100) {
            die(__CLASS__.' action max 100 char');
        }
        return $action ;
    }
    //----------------------------------------------------------------------------
    //  validazione time && KEY
    //----------------------------------------------------------------------------
    const TIME_DELTA_MAX = 30;
    // metodo unico di tes del time inviato
    public static function checkTime($client_time) {
        $server_time   = time();
        $delta         = (int) $server_time - $client_time;
        $time_is_valid = $delta >= 0 && $delta <= self::TIME_DELTA_MAX ;
        return [ $server_time, $delta, $time_is_valid ];
    }
    // controlla che il time inviato sia recente
    // valida la hash che vien einviata
    protected function checkAPIKey() {
        $client_id = $this->getRequest()->getParam('client_id', '');
        // recuperare la API KEY assegnata ad un cliente
        $client_key = self::getKey($client_id);
        if( empty($client_key) ) {
            return $this->respond(APICodes::BED_KEY, "client_id dont have associed API KEY", $err=[], $data=[ ] );
        }
        $client_time = $this->getRequest()->getParam('time', '');
        $client_hash = $this->getRequest()->getParam('hash', '');
        // Only numbers
        if( !ctype_digit($client_id) ) {
            return $this->respond(APICodes::BED_PARAMETER, __FUNCTION__." invalid client_id", $err=[], $data=[] );
        }
        // Only numbers
        if( !ctype_digit($client_time) ) {
            return $this->respond(APICodes::BED_PARAMETER, __FUNCTION__." invalid time", $err=[], $data=[] );
        }
        // ctype_alnum() Numbers or letters
        if( !ctype_print($client_hash) ) {
            return $this->respond(APICodes::BED_PARAMETER, __FUNCTION__." invalid hash", $err=[], $data=[] );
        }
        if( empty($client_id) ) {
            return $this->respond(APICodes::BED_PARAMETER, __FUNCTION__." empty client_id", $err=[], $data=[] );
        }
        if( empty($client_time) ) {
            return $this->respond(APICodes::BED_PARAMETER, __FUNCTION__." empty time", $err=[], $data=[] );
        }
        if( empty($client_hash) ) {
            return $this->respond(APICodes::BED_PARAMETER, __FUNCTION__." empty hash", $err=[], $data=[] );
        }
        list($server_time, $delta, $time_is_valid) = self::checkTime($client_time);
        if( !$time_is_valid ) {
            return $this->respond(APICodes::BED_PARAMETER, __FUNCTION__." time not valid", $err=[], $data=[] );
        }
        $my_hash = md5( $client_key.$client_time );
        if( $my_hash != $client_hash ) {
            return $this->respond(APICodes::BAD_AUTH_REQUEST, __FUNCTION__." hash is not matching", $err=[], $data=[ ] );
        }
    }
    // recupera la API KEY assegnata ad un cliente
    public static function getKey($client_id) {
        $a_client_key = [ ];
        if( isset($a_client_key[$client_id]) ) {
            return $a_client_key[$client_id];
        }
    }
    //----------------------------------------------------------------------------
    // response
    //----------------------------------------------------------------------------
    const SIGN = __DIR__;
    // implementa una risposta standard
    protected function respond($isOK, $message = '', array $data = [], array $errors = []) {
        if( !is_bool($isOK) ) {
            $isOK = false;
            $http_code = $isOK;
            http_response_code( $http_code );
        }
        // compose data
        $r = [
            'response' => ($isOK ? 'ok' : 'ko'),
            'message' => $message,
            'errors' => $errors,
            'data' => []
        ];
        $add_dbg = function( array $r) {
            if( Bootstrap::isEnvDev() ) {
                $r = array_merge($r, [
                    'memory' => round(memory_get_peak_usage() / (1024 * 1024), 2) . 'MB',
                    'exec_time' => number_format( microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'] , 4, '.', ''),
                    ]);
            }
            return $r;
        };
        $add_sign = function( array $r) {
            $r['checksum'] = md5(implode('', array_keys($r['data'])) . implode('', array_values($r['data'])));
            $r['sign'] = md5(self::SIGN . $r['checksum']);
            return $r;
        };
        if (!empty($data)) {
            if (is_array($data)) {
                $r['data'] = $data;
            } elseif (is_string($data)) {
                // altrimenti il successivo encode quota come stringa i dati e non vengono riconosciuti lato client
                ini_set('memory_limit', -1);
                $r['data'] = json_decode($data);
            }
        }
        $r = $add_sign($r);
        $r = $add_dbg($r);
        // encode
        $json = json_encode($r, ($isOK ? null : JSON_PRETTY_PRINT) );
        if( json_last_error() ){
            die( 'JSON error: '.json_last_error() );
        }
        // log
        $this->logRequest($isOK, $message, $errors);
        // send
        header('Content-Type: application/json');
        die($json);
    }
    protected function logRequest($isOK, $message, array $errors){
        // logging
        $log_msg =  sprintf('%s %s action:%s msg:"%s" errors:%s params:%s %s'.PHP_EOL,
            date('Y-m-d H:i:s'),
            $isOK ? 'OK' : 'KO',
            $action=$this->getRequest()->getActionName(),
            $message,
            json_encode($errors),
            $request = substr(json_encode(array_merge($_GET,$_POST)),0,2000),
            substr($_SERVER['REQUEST_METHOD'],0,1)
        );
        $log_path = __DIR__.'/../var/logs/api_requests_'.date('my').'.log';
        file_put_contents($log_path, $log_msg, (FILE_APPEND | LOCK_EX));
    }
    // rispode negativamente ma fa aspettare la risposta arbitrariamente per diminuire possibilità di attacco brute force
    // todo: slow an IP address with multiple failed logins(same username or same IP). loggare i tetativi falliti, data, IP, username in una banlist
    // todo: permettere un solo tentativo per volta, bloccare il metodo se c'è login concorrente
    // sintomi di attacco:
    // - Many failed logins from the same IP address
    // - Logins with multiple usernames from the same IP address
    // - Logins for a single account coming from many different IP addresses
    protected function respondAndWait($message, $username, $IP) {
        $number_failed_retry = 1;
        sleep(7 * $number_failed_retry); // seconds,
        //header('HTTP/1.0 401 Unauthorized');
        return $this->respond($isOK = false, $message);
    }
    public static function setOnException() {
        // tutte le eccezioni non esplicitamente gestite in questo modulo devono
        // essere riportate al client e inviate per email
        $prev_exception_handler = set_exception_handler( function($exception) {
            // report by mail
            // if ( \Bootstrap::isEnvProd() ) {
            //     \Bootstrap::_ExcpLoggerMail($exception);
            // }
            // log exception
            // $this->log_error(__METHOD__, 'error: exception', $_POST, [
            //     'Message'       => $exception->getMessage(),
            //     'File'          => $exception->getFile(),
            //     'Line'          => $exception->getLine(),
            //     'TraceAsString' => $exception->getTraceAsString()
            // ] );
            // messaggio al client, il minimo necessario a ritrovare i dettagli dell'eccezione
            $msg = sprintf("Remote Exception:'%s' line:%s file:%s ", $exception->getMessage(), $exception->getLine(), basename( $exception->getFile() ) );
            return $this->respond($isOK = false, $msg);
        } );
    }
}
/*
mantenere sincronizzato con la documentazione
+--------+-----------------------------------------------------------------------+
| Status | Significato                                                           |
+================================================================================+
| 400    | Bad input parameter. Error message should indicate which one and why. |
+--------+-----------------------------------------------------------------------+
| 401    | Bad or expired KEY. To fix it, you should contact the administrator.  |
+--------+-----------------------------------------------------------------------+
| 403    | Bad Auth request (wrong key, expired timestamp...)                    |
+--------+-----------------------------------------------------------------------+
| 404    | Resource not found at the specified path.                             |
+--------+-----------------------------------------------------------------------+
| 405    | Request method not expected (generally should be GET or POST).        |
+--------+-----------------------------------------------------------------------+
| 429    | Your app is making too many requests and is being rate limited.       |
+--------+-----------------------------------------------------------------------+
| 500    | Server can't respond. Contact the maintainence team.                  |
+--------+-----------------------------------------------------------------------+
| 503    | This usually means your app is being rate limited.                    |
+--------+-----------------------------------------------------------------------+
*/
class APICodes   {
    const OK = 200;
    // client errors
    const BED_PARAMETER = 400;
    const BED_KEY = 401;
    const BAD_AUTH_REQUEST = 403;
    const NOT_FOUND = 404;
    const METHOD_NOT_ALLOWED = 405;
    const TOO_MANY_REQUESTS = 429;
    // server errors
    const INTERNAL_SERVER_ERROR = 500;
    const SERVICE_UNAVAILABLE = 503; // rate limitint the user
}
