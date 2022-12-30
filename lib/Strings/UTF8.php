<?php

// utf-8 compatible class
// @see https://github.com/neitanod/forceutf8
class UTF8 {
    //
    // ----- setup php for working with Unicode data -----
    public static function setup() {
        mb_internal_encoding('UTF-8');
        mb_http_output('UTF-8');
        mb_http_input('UTF-8');
        mb_language('uni');
        mb_regex_encoding('UTF-8');
        //
        ini_set('default_charset', 'utf-8');
        ini_set('output_encoding', 'utf-8');
        if (extension_loaded('iconv')) {
            iconv_set_encoding('internal_encoding', 'UTF-8');
        }
    }

    // check str is valid
    // isUTF8($str) {
    function valid($str) {
        return (bool) preg_match('//u', $str);
        // alternative: (utf8_encode(utf8_decode($str)) == $str);
    }

    // safer encode, only if necessary
    // If you apply the PHP function utf8_encode() to an already-UTF8 string it will return a garbled UTF8 string.
    public static function encode($str) {
        if (mb_detect_encoding($str) != 'UTF-8') {
            $str = utf8_encode($str);
        }
        return $str;
    }

    public static function len($ustr) {return mb_strlen($ustr);}
    public static function sub($ustr) {return mb_substr($ustr);}
    public static function replace($ustr) {
        // str_replace works just fine with multibyte strings
        return str_replace($ustr);
    }
    public static function upper($ustr) {return mb_strtoupper($ustr);}
    public static function lower($ustr) {return mb_strtolower($ustr);}

    function str_pad($str, $pad_len, $pad_str = ' ', $dir = STR_PAD_RIGHT) {
        $str_len = mb_strlen($str);
        $pad_str_len = mb_strlen($pad_str);
        if (!$str_len && ($dir == STR_PAD_RIGHT || $dir == STR_PAD_LEFT)) {
            $str_len = 1; // @debug
        }
        if (!$pad_len || !$pad_str_len || $pad_len <= $str_len) {
            return $str;
        }

        $result = null;
        if ($dir == STR_PAD_BOTH) {
            $length = ($pad_len - $str_len) / 2;
            $repeat = ceil($length / $pad_str_len);
            $result = mb_substr(str_repeat($pad_str, $repeat), 0, floor($length))
            . $str
            . mb_substr(str_repeat($pad_str, $repeat), 0, ceil($length));
        } else {
            $repeat = ceil($str_len - $pad_str_len + $pad_len);
            if ($dir == STR_PAD_RIGHT) {
                $result = $str . str_repeat($pad_str, $repeat);
                $result = mb_substr($result, 0, $pad_len);
            } else if ($dir == STR_PAD_LEFT) {
                $result = str_repeat($pad_str, $repeat);
                $result = mb_substr($result, 0,
                    $pad_len - (($str_len - $pad_str_len) + $pad_str_len))
                . $str;
            }
        }

        return $result;
    }
    /*
    $t = STR_PAD_LEFT;
    $s = '...';
    $as = 'AO';
    $ms = 'ÄÖ';
    echo "<pre>\n";
    for ($i = 3; $i <= 1000; $i++) {
    $s1 = str_pad($s, $i, $as, $t); // can not inculde unicode char!!!
    $s2 = str_pad_unicode($s, $i, $ms, $t);
    $l1 = strlen($s1);
    $l2 = mb_strlen($s2);
    echo "len $l1: $s1 \n";
    echo "len $l2: $s2 \n";
    echo "\n";
    if ($l1 != $l2) die("Fail!");
    }
    echo "</pre>";
     */
    // trucate() function
    public static function reminder($str, $maxlen = 50, $suffisso = ' [...] ') {
        if (mb_strlen($str) > $maxlen) {
            $result = '';
            $str = mb_ereg_replace('[[:space:]]+', ' ', $str);
            // per migliorare le prestazioni vado a fare l'explode di una stringa ragionevolmente ridimensionata
            $a = mb_split('[[:space:]]+', mb_substr($str, 0, $maxlen + 10));
            for ($i = 0; $i < count($a); $i++) {
                if (mb_strlen($result . $a[$i] . ' ') < $maxlen) {
                    $result .= $a[$i] . ' ';
                } else {
                    break;
                }
            }
            return mb_trim($result) . ' ' . $suffisso;
        } else {
            return $str;
        }
    }

