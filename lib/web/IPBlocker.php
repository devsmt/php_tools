<?php
declare (strict_types = 1);

// funzione: logga i tentativi di accesso a una risorsa da parte di un IP
// superata una soglia, rifiuta l'accesso, i tentativi sono ricordati per n minuti( ttl_m )
//
// table IP Timestamp
//   if user logs in, delete all "failed_logins" rows for that IP
//   if user unsuccessfully logs in, add "failed_logins" with the current timestamp.
//   BEFORE checking to see if password is correct/incorrect:
//   delete "failed_logins" rows older than max minutes
//   check count of rows in failed_logins for the user attempting to login. If >= $max stop the login attempt
//   notifying the user login is invalid
/*
// throttle
$B = new IPBLocker();
$B->addAttempt($resource_id);
if( !$B->isLegit() ){
$B->makeClientWait();
Logger::log("$IP failed to login {$this->max_attempts} times");
throw new \Exception('too much login attempts from the current IP. check later.');
}
... do login validation
$is_login_ok = true;
if( $is_login_ok ) {
$B->deleteAttempts($IP);
}
 */
class IPBLocker {
    public function __construct($max_attempts = 5, $ttl_m = 60, $IP = null, $resource_id = 'ip_login_log') {
        $this->cache_key = $resource_id;
        $this->max_attempts = $max_attempts;
        $this->ttl_secs = $ttl_m * 60;
        $this->IP = coalesce($IP, Net::getIP());
    }

    /*
    public function throttle( \Closure $_login_validator ) {
    $B->addAttempt();
    if( !$B->isLegit() ){
    $B->makeClientWait();
    Logger::log("$IP failed to login {$this->max_attempts} times");
    throw new \Exception('too much login attempts from the current IP. check later.');
    }
    // ... do login validation
    $is_login_ok = $_login_validator();
    if( $is_login_ok ) {
    $B->deleteAttempts();
    }
    }
     */

    // get IP
    // log IP for the current date
    public function addAttempt() {
        // Atomically fetch or generate a cache entry
        $a_ip_log = apcu_entry($this->cache_key, function ($key) {
            return [];
        }, $this->ttl_secs);
        // assicura che la chiave esista
        if (!isset($a_ip_log[$this->IP])) {
            $a_ip_log[$this->IP] = [];
        }
        // scrivi il valore
        $a_ip_log[$this->IP][] = time();
        // remove old entries
        $a_ip_log[$this->IP] = array_filter($a_ip_log[$this->IP], function ($v) {
            $i_old = time() - $this->ttl_secs;
            // false will be skipped
            return $v > $i_old;
        });
        // store back
        apc_store($this->cache_key, $a_ip_log, $this->ttl_secs);
    }
    // TODO: controllare il tempo intercorso tra le richieste
    public function getAttemptNum() {
        $a_ip_log_all = apcu_entry($this->cache_key, function ($key) {
            return [];
        }, $this->ttl_secs);
        $a_ip_log = isset($a_ip_log_all[$this->IP]) ?? [];
        return count($a_ip_log);
    }
    //
    public function isLegit() {
        // get attempts number
        $attempts_num = $this->getAttemptNum();
        $is_legit = $attempts_num <= $this->max_attempts;
        return $is_legit;
    }
    // fa aspettare un client in modo proporzianale al numero di hit fallite
    public function makeClientWait() {
        $attempts_num = $this->getAttemptNum();
        sleep(2 ^ ($attempts_num - 1));
    }
    // if login is_a ok, delete attempts older than TTL
    public function deleteAttempts() {
        /*
    // get data
    $a_ip_log = apcu_entry($this->cache_key, function ($key) {
    return [];
    }, $this->ttl_secs);
    // elimina
    unset($a_ip_log[$this->IP]);
    // store back
    apc_store($this->cache_key, $a_ip_log, $this->ttl_secs);
     */
    }
}

// funzione:
// per una risorsa
// max NUM tentativi(per login o altro) per IP al giorno
// -scivere in un file, con nome contenente la data odierna, una riga per ogni login(o altra azione)
// -contare le righe che contengono l'ip
// se le richeiste superano la soglia l'accesso è negato
/*
uso:
Boundary::log($id,$resource_id);
if( Boundary::count($id,$resource_id) > $treshold
|| Boundary::is_mechanichal($id,$resource_id)
){
// .. deny access
} else {
// ok
}
 */
