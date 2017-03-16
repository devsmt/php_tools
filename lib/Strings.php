<?php

//----------------------------------------------------------------------------
//   PHP7 str_find
//----------------------------------------------------------------------------
function str_find( string $str, string $substr, int $offset = 0, bool $ci = false, ): ?int {
    // strpos()/stripos() support negative lengths as of PHP 7.1.0
    if (\PHP_VERSION_ID < 70100 && $offset < 0) {
        $offset += length($str);
    }
    if( $ci ) {
        $ret = \stripos($str, $substr, $offset);
    } else {
        $ret = \strpos($str, $substr, $offset);
    }
    return $ret === false ? null : $ret;
}
function str_find_last( string $str, string $substr, int $offset = 0, bool $ci = false, ): ?int {
    // Unlike strpos() and stripos(), strrpos() and strripos() both support
    // negative offsets in all PHP versions.

    if( $ci ) {
        $ret = \strripos($str, $substr, $offset);
    } else {
        $ret = \strrpos($str, $substr, $offset);
    }
    return $ret === false ? null : $ret;
}
function str_find_count(string $str, string $substr, int $offset = 0): int {
    // substr_count() supports negative lengths as of PHP 7.1.0
    if (\PHP_VERSION_ID < 70100 && $offset < 0) {
        $offset += length($str);
    }
    return \substr_count($str, $substr, $offset);
}
function str_contains(string $str, string $substr, int $offset = 0): bool {
    return str_find($str, $substr, $offset) !== null;
}
// minimal implementation
//function str_contains($str, $substr, $ci=true) {
//    if( $ci ) {
//        return (strpos($str, $substr) !== false);
//    } else {
//        return (stripos($str, $substr) !== false);
//    }
//}

function str_begins($str, $substr, $ci=false):bool {
    $p = '/^' . $substr . ' /i';
    return preg_match($p, $str) > 0;
}

//
// @param str haystack
// var params needle
//
function str_begins_with() {
    $str = strtolower(func_get_arg(0));
    for ($i = 1; $i < func_num_args(); $i++) {
        $substr = strtolower(func_get_arg($i));
        if (str_begins($str, $substr)) {
            return true;
        }
    }
    return false;
}

// toglie qualunque chr non sia una lettera latina o un numero
function str_clean($s) {
    $c = strlen($s);
    $result = '';
    for ($i = 0; $i < $c; $i++) {
        $p = ord($s[$i]);
        // chr(32) is space, it is preserved
        if (($p >= 32 && $p <= 254)) {
            $result.= $s[$i];
        } else {
            $result.= '';
        }
    }
    return $result;
}

// toglie tutti i caratteri non stampabili a terminale (mantiene solo ASCII)
function str_clean_non_printable($str){
    $str = preg_replace('/[[:^print:]]/', '', $str);
    return $str;
}

// toglie i whitespace
function str_clean_w($s) {
    return preg_replace(['/\r\n|\n|\r|\t|\s\s/'], '', $s);
}

// trying to insert a string into a utf8 mysql table.
// The string (and its bytes) all conformed to utf8, but had several bad sequences.
// I assume that most of them were control or formatting.
function str_clean_utf8($string) {
    $s = trim($string);
    $s = iconv("UTF-8", "UTF-8//IGNORE", $s); // drop all non utf-8 characters
    // this is some bad utf-8 byte sequence that makes mysql complain - control and formatting i think
    $s = preg_replace('/(?>[\x00-\x1F]|\xC2[\x80-\x9F]|\xE2[\x80-\x8F]{2}|\xE2\x80[\xA4-\xA8]|\xE2\x81[\x9F-\xAF])/', ' ', $s);
    $s = preg_replace('/\s+/', ' ', $s); // reduce all multiple whitespace to a single space
    return $s;
}

// strip by regexp, specify what you want to include
function str_clean_r(string $s,
    $opt_chars = "`_.,;@#%~'\"\+\*\?\[\^\]\$\(\)\{\}\=\!\<\>\|\:\-\s\\\\",
    array $permit_chars = []
):string {
    // '"
    return preg_replace("/[^A-Z0-9$opt_chars]+/i", '', $s);
}


