<?php



class Autoloader {

    public static function register() {
        spl_autoload_register(function ($pClassName) {
            $path = str_replace('\\', DIRECTORY_SEPARATOR, $pClassName);
            require_once (__DIR__ . DIRECTORY_SEPARATOR . $path . ".php");
        });
    }

}
