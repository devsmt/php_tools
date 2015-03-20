<?php

// interface storage adapter
abstract class CacheStorage {

    // TODO: salvare il numero di accessi ad ogni chiave in modo da reperire quali chiavi sono pie' spesso richieste e rendere possibili ulteriori aggiornamenti
    // sovrascrive la chiave con un dato
    function save($key, $data) {

    }

    // scrive la chiave solo se non esiste
    function add($key, $data) {

    }

    function get($key) {

    }

    function isExpired($key) {

    }

    function delete($key) {

    }

    //Removes all entries from the cache.
    function deleteAll() {

    }

}

//
// File-based cache controller.
// Solar_Cache
// original author Paul M. Jones <pmjones@solarphp.com>
// license http://opensource.org/licenses/bsd-license.php BSD
//
//
//
// File-based cache storage.
//
// If you specify a path (for storing cache entry files) that does
// not exist, this adapter attempts to create it for you.
//
// This adapter always uses [[php::flock() | ]] when reading and writing
// cache entries; it uses a shared lock for reading, and an exclusive
// lock for writing.  This is to help cut down on cache corruption
// when two processes are trying to access the same cache file entry,
// one for reading and one for writing.
//
// In addition, this adapter automatically serializes and unserializes
// arrays and objects that are stored in the cache.  This means you
// can store not only string output, but also array data and entire
// objects in the cache ... just like Solar_Cache_Memcache.
//
class FileCache extends CacheStorage {

    // Whether or not the cache is active.
    var $_active = true;
    // The lifetime of each cache entry in seconds.
    var $_life = 86400; //24h
    //
    //
    // User-provided configuration.
    //
    // Config keys are ...
    //
    // `path`
    // : (string) The directory where cache files are located; should be
    //   readable and writable by the script process, usually the web server
    //   process. Default is '/Solar_Cache_File' in the system temporary
    //   directory.  Will be created if it does not already exist.  Supports
    //   streams, so you may specify (e.g.) 'http://cache-server/' as the
    //   path.
    //
    // `mode`
    // : (int) If the cache path does not exist, when it is created, use
    //   this octal permission mode.  Default is `0750` (user read/write/exec,
    //   group read, others excluded).
    //
    // `context`
    // : (array|resource) A stream context resource, or an array to pass to
    //   stream_create_context(). When empty, no context is used.  Default
    //   null.
    //
    // Path to the cache directory.
    var $path = null;
    var $mode = 0740;
    // A stream context resource to define how the input/output for the cache is handled.
    var $context = null;

    function __construct() {
        // set the default cache directory location
        $this->path = dirname(__FILE__) . '/../cache';
        //str_replace('/',DIRECTORY_SEPARATOR,$this->path)
        // keep local values so they can't be changed
        // $this->path = Solar::fixdir($this->path);
        // build the context property
        if (is_resource($this->context)) {
            // assume it's a context resource
            $this->context = $this->context;
        } elseif (is_array($this->context)) {
            // create from scratch
            $this->context = stream_context_create($this->context);
        } else {
            // not a resource, not an array, so ignore.
            // have to use a resource of some sort, so create
            // a blank context resource.
            $this->context = stream_context_create(array());
        }
        // make sure the cache directory is there; create it if
        // necessary.
        if (!is_dir($this->path)) {
            //, true, $this->context//
            if (!mkdir($this->path, $this->mode
                    )) {
                echo $this->path . ' not readable';
            }
        }
    }

    // Inserts/updates cache entry data.
    function save($key, $data) {
        if (!$this->_active) {
            return;
        }
        // should the data be serialized?
        // serialize all non-scalar data: array and object
        if (!is_scalar($data)) {
            $data = serialize($data);
            $serial = true;
        } else {
            $serial = false;
        }
        // open the file for over-writing. not using file_put_contents
        // becuase we may need to write a serial file too (and avoid race
        // conditions while doing so). don't use include path.
        $file = $this->entry($key);
        $fp = @fopen($file, 'wb', false, $this->context);
        // was it opened?
        if ($fp) {
            // yes.  exclusive lock for writing.
            flock($fp, LOCK_EX);
            fwrite($fp, $data, strlen($data));
            // add a .serial file? (do this while the file is locked to avoid
            // race conditions)
            if ($serial) {
                // use this instead of touch() because it supports stream
                // contexts.
                //file_put_contents($file . '.serial', null, LOCK_EX, $this->context);
                touch($file . '.serial');
            } else {
                // make sure no serial file is there from any previous entries
                // with the same name
                @unlink($file . '.serial', $this->context);
            }
            // unlock and close, then done.
            flock($fp, LOCK_UN);
            fclose($fp);
            return true;
        }
        // could not open the file for writing.
        return false;
    }

    // Inserts cache entry data, but only if the entry does not already exist.
    function add($key, $data) {
        if (!$this->_active) {
            return;
        }
        // what file should we look for?
        $file = $this->entry($key);
        // if the file does not exists or is unreadable, key is available
        if (!file_exists($file) || !is_readable($file)) {
            return $this->save($key, $data);
        }
        // if the file has expired, key is available
        if ($this->isExpired($key)) {
            return $this->save($key, $data);
        }
        // key already exists
        return false;
    }