//
// assicura che gli utenti non possano iniettare HTML(e quindi js) dalle variabili
// passate in GET/POST
//
function str_escape($input) {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

function str_replace_last($what, $with_what, $where) {
    $tmp_pos = strrpos($where, $what);
    if ($tmp_pos !== false) {
        $where = substr($where, 0, $tmp_pos) . $with_what . substr($where, $tmp_pos + strlen($what));
    }
    return $where;
}

// mostra solo n char di un testo lungo, evitando di spezzare le parole
// brutalmente, ma non fa nulla di particolare per funzionare con html
function str_reminder($str, $maxlen = 50, $suffisso = ' [...] ') {
    if (strlen($str) > $maxlen) {
        $result = '';
        $str = str_replace('  ', ' ', $str);
        $a = explode(' ', substr($str, 0, $maxlen + 10)); // per migliorare le prestazioni vado a fare l'explode di una stringa ragionevolmente ridimensionata
        for ($i = 0; $i < count($a); $i++) {
            if (strlen($result . $a[$i] . ' ') < $maxlen) {
                $result.= $a[$i] . ' ';
            } else {
                break;
            }
        }
        return trim($result) . ' ' . $suffisso;
    } else {
        return $str;
    }
}

// semplifica l'individuazione di almeno una occorrenza di una sottostringa
function str_match($str, $sub_str) {
    $result = strpos($str, $sub_str);
    return ($result !== false) ? true : false;
}

// semplifica l'individuazione del numero di occorrenze di una sottostringa
function str_count_matches($str, $sub_str) {
    $result = strpos($str, $sub_str);
    return ($result === false) ? 0 : $result;
}


// random, human readable string, good for password, captcha and other codes
// esclude i caratteri che potrebbero essere confusi, come i,l,1,I,0,o,O
function str_random_human_readable($length = 9, $strength = 1, $readable = true) {
    // esclusi i caratteri che potrebbero essere confusi, come i,l,1,I oppure 0 e o/O
    $vowels = 'aeuy';
    $consonants = 'bdghjmnpqrstvz';
    if ($strength & 1) {
        $consonants.= '23456789';
    }
    if ($strength & 2) {
        $vowels.= "AEUY";
    }
    if ($strength & 4) {
        $consonants.= 'BDGHJLMNPQRSTVWXZ';
    }
    if ($strength & 8) {
        $consonants.= '@#$%';
    }
    $password = '';
    $alt = time() % 2;
    for ($i = 0; $i < $length; $i++) {
        if ($alt == 1) {
            $password.= $consonants[(rand() % strlen($consonants))];
            $alt = 0;
        } else {
            $password.= $vowels[(rand() % strlen($vowels))];
            $alt = 1;
        }
    }
    return $password;
}

// Generate random char sequence
function rand_chars( $count, $chars = 36 ) {
    return string_random($count, $chars );
}
// genera una stringa random della lunghezza specificata sfruttando il valore ASCII di un carattere
// $chars <= 9 digits <= 35 lowercase, <= 61 uppercase
// ord('0') = 48   z=a+25
// ord('A') = 65   Z=A+25
// ord('a') = 97
function string_random($l=128, $chars = 35 ) {
    $s = '';
    for ($i = 0; $i < $l; ++$i) {
        $r = mt_rand(0, $chars );
        if ($r < 10) {
            $c = chr(ord('0') + $r);//numeric
        } elseif ($r < 36 )  {
            $c = chr(ord('a') + $r - 26 );//alpha
        } elseif ($r < 62 )   {
            // alpha uppecase r=range(0,25)
            $c = chr(ord('A') + $r - 36 );//upper alpha
        }
        $s .= $c;
    }
    return $s;
}

class RandStr {
    // data una stringa di base, genera password di uguale lunghezza
    // e assicura che almeno un carattere sia numerico e punteggiatura
    public static function mkPassword($str, $len=10, $min_num_len=1, $len_sign=1, $upper=true) {
        if($upper) $str = strtoupper($str);

        $str = preg_replace('/[^A-Z0-9]/', '', $str);
        $str = substr($str,0, $len );
        $str = self::pad($str, $len );
        $str = strtoupper($str);
        $str = $str . self::generate($min_num_len, '123456789'  );// aggiunge caratteri di punteggiatura
        $str = $str . self::generate($len_sign, '.,?;:!%_=-+*@' );// aggiunge caratteri di punteggiatura

        if($upper) $str = strtoupper($str);
        return $str;
    }
    // generara una stringa rand della lunghezza specifica
    // il dict di default non contiene la lettera "O" perchè facile confonderla con numero 0
    public static function generate($len, $dict='ABCDEFGHIJKLMNPQRSTUVWXYZ0123456789' ) {
        $dict_len = strlen($dict);
        $str = '';
        for ($i = 0; $i < $len; $i++) {
            $pos = rand(0, $dict_len-1 );
            $str .= $dict{$pos};
        }
        return $str;
    }
    // random pad
    public static function pad($str, $len) {
        if( strlen($str) >= $len ) {
            return $str;
        } else {
            $delta = $len - strlen($str);
            $suffix = self::generate($delta);
            return $str.$suffix;
        }
    }
}

function str_rm_diacritics($str) {
    // hash "lettera latina" => regexp char group da sostituire
    $DIACRITICS = [
        'a' => '[aÀÁÂÃÄÅàáâãäåĀā]',
        'c' => '[cÇçćĆčČ]',
        'd' => '[dđĐďĎ]',
        'e' => '[eÈÉÊËèéêëěĚĒē]',
        'i' => '[iÌÍÎÏìíîïĪī]',
        'n' => '[nÑñňŇ]',
        'o' => '[oÒÓÔÕÕÖØòóôõöøŌō]',
        'r' => '[rřŘ]',
        's' => '[sŠš]',
        't' => '[tťŤ]',
        'u' => '[uÙÚÛÜùúûüůŮŪū]',
        'y' => '[yŸÿýÝ]',
        'z' => '[zŽž]'
    ];
    $str_result = trim($str);
    foreach( $DIACRITICS as $letter=>$dia_regex ) {
        $str = preg_replace('/'.$dia_regex.'/', $letter, $str);
    }
    return $str_result;
}


function str_transliterate($str){
    if (function_exists('iconv')) {
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $str);
    }
    return $text;
}
/*
@see utf8 lib
require_once('libs/utf8/utf8.php');
require_once('libs/utf8/utils/bad.php');
require_once('libs/utf8/utils/validation.php');
require_once('libs/utf8_to_ascii/utf8_to_ascii.php');
if(!utf8_is_valid($str)){
  $str=utf8_bad_strip($str);
}
$str = utf8_to_ascii($str, '' );
*/
function str_rm_nonascii($str){
    $res = preg_replace('/[^\x20-\x7E]/','', $str);
    // remove non ascii characters
    // $res =  preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $str);
    return $res;
}

