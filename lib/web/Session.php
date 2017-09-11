<?php

// helpers
class Session {

    public static function has($k) {
        $s = new SessionDriverPHP5();
        return $s->has($k);
    }

    public static function get($k, $d) {
        $s = new SessionDriverPHP5();
        return $s->get($k, $d);
    }

    public static function set($k, $v) {
        $s = new SessionDriverPHP5();
        $s->set($k, $v);
    }

    public static function dump() {
        $s = new SessionDriverPHP5();
        $s->dump();
    }

    public static function destroy() {
        $s = new SessionDriverPHP5();
        $s->destroy();
    }

    public static function clear() {
        $s = new SessionDriverPHP5();
        $s->clear();
    }

}
interface ISessionDriver {

    function __construct($options);

    function open();

    function close();

    function has($k);

    function get($k, $d);

    function set($k, $v);

    function delete($k);

    function clear();

    function dump();

    function destroy();
}
class SessionDriverPHP5 implements ISessionDriver {

    function __construct($options = []) {
        if (!SessionDriverPHP5::started()) {
            @session_start();
        }
    }

    function has($k) {
        return isset($_SESSION[$k]);
    }

    function get($k, $d = null) {
        return isset($_SESSION[$k]) ? $_SESSION[$k] : $d;
    }

    function set($k, $v) {
        $_SESSION[$k] = $v;
    }

    function delete($k) {
        unset($_SESSION[$k]);
    }

    function destroy() {
        if (SessionDriverPHP5::started()) {
            session_destroy();
        }
    }

    function started() {
        $id = session_id();
        return $id !== '';
    }

    function dump() {
        return print_r($_SESSION);
    }

    function clear() {
        if (SessionDriverPHP5::started()) {
            foreach ($_SESSION as $k => $v) {
                unset($_SESSION[$k]);
            }
        }
    }

    function open() {

    }

    function close() {

    }

}

/*
class SessionDriverDB implements ISessionDriver {

  function __construct($options=[] )  {
  $this->database = Weasel::GetDB();//$locator->get( 'database' );
  $this->request  = Weasel::GetRequest();//$locator->get( 'request' );

  session_set_save_handler( array( &$this, 'open' ),
  array( &$this, 'close' ),
  array( &$this, 'read' ),
  array( &$this, 'write' ),
  array( &$this, 'destroy' ),
  array( &$this, 'clean' ) );

  register_shutdown_function( 'session_write_close' );

  if ( !$this->request->has( 'test', 'cookie' ) )  {
  setcookie( 'test', 'accept', time()  + 60 * 60 * 24 * 30, '/', NULL, false );
  }

  if ( $this->request->has( 'test', 'cookie' ) )  {
  session_set_cookie_params( 0, '/' );

  session_start();
  }
  }

  function set( $k, $value )  {
  $_SESSION[ $k ]  = $value;
  }

  function get($k, $d)  {
  return ( isset( $_SESSION[ $k ] )  ? $_SESSION[ $k ]  : NULL );
  }

  function has( $k )  {
  return isset( $_SESSION[ $k ] );
  }

  function delete( $k )  {
  if ( isset( $_SESSION[ $k ] ) )  {
  unset( $_SESSION[ $k ] );
  }
  }

  function open()  {
  return TRUE;
  }

  function close()  {
  return TRUE;
  }

  function read( $session_id )  {
  $result = $this->database->getRow( $this->database->parse( "select value from session where session_id = '?' and expire > '?'", $session_id, time() ) );

  return ( isset( $result[ 'value' ] )  ? $result[ 'value' ]  : NULL );
  }

  function write( $session_id, $data )  {
  if ( !$this->database->getRow( $this->database->parse( "select * from session where session_id = '?'", $session_id ) ) )  {
  $sql = "insert into session set session_id = '?', expire = '?', `value` = '?', ip = '?', time = now(), url = '?'";
  $this->database->query( $this->database->parse( $sql, $session_id, time()  + $this->expire, $data, $_SERVER[ 'REMOTE_ADDR' ], $_SERVER[ 'REQUEST_URI' ] ) );
  } else {
  $sql = "update session set expire = '?', `value` = '?', ip = '?', time = now(), url = '?' where session_id = '?'";
  $this->database->query( $this->database->parse( $sql, time()  + $this->expire, $data, $_SERVER[ 'REMOTE_ADDR' ], $_SERVER[ 'REQUEST_URI' ], $session_id ) );
  }

  return $this->database->countAffected();
  }

  function destroy( $session_id )  {
  $this->database->query( $this->database->parse( "delete from session where session_id = '?'", $session_id ) );

  return $this->database->countAffected();
  }

  function clean( $maxlifetime )  {
  $this->database->query( $this->database->parse( "delete from session where expire < '?'", time() ) );

        return $this->database->countAffected();
    }
}
*/
/*
class SessionDriverPHP4 implements ISessionDriver {


/*----------------------------------------------------------------------------
uso:
register_shutdown_function(function(){
    MobileSession::save();
    MobileSession::gc();
});
*/
// implmentazione minimale di sessione basata su file
class SessionDriverFile implements ISessionDriver {
    static $ID = '';
    static $data = [];
    public static function generateID() {
        self::$ID = self::cleanSID(uniqid('session_mobile', true));
        self::open();
        return self::$ID;
    }
    //assicura che gli SID siano testo semplice  enon contengano nulla di pericoloso
    protected static function cleanSID($session_id){
        $session_id = preg_replace('/[^a-zA-Z0-9_]/', '', $session_id );
        $session_id = substr($session_id,0,32);
        return $session_id;
    }
    // sessione che sto servendo attualmente
    public static function currentID(){
        return self::$ID;
    }
    public static function setCurrentID($session_id){
        self::$ID = self::cleanSID($session_id);
        self::open();
        return self::$ID;
    }
    public static function getFilePath(){
        if( empty(self::$ID) ) {
            // sessione non è stata aperta
            return false;
        }
        $d = realpath(APPLICATION_PATH.'/../var/mobile_session/');
        if( empty($d) || !file_exists($d) ) {
            die(__METHOD__." '$d' non esiste ");
        }
        $p = $d .'/'.self::$ID;
        return $p;
    }
    public static function set($k,$v){
        self::$data[$k]=$v;
    }
    public static function get($k, $d=''){
        return isset(self::$data[$k]) ? self::$data[$k] : $d;
    }
    public static function open(){
        // try load data
        $path =  self::getFilePath();

        if( $path && file_exists($path) ) {
            $json_str = file_get_contents($path);
            if( !empty($json_str) ) {
                $data =  json_decode($json_str, $use_assoc=true);
                self::$data = $data;
                return true;
            }
        }
    }
    public static function save(){
        $path = self::getFilePath();
        if( !empty(self::$ID) && $path && !empty(self::$data) ) {
            $str = json_encode(self::$data);
            file_put_contents($path, $str, LOCK_EX );
        }
    }
    // elimina sessioni vecchie
    public static function gc(){
        $dir_path = dirname( self::getFilePath() );
        if( !empty($dir_path) ) {
            // max 3 gg
            `find $dir_path -maxdepth 1 -type f -mtime +3 -exec rm -f {} \;`;
        }
    }
}