class Boundary {
    // si puo usare ip o username
    public static function log($id, $resource_id) {
        $time = date('Y-m-d_H:i:s');
        file_put_contents(self::getFileName(), "$id:$time\n");
    }
    //
    public static function count($id, $resource_id) {
        $s = file_get_contents(self::getFileName());
        if (empty($s)) {
            return 0;
        }
        $a = explode("\n", $s);
        $a = array_filter($a, function ($s) {
            return !empty($s) && (strpos($s, $resource_id) !== false);
        });
        return count($a);
    }
    // se il tempo tra una richiesta e l'altra è eccessivamente breve
    public static function is_mechanichal($id, $resource_id) {}

    //
    protected function getFileName() {
        return sprintf('/tmp/%s_%s.txt', __CLASS__, date('Ymd'));
    }
    // dopo TTL vanno eliminati i dati
    protected function gc() {
    }
}

//  DDOS:
// different web server processes need to access frequently when a file or database would be too slow.
// DDOS_Check uses **shared memory** to track accesses to web pages
// in order to cut off users that abuse a site by bombarding it with requests.
//

class DDOS_Check {
    var $sem_key;
    var $shm_key;
    var $shm_size;
    var $recalc_seconds;
    var $pageview_threshold;
    var $sem;
    var $shm;
    var $data;
    var $exclude;
    var $block_message;
    public function __construct() {
        $this->sem_key = 5000;
        $this->shm_key = 5001;
        $this->shm_size = 16000;
        $this->recalc_seconds = 60;
        $this->pageview_threshold = 30;

        $this->exclude['/ok-to-bombard.html'] = 1;
        $this->block_message = <<<END
<html>
<head><title>403 Forbidden</title></head>
<body>
<h1>Forbidden</h1>
You have been blocked from retrieving pages from this site due to
abusive repetitive activity from your account. If you believe this
is an error, please contact
<a href="mailto:webmaster@example.com?subject=Site+Abuse">webmaster@example.com</a>.
</body>
</html>
END;
    }

    function get_lock() {
        $this->sem = sem_get($this->sem_key, 1, 0600);
        if (sem_acquire($this->sem)) {
            $this->shm = shm_attach($this->shm_key, $this->shm_size, 0600);
            $this->data = shm_get_var($this->shm, 'data');
        } else {
            error_log("Can't acquire semaphore $this->sem_key");
        }
    }

    function release_lock() {
        if (isset($this->data)) {
            shm_put_var($this->shm, 'data', $this->data);
        }
        shm_detach($this->shm);
        sem_release($this->sem);
    }

    function check_abuse($user) {
        $this->get_lock();
        if ($this->data['abusive_users'][$user]) {
            // if user is on the list release the semaphore & memory
            $this->release_lock();
            //  serve the "you are blocked" page
            header('HTTP/1.0 403 Forbidden');
            print $this->block_message;
            return true;
        } else {
            // mark this user looking at a page at this time
            $now = time();
            if (!$this->exclude[$_SERVER['PHP_SELF']]) {
                $this->data['user_traffic'][$user]++;
            }
            // (sometimes) tote up the list and add bad people
            if (!$this->data['traffic_start']) {
                $this->data['traffic_start'] = $now;
            } else {
                if (($now - $this->data['traffic_start']) > $this->recalc_seconds) {
                    while (list($k, $v) = each($this->data['user_traffic'])) {
                        if ($v > $this->pageview_threshold) {
                            $this->data['abusive_users'][$k] = $v;
                            // log the user's addition to the abusive user list
                            error_log("Abuse: [$k] (from " . $_SERVER['REMOTE_ADDR'] . ')');
                        }
                    }
                    $this->data['traffic_start'] = $now;
                    $this->data['user_traffic'] = [];
                }
            }
            $this->release_lock();
        }
        return false;
    }
}
/*
// uso: call its check_abuse( ) method at the top of a page, passing it the username of a logged in user:

$abuse = new DDOS_Check();
if ($abuse->check_abuse($IP)) {
exit;
}
*/
