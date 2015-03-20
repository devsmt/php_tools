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
    public static function log($operaton_type, $operaton_status, $params=[], $result = []) {
        $path = APPLICATION_PATH.'/../var/logs/app_'.date('Y_m').'.log';

        $log_data = [
            date('Y-m-d H:i:s'),
            'session:'.session_id(),
            'operation:'.$operaton_type,
            'status:'.$operaton_status,
            !empty($params) ? 'params:'.json_encode($params):'',
            !empty($identity_info) ? 'result:'.json_encode($identity_info):''
        ];

        $str  = implode(' ', array_filter($log_data,
            function ($s){
                return !empty($s);
            })
        )."\n";

        file_put_contents($path, $str, FILE_APPEND | LOCK_EX);
    }
}




