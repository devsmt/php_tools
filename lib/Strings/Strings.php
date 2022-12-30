<?php
declare(strict_types=1);

//----------------------------------------------------------------------------
// string functions
//----------------------------------------------------------------------------
// semplifica l'individuazione di almeno una occorrenza di una sottostringa
if (!function_exists('str_contains')) {
    function str_contains(string $str, string $sub_str, bool $ignore_case = true): bool{
        $result = $ignore_case ? mb_strpos($str, $sub_str) : mb_stripos($str, $sub_str);
        return ($result !== false) ? true : false;
    }
}
//
function str_contains_any(string $str, array $arr): bool {
    foreach ($arr as $substring) {
        if (stripos($str, $substring) !== false) {
            return true;
        }
    }
    return false;
}

// semplifica l'individuazione del numero di occorrenze di una sottostringa
function str_count_matches(string $str, string $sub_str): int{
    $result = mb_strpos($str, $sub_str);
    return ($result === false) ? 0 : $result;
}
function str_begins_re(string $str, string $substr, bool $ci = false): bool{
    $p = '/^' . $substr . ' /i';
    return preg_match($p, $str) > 0;
}
/** @param list<string> $args */
function str_begins_with(...$args): bool{
    $str = mb_strtolower(func_get_arg(0));
    for ($i = 1; $i < func_num_args(); $i++) {
        $substr = mb_strtolower(func_get_arg($i));
        if (str_begins($str, $substr)) {
            return true;
        }
    }
    return false;
}
function str_mb_ends(string $str, string $end): bool{
    $len = mb_strlen($end);
    $sub = mb_substr($str, (-1 * $len));
    return (mb_strtolower($sub) === mb_strtolower($end));
}
function str_mb_begins($str, $s_begin) {
    $len = mb_strlen($s_begin);
    $sub = mb_substr($str, 0, $len);
    return mb_strtolower($sub) === mb_strtolower($s_begin);
}
// dato un valore massimo e uno minimo, ritorna un valore compreso trai limiti
function clamp($current, $min = 0, $max = 999) {
    return max($min, min($max, $current));
}
// mette testo su singola linea per migliorare il logging
function str_inline(string $txt): string{
    $txt = preg_replace($regex = '/(\s{2,}|\\n)/ui', ' ', $txt);
    return $txt;
}
//----------------------------------------------------------------------------
//  mb version
//----------------------------------------------------------------------------
// ritorna n caratteri di una str partendo da sinistra
function str_left(string $str, int $length): string {
    return mb_substr($str, 0, $length);
}
// ritorna n caratteri di una str partendo da destra
function str_right(string $str, string $length): string {
    return mb_substr($str, -$length);
}
function str_begins(string $str, string $start): bool{
    $sub = mb_substr($str, 0, mb_strlen($start));
    return $sub === $start;
}
function str_ends(string $str, string $end): bool{
    $el = mb_strlen($end);
    $sub = mb_substr($str, -$el, $el);
    return ($sub === $end);
}
//----------------------------------------------------------------------------
//
//----------------------------------------------------------------------------
// $string = str_end_remove($str='picture.jpg.jpg', '.jpg');//=> 'picture.jpg'
function str_end_remove(string $string, string $s_end): string {
    if (str_ends($string, $s_end)) {
        $end_l = mb_strlen($s_end);
        $str_l = mb_strlen($string);
        $pos = $str_l - $end_l;
        $out = mb_substr($string, 0, $pos);
        return $out;
    }
    return $string;
}
//   str_between("0012345678900", "00", "00"); // => 345678
function str_between(string $input, string $start, string $end): string{
    $p1 = (int) mb_strpos($input, $start);
    $p2 = (int) mb_strpos($input, $end);
    $substr = mb_substr($input, mb_strlen($start) + $p1, (mb_strlen($input) - $p2) * (-1));
    return $substr;
}
//  trim an optional trailing slash off the end of a path:
// if ( mb_substr( $path, -1 ) == '/') $path = mb_substr( $path, 0, -1 );
function path_canonical(string $path): string {return str_end_remove($path, '/');}
//----------------------------------------------------------------------------
//
//----------------------------------------------------------------------------
// toglie qualunque chr non sia una lettera latina o un numero
function str_clean(string $s): string{
    $c = mb_strlen($s);
    $result = '';
    for ($i = 0; $i < $c; $i++) {
        $p = ord($s[$i]);
        // chr(32) is space, it is preserved
        if (($p >= 32 && $p <= 254)) {
            $result .= $s[$i];
        } else {
            $result .= '';
        }
    }
    return $result;
}
// toglie tutti i caratteri non stampabili a terminale (mantiene solo ASCII)
function str_clean_non_printable(string $str): string{
    $str = mb_ereg_replace('/[[:^print:]]/', '', $str);
    return $str;
}
// toglie i whitespace
function str_clean_w(string $s): string {
    return mb_ereg_replace('/\r\n|\n|\r|\t|\s\s/', '', $s);
}
//Remove inside spaces when more than 1
function trim_ws(string $str): string{
    //Remove outside spaces
    $str = trim($str);
    //Remove inside spaces when more than 1
    $str = mb_ereg_replace("/ {2,}/", " ", $str);
    //Remove inside spaces when more than 1
    $str = mb_ereg_replace("/\t{2,}/", "\t", $str);
    //Change double quotes to single quotes - why?!?!
    //$str = mb_ereg_replace("/\"/", "'", $str);
    //Remove new lines when more than 2
    $str = mb_ereg_replace("/\n{3,}/", "\n\n", $str);
    return $str;
}
function bool2str(string $var): string {
    if (mb_strtoupper($var) == 'FALSE') {
        $var = FALSE;
    }
    return $var ? 'TRUE' : 'FALSE';
}
//
// Removes line breaks
//
function str_oneline(string $string): string{
    $string = mb_ereg_replace('/\t/', ' ', $string);
    $string = mb_ereg_replace('/\r?\n/', ' ', $string);
    $string = mb_ereg_replace('/\s{2,}/', ' ', $string);
    return $string;
}
// trying to insert a string into a utf8 mysql table.
// The string (and its bytes) all conformed to utf8, but had several bad sequences.
// I assume that most of them were control or formatting.
function str_clean_utf8(string $string): string{
    $s = trim($string);
    $s = iconv("UTF-8", "UTF-8//IGNORE", $s); // drop all non utf-8 characters
    // this is some bad utf-8 byte sequence that makes mysql complain - control and formatting i think
    $s = mb_ereg_replace('/(?>[\x00-\x1F]|\xC2[\x80-\x9F]|\xE2[\x80-\x8F]{2}|\xE2\x80[\xA4-\xA8]|\xE2\x81[\x9F-\xAF])/', ' ', $s);
    $s = mb_ereg_replace('/\s+/', ' ', $s); // reduce all multiple whitespace to a single space
    return $s;
}
// strip by regexp, specify what you want to include
function str_clean_r(string $s,
    string $opt_chars = "`_.,;@#%~'\+\*\?\[\^\]\$\(\)\{\}\=\!\<\>\|\:\-\\\s",
    array $permit_chars = []
): string {
// '"
    return mb_ereg_replace("/[^A-Z0-9$opt_chars]+/i", '', $s);
}
function str_replace_last(string $what, string $with_what, string $where): string{
    $tmp_pos = mb_strrpos($where, $what);
    if ($tmp_pos !== false) {
        $where = mb_substr($where, 0, $tmp_pos) . $with_what . mb_substr($where, $tmp_pos + mb_strlen($what));
    }
    return $where;
}