function str_is_utf8(string $str): bool {
    return (bool) \preg_match('//u', $str);
}




// Encodes HTML safely for UTF-8. Use instead of htmlentities.
//
// The htmlentities() function doesn't work automatically with multibyte strings. To save time, you'll want to create a wrapper function and use this instead
// htmlentities: is identical to htmlspecialchars() in all ways, except with htmlentities(), all characters which have HTML character entity equivalents are translated into these entities
// htmlspecialchars: Certain characters have special significance in HTML, and should be represented by HTML entities if they are to preserve their meanings.
//
function str_to_html($var) {
    return htmlentities($var, ENT_QUOTES, 'UTF-8');
}

// code derived from http://php.vrana.cz/vytvoreni-pratelskeho-url.php
function str_slugify($text) {
    // replace non letter or digits by dash -
    $text = preg_replace('~[^\\pL\d]+~u', '-', $text);
    // trim
    $text = trim($text, '-');
    // transliterate
    if (function_exists('iconv')) {
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    }
    // lowercase
    $text = strtolower($text);
    // remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);
    if (empty($text)) {
        return 'n-a';
    }
    return $text;
}

// data una stringa interpola i valori passati in this->binds nei segnaposto
// espressi con la sintassi {{nome_var}}
// TODO: gestire il caso in cui si passi un oggetto come $a_bind
// $obj->view_$name || $obj->view_$name()
function str_template($str_template, array $a_binds, $default_sub='__') {
    $_substitute = function ($buffer, $name, $val) {
        $reg = sprintf('{{%s}}', $name );
        $reg = preg_quote($reg, '/');
        return preg_replace('/'.$reg.'/i', $val, $buffer);
    };
    $_clean_unused_vars = function ($buffer) use($default_sub) {
        return preg_replace('/\{\{[a-zA-Z0-9_]*\}\}/i', $default_sub, $buffer );
    };
    $buffer = $str_template;
    foreach ($a_binds as $name => $val) {
        $buffer = $_substitute($buffer, $name, $val);
    }
    $buffer = $_clean_unused_vars($buffer);
    return $buffer;
}
// aggiunge la codifica dei caratteri per output HTML
function html_template($str_template, array $a_binds, $default_sub='__' ) {
    // prevent cross-site scripting attacks (XSS) escaping values
    // escape all by default, skip vars names beginning with '_'
    $_sanitizer = function($name, $val) {
        $name_begins_with_underscore = substr($name,0,1) == '_';
        return $name_begins_with_underscore ? $val : htmlspecialchars($s, ENT_QUOTES);
    }
    $a_binds_sanitized = array_map(function($k, $v) {
        return $val_s = $_sanitizer($k, $v);
    }, array_keys($a_binds), array_values($a_binds) );
    return str_template($str_template,$a_binds_sanitized, $default_sub);
}

