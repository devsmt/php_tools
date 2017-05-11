<?php

// mantiene in memoria il risultato di una chiamata e se possibile,
// ritorna il risultato in memoria.
// usare se l'output è determinabile dai parametri in input e non varia per parametri
// globali tipo user, db, files su disco, network.
/* USO:
function ff($in){
var_dump($in);
// simulate computation
// rand(2,20)*10000
usleep(22*1000);// sleep 2 dec secondo

return $in['a'] ^ $in['b'];
}

$m = 33;
for($i=0; $i<5000; $i++){
echo $i,"\n";
//ff( ['a'=>rand(1,$m),'b'=>rand(1,$m) ] );
memoized('ff', ['a'=>rand(1,$m),'b'=>rand(1,$m) ] );
}
 */
function memoized(Callable $f, array $args = [], $function_name = '') {
    static $__RAM_storage;
    // generic cache key
    ksort($args);
    $k = (is_string($f) ? $f : $function_name) . ':' . http_build_query($args);
    // if needed update the cache with the result
    $has_key = array_key_exists($k, $__RAM_storage);
    if (!$has_key) {
        $__RAM_storage[$k] = $f($args);
        // TODO: call_user_func_array( $f, $args );
    } else {
        // print 'cache hit! '.$k."\n";
    }
    return $__RAM_storage[$k];
}

define('TTL_FOREVER', 0, false);
define('TTL_H', 60 * 60, false);
define('TTL_8H', 8 * TTL_H, false);
define('TTL_D', 24 * TTL_H, false);
/* uso:
$cache_key = '';//__METHOD__.'_'.json_encode(func_get_args());//cache_mk_key()
$data = cache($cache_key, function() {
return [];
});
return $data;
 */
function cache(string $cache_key, \Closure $generator, int $ttl_secs = TTL_8H) {
    $val = apc_fetch($cache_key);
    if (false === $val) {
        $val = $generator();
        apc_store($cache_key, $val, $ttl_secs); //secs, 0=forever
    } else {
        // from cache
    }
    return $val;
}

// uso:
// $res = cache2(__METHOD__, $args = func_get_args() );
function cache2(string $method, array $a_args, \Closure $generator, int $ttl_secs = TTL_8H) {
    $cache_key = cache_mk_key($method, $a_args);
    return cache($cache_key, $generator, $ttl_secs);
}
function cache_mk_key(string $method, array $a_args) {
    return $method . '_' . json_encode($a_args);
}
//----------------------------------------------------------------------------
//  cache interface
//----------------------------------------------------------------------------
// interface storage adapter
interface ICacheStorage {
    // scrive la chiave solo se non esiste
    public function add(string $key, array $data);
    public function save(string $key, array $data);
    public function get(string $key);
    public function isExpired(string $key);
    public function delete(string $key);
    // Removes all entries from the cache.
    public function deleteAll();
}

//----------------------------------------------------------------------------
//  Minimalist File Cache
//----------------------------------------------------------------------------

// salva e legge dati, che dureranno in cache 1 gg
class MFCache {
    public static function fetch(string $cache_key) {
        $path = self::getFileName($cache_key);
        if (!file_exists($path)) {
            return false;
        }
        return unserialize(file_get_contents($path));
    }
    public static function store(string $cache_key, $val, int $ttl_secs = 3600) {
        $path = self::getFileName($cache_key);
        file_put_contents($path, serialize($val));
    }
    public static $cache_dir = '';
    public static $cache_date_fmt = 'Ymd';
    // ottiene il filename.  la cache dura una giornata
    public static function getFileName(string $cache_key) {
        self::$cache_dir = dirname(realpath(__FILE__));
        $f = sprintf('%s/cache/%s_%s.txt', self::$cache_dir, $cache_key, date(self::$cache_date_fmt));
        return $f;
    }
    // eliminare i file più vecchi di 1 giorno
    public static function cleanup() {
        $files = glob(self::$cache_dir . "/cache/*");
        $time = time();
        $s_day = 60 * 60 * 24;
        foreach ($files as $file) {
            if (is_file($file)) {
                if ($time - filemtime($file) >= $s_day * 2) { // 2 days
                    unlink($file);
                }
            }
        }
    }

    // refreshes a cached repr if it's outdated
    function refresh(string $cache_file, int $ttl, Closure $cb_generator) {
        if (file_exists($cache_file)) {
            $time = time();
            if ($time - filemtime($file) >= $ttl) {
                // modified, clear it
                unlink($cache_file);
                $str = $cb_generator();
                file_put_contents($cache_file, $str);
            }
        } else {
            $str = $cb_generator();
            file_put_contents($cache_file, $str);
        }
    }
}