/**
 * Returns of direct output of given function
 *
 * @param callable $callback
 *
 * @return string
 */
function ob_wrapper(callable $callback): string{
    ob_start();
    $callback();
    return ob_get_clean();
}

function capwords(string $str): string{
    $a_words = preg_split('/[-_. ]/', $str);
    /** @var array<string> $a_words */
    $a_words = array_values(array_filter($a_words, function ($v){
        // false will be skipped
        return empty($v) ? false : true; // filter out if empty
    }));

    $capped_str = '';
    if( is_array($a_words) ){
        $a_words = array_map( function (string $v):string {
            return ucfirst(mb_strtolower($v));
        } , $a_words);
        $capped_str = join('', $a_words);
    }
    if (!empty($capped_str)) {
        $capped_str[0] = mb_strtoupper($capped_str[0]);
    }
    return $capped_str;
}
// applica funzione f a ciascuno dei char della stringa
// se f ritorna false, il carattere verrà saltato
function str_map(string $str, callable $f): string{
    $a_chars = str_split($str);
    // se f ritorna false, il carattere verrà saltato
    $a_chars_m = array_map($f, $a_chars);
    // toglie char che hanno dato risultato false
    $a_chars_m_f = array_filter($a_chars_m, function (string $c): bool {return false !== $c;});
    $str_m = implode($sep = '', $a_chars_m_f);
    return $str_m;
}