// bypass heredoc limitation to not be able to call functions
// an alternative method to
// $fn = function($a){ return $a };
// $html = <<<__END__
// {$fn('aa')}
// __END__;
class HEREDOCHelpers {
    public function __call($name, $args) {
        if (function_exists($name)) {
            return call_user_func_array($name, $args);
        }
    }
}
// USO:
// $_fn = new HEREDOCHelpers();
// in a heredoc with {$_fn->time()}




//
//  Returns subject replaced with regular expression matchs
//
//  $patterns = array(
//  '/(19|20)(\d{2})-(\d{1,2})-(\d{1,2})/ => '\3/\4/\1\2',
//  '/^\s*{(\w+)}\s*=/' => '$\1 ='
//  );
//
//  echo str_a_reg_replace('{startDate} = 1999-5-27', $patterns );
//
//  output:
//  $startDate = 5/27/1999
//
function str_a_reg_replace($str, array $a_binds) {
    return preg_replace(array_keys($a_binds), array_values($a_binds), $str);
}

// dato un array associativo di variabili da interpolare, esegue la sostituzione
// $a_replace = array(
//     'apple' => 'orange'
//     'chevy' => 'ford'
// );
function str_a_replace(array $a_binds, $str) {
   return str_replace(array_keys($a_binds), array_values($a_binds), $str);
}

// sostituzione gestendo array di stringhe in input
function str_replace_deep($search, $replace, $a_str) {
    if (is_array($a_str)) {
        foreach($a_str as &$_str) {
            $_str = str_replace_deep($search, $replace, $_str);
        }
        unset($_str);

        return $a_str;
    } else {
        return str_replace($search, $replace, $a_str);
    }
}



function str_money($s) {
    $v = str_to_float($s);
    return Money::format($v);
}

// ritorna n caratteri di una str partendo da destra
function str_right($str, $n) {
    //return substr($str, strlen($str)-$n, $n );
    if (is_array($str))
        return ''; //'Array:'.print_r($str).'';
    else
        return substr($str, -$n);
}

// ritorna n caratteri di una str partendo da sinistra
function str_left($str, $n) {
    return substr($str, 0, -$n);
}

