<?php

class Error {
    function initErrorHandling() {
        error_reporting(E_ALL ^ E_NOTICE); // mostra tutti gli errori ma Esclude i NOTICE
        ini_set('log_errors', 'On');
        // ensure dir LOG_PATH
        ini_set('error_log', LOG_PATH);
        if (isset($_REQUEST['__verbose__']) && $_REQUEST['__verbose__'] > 0) {
            ini_set('display_errors', 'On');
        } else {
            ini_set('display_errors', 'Off');
        }
    }
}

// catch fatal errors (es. memory problems)
// Log fatal errors using register_shutdown_function,
// requires PHP 5.2+:
register_shutdown_function(function () {
    $format_error = function ($errno, $errstr, $errfile, $errline) {
        $trace = print_r(debug_backtrace(false), true);
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
    $errstr = "shutdown";
    $errno = E_CORE_ERROR;
    $errline = 0;

    $error = error_get_last();

    if ($error !== NULL) {
        $errno = $error["type"];
        $errfile = $error["file"];
        $errline = $error["line"];
        $errstr = $error["message"];
        die($format_error($errno, $errstr, $errfile, $errline));
    }
});

set_error_handler(function ($errno, $errstr, $errfile, $errline, array $errcontext) {
    // error was suppressed with the @-operator
    if (0 === error_reporting()) {
        return false;
    }
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
},
// on which error report level the user-defined error will trigger. Default is "E_ALL"
    $on_err_level = self::isEnvProd() ? E_WARNING : E_ALL

);

/*
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
set_exception_handler(['Monitor', 'ExcpLoggerMail']);
}
}
 */

class Error_Monitor {

    // informa di possibili problemi online
    // TODO: evitare che si generino troppi messaggi di notifica
    // USO: set_exception_handler( ['Monitor', 'ExcpLoggerMail'] );
    public static function ExcpLoggerMail($exception) {

        $url = sprintf('%s://%s/%s', $_SERVER['REQUEST_SCHEME'], $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI']
        );

        // get user for Zend Apps
        $auth = Zend_Auth::getInstance();
        if (!empty($auth)) {
            $idnt = $auth->getIdentity();
            if (!empty($idnt)) {
                $user = $idnt->getUsername();
            }
        }
        // fallback
        if (empty($user)) {
            $a_sess = $_SESSION;
            $a_sess = array_filter($a_sess, function ($s) {
                if (is_string($s)) {
                    // se stringa scarta testi lunghi
                    return !empty($s) && strlen($s) < 40;
                } else {
                    // scarta oggetti o testi
                    return !empty($s) && is_scalar($s);
                }
            });
            $user = var_export($a_sess, 1);
        }

        $str = '
        <pre>
        <b>Fatal exception handler</b>:  Uncaught exception Type:"{{exc_class}}"
        Server: {{server}} IP:{{ip}}
        Time: {{time}}
        User: {{user}}

        Url: {{url}}
        Method: {{method}}
        Request: {{request}}

        Line: {{line}}
        File: {{file}}

        Message: "{{message}}"
        Stack trace: {{trace}}
        </pre>';
        $data = array(
            'server' => $_SERVER['SERVER_NAME'],
            'time' => date('d-m-Y H:i:s'),
            'user' => $user,
            'ip' => $_SERVER['SERVER_ADDR'],
            'url' => $url,
            'method' => $_SERVER['REQUEST_METHOD'],
            'request' => var_export($_REQUEST, 1),
            'line' => $exception->getLine(),
            'file' => $exception->getFile(),
            'exc_class' => get_class($exception),
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        );
        // interpola data
        foreach ($data as $k => $v) {
            $str = str_replace('{{' . $k . '}}', $v, $str);
        }

        // trim delle righe per formattare
        $a = explode(PHP_EOL, $str);
        $a = array_map(function ($l) {
            return trim($l);
        }, $a);
        $str = implode(PHP_EOL, $a);

        //--------------------------------------------------------------------
        // mail dell'errore
        //
        $headers = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        //$headers .= 'Cc: birthdayarchive@example.com' . "\r\n";
        //$headers .= 'From: Birthday Reminder <birthday@example.com>' . "\r\n";
        $subject = sprintf('eccezione in PROD %s', $_SERVER['SERVER_ADDR']);
        $to = APP_SUPPORT_EMAIL;
        $mail_res = mail($to, $subject, $str, $headers);
        //--------------------------------------------------------------------
        // log error to var/log/php_error(.*).log
        // 0 => message is sent to PHP's system logger, using the Operating System's system
        // logging mechanism or a file, depending on what the error_log configuration
        // directive is set to. This is the default option.
        // aiuta a correlare errori php, eccezioni, date
        error_log(strip_tags($str), 0);

        // If the function returns FALSE then the normal error handler continues.
        return false;
    }

    // error handler function
    //     - impedire che il sistema generi un numero esagerato di notifiche
    //     - al momento non servono molti dettagli sul utente
    // uso:
    //    $old_error_handler = set_error_handler(['Monitor','ErrorLoggerEmail']);
    //    trigger_error("Cannot divide by zero", E_USER_ERROR);
    public static function ErrorLoggerEmail($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) {
            // This error code is not included in error_reporting
            return;
        }

        switch ($errno) {
        case E_USER_ERROR:
            $str = '
                <pre>
                FATAL ERROR: {{errmsg}}

                Server: {{server}} IP:{{ip}}
                Time: {{time}}
                User: {{user}}

                Url: {{url}}
                Method: {{method}}
                Request: {{request}}

                Line: {{line}}
                File: {{file}}

                </pre>';
            $data = array(
                'errmsg' => $errstr,
                'server' => $_SERVER['SERVER_NAME'],
                'time' => date('d-m-Y H:i:s'),
                //'user' => $user,
                'ip' => $_SERVER['SERVER_ADDR'],
                'url' => sprintf('%s://%s/%s', $_SERVER['REQUEST_SCHEME'], $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI']),
                'method' => $_SERVER['REQUEST_METHOD'],
                'request' => var_export($_REQUEST, 1),
                'line' => $errline,
                'file' => $errfile,
            );
            // interpola data
            foreach ($data as $k => $v) {
                $str = str_replace('{{' . $k . '}}', $v, $str);
            }
            // trim delle righe per formattare
            $a = explode(PHP_EOL, $str);
            $a = array_map(function ($l) {
                return trim($l);
            }, $a);
            $str = implode(PHP_EOL, $a);

            echo $str;

            exit(1);
            break;
            /*
        case E_USER_WARNING:
        echo "<b>My WARNING</b> [$errno] $errstr<br />\n";
        break;

        case E_USER_NOTICE:
        echo "<b>My NOTICE</b> [$errno] $errstr<br />\n";
        break;

        default:
        echo "Unknown error type: [$errno] $errstr<br />\n";
        break;
         */
        }
        // If the function returns FALSE then the normal error handler continues.
        return false;

    }

    //
    // per essere avvertiti degli errori php, non solo delle eccezioni (es. include dinamico errato)
    // register_shutdown_function(      function  () {
    //     $error = error_get_last();
    //     if ($error['type'] == E_ERROR) {
    //         //do your stuff
    //     }
    // });
    //
    //
}



// better trace format
function fmt_exception_trace(Exception $e) {
    function _serialize_args($args) {
        $_v_mapper = function ($val) use (&$_v_mapper) {
            if (is_array($val)) {
                $a_h = array_map_keys($val,
                    function ($k) {
                        return $k;
                    },
                    $_v_mapper);
                return $a_h;
            } elseif (is_object($val)) {
                return get_class($val);
            } elseif (is_resource($val)) {
                return $val;
            } elseif (is_string($val)) {
                $val = trim(strip_tags($val));
                $is_path = substr($val, 0, 1) == '/' && substr_count($val, '/') >= 2; // inizia con /
                if (strlen($val) < 15 || $is_path) {
                    return $val;
                } else {
                    return substr($val, 0, 15) . '...';
                }
            }
            return $val;
        };
        if (is_array($args)) {
            $args = array_map($_v_mapper, $args);
        } else {
            $args = $_v_mapper($args);
        }
        $args = json_encode($args, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        return $args;
    }
    $a_trace = $e->getTrace();
    $a_trace = array_reverse($a_trace);
    $result = '';
    $i = 0;
    foreach ($a_trace as $trace) {
        $result .= $str = sprintf('%d) %s%s%s() %s @%s args:%s ' . PHP_EOL,
            ++$i,
            @$trace['class'],
            @$trace['type'],
            $trace['function'],
            //
            $trace["file"],
            $trace["line"],
            _serialize_args($trace["args"])
        );
    }
    return $result;
}
