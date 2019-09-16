<?php
class JSONOutput {
    // decide come serializzare i dati in json
    public static function json(array $a_data) {
        // @see JSON_UNESCAPED_UNICODE
        $encode_opt = JSON_UNESCAPED_UNICODE;
        if (DEBUG) {
            $encode_opt |= JSON_PRETTY_PRINT;
        }
        $a_data = self::to_UTF8($a_data);
        $json = json_encode($a_data, $encode_opt);
        // if( json_last_error() ) {
        if (empty($json)) {
            $msg = sprintf('Errore JSON %s ', self::lastJsonErrorStr());
            throw new Exception($msg);
        }
        return $json;
    }
    // encode alla data
    static function to_UTF8($data) {
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = self::to_UTF8($v);
            }
        } else if (is_string($data)) {
            return utf8_encode($data);
        }
        return $data;
    }
    // i caratteri fuori dal reange ASCII potrebbero causare errore JSON_ERROR_UTF8
    // una possibile soluzione consiste nel togliere tutto
    public static function rmNonASCII($str) {
        $res = preg_replace('/[^\x20-\x7E]/', '', $str);
        return $res;
    }
    // decode JSON error
    public static function lastJsonErrorStr($json_error_num) {
        $json_error_num = json_last_error();
        switch ($json_error_num) {
        case JSON_ERROR_NONE:
            $str = 'No errors';
            break;
        case JSON_ERROR_DEPTH:
            $str = 'Maximum stack depth exceeded';
            break;
        case JSON_ERROR_STATE_MISMATCH:
            $str = 'Underflow or the modes mismatch';
            break;
        case JSON_ERROR_CTRL_CHAR:
            $str = 'Unexpected control character found';
            break;
        case JSON_ERROR_SYNTAX:
            $str = 'Syntax error, malformed JSON';
            break;
        case JSON_ERROR_UTF8:
            $str = 'Malformed UTF-8 characters, possibly incorrectly encoded';
            break;
        default:
            $str = 'Unknown error';
            break;
        }
        return $str;
    }

    function _json_check_error(): void {
        if (\json_last_error() !== \JSON_ERROR_NONE) {
            throw new JSONException(\json_last_error_msg(), \json_last_error());
        }
    }

    public static function println($msg) {
        echo sprintf('//%s: %s ' . "\n", date('H:i:s'), $msg);
    }
}

class JSONException extends \Exception {}
//  run the tests:
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/Test.php';
}