<?php
//----------------------------------------------------------------------------
//  str_find
//----------------------------------------------------------------------------
// semplifica l'individuazione di almeno una occorrenza di una sottostringa
function str_match($str, $sub_str) {
    $result = mb_strpos($str, $sub_str);
    return ($result !== false) ? true : false;
}
// alias
function str_contains($str, $sub_str) {return str_match($str, $sub_str);}
function str_find($str, $sub_str) {return str_match($str, $sub_str);}
// semplifica l'individuazione del numero di occorrenze di una sottostringa
function str_count_matches($str, $sub_str) {
    $result = mb_strpos($str, $sub_str);
    return ($result === false) ? 0 : $result;
}
function str_begins_re($str, $substr, $ci = false): bool{
    $p = '/^' . $substr . ' /i';
    return preg_match($p, $str) > 0;
}
//
// @param str haystack
// var params needle
//
function str_begins_with() {
    $str = mb_strtolower(func_get_arg(0));
    for ($i = 1; $i < func_num_args(); $i++) {
        $substr = mb_strtolower(func_get_arg($i));
        if (str_begins($str, $substr)) {
            return true;
        }
    }
    return false;
}
//----------------------------------------------------------------------------
//  mb version
//----------------------------------------------------------------------------
// ritorna n caratteri di una str partendo da sinistra
function str_left($str, $length) {
    return mb_substr($str, 0, $length);
}
// ritorna n caratteri di una str partendo da destra
function str_right($str, $length) {
    return mb_substr($str, -$length);
}
function str_begins($str, $start) {
    $sub = mb_substr($str, 0, mb_strlen($start));
    return $sub === $start;
}
function str_ends($str, $end) {
    $el = mb_strlen($end);
    $sub = mb_substr($str, -$el, $el);
    return ($sub === $end);
}
//----------------------------------------------------------------------------
//
//----------------------------------------------------------------------------
// $string = str_end_remove($str='picture.jpg.jpg', '.jpg');//=> 'picture.jpg'
function str_end_remove($string, $s_end) {
    if (str_ends($string, $s_end)) {
        $end_l = mb_strlen($s_end);
        $str_l = mb_strlen($string);
        $pos = $str_l - $end_l;
        $out = mb_substr($string, 0, $pos);
        return $out;
    }
}
//   str_between("0012345678900", "00", "00"); // => 345678
function str_between($input, $start, $end) {
    $substr = mb_substr($input, mb_strlen($start) + mb_strpos($input, $start), (mb_strlen($input) - mb_strpos($input, $end)) * (-1));
    return $substr;
}
//  trim an optional trailing slash off the end of a path:
// if ( mb_substr( $path, -1 ) == '/') $path = mb_substr( $path, 0, -1 );
function path_canonical($path) {return str_end_remove($apth, '/');}
//----------------------------------------------------------------------------
//
//----------------------------------------------------------------------------
// toglie qualunque chr non sia una lettera latina o un numero
function str_clean($s) {
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
function str_clean_non_printable($str) {
    $str = mb_ereg_replace('/[[:^print:]]/', '', $str);
    return $str;
}
// toglie i whitespace
function str_clean_w($s) {
    return mb_ereg_replace(['/\r\n|\n|\r|\t|\s\s/'], '', $s);
}
//Remove inside spaces when more than 1
function trim_ws($str) {
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
function bool2str($var) {
    if (mb_strtoupper($var) == 'FALSE') {
        $var = FALSE;
    }
    return $var ? 'TRUE' : 'FALSE';
}
//
// Removes line breaks
//
function str_oneline($string) {
    $string = mb_ereg_replace('/\t/', ' ', $string);
    $string = mb_ereg_replace('/\r?\n/', ' ', $string);
    $string = mb_ereg_replace('/\s{2,}/', ' ', $string);
    return $string;
}
// trying to insert a string into a utf8 mysql table.
// The string (and its bytes) all conformed to utf8, but had several bad sequences.
// I assume that most of them were control or formatting.
function str_clean_utf8($string) {
    $s = trim($string);
    $s = iconv("UTF-8", "UTF-8//IGNORE", $s); // drop all non utf-8 characters
    // this is some bad utf-8 byte sequence that makes mysql complain - control and formatting i think
    $s = mb_ereg_replace('/(?>[\x00-\x1F]|\xC2[\x80-\x9F]|\xE2[\x80-\x8F]{2}|\xE2\x80[\xA4-\xA8]|\xE2\x81[\x9F-\xAF])/', ' ', $s);
    $s = mb_ereg_replace('/\s+/', ' ', $s); // reduce all multiple whitespace to a single space
    return $s;
}
// strip by regexp, specify what you want to include
function str_clean_r(string $s,
    $opt_chars = "`_.,;@#%~'\+\*\?\[\^\]\$\(\)\{\}\=\!\<\>\|\:\-\\\s",
    array $permit_chars = []
): string {
// '"
    return mb_ereg_replace("/[^A-Z0-9$opt_chars]+/i", '', $s);
}
function str_replace_last($what, $with_what, $where) {
    $tmp_pos = mb_strrpos($where, $what);
    if ($tmp_pos !== false) {
        $where = mb_substr($where, 0, $tmp_pos) . $with_what . mb_substr($where, $tmp_pos + mb_strlen($what));
    }
    return $where;
}
// mostra solo n char di un testo lungo, evitando di spezzare le parole
// brutalmente, ma non fa nulla di particolare per funzionare con html
function str_reminder($str, $maxlen = 50, $suffisso = ' [...] ') {
    if (mb_strlen($str) > $maxlen) {
        $result = '';
        $str = mb_str_replace('  ', ' ', $str);
        $a = explode(' ', mb_substr($str, 0, $maxlen + 10)); // per migliorare le prestazioni vado a fare l'explode di una stringa ragionevolmente ridimensionata
        for ($i = 0; $i < count($a); $i++) {
            if (mb_strlen($result . $a[$i] . ' ') < $maxlen) {
                $result .= $a[$i] . ' ';
            } else {
                break;
            }
        }
        return trim($result) . ' ' . $suffisso;
    } else {
        return $str;
    }
}
/**
 * Truncates text
 *
 * Cuts a string to the length of <i>$length</i> and replaces the last characters
 * with the ending if the text is longer than length.
 * Function from CakePHP
 *
 * @license Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * @param string $text         String to truncate
 * @param int    $length       Length of returned string, including ellipsis
 * @param string $ending       Ending to be appended to the trimmed string
 * @param bool   $exact        If <b>false</b>, $text will not be cut mid-word
 * @param bool   $considerHtml If <b>true</b>, HTML tags would be handled correctly
 *
 * @return string Truncated string
 */
function truncate($text, $length = 1024, $ending = '...', $exact = false, $considerHtml = true) {
    $open_tags = [];
    if ($considerHtml) {
        // if the plain text is shorter than the maximum length, return the whole text
        if (mb_strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
            return $text;
        }
        // splits all html-tags to scanable lines
        preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
        $total_length = mb_strlen($ending);
        $truncate = '';
        foreach ($lines as $line_matchings) {
            // if there is any html-tag in this line, handle it and add it (uncounted) to the output
            if (!empty($line_matchings[1])) {
                // if it's an "empty element" with or without xhtml-conform closing slash (f.e. <br/>)
                if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|col|frame|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
                    // do nothing
                    // if tag is a closing tag (f.e. </b>)
                } else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
                    // delete tag from $open_tags list
                    $pos = array_search($tag_matchings[1], $open_tags);
                    if ($pos !== false) {
                        unset($open_tags[$pos]);
                    }
                    // if tag is an opening tag (f.e. <b>)
                } else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
                    // add tag to the beginning of $open_tags list
                    array_unshift($open_tags, mb_strtolower($tag_matchings[1]));
                }
                // add html-tag to $truncate'd text
                $truncate .= $line_matchings[1];
            }
            // calculate the length of the plain text part of the line; handle entities as one character
            $content_length = mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
            if ($total_length + $content_length > $length) {
                // the number of characters which are left
                $left = $length - $total_length;
                $entities_length = 0;
                // search for html entities
                if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) {
                    // calculate the real length of all entities in the legal range
                    foreach ($entities[0] as $entity) {
                        if ($entity[1] + 1 - $entities_length <= $left) {
                            $left--;
                            $entities_length += mb_strlen($entity[0]);
                        } else {
                            // no more characters left
                            break;
                        }
                    }
                }
                $truncate .= mb_substr($line_matchings[2], 0, $left + $entities_length);
                // maximum length is reached, so get off the loop
                break;
            } else {
                $truncate .= $line_matchings[2];
                $total_length += $content_length;
            }
            // if the maximum length is reached, get off the loop
            if ($total_length >= $length) {
                break;
            }
        }
    } else {
        if (mb_strlen($text) <= $length) {
            return $text;
        } else {
            $truncate = mb_substr($text, 0, $length - mb_strlen($ending));
        }
    }
    // if the words shouldn't be cut in the middle...
    if (!$exact) {
        // ...search the last occurrence of a space...
        $spacepos = mb_strrpos($truncate, ' ');
        if (isset($spacepos)) {
            // ...and cut the text in this position
            $truncate = mb_substr($truncate, 0, $spacepos);
        }
    }
    // add the defined ending to the text
    $truncate .= $ending;
    if ($considerHtml) {
        // close all unclosed html-tags
        foreach ($open_tags as $tag) {
            $truncate .= "</$tag>";
        }
    }
    return $truncate;
}
/**
 * Search for links inside html attributes
 *
 * @param string $text
 *
 * @return string[] Array of found links or empty array otherwise
 */
