<?php

// l'applicazione o il controller devono configurare e istanziare il logger
//  uso
//  $config = array(
//                 'adapters'=>array(
//                                   array(
//                                         'name'=>'LoggerAdapterFile',
//                                         'param'=>array('file'=>'mail.log')
//                                   )
//                             )
//                 );
//
class Logger {

    var $adapters = array();

    function __construct($config) {
        if (isset($config['adapters'])) {
            foreach ($config['adapters'] as $i => $adapter) {
                $this->adapters[$i] = new $adapter['name']($adapter['param']);
            }
        }
    }

    function write($msg) {
        foreach ($this->adapters as $i => $adapter) {
            $adapter->write($msg);
        }
    }

    function log($msg) {
        $this->write($msg);
    }

}

class LoggerAdapter {

    function __construct($config) {
        $this->init($config);
        $this->open();
    }

    function __destruct() {
        $this->close();
    }

    function init($config) {
        $this->config = $config;
    }

    function open() {
        return true;
    }

    function close() {
        return true;
    }

    function write($msg) {
        return true;
    }

    // costrisce il messaggio di log in modo meno verboso
    public function printf() {
        $mgs = func_get_arg(0);
        $args = array_unshift(func_get_args());
        // gestisce argomenti oggetto o array
        // $args = array_map(function($v) {
        //     if(is_array($v) || is_object($v) ) {
        //         return var_export($v, true);
        //     }
        //     return $v;
        // }, $args);
        $msg = call_user_func_array('sprintf', func_get_args());
        return $this->log($msg);
    }

}

//-- adapters ------------------------------------------------------------
class LoggerAdapterFile extends LoggerAdapter {

    var $file;
    var $handle;
    var $subDir = '/../var/log/'; // dir where the log file is placed

    // e' possibile configurare un percorso di file di log, oppure verre' usato
    // var/log

    function __construct($context) {
        parent::__construct();
        $this->file = dirname(__FILE__) .
                $this->subDir .
                sprintf('%s_%s.log', $context, date('m_Y'));
        $this->open();
    }

    function open() {
        $this->handle = fopen($this->file, 'a+');
    }

    function close() {
        fclose($this->handle);
    }

    function write($msg) {
        $s = sprintf("%s %s \n", date('d/m/Y H:i:s '), $msg);
        fwrite($this->handle, $s);
    }

    // ruota file di log che eccedano la dimensione specifica in MB
    function rotate($maxsize_MB = 5) {
        // converte da MB in byte come occorre alla filesize
        $maxsize = $maxsize_MB * 1024 * 1024;
        if (file_exists($this->file) && filesize($this->file) > $maxsize) {
            $new_name = $this->file . date('dmy_hi');
            rename($this->file, $new_name);
        }
    }

}

//
class LoggerAdapterEcho extends LoggerAdapter {

    function write($msg) {
        //$s = sprintf("%s\r\n", date('d/m/Y H:i:s '), $msg );
        echo $msg, "\n";
        flush();
    }

}

class LoggerAdapterMysqlDB extends LoggerAdapter {

}

class LoggerAdapterEmpty extends LoggerAdapter {

}


// minimal file logger implementation
class MFLogger {
    const OP_KO   = 'error'  ;
    const OP_OK   = 'success';
    const OP_INFO = 'info'   ;

        public static function log($ns, $operation_name, $msg, $params=[], $identity_info = []) {

            $path = self::path($ns);

            $pack = function($str, $label){
                if( empty($str) ) {
                    return '';
                }
                if( is_array($str) ) {
                    $str = json_encode($str);
                    // subset per impedire scritture di dati arbitrari
                    $str = substr($str, 0, 200);
                }
                return "$label:$str";
            };

            $log_data = [
                date('Y-m-d H:i:s'),
                $pack($operation_name, 'operation'),
                $pack($msg, 'msg'),
                $pack($params, 'params'),
                $pack($identity_info, 'identity'),

                $pack(coalesce(@$_SERVER['HTTP_X_REAL_IP'], $_SERVER['REMOTE_ADDR']), 'IP'      ),
                $pack(Browser::translate()                                          , 'BROWSER' ),
            ];

            $str = implode(' ', array_filter($log_data, function ($s){
                return !empty($s);
            }) );

            // implementa una soglia massima
            $bytes = filesize($path);
            $MB = pow(1024, $factor=2);
            if( $bytes > 500 * $MB ) {
                return ;
            }

            file_put_contents($path, $str."\n", FILE_APPEND | LOCK_EX);
        }

    // dipende dall'applicazione
    public static function path($ns) {
        $path = APPLICATION_PATH.'/../var/logs/'.$ns.'_'.date('Y_m').'.log';
        return $path;
    }


    //----------------------------------------------------------------------------
    // log procedure apposita per programmi CLI
    //----------------------------------------------------------------------------
    public static function flog($isOK, $message, array $errors=[]) {
        $log_msg =  sprintf('%s %s msg:"%s" errors:%s params:%s '.PHP_EOL,
            date('Y-m-d H:i:s'),
            $isOK ? 'OK' : 'KO',
            $message,
            json_encode($errors),
            $request =  implode(' ', array_slice($GLOBALS['argv'], $pos=1 ) )
            );
        $pgm_name = str_replace('.php','', basename( $GLOBALS['argv'][0] ) );
        $_log_path = sprintf('%s/../var/logs', APPLICATION_PATH );
        $_log_file = sprintf('%s_%s.log', $pgm_name, date('my') );

        $log_path = realpath( $_log_path );
        if( empty($log_path) ) {
            echo "log_path non valido $_log_path \n";
            return;
        } else {
            $log_path = "$log_path/$_log_file";
            return file_put_contents($log_path, $log_msg, (FILE_APPEND | LOCK_EX) );
        }
    }

}