// customize if clashes with other libraries
define('__FLASH__', '__FLASH__', false);

//
// Provides messages processing functionality
//
class SessionMessages {

    public static function error($msg) {
        $this->add('error', $msg);
    }

    public static function succes($msg) {
        $this->add('warn', $msg);
    }

    public static function info($msg) {
        $this->add('info', $msg);
    }

    public static function warn($msg) {
        $this->add('warn', $msg);
    }

    // Get currently loaded messages by message type
    public static function get($msg_type = null) {
        if (!is_null($msg_type)) {
            if (isset($_SESSION[__FLASH__][$msg_type])) {
                return $_SESSION[__FLASH__][$msg_type];
            } else {
                return [];
            }
        } else {
            return $_SESSION[__FLASH__];
        }
    }

    // Add a message of the specified type
    // Messages are stored in the SESSION so they persist
    // until they are outputted to the page
    protected function add($type, $msg) {
        if (!isset($_SESSION[__FLASH__])) {
            $_SESSION[__FLASH__] = [];
        }
        $type = strtolower($type);
        if (!in_array($type, ['error', 'success', 'info']);
        ) {
            $msg = sprintf('Errore %s unrecognized message type "%s" ', __CLASS__, $type);
            throw new Exception($msg);
        }
        if (isset($_SESSION[__FLASH__][$type])) {
            $_SESSION[__FLASH__][$type][] = $msg;
        } else {
            $_SESSION[__FLASH__][$type] = [$msg];
        }
        return count($_SESSION[__FLASH__][$type]);
    }

    // display all messages
    public static function displayAll(Closure $render_cb, $clear = true) {
        if (!isset($_SESSION[__FLASH__])) {
            return 0;
        }
        $msg_count = count($_SESSION[__FLASH__]);
        if ($msg_count > 0) {
            $msg_out = $render_cb($_SESSION[__FLASH__]);
            if ($clear) {
                $this->clear();
            }
            return $msg_count;
        } else {
            return 0;
        }
    }

    // Clear all currently loaded messages
    public static function clear($msg_type = null) {
        if (!isset($_SESSION[__FLASH__])) {
            return;
        }
        if (is_null($msg_type)) {
            unset($_SESSION[__FLASH__]);
        } elseif (isset($_SESSION[__FLASH__][$msg_type])) {
            unset($_SESSION[__FLASH__][$msg_type]);
        }
    }

}

/***
 * Starts a session with a specific timeout and a specific GC probability.
 * @param int $ttl The number of seconds until it should time out.
 * @param int $gc_probability The probablity, in int percentage, that the garbage
 *        collection routine will be triggered right now.
 * @param strint $cookie_domain The domain path for the cookie.
 */
function session_start_timeout($path, $ttl=5, $gc_probability=100, $cookie_domain='/') {
    // Set the max lifetime
    ini_set('session.gc_maxlifetime', $ttl);

    // Set the session cookie to timout
    ini_set('session.cookie_lifetime', $ttl);


    // the session must be stored in a separate directory to persist more than
    // php default/debian default
    ini_set('session.save_path', $path);

    // Set the chance to trigger the garbage collection.
    ini_set('session.gc_probability', $gc_probability);
    ini_set('session.gc_divisor', 100); // Should always be 100

    // Start the session!
    session_start();

    // Renew the time left until this session times out.
    // If you skip this, the session will time out based
    // on the time when it was created, rather than when
    // it was last used.
    if(isset($_COOKIE[session_name()])) {
        setcookie(session_name(), $_COOKIE[session_name()], time() + $ttl, $cookie_domain);
    }
}