function find_links($text) {
    preg_match_all('/"(http[s]?:\/\/.*)"/Uims', $text, $links);
    return $links[1] ?: [];
}
/**
 * Returns of direct output of given function
 *
 * @param callable $callback
 *
 * @return string
 */
function ob_wrapper($callback) {
    ob_start();
    $callback();
    return ob_get_clean();
}
// restituisce stringhe descrizione molto lunghe in array di n substr
function split_multilines($item_descr, $max_len = 40) {
    $item_descr = trim($item_descr);
    $item_descr = mb_str_replace($sub = '  ', $re = ' ', $item_descr);
    $len = mb_strlen($item_descr);
    if ($len < $max_len) {
        return [$item_descr];
    } else {
        $a_str = explode(' ', $item_descr);
        $res = [];
        $idx = 0;
        foreach ($a_str as $_i => $str) {
            if (!isset($res[$idx])) {
                $res[$idx] = $str;
                continue;
            }
            // +1 dello spazio di separazione
            $new_len = (mb_strlen($res[$idx]) + 1 + mb_strlen($str));
            $max_len_c = ($idx + 1) * $max_len; //current maxlen
            if ($new_len <= $max_len_c) {
                $res[$idx] .= " $str";
            } else {
                $idx++; // next index
                if (isset($res[$idx])) {
                    $res[$idx] .= " $str";
                } else {
                    $res[$idx] = $str;
                }
            }
        }
        $res = array_map(function ($val) {return trim($val);}, $res);
        return $res;
    }
};
function capwords($str) {
    $a_words = preg_split('/[-_. ]/', $str);
    $a_words = array_map('ucfirst', array_map('mb_strtolower', $a_words));
    $capped_str = join('', $a_words);
    if (!empty($capped_str)) {
        $capped_str[0] = mb_strtoupper($capped_str[0]);
    }
    return $capped_str;
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
    public static function mkPassword($len = 10, $min_num_len = 1, $len_sign = 1) {
        $str = '';
        $str .= self::generate($len, self::ALPHA);
        $str .= self::generate($min_num_len, self::NUM); // aggiunge caratteri di punteggiatura
        $str .= self::generate($len_sign, self::SIGN); // aggiunge caratteri di punteggiatura
        return $str;
    }
    // random, human readable string, good for password, captcha and other codes
    // $readable esclude i caratteri che potrebbero essere confusi, come i,l,1,I,0,o,O
    function mkPasswordReadable($len = 10, $flg_strength = 'nVA!', $readable = true) {
        // esclusi i caratteri che potrebbero essere confusi, come i,l,1,I oppure 0 e o/O
        $str_dict = 'bdghjmnpqrstvz';
        $vowels = 'aeuy';
        if (!$readable) {
            $str_dict .= 'lo';
            $vowels .= 'io';
        }
        if (str_match($str, 'V')) {
            $vowels .= 'AEUY';
        }
        if (str_match($str, 'n')) {
            $str_dict .= '23456789';
        }
        if (str_match($str, 'A')) {
            $str_dict .= 'BDGHJLMNPQRSTVWXZ';
        }
        if (str_match($str, '!')) {
            $str_dict .= '!@#$%';
        }
        $password = '';
        // alterna una vocale e una consonante
        // TODO far in modo che i caratteri numero e simbolo siano stamapti assieme per
        // facilitare la digitazione dalla tastiera
        $alt = time() % 2;
        for ($i = 0; $i < $len; $i++) {
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
    public static function generate($len, $dict = 'ABCDEFGHIJKLMNPQRSTUVWXYZ0123456789') {
        $dict_len = mb_strlen($dict);
        $str = '';
        for ($i = 0; $i < $len; $i++) {
            $pos = rand(0, $dict_len - 1);
            $str .= $dict{$pos};
        }
        return $str;
    }
    // random pad
    public static function pad($str, $len) {
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
    function mb_str_split($string, $en = 'UTF-8') {
        $l = mb_strlen($string, $en);
        $ret = [];
        for ($i = 0; $i < $l; $i++) {
            $ret[] = mb_substr($string, $i, 1, $en);
        }
    }
    function mb_strrev($str) {
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
    function password_check($user, $pass) {
        $lc_pass = mb_strtolower($pass);
        // also check password with numbers or punctuation subbed for letters
        $denum_pass = mb_strtr($lc_pass, '5301!', 'seoll');
        $lc_user = mb_strtolower($user);
        // the password must be at least six characters
        if (mb_strlen($pass) < 6) {
            return 'The password is too short.';
        }
        // the password can't be the username (or reversed username)
        if (($lc_pass == $lc_user) || ($lc_pass == mb_strrev($lc_user)) ||
            ($denum_pass == $lc_user) || ($denum_pass == mb_strrev($lc_user))) {
            return 'The password is based on the username.';
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
            return "The password has too many upper case characters.";
        }
        if ($lc > $max) {
            return "The password has too many lower case characters.";
        }
        if ($num > $max) {
            return "The password has too many numeral characters.";
        }
        if ($other > $max) {
            return "The password has too many special characters.";
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
                    return 'The password is based on a dictionary word.';
                }
            }
        }
        return false;
    }
}
// genera lista caratteri da a-z
function get_all_chars() {
    $a_characters = array_merge(range('a', 'z'), range('A', 'A'));
    return $a_characters;
}
//----------------------------------------------------------------------------
//
//----------------------------------------------------------------------------
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
function str_transliterate($str) {
    if (function_exists('iconv')) {
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $str);
    } else {
        $text = str_rm_diacritics($text);
    }
    return $text;
}
// transliterate from utf-8 a ascii
function str_to_ascii($s) {
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
function str_rm_nonascii($str) {
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
function UTF8_is($str) {
    $length = mb_strlen($str);
    for ($i = 0; $i < $length; $i++) {
        if (ord($Str[$i]) < 0x80) {
            continue;
        } elseif ((ord($Str[$i]) & 0xE0) == 0xC0) {
            $n = 1;
        } elseif ((ord($Str[$i]) & 0xF0) == 0xE0) {
            $n = 2;
        } elseif ((ord($Str[$i]) & 0xF8) == 0xF0) {
            $n = 3;
        } elseif ((ord($Str[$i]) & 0xFC) == 0xF8) {
            $n = 4;
        } elseif ((ord($Str[$i]) & 0xFE) == 0xFC) {
            $n = 5;
        } else {
            return FALSE;
        }
        for ($j = 0; $j < $n; $j++) {
            if ((++$i == $length) || ((ord($Str[$i]) & 0xC0) != 0x80)) {
                return FALSE;
            }
        }
    }
    return TRUE;
}
// in ASCII str remove uncommon printable chars, depending on font may not appear in terminals
function str_asci_simplify($str) {
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
function str_to_html($var) {
    return htmlentities($var, ENT_QUOTES, 'UTF-8');
}
//
// assicura che gli utenti non possano iniettare HTML(e quindi js) dalle variabili
// passate in GET/POST
//
function str_escape($input) {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}
function e($string) {
    return str_replace("\0", "&#0;", htmlspecialchars($string, ENT_QUOTES, 'utf-8'));
}
//----------------------------------------------------------------------------
//
//----------------------------------------------------------------------------
// code derived from http://php.vrana.cz/vytvoreni-pratelskeho-url.php
function str_slugify($text, $word_delimiter = '-') {
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
function str_camelize($id) {
    return strtr(
        ucwords(
            strtr(
                $id,
                ['_' => ' ', '.' => '_ ', '\\' => '_ ']
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
function str_interpolate($tmpl, array $a_binds = []) {
    // build a replacement array with braces around the context keys
    $a_repl = [];
    foreach ($a_binds as $key => $val) {
        // check that the value can be casted to string
        if (str_is_repr($val)) {
            $a_repl["{{$key}}"] = $val;
        } elseif (is_obj($val)) {
            $a_repl["{{$key}}"] = sprintf('[object class %s]', get_class($val));
        }
    }
    return mb_strtr($tmpl, $a_repl);
}
// check input can be converted to str
function str_is_repr($val): bool {
    return !is_array($val) && (!is_object($val) || method_exists($val, '__toString'));
}
//----------------------------------------------------------------------------
// templates
//----------------------------------------------------------------------------
// data una stringa interpola i valori passati in this->binds nei segnaposto
// espressi con la sintassi {{nome_var}}
function str_template($str_template, array $a_binds, $default_sub = '__') {
    $_substitute = function ($buffer, $name, $val) {
        $reg = sprintf('{{%s}}', $name);
        $reg = preg_quote($reg, '/');
        return mb_ereg_replace('/' . $reg . '/i', $val, $buffer);
    };
    $_clean_unused_vars = function ($buffer) use ($default_sub) {
        return mb_ereg_replace('/\{\{[a-zA-Z0-9_]*\}\}/i', $default_sub, $buffer);
    };
    $buffer = $str_template;
    // sort keys by +lenght first
    uksort($a_binds, function ($a, $b) {
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
            $val = sprintf('[object class %s]', get_class($val));
            $buffer = $_substitute($buffer, $name, $val);
        }
    }
    $buffer = $_clean_unused_vars($buffer);
    return $buffer;
}
// aggiunge la codifica dei caratteri per output HTML
function html_template($str_template, array $a_binds, $default_sub = '__') {
    // xss mitigation functions
    $xss = function ($str) {
        return htmlspecialchars($str, ENT_QUOTES | ENT_HTML401, $encoding = 'UTF-8');
    };
    // prevent cross-site scripting attacks (XSS) escaping values
    // escape all by default, skip vars names beginning with '_'
    $_sanitizer = function ($name, $val) use ($xss) {
        $name_begins_with_underscore = mb_substr($name, 0, 1) == '_';
        return $name_begins_with_underscore ? $val : $xss($val);
    };
    $a_binds_sanitized = array_map(function ($k, $v) {
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
    return mb_ereg_replace(array_keys($a_binds), array_values($a_binds), $str);
}
// dato un array associativo di variabili da interpolare, esegue la sostituzione
// $a_replace = [
//     'apple' => 'orange'
//     'chevy' => 'ford'
// ];
function str_a_replace(array $a_binds, $str) {
    return mb_str_replace(array_keys($a_binds), array_values($a_binds), $str);
}
// sostituzione gestendo array di stringhe in input
function str_replace_deep($search, $replace, $a_str) {
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
function str_money($s) {
    $v = str_to_float($s);
    return Money::format($v);
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
            $s = mb_str_replace('.', '', $s);
            $s = mb_str_replace(',', '.', $s);
        }
    }
    return (float) $s;
}
// usa nuova estensione senza '.'
function str_extension_replace($filename, $new_extension) {
    // alternatives:
    //   $info = pathinfo($filename);
    //   return $info['filename'] . '.' . $new_extension;
    //   return mb_ereg_replace('/\..+$/', '.' . $new_extension, $filename);
    return mb_substr_replace($filename, $new_extension,
        1 + mb_strrpos($filename, '.')
    );
}
// human readable per le funzioni memory_get_peak_usage() / memory_get_usage()
function format_bytes($bytes_size, $precision = 2) {
    $base = log($bytes_size) / log(1024);
    $suffixes = ['', 'k', 'M', 'G', 'T'];
    $b = pow(1024, $base - floor($base));
    $suffix = $suffixes[floor($base)];
    return round($b, $precision) . $suffix;
}
//
function format_time($secs) {
    static $timeFormats = array(
        array(0, '< 1 sec'),
        array(2, '1 sec'),
        array(59, 'secs', 1),
        array(60, '1 min'),
        array(3600, 'mins', 60),
        array(5400, '1 hr'),
        array(86400, 'hrs', 3600),
        array(129600, '1 day'),
        array(604800, 'days', 86400),
    );
    foreach ($timeFormats as $format) {
        if ($secs >= $format[0]) {
            continue;
        }
        if (2 == count($format)) {
            return $format[1];
        }
        return ceil($secs / $format[2]) . ' ' . $format[1];
    }
}
// data una grandezza in una unità specifica, ritorna la grandezza in bytes
function format_bytes_size($size = 0, $unit = 'B') {
    $unit = mb_strtoupper($unit);
    $a_units = ['B' => 0, 'KB' => 1, 'MB' => 2, 'GB' => 3, 'TB' => 4, 'PB' => 5, 'EB' => 6, 'ZB' => 7, 'YB' => 8];
    if (!in_array($str_unit, array_keys($a_units))) {
        return false;
    }
    if (!intval($size) < 0) {
        return false;
    }
    $b_unit = pow(1024, $a_units[$str_unit]);
    return $size * $b_unit;
}
function format_bytes_str($str) {
    $str_unit = trim(substr($str, -2));
    $str_unit = mb_strtoupper($str_unit);
    if (intval($str_unit) !== 0) {
        $str_unit = 'B';
    }
    $a_units = ['B' => 0, 'KB' => 1, 'MB' => 2, 'GB' => 3, 'TB' => 4, 'PB' => 5, 'EB' => 6, 'ZB' => 7, 'YB' => 8];
    if (!in_array($str_unit, array_keys($a_units))) {
        return false;
    }
    $size = trim(substr($str, 0, mb_strlen($str) - 2));
    if (!intval($size) == $size) {
        return false;
    }
    $b_unit = pow(1024, $a_units[$str_unit]);
    return $size * $b_unit;
}
// trova un tag con un particolare attributo
function get_by_tag_att($attr, $value, $xml, $tag = null) {
    if (is_null($tag)) {
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
function text_auto_link($text) {
    $text = mb_ereg_replace("/([a-zA-Z]+:\/\/[a-z0-9\_\.\-]+" . "[a-z]{2,6}[a-zA-Z0-9\/\*\-\_\?\&\%\=\,\+\.]+)/", " <a href=\"$1\" target=\"_blank\">$1</a>", $text);
    $text = mb_ereg_replace("/[^a-z]+[^:\/\/](www\." . "[^\.]+[\w][\.|\/][a-zA-Z0-9\/\*\-\_\?\&\%\=\,\+\.]+)/", " <a href=\"\" target=\"\">$1</a>", $text);
    $text = mb_ereg_replace("/([\s|\,\>])([a-zA-Z][a-zA-Z0-9\_\.\-]*[a-z" . "A-Z]*\@[a-zA-Z][a-zA-Z0-9\_\.\-]*[a-zA-Z]{2,6})" . "([A-Za-z0-9\!\?\@\#\$\%\^\&\*\(\)\_\-\=\+]*)" . "([\s|\.|\,\<])/i", "$1<a href=\"mailto:$2$3\">$2</a>$4", $text);
    return $text;
}
// $a_links = text_link_extract($page);
function text_link_extract($s) {
    $a = [];
    if (preg_match_all('/<a\s+.*?href=[\"\']?([^\"\' >]*)[\"\']?[^>]*>(.*?)<\/a>/i',
        $s, $matches, PREG_SET_ORDER)
    ) {
        foreach ($matches as $match) {
            array_push($a, [$match[1], $match[2]]);
        }
    }
    return $a;
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
function str_highlight($str, $search = null, $replacement = '<em>${0}</em>') {
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
    private static function validate($str) {
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
    private static function filterStr($str) {
        return trim(htmlspecialchars(strip_tags($str)));
    }
    // increment $char by 1
    private static function nextCharacter($char) {
        if ($char == '9') {
            return 'a';
        } elseif ($char == 'Z' || $char == 'z') {
            return '0';
        } else {
            return chr(ord($char) + 1);
        }
    }
    // return new sequencially incremented string
    private static function nextSequence($str) {
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
    public static function increment($str, $offset = 1) {
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
function str2int($val) {
    return intval(preg_replace('~[^0-9]+~', '', $val));
}
function str2float($val) {
    return floatval(preg_replace('~[^0-9\.\,]+~', '', $val));
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
    case 6/* PREG_JIT_STACKLIMIT_ERROR */:
        return 'JIT stack space limit exceeded';
    default:
        return 'Unknown error';
    }
}
function _pcre_check_last_error(): void{
    $error = \preg_last_error();
    if ($error !== \PREG_NO_ERROR) {
        throw new PCREException(_pcre_get_error_message($error), $error);
    }
}
//----------------------------------------------------------------------------
// main tests
//----------------------------------------------------------------------------
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/Test.php';
    $ss = str_slugify("This is just a small test for a slug creation");
    ok($ss, 'this-is-just-a-small-test-for-a-slug-creation');

    $r = str_template('second: {{second}}; first: {{first}}', [
        'first' => '1st',
        'second' => '2nd',
    ]);
    $e = 'second: 2nd; first: 1st';
    is($r, $e, 'str_template');

    $s = str_replace_last('.', ".bb.", '.....aaaa.exe');
    is($s, ".....aaaa.bb.exe", "str_replace_last");
}

