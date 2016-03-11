<?php
/*
different web server processes need to access frequently when a file or database would be too slow.
Web_Abuse_Check uses **shared memory** to track accesses to web pages
in order to cut off users that abuse a site by bombarding it with requests.
*/

class Web_Abuse_Check {
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
    public function __construct(){
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
                    $this->data['user_traffic'] = array();
                }
            }
            $this->release_lock();
        }
        return false;
    }
}
/*
// uso: call its check_abuse( ) method at the top of a page, passing it the username of a logged in user:

// get_user_IP() is a function that finds out if a user is logged in
if ($user = get_user_IP()) {
    $abuse = new pc_Web_Abuse_Check();
    if ($abuse->check_abuse($user)) {
        exit;
    }
}
*/

