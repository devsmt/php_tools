<?php

// catch fatal errors (es. memory problems)
// Log fatal errors using register_shutdown_function,
// requires PHP 5.2+:
register_shutdown_function(function () {
    $format_error = function ( $errno, $errstr, $errfile, $errline ) {
        $trace = print_r( debug_backtrace( false ), true );
        $content = "
        <table>
        <thead><th>Item</th><th>Description</th></thead>
        <tbody>
        <tr>
        <th>Error</th>
        <td><pre>$errstr</pre></td>
        </tr>
        <tr>
        <th>Errno</th>
        <td><pre>$errno</pre></td>
        </tr>
        <tr>
        <th>File</th>
        <td>$errfile</td>
        </tr>
        <tr>
        <th>Line</th>
        <td>$errline</td>
        </tr>
        <tr>
        <th>Trace</th>
        <td><pre>$trace</pre></td>
        </tr>
        </tbody>
        </table>";
        return $content;
    };
    $errfile = "unknown file";
    $errstr  = "shutdown";
    $errno   = E_CORE_ERROR;
    $errline = 0;

    $error = error_get_last();

    if( $error !== NULL) {
        $errno   = $error["type"];
        $errfile = $error["file"];
        $errline = $error["line"];
        $errstr  = $error["message"];
        die( $format_error($errno, $errstr, $errfile, $errline));
    }
});






set_error_handler(function ($errno, $errstr,  $errfile, $errline, array $errcontext) {
        // error was suppressed with the @-operator
        if (0 === error_reporting()) {
            return false;
        }
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
},
            // on which error report level the user-defined error will trigger. Default is "E_ALL"
            $on_err_level = self::isEnvProd() ? E_WARNING : E_ALL

);


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



