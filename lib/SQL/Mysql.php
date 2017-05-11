<?php

/*
TODO: reimplementare con nuove funzioni mysqlnd o PDO

// classe generica per l'accesso ai dati
class DBAdapter {
    var $name; //db name
    var $user = 'root';
    var $password = '';
    var $host = 'localhost';
    function DBAdapter() {
        die('Abstract Error: class DBAdapter');
    }
    function __construct($host = 'localhost', $user = 'root', $password, $name) {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->name = $name;
        if ($this->host != '' && $this->user != '') {
            $this->open();
        }
    }
    function open() {
    }
    function close() {
    }
    function qry() {
    }
    // format di una query per prevenire sql injection
    // il primo par e' il formato, gli altri parametri sono da passare con mysql_escape
    function format() {
        $args = func_get_args();
        $fmt = func_get_arg(0);
        $a = ;
        for ($i = 1;$i < count($args);$i++) {
            $a[] = SQL::escape($args[$i]);
        }
        return vsprintf($fmt, $a);
    }
    function getErrMsg() {
    }
    function format_qry_err($line, $file, $sql, $err) {
        $s = "<div class=sql_err>$line@$file:</div>";
        $s.= '<div class="sql_err">' . $sql . '</div>';
        $s.= '<div class="sql_err">' . mysql_error($this->_con_id) . "</div>";
        return $s;
    }
    // stampa la query in modo comprensibile
    function debug($sql) {
        echo "<pre style=\"background-color:#fff;color:#f00;\">$sql</pre>";
    }
    // clean up operations on the db
    function cleanup() {
    }
}
// accesso ad un databsa di tipo mysql
class DBAdapterMysql extends DBAdapter {
    var $_con_id;
    // rs attuale
    // TODO: verificare se usato
    var $rs = null;
    var $took; //secondi usati dall'esecuzione dell'ultima qry
    // le query di insert e update ritornano questo valore
    var $lines = 0;
    var $record;
    function DBAdapterMysql($host = 'localhost', $user = 'root', $password, $name) {
        $this->__construct($host, $user, $password, $name);
    }
    function open() {
        $this->_con_id = mysql_connect($this->host, $this->user, $this->password);
        if (!$this->_con_id) {
            die('<h3>impossibile connettersi al database "' . $this->name . '"</h3>' . mysql_error());
        } else {
            //define('DB_HDL',$this->_con_id);
            if (!mysql_select_db($this->name)) {
                die(__LINE__ . '@' . __FILE__ . ": database \"{$this->name}\" non esiste ");
            } else {
                // echo 'connesso';

            }
        }
    }
    function close() {
        mysql_close($this->_con_id);
    }
    function qry($sql, $line, $file) {
        if (is_resource($this->rs)) {
            mysql_free_result($this->rs);
        }
        $this->rs = null;
        // $t = get_microtime();
        $this->rs = mysql_query($sql, $this->_con_id);
        if (DEBUG) {
            echo $this->debug($sql);
        }
        $sql = trim($sql);
        if (stristr(substr($sql, 0, 8), "delete") || stristr(substr($sql, 0, 8), "insert") || stristr(substr($sql, 0, 8), "update")) {
            if (mysql_affected_rows() > 0) return true;
            else return false;
        } else {
            if (is_resource($this->rs)) $this->count = mysql_num_rows($this->rs);
            else $this->count = 0;
        }
        //$this->took = interval_time($t);
        // !mysql_error() &&
        if (
            $this->rs != null && $this->rs != false) {
            return $this->rs;
        } else {
            $s = $this->format_qry_err($line, $file, $sql, mysql_error($this->_con_id));
            Weasel::error($line, $file, $s);
            return false;
        }
    }
    //function uqry($sql, $line, $file){
    //    mysql_free_result($this->rs);
    //    if( mysql_unbuffered_query($sql,$this->_con_id)==false ) {
    //        return true;
    //    } else {
    //        $s = $this->format_qry_err($line,$file,$sql,mysql_error($this->_con_id));
    //        app_error($s,__FUNCTION__,__CLASS__);
    //        return false;
    //    }
    //}
    // ritorna anche il numero di linee modificate
    function qry_cmd($sql, $line, $file) {
        $this->lines = 0;
        if ($this->qry($sql, $line, $file)) {
            $this->lines = (int)mysql_affected_rows($this->_con_id);
            return true;
        } else {
            $this->lines = 0;
            return false;
        }
    }
    function num_rows() {
        return mysql_num_rows($this->rs);
    }
    function get_last_inserted_id() {
        return mysql_insert_id($this->_con_id);
    }
    function getErrMsg() {
        return mysql_error();
    }
    // while( $app_db->fetch() ){
    //     echo $app_db->record['polizza'].'<br>';
    // }
    function fetch($fetch_type = MYSQL_BOTH) {
        $this->record = mysql_fetch_array($this->rs, $fetch_type);
        return $this->record !== false;
    }
    // ritorna un array di valori
    // uso:
    // if($this->qry($sql, $line, $file))
    //         $a = $db->rs2a();
    // else
    // fetch_type: MYSQL_BOTH,MYSQL_ASSOC,MYSQL_NUM
    function rs2a($fetch_type = MYSQL_BOTH) {
        $a = ;
        while ($ar = mysql_fetch_array($this->rs)) {
            $a[] = $ar;
        }
        return $a;
    }
    // ritorna string
    function rs2s($fetch_type = MYSQL_BOTH) {
        $a = $this->rs2a();
        return isset($a[0][0]) ? (string)$a[0][0] : '';
    }
    // ritorna flaot o integer
    function rs2i($fetch_type = MYSQL_BOTH) {
        $a = $this->rs2a();
        return isset($a[0][0]) ? (int)$a[0][0] : 0;
    }
    function rs2f($fetch_type = MYSQL_BOTH) {
        $a = $this->rs2a();
        return isset($a[0][0]) ? (float)$a[0][0] : 0;
    }
    // ritorna un boolean
    function rs2b($fetch_type = MYSQL_BOTH) {
        $a = $this->rs2a();
        return isset($a[0][0]) ? (boolean)$a[0][0] : false;
    }
    function cleanup() {
        // fa girare il comando optimize su tutte le tabelle
        // serve se le tabelle sono con campi a lunghezz variabile e vengono fatte frequenti eliminazioni e inserimenti
        $alltables = mysql_query("SHOW TABLES");
        while ($table = mysql_fetch_assoc($alltables)) {
            foreach ($table as $db => $tablename) {
                mysql_query("OPTIMIZE TABLE '" . $tablename . "'") or die(mysql_error());
            }
        }
    }
}
