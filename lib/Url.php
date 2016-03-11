<?php

class URL {

    function GetSelf() {
        if (isset($GLOBALS['HTTP_SERVER_VARS']['PHP_SELF'])) {
            return $GLOBALS['HTTP_SERVER_VARS']['PHP_SELF'];
        } elseif (isset($_SERVER['PHP_SELF'])) {
            return $_SERVER['PHP_SELF'];
        } else {
            return '';
        }
    }

    // costruisce una url, a partire dalla pagina inviata e dai dati inviati
    // non appende GET automaticamente, se non presente il par $page usa PHP_SELF
    // $page='', $data=array()
    function get() {
        $args = func_get_args();
        $c = func_num_args();
        switch ($c) {
            case 0:
                $page = URL::GetSelf();
                $data = array();
                break;
            case 1:
                if (is_array($args[0])) {
                    $page = URL::GetSelf();
                    $data = $args[0];
                } else {
                    $page = $args[0];
                    $data = array();
                }
                break;
            case 2:
                $page = $args[0];
                $data = $args[1];
                break;
            default:
                $page = $args[0];
                $data = $args[1];
                for ($i = 2; $i < $c; $i++) {
                    $data = array_merge($data, $args[$i]);
                }
                break;
        }
        if (count($data)) {
            $data = Arr::deleteEmpty($data);
            return $page . '?' . str_replace('&amp;', '&', http_build_query($data));
        } else {
            return $page;
        }
    }

}