//----------------------------------------------------------------------------
//  sqlite based cache wrapper
//----------------------------------------------------------------------------
class MSqliteCache {
    static $DB = null;
    public function __construct() {
        self::$DB = sqlite_open("cache.db", 0666, $sqlite_error);
        if (!self::$DB) {
            die("Errore Sqlite: " . $sqlite_error);
        }

        // Test for existing DB
        $table_name = __CLASS__;
        $result = sqlite_query("SELECT * FROM sqlite_master WHERE name='$table_name' AND type='table'");
        // If there is no table, create a new one
        if (0 == count($result)) {
            sqlite_query(self::$DB, "CREATE TABLE $table_name (key varchar(50), value_field varchar(255), create_time timestamp )");
        }

    }

    public function get(string $key) {
        $result = sqlite_query(self::$DB, "SELECT * FROM $table_name");
        while ($data = sqlite_fetch_array($result)) {
            echo $data['value_field'] . "<br />";
        }
    }
    public function set(string $key, $value, $tll) {
        $table_name = __CLASS__;
        sqlite_query(self::$DB, "INSERT INTO $table_name VALUES ('$key', '$value', " . time() . ")");
    }

    public function cleanup() {
        $table_name = __CLASS__;
        sqlite_query("DELETE FROM $table_name WHERE create_time < $expiration");
    }
}

//------------------------------------------------------------------------------
//
//------------------------------------------------------------------------------
// php7 emulation of apc_* functions
if (!function_exists('apc_fetch')) {
    function apc_fetch($key) {
        return apcu_fetch($key);
    }
    function apc_exists($keys) {
        return apcu_exists($keys);
    }
    function apc_delete($key) {
        return apcu_delete($key);
    }
    function apc_store($key, $var) {
        return apcu_store($key, $var);
    }
    function apc_inc($key, $step = 1) {
        return apcu_inc($key, $step);
    }
    function apc_cache_info($cache_type = "", $limited = false) {return apcu_cache_info($limited);}

    function apc_clear_cache() {return apcu_clear_cache();}
}
//----------------------------------------------------------------------------
// TAG cache
// emulazione tags non supportati nativamente dal backend APC
// uso:
//   APCCacheTags::clean_by_any_matching_tag([__CLASS__]);
//   APCCacheTags::tag_set([__CLASS__]);
class APCCacheTags {
    //
    // elimina dalla cache tutte le chiavi appartenenti a un tag
    public static function clean_by_any_matching_tag(array $a_tags) {
        $cacheEngine = Zend_Registry::get('cache');
        $a_keys = self::tag_get_keys($a_tags);
        foreach ($a_keys as $key) {
            $cacheEngine->remove($key);
        }
        self::tag_remove($a_tags);
    }
    // setta i tags per una chiave nel tagfile
    public static function tag_set(array $a_tags, $c_key) {
        $a_tag_keys = self::tag_get_tree();
        foreach ($a_tags as $tag) {
            if (isset($a_tag_keys[$tag])) {
                if (!in_array($c_key, $a_tag_keys[$tag])) {
                    $a_tag_keys[$tag][] = $c_key;
                }
            } else {
                $a_tag_keys[$tag] = [$c_key];
            }
        }

        self::tag_save_tree($a_tag_keys);
    }
    // ritorna le chiavi appartenenti al tag
    public static function tag_get_keys(array $a_tags) {
        $a_tag_keys = self::tag_get_tree();
        $a_result = [];
        foreach ($a_tags as $tag) {
            if (isset($a_tag_keys[$tag])) {
                $a_keys = $a_tag_keys[$tag];
                $a_result = array_merge($a_result, $a_keys);
            }
        }
        return $a_result;
    }
    // rimuove i tag (e le chiavi associate) dal tagfile
    public static function tag_remove(array $a_tags) {
        $a_tag_keys = self::tag_get_tree();
        $a_result = [];
        foreach ($a_tags as $tag) {
            if (isset($a_tag_keys[$tag])) {
                unset($a_tag_keys[$tag]);
            }
        }
        self::tag_save_tree($a_tag_keys);
    }
    // path su disco del file
    public static function tag_file_path() {
        $path = realpath(APPLICATION_PATH . '/../var');
        $path = $path . '/cache_tags.json';
        return $path;
    }

