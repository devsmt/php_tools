<?php
//----------------------------------------------------------------------------
//  str_find
//----------------------------------------------------------------------------
// semplifica l'individuazione di almeno una occorrenza di una sottostringa
function str_contains(string $str, string $sub_str, bool $ignore_case = true): bool{
    $result = $ignore_case ? mb_strpos($str, $sub_str) : mb_stripos($str, $sub_str);
    return ($result !== false) ? true : false;
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
// random strings
//----------------------------------------------------------------------------
class RandStr {
    const ALPHA = 'bdghjmnpqrstvz';
    const NUM = '123456789';
    const SIGN = '.,?;:!%_=-+*@';
    // data una stringa di base, genera password di uguale lunghezza
    // e assicura che almeno un carattere sia numerico e punteggiatura
    public static function mkPassword(int $len = 10, int $min_num_len = 1, int $len_sign = 1): string{
        $str = '';
        $str .= self::generate($len, self::ALPHA);
        $str .= self::generate($min_num_len, self::NUM); // aggiunge caratteri di punteggiatura
        $str .= self::generate($len_sign, self::SIGN); // aggiunge caratteri di punteggiatura
        return $str;
    }
    // random, human readable string, good for password, captcha and other codes
    // $readable esclude i caratteri che potrebbero essere confusi, come i,l,1,I,0,o,O
    function mkPasswordReadable(int $len = 10, string $flg_strength = 'nVA!', bool $readable = true): string{
        // esclusi i caratteri che potrebbero essere confusi, come i,l,1,I oppure 0 e o/O
        $str_dict = 'bdghjmnpqrstvz';
        $vowels = 'aeuy';
        if (!$readable) {
            $str_dict .= 'lo';
            $vowels .= 'io';
        }
        if (str_match($flg_strength, 'V')) {
            $vowels .= 'AEUY';
        }
        if (str_match($flg_strength, 'n')) {
            $str_dict .= '23456789';
        }
        if (str_match($flg_strength, 'A')) {
            $str_dict .= 'BDGHJLMNPQRSTVWXZ';
        }
        if (str_match($flg_strength, '!')) {
            $str_dict .= '!@#$%';
        }
        $password = '';
        // alterna una vocale e una consonante
        // TODO far in modo che i caratteri numero e simbolo siano stamapti assieme per
        // facilitare la digitazione dalla tastiera
        $alt = time() % 2;
        for ($j = 0; $j < $len; $j++) {
            if ($alt == 1) {
                $i = (rand() % mb_strlen($str_dict));
                $password .= $str_dict[$i];
                $alt = 0;
            } else {
                $i = (rand() % mb_strlen($vowels));
                $password .= $vowels[$i];
                $alt = 1;
            }
        }
        return $password;
    }
    // generara una stringa rand della lunghezza specifica
    // il dict di default non contiene la lettera "O" perchè facile confonderla con numero 0
    public static function generate(int $len, string $dict = 'ABCDEFGHIJKLMNPQRSTUVWXYZ0123456789'): string{
        $dict_len = mb_strlen($dict);
        $str = '';
        for ($i = 0; $i < $len; $i++) {
            $pos = rand(0, $dict_len - 1);
            $str .= $dict{$pos};
        }
        return $str;
    }
    // random pad
    public static function pad(string $str, int $len): string {
        if (mb_strlen($str) >= $len) {
            return $str;
        } else {
            $delta = $len - mb_strlen($str);
            $suffix = self::generate($delta);
            return $str . $suffix;
        }
    }
    // Generate random char sequence
    // genera una stringa random della lunghezza specificata sfruttando il valore ASCII di un carattere
    // $chars <= 9 digits <= 35 lowercase, <= 61 uppercase
    // ord('0') = 48   z=a+25
    // ord('A') = 65   Z=A+25
    // ord('a') = 97
    // function string_random($l, $flag = 'naA') {
    //     $s = '';
    //     for ($i = 0; $i < $l; ++$i) {
    //         if ( $flag == 'n' ) {
    //             $r = mt_rand(0, 10 );
    //             $c = chr(ord('0') + $r);//numeric
    //         } elseif ( $flag == 'a' )  {
    //             $r = mt_rand(0, 26 );
    //             $c = chr(ord('a') + $r  );//alpha
    //         } elseif ( $flag == 'A' )   {
    //             $r = mt_rand(0, 36 );
    //             // alpha uppecase r=range(0,25)
    //             $c = chr(ord('A') + $r );//upper alpha
    //         }
    //         $s .= $c;
    //     }
    //     return $s;
    // }
    function mb_str_split(string $string, string $en = 'UTF-8'): array{
        $l = mb_strlen($string, $en);
        $ret = [];
        for ($i = 0; $i < $l; $i++) {
            $ret[] = mb_substr($string, $i, 1, $en);
        }
        return $ret;
    }
    function mb_strrev(string $str): string {
        return implode(array_reverse(mb_str_split($str)));
    }
    /*
    if ($err = password_check($_REQUEST['username'],$_REQUEST['password'])) {
    print "Bad password: $err";
    // Make the user pick another password
    }
    Basics
    Use at least eight characters, the more characters the better really, but most people will find anything more than about 15 characters difficult to remember.
    Use a random mixture of characters, upper and lower case, numbers, punctuation, spaces and symbols.
    Don't use a word found in a dictionary, English or foreign.
    Never use the same password twice.
    Things to avoid
    Don't just add a single digit or symbol before or after a word. e.g. "apple1"
    Don't double up a single word. e.g. "appleapple"
    Don't simply reverse a word. e.g. "elppa"
    Don't just remove the vowels. e.g. "ppl"
    Key sequences that can easily be repeated. e.g. "qwerty","asdf" etc.
    Don't just garble letters, e.g. converting e to 3, L or i to 1, o to 0. as in "z3r0-10v3"
     */
    /**
     * @return array{0: bool, 1: string }
     */
    function password_check(string $user, string $pass): array{
        $lc_pass = mb_strtolower($pass);
        // also check password with numbers or punctuation subbed for letters
        $denum_pass = mb_strtr($lc_pass, '5301!', 'seoll');
        $lc_user = mb_strtolower($user);
        // the password must be at least six characters
        if (mb_strlen($pass) < 6) {
            return [false, 'The password is too short.'];
        }
        // the password can't be the username (or reversed username)
        if (($lc_pass == $lc_user) || ($lc_pass == mb_strrev($lc_user)) ||
            ($denum_pass == $lc_user) || ($denum_pass == mb_strrev($lc_user))) {
            return [false, 'The password is based on the username.'];
        }
        // count how many lowercase, uppercase, and digits are in the password
        $uc = 0;
        $lc = 0;
        $num = 0;
        $other = 0;
        for ($i = 0, $j = mb_strlen($pass); $i < $j; $i++) {
            $c = mb_substr($pass, $i, 1);
            if (preg_match('/^[[:upper:]]$/', $c)) {
                $uc++;
            } elseif (preg_match('/^[[:lower:]]$/', $c)) {
                $lc++;
            } elseif (preg_match('/^[[:digit:]]$/', $c)) {
                $num++;
            } else {
                $other++;
            }
        }
        // the password must have more than two characters of at least
        // two different kinds
        $max = $j - 2;
        if ($uc > $max) {
            return [false, "The password has too many upper case characters."];
        }
        if ($lc > $max) {
            return [false, "The password has too many lower case characters."];
        }
        if ($num > $max) {
            return [false, "The password has too many numeral characters."];
        }
        if ($other > $max) {
            return [false, "The password has too many special characters."];
        }
        // the password must not contain a dictionary word
        $word_file = '/usr/share/dict/words';
        if (is_readable($word_file)) {
            if ($fh = fopen($word_file, 'r')) {
                $found = false;
                while (!($found || feof($fh))) {
                    $word = preg_quote(trim(mb_strtolower(fgets($fh, 1024))), '/');
                    if (preg_match("/$word/", $lc_pass) ||
                        preg_match("/$word/", $denum_pass)) {
                        $found = true;
                    }
                }
                fclose($fh);
                if ($found) {
                    return [false, 'The password is based on a dictionary word.'];
                }
                return [true, ''];
            }
        }
        return [false, ''];
    }
}
// genera lista caratteri da a-z
function get_all_chars(): array{
    $a_characters = array_merge(range('a', 'z'), range('A', 'Z'));
    return $a_characters;
}
//----------------------------------------------------------------------------
//
//----------------------------------------------------------------------------
function str_rm_diacritics(string $str): string{
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
        'z' => '[zŽž]',
    ];
    $str_result = trim($str);
    foreach ($DIACRITICS as $letter => $dia_regex) {
        $str = mb_ereg_replace('/' . $dia_regex . '/', $letter, $str);
    }
    return $str_result;
}
//----------------------------------------------------------------------------
// transliteration
//----------------------------------------------------------------------------
function str_transliterate(string $str): string {
    if (function_exists('iconv')) {
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $str);
    } else {
        $text = str_rm_diacritics($str);
    }
    return $text;
}
// transliterate from utf-8 a ascii
function str_to_ascii(string $s): string {
    return str_transliterate($s);
}
/*------------------------------------------------------------------------------
@see utf8 lib
require_once('libs/utf8/utf8.php');
require_once('libs/utf8/utils/bad.php');
require_once('libs/utf8/utils/validation.php');
require_once('libs/utf8_to_ascii/utf8_to_ascii.php');
if(!utf8_is_valid($str)){
$str=utf8_bad_strip($str);
}
$str = utf8_to_ascii($str, '' );
------------------------------------------------------------------------------*/
function str_rm_nonascii(string $str): string{
    $res = mb_ereg_replace('/[^\x20-\x7E]/', '', $str);
    // remove non ascii characters
    // $res =  mb_ereg_replace('/[\x00-\x1F\x80-\xFF]/', '', $str);
    return $res;
}
function str_is_utf8(string $str): bool {
    return (bool) \preg_match('//u', $str);
}
//
//  Restituisce TRUE se la stringa sembra codificata in UTF8, FALSE altrimenti.
//
function UTF8_is(string $str): bool{
    $length = mb_strlen($str);
    for ($i = 0; $i < $length; $i++) {
        if (ord($str[$i]) < 0x80) {
            continue;
        } elseif ((ord($str[$i]) & 0xE0) == 0xC0) {
            $n = 1;
        } elseif ((ord($str[$i]) & 0xF0) == 0xE0) {
            $n = 2;
        } elseif ((ord($str[$i]) & 0xF8) == 0xF0) {
            $n = 3;
        } elseif ((ord($str[$i]) & 0xFC) == 0xF8) {
            $n = 4;
        } elseif ((ord($str[$i]) & 0xFE) == 0xFC) {
            $n = 5;
        } else {
            return FALSE;
        }
        for ($j = 0; $j < $n; $j++) {
            if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80)) {
                return FALSE;
            }
        }
    }
    return TRUE;
}
// in ASCII str remove uncommon printable chars, depending on font may not appear in terminals
function str_asci_simplify(string $str): string{
    $str = mb_str_replace(chr(130), ',', $str); // baseline single quote
    $str = mb_str_replace(chr(131), 'NLG', $str); // florin
    $str = mb_str_replace(chr(132), '"', $str); // baseline double quote
    $str = mb_str_replace(chr(133), '...', $str); // ellipsis
    $str = mb_str_replace(chr(134), '**', $str); // dagger (a second footnote)
    $str = mb_str_replace(chr(135), '***', $str); // double dagger (a third footnote)
    $str = mb_str_replace(chr(136), '^', $str); // circumflex accent
    $str = mb_str_replace(chr(137), 'o/oo', $str); // permile
    $str = mb_str_replace(chr(138), 'Sh', $str); // S Hacek
    $str = mb_str_replace(chr(139), '<', $str); // left single guillemet
    $str = mb_str_replace(chr(140), 'OE', $str); // OE ligature
    $str = mb_str_replace(chr(145), "'", $str); // left single quote
    $str = mb_str_replace(chr(146), "'", $str); // right single quote
    $str = mb_str_replace(chr(147), '"', $str); // left double quote
    $str = mb_str_replace(chr(148), '"', $str); // right double quote
    $str = mb_str_replace(chr(149), '-', $str); // bullet
    $str = mb_str_replace(chr(150), '-', $str); // endash
    $str = mb_str_replace(chr(151), '--', $str); // emdash
    $str = mb_str_replace(chr(152), '~', $str); // tilde accent
    $str = mb_str_replace(chr(153), '(TM)', $str); // trademark ligature
    $str = mb_str_replace(chr(154), 'sh', $str); // s Hacek
    $str = mb_str_replace(chr(155), '>', $str); // right single guillemet
    $str = mb_str_replace(chr(156), 'oe', $str); // oe ligature
    $str = mb_str_replace(chr(159), 'Y', $str); // Y Dieresis
    return $str;
}
//----------------------------------------------------------------------------
//
//----------------------------------------------------------------------------
// Encodes HTML safely for UTF-8. Use instead of htmlentities.
//
// The htmlentities() function doesn't work automatically with multibyte strings. To save time, you'll want to create a wrapper function and use this instead
// htmlentities: is identical to htmlspecialchars() in all ways, except with htmlentities(), all characters which have HTML character entity equivalents are translated into these entities
// htmlspecialchars: Certain characters have special significance in HTML, and should be represented by HTML entities if they are to preserve their meanings.
//
function str_to_html(string $var): string {
    return htmlentities($var, ENT_QUOTES, 'UTF-8');
}
//
// assicura che gli utenti non possano iniettare HTML(e quindi js) dalle variabili
// passate in GET/POST
//
function str_escape(string $input): string {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}
function e(string $string): string {
    return str_replace("\0", "&#0;", htmlspecialchars($string, ENT_QUOTES, 'utf-8'));
}
//----------------------------------------------------------------------------
//
//----------------------------------------------------------------------------
// code derived from http://php.vrana.cz/vytvoreni-pratelskeho-url.php
function str_slugify(string $text, string $word_delimiter = '-'): string{
    // replace non letter or digits by dash -
    $text = mb_ereg_replace('~[^\\pL\d]+~u', $word_delimiter, $text);
    // trim
    $text = trim($text, $word_delimiter);
    // transliterate
    $text = str_transliterate($text);
    // remove consecutive multiple dividers
    $slug = preg_replace("/[\/_|\+\-\s]+/", $word_delimiter, $text);
    // lowercase
    $text = mb_strtolower($text);
    // remove unwanted characters
    $text = mb_ereg_replace('~[^-\w]+~', '', $text);
    if (empty($text)) {
        return 'n-a'; // error
    }
    return $text;
}
//
// Camelizes a string.
//
function str_camelize(string $str): string {
    return strtr(
        ucwords(
            strtr(
                $str,
                ['_' => ' ', '.' => '_', '\\' => '_']
            )
        ),
        [' ' => '']
    );
}
// Interpolates context values into the message placeholders.
// usage:
//   $message = "User {{username}} created";
//   $context = ['username' => 'bolivar'];
//   echo str_interpolate($message, $context);
function str_interpolate(string $tmpl, array $a_binds = []): string{
    // build a replacement array with braces around the context keys
    $a_repl = [];
    foreach ($a_binds as $key => $val) {
        // check that the value can be casted to string
        if (str_is_repr($val)) {
            $a_repl["{{$key}}"] = $val;
        } elseif (is_object($val)) {
            $obj = (object) $val;
            $a_repl["{{$key}}"] = sprintf('[object class %s]', get_class($obj));
        }
    }
    return mb_strtr($tmpl, $a_repl);
}
// check input can be converted to str
function str_is_repr(string $val): bool {
    return !is_array($val) && (!is_object($val) || method_exists($val, '__toString'));
}
//----------------------------------------------------------------------------
// templates
//----------------------------------------------------------------------------
// data una stringa interpola i valori passati in this->binds nei segnaposto
// espressi con la sintassi {{nome_var}}
function str_template(string $str_template, array $a_binds, string $default_sub = '__'): string{
    $_substitute = function (string $buffer, string $name, string $val): string{
        $reg = sprintf('{{%s}}', $name);
        $reg = preg_quote($reg, '/');
        return mb_ereg_replace('/' . $reg . '/i', $val, $buffer);
    };
    $_clean_unused_vars = function (string $buffer) use ($default_sub): string {
        return mb_ereg_replace('/\{\{[a-zA-Z0-9_]*\}\}/i', $default_sub, $buffer);
    };
    $buffer = $str_template;
    // sort keys by +lenght first
    uksort($a_binds, function (string $a, string $b): int{
        $al = mb_strlen($a);
        $bl = mb_strlen($b);
        if ($al == $bl) {
            return 0;
        }
        return ($al < $bl) ? -1 : 1;
    });
    foreach ($a_binds as $name => $val) {
        // check that the value can be casted to string
        if (str_is_repr($val)) {
            $buffer = $_substitute($buffer, $name, $val);
        } elseif (is_obj($val)) {
            $obj = (object) $val;
            $val = sprintf('[object class %s]', get_class($obj));
            $buffer = $_substitute($buffer, $name, $val);
        }
    }
    $buffer = $_clean_unused_vars($buffer);
    return $buffer;
}
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
 * @param string|array<string> $m_re
 */
function str_replace_all(string $s_sub, $m_re, string $str): string {
    do {
        $str = str_replace($s_sub, $m_re, $str, $c);
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
    return $matches[3];
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

}