    // converte caratteri oltre il range ASCII nella rappresentazione html entity
    // function encode_full($str) {
    //     $str = mb_convert_encoding($str , 'UTF-32', 'UTF-8');
    //     $t = unpack("N*", $str);
    //     $t = array_map(function($n) { return "&#$n;"; }, $t);
    //     return implode("", $t);
    // }
    // $str = 'ABCabc àèì +-?= €';
    // $e = 'ABCabc &#224;&#232;&#236; +-?= &#8364;';
    // ok(encode_full($str), $e, 'encode_full: ' . $str);
    function encode_full($str) {
        $str = mb_convert_encoding($str, 'UTF-32', 'UTF-8'); //big endian
        $a_splitted = str_split($str, 4);
        $res = '';
        foreach ($a_splitted as $c) {
            $cur = 0;
            for ($i = 0; $i < 4; $i++) {
                $cur |= ord($c[$i]) << (8 * (3 - $i));
            }
            // 32 = space
            if (($cur < 32 || $cur > 127)) {
                $res .= "&#" . $cur . ";";
            } else {
                $res .= chr($cur); // ok char
            }
        }
        return $res;
    }

    // caratteri accentati convertiti nella lettera non accentata più vicina
    // es. à => a
    /*
    test:
    $utf8 = '€' . 'ÄÖÜ'; // file must be UTF-8 encoded
    // $iso88591[] = utf8_decode($utf8);
    $iso88591[] = iconv('UTF-8', 'ASCII//TRANSLIT', $utf8);
    die(
    implode( PHP_EOL, $iso88591 )
    );
     */
    public static function transliterate($str) {
        if (!function_exists('iconv')) {
            die('installare iconv');
        }
        $str = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        return $str;
    }
    // pure php implementation
    public static function transliterate_php($str) {
        $a = ['À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ'];
        $b = ['A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o'];
        return str_replace($a, $b, $str);
    }

    public static function rm_accented($text) {
        $chars = [
            'ç' => 'c', 'æ' => 'ae', 'œ' => 'oe', 'á' => 'a',
            'ì' => 'e', 'ò' => 'i', 'ù' => 'o', 'ä' => 'u',
            'ê' => 'a', 'î' => 'e', 'ô' => 'i', 'û' => 'o',
            'Æ' => 'u', 'Œ' => 'a', 'Á' => 'e', 'É' => 'i',
            'Ò' => 'o', 'Ù' => 'u', 'Ä' => 'y', 'Ë' => 'a',
            'Î' => 'e', 'Ô' => 'i', 'Û' => 'o', 'Å' => 'u',
            'é' => 'a', 'í' => 'e', 'ë' => 'i', 'ï' => 'o',
            'å' => 'u', 'e' => 'C', 'Í' => 'AE', 'Ó' => 'OE',
            'Ï' => 'A', 'Ö' => 'E', 'ó' => 'I', 'ö' => 'O',
            'i' => 'U', 'Ú' => 'A', 'Ü' => 'E', 'ú' => 'I',
            'ü' => 'O', 'ø' => 'U', 'À' => 'A', 'Ÿ' => 'E',
            'à' => 'I', 'ÿ' => 'O', 'u' => 'U', 'È' => 'Y',
            'Â' => 'A', 'è' => 'E', 'â' => 'I', 'Ç' => 'O',
            'Ì' => 'U', 'Ê' => 'A', 'Ø' => 'O',
        ];
        return str_replace($from = array_keys($chars), $to = array_values($chars), $text);
    }
    //
    // sudo apt install php-intl
    // works best with UTF chars
    // like 'Êl síla erin lû e-govaned vîn.'
    //
    function rm_diacritics(string $str): string {
        $transliterator = Transliterator::createFromRules(':: Any-Latin; :: Latin-ASCII; :: NFD; :: [:Nonspacing Mark:] Remove; :: NFC;', Transliterator::FORWARD);
        $str = $transliterator->transliterate($str);
        return $str;
    }
    // remove
    // control characters
    //  chr(0) to chr(31),
    // non-printing characters
    //  above chr(127)
    public static function rm_unprintable($str) {
        // If you wish to strip everything except basic printable ASCII characters (all the example characters above will be stripped) you can use:
        // $string = preg_replace( '/[^[:print:]]/', '',$string);
        // You can also html-encode low characters (newline, tab, etc.) while stripping high:
        // $string = filter_var($input, FILTER_UNSAFE_RAW, FILTER_FLAG_ENCODE_LOW|FILTER_FLAG_STRIP_HIGH);
        // remove highers and uppers
        $str = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '?', $str);
        return $str;
    }

}

// missing mb str func

function mb_ucfirst($s) {
    $f_c = mb_strtoupper(
        mb_substr($s, 0, 1, 'UTF-8')
    );
    $rem = mb_substr($s, 1, mb_strlen($s), 'UTF-8');
    return $fc . $rem;
}

function mb_ucwords($str) {
    return mb_convert_case(mb_strtolower($str), MB_CASE_TITLE, 'UTF-8');
}

