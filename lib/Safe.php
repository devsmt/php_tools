<?php
// processa i parametri in input prima delle query
class Safe {
    // toglie caratteri pericolosi da un input che debba essere processato con SQL
    public static function sanitize(string $s, int $len = 0, string $regexp = '/[^a-z0-9\-\_]/i'): string{
        $s = preg_replace($regexp, '', $s);
        $s = self::chop($s, $len);
        return $s;
    }
    // definire una lunghezza massima della stringa
    public static function chop(string $str, int $len = 256): string {
        // opzionalmente applica troncamento per lunghezza
        if (!empty($len)) {
            $debug = true;
            /** @psalm-suppress   RedundantCondition */
            if ($debug) {
                $c_len = mb_strlen($str);
                if ($c_len > $len) {
                    $msg = sprintf('Errore: lunghezza parametro eccede la configurazione in %s, %s>%s str="%s"  ', __METHOD__, $c_len, $len, $str);
                    throw new \InvalidArgumentException($msg);
                }
            }
            return mb_substr($str, 0, $len);
        } else {
            return $str;
        }
    }
    //------------------------------------------------------------------------------
    // TODO: verificare quote()
    public static function quote(string $s): string{
        // ENT_QUOTES => entrambe ' e " convertiti
        $s = htmlspecialchars($s, ENT_QUOTES | ENT_HTML401, $encoding = 'UTF-8');
        return $s;
    }
    // le stringhe vannno espresse con singolo apice: campo = 'stringa',
    // gli apici sono escaped con '' es. campo='bar''s'
    // $s = addslashes($s); non è sufficiente
    protected static function quote2(string $s): string {
        return str_replace("'", "''", $s);
    }
    //
    //----------------------------------------------------------------------------
    // da HTML quoted( &#039; &quot; ) a straight text
    public static function unquote(string $str): string{
        // nel db ho scritto le html entities
        $str = htmlspecialchars_decode($str, ENT_QUOTES | ENT_HTML401);
        return $str;
    }
    // rimuovi caratteri non compatibili con AS400 encoding
    public static function utf8_to_ASCII(string $str): string {
        // $c_map = [
        // '€' => 'EUR',
        // 'À' => 'A',
        // 'Á' => 'A',
        // 'Â' => 'A',
        // 'Ã' => 'A',
        // 'Ä' => 'A',
        // 'Å' => 'A',
        // 'È' => 'E',
        // 'É' => 'E',
        // 'Ê' => 'E',
        // 'Ë' => 'E',
        // 'Ì' => 'I',
        // 'Í' => 'I',
        // 'Î' => 'I',
        // 'Ï' => 'I',
        // 'Ò' => 'O',
        // 'Ó' => 'O',
        // 'Ô' => 'O',
        // 'Õ' => 'O',
        // 'Ö' => 'O',
        // 'Ù' => 'U',
        // 'Ú' => 'U',
        // 'Û' => 'U',
        // 'Ü' => 'U',
        // ];
        // $str = str_replace( array_keys($c_map), array_values($c_map), $str);
        // // remove highers and uppers
        // $str = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '?', $str);
        if (!function_exists('iconv')) {
            die('installare iconv');
        }
        $str = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        return $str;
    }
    //----------------------------------------------------------------------------
    //  sanitization
    //----------------------------------------------------------------------------
    // stringhe estese
    public static function str(string $str, int $len = 20, string $regexp = '/[^a-z0-9\_\-\+\.\/,\s]/i'): string {
        return self::sanitize($str, $len, $regexp);
    }
    // only alpha and numeric
    public static function alphanum(string $str, int $len = 20, string $regexp = '/[^a-z0-9\-\_]/i'): string {
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
    public static function text(string $s, int $len = 256, string $s_additional = '', string $s_replace = ' '): string {
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
    public static function flag(string $s, int $len = 3): string{
        $s = strtoupper($s);
        $s = self::chop($s, $len);
        $s = preg_replace('/[^a-z0-9_]/i', '_', $s);
        return $s;
    }
    //----------------------------------------------------------------------------
    // GUID
    //----------------------------------------------------------------------------
    // preg_match("/\w{8}-\w{4}-\w{4}-\w{4}-\w{12}/i", $guid)
    public static function GUID(string $s): string{
        $s = self::chop($s, $len = 32);
        $s = preg_replace('/[^a-z0-9\-]/i', '?', $s);
        return $s;
    }
    //----------------------------------------------------------------------------
    //  numerics
    //----------------------------------------------------------------------------
    // gestisce numeri espressi come float as400, ',' viene convertito a '.'
    /** @param string|int|float $s */
    public static function num($s, int $int_len = 12, int $dec_len = 2, string $def = '0.0'): string{
        $s = strval($s);
        $s = trim($s);
        $len = $int_len + 1 + $dec_len; // lunghezza stringa totale, 1 per il '.'
        $s = str_replace(',', '.', $s);
        $s = preg_replace('/[^0-9\.\-]/i', '', $s);
        // elimina input eccessivo
        $s = self::chop($s, $len);
        $s = coalesce($s, $def);
        $s = strval($s);
        return $s;
    }
    public static function int(string $s, int $default = 0): int{
        $len = 15;
        $s = preg_replace('/[^0-9]/i', '', $s);
        // elimina input eccessivo
        $s = self::chop($s, $len);
        if (is_numeric($s)) {
            $i = intval($s);
            return $i;
        } else {
            return $default;
        }
    }
    // int left zero padded
    // es. 000001
    // non fare cast a int
    public static function int_lzp(string $str, int $len = 6): string{
        // correct form: 000001
        $_zpl = function (int $i) use ($len): string {
            return str_pad(strval($i), $len, '0', STR_PAD_LEFT);
        };
        $i = self::int($str, 0); // get just the numeric part
        $str_p = $_zpl($i);
        return $str_p;
    }
    //
    public static function dec(string $num, string $default = '0'): string{
        $num1 = preg_replace('/[^0-9\,\.]/i', '', $num);
        $num2 = str_replace($sub = ',', $re = '.', $num);
        $num3 = empty($num2) ? $default : $num2;
        return $num3;
    }
    // ripulisce email address
    public static function email(string $email): string{
        $email = trim(Safe::text($email));
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
    // email multiple nello stesso campo
    public static function email_multi(string $email):string {
        $_clean_mail = function (string $email):string {
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
    public static function tel(string $tel): string{
        $tel = preg_replace('/[^0-9\+\s]/', '', $tel);
        $tel = str_replace($sub = '  ', $re = ' ', $tel); // replace duble ' '
        $tel = trim($tel);
        $tel = self::chop($tel, $len = 17);
        return $tel;
    }
    // upper alphanumeric plus '-'
    public static function key(string $s, string $default = '', int $len = 50): string{
        $s = preg_replace('/[^A-Z0-9\-\_]/', '', $s);
        // elimina input eccessivo
        $s = self::chop($s, $len);
        return $s ? $s : $default;
    }
    // ensure string is safe IP
    public static function IP(string $s, string $default = ''): string{
        $len = 15;
        $s = preg_replace('/[^0-9\.]/', '', $s);
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
    /**
     * @param string|int|float|bool $def
     * @return scalar
     * return string|int|float
     */
    public static function whitelist(string $str, array $a_wl, $def=null) {
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
    /**
     * @param array<string, mixed> $a_data
     * @param array<string, callable> $a_fields_val
     * @return array<string, scalar>
     */
    public static function hash(array $a_data, array $a_fields_val): array{
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
    /**
     * @psalm-suppress MissingClosureReturnType
     * @psalm-suppress MissingClosureParamType
     */
    public static function _list(array $a_data, callable $_filter = null): array{
        /** @param mixed $v */
        $_f_d = function ($v): string{
            $s = filter_var($v, FILTER_SANITIZE_NUMBER_INT);
            $s = strval($s);
            return $s;
        };
        $_filter = $_filter ? $_filter : $_f_d;
        /**
         * @param mixed $val
         * @return mixed
         */
        $_f = function ($val) use ($_filter) {
            return $_filter($val);
        };
        $a_data_f = array_map($_f, $a_data);
        return $a_data_f;
    }
    //----------------------------------------------------------------------------
    //  uploads
    //  @see https://owasp.org/www-community/vulnerabilities/Unrestricted_File_Upload
    //----------------------------------------------------------------------------
    // riscrive il nome del file per assicurare che non contenga alcuna estensione eseguibile .php .phtml .phar .php3
    // un file eseguibile caricato dall'utente non deve _mai_ poter essere richiamato da apache
    // ok('aa.jpg')
    // ok('aa.php', false)
    // ok('aa.phtml', false)
    // ok('aa.phar', false)
    // test cases
    // ok('aa.Php', false)
    // ok('aa.pHtml', false)
    // ok('aa.phAr', false)
    // test raw replacement of '.php' do not produce dengerous file name
    // ok('aa.phpphp', false)
    public static function file_name(string $file_name): string{
        // strip dangerous extensions, case insensitive
        $apache_regex = ".+\.ph(ar|p|tml)$";
        $file_name = strtolower($file_name);
        $file_name = Safe::alphanum($file_name);
        $is_exe = str_match($file_name, $apache_regex);
        if ($is_exe) {
            // dangerous file name detected
            return '';
        }
        $a_badext = ['php', 'php3', 'php4', 'php5', 'pl', 'cgi', 'py', 'asp', 'cfm', 'js', 'vbs', 'html', 'htm', 'phtml', 'phar'];
        $ext = pathinfo($file_path, PATHINFO_EXTENSION);
        $ext = strtolower($ext);
        if (in_array($ext, $a_badext)) {
            return '';
        }
        return $file_name;
    }
    // test if file copntains any trace of php
    public static function file_get_contents(string $path): string {
        // se ci sono errori elimina il file
    }
    // assicura che il path sia una immagine e non un file eseguibile .php o .js
    public static function image(string $path): string{
        // TODO: processa con GD e riscrive l'immagine
        // se ci sono errori il file tmp deve essere eliminato
        // imagesize può essere scavalcata da contenuto ad hoc che contenga php eseguibile
        $a_img_size = getimagesize($path);
        $image_type = $a_img_size[2];
        // non gestisco IMAGETYPE_BMP
        if (in_array($image_type, [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG])) {
            $a_ext = [
                IMAGETYPE_GIF => 'gif',
                IMAGETYPE_JPEG => 'jpg',
                IMAGETYPE_PNG => 'png',
            ];
            $new_ext = $a_ext[$image_type];
            // rinomina il file in modo sicuro
            $new_path = dirname($path) . DS . self::alphanum(basename($path)) . $new_ext;
            // Get new sizes
            list($width, $height) = $a_img_size;
            $newwidth = $width - 1;
            $newheight = $height - 1;
            // Load
            $thumb = imagecreatetruecolor($newwidth, $newheight);
            $source = imagecreatefromjpeg($path);
            // Resize, after resizing the binary cant contain any php
            imagecopyresized($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
            switch ($image_type) {
            case IMAGETYPE_JPEG:
                imagejpeg($thumb, $new_path);
                break;
            case IMAGETYPE_PNG:
                imagepng($thumb, $new_path);
                break;
            default:
                // TODO
                die(implode('/', [__FUNCTION__, __METHOD__, __LINE__]) . ' > ok...');
                break;
            }
        }

        return $new_path;
    }
    // assicura che il path sia un documento .pdf .xls .doc e non un file eseguibile .php o .js
    // il file potrebbe contenere macro o virus
    public static function document($path) {
        // se il file è pericoloso elimina il file unlink($path)

    }
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
    /**
     * @psalm-suppress InvalidScalarArgument
     * @psalm-suppress NullArgument
     * @psalm-suppress DuplicateClass
     */
    require_once __DIR__ . '/../../lib/functions.php';
    ok(Safe::str(''), $exp = '', '');
    ok(Safe::alphanum(''), $exp = '', '');
    ok(Safe::flag(''), $exp = '', '');
    ok(Safe::num(''), $exp = '', '');
    ok(Safe::int('1'), $exp = 1, 'int');
    ok(Safe::dec('1'), $exp = '1', 'dec');
    ok(Safe::email(''), $exp = '', 'email');
    ok(Safe::tel(''), $exp = '', 'tel');
    ok(Safe::whitelist('a', ['a'], 0), $exp = 'a', 'whitelist');
    ok(Safe::hash(['a' => 1], ['a' => function ($x) {return $x;}]), ['a' => 1], 'hash');
    //
    ok(Safe::email(''), $exp = '', 'email');
    ok(Safe::email("test'@test.com"), $exp = 'test@test.com', 'email 0');
    ok(Safe::email('test@test.com'), $exp = 'test@test.com', 'email 1');
    ok(Safe::email('test^@@test.com'), $exp = 'test@test.com', 'email 1b');
    ok(Safe::email('test"@test.com'), $exp = 'test@test.com', 'email 1c');
    //----------------------------------------------------------------------------
    //  multi mail
    //----------------------------------------------------------------------------
    ok(Safe::email(''), $exp = '', 'm email null');
    ok(Safe::email('jeff£@gmail.com'), $exp = 'jeff@gmail.com', 'm email + garbage');
    ok(Safe::email_multi('aa@bb.com ; cc@ggg.com'), $exp = 'aa@bb.com;cc@ggg.com', 'm email 2');
    ok(Safe::email_multi('aa@bb.com ; garbage'), $exp = 'aa@bb.com', 'm email 2+garbage');
    ok(Safe::email_multi('aa@bb.com ; ?????'), $exp = 'aa@bb.com', 'm email 3+garbage');
    ok(Safe::email_multi('aa@bb.com ; cc@ggg.com ; cc@ggg.com '), $exp = 'aa@bb.com;cc@ggg.com', 'm email non unique');
    //
    ok(Safe::GUID('AA-BB-44-55-JJ'), $exp = 'AA-BB-44-55-JJ', 'GUID 1');
    //
    //ok(Safe::tel(''), $exp = '', 'tel');
    ok(Safe::tel(''), $exp = '', 'tel');
    /** @psalm-suppress InvalidArgument  */
    ok(Safe::whitelist('a', ['a'], 0), $exp = 'a', 'whitelist');
    ok(Safe::hash(['a' => 1], ['a' => function ($x) {return $x;}]), ['a' => 1], 'hash');
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
        ok(Safe::text($str, $l = 999, $addit = '', $re = '?'), $str_res, 'safe_text:' . Safe::chop($str, 30000));
    }
    $str = "To start counting y'our letters"; // 36>35 str="To start counting y&#039;our letters"
    ok(Safe::text($str, $l = 35, $addit = '', $re = '?'), $str_res, 'text len');
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
    ok(Safe::num(''), $exp = '0.0', 'num ""');
    ok(Safe::num(0), $exp = '0.0', 'num 0');
    ok(Safe::num(null), $exp = '0.0', 'num NULL');
    ok(Safe::num(' 1.0'), $exp = '1.0', 'num trim');
    ok(Safe::num('1'), $exp = '1', 'num str');
    ok(Safe::num('aaa'), $exp = '0.0', 'num str invalid');
    ok(Safe::num('1'), $exp = '1', 'num str');

    //----------------------------------------------------------------------------
    //  multi mail
    //----------------------------------------------------------------------------
    ok(Safe::email(''), $exp = '', 'm email null');
    ok(Safe::email('jeff£@gmail.com'), $exp = 'jeff@gmail.com', 'm email + garbage');
    ok(Safe::email('aa@bb.com ; cc@ggg.com'), $exp = 'aa@bb.com;cc@ggg.com', 'm email 2');
    ok(Safe::email('aa@bb.com ; garbage'), $exp = 'aa@bb.com', 'm email 2+garbage');
    ok(Safe::email('aa@bb.com ; ?????'), $exp = 'aa@bb.com', 'm email 3+garbage');
    ok(Safe::email('aa@bb.com ; cc@ggg.com ; cc@ggg.com '), $exp = 'aa@bb.com;cc@ggg.com', 'm email non unique');
}
