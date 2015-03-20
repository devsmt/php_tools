<?php

// evrything comes from user imput should be accessed from Request,
// which will  filter it and  abstract the retrive mechanism ( we allow only upper & lower alphas and integers )
class Request {

    function getRaw($key, $default = null, $opt = array()) {
        $v = null;
        //---- get from specific source
        $type = isset($opt['type']) ? strtoupper($opt['type']) : 'REQUEST';
        switch ($type) {
            // possibile iniettare dati per test
            case 'TEST':
                $data = $opt['DATA'];
                break;
            case 'GET':
                $data = $_GET;
                break;
            case 'POST':
                $data = $_POST;
                break;
            case 'COOKIE':
                $data = $_COOKIE;
                break;
            case 'REQUEST':
            default:
                if (isset($_REQUEST)) {
                    $data = $_REQUEST;
                } else {
                    $data = array_merge($_GET, $_POST, $_COOKIE);
                }
        }
        $v = (isset($data[$key]) ? $data[$key] : $default);
        //---- decode
        $urldecode = isset($opt['urldecode']) ? $opt['urldecode'] : false;
        if ($urldecode) {
            $v = urldecode($v);
        }
        //----- filter
        $filter = isset($opt['filter']) ? $opt['filter'] : null;
        if (!is_null($filter)) {
            // will only allow upper & lower alphas and integers
            $c = preg_replace($filter, '', $v);
            if ($v != $c) {
                echo ("you are trying do pass value '$v' for var '$key', but we allow only safe characters.");
                // if( DEBUG ){ $filter }
                return null;
            }
        }
        //---- apply maxlen
        $v = isset($opt['max_len']) ? substr($v, 0, $max_len) : $v;
        //---- cast
        $cast = isset($opt['cast']) ? strtoupper($opt['cast']) : 'S';
        switch ($cast) {
            case 'D':
            case 'I':
                $v = (int) $v;
                break;
            case 'F':
                $v = (float) $v;
                break;
            // case 'A':  array!
            case 'S':
            default:
            //$v = $v;
        }
        return $v;
    }

    //----------------------------------------------------------------------------
    //
    //----------------------------------------------------------------------------
    function has($key, $type = 'GET') {
        return (null !== Request::getRaw($key, null, array('type' => $type)));
    }

    //----------------------------------------------------------------------------
    //  specilized access
    //----------------------------------------------------------------------------
    // get a variable, with some default
    // filterd! no ; , . - caracters allowed!
    function getStr($key, $default = null, $max_len = 15) {
        $opt = array();
        $opt['urldecode'] = true;
        $opt['filter'] = '[^a-zA-Z0-9_]';
        $opt['max_len'] = $max_len;
        $v = Request::getRaw($key, $default, $opt);
        return $v;
    }

    // get int
    function getInt($k, $d = 0) {
        $opt = array();
        $opt['urldecode'] = true;
        $opt['filter'] = '[^0-9]';
        $opt['cast'] = 'I';
        $opt['max_len'] = 14;
        return Request::getRaw($k, $d, $opt);
    }

    function getArray($k, $d = array()) {
        $opt['cast'] = 'A';
        return Request::getRaw($k, $d, $opt);
    }

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
        return !in_array(@$_SERVER['REMOTE_ADDR'], $ip_list);
    }

    // determina se si tratta di una richiesta locale
    public static function isLocalServed() {
        return in_array(current(explode('.', @$_SERVER['SERVER_ADDR'])), array('10', '192', '127'));
    }

    function isMain($__FILE__) {
        return basename($_SERVER['PHP_SELF']) == basename($__FILE__);
    }

}
