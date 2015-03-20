<?php

// Never Trust User Input
// rimuove input pericoloso
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

    // un intero controllato
    public static function clean_i($int, $max = PHP_INT_MAX, $min = 0) {
        return (int) filter_var($int, FILTER_SANITIZE_NUMBER_INT, array("min_range" => $min, "max_range" => $max));
    }

    // convertono a true: 1 "1" "yes" "true" "on" TRUE
    // convertono a false: 0 "0" "no" "false" "off" "" NULL FALSE
    public static function claen_bool($bool, $d = false) {
        return Validate::is_bool($bool) ? (boolean) $bool : $d;
    }

    //
    public static function clean_float($float, $max = 1, $min = 0) {
        return filter_var($float, FILTER_SANITIZE_NUMBER_FLOAT, array("min_range" => $min, "max_range" => $max)
            );
    }

    // rende più sicura una url
    // esempio:
    //     in:  "http://test.org/a dir!/file.php?foo=1&bar=2"
    //     out: http%3A%2F%2Ftest.org%2Fa%20dir%21%2Ffile.php%3Ffoo%3D1%26bar%3D2
    public static function clean_url($url) {
        // FILTER_FLAG_STRIP_LOW => Any characters below ASCII 32 will be stripped from the URL
        return filter_var($url, FILTER_SANITIZE_URL, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    }

    // ritorna una stringa togliendo tutti i tags html
    // http://it2.php.net/htmlspecialchars
    public static function clean_html($html) {
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
        $p->options = array(
            'HTML.Allowed' => 'img[src],p,br,b,strong,i'
            );
        $html = $p->purify($html);
        $p->options = array(
            'HTML.Allowed' => ''
            );
        $text = $p->purify($html);
        if (mb_strlen($text, 'UTF-8') === mb_strlen($html, 'UTF-8')) {
            return '<pre>' . $text . '</pre>';
        }
        return $html;
    }
}