    // $a_tag_keys = [ tag => [keys] ];
    static $_a_tag_keys = [];
    public static function tag_get_tree() {
        // se c'è un acopia in memoria, altrimenti legge da file
        if (!empty(self::$_a_tag_keys)) {
            return self::$_a_tag_keys;
        } else {
            $path = self::tag_file_path();
            if (!file_exists($path)) {
                touch($path);
            }
            $json_str = file_get_contents($path);
            $a = json_decode($json_str, $use_assoc = true);
            if (empty($a)) {
                return [];
            } else {
                return $a;
            }
        }
    }
    // salva su file la struttura dati intera
    public static function tag_save_tree(array $a_tag_keys) {
        ksort($a_tag_keys); //in-place sort!
        // aggiorna la copia in memoria
        self::$_a_tag_keys = $a_tag_keys;
        // aggiorna il dato su file
        $path = self::tag_file_path();
        $json_str = json_encode($a_tag_keys, JSON_PRETTY_PRINT);
        file_put_contents($path, $json_str, LOCK_EX);
    }
    // elimina tutto per evitare che i file diventino troppo grandi e la cache si frammenti
    public static function gc() {
        $a_tag_keys = self::tag_get_tree();
        $path = self::tag_file_path();
        $a_keys = array_keys($a_tag_keys);
        self::clean_by_any_matching_tag($a_keys);
        self::tag_save_tree($empty_a_tag_keys = []); //sovrascrive lo store con un contenuto vuoto

        // questo svuota tutta la cache
        //   $cacheEngine->clean(Zend_Cache::CLEANING_MODE_ALL);

    }
}

//----------------------------------------------------------------------------
//
//----------------------------------------------------------------------------

/*
 * LRU is a system which cache data used last recently.
 * uso:
$lru = new LRUCache($cacheSize= 2);
$data = [
'a' => 'dataA',
'b' => 'dataB',
'c' => 'dataC',
];
foreach ($data as $key => $value) {
$lru->set($key, $value);
}
var_dump($lru->get('a'));
var_dump($lru->get('b'));
$lru->set('d', 'data_test');
var_dump($lru->get('c'));
var_dump($lru->get('d'));

 */
class LRUCache {
    protected $store = [];
    protected $cache_size;
    public function __construct($size) {
        $this->cache_size = $size;
    }
    public function set($key, $value) {
        $this->store[$key] = $value;
        if (count($this->store) > $this->cache_size) {
            array_shift($this->store);
        }
    }
    public function get($key) {
        if (!isset($this->store[$key])) {
            return null;
        }
        // Put the value gotten to last.
        $tmp = $this->store[$key];
        unset($this->store[$key]);
        $this->store[$key] = $tmp;
        return $this->store[$key];
    }
}

/*
// cache di una intera pagina
// identificata da una chiave ricavata daiparametri con cui e' stata chiamata
// non gestire pagine che richiedono informazioni relative alla sessione corrente con questo meccanismo
class PageCache {
var $_key = ''; // la chiave univoca che identifica la pagina corrente
function __construct() {
$this->storage = new FileCache();
$this->_key = $this->genKey();
}
// genera una chiave univoca per la pagina(richiesta http) corrente
function genKey() {
// ordino le chiavi in modo che a=1&b=2 sia la stessa pagina di b=2&a=1
// elimino le chiavi nulle o vuote
$post = $_POST;
$post = Arr::deleteEmpty($post);
ksort($post);
$get = $_GET;
$get = Arr::deleteEmpty($get);
ksort($get);
//$_SESSION, $_COOKIE
$req = array_merge($post, $get);
$qry = !empty($req) ? '-' . str_replace('&amp;', '&', http_build_query($req)) : '';
return $_SERVER['PHP_SELF'] . $qry;
}
// se esiste una pagina in cache usala ed esci dal programma, altrimenti continua
// per generare la pagina
function render() {
$file = $this->storage->entry($this->_key);
// var_dump( $file , file_exists($file) , is_readable($file) , $this->storage->isExpired($this->genKey()) );
if (!file_exists($file) || !is_readable($file) || $this->storage->isExpired($this->_key)) {
return false;
} else {
echo $this->storage->get($this->_key);
// exit;
return true;
}
}
// sovrascrivi la cache della chiave corrente
function savePage() {
$this->storage->save($this->_key, ob_get_contents());
}
function get($k) {
return $this->storage->get($k);
}
function clear() {
return $this->storage->deleteAll();
}
}
 */