//
// converte un numero in formato sia (1.000,00)in un float
// un float a' un numero con i decimali separati dal '.'
// da sia esce una stringa con il punto come separatore delle migliaia
// e la virgola come separatore dei decimali.
//
function str_to_float($s) {
    if (is_float($s)) {
        return $s;
    } elseif (is_string($s)) {
        $s = trim($s);
        if (preg_match('/\.[0-9]{2}$/', $s)) { // se a' gia' una str contenente un float
            return (float) $s;
        } elseif (preg_match('/,[0-9]{2}$/', $s)) { // se esce un valore monetario
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        }
    }
    return (float) $s;
}

// transliterate from utf-8 a ascii
function str_to_ascii($s) {
    if (function_exists('iconv')) {
        $s = iconv('utf-8', 'us-ascii//TRANSLIT', $s);
    }
    return $s;
}

// usa nuova estensione senza '.'
function str_extension_replace($filename, $new_extension) {
    // alternatives:
    //   $info = pathinfo($filename);
    //   return $info['filename'] . '.' . $new_extension;
    //   return preg_replace('/\..+$/', '.' . $new_extension, $filename);
    return substr_replace($filename, $new_extension, 1 + strrpos($filename, '.'));
}

// human readable per le funzioni memory_get_peak_usage() / memory_get_usage()
function format_bytes($bytes_size, $precision = 2) {
    $base = log($bytes_size) / log(1024);
    $suffixes = ['', 'k', 'M', 'G', 'T'];
    $b = pow(1024, $base - floor($base));
    $suffix = $suffixes[floor($base)];
    return round($b, $precision) . $suffix;
}
// data una grandezza in una unità specifica, ritorna la grandezza in bytes
function format_bytes_size($size = 0, $unit = 'B') {
    $unit = strtoupper( $unit );
    $a_units = ['B'=>0, 'KB'=>1, 'MB'=>2, 'GB'=>3, 'TB'=>4, 'PB'=>5, 'EB'=>6, 'ZB'=>7, 'YB'=>8];
    if (!in_array($str_unit, array_keys($a_units))) {
        return false;
    }
    if (!intval($size) < 0 ) {
        return false;
    }
    $b_unit = pow(1024, $a_units[$str_unit]);
    return $size * $b_unit;
}

function format_bytes_str($str) {
    $str_unit = trim(substr($str, -2));
    $str_unit = strtoupper( $str_unit );
    if (intval($str_unit) !== 0) {
        $str_unit = 'B';
    }
    $a_units = ['B'=>0, 'KB'=>1, 'MB'=>2, 'GB'=>3, 'TB'=>4, 'PB'=>5, 'EB'=>6, 'ZB'=>7, 'YB'=>8];
    if (!in_array($str_unit, array_keys($a_units))) {
        return false;
    }
    $size = trim(substr($str, 0, strlen($str) - 2));
    if (!intval($size) == $size) {
        return false;
    }
    $b_unit = pow(1024, $a_units[$str_unit]);

    return $size * $b_unit;
}


// trova un tag con un particolare attributo
function get_by_tag_att($attr, $value, $xml, $tag = null) {
    if (is_null($tag)){
        $tag = '\\w+';
    }else{
        $tag = preg_quote($tag);
    }
    $attr = preg_quote($attr);
    $value = preg_quote($value);
    $tag_regex = "/<(" . $tag . ")[^>]*$attr\\s*=\\s*" . "(['" . '"' . "])$value\\\\2[^>]*>(.*?)<\\/\\\\1>/";
    preg_match_all($tag_regex, $xml, $matches, PREG_PATTERN_ORDER);
    return $matches[3];
}

