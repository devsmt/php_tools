<?php

/*
  Key Value Store, un meccanismo per persistere dati
 */

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

    private $dbm;
    private $dbmFile;

    function __construct() {
        $this->dbmFile = get_class($this) . '.nmdb';
        $this->dbm = dba_popen($this->dbmFile, "c", "flatfile");
    }

    function __destruct() {
        dba_close($this->dbm);
    }

    function __get($name) {
        $data = dba_fetch($name, $this->dbm);
        if ($data) {
            //print $data;
            return unserialize($data);
        } else {
            print "$name not found\n";
            return false;
        }
    }

    function __set($name, $value) {
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
}
