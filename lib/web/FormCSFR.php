<?php

//
// The rule of thumb is to make GET requests they can be run over and over without
// modifing the state of the application (only selects and Read ops).
//
//
// controlla che la richiesta sia stata inviata da una form generata dal nostro server e non da un client qualsiasi
//
// le form sicure contengono una firma da noi emessa,
// sul server amntiene un registro delle firme valide
// se la firma è registrata, viene eliminata ed emessa una ulteriore
// se la firma non è (più) valida, la form è stata generara da un'altro sistema e va scartata
//
// This technique is based on the protection mechanism in the Django
// project, detailed at and thanks to
// https://docs.djangoproject.com/en/dev/ref/contrib/csrf/.
//
// abstraction over how session variables are stored. Replace if you don't use native PHP sessions.
function session_set($key, $value) {
    if (!isset($_SESSION)) {
        session_start();
    }
    $_SESSION[$key] = $value;
}
function session_delete($key) {
    unset($_SESSION[$key]);
}
function session_get($key, $def=false) {
    return isset($_SESSION[$key]) ? $_SESSION[$key] : $def;
}

class CSRF {
    const NAME = 'CSRF_token';
    // generate a strong rand
    public static function rand() {
        // use If SHA512 is available,
        // otherwise a 512 bit random string in the same format is generated.
        if (function_exists('hash_algos') && in_array('sha512', hash_algos())) {
            $rand = hash('sha512', mt_rand(0, mt_getrandmax())   );
        } elseif( function_exists('string_random')  ) {
            $rand = string_random($l=128);
        } else {
            $SALT = __DIR__;
            $rand = sha1(session_id() . rand(0, 10000) . time() . $SALT);
        }
        return $rand;
    }
    // rand gen and set
    public static function get() {
        // generated once per request
        if (empty(self::$val)) {
            self::$val = self::rand();
            session_set($k, self::$val);
            // .';'.time(); // can also set a timestamp to expire old token
        }
        return self::$val;
    }
    // add hidden token input with to POST forms
    public static function getHTML() {
        $k = self::NAME;
        $val = self::get();
        return sprintf('<input type="hidden" name="%s" value="%s" />', $k, $val);
    }
    // there can only be one POST per session
    // - Sessions not active: validate fails
    // - Token found but not the same, or token not found: validation fails
    // - Token found and the same: validation succeeds
    // TODO: sending a separate header (X-CSRFToken) when forms are submitted with Ajax
    public static function isValid() {
        $k = self::NAME;
        $val = isset($_POST[$k]) ? $_POST[$k] : false;
        $is_present = session_get($k) && $val;
        $valid = $is_present && ( session_get($k) === $val ); // inviato e uguale
        session_delete( $k ); // removes the token from sessions, ensuring one-timeness
        return $valid;
    }
    // public static function isExpired($time, $timeout = 90) {
    //     return (time() - $time) > $timeout;
    // }
}











