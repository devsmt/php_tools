<?php

class OS {
    public static function isWindows(){
        return substr(PHP_OS, 0, 3) == 'WIN';
    }
}