function mb_trim($str) {
    return mb_ereg_replace('^[[:space:]]*([\s\S]*?)[[:space:]]*$', '\1', $str);
}

function mb_str_pad($input, $pad_length, $pad_string = ' ', $pad_type = STR_PAD_RIGHT) {
    return UTF8::str_pad($input, $pad_length, $pad_string, $pad_type);
}

function mb_str_replace($ustr) {
    // str_replace works just fine with multibyte strings
    return str_replace($ustr);
}

function mb_sprintf($format) {
    $argv = func_get_args();
    array_shift($argv);
    return mb_vsprintf($format, $argv);
}

function mb_vsprintf($format, $argv) {
    $newargv = [];

    preg_match_all("`\%('.+|[0 ]|)([1-9][0-9]*|)s`U", $format, $results, PREG_SET_ORDER);

    foreach ($results as $result) {
        list($string_format, $filler, $size) = $result;
        if (strlen($filler) > 1) {
            $filler = substr($filler, 1);
        }

        while (!is_string($arg = array_shift($argv))) {
            $newargv[] = $arg;
        }

        $pos = strpos($format, $string_format);
        $format = substr($format, 0, $pos)
        . ($size ? str_repeat($filler, $size - strlen($arg)) : '')
        . str_replace('%', '%%', $arg)
        . substr($format, $pos + strlen($string_format))
        ;
    }

    return vsprintf($format, $newargv);
}

/*
handle with care :
1. that function was designed mostly for utf-8. i guess it won't work with any static mb encoding.
2. my configuration sets the mbstring.func_overload configuration directive to 7, so you may wish to replace substr, strlen, etc. with mb_* equivalents.
3. since preg_* doesn't complies with mb strings, I used a '.+' in the regexp to symbolize an escaped filler character. That means, %'xy5s pattern will match, unfortunately. It is recomended to remove the '+', unless you are intending to use an mb char as filler.
4. the filler fills at left, and only at left.
5. I couldn't succeed with a preg_replace thing : the problem was to use the differents lengths of the string arguements in the same replacement, string or callback. That's why the code is much longuer than I expected.
6. The pattern wil not match any %1\$s thing... just was too complicated for me.
7. Although it has been tested, and works fine within the limits above, this is much more a draft than a end-user function. I would enjoy any improvment.

// test code
header("content-type:text/plain; charset=UTF-8") ;
$mb_string = "xéxàx" ;
echo sprintf("%010s", $mb_string), " [octet-size: ", str_sizeof($mb_string) , " ; count: ", strlen(sprintf("%010s", $mb_string)), " characters]\n" ;
echo mb_sprintf("%010s", $mb_string), " [octet-size: ", str_sizeof($mb_string) , " ; count: ", strlen(mb_sprintf("%010s", $mb_string)), " characters]\n" ;
echo "\n" ;
echo mb_sprintf("%''10s\n%'010s\n%'û10s\n%10d\n%'x10s\n%010s\n% 10s\n%010s\n%'1s\n", "zero", "one", "two", 3, "four", "ƒîve", "%s%i%x", "šéveñ", "eight") ;
 */

//----------------------------------------------------------------------------
//  compat layer
//----------------------------------------------------------------------------