//----------------------------------------------------------------------------
// str template
//----------------------------------------------------------------------------
// data una stringa interpola i valori passati in this->binds nei segnaposto
// espressi con la sintassi {{nome_var}}
// wrappwer
function str_template($str_template, $a_binds) {
    return tmpl($str_template, $a_binds);
}

// tmpl('{{aa}}', get_defined_vars() );
/**
 * @psalm-suppress TypeDoesNotContainType
 * @psalm-suppress MissingClosureParamType
 * @psalm-suppress MissingClosureReturnType
 */
function tmpl(string $tmpl, array $get_defined_vars = []): string {
    if (!is_string($tmpl)) {
        $msg = sprintf('Errore %s ', 'invalid template');
        throw new \Exception($msg);
    }
    $_filter = function ($v, $k) {
        // false will be skipped
        return is_string($k) && (is_scalar($v) || (is_object($v) && method_exists($v, '__toString')));
    };
    if (PHP_VERSION_ID >= 50600) {
        $get_defined_vars = array_filter($get_defined_vars, $_filter, ARRAY_FILTER_USE_BOTH); // php5.6+
    } else {
        // compatibility code
        $get_defined_vars = h_filter($get_defined_vars, function ($v, $k) use ($_filter) {
            return $_filter($k, $v);
        });
    }
    /** @param float|int|string $k */
    $_f = function ($k) {
        // Scalar are float, string or boolean.  array, object and resource are not
        if (is_scalar($k)) {
            return sprintf('{{%s}}', (string) $k);
        } elseif (is_bool($k)) {
            return sprintf('{{%s}}', (string) $k);
        } else {
            return '';
        }
    };
    $vars = array_map_keys($get_defined_vars, $_f);
    return strtr($tmpl, $vars);
}



