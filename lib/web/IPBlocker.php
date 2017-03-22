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
$B->addAttempt();
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
    public function __construct($max_attempts = 5, $ttl_m = 60, $IP=null) {
        $this->cache_key = 'ip_login_log';
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
    //
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
    public function makeClientWait(){
        $attempts_num = $this->getAttemptNum();
        sleep(2 ^ ($attempts_num - 1));
    }
    // if login is_a ok, delete old attempts
    public function deleteAttempts() {
        // get data
        $a_ip_log = apcu_entry($this->cache_key, function ($key) {
            return [];
        }, $this->ttl_secs);
        // elimina
        unset($a_ip_log[$this->IP]);
        // store back
        apc_store($this->cache_key, $a_ip_log, $this->ttl_secs);
    }
}
