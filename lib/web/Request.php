<?php

// evrything comes from user imput should be accessed from Request,
// which will  filter it and  abstract the retrive mechanism ( we allow only upper & lower alphas and integers )
class Request {


    //----------------------------------------------------------------------------
    //
    //----------------------------------------------------------------------------

    // $def string | array
    public static function get(string $key, string $def = '', \Closure &$_cleaner = null, int $max_len=0): string{
        $__cleaner = $_cleaner ?? function ($v) {return $v;};
        $orig_v = isset($_REQUEST[$key]) ? $_REQUEST[$key] : $def;
        $v = $__cleaner($orig_v);
        $v = empty($max_len) ? $v : substr($s,0, $max_len);
        return $v;
    }
    public static function get_a(string $key, array $def = [], \Closure &$_cleaner = null): array{
        $__cleaner = $_cleaner ?? function ($rec) { return $rec;};
        $a_orig = isset($_REQUEST[$key]) ? $_REQUEST[$key] : $def;
        $a_v = array_map(function($rec) use($__cleaner) {
                $a = $__cleaner($rec);
                return $a;
        }, $a_orig );
        return $a_v;
    }
    //----------------------------------------------------------------------------
    //  filtred
    //----------------------------------------------------------------------------
    //
    public static function geti($k, $d, $min=null, $max=null) {
        $v = filter_input(INPUT_GET, $k, FILTER_SANITIZE_NUMBER_INT);
        return coalesce($v,$d);
    }
    public static function posti($k, $d, $min=null, $max=null) {
        $v = filter_input(INPUT_POST, $k, FILTER_SANITIZE_NUMBER_INT);
        return coalesce($v,$d);
    }
    //----------------------------------------------------------------------------
    //
    public static function getf($k, $d, $min=null, $max=null) {
        $v = filter_input(INPUT_GET, $k, FILTER_SANITIZE_NUMBER_FLOAT);
        return coalesce($v,$d);
    }
    public static function postf($k, $d, $min=null, $max=null) {
        $v = filter_input(INPUT_POST, $k, FILTER_SANITIZE_NUMBER_FLOAT);
        return coalesce($v,$d);
    }
    //----------------------------------------------------------------------------
    public static function gets($k, $d, $max_len=null) {
        $flg = FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK ;
        $v = filter_input(INPUT_GET, $k, FILTER_SANITIZE_STRING, $flg );
        if( !empty($max_len)  ) {
             $v = substr($v, 0, $max_len);
        }
        return coalesce($v,$d);
    }

    // list of sanitize filters: http://php.net/manual/en/filter.filters.sanitize.php
    // notably:
    //   FILTER_SANITIZE_EMAIL
    //   FILTER_SANITIZE_URL

    //----------------------------------------------------------------------------
    // other request info
    //----------------------------------------------------------------------------
    function isPost() {
        return (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST');
    }

    function isSecure() {
        return (@$_SERVER['HTTPS'] == 'on');
    }

    function isAjax() {
        return ($this->getMethod() === 'ajax');
    }

    // string "get", "post" or "ajax", depending on a request type. An empty POST request will resolve as a GET request.
    function getMethod() {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') ? 'ajax' : (count($_POST) === 0 ? 'get' : 'post');
    }

    // test se l'url corrente e' all'interno di una specifica directory o un array di dir
    // use case: /en/index.php
    function hasDirectory($dir, &$metched_dir) {
        if (!is_array($dir)) {
            $dir = array($dir);
        }
        $metched_dir = array();
        $p_reg = "/(\/(" . implode('|', $dir) . ")\/){1}/i"; // -> CI-cms:"/^\/(en|fr){1}\//i" or /(\/(it|en|de|fr|es|ru)\/){1}/i
        $result = (preg_match($p_reg, $_SERVER['PHP_SELF'], $metched_dir) === 1);
        if ($result) {
            // clean the result
            $metched_dir = str_replace('/', '', $metched_dir[1]);
        }
        return $result;
    }

    function isDebugServer() {
        $ip_list = array('127.0.0.1', '::1');
        // tutta la rete locale
        for ($i = 0; $i <= 255; $i++) {
            $ip_list[] = '192.168.1.' . $i;
        }
        $IP = Net::getIP();
        return !in_array($IP, $ip_list);
    }

    // determina se si tratta di una richiesta locale
    public static function isLocalServed() {
        return in_array(current(explode('.', @$_SERVER['SERVER_ADDR'])), array('10', '192', '127'));
    }

    function isMain($__FILE__) {
        return basename($_SERVER['PHP_SELF']) == basename($__FILE__);
    }

}