//
// aggiunge la codifica dei caratteri per output HTML
function html_template(string $str_template, array $a_binds, string $default_sub = '__'): string{
    // xss mitigation functions
    $xss = function (string $str): string {
        return htmlspecialchars($str, ENT_QUOTES | ENT_HTML401, $encoding = 'UTF-8');
    };
    // prevent cross-site scripting attacks (XSS) escaping values
    // escape all by default, skip vars names beginning with '_'
    $_sanitizer = function (string $name, string $val) use ($xss) {
        $name_begins_with_underscore = mb_substr($name, 0, 1) == '_';
        return $name_begins_with_underscore ? $val : $xss($val);
    };
    $a_binds_sanitized = array_map(function (string $k, string $v) use ($_sanitizer): string {
        return $val_s = $_sanitizer($k, $v);
    }, array_keys($a_binds), array_values($a_binds));
    return str_template($str_template, $a_binds_sanitized, $default_sub);
}
// bypass heredoc limitation to not be able to call functions
// an alternative method to
// $fn = function($a){ return $a };
// $html = <<<__END__
// {$fn('aa')}
// __END__;
class HEREDOCHelpers {
    public function __call(string $name, array $args) {
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
function str_a_reg_replace(string $str, array $a_binds): string {
    foreach ($a_binds as $key => $val) {
        $str = mb_ereg_replace($key, $val, $str);
    }
    return $str;
}
// dato un array associativo di variabili da interpolare, esegue la sostituzione
// $a_replace = [
//     'apple' => 'orange'
//     'chevy' => 'ford'
// ];
function str_a_replace(array $a_binds, string $str): string {
    return mb_str_replace(array_keys($a_binds), array_values($a_binds), $str);
}
// sostituzione gestendo array di stringhe in input
/** @return string|array */
function str_replace_deep(string $search, string $replace, array $a_str) {
    if (is_array($a_str)) {
        foreach ($a_str as &$_str) {
            $_str = str_replace_deep($search, $replace, $_str);
        }
        unset($_str);
        return $a_str;
    } else {
        return mb_str_replace($search, $replace, $a_str);
    }
}

/**
 * @param string|array<string> $m_sub
 * @param string|array<string> $m_re
 */
function str_replace_all($m_sub, $m_re, string $str): string {
    do {
        $str = str_replace($m_sub, $m_re, $str, $c);
    } while ($c > 0);
    return $str;
}

// function str_money(string $s): string{
//     $v = str_to_float($s);
//     return Money::format($v);
// }
//
// converte un numero in formato sia (1.000,00)in un float
// un float a' un numero con i decimali separati dal '.'
// da sia esce una stringa con il punto come separatore delle migliaia
// e la virgola come separatore dei decimali.
//
function str_to_float(string $s): float {
    if (is_float($s)) {
        return $s;
    } elseif (is_string($s)) {
        $s = trim($s);
        if (preg_match('/\.[0-9]{2}$/', $s)) { // se a' gia' una str contenente un float
            return (float) $s;
        } elseif (preg_match('/,[0-9]{2}$/', $s)) { // se esce un valore monetario
            $s = mb_str_replace('.', '', $s);
            $s = mb_str_replace(',', '.', $s);
        }
    }
    return (float) $s;
}
// usa nuova estensione senza '.'
function str_extension_replace(string $filename, string $new_extension): string{
    // alternatives:
    //   $info = pathinfo($filename);
    //   return $info['filename'] . '.' . $new_extension;
    //   return mb_ereg_replace('/\..+$/', '.' . $new_extension, $filename);
    $i = intval(mb_strrpos($filename, '.'));
    return mb_substr_replace($filename, $new_extension,
        1 + $i
    );
}
// human readable per le funzioni memory_get_peak_usage() / memory_get_usage()
function format_bytes(int $bytes_size, int $precision = 2): string{
    $base = log($bytes_size) / log(1024);
    $suffixes = ['', 'k', 'M', 'G', 'T'];
    $b = pow(1024, $base - floor($base));
    $i = (int) floor($base);
    $suffix = $suffixes[$i];
    return (string) round($b, $precision) . $suffix;
}
//
function format_time(string $secs): string {
    static $timeFormats = [
        [0, '< 1 sec'],
        [2, '1 sec'],
        [59, 'secs', 1],
        [60, '1 min'],
        [3600, 'mins', 60],
        [5400, '1 hr'],
        [86400, 'hrs', 3600],
        [129600, '1 day'],
        [604800, 'days', 86400],
    ];
    foreach ($timeFormats as $format) {
        if ($secs >= $format[0]) {
            continue;
        }
        if (2 == count($format)) {
            return $format[1];
        }
        return sprintf('%s %s', ceil($secs / $format[2]), $format[1]);
    }
    return '';
}
// data una grandezza in una unità specifica, ritorna la grandezza in bytes
function format_bytes_size(int $size = 0, string $unit = 'B'): int{
    $unit = mb_strtoupper($unit);
    $a_units = ['B' => 0, 'KB' => 1, 'MB' => 2, 'GB' => 3, 'TB' => 4, 'PB' => 5, 'EB' => 6, 'ZB' => 7, 'YB' => 8];
    if (!in_array($unit, array_keys($a_units))) {
        return 0;
    }
    if (!intval($size) < 0) {
        return 0;
    }
    $b_unit = pow(1024, $a_units[$unit]);
    return intval($size) * intval($b_unit);
}
function format_bytes_str(string $str): int{
    $str_unit = trim(substr($str, -2));
    $str_unit = mb_strtoupper($str_unit);
    if (intval($str_unit) !== 0) {
        $str_unit = 'B';
    }
    $a_units = ['B' => 0, 'KB' => 1, 'MB' => 2, 'GB' => 3, 'TB' => 4, 'PB' => 5, 'EB' => 6, 'ZB' => 7, 'YB' => 8];
    if (!in_array($str_unit, array_keys($a_units))) {
        return -1;
    }
    $size = trim(substr($str, 0, mb_strlen($str) - 2));
    if (intval($size) != $size) {
        return -1;
    }
    $power = $a_units[$str_unit];
    $b_unit = pow(1024, $power);
    return intval($size) * intval($b_unit);
}
// trova un tag con un particolare attributo
function get_by_tag_att(string $attr, string $value, string $xml, string $tag = ''): string {
    if (empty($tag)) {
        $tag = '\\w+';
    } else {
        $tag = preg_quote($tag);
    }
    $attr = preg_quote($attr);
    $value = preg_quote($value);
    $tag_regex = "/<(" . $tag . ")[^>]*$attr\\s*=\\s*" . "(['" . '"' . "])$value\\\\2[^>]*>(.*?)<\\/\\\\1>/";
    preg_match_all($tag_regex, $xml, $matches, PREG_PATTERN_ORDER);
    $tag = (string) h_get($matches, 3, '');
    return $tag;
}

// Rotate each string characters by n positions in ASCII table
// To encode use positive n, to decode - negative.
// With n = 13 (ROT13), encode and decode n can be positive.
/* USO
$enc = rotate('string', 6);
echo "Encoded: $enc<br/>\n";
echo 'Decoded: ' . rotate($enc, -6);
 */
function str_rotate(string $str, int $n): string{
    $length = mb_strlen($str);
    $result = '';
    for ($i = 0; $i < $length; $i++) {
        $ascii = ord($str[$i]);
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
function str_highlight(string $str, string $search = '', string $replacement = '<em>${0}</em>'): string {
    if (!empty($search)) {
        $ind = mb_stripos($str, $search);
        $is_found = $ind !== false;
        $len = mb_strlen($search);
        if ($is_found) {
            // usa espressione regolare per preservare il case della parola cercata
            $pattern = "/$search/i";
            $str = mb_ereg_replace($pattern, $replacement, $str);
        }
    }
    if (mb_detect_encoding($str) != "UTF-8") {
        $str = utf8_encode($str);
    }
    return $str;
}
// increments a string(converted to it's numeric representation) and outputs the incremented string
// StringSequence::increment('asdaW31RG2B3q'); => 'asdaW31RG2B3r'
class StringSequence {
    static $ANSI_LIMIT = 30;
    private static function validate(string $str): bool {
        if (mb_strlen($str) > self::$ANSI_LIMIT) {
            $msg = sprintf('Expected string length should not exceed  %s ', self::$ANSI_LIMIT);
            throw new Exception($msg);
        }
        if (!ctype_alnum($str)) {
            $msg = sprintf('String must only contain alphabats or integers');
            throw new Exception($msg);
        }
        return true;
    }
    private static function filterStr(string $str): string {
        return trim(htmlspecialchars(strip_tags($str)));
    }
    // increment $char by 1
    private static function nextCharacter(string $char): string {
        if ($char == '9') {
            return 'a';
        } elseif ($char == 'Z' || $char == 'z') {
            return '0';
        } else {
            return chr(ord($char) + 1);
        }
    }
    // return new sequencially incremented string
    private static function nextSequence(string $str): string{
        // reverse, make into array, increment last and next if needed(=0)
        // array to string,
        // then reverse again
        $a_chars = mb_str_split(mb_strrev($str));
        foreach ($a_chars as $char) {
            $char = self::nextCharacter($char);
            // keep going down the line if we're moving from 'Z' to '0'
            if ($char != '0') {
                break;
            }
        }
        $str = mb_strrev(implode('', $a_chars));
        // check string if contains all 0's then prepend string by 1
        $is_all_zero = preg_match('/^[0]+$/i', $str);
        if ($is_all_zero) {
            $str = '1' . $str;
        }
        return $str;
    }
    // Get New Sequencially Incremented String
    public static function increment(string $str, int $offset = 1): string{
        $str = self::filterStr($str);
        self::validate($str);
        $res = $str;
        for ($i = 0; $i < $offset; $i++) {
            $res = self::nextSequence($res);
        }
        return $res;
    }
}
/** Remove non-digits from a string
 * @param string
 * @return string
 */
function str2int(string $val): float {
    return intval(preg_replace('~[^0-9]+~', '', $val));
}
function str2float(string $val): float {
    return floatval(preg_replace('~[^0-9\.\,]+~', '', $val));
}

//
// traduce al plurale una stringa
// nella forma:
// str_plural('[[singular|plural]]', ['singular' => 1])
function str_plural(string $str_template, array $h_counts): string{
    $reg = '/\[\[(\w+)\|(\w+)\]\]/i';
    $line = preg_replace_callback($reg, function ($matches) use ($h_counts) {
        $singular = $matches[1];
        $plural = $matches[2];
        $count = $h_counts[$singular];
        return ($count == 1) ? $singular : $plural;
    }, $line = $str_template);
    return $line;
}


// testa l'aderenza di una stringa $str al pattern POSIX/SQL specificato, case insensitive
function str_like(string $s_pattern, string $str): bool{
    $_str_replace_all = function ($s_sub, $s_re, $str) {
        do {
            $str = str_replace($s_sub, $s_re, $str, $c);
        } while ($c > 0);
        return $str;
    };
    // to convert your patterns to regex, place a ^ at the beginning, and a $ at the end, then replace * with .* and escape .s.
    // 'goo*',       =>  preg_match('#^goo.*$#','google.com')
    // '*gl*',       =>  preg_match('#^.*gl.*$#', 'google.com');
    // 'google.com', =>  preg_match('#^google\.com$#', 'google.com')
    $_pattern_2_regex = function ($s_pattern) use ($_str_replace_all) {
        // => '#^goo.*$#'
        $regex = preg_quote($s_pattern);
        // caratteri che vengono escaped:   . \ + * ? ^ $ [ ] ( ) { } < > = ! | :
        $regex = "/^$regex$/i"; // case insensitive
        $regex = $_str_replace_all($sub = '\*', $re = '(.*)', $regex);
        $regex = $_str_replace_all($sub = '%', $re = '(.*)', $regex);
        return $regex;
    };
    $regex = $_pattern_2_regex($s_pattern);
    $r = preg_match($regex, $str, $a_matches);
    // if (true) {
    //     unset($a_matches[0]);
    //     echo "$regex $r =>" . json_encode(array_values($a_matches)) . "\n";
    // }
    return $r == 1;
}

function str_unCamel_Case(string $input, string $sep = ' '):string {
    $output = preg_replace( ['/(?<=[^A-Z])([A-Z])/', '/(?<=[^0-9])([0-9])/'], $sep.'$0', $input);
    return ucwords($output);
}


//----------------------------------------------------------------------------
// main tests
//----------------------------------------------------------------------------
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/../Test.php';
    $ss = str_slugify("This is just a small test for a slug creation");
    ok($ss, 'this-is-just-a-small-test-for-a-slug-creation');

    $r = str_template('second: {{second}}; first: {{first}}', [
        'first' => '1st',
        'second' => '2nd',
    ]);
    $e = 'second: 2nd; first: 1st';
    ok($r, $e, 'str_template');

    $s = str_replace_last('.', ".bb.", '.....aaaa.exe');
    ok($s, ".....aaaa.bb.exe", "str_replace_last");

    //
    //
    //
    ok(str_plural('[[singular|plural]]', ['singular' => 1]), 'singular', 'tmpl 1');
    ok(str_plural('[[singular|plural]]', ['singular' => 2]), 'plural', 'tmpl 2');
    // multiline
    ok(str_plural('[[singular|plural]] [[singular2|plural2]]', ['singular' => 2, 'singular2' => 22]), 'plural plural2', 'tmpl 3');
    ok(str_plural('[[singular|plural]] [[singular|plural]]!!', ['singular' => 2]), 'plural plural!!', 'tmpl multi');

    function test_str_like() {
        ok(str_like('google.com', 'google.com'), true, 'equal');
        ok(str_like('g*gle.com', 'google.com'), true, 'parts');
        ok(str_like('*oo*le.com', 'google.com'), true, 'parts');
        ok(str_like('goo*', 'google.com'), true, '1');
        ok(str_like('*gl*', 'google.com'), true, '2');
        ok(str_like('google.com', 'google.com'), true, '3');
        ok(str_like('zzz', 'google.com'), false, '4');
        // sql like
        ok(str_like('goo%', 'google.com'), true, 'sql_like 1');
        ok(str_like('g%.com', 'google.com'), true, 'sql_like 3');
        ok(str_like('%gl%', 'google.com'), true, 'sql_like 2');
        //
        ok(str_like('G%.COM', 'google.com'), true, 'case insensitive');
    }
    test_str_like();

}
