<?php

class DB_mysqli {
    static $link = null;
    public static function open($db_host, $db_user, $db_password, $db_name) {
        self::$link = mysqli_connect($db_host, $db_user, $db_password) or WebError::fatal('connection error ' . mysqli_connect_error());
        mysqli_select_db(DB::$link, $db_name) or WebError::fatal('DB selection error');
    }
    public static function qry($sql) {
        $t_start = 0;
        if (V_LEVEL >= 2) {
            echo "<pre>query:$sql;<br>";
            $t_start = Env::get_microtime();
        }
        $rs = mysqli_query(self::$link, $sql);
        if ($rs) {
            if (V_LEVEL >= 2) {
                echo Env::format_timer($t_start), '</pre>';
            }
            return $rs;
        } else {
            WebError::fatal(WebError::SQL($sql));
        }
    }
    //!
    //! esegue una query SELECT,
    //! recupera di ogni riga i dati *in array di records*
    //! se disponibile, usa i risultati presenti in cache
    public static function qry_records($sql, $use_cache = false) {
        // check che la query sia di selezione, altrimenti non ha senso usare questo metodo
        if (!STR::is_substr(strtolower($sql), 'select')) {
            WebError::fatal(WebError::SQL($sql, "la funzione qry_records va usata con istruzioni select"));
        }
        $use_cache = false;
        $key = md5($sql);
        $data = apc_fetch($key);
        $exists = !empty($data) && $use_cache;
        if ($exists) {
            //echo 'cache: retrieving '.$key.'<br>';
            return $data;
        } else {
            $rs = DB::qry($sql);
            $a = [];
            while ($o = mysqli_fetch_array($rs, MYSQLI_ASSOC)) {
                $a[] = $o;
            }
            if ($use_cache && !empty($a)) {
                // non inserisco valori epmty perché può dare falsi positivi al test cache exists
                $ttl = 60 * 60 * 1; // 1 ora
                apc_store($key, $a, $ttl);
                //echo 'cache: storing '.$key.'<br>';
            }
            return $a;
        }
    }
    //! return array associativo della prima riga trovata
    //! da usare con le qry che non restituiscono n righe ma soltanto una
    public static function qry_record($sql) {
        //$rs = qry($sql);
        //return mysqli_fetch_array($rs, MYSQLI_ASSOC);
        $a = DB::qry_records($sql);
        if (isset($a[0])) {
            return $a[0];
        }
        return [];
    }
    //! esegue una query,
    //! recupera di ogni riga i dati in un apposito oggetto
    //! e li inserisce in un array di risultati
    public static function qry_retrieve($sql, $class) {
        // NOTA:
        // apc_store('objs',new ArrayObject($objs),60);
        // $tmp = apc_fetch('objs');
        // print_r($tmp->getArrayCopy());
        // check che la query sia di selezione, altrimenti non ha senso usare questo metodo
        if (!STR::is_substr(strtolower($sql), 'select')) {
            WebError::fatal(WebError::SQL($sql, "la funzione qry_records va usata con istruzioni select"));
        }
        $key = md5($sql);
        $exists = false; //NOTA: apc_exists($key) sul server non funziona
        $data = apc_fetch($key, $exists);
        if ($exists) {
            echo 'cache: retrieving ' . $key . '<br>';
            return $data;
        } else {
            $rs = DB::qry($sql);
            $a = [];
            while ($o = mysqli_fetch_object($rs, $class)) {
                $a[] = $o;
            }
            if (!empty($a)) {
                // non inserisco valori emty perché può dare falsi positivi al test cache exists
                $ttl = 60 * 60 * 1; // 1 ora
                apc_store($key, $a, $ttl);
                echo 'cache: storing ' . $key . '<br>';
            }
            return $a;
        }
    }
    // I want a single column of the first row
    // es. 'SELECT COUNT(*) as count FROM `user`'
    public static function qry_column($sql, $column = null) {
        if (!empty($column)) {
            $a = DB::qry_record($sql);
            return $a[$column];
        } else {
            $rs = DB::qry($sql);
            $a = mysqli_fetch_array($rs, MYSQLI_ASSOC);
            return $a[0];
        }
    }
    //------------------------------------------------------------------------------
    // query wrappers
    //------------------------------------------------------------------------------
    // $field_data deve contenere solo field presenti nella tabella, togliere tutto il resto prima di usare questo automatismo
    // ritorna il record inserito
    public static function insert($table, $pk_field, $field_data) {
        $sql = SQL::insert($table, $field_data);
        if (DB::qry($sql)) {
            $field_data[$pk_field] = mysqli_insert_id(self::$link);
            return $field_data;
        } else {
            return false;
        }
    }

    public static function bulk_insert($table, $labels, $data, $truncate = FALSE) {
        $sql = SQL::bulk_insert($table, $labels, $data );
        if ($truncate) {
            self::truncate($table);
        }
        self::qry($sql);
    }

    public static function truncate($table) {
        $sql = 'TRUNCATE TABLE ' . $table;
        self::qry($sql);
    }


}
//
if( isset($argv[0]) && basename($argv[0]) == basename(__FILE__) ) {
    require_once __DIR__ . '/../Test.php';
}