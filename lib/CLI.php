<?php

ini_set('register_argc_argv', true);
ini_set('max_execution_time', 0);
ini_set('html_errors', false);
ini_set('implicit_flush', false);

class CLI {

    var $argc = 0;
    var $argv = array();
    // array associativo dei comandi e dei parametri passati
    var $args = array();

    function __construct() {
        $sapi = php_sapi_name(); // Server API (cgi, apache, cli, o altro)
        $version = phpversion();
        if ($sapi === 'cli') {
            if (version_compare($version, '4.3.0', '<')) {
                set_time_limit(0);
                if (!defined('STDIN')) {
                    define(STDIN, fopen('php://stdin', 'r'));
                }
                if (!defined('STDOUT')) {
                    define(STDOUT, fopen('php://stdout', 'w'));
                }
                if (!defined('STDERR')) {
                    define(STDERR, fopen('php://stderr', 'w'));
                }
                register_shutdown_function(create_function('', 'fclose(STDIN); fclose(STDOUT); fclose(STDERR); return true;'));
            }
            // se non fossero settate, le copio
            if (empty($GLOBALS['argc'])) {
                $GLOBALS['argc'] = $_SERVER['argc'];
                $GLOBALS['argv'] = $_SERVER['argv'];
            }
        } else {
            echo ("this script is intended to run under CLI\n");
        }
        $this->argc = $GLOBALS['argc'];
        $this->argv = $GLOBALS['argv'];
        $this->parse($GLOBALS['argv']);
    }

    // normalmente non vogliamo permettere l'accesso da web
    public static function checkAccess() {
        if (PHP_SAPI != 'cli') {
            die("questo e' uno script CLI.");
        }
    }

    // determina se chi sta lanciando lo script è l'utente root
    public static function userIsRoot() {
        $processUser = posix_getpwuid(posix_geteuid());
        return $processUser['name'] == 'root';
    }

    // nome della macchia su cui si sta eseguendo
    //   La funzione restituisce un array con informazioni sul sistema. Le chiavi dell'array sono:
    //   sysname - nome del sistema operativo (es. Linux)
    //   nodename - nome del sistema (es. valiant)
    //   release - release del sistema operativo (es. 2.2.10)
    //   version - versione del sistema operativo (es. #4 Tue Jul 20 17:01:36 MEST 1999)
    //   machine - architettura del sistema (es. i586)
    //   domainname - nome del dominio DNS (es. example.com)
    public static function getHostInfo() {
        return posix_uname();
    }

    public static function getHostName() {
        $a_info = posix_uname();
        // $server_name = `hostname -f`;
        // $server_IP = `/sbin/ifconfig eth0 | grep 'inet addr:' | cut -d: -f2 | awk '{ print $1}'`;
        return sprintf('%s %s', $a_info['nodename'], $a_info['domainname']
        );
    }

    // aggiunge risorse per far girare elaborazioni lunghe
    public static function addResources($s_max = 0, $mem = '-1') {
        //  $max_time == 0 run till completion
        set_time_limit($s_max);
        // per evitare che possa bloccarsi durante la notte
        // '256M'
        ini_set('memory_limit', $mem);
    }

    //----------------------------------------------------------------------------
    //  input
    //----------------------------------------------------------------------------
    // cerca tra gli argomenti se è stato passato l'argomento $arg_name es. --doX
    public static function hasArgument($arg_name) {
        global $argv;
        foreach ($argv as $arg) {
            if ($arg === '--' . $arg_name) {
                return true;
            }
        }
        return false;
    }

    //
    public function parse(array $argv) {
        //$qs='--host=localhost --db=test';
        $qs = implode(' ', $argv);
        $a_p = explode('--', $qs);
        foreach ($a_p as $k => $v) {
            $a_par = explode('=', $v);
            if (isset($a_par[1])) {
                $this->args[$a_par[0]] = trim($a_par[1]);
            } else {
                // l'unico parametro che non ha il char '=' e un elemento seguente
                $this->action = $a_par[0];
            }
        }
    }

    function getAction() {
        if (!isset($this->action)) {
            $this->parse();
        }
        return $this->action;
    }

    /*
      I find regex and manually breaking up the arguments instead of havingon $_SERVER['argv'] to do it more flexiable this way.

      cli_test.php asdf asdf --help --dest=/var/ -asd -h --option mew arf moo -z

      Array
      (
      [input] => Array
      (
      [0] => asdf
      [1] => asdf
      )

      [commands] => Array
      (
      [help] => 1
      [dest] => /var/
      [option] => mew arf moo
      )

      [flags] => Array
      (
      [0] => asd
      [1] => h
      [2] => z
      )

      )
     */