function text_auto_link($text) {
    $text = preg_replace("/([a-zA-Z]+:\/\/[a-z0-9\_\.\-]+" . "[a-z]{2,6}[a-zA-Z0-9\/\*\-\_\?\&\%\=\,\+\.]+)/", " <a href=\"$1\" target=\"_blank\">$1</a>", $text);
    $text = preg_replace("/[^a-z]+[^:\/\/](www\." . "[^\.]+[\w][\.|\/][a-zA-Z0-9\/\*\-\_\?\&\%\=\,\+\.]+)/", " <a href=\"\" target=\"\">$1</a>", $text);
    $text = preg_replace("/([\s|\,\>])([a-zA-Z][a-zA-Z0-9\_\.\-]*[a-z" . "A-Z]*\@[a-zA-Z][a-zA-Z0-9\_\.\-]*[a-zA-Z]{2,6})" . "([A-Za-z0-9\!\?\@\#\$\%\^\&\*\(\)\_\-\=\+]*)" . "([\s|\.|\,\<])/i", "$1<a href=\"mailto:$2$3\">$2</a>$4", $text);
    return $text;
}

// Rotate each string characters by n positions in ASCII table
// To encode use positive n, to decode - negative.
// With n = 13 (ROT13), encode and decode n can be positive.
/* USO
  $enc = rotate('string', 6);
  echo "Encoded: $enc<br/>\n";
  echo 'Decoded: ' . rotate($enc, -6);
 */
function str_rotate($str, $n) {

    $length = strlen($str);
    $result = '';

    for ($i = 0; $i < $length; $i++) {
        $ascii = ord($str{$i});

        $rotated = $ascii;

        if ($ascii > 64 && $ascii < 91) {
            $rotated += $n;
            $rotated > 90 && $rotated += -90 + 64;
            $rotated < 65 && $rotated += -64 + 90;
        } elseif ($ascii > 96 && $ascii < 123) {
            $rotated += $n;
            $rotated > 122 && $rotated += -122 + 96;
            $rotated < 97 && $rotated += -96 + 122;
        }

        $result .= chr($rotated);
    }

    return $result;
}

// formatta le occorrenze $query all'interno del testo $str
function str_highlight($str, $search = null, $replacement = '<em>${0}</em>'){

    if( !empty($search)) {
        $ind = stripos($str, $search);
        $is_found = $ind !== false;
        $len = strlen($search);
        if($is_found){
            // usa espressione regolare per preservare il case della parola cercata
            $pattern = "/$search/i";
            $str = preg_replace($pattern, $replacement, $str);
        }
    }
    if(mb_detect_encoding($str) != "UTF-8"){
        $str = utf8_encode($str);
    }
    return $str;
}

/* @see utf8 lib
// utf-8 compatible class
class Str {

    public static function UCFirst($s) {
        return mb_strtoupper(mb_substr($s, 0, 1, "UTF-8")) . mb_substr($s, 1, mb_strlen($s), "UTF-8");
    }

    public static function toLower($s) {
        return mb_strtolower($s, "UTF-8");
    }

    public static function toUpper($s) {
        return mb_strtoupper($s, "UTF-8");
    }

    public static function trim($str) {
        return mb_ereg_replace('^[[:space:]]*([\s\S]*?)[[:space:]]*$', '\1', $str);
    }

    public static function reminder($str, $maxlen = 50, $suffisso = ' [...] ') {
        if (mb_strlen($str) > $maxlen) {
            $result = '';
            $str = mb_ereg_replace('[[:space:]]+', ' ', $str);
            // per migliorare le prestazioni vado a fare l'explode di una stringa ragionevolmente ridimensionata
            $a = mb_split('[[:space:]]+', mb_substr($str, 0, $maxlen + 10));
            for ($i = 0; $i < count($a); $i++) {
                if (mb_strlen($result . $a[$i] . ' ') < $maxlen) {
                    $result.= $a[$i] . ' ';
                } else {
                    break;
                }
            }
            return mb_trim($result) . ' ' . $suffisso;
        } else {
            return $str;
        }
    }

    function isUTF8($str) {
        return (utf8_encode(utf8_decode($str)) == $str);
    }

    // encode se necessario
    public static function encode($str) {
        if (mb_detect_encoding($str) != "UTF-8") {
            $str = utf8_encode($str);
        }
        return $str;
    }

}
*/


