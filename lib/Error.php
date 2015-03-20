<?php

set_error_handler(function ($errno, $errstr,  $errfile, $errline, array $errcontext) {
        // error was suppressed with the @-operator
        if (0 === error_reporting()) {
            return false;
        }
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}, E_WARNING);


ini_set('expose_php','0');
// DBG: PHP syntax errors
if (self::isEnvDev()) {
    // DEV: assicura che siano tutti visibili e stampati a video
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    // PROD: log all errors
    error_reporting(E_ALL ^ E_NOTICE);
    ini_set('display_errors', '1');
    //
    $log_file = APPLICATION_PATH . "/../var/logs/php_error-" . date('my') . ".log";
    ini_set('error_log', $log_file);

    if (self::isEnvProd()) {
        set_exception_handler(array('Monitor', 'ExcpLoggerMail'));
    }
}