    public static function _parse($args) {
        array_shift($args);
        $args = join($args, ' ');
        preg_match_all('/ (--\w+ (?:[= ] [^-]+ [^\s-] )? ) | (-\w+) | (\w+) /x', $args, $match);
        $args = array_shift($match);
        $ret = array('input' => array(), 'commands' => array(), 'flags' => array());
        foreach ($args as $arg) {
            // Is it a command? (prefixed with --)
            if (substr($arg, 0, 2) === '--') {
                $value = preg_split('/[= ]/', $arg, 2);
                $com = substr(array_shift($value), 2);
                $value = join($value);
                $ret['commands'][$com] = !empty($value) ? $value : true;
                continue;
            }
            // Is it a flag? (prefixed with -)
            if (substr($arg, 0, 1) === '-') {
                $ret['flags'][] = substr($arg, 1);
                continue;
            }
            $ret['input'][] = $arg;
            continue;
        }
        return $ret;
    }

    function init_param($param, $def = '') {
        if (!isset($this->args[$param])) {
            $this->args[$param] = $def;
        }
        return $this->args[$param];
    }

    function init_a_param($param, $def = array()) {
        $this->init_param($param, '');
        if (!empty($this->args[$param])) {
            return explode(',', $this->args[$param]);
        } else {
            return $def;
        }
    }

    function read_stdin() {
        if( function_exists('stream_get_contents') ) {
            return stream_get_contents(STDIN);
        } else {
            $b = '';
            while (!feof(STDIN)) {
                $b = fgets(STDIN, 4096);
                break;
            }
            return $b;
        }
    }

    // multiline text
    function read_stdin_text() {
        $b = '';
        while (!feof(STDIN)) {
            $b.= fgets(STDIN, 4096);
            if (substr($b, -3, 1) == ";" || substr($b, -2, 1) == ";")
                break;
        }
        return substr($b, 0, -3);
    }

    function ask_boolean($q, $def = false) {
        echo "$q\n>>>";
        $b = $this->read_stdin();
        return (trim($b) == 'Y') ? true : false;
    }

    function ask_int($q, $def = 0) {
        echo "$q\n>>>";
        $b = $this->read_stdin();
        return (int) $b;
    }

    function ask_string($q, $def = '') {
        echo "$q\n>>>";
        $b = $this->read_stdin();
        //toglie cariage return finale
        //str_replace( chr(10),"",$b);
        //str_replace( chr(13),"",$b);
        if ($b != '') {
            /*
              for($i=0; $i<strlen($b); $i++) {
              echo ord($b[$i]),"\n";
              }
              echo strlen($b);
              return $b;
             */
            return substr($b, 0, -2);
        } else {
            return $def;
        }
    }

