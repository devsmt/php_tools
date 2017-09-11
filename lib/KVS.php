<?php

//
// Key Value Store, un meccanismo per persistere dati
//
class KVS {

    // config options
    // crea un file per ogni variabile
    static $dir = "./data";

    static function set($key, $val) {
        if (!is_dir(self::$dir)) {
            mkdir(self::$dir, 0755);
        }
        file_put_contents(self::$dir . $key, serialize($val));
    }

    static function get($key) {
        // fixit name back to normal
        $data = file_get_contents(self::$dir . $key);
        return unserialize($data);
    }

    static function delete($key) {
        unlink(self::$dir . $key);
    }

}

// aggiunge a un oggetto la possibilità di persistere le proprietà tra le chiamate
abstract class PersistentObject {

    private $dbm = null;
    private $dbmFile = '';

    public function __construct($dir = '', $reset = false) {
        $class = preg_replace('/[^a-zA-Z0-9_]/', '', get_class($this));
        if (empty($dir)) {
            $dir = realpath(__FILE__ . '/../var');
        }
        if (empty($dir)) {
            $msg = sprintf('Errore: %s dir "%s" non deve essere vuota ', __CLASS__, $dir);
            throw new Exception($msg);
        }
        $this->dbmFile = sprintf('%s/%s.nmdb', $dir, $class);
        if ($reset && file_exists($this->dbmFile)) {
            // elimina informazioni presenti, così che il file venga riscritto
            unlink($this->dbmFile);
        }
        $this->dbm = dba_popen($this->dbmFile, "c", "flatfile");
    }

    public function __destruct() {
        dba_close($this->dbm);
    }

    public function __get($name) {
        $data = dba_fetch($name, $this->dbm);
        if ($data) {
            //print $data;
            return unserialize($data);
        } else {
            print "$name not found\n";
            return false;
        }
    }

    public function __set($name, $value) {
        dba_replace($name, serialize($value), $this->dbm);
    }

    public static function dump() {
        echo "Available DBA handlers:\n";
        foreach (dba_handlers(true) as $handler_name => $handler_version) {
            // clean the versions
            $handler_version = str_replace('$', '', $handler_version);
            echo " - $handler_name: $handler_version\n";
        }
    }
    // ottien tutte le chievi e tutti i valori
    public function fetchAll() {
        $assoc = [];
        for ($k = dba_firstkey($this->dbm); $k != false; $k = dba_nextkey($this->dbm)) {
            $assoc[$k] = dba_fetch($k, $this->dbm);
        }
        return $assoc;
    }
}



// persiste un array in un hash file database
// $pa = new ArrayPersistent(__DIR__.'/test.cdb');
// $pa['key'] = time();
// foreach( $pa as $k => $v) { }
class ArrayPersistentF implements ArrayAccess, Iterator {
    private $db;
    private $current;
    function __construct($path) {
        $this->db = dba_popen($path, "c", "flatfile");
        if (!$this->db) {
            throw new Exception("$path could not be opened");
        }
    }
    function __destruct() {
        dba_close($this->db);
    }
    function offsetExists($index) {
        return dba_exists($index, $this->db);
    }
    function offsetGet($index) {
        return unserialize(dba_fetch($index, $this->db));
    }
    function offsetSet($index, $newval) {
        dba_replace($index, serialize($newval), $this->db);
        return $newval;
    }
    function offsetUnset($index) {
        return dba_delete($index, $this->db);
    }
    function rewind() {
        $this->current = dba_firstkey($this->db);
    }
    function current() {
        $key = $this->current;
        if ($key !== false) {
            return $this->offsetGet($key);
        }
    }
    function next() {
        $this->current = dba_nextkey($this->db);
    }
    function valid() {
        return ($this->current == false) ? false : true;
    }
    function key() {
        return $this->current;
    }
    // aggiunge i valori povenienti da un altro hash
    function merge(array $a_hash) {
        foreach ($a_hash as $k => $v) {
            $this->offsetSet($k, $v);
        }
    }
}

// persist an array in memory using APC
/*
class ArrayPersistentM implements ArrayAccess, Iterator {
}
*/