// MPDF requires mbstring functions
if (!extension_loaded('mbstring')) {
    if (function_exists('iconv')) {
        function mb_strpos($a, $b) {return iconv_strpos($a, $b);}
        function mb_strlen($str) {return iconv_strlen($str);}
        function mb_substr($a, $b, $c = null) {
            return iconv_substr($a, $b, $c);}
        function mb_convert_encoding($str, $to, $from = 'utf-8') {
            return iconv($from, $to, $str);}
    } else {
        function mb_strpos($a, $b) {
            $c = preg_replace('/^(\X*)' . preg_quote($b) . '.*$/us', '$1', $a);
            return ($c === $a) ? false : mb_strlen($c);
        }
        function mb_strlen($str) {
            $a = [];
            return preg_match_all('/\X/u', $str, $a);
        }
        function mb_substr($a, $b, $c = null) {
            return preg_replace("/^\X{{$b}}(\X" . ($c ? "{{$c}}" : "*") . ").*/us", '$1', $a);
        }
        function mb_convert_encoding($str, $to, $from = 'utf-8') {
            if (strcasecmp($to, $from) == 0) {
                return $str;
            } elseif (
                in_array(strtolower($to),
                    ['us-ascii', 'latin-1', 'iso-8859-1']
                )
                && function_exists('utf8_encode')
            ) {
                return utf8_encode($str);
            } else {
                return $str;
            }
        }
    }
    define('LATIN1_UC_CHARS', 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝ');
    define('LATIN1_LC_CHARS', 'àáâãäåæçèéêëìíîïðñòóôõöøùúûüý');
    function mb_strtoupper($str) {
        if (is_array($str)) {
            $str = $str[0];
        }
        return strtoupper(strtr($str, LATIN1_LC_CHARS, LATIN1_UC_CHARS));
    }
    function mb_strtolower($str) {
        if (is_array($str)) {
            $str = $str[0];
        }
        return strtolower(strtr($str, LATIN1_UC_CHARS, LATIN1_LC_CHARS));
    }
    define('MB_CASE_LOWER', 1);
    define('MB_CASE_UPPER', 2);
    define('MB_CASE_TITLE', 3);
    function mb_convert_case($str, $mode) {
        // XXX: Techincally the calls to strto...() will fail if the
        //      char is not a single-byte char
        switch ($mode) {
        case MB_CASE_LOWER:
            return preg_replace_callback('/\p{Lu}+/u', 'mb_strtolower', $str);
        case MB_CASE_UPPER:
            return preg_replace_callback('/\p{Ll}+/u', 'mb_strtoupper', $str);
        case MB_CASE_TITLE:
            return preg_replace_callback('/\b\p{Ll}/u', 'mb_strtoupper', $str);
        }
    }
    function mb_internal_encoding($encoding) {return 'UTF-8';}
    function mb_regex_encoding($encoding) {return 'UTF-8';}
    function mb_substr_count($haystack, $needle) {
        $matches = [];
        return preg_match_all('`' . preg_quote($needle) . '`u', $haystack, $matches);
    }

    define('MB_OVERLOAD_MAIL', 1);
    define('MB_OVERLOAD_STRING', 2);
    define('MB_OVERLOAD_REGEX', 4);
    define('MB_CASE_UPPER', 0);
    define('MB_CASE_LOWER', 1);
    define('MB_CASE_TITLE', 2);
    function mb_convert_encoding($data, $to_encoding, $from_encoding = 'UTF-8') {
        if (str_replace('-', '', strtolower($to_encoding)) === 'utf8') {
            return utf8_encode($data);
        } else {
            return utf8_decode($data);
        }
    }
    function mb_detect_encoding($data, $encoding_list = ['iso-8859-1'], $strict = false) {
        return 'iso-8859-1';
    }
    function mb_detect_order($encoding_list = ['iso-8859-1']) {
        return 'iso-8859-1';
    }
    function mb_internal_encoding($encoding = null) {
        if (isset($encoding)) {
            return true;
        } else {
            return 'iso-8859-1';
        }
    }
    function mb_strlen($str, $encoding = 'iso-8859-1') {
        switch (str_replace('-', '', strtolower($encoding))) {
        case 'utf8':return strlen(utf8_encode($str));
        case '8bit':return strlen($str);
        default:return strlen(utf8_decode($str));
        }
    }
    function mb_strpos($haystack, $needle, $offset = 0) {
        return strpos($haystack, $needle, $offset);
    }
    function mb_strrpos($haystack, $needle, $offset = 0) {
        return strrpos($haystack, $needle, $offset);
    }
    function mb_strtolower($str) {
        return strtolower($str);
    }
    function mb_strtoupper($str) {
        return strtoupper($str);
    }
    function mb_substr($string, $start, $length = null, $encoding = 'iso-8859-1') {
        if (is_null($length)) {
            return substr($string, $start);
        } else {
            return substr($string, $start, $length);
        }
    }
    function mb_substr_count($haystack, $needle, $encoding = 'iso-8859-1') {
        return substr_count($haystack, $needle);
    }
    function mb_encode_numericentity($str, $convmap, $encoding) {
        return htmlspecialchars($str);
    }
    function mb_convert_case($str, $mode = MB_CASE_UPPER, $encoding = []) {
        switch ($mode) {
        case MB_CASE_UPPER:return mb_strtoupper($str);
        case MB_CASE_LOWER:return mb_strtolower($str);
        case MB_CASE_TITLE:return ucwords(mb_strtolower($str));
        default:return $str;
        }
    }
    function mb_list_encodings() {
        return [
            'ISO-8859-1',
            'UTF-8',
            '8bit',
        ];
    }

}

/**
 * Check if a string is ASCII.
 *
 * The negative regex is faster for non-ASCII strings, as it allows
 * the search to finish as soon as it encounters a non-ASCII character.
 *
 * @since 4.2.0
 *
 * @param string $string String to check.
 * @return bool True if ASCII, false if not.
 */
function check_ascii($string) {
    if (function_exists('mb_check_encoding')) {
        if (mb_check_encoding($string, 'ASCII')) {
            return true;
        }
    } elseif (!preg_match('/[^\x00-\x7F]/', $string)) {
        return true;
    }
    return false;
}
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/../Test.php';

}