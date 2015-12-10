<?php
class JSONOutput {
    // decide come serializzare i dati in json
    public static function json(array $a_data) {
        // @see JSON_UNESCAPED_UNICODE
        $encode_opt = JSON_UNESCAPED_UNICODE;
        if ( DEBUG ) {
            $encode_opt |= JSON_PRETTY_PRINT;
        }
        $json = json_encode($a_data, $encode_opt);
        if( empty($json) ) {
            $msg = sprintf('Errore JSON %s ',  self::lastJsonErrorStr() );
            throw new Exception($msg);
        }
        return $json;
    }

    // i caratteri fuori dal reange ASCII potrebbero causare errore JSON_ERROR_UTF8
    // una possibile soluzione consiste nel togliere tutto
    public static function rmNonASCII($str){
        $res = preg_replace('/[^\x20-\x7E]/','', $str);
        return $res;
    }


    // str errore
    public static function lastJsonErrorStr(){
        $msg='';
        switch (json_last_error()) {
        case JSON_ERROR_NONE:
            $msg='';
            break;
        case JSON_ERROR_DEPTH:
            $msg=' - Maximum stack depth exceeded';
            break;
        case JSON_ERROR_STATE_MISMATCH:
            $msg=' - Underflow or the modes mismatch';
            break;
        case JSON_ERROR_CTRL_CHAR:
            $msg=' - Unexpected control character found';
            break;
        case JSON_ERROR_SYNTAX:
            $msg=' - Syntax error, malformed JSON';
            break;
        case JSON_ERROR_UTF8:
            // con questo errore usare utf8_encode() e rmNonASCII()
            $msg=' - Malformed UTF-8 characters, possibly incorrectly encoded (do utf8_encode($data) )';
            break;
        default:
            $msg=' - Unknown error';
            break;
        }

        return $msg;
    }

    public static function println($msg) {
        echo sprintf('//%s: %s ' . "\n", date('H:i:s'), $msg);
    }
}
