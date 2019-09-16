<?php
// funzioni con gli indirizzi di rete
class Net {
    // IP anche se dietro un proxy
    public static function getIP($def = 'UNKNOWN'): string {
        static $ip = null;
        if (!empty($ip)) {
            return $ip;
        }
        return h_get($_SERVER, 'REMOTE_ADDR', $def);
    }
    public static function get_forwarded_IP() {
        //Do not check any HTTP_* headers for the client IP unless you specifically know your application is configured behind a reverse proxy.
        //Trusting the values of these headers unconditionally will allow users to spoof their IP address.
        //The only $_SERVER field containing a reliable value is REMOTE_ADDR.
        return $IP = coalesce(
            h_get($_SERVER, 'HTTP_X_REAL_IP'), // nginx rewrite
            h_get($_SERVER, 'REMOTE_ADDR'),
            'UNKNOWN'
        );
        // others possible header rewrites:
        // h_get($_SERVER,'HTTP_CLIENT_IP'),
        // h_get($_SERVER,'HTTP_X_FORWARDED_FOR'),
        // h_get($_SERVER,'HTTP_X_FORWARDED'),
        // h_get($_SERVER,'HTTP_FORWARDED_FOR'),
        // h_get($_SERVER,'HTTP_FORWARDED'),
    }
    /*
    $a_k = [
    'HTTP_X_REAL_IP',
    'HTTP_CLIENT_IP',
    'HTTP_X_FORWARDED_FOR',
    'HTTP_X_FORWARDED',
    'HTTP_X_CLUSTER_CLIENT_IP',
    'HTTP_FORWARDED_FOR',
    'HTTP_FORWARDED',
    'REMOTE_ADDR',
    ];
    foreach ($a_k as $k) {
    if (isset($_SERVER[$k]) && !empty($_SERVER[$k])) {
    // server with multiple interfaces, contains the ',' char
    // foreach( explode(',', $_SERVER[$k]) as $ip) { }
    $ip = $_SERVER[$k];
    $ip = trim($ip);
    // Allow only IPv4 address, Deny reserved addresses, Deny private addresses
    // $is_valid = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    return $ip;
    }
    }
    return $def;
     */
    // validazione su IP
    public static function checkIP($ip) {
        // Allow only IPv4 address, Deny reserved addresses, Deny private addresses
        return $is_valid = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }
    // es.  111.112.113.0 - 111.112.113.255
    // $wlist = [ '188.135.166.', '188.135.167.'];    // Net::checkWhiteList($wlist, Net::getIP() );
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
    //
    // Convert one or more comma separated IPs to hostnames
    //
    // If $conf['dnslookups'] is disabled it simply returns the input string
    //
    // @param  string $ips comma separated list of IP addresses
    // @return string a comma separated list of hostnames
    //
    public static function getHostsByAddrs(array $ips) {
        $hosts = [];
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
    public static function getContent($url, $opts = []) {
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
        // apply opts
        if (is_array($opts) && $opts) {
            foreach ($opts as $key => $val) {
                curl_setopt($ch, $key, $val);
            }
        }
        // transfer
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (FALSE === ($retval = curl_exec($ch))) {
            $err = curl_error($ch);
            $msg = sprintf('Errore CURL %s ', $err);
            throw new Exception($msg);
        } else {
            curl_close($ch);
            return $retval;
        }
    }
    // richiama una url con dati in post
    protected static function post($url, array $fields) {
        foreach ($fields as $key => $value) {
            $value = urlencode($value);
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim($fields_string, '&');
        // open connection
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        // transfer
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (FALSE === ($retval = curl_exec($ch))) {
            $err = curl_error($ch);
            $msg = sprintf('Errore CURL %s ', $err);
            throw new Exception($msg);
        } else {
            curl_close($ch);
            return $retval;
        }
    }
    /*
    $endpoint = "https://graph.facebook.com/?id=" . urlencode($uri);
    $curlopts = [ CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4 ];
    $retval = http_get_contents($endpoint, $curlopts);
     */
    function http_get_contents($url, $opts = []) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, "{$_SERVER['SERVER_NAME']}");
    }
    // verifica un IP su diversi database di IP malevoli
    function checkDNSBL($ip) {
        $dnsbl_check = [
            'bl.spamcop.net',
            'list.dsbl.org',
            'sbl.spamhaus.org',
            'xbl.spamhaus.org',
        ];
        if (!empty($ip)) {
            $reverse_ip = implode('.', array_reverse(explode(".", $ip)));
            $reverse_ip = idn_to_ascii($reverse_ip);
            foreach ($dnsbl_check as $server_name) {
                if (checkdnsrr($reverse_ip . '.' . $server_name . '.', 'A')) {
                    return $rip . '.' . $server_name;
                }
            }
        }
        return false;
    }
    // verifica se una porta locale è aperta o chiusa
    function portIsOpen($port = 25) {
        $fp = fsockopen('127.0.0.1', $port, $errno, $errstr, 5);
        if (!$fp) {
            // port is closed or blocked
            return false;
        } else {
            // port is open and available
            fclose($fp);
            return true;
        }
    }
}
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/Test.php';

}