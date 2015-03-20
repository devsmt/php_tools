<?php

// funzioni con gli indirizzi di rete
class Net {

    // IP anche se dietro un proxy
    public static function getIP() {
        $IP = '';
        if ($_SERVER['HTTP_CLIENT_IP']) {
            $IP = $_SERVER['HTTP_CLIENT_IP'];
        } elseif ($_SERVER['HTTP_X_FORWARDED_FOR']) {
            $IP = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif ($_SERVER['HTTP_X_FORWARDED']) {
            $IP = $_SERVER['HTTP_X_FORWARDED'];
        } elseif ($_SERVER['HTTP_FORWARDED_FOR']) {
            $IP = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif ($_SERVER['HTTP_FORWARDED']) {
            $IP = $_SERVER['HTTP_FORWARDED'];
        } elseif ($_SERVER['REMOTE_ADDR']) {
            $IP = $_SERVER['REMOTE_ADDR'];
        } else {
            $IP = 'UNKNOWN';
        }
        return $IP;
    }


    // Returns the user IP address
    public function getUserHostAddress() {
        static $ip = 0;
        if ( !empty($ip) ) {
            return $ip;
        }
        $a_k = array(
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        foreach ($a_k as $key) {
            if( true === array_key_exists($key, $_SERVER)) {
                foreach( explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    // Allow only IPv4 address, Deny reserved addresses, Deny private addresses
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return ($ip = $ip);
                    }
                }
            }
        }
        return ($ip = '0.0.0.0');
    }



    // es.  188.135.166.0 - 188.135.167.255
    // $wlist = array( '188.135.166.', '188.135.167.');
    public static function checkWhiteList(array $a, $IP = null) {
        if (empty($IP)) {
            $IP = Net::getIP();
        }
        foreach ($a as $s) {
            if (strpos($s, $IP) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Convert one or more comma separated IPs to hostnames
     *
     * If $conf['dnslookups'] is disabled it simply returns the input string
     *
     * @param  string $ips comma separated list of IP addresses
     * @return string a comma separated list of hostnames
     */
    public static function getHostsByAddrs(array $ips) {

        $hosts = array();
        $ips = explode(',', $ips);

        if (is_array($ips)) {
            foreach ($ips as $ip) {
                $hosts[] = gethostbyaddr(trim($ip));
            }
            return join(',', $hosts);
        } else {
            return gethostbyaddr(trim($ips));
        }
    }

    // @see https://github.com/rmccue/Requests
    // permette di ottenre il contenuto della pagina servita ad un indirizzo specifico
    public static function getContent($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FAILONERROR, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0)');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        if (preg_match('/^https:\/\//sim', $url) == true) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        ob_start();
        curl_exec($ch);
        curl_close($ch);
        $string = ob_get_contents();
        ob_end_clean();
        return $string;
    }

}
