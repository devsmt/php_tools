<?php

class i18n {

    var $container = null;

    function i18n() {
        $this->__construct();
    }

    function __construct() {
        //TODO: CONFIG
        $this->container = new i18nConainerArray();
    }

    function getLang() {

    }

    function get($enstr) {

    }

    function format($enstr) {

    }

}

/* abstract driver that stores translated version of english phrase
used as key */

class i18nContainer {

    function get($key) {

    }

}

/* uses a PHP array to store str */

class i18nConainerArray extends i18nContainer {

    function get($key) {

    }

}

/* uses a DB to store str */

class i18nConainerDB extends i18nContainer {

    function get($key) {

    }

}

class i18nContainerGettext extends i18nContainer {

    function get($key) {
        /*
    // You can use gettext() (You will need to build PHP with GNU gettext support)
    // together with some other functions, this example was taken from the PHP Manual:
    // Set language to German
    setlocale(LC_ALL, 'de_DE');
    // Specify location of translation tables
    bindtextdomain("myPHPApp", "./locale");
    // Choose domain
    textdomain("myPHPApp");
    echo _("Have a nice day");
     */
    }

}

/*
localization
 */

class l10n {

    function defaultLocale() {
        return 'it_IT';
    }

    function init() {
        setlocale(LC_ALL, l10n::defaultLocale());
    }

    function date($time) {
        // se e' str, convertila a time
        if (is_numeric($time)) {
            $i = $time;
        } else {
            $i = strtotime($time);
        }
        return strftime("%A %d %B %Y", $i);
    }

    function time($time) {
        // se e' str, convertila a time
        if (is_numeric($time)) {
            $i = $time;
        } else {
            $i = strtotime($time);
        }
        return strftime("%H:%M", $i);
    }

    function number($i) {
        return money_format('%.2n', $i);
    }

}

l10n::init();
