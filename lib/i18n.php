<?php

class i18n {



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