    // if the file has expired, key is available
    function isExpired($key) {
        $file = $this->entry($key);
        $expire_time = filemtime($file) + $this->_life;
        if (time() > $expire_time) {
            return true;
        } else {
            return false;
        }
    }

    // gets cache entry data.
    function get($key) {
        if (!$this->_active) {
            return;
        }
        // get the entry filename *before* validating;
        // this avoids race conditions.
        $file = $this->entry($key);
        // make sure the file exists and is readable,
        if (file_exists($file) && is_readable($file)) {
            // has the file expired?
            $expire_time = filemtime($file) + $this->_life;
            if (time() > $expire_time) {
                // expired, remove it
                $this->delete($key);
                return false;
            }
        } else {
            return false;
        }
        // file exists; open it for reading
        $fp = @fopen($file, 'rb', false, $this->context);
        // could it be opened?
        if ($fp) {
            // lock the file right away
            flock($fp, LOCK_SH);
            // get the cache entry data.
            // PHP caches file lengths; clear that out so we get
            // an accurate file length.
            clearstatcache();
            $len = filesize($file);
            $data = fread($fp, $len);
            // check for serializing while file is locked
            // to avoid race conditions
            if (file_exists($file . '.serial')) {
                $data = unserialize($data);
            }
            // unlock and close the file
            flock($fp, LOCK_UN);
            fclose($fp);
            // done!
            return $data;
        }
        // could not open file.
        return false;
    }

    // Deletes an entry from the cache.
    function delete($key) {
        if (!$this->_active) {
            return;
        }
        $file = $this->entry($key);
        @unlink($file, $this->context);
        @unlink($file . '.serial', $this->context);
    }

    // Removes all entries from the cache.
    function deleteAll() {
        if (!$this->_active) {
            return;
        }
        // get the list of files in the directory, suppress warnings.
        $list = (array) @scandir($this->path, null, $this->context);
        // delete each file
        foreach ($list as $file) {
            @unlink($this->path . $file, $this->context);
        }
    }

    // Returns the path and filename for the entry key.
    // @param string $key The entry ID.
    // @return string The cache entry path and filename.
    function entry($key) {
        // sostituire con Path::join!
        $path = $this->path . DIRECTORY_SEPARATOR . md5($key);
        return $path;
    }

}

// cache di una intera pagina
// identificata da una chiave ricavata daiparametri con cui e' stata chiamata
// non gestire pagine che richiedono informazioni relative alla sessione corrente con questo meccanismo
class PageCache {

    var $_key = ''; // la chiave univoca che identifica la pagina corrente

    function PageCache() {
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
        //$_SESSION, $_COOKIE,//
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

// TODO: versione con apc
$__RAM_storage = array();

// mantiene in memoria il risultato di una chiamata e se possibile,
// ritorna il risultato in memoria.
// usare se l'output è determinabile dai parametri in input e non varia per parametri
// globali tipo user, db, files su disco, network.
function memoized(Callable $f, array $args = array()) {
    global $__RAM_storage;

    // generic cache key
    // TODO: verificare con closure
    ksort($args);
    $k = $f . ':' . http_build_query($args);

    // if needed update the cache with the result
    $has_key = array_key_exists($k, $__RAM_storage);
    if (!$has_key) {
        $__RAM_storage[$k] = $f($args);
    } else {
        // print 'cache hit! '.$k."\n";
    }

    return $__RAM_storage[$k];
}

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
    //ff( array('a'=>rand(1,$m),'b'=>rand(1,$m) ) );
    memoized('ff', array('a'=>rand(1,$m),'b'=>rand(1,$m) ) );
}
*/

//----------------------------------------------------------------------------
//  Minimalist File Cache
//----------------------------------------------------------------------------

// salva e legge dati, che dureranno in cache 1 gg
class MFCache {
    public static function fetch($cache_key) {
        $path = self::getFileName($cache_key);
        if( !file_exists($path) ) {
            return false;
        }
        return unserialize( file_get_contents($path) );
    }
    public static function store($cache_key, $val, $ttl_secs=3600 ) {
        $path = self::getFileName($cache_key);
        file_put_contents($path, serialize($val) );
    }
    public static $cache_dir = dirname( realpath(__FILE__) );
    public static $cache_date_fmt = 'Ymd';
    // ottiene il filename.  la cache dura una giornata
    public static function getFileName($cache_key) {
        $f = sprintf('%s/cache/%s_%s.txt', self::$cache_dir, $cache_key , date(self::$cache_date_fmt) );
        return $f;
    }
    // eliminare i file più vecchi di 1 giorno
    public static function cleanup() {
        $files = glob(self::$cache_dir."/cache/*");
        $time  = time();
        $s_day = 60*60*24;
        foreach ($files as $file){
            if (is_file($file)){
                if ($time - filemtime($file) >= $s_day*2) {// 2 days
                    unlink($file);
                }
            }
        }
    }

    // refreshes a cached repr if it's outdated
    function refresh($cache_file, $ttl, Closure $cb_generator) {
        if (file_exists($cache_file)) {
            $time = time();
            if ( $time - filemtime($file) >= $ttl ) {
                // modified, clear it
                unlink($cache_file);
                $str = $cb_generator();
                file_put_contents($cache_file, $str );
            }
        } else {
            $str = $cb_generator();
            file_put_contents($cache_file, $str );
        }
    }
}




