<?php
class i18n {
}
/*
localization formats
 */

class l10n {
    public static function defaultLocale():string {
        return 'it_IT';
    }
    public static function init():void {
        setlocale(LC_ALL, l10n::defaultLocale());
    }
    /** @param string|int|float $time */
    public static function date($time):string {
        // se e' str, convertila a time
        if (is_numeric($time)) {
            $i = (int) $time;
        } else {
            $i = (int) strtotime($time);
        }
        return strftime('%A %d %B %Y', $i);
    }
    /** @param string|int|float $time */
    public static function time($time):string { // se e' str, convertila a time
        if (is_numeric($time)) {
            $i = (int) $time;
        } else {
            $i = (int) strtotime($time);
        }
        return strftime('%H:%M', $i);
    }
    /** @param string|int|float $i */
    public static function number($i):string {
        return money_format('%.2n', (float)$i);
    }
}
l10n::init();