    //------------------------------------------------------------------------------
    //  colored output
    //------------------------------------------------------------------------------
    // Returns colored string
    public static function getColoredString($str, $foreground_color = null, $background_color = null) {
        // Set up shell colors
        $a_fg = [
        'black'=>'0,30',
        'dark_gray'=>'1,30',
        'blue'=>'0,34',
        'light_blue'=>'1,34',
        'green'=>'0,32',
        'light_green'=>'1,32',
        'cyan'=>'0,36',
        'light_cyan'=>'1,36',
        'red'=>'0,31',
        'light_red'=>'1,31',
        'purple'=>'0,35',
        'light_purple'=>'1,35',
        'brown'=>'0,33',
        'yellow'=>'1,33',
        'light_gray'=>'0,37',
        'white'=>'1,37',
        ];
        // background
        $a_bg = [
        'black'=>'40',
        'red'=>'41',
        'green'=>'42',
        'yellow'=>'43',
        'blue'=>'44',
        'magenta'=>'45',
        'cyan'=>'46',
        'light_gray'=>'47',
        ];

        $s = '';
        // Check if given foreground color found
        if (isset($a_fg[$foreground_color])) {
            $s.= "\033[" . $a_fg[$foreground_color].'m';
        }
        // Check if given background color found
        if (isset($a_bg[$background_color])) {
            $s.= "\033[" . $a_bg[$background_color].'m';
        }
        // Add string and end coloring
        $s.= $str . "\033[0m";
        return

    /*
      static function prompt($promptStr,$defaultVal=false){

      if($defaultVal) {
      // If a default set
      echo $promptStr. "[". $defaultVal. "] : ";
      // print prompt and default
      } else {
      // No default set
      // print prompt only
      echo $promptStr. ": ";
      }

      $name = chop(fgets(STDIN)); // Read input. Remove CR
      if(empty($name)) {          // No value. Enter was pressed
      return $defaultVal;     // return default
      } else {                    // Value entered
      return $name;           // return value
      }
      }
     */

    // print a colored string
    public static function printc($s, $fc = "purple", $bgc = "yellow") {
        echo self::getColoredString("$s\n", $fc, $bgc);
    }

    /* uso:
      for($x=1;$x<=100;$x++){
      show_status($x, 100);
      usleep(100000);
      }
     */

    static function show_status($done, $total, $size = 30) {
        static $start_time;
        // if we go over our bound, just ignore it
        if ($done > $total)
            return;
        if (empty($start_time))
            $start_time = time();
        $now = time();
        $perc = (double) ($done / $total);
        $bar = floor($perc * $size);
        $status_bar = "\r[";
        $status_bar.= str_repeat("=", $bar);
        if ($bar < $size) {
            $status_bar.= ">";
            $status_bar.= str_repeat(" ", $size - $bar);
        } else {
            $status_bar.= "=";
        }
        $disp = number_format($perc * 100, 0);
        $status_bar.= "] $disp%  $done/$total";
        $rate = ($now - $start_time) / $done;
        $left = $total - $done;
        $eta = round($rate * $left, 2);
        $elapsed = $now - $start_time;
        $status_bar.= " remaining: " . number_format($eta) . " sec.  elapsed: " . number_format($elapsed) . " sec.";
        echo "$status_bar  ";
        flush();
        // when done, send a newline
        if ($done == $total) {
            echo "\n";
        }
    }


    /**
     * Runs an external command (runs in own thread) with input and output pipes.
     * Returns the exit code from the process.
     */
    function pipeExec($cmd, $input, &$output){
        $descspec = array(
                0=>array("pipe","r"),
                1=>array("pipe","w"),
                2=>array("pipe","w"));
        $ph = proc_open($cmd, $descspec, $pipes);
        if(!$ph) {
            return -1;
        }
        fclose($pipes[2]); // ignore stderr
        fwrite($pipes[0], $input);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        return proc_close($ph);
    }
      
   
}

/*
  // Test some basic printing with Colors class
  cli::printc("Testing Colors class, this is purple string on yellow background.", "purple", "yellow")	      ;
  cli::printc("Testing Colors class, this is blue string on light gray background.", "blue", "light_gray")   ;
  cli::printc("Testing Colors class, this is red string on black background.", "red", "black") 			      ;
  cli::printc("Testing Colors class, this is cyan string on green background.", "cyan", "green")			  ;
  cli::printc("Testing Colors class, this is cyan string on default background.", "cyan") 				      ;
  cli::printc("Testing Colors class, this is default string on cyan background.", null, "cyan") 			  ;
 */

// controller for CLI scripts
class CLIController {

    protected $action = 'help';

    function __construct() {
        if (CLI::getAction()) {
            $this->action = CLI::getAction();
        }
        $method = 'Action' . ucfirst($this->action);
        if (method_exists($this, $method)) {
            try {
                $this->$method();
            } catch (Exception $e) {
                echo sprintf("Exception file:%s line:%s: \n" . "trace:%s \n", $e->getFile(), $e->getLine(), $e->getMessage(), $e->getTraceAsString()
                );
            }
        } else {
            echo sprintf("azione $method non implementata %s %s\n", __LINE__, __FILE__);
        }
    }

    // method catchall
    function ActionHelp() {
        return "implementa ActionHelp\n";
    }

}

// funzione: permette il monitoring del batch job
// use: CLIMonitor::registerMonitoringMailHook(array('test@gmail.com'));
class CLIMonitor {

    // notifica l'amministratore di errori nella procedura di importazione
    public static function registerMonitoringMailHook(array $a_dev_email) {
        $funcname = __FUNCTION__;
        register_shutdown_function(function () use($a_dev_email, $funcname) {
            $errfile = "unknown file";
            $errstr = "shutdown";
            $errno = E_CORE_ERROR;
            $errline = 0;

            $error = error_get_last();
            $trace = print_r(debug_backtrace(false), true);
            $server_name = Common::getServerName();
            $server_IP = Common::getServerIP();

            if ($error !== NULL) {
                $errno = $error["type"];
                $errfile = $error["file"];
                $errline = $error["line"];
                $errstr = $error["message"];


                $content = "";
                $content .= "Error : $errstr  \n";
                $content .= "Errno : $errno   \n";
                $content .= "File  : $errfile \n";
                $content .= "Line  : $errline \n";
                $content .= "Trace : $trace   \n";
                $content .= "\n";
                $content .= sprintf("generated by:%s %s date:", $funcname, __FILE__, date('Y-m-d H:i:s'));


                $subject = "CLI fatal error. server:$server_name($server_IP)";

                foreach ($a_dev_email as $to) {
                    $mail_res = mail($to, $subject, $content);
                }
            }
        });
    }

}
