<?php
declare (strict_types = 1); //php7.0+, will throw a catchable exception if call typehints and returns do not match declaration
//
// wrapper su curl come mezzo per fare la chiamata di rete,
// gestisce info di debug
// e espone interfaccia uniforme ai paramatri di input e risultati di output
//
class CURL {
    /**
     * chiamata HTTP POST
     * @return array{0: bool, 1: string, 2: int }
    */
    public static function POST(string $url, array $data, array $opt = [], array $headers = []): array{
        $option = array_merge([
            'debug_info' => false,
            'debug_request_headers' => false,
            'debug_response_headers' => false,
        ], $opt);
        extract($option);
        $ch = curl_init($url);
        //---- options ----------------------------------------
        // @see https://www.php.net/manual/en/function.curl-setopt.php
        curl_setopt($ch, CURLOPT_HEADER, false); // stampa l'header di risposta del server in response, usa curlinfo instead.
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        // default: curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true); // restituisce in curl_getinfo() la chiave CURLINFO_HEADER_OUT:The request string sent
        //----------------------------------------------
        // richiesto dall'API POST in JSON format
        $payload = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        // Set HTTP Header for POST+JSON request
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload),
        ]);
        // set headers
        if (!empty($headers)) {
            // if(false) self::log("using token " . self::$AUTH_RESULT['tokenValue'] . " \n");
            $a_curl_h = [];
            // es. [ 'tokenValue: ' . self::$AUTH_RESULT['tokenValue'] ]
            foreach ($headers as $key => $val) {
                $a_curl_h[] = sprintf('%s: %s', $key, $val);
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $a_curl_h);
        }
        $json = self::logged_exec($ch, $debug_response_headers, $debug_request_headers, $debug_info);
        // get HTTP response code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        // loaded successfully without any redirection or error
        $r = ($http_code >= 200 && $http_code < 300);
        return [$r, $json, $http_code];
    }
    /**
     * chiamata HTTP GET
     * @return array{0: bool, 1: string, 2: int }
    */
    public static function GET($url, array $data, array $opt = [], array $headers = []): array{
        $option = array_merge([
            'debug_info' => false,
            'debug_request_headers' => false,
            'debug_response_headers' => false,
        ], $opt);
        extract($option);
        if (!empty($data)) {
            $url .= '?' . http_build_query($data);
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true); // restituisce in curl_getinfo() la chiave CURLINFO_HEADER_OUT:The request string sent
        // Set HTTP Header for JSON request
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            // 'Content-Length: ' . strlen($payload),
        ]);
        //  setting headers
        if (!empty($headers)) {
            // if(false) self::log("using token " . self::$AUTH_RESULT['tokenValue'] . " \n");
            $a_curl_h = [];
            // es. [ 'tokenValue: ' . self::$AUTH_RESULT['tokenValue'] ]
            foreach ($headers as $key => $val) {
                $a_curl_h[] = sprintf('%s: %s', $key, $val);
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $a_curl_h);
        }
        $json = self::logged_exec($ch, $debug_response_headers, $debug_request_headers, $debug_info);
        //----------------------------------------------------------------------------
        // get HTTP response code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        // loaded successfully without any redirection or error
        $r = ($http_code >= 200 && $http_code < 300);
        return [$r, $json, $http_code];
    }
    /**
    * logger per ispezionare il funzionamento della classe
    * @return void
    */
    public static function log(string $message) {
        // where do you want to log?
        $_log_e = function (string $message) {
            echo $message;
        };
        $_log_f = function (string $message) {
		// configure a file logger
        };
        $_log_e($message);
    }
    // esegue la def con logging headers
    protected static function logged_exec(&$ch, bool $debug_response_headers, bool $debug_request_headers, bool $debug_info) : string{
        // where do you want to log?
        $_log = function (string $message) {self::log($message);};
        //----------------------------------------------
        // capture response headers:
        // this function is called by curl for each header received
        if ($debug_response_headers) {
            $response_headers = [];
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$response_headers) {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) {
                    // ignore invalid headers
                    return $len;
                }
                $k = strtolower(trim($header[0]));
                $response_headers[$k][] = trim($header[1]);
                return $len;
            });
        }
        $json = curl_exec($ch);
        if (false === $json) {
            $err = curl_error($ch);
            $msg = sprintf('Errore CURL %s ', $err);
            throw new Exception($msg);
        }
        //----- debug info -----------------------------------------
        if ($debug_request_headers) {
            $req_info = curl_getinfo($ch);
            if (isset($req_info['request_header'])) {
                $_log("---- begin  request_headers:\n");
                $_log(trim($req_info['request_header']) . "\n");
                $_log("---- end  request_headers \n");
            } else {
                $_log("----  request_headers: empty \n");
            }
        }
        if ($debug_response_headers) {
            $_log("---- begin response_headers:\n");
            foreach ($response_headers as $key => $val) {
                $_log(sprintf("    $key => %s\n", is_scalar($val) ? $val : json_encode($val)));
            }
            $_log("---- end response_headers \n");
        }
        //----------------------------------------------------------------------------
        if ($debug_info) {
            // parametri e info di funzionamento di curl
            $req_info = curl_getinfo($ch);
            $_log("---- begin debug info:\n");
            foreach ($req_info as $key => $val) {
                $_log(sprintf("    $key => %s\n", is_scalar($val) ? $val : json_encode($val)));
            }
            $_log("---- end \n");
        }
        return $json;
    }
    /*
    public function PUT($url, $params) {
    $post_data = '';
    foreach ($params as $k => $v) {
    $post_data .= $k . '=' . $v . '&';
    }
    rtrim($post_data, '&');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_POST, count($post_data));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
    }
    public function DELETE($url, $params) {
    $post_data = '';
    foreach ($params as $k => $v) {
    $post_data .= $k . '=' . $v . '&';
    }
    rtrim($post_data, '&');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_POST, count($post_data));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
    }
     */
    // @see https://github.com/rmccue/Requests
    // permette di ottenre il contenuto della pagina servita ad un indirizzo specifico
    public static function getContent($url, $opts = []) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FAILONERROR, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0)');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        if (preg_match('/^https:\/\//sim', $url) == true) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        // apply opts
        if (is_array($opts) && $opts) {
            foreach ($opts as $key => $val) {
                curl_setopt($ch, $key, $val);
            }
        }
        // transfer
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (FALSE === ($retval = curl_exec($ch))) {
            $err = curl_error($ch);
            $msg = sprintf('Errore CURL %s ', $err);
            throw new Exception($msg);
        } else {
            curl_close($ch);
            return $retval;
        }
    }
    // richiama una url con dati in post
    protected static function post_s($url, array $fields) {
        foreach ($fields as $key => $value) {
            $value = urlencode($value);
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim($fields_string, '&');
        // open connection
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        // transfer
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (FALSE === ($retval = curl_exec($ch))) {
            $err = curl_error($ch);
            $msg = sprintf('Errore CURL %s ', $err);
            throw new Exception($msg);
        } else {
            curl_close($ch);
            return $retval;
        }
    }

}
// if colled directly in CLI, run the tests:
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    include substr(__FILE__, 0, -4) . 'Test.php';
}
