<?php

// @see https://github.com/Respect/Validation
class Validate {

    function isEmail($str) {
        //preg_match("~([a-zA-Z0-9!#$%&amp;'*+-/=?^_`{|}~])@([a-zA-Z0-9-]).([a-zA-Z0-9]{2,4})~",$str);
        return filter_var($str, FILTER_VALIDATE_EMAIL);
    }

    // Controlla i caratteri alfanumerici
    static function isAlnum($str) {
        return ctype_alnum($str);
    }

    // Controlla i caratteri alfabetici
    static function isAlpha($str) {
        return ctype_alpha($str);
    }

    // Controlla i caratteri di controllo
    static function isCntrl($str) {
        return ctype_cntrl($str);
    }

    // Controlla i caratteri numerici
    static function isDigit($str) {
        return ctype_digit($str);
    }

    // Controlla ogni carattere stampabile tranne lo spazio
    static function isGraph($str) {
        return ctype_graph($str);
    }

    // Controlla i caratteri minuscoli
    static function isLower($str) {
        return ctype_lower($str);
    }

    // Controlla i caratteri stampabili
    static function isPrint($str) {
        return ctype_print($str);
    }

    // Controlla ogni carattere stampabile che non è uno spazio o un carattere alfanumerico
    static function isPunct($str) {
        return ctype_punct($str);
    }

    // Controlla gli spazi
    static function isSpace($str) {
        return ctype_space($str);
    }

    // Controlla i caratteri maiuscoli
    static function isUpper($str) {
        return ctype_upper($str);
    }

    // Controlla i caratteri che rappresentano una cifra esadecimale
    static function isXdigit($str) {
        return ctype_xdigit($str);
    }

    // whitelist
    // controlla che sia in un range di valori accettati
    static function isInSet($str, array $a_set) {
        return in_array($str, $a_set);
    }

    // un intero entro i limiti previsti
    public static function is_int($int, $max = PHP_INT_MAX, $min = 0) {
        return filter_var($int, FILTER_VALIDATE_INT, ["min_range" => $min, "max_range" => $max]);
    }

    // convertono a true: 1 "1" "yes" "true" "on" TRUE
    // convertono a false: 0 "0" "no" "false" "off" "" NULL FALSE
    public static function is_bool($bool) {
        return filter_var($bool, FILTER_VALIDATE_BOOLEAN);
    }

    //
    public static function is_float($float, $max = 1, $min = 0) {
        return filter_var($float, FILTER_VALIDATE_FLOAT);
    }

    //
    public static function isUrl($url) {
        // flag di interesse:
        //     FILTER_FLAG_SCHEME_REQUIRED
        //     FILTER_FLAG_HOST_REQUIRED
        //     FILTER_FLAG_PATH_REQUIRED
        //     FILTER_FLAG_QUERY_REQUIRED
        return filter_var($url, FILTER_VALIDATE_URL) !== FALSE;
    }

    //
    public static function isIP($ip) {
        // interessante il flag FILTER_FLAG_NO_PRIV_RANGE, per determinare se non è pubblico
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== FALSE;
    }

    // Y-d-m
    public static function isDateISO($date) {
        //match the format of the date
        if (preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', $date, $parts)) {
            //check weather the date is valid of not
            if (checkdate($parts[2], $parts[3], $parts[1])) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    // Y-d-m
    public static function isDateIt($date) {
        // match the format of the date
        if (preg_match('/^([0-9]{2})-([0-9]{2})-([0-9]{4})$/', $date, $parts)) {
            //check weather the date is valid of not
            if (checkdate($parts[2], $parts[3], $parts[1])) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

}
