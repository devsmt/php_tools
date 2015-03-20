<?php

class Monitor {

    // informa di possibili problemi online
    // TODO: evitare che si generino troppi messaggi di notifica
    // USO: set_exception_handler( array('Monitor', 'ExcpLoggerMail') );
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
            'trace' => $exception->getTraceAsString()
        );
        // interpola data
        foreach ($data as $k => $v) {
            $str = str_replace('{{' . $k . '}}', $v, $str);
        }

        // trim delle righe per formattare
        $a = explode(PHP_EOL, $str);
        $a = array_map(function($l) {
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
    //    $old_error_handler = set_error_handler(array('Monitor','ErrorLoggerEmail'));
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
                $a = array_map(function($l) {
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

    /*
      per essere avvertiti degli errori php, non solo delle eccezioni (es. include dinamico errato)
      register_shutdown_function('shutdownFunction');
      function shutDownFunction() {
      $error = error_get_last();
      if ($error['type'] == E_ERROR) {
      //do your stuff
      }
      }
     */
}
