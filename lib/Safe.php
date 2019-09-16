<?php
// processa i parametri in input prima delle query
class Safe {
    // toglie caratteri pericolosi da un input che debba essere processato con SQL
    // Safe::str($s);
    public static function sanitize($s, $len = 0, $regexp = '/[^a-z0-9\-\_]/i') {
        $s = preg_replace($regexp, '', $s);
        $s = self::chop($s, $len);
        return $s;
    }
    // definire una lunghezza massima della stringa
    public static function chop($str, $len = 256) {
        // opzionalmente applica troncamento per lunghezza
        if (!empty($len)) {
            if (class_exists('Bootstrap', $autoload = false)) {
                if (!Bootstrap::EnvIsProd()) {
                    $c_len = mb_strlen($str);
                    if ($c_len > $len) {
                        $msg = sprintf('Errore: lunghezza parametro eccede la configurazione in %s, %s>%s str="%s"  ', __METHOD__, $c_len, $len, $str);
                        throw new \InvalidArgumentException($msg);
                    }
                }
            }
            return mb_substr($str, 0, $len);
        } else {
            return $str;
        }
    }
    //------------------------------------------------------------------------------
    //
    public static function quote($s) {
        // ENT_QUOTES => entrambe ' e " convertiti
        $s = htmlspecialchars($s, ENT_QUOTES | ENT_HTML401, $encoding = 'UTF-8');
        return $s;
    }
    // da HTML quoted( &#039; &quot; ) a straight text
    public static function unquote($str) {
        // nel db ho scritto le html entities
        $str = htmlspecialchars_decode($str, ENT_QUOTES | ENT_HTML401);
        return $str;
    }
    // rimuovi caratteri non compatibili con AS400 encoding
    /* prod:
    DSPSYSVAL QCCSID
    Identificativo serie
    caratteri codificati :   280        1-65535
    DSPFD LA_DAT.ZNTCT00F
    CCSID (Coded character set identifier). . . : CCSID 280
     */
    public static function utf8_to_CCSID280($str) {
        /*
        $c_map = [
        '€' => 'EUR',
        'À' => 'A',
        'Á' => 'A',
        'Â' => 'A',
        'Ã' => 'A',
        'Ä' => 'A',
        'Å' => 'A',
        'È' => 'E',
        'É' => 'E',
        'Ê' => 'E',
        'Ë' => 'E',
        'Ì' => 'I',
        'Í' => 'I',
        'Î' => 'I',
        'Ï' => 'I',
        'Ò' => 'O',
        'Ó' => 'O',
        'Ô' => 'O',
        'Õ' => 'O',
        'Ö' => 'O',
        'Ù' => 'U',
        'Ú' => 'U',
        'Û' => 'U',
        'Ü' => 'U',
        ];
        $str = str_replace( array_keys($c_map), array_values($c_map), $str);
        // remove highers and uppers
        $str = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '?', $str);
        return $str;
         */
        if (!function_exists('iconv')) {
            die('installare iconv');
        }
        $str = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        return $str;
    }
    //----------------------------------------------------------------------------
    //  strings
    //----------------------------------------------------------------------------
    // stringhe estese
    public static function str($str, $len = 20, $regexp = '/[^a-z0-9\_\-\+\.\/,\s]/i') {
        return self::sanitize($str, $len, $regexp);
    }
    // only alpha and numeric
    public static function alphanum($str, $len = 20, $regexp = '/[^a-z0-9\-\_]/i') {
        return self::sanitize($str, $len, $regexp);
    }
    // richiesta: Aggiungere &euro;, apostrofo, caratteri accentati
    //
    // test cases:
    // includere sconto 50%
    // come ordine del'altra volta
    // 10euro
    // perché però qualità più mercoledì
    // &euro;
    //
    public static function text($s, $len = 256, $s_additional = '', $s_replace = ' ') {
        // opzionalmente applica troncamento per lunghezza
        // questo prima di trasformare le entity, che hanno lunghezza maggiore di 1 del char originale
        if ($len > 1) {
            $s = self::chop($s, $len);
        }
        $s = self::quote($s); // entity x ' & " e altri caratteri difficoltosi
        // questo elimina òàùèì
        // $s = filter_var($s, FILTER_SANITIZE_STRING,
        //     FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH
        // );
        //
        // $char_list = 'àèéìòù€_,;:!.-+*/()[]?=@%&#';
        // $s_escaped = implode('', array_map(function($c) {
        //     return '\\'.$c;
        // }, mb_split( '//u', $char_list,1) ) );
        $s_escaped = '\à\è\é\ì\ò\ù\€\_\,\;\:\!\.\-\+\*\/\(\)\[\]\?\=\@\%\&\#';
        $s_escaped .= $s_additional;
        // consider allowing \s => \n\t\r
        $regexp = '/[^0-9A-Z' . $s_escaped . ' \n]/iu';
        $s = preg_replace($regexp, $s_replace, $s);
        return $s;
    }
    // specifica per il tipo flag
    public static function flag($s, $len = 3) {
        $s = strtoupper($s);
        $s = self::chop($s, $len);
        $s = preg_replace('/[^a-z0-9_]/i', '_', $s);
        return $s;
    }
    //----------------------------------------------------------------------------
    //  numerics
    //----------------------------------------------------------------------------
    // gestisce numeri espressi come float as400, ',' viene convertito a '.'
    public static function num($s, $int_len = 12, $dec_len = 2, $def = '0.0') {
        $s = trim($s);
        $len = $int_len + 1 + $dec_len; // lunghezza stringa totale, 1 per il '.'
        $s = str_replace(',', '.', $s);
        $s = preg_replace('/[^0-9\.\-]/i', '', $s);
        // elimina input eccessivo
        $s = self::chop($s, $len);
        $s = coalesce($s, $def);
        return $s;
    }
    public static function int($s, $default = 0) {
        $len = 15;
        $s = preg_replace('/[^0-9]/i', '', $s);
        // elimina input eccessivo
        $s = self::chop($s, $len);
        $s = intval($s);
        return is_numeric($s) ? $s : $default;
    }
    // int left zero padded
    // es. 000001
    // non fare cast a int
    public static function int_lzp($str, $len = 6) {
        // correct form: 000001
        $_zpl = function ($i) use ($len) {
            return str_pad($i, $len, '0', STR_PAD_LEFT);
        };
        $str = self::int($str, 0); // get just the numeric part
        $str = $_zpl($str);
        return $str;
    }
    //
    public static function dec($num, $default = 0) {
        $num1 = preg_replace('/[^0-9\,\.]/i', '', $num);
        $num2 = str_replace($sub = ',', $re = '.', $num);
        $num3 = empty($num2) ? $default : $num2;
        return $num3;
    }
    // TODO: spostare in Validate
    // warning se parametro errato
    public static function intW($s, $name = '', $len = 20) {
        $s = self::chop($s, $len);
        $s = filter_var($s, FILTER_SANITIZE_NUMBER_INT);
        if (false === $s) {
            $msg = "validation param $name";
            throw new \Exception($msg);
        }
        return $s;
    }
    // ripulisce email address
    // email multiple nello stesso campo
    public static function email($email) {
        $_clean_mail = function ($email) {
            $email = trim(Safe::text($email));
            return filter_var($email, FILTER_SANITIZE_EMAIL);
        };
        $email = trim($email);
        if (str_contains($email, ';')) {
            $a_m = explode(';', $email);
            $a_m2 = array_map($_clean_mail, $a_m);
            // togli garbage
            $a_m3 = array_filter($a_m2, function ($s) {return !empty($s);});
            $a_m3 = array_filter($a_m3, function ($email) {
                // !empty($s);
                return filter_var($email, FILTER_VALIDATE_EMAIL);
            });
            // elimina ripetute
            $a_m3 = array_unique($a_m3, SORT_STRING);
            return implode($sep = ';', $a_m3);
        } else {
            return $_clean_mail($email);
        }
    }
    // ripulisce tel address
    public static function tel($tel, $len = 17) {
        $tel = preg_replace('/[^0-9\+\s]/i', '', $tel);
        $tel = str_replace($sub = '  ', $re = ' ', $tel); // replace duble ' '
        $tel = trim($tel);
        $tel = self::chop($tel, $len);
        return $tel;
    }
    // upper alphanumeric plus '-'
    public static function key($s, $default = '', $len = 50) {
        $s = preg_replace('/[^A-Z0-9\-\_]/', '', $s);
        // elimina input eccessivo
        $s = self::chop($s, $len);
        return $s ? $s : $default;
    }
    //----------------------------------------------------------------------------
    //  array
    //----------------------------------------------------------------------------
    /*
    $type = Safe::whitelist($type, $a_valid_types = [
    self::MAILEXT_CONFERMA_ORD, // ConfermaOrdine
    self::MAILEXT_BOLLA, // Bolle
    self::MAILEXT_FATTURA, // Fatture
    self::MAILEXT_PEC, // PEC
    ], $def = null);
    if (empty($type)) {
    $msg = sprintf('Errore type cant be empty "%s" ', $type);
    throw new \Exception($msg);
    }
     */
    public static function whitelist($str, array $a_wl, $def = null) {
        // se def non è specificato è il primo valore della lista di valori validi
        if (is_null($def)) {
            $def = $a_wl[0];
        }
        if (in_array($str, $a_wl)) {
            return $str;
        } else {
            return $def;
        }
    }
    // filtra un array in input con i filtri proposti,
    // solo i valori che hanno un filtro passano
    //   $_safe_an = function($v) {return Safe::alphanum($v); };
    //   $_safe_int = function($v) {return Safe::int($v); };
    //   $_safe_txt = function($v) {return Safe::text($v); };
    //   $a_fields_val = [
    //       'name' => $_safe_an,
    //       'note'  => $_safe_txt,
    //       'destination_id'  => $_safe_int,
    //   ],
    //   $a_data = Safe::hash( $a_data, $a_fields_val );
    // TODO: dare una funzione di default per i campi non validati
    public static function hash(array $a_data, array $a_fields_val) {
        $a_data_f = [];
        foreach ($a_fields_val as $k => $_filter) {
            if (isset($a_data[$k])) {
                $v = $a_data[$k];
            } else {
                continue;
            }
            $a_data_f[$k] = $_filter($v);
        }
        return $a_data_f;
    }
    // filtra un array per un filtro
    // array e list sono parole riservete in php5.6
    public static function _list(array $a_data, $_filter = null) {
        $_filter = $_filter ? $_filter : function ($v) {
            return filter_var($v, FILTER_SANITIZE_NUMBER_INT);
        };
        $a_data_f = array_map(function ($val) use ($_filter) {
            return $_filter($var);
        }, $a_data);
        return $a_data_f;
    }
    //----------------------------------------------------------------------------
    //  app specific
    //----------------------------------------------------------------------------
    // ...
}
// Never Trust User Input
// wrapper over filter_var(FILTER_SANITIZE_*)
// rimuove input pericoloso
//
//
// * SQL unescaped => usa prepared statements
// * XSS es. $_SERVER['PHP_SELF'].'/<script>alert("XSS HERE");</script>';
//   => usa Filter::clean_s($s) o Filter::clean_url
// @see filtri sanitizzazione http://it1.php.net/manual/it/filter.filters.sanitize.php
// @see https://github.com/ircmaxell/filterus
class Filter {
    // la cosa più semplice è definire una lunghezza massima della stringa
    public static function chop($var, $len) {
        return substr($var, 0, $len);
    }
    // * applica filtro e lungheza
    // * Strip tags, optionally strip or encode special characters.
    public static function clean_s($var, $len = -1) {
        if (-1 === $len) {
            return filter_var($var, FILTER_SANITIZE_STRING);
        } else {
            $var = self::chop($var, $len);
            return filter_var($var, FILTER_SANITIZE_STRING);
        }
    }
    // xss mitigation functions
    public static function xss_safe($str, $encoding = 'UTF-8') {
        return htmlspecialchars($str, ENT_QUOTES | ENT_HTML401, $encoding);
    }
    // rimuove char non stampabili, fuori intervallo 32...127
    public static function ascii($s) {
        return filter_var($s, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    }
    // un intero controllato
    public static function int($int, $max = PHP_INT_MAX, $min = 0) {
        return (int) filter_var($int, FILTER_SANITIZE_NUMBER_INT, ["min_range" => $min, "max_range" => $max]);
    }
    // convertono a true: 1 "1" "yes" "true" "on" TRUE
    // convertono a false: 0 "0" "no" "false" "off" "" NULL FALSE
    public static function bool($bool, $d = false) {
        $is = filter_var($bool, FILTER_VALIDATE_BOOLEAN);
        return $is ? (boolean) $bool : $d;
    }
    //
    public static function float($float, $max = 1, $min = 0) {
        return filter_var($float, FILTER_SANITIZE_NUMBER_FLOAT, ["min_range" => $min, "max_range" => $max]);
    }
    // rende più sicura una url
    // esempio:
    //     in:  "http://test.org/a dir!/file.php?foo=1&bar=2"
    //     out: http%3A%2F%2Ftest.org%2Fa%20dir%21%2Ffile.php%3Ffoo%3D1%26bar%3D2
    public static function url($url) {
        // FILTER_FLAG_STRIP_LOW => Any characters below ASCII 32 will be stripped from the URL
        return filter_var($url, FILTER_SANITIZE_URL, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    }
    // ritorna una stringa togliendo tutti i tags html
    // http://it2.php.net/htmlspecialchars
    public static function html($html) {
        $html = filter_var($html, FILTER_SANITIZE_STRING);
        return $html;
    }
}
// wrapper over http://htmlpurifier.org
// @see Yii CHtmlPurifier();
class FilterHTML {
    public static function purify($html) {
        //require_once '/path/to/HTMLPurifier.auto.php';
        $config = HTMLPurifier_Config::createDefault();
        $p = new HTMLPurifier($config);
        $clean_html = $p->purify($dirty_html);
        // remove bad parsing
        $html = preg_replace('#\\\r\\\n|\\\r|\\\n|\\\#sui', '', $html);
        $p->options = [
            'HTML.Allowed' => 'img[src],p,br,b,strong,i',
        ];
        $html = $p->purify($html);
        $p->options = [
            'HTML.Allowed' => '',
        ];
        $text = $p->purify($html);
        if (mb_strlen($text, 'UTF-8') === mb_strlen($html, 'UTF-8')) {
            return '<pre>' . $text . '</pre>';
        }
        return $html;
    }
}
//
// printable char type
class PT {
    function all_alnum(string $s): bool {
        return $s === '' || \ctype_alnum($s);
    }
    function all_blank(string $s): bool{
        $l = \strlen($s);
        for ($i = 0; $i < $l; $i++) {
            $c = $s[$i];
            if ($c !== "\t" && $c !== " ") {
                return false;
            }
        }
        return true;
    }
    function all_alpha(string $s): bool {
        return $s === '' || \ctype_alpha($s);
    }
    function all_cntrl(string $s): bool {
        return $s === '' || \ctype_cntrl($s);
    }
    function all_digit(string $s): bool {
        return $s === '' || \ctype_digit($s);
    }
    function all_graph(string $s): bool {
        return $s === '' || \ctype_graph($s);
    }
    function all_lower(string $s): bool {
        return $s === '' || \ctype_lower($s);
    }
    function all_print(string $s): bool {
        return $s === '' || \ctype_print($s);
    }
    function all_punct(string $s): bool {
        return $s === '' || \ctype_punct($s);
    }
    function all_space(string $s): bool {
        return $s === '' || \ctype_space($s);
    }
    function all_upper(string $s): bool {
        return $s === '' || \ctype_upper($s);
    }
    function all_xdigit(string $s): bool {
        return $s === '' || \ctype_xdigit($s);
    }
    function is_alnum(string $s, int $i = 0): bool {
        return \ctype_alnum(char_at($s, $i));
    }
    function is_blank(string $s, int $i = 0): bool{
        $c = char_at($s, $i);
        return $c === ' ' || $c === "\t";
    }
    function is_alpha(string $s, int $i = 0): bool {
        return \ctype_alpha(char_at($s, $i));
    }
    function is_cntrl(string $s, int $i = 0): bool {
        return \ctype_cntrl(char_at($s, $i));
    }
    function is_digit(string $s, int $i = 0): bool {
        return \ctype_digit(char_at($s, $i));
    }
    function is_graph(string $s, int $i = 0): bool {
        return \ctype_graph(char_at($s, $i));
    }
    function is_lower(string $s, int $i = 0): bool {
        return \ctype_lower(char_at($s, $i));
    }
    function is_print(string $s, int $i = 0): bool {
        return \ctype_print(char_at($s, $i));
    }
    function is_punct(string $s, int $i = 0): bool {
        return \ctype_punct(char_at($s, $i));
    }
    function is_space(string $s, int $i = 0): bool {
        return \ctype_space(char_at($s, $i));
    }
    function is_upper(string $s, int $i = 0): bool {
        return \ctype_upper(char_at($s, $i));
    }
    function is_xdigit(string $s, int $i = 0): bool {
        return \ctype_xdigit(char_at($s, $i));
    }
}
//----------------------------------------------------------------------------
// tests
//----------------------------------------------------------------------------
// if colled directly in CLI, run the tests:
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/../../lib/DMS/functions.php';
    echo ok(Safe::str(''), $exp = '', '');
    echo ok(Safe::alphanum(''), $exp = '', '');
    echo ok(Safe::flag(''), $exp = '', '');
    echo ok(Safe::num(''), $exp = '', '');
    echo ok(Safe::int('1'), $exp = 1, 'int');
    echo ok(Safe::dec('1'), $exp = '1', 'dec');
    echo ok(Safe::email(''), $exp = '', 'email');
    echo ok(Safe::tel(''), $exp = '', 'tel');
    echo ok(Safe::whitelist('a', ['a'], 0), $exp = 'a', 'whitelist');
    echo ok(Safe::hash(['a' => 1], ['a' => function ($x) {return $x;}]), ['a' => 1], 'hash');
    // test str:  lunedì,;.?! 10%=2€ l'altra volta
    // test str:  !.,?:;@+-%=_# <> sconto 50% perché però qualità più mercoledì l'altra volta
    // altri caratteri bastardi:  '"^~`
    $a_in = [
        'punteggiatura !.,?:;@+-%=_' => null,
        '#' => null, // meta char, do not touch
        '<>' => '&lt;&gt;', //dangerous html
        'includere sconto 50%' => null,
        'perché però qualità più mercoledì' => null,
        'àèéìòù' => null,
        '10 €' => null, //euro
        // test " e '
        "come ordine dell'altra volta" => "come ordine dell&#039;altra volta",
        // "'\"" => '', //quotations not allowed
        '"citazione"' => '&quot;citazione&quot;', //
        //
        "multi\nline" => null,
        "\\" => '?',
        "\\\\" => '??',
        // json_decode("\u0001\u0002\u0003\u0004\u0005\u0006\u0007\b\u000e\u000f\u0010\u0011\u0012\u0013\u0014\u0015\u0016\u0017\u0018\u0019\u001a\u001b\u001c\u001d\u001e\u001f") => '',
        // json_decode('\u000b')=>'',
        // "" => '                               ',
        "\t\f" => '??',
        '^~`' => '???',
        chr(0) => '?',
        chr(13) => '?',
        // test string come concordata con minh
        "!.,?:;@+-%=_# <> sconto 50% perché però qualità più mercoledì l'altra volta €€" => '!.,?:;@+-%=_# &lt;&gt; sconto 50% perché però qualità più mercoledì l&#039;altra volta €€',
        // test chinese text
        "长达 天的等待。 自选举之日起，从未有过这样的意大利共和国历史，政府出生（尚未见过）。 在中间进行了五轮磋商，两次对分庭和参议院议长进行探索性任务，还有两项预先任务。 第一个 ，总理由 和 任命为 政府，第二个由 技术人员" => '?? ????? ?????????????????????????????????? ??????????????????????????????????????? ??? ???? ? ??? ??????? ????',
        '  È ' => null,
    ];
    foreach ($a_in as $str => $str_res) {
        $str_res = $str_res == null ? $str : $str_res;
        echo ok(Safe::text($str, $l = 999, $addit = '', $re = '?'), $str_res, 'safe_text:' . Safe::chop($str, 30000));
    }
    $str = "To start counting y'our letters"; // 36>35 str="To start counting y&#039;our letters"
    echo ok(Safe::text($str, $l = 35, $addit = '', $re = '?'), $str_res, 'text len');
    // test unquote
    $a_in = [
        // html in => txt out
        "come ordine dell&#039;altra volta" => "come ordine dell'altra volta",
        '&quot;citazione&quot;' => '"citazione"',
        '10 € e poi' => '10 € e poi',
        'perché però qualità più mercoledì' => 'perché però qualità più mercoledì',
        '&amp; &quot; &#039; &lt; &gt;' => '& " \' < >',
        // test string come concordata con minh
        "!.,?:;@+-%=_# <> sconto 50% perché però qualità più mercoledì l&#039;altra volta €€" => '!.,?:;@+-%=_# <> sconto 50% perché però qualità più mercoledì l\'altra volta €€',
    ];
    foreach ($a_in as $s_in => $s_out) {
        $s_res = Safe::unquote($s_in);
        ok($s_res, $exp = $s_out, 'unquote:' . $s_in);
    }
    // test unquote
    $a_in = [
        // html in => txt out
        '€' => 'EUR',
        'È' => 'E',
        'èé' => 'èé',
        "长达" => '??',
        ',.-@?!%()°;:' => ',.-@?!%()?;:',
    ];
    foreach ($a_in as $s_in => $s_out) {
        $s_res = Safe::utf8_to_CCSID280($s_in);
        ok($s_res, $exp = $s_out, 'utf8_to_CCSID280:' . $s_in);
    }
    //----------------------------------------------------------------------------
    //  num treatment
    //----------------------------------------------------------------------------
    echo ok(Safe::num(''), $exp = '0.0', 'num ""');
    echo ok(Safe::num(0), $exp = '0.0', 'num 0');
    echo ok(Safe::num(null), $exp = '0.0', 'num NULL');
    echo ok(Safe::num(' 1.0'), $exp = '1.0', 'num trim');
    echo ok(Safe::num('1'), $exp = '1', 'num str');
    echo ok(Safe::num('aaa'), $exp = '0.0', 'num str invalid');
    echo ok(Safe::num('1'), $exp = '1', 'num str');
    //----------------------------------------------------------------------------
    // cod dest
    //----------------------------------------------------------------------------
    echo ok(Safe::cod_dest('1'), $exp = '1', 'num cod_dest 1');
    echo ok(Safe::cod_dest(1), $exp = '1', 'num cod_dest 1s');
    echo ok(Safe::cod_dest(0), $exp = '0', 'num cod_dest');
    echo ok(Safe::cod_dest(null), $exp = '', 'num cod_dest null');
    echo ok(Safe::cod_dest(false), $exp = '', 'num cod_dest false');
    echo ok(Safe::cod_dest('a'), $exp = '0', 'num cod_dest a');
    //
    echo ok(Safe::cod_dest('1', true), $exp = '000001', 'pad cod_dest 1s');
    echo ok(Safe::cod_dest('0', true), $exp = '000000', 'pad cod_dest 0s');
    echo ok(Safe::cod_dest(0, true), $exp = '000000', 'pad cod_dest 0i');
    echo ok(Safe::cod_dest(1, true), $exp = '000001', 'pad cod_dest 1i');
    echo ok(Safe::cod_dest(null, true), $exp = '000000', 'pad cod_dest null');
    echo ok(Safe::cod_dest(false, true), $exp = '000000', 'pad cod_dest false');
    echo ok(Safe::cod_dest('a', true), $exp = '000000', 'pad cod_dest');
    //----------------------------------------------------------------------------
    //  multi mail
    //----------------------------------------------------------------------------
    echo ok(Safe::email(''), $exp = '', 'm email null');
    echo ok(Safe::email('jeff£@gmail.com'), $exp = 'jeff@gmail.com', 'm email + garbage');
    echo ok(Safe::email('aa@bb.com ; cc@ggg.com'), $exp = 'aa@bb.com;cc@ggg.com', 'm email 2');
    echo ok(Safe::email('aa@bb.com ; garbage'), $exp = 'aa@bb.com', 'm email 2+garbage');
    echo ok(Safe::email('aa@bb.com ; ?????'), $exp = 'aa@bb.com', 'm email 3+garbage');
    echo ok(Safe::email('aa@bb.com ; cc@ggg.com ; cc@ggg.com '), $exp = 'aa@bb.com;cc@ggg.com', 'm email non unique');
}