// increments a string(converted to it's numeric representation) and outputs the incremented string
// StringSequence::increment('asdaW31RG2B3q'); => 'asdaW31RG2B3r'
class StringSequence {
     static $ANSI_LIMIT = 30;
     private static function validate($str) {
         if(strlen($str) > self::$ANSI_LIMIT) {
            $msg = sprintf('Expected string length should not exceed  %s ', self::$ANSI_LIMIT );
            throw new Exception($msg);
         }
         if(!ctype_alnum($str)) {
            $msg = sprintf('String must only contain alphabats or integers');
            throw new Exception($msg);
         }
         return true;
     }
     private static function filterStr($str){
         return trim(htmlspecialchars(strip_tags($str)));
     }
     // increment $char by 1
     private static function nextCharacter($char) {
         if ($char == '9') {
             return 'a';
         } elseif ($char == 'Z' || $char == 'z') {
             return '0';
         } else {
             return chr( ord($char) + 1);
         }
     }
     // return new sequencially incremented string
     private static function nextSequence($str) {
         // reverse, make into array, increment last and next if needed(=0)
         // array to string,
         // then reverse again
         $a_chars = str_split(strrev($str));
         foreach($a_chars as $char) {
             $char = self::nextCharacter($char);
             // keep going down the line if we're moving from 'Z' to '0'
             if ($char != '0') {
                 break;
             }
         }
         $str = strrev(implode('', $a_chars));
         // check string if contains all 0's then prepend string by 1
         $is_all_zero = preg_match('/^[0]+$/i', $str);
         if($is_all_zero){
            $str = '1'.$str;
         }
         return $str;
     }
     // Get New Sequencially Incremented String
     public static function increment($str, $offset=1) {
         $str = self::filterStr($str);
         self::validate($str);
         $res = $str;
         for( $i=0; $i<$offset; $i++) {
             $res = self::nextSequence($res);
         }
         return $res;
     }
}
//----------------------------------------------------------------------------
//  pcre utils
//----------------------------------------------------------------------------
function _pcre_get_error_message(int $error): string {
    switch ($error) {
    case \PREG_NO_ERROR:
        return 'No errors';
    case \PREG_INTERNAL_ERROR:
        return 'Internal PCRE error';
    case \PREG_BACKTRACK_LIMIT_ERROR:
        return 'Backtrack limit (pcre.backtrack_limit) was exhausted';
    case \PREG_RECURSION_LIMIT_ERROR:
        return 'Recursion limit (pcre.recursion_limit) was exhausted';
    case \PREG_BAD_UTF8_ERROR:
        return 'Malformed UTF-8 data';
    case \PREG_BAD_UTF8_OFFSET_ERROR:
        return
        'The offset didn\'t correspond to the beginning of a valid UTF-8 code point';
    case 6 /* PREG_JIT_STACKLIMIT_ERROR */:
        return 'JIT stack space limit exceeded';
    default:
        return 'Unknown error';
    }
}
function _pcre_check_last_error(): void {
    $error = \preg_last_error();
    if ($error !== \PREG_NO_ERROR) {
        throw new PCREException(_pcre_get_error_message($error), $error);
    }
}
namespace PCRE {
    use Exception;
    const string PCRE_CASELESS = 'i';
    const string PCRE_MULTILINE = 'm';
    const string PCRE_DOTALL = 's';
    const string PCRE_EXTENDED = 'x';
    const string PCRE_ANCHORED = 'A';
    const string PCRE_DOLLAR_ENDONLY = 'D';
    const string PCRE_UNGREEDY = 'U';
    const string PCRE_EXTRA = 'X';
    const string PCRE_UTF8 = 'u';
    const string PCRE_STUDY = 'S';
    function pcre_quote(string $text): string {
        return \preg_quote($text);
    }
    function pcre_match( string $regex, string $subject, string $options = '', int $offset = 0, ): ?PCREMatch {
        $match = [];
        $count = \preg_match(
            _pcre_compose($regex, $options),
            $subject,
            $match,
            \PREG_OFFSET_CAPTURE,
            $offset,
        );
        _pcre_check_last_error();
        return $count ? new PCREMatch($match) : new_null();
    }
    function pcre_match_all( string $regex, string $subject, string $options, int $offset = 0 ): array<PCREMatch> {
        $matches = [];
        \preg_match_all(
            _pcre_compose($regex, $options),
            $subject,
            $matches,
            \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE,
            $offset,
        );
        $f = function($match) {
            return new PCREMatch($match);
        };
        return map( $matches, $f );
    }
    function pcre_replace( string $regex, string $subject, string $replacement, ?int $limit = null, string $options = '' ): string {
        $result = \preg_replace(
            _pcre_compose($regex, $options),
            $replacement,
            $subject,
            $limit === null ? -1 : \max(0, $limit),
            );
        _pcre_check_last_error();
        if (!\is_string($result)) {
            throw new PCREException('preg_replace() failed');
        }
        return $result;
    }
    function pcre_split( string $regex, string $subject, ?int $limit = null, string $options = '' ): array<string> {
        $pieces = \preg_split(
            _pcre_compose($regex, $options),
            $subject,
            $limit === null ? -1 : max(1, $limit),
            );
        _pcre_check_last_error();
        if (!\is_array($pieces)) {
            throw new PCREException('preg_split() failed');
        }
        return $pieces;
    }
    final class PCREMatch {
        public function __construct(private array<arraykey, (string, int)> $match) {
            // A sub pattern will exist in $subPatterns if it didn't match
            // only if a later sub pattern matched.
            //
            // Example:
            //   match (a)(lol)?b against "ab"
            //   - ["ab", 0]
            //   - ["a", 0]
            //   match (a)(lol)?(b) against "ab"
            //   - ["ab", 0]
            //   - ["a", 0]
            //   - ["", -1]
            //   - ["b", 1]
            //
            // Remove those ones.
            foreach ($this->match as $k => $v) {
                if ($v[1] == -1) {
                    unset($this->match[$k]);
                }
            }
        }
        public function get(arraykey $pat = 0): string {
            return $this->match[$pat][0];
        }
        public function getOrNull(arraykey $pat = 0): ?string {
            $match = get_or_null($this->match, $pat);
            return $match === null ? new_null() : $match[0];
        }
        public function getOrEmpty(arraykey $pat = 0): string {
            $match = get_or_null($this->match, $pat);
            return $match === null ? '' : $match[0];
        }
        public function getOffset(arraykey $pat = 0): int {
            return $this->match[$pat][1];
        }
        public function getRange(arraykey $pat = 0): (int, int) {
            list($text, $offset) = $this->match[$pat];
            return tuple($offset, $offset + \strlen($text));
        }
        public function has(arraykey $pat): bool {
            return key_exists($this->match, $pat);
        }
        public function __toString(): string {
            return $this->get();
        }
        public function toArray(): array<arraykey, string> {
            return map_assoc($this->match, $x ==> $x[0]);
        }
    }
    final class PCREException extends \Exception {}
    function _pcre_compose(string $regex, string $options = ''): string {
        return '/'._EscapeCache::escape($regex).'/'.$options;
    }
    final class _EscapeCache {
        private static array<arraykey, string> $cache = [];
        public static function escape(string $regex): string {
            $escaped = get_or_null(self::$cache, $regex);
            if ($escaped !== null) {
                return $escaped;
            }
            // Dumb cache policy, but it works.
            if (size(self::$cache) >= 10000) {
                self::$cache = [];
            }
            return (self::$cache[$regex] = _pcre_escape($regex));
        }
    }
    function _pcre_escape(string $regex): string {
        // Insert a "\" before each unescaped "/".
        // I'm really hoping this simple state machine will get jitted to efficient
        // machine code.
        $result = '';
        $length = length($regex);
        $escape = false;
        for ($i = 0; $i < $length; $i++) {
            $char = $regex[$i];
            if ($escape) {
                $escape = false;
            } else if ($char === '/') {
                $result .= '\\';
            } else if ($char === '\\') {
                $escape = true;
            }
            $result .= $char;
        }
        return $result;
    }
}
