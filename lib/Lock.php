<?php
// assicura che la funzione non sia eseguita in concorrenza con altre chiamate
/*
// example use
$k = 'mykey';
if (LockMem::acquire($k)) {
do_something_that_requires_a_lock();
LockMem::release($k);
}
// example use
LockMem::tryUntillAvailable($key, function(){
// atomic operation
});
 */
class LockMem {
    public static function doIfAvailable($key, Closure $operation) {
        if (LockMem::acquire($k)) {
            try {
                $operation();
                LockMem::release($k);
                return true;
            } catch (Exception $e) {
                $fmt = 'Exception: <b>%s</b> line:%s file:%s<br> trace:<pre>%s</pre>';
                $msg = sprintf($fmt, $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
                die($msg);
            }
        }
        return false;
    }
    public static function tryUntillAvailable($key, Closure $operation, $n_times = 10, $pause_s = 2) {
        for ($i = 1; $i <= $n_times; $i++) {
            if (self::doIfAvailable($key, $operation)) {
                return true;
            } else {
                sleep($pause_s);
            }
        }
    }
    public static function acquire($key, $expire = 60) {
        if (is_locked($key)) {
            return null;
        }
        return apc_store($key, 1, $expire);
    }
    public static function release($key) {
        if (!self::isLocked($key)) {
            return null;
        }
        return apc_delete($key);
    }
    public static function isLocked($key) {
        return apc_fetch($key) == 1;
    }
}
// PHP needs to be compiled with sysvsem support in order to use sem_* functions
/* uso:
$k = 1000;
LockSem::tryUntillAvailable($k, function(){} );
 */
class LockSem {
    public static function tryUntillAvailable($key, Closure $operation) {
        $is_int = ctype_digit($key);
        if (!$is_int) {
            die('la chiave deve essere un intero');
        }
        // get the resource for the semaphore
        $sem_res = sem_get($key, 1, 0666, 0);
        // try to acquire the semaphore: this function **will block until** the sem will be available
        if (sem_acquire($sem_res)) {
            try {
                $operation();
            } catch (Exception $e) {
                $fmt = 'Exception: <b>%s</b> line:%s file:%s<br> trace:<pre>%s</pre>';
                $msg = sprintf($fmt, $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
            }
            // release the semaphore so other process can use it
            sem_release($sem_res);
        }
    }
}
// funzione: crea un lock per un file
class LockFile {
    //
    // Returns the full path to the file for locking the page while editing.
    //
    function getLockFilePath($id) {
        return $conf_lockdir . '/' . md5(cleanID($id)) . '.lock';
    }
    //
    function cleanID($id) {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $id);
    }
    //
    // Checks if a given page is currently locked.
    //
    // removes stale lockfiles
    //
    function checklock($id) {
        global $conf;
        $lock = getLockFilePath($id);
        //no lockfile
        if (!@file_exists($lock)) {
            return false;
        }
        //lockfile expired
        if ((time() - filemtime($lock)) > $conf['locktime']) {
            @unlink($lock);
            return false;
        }
        //my own lock
        list($ip, $session) = explode("\n", file_get_contents($lock));
        if ($ip == $_SERVER['REMOTE_USER'] || $ip == clientIP() || $session == session_id()) {
            return false;
        }
        return $ip;
    }
    //
    // Lock a page for editing
    //
    function lock($id) {
        $lock = getLockFilePath($id);
        if ($_SERVER['REMOTE_USER']) {
            file_put_contents($lock, $_SERVER['REMOTE_USER']);
        } else {
            file_put_contents($lock, clientIP() . "\n" . session_id());
        }
    }
    // Unlock a page if it was locked by the user
    // @param string $id page id to unlock
    // @return bool true if a lock was removed
    function unlock($id) {
        $lock = getLockFilePath($id);
        if (@file_exists($lock)) {
            list($ip, $session) = explode("\n", file_get_contents($lock));
            if ($ip == $_SERVER['REMOTE_USER'] || $ip == Net::getIP() || $session == session_id()) {
                @unlink($lock);
                return true;
            }
        }
        return false;
    }
}
//  run the tests:
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/Test.php';
}