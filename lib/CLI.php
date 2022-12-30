<?php
declare (strict_types = 1);
ini_set('register_argc_argv', '1');
ini_set('max_execution_time', '0');
ini_set('html_errors', '0');
ini_set('implicit_flush', '0');
ini_set('apc.enable_cli', '1');
// functions for building console programs
class CLI {

    // normalmente non vogliamo permettere l'accesso da web
    public static function checkAccess() {
        if (strtolower(PHP_SAPI) != 'cli') {
            die("questo e' uno script CLI.\n");
        }
    }
    // determina se chi sta lanciando lo script è l'utente root
    public static function userIsRoot($user = 'root') {
        $processUser = posix_getpwuid(posix_geteuid());
        return $processUser['name'] == $user;
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
    /*public static function hasArgument($arg_name) {
    global $argv;
    foreach ($argv as $arg) {
    if ($arg === '--' . $arg_name) {
    return true;
    }
    }
    return false;
    }*/
    // verifica che sia stato passato un valore in cli
    public static function hasFlag($flag, $argv = null) {
        if (empty($argv)) {
            if (isset($_SERVER['argv']) && !empty($_SERVER['argv'])) {
                $argv = $_SERVER['argv'];
            }
        }
        $s_argv = implode(" ", $argv) . " ";
        $substr = " --" . $flag . " ";
        return strpos($s_argv, $substr) !== false;
    }
    public static $h_args = [];
    // get a single flag
    public static function getFlag($flag, $def = '', $argv = null) {
        if (empty(self::$h_args)) {
            self::$h_args = self::getConsoleArgs($argv);
        }
        return h_get($h_args, $flag, $def);
    }
    //
    // parsing argomenti con dato e flags
    //
    // @param   $argv
    // @return hash [ param => val ]
    //
    public static function getConsoleArgs($argv = null) {
        if (empty($argv)) {
            if (isset($_SERVER['argv']) && !empty($_SERVER['argv'])) {
                $argv = $_SERVER['argv'];
            } else {
                return; // invalid env
            }
        }
        $a_args = [];
        foreach ($argv as $arg) {
            if (preg_match('/--([^=]+)="(.*)"/', $arg, $match)) {
                // "" enclosed args
                $k = $match[1];
                $v = $match[2];
                $v = trim($v, $charlist = '"');
                $a_args[$k] = $v;
            } elseif (preg_match('/--([^=]+)=(.*)/', $arg, $match)) {
                $k = $match[1];
                $v = $match[2];
                $a_args[$k] = $v;
            } elseif (preg_match('/-([a-zA-Z0-9])/', $arg, $match)) {
                $a_args[$match[1]] = true;
            } else {
                // what's that?
            }
        }
        return $a_args;
    }
    /* uso:
    // example.php -r=1  --optional=text --debug
    $cl_options_parsed = cli_getopt(
    $a_opts = [
    'r:' => 'required:',//:=>required
    'o::' => 'optional::', //::=>optional
    ],
    $a_flags = [
    'd' => 'debug', // flag
    ],
    $a_defaults=[
    'r' => 0,
    'o' => 'o_default',
    'd' => false
    ] );
     */
    // will stop parsing options upon the '--'
    // arguments not listed will be ignored
    // it keep in sync short and long
    // better handling of flags
    // defaults: use short version for defaults
    public static function getopt(array $a_opts, array $a_flags, array $a_defaults = []): array{
        // ottiene il valore
        $get_result = function ($a_res, $short, $long) use ($a_defaults, $a_flags) {
            // nel caso sia un parametro flag,
            if (isset($a_flags[$short]) || isset($a_flags[$long])) {
                // getopt setta la chiave se passato, atrimenti non la setta
                if (isset($a_res[$short]) || isset($a_res[$long])) {
                    return true;
                } else {
                    return (int) ($a_defaults[$short] ?? $a_defaults[$long]);
                }
            } else {
                // return the first setted
                return
                $a_res[$short] ??
                $a_res[$long] ??
                $a_defaults[$short] ??
                $a_defaults[$long] ??
                '';
            }
        };
        // merge flags
        $a_opts = array_merge($a_opts, $a_flags);
        // make map short => long
        $a_s_l = [];
        foreach ($a_opts as $short => $long) {
            $short = str_replace(':', '', $short);
            $long = str_replace(':', '', $long);
            $a_s_l[$short] = $long;
        }
        // create the short string
        $s_p = implode('', array_keys($a_opts));
        if ('cli' === PHP_SAPI) {
            $a_res = getopt($s_p, array_values($a_opts));
            // apply defaults long <-> short values
            $a_result = [];
            foreach ($a_s_l as $short => $long) {
                $a_result[$short] = $get_result($a_res, $short, $long);
                $a_result[$long] = $get_result($a_res, $short, $long);
            }
            return $a_result;
        } else {
            die(__FUNCTION__ . '/' . __LINE__ . ' will not parse option, not in cli');
        }
    }
    //----------------------------------------------------------------------------
    // std in/out
    //----------------------------------------------------------------------------
    // legge ttutto l'input
    public static function readStdin() {
        if (function_exists('stream_get_contents')) {
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
    public static function askBoolean($q, $def = false) {
        echo "$q\n>>>";
        $b = self::readStdin();
        return (strtoupper(trim($b)) == 'Y') ? true : false;
    }
    public static function askInt($q, $def = 0) {
        echo "$q\n>>>";
        $b = self::readStdin();
        return (int) $b;
    }
    public static function askString($q, $def = '') {
        echo "$q\n>>>";
        $b = self::readStdin();
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
    //----- input() function
    // read from the command line
    public static function prompt($prompt, $_is_valid = null, $_on_invalid = null) {
        // default lascia passare tutto
        $_is_valid = $_is_valid ?? function ($v) {return true;};
        $_on_invalid = $_on_invalid ?? function ($v) {die("invalid input $v \n");};
        // Define STDIN for compatibility
        if (!defined("STDIN")) {
            define("STDIN", fopen('php://stdin', 'rb')); //"b" Here for Binary-Safe
        }
        //
        echo $prompt . PHP_EOL;
        $input = null;
        while (empty($input) && $input !== 0) {
            $input = fgets(STDIN, 128); // read max 128 char
            $input = rtrim($input);
        }
        if ($_is_valid($input)) {
            return $input;
        } else {
            return $_on_invalid($input);
        }
    }
    /*
    $action = cli_input("chose an action (1,2,3): ");
    echo "action: $action \n";
     */
    public static function input($prompt, $_is_valid = null, $_on_invalid = null) {
        self::prompt($prompt, $_is_valid, $_on_invalid);
    }

/*

//----- input() function
// read from the command line
function cli_input($prompt, $_is_valid = null, $_on_invalid = null) {
// default lascia passare tutto
$_is_valid = $_is_valid ?? function ($v) {return true;};
$_on_invalid = $_on_invalid ?? function ($v) {die("invalid input $v \n");};
//
echo $prompt . PHP_EOL;
$input = null;
while (empty($input) && $input !== 0) {
$input = fgets(STDIN, 128); // read max 128 char
$input = rtrim($input);
}
if ($_is_valid($input)) {
return $input;
} else {
return $_on_invalid($input);
}
}
$action = cli_input("chose an action (1,2,3): ");
echo "action: $action \n";

// Define STDIN for compatibility
if(!defined("STDIN")) {
define("STDIN", fopen('php://stdin','rb'));//"b" Here for Binary-Safe
}
function input($msg) {
echo "$msg\n";
$str = fread(STDIN, 80); // Read up to 80 characters or a newline
return $str;
}
function input_bool($msg) {
$str = input($msg);
$str = strtolower( $str );
return $str == 'yes' || $str == 'y';
}
function input_int($msg){
$str = input($msg);
return parseInt( $str );
}

 */

    //----------------------------------------------------------------------------
    //  output
    //----------------------------------------------------------------------------
    //
    public static function std_error($msg) {
        fputs(STDERR, $msg);
    }
    //------------------------------------------------------------------------------
    //  ANSI colored output
    //------------------------------------------------------------------------------
    // stampa stringa colorata
    public static function colored(string $str, string $foreground_color = '', string $background_color = '') {
        if (php_sapi_name() != "cli") {
            return $str;
        }
        // ForeGround
        static $a_fg = [
            'black' => '0;30',
            'red' => '0;31',
            'green' => '0;32',
            'brown' => '0;33',
            'blue' => '0;34',
            'purple' => '0;35',
            'cyan' => '0;36',
            'white' => '0;37',
            // Bold
            'bblack' => '1;30',
            'bred' => '1;31',
            'bgreen' => '1;32',
            'byellow' => '1;33',
            'bblue' => '1;34',
            'bpurple' => '1;35',
            'bcyan' => '1;36',
            'bwhite' => '1;37',
        ];
        // background
        static $a_bg = [
            'black' => '40',
            'red' => '41',
            'green' => '42',
            'yellow' => '43',
            'blue' => '44',
            'magenta' => '45',
            'cyan' => '46',
            'light_gray' => '47',
        ];
        $str_result = "";
        if (array_key_exists($foreground_color, $a_fg)) {
            // or "\e[%sm"
            $str_result .= sprintf("\033[%sm", $a_fg[$foreground_color]);
        }
        if (array_key_exists($background_color, $a_bg)) {
            $str_result .= sprintf("\033[%sm", $a_bg[$background_color]);
        }
        $str_result .= $str . "\033[0m";
        return $str_result;
    }

    // toglie formattazione colore da una stringa
    // conta solo i char, non i caratteri di controllo usati per generare la colazione
    public static function uncolor(string $s): string{
        // in for 434,324.79
        // [1;37m[42m434,324.79[0m
        // "[1;37m[41m434,324.79[0m"
        // $_n_match = sscanf($s, '[1;37m[42m'.'%s'.'[0m', $num);
        $num = str_replace($sub = ['[1;37m', '[42m', '[41m', '[0m'], $re = '', $s);
        // ispeziona una str per trovare caratteri che diano problemi in output
        $_only_printable = function ($str) {
            $r = '';
            $a_in = str_split($str);
            foreach ($a_in as $c) {
                // ascii alpha to int
                $i = ord($c);
                // 0-32 128-254 non stampabili
                // 33-127 stampabili
                if (10 == $i || ($i >= 32 && $i <= 127)) {
                    $r .= $c; // solo caratteri visibili e innocui
                } else {
                    // $r .= sprintf('@%s@', $i);// char che possono dare problemi
                }
            }
            return $r;
        };
        return $_only_printable($num);
    }

// @see https://en.wikipedia.org/wiki/ANSI_escape_code
    function ansi(string $code, string $text): string {
        static $h_ansi = [];
        if (empty($h_ansi)) {
            $h_ansi = [
                'reset' => "\33[0m", // toglie la formattazione corrente
                //
                'bold' => "\33[1m",
                'no bold' => "\33[22m",
                //
                'underline' => "\33[4m",
                'no underline' => "\33[24m",
                //
                'negative' => "\33[7m",
                'positive' => "\33[27m",
                //
                'black' => "\33[30m",
                'red' => "\33[31m",
                'green' => "\33[32m",
                'yellow' => "\33[33m",
                'blue' => "\33[34m",
                'magenta' => "\33[35m",
                'cyan' => "\33[36m",
                'gray' => "\33[37m",
                // light colors
                'gray2' => "\33[90m",
                'red2' => "\33[91m",
                'green2' => "\33[92m",
                'yellow2' => "\33[93m",
                'blue2' => "\33[94m",
                'magenta2' => "\33[95m",
                'cyan2' => "\33[96m",
                // from 40 to 47 are BG colors, from 100 to 107 are BG colors
                'white' => "\33[97m",
                'default' => "\33[39m",
            ];
        }
        switch ($code) {
        case 'bold':
            return $h_ansi[$code] . $text . $h_ansi['no bold'];
        case 'underline':
            return $h_ansi[$code] . $text . $h_ansi['no underline'];
        case 'negative':
            return $h_ansi[$code] . $text . $h_ansi['positive'];
        }
        return $h_ansi[$code] . $text . $h_ansi['reset'];
    }

    //----------------------------------------------------------------------------
    //
    //----------------------------------------------------------------------------

    // UI reporting automatico sull'esecuzione dello script
    // uso:
    // for($x=1;$x<=100;$x++){
    //     self::showStatus($x, 100);
    //     usleep(100000);
    // }
    public static function showStatus($done, $total, $size = 30) {
        static $start_time;
        // if we go over our bound, just ignore it
        if ($done > $total) {
            return;
        }
        if (empty($start_time)) {
            $start_time = time();
        }
        $now = time();
        $perc = (double) ($done / $total);
        $bar = floor($perc * $size);
        $status_bar = "\r[";
        $status_bar .= str_repeat("=", $bar);
        if ($bar < $size) {
            $status_bar .= ">";
            $status_bar .= str_repeat(" ", $size - $bar);
        } else {
            $status_bar .= "=";
        }
        $disp = number_format($perc * 100, 0);
        $status_bar .= "] $disp%  $done/$total";
        $rate = ($now - $start_time) / $done;
        $left = $total - $done;
        $eta = round($rate * $left, 2);
        $elapsed = $now - $start_time;
        $status_bar .= " remaining: " . number_format($eta) . " sec.  elapsed: " . number_format($elapsed) . " sec.";
        echo "$status_bar  ";
        flush();
        // when done, send a newline
        if ($done == $total) {
            echo "\n";
        }
    }
    function progressBarSimple($progress, $qta, $pl_len = 10) {
        $dec_progress = floor($progress * $pl_len / $qta);
        return sprintf('[%s]', str_pad(str_repeat('=', $dec_progress), $pl_len, '-', STR_PAD_RIGHT));
    }
    // print a progress bar
    function progressBar($finished_percent, $width = 80) {
        $finished_percent = str_pad($finished_percent, 2, ' ');
        $fixed_space = 9; // for spaces, braces [ and number %
        $width -= $fixed_space;
        $finished_count = ceil((($finished_percent * $width) / 100));
        $empty_count = $width - $finished_count;
        $finished = str_repeat("#", $finished_count);
        $empty = str_repeat("-", $empty_count);
        return "\r[ {$finished}{$empty} ] {$finished_percent}% ";
    }
    // uso:
    // $width = intval(`tput cols`);
    // foreach( range(0,100) as $count){
    //   echo print_progress_bar($count, $width);
    //   sleep(1);
    // }
    function progressPerc($progress, $qta) {
        $perc_progress = floor($progress * 100 / $qta);
        $perc_progress = str_pad($perc_progress, 3, ' ', STR_PAD_LEFT);
        return $perc_progress . '%';
    }
    //----------------------------------------------------------------------------
    //
    //----------------------------------------------------------------------------
    //
    // Runs an external command (runs in own thread) with input and output pipes.
    // Returns the exit code from the process.
    //
    function pipeExec($cmd, $input, &$output) {
        $descspec = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"],
        ];
        $ph = proc_open($cmd, $descspec, $pipes);
        if (!$ph) {
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
//----------------------------------------------------------------------------
//
//----------------------------------------------------------------------------
// controller for CLI scripts
class CLIController {
    protected $action = 'help';
    function __construct() {
        if ($this->getAction()) {
            $this->action = $this->getAction();
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
    function getAction() {
        if (!isset($this->action)) {
            $this->action = $argv[1];
        }
        return $this->action;
    }
    // method catchall
    function ActionHelp() {
        return "implementa ActionHelp\n";
    }
}
// funzione: permette il monitoring del batch job
// use: CLIMonitor::registerMonitoringMailHook(['test@gmail.com']);
class CLIMonitor {
    // notifica l'amministratore di errori nella procedura di importazione
    public static function registerMonitoringMailHook(array $a_dev_email) {
        $funcname = __FUNCTION__;
        register_shutdown_function(function () use ($a_dev_email, $funcname) {
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

// Get INI boolean value
function ini_bool(string $ini): bool{
    $val = ini_get($ini);
    return (preg_match('/^(on|true|yes)$/i', $val) || (int) $val); // boolean values set by php_value are strings
}
function stderr(string $text): void{
    fwrite(STDERR, $text . PHP_EOL);
}
/**
 * Check if we aren't running jobs too frequently
 * @return bool OK to run?

$t = new job_throttle();
$t->mk_lock_file(__DIR__, 'my_op');
$c=100;
for( $i=0; $i < $c; $i++) {
if( $t->check() ){
// do aperation and exit cycle if done
} else {
sleep(1);// int seconds
}
}
 */
class job_throttle {
    public $lockfile = '';
    public $min_interval = 30; //secondi
    // assigna a name for this lock file
    public function mk_lock_file(string $dir, string $job_name) {
        $this->lockfile = "$dir/.$job_name.lockfile";
        // create_cache_directory($dir);// check dir is ok
    }
    /**
     * Check if we aren't running jobs too frequently
     * @return bool OK to run?
     */
    public function check(): bool {
        if ($this->min_interval == 0) {
            return true;
        }
        if (!file_exists($this->lockfile)) {
            $this->markLastRun();
            return true;
        }
        $ts = file_get_contents($this->lockfile);
        $this->markLastRun();
        $now = time();
        if ($now - $ts < $this->min_interval) {
            // run too frequently
            return false;
        }
        return true;
    }
    /**
     * Remember last time it was run
     */
    protected function markLastRun() {
        $ok = file_put_contents($this->lockfile, time());
        if (!$ok) {
            die('Scheduler cannot write PID file.  Please check permissions on ' . $this->lockfile);
        }
    }
}

//----------------------------------------------------------------------------
function colored($str, $foreground_color = "", $background_color = "") {
    /* minimal impl
    $_colored = function ($str, $foreground_color = 'green') {
    static $a_fg = ['red' => '0;31', 'green' => '0;32', 'brown' => '0;33'];
    return sprintf("\e[%sm", $a_fg[$foreground_color]) . $str . "\033[0m";
    };*/
    return CLI::colored($str, $foreground_color, $background_color);
}
function colored_ko($str) {
    $foreground_color = "bwhite";
    $background_color = "red";
    return CLI::colored($str, $foreground_color, $background_color);
}
function colored_ok($str) {
    $foreground_color = "bwhite";
    $background_color = "green";
    return CLI::colored($str, $foreground_color, $background_color);
}

// convience aliases
function bold(string $text): string {
    return CLI::ansi('bold', $text);
}
function warning(string $text): void {
    //print(ansi('yellow', $text)."\n");
    print(bold(CLI::ansi('yellow', $text)) . "\n");
}
function error(string $text): void {
    print(bold(CLI::ansi('red', $text)) . "\n");
}
function info(string $text): void {
    print(CLI::ansi('gray', $text)) . "\n";
}

//----------------------------------------------------------------------------
function alert(string $msg): void {
    global $do_print;
    if ($do_print) {return;}
    $cmd = sprintf('zenity --info --text="%s"', escapeshellarg($msg));
    `$cmd`;
}

if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/Test.php';
    // testing
    function test_args_parse() {
        // test str: php myscript.php --user=nobody --password=secret -p --access="host=127.0.0.1 port=456"
        $t_argv = [
            'xxx',
            '--user=nobody',
            '--password=secret',
            '-p',
            '--access="host=127.0.0.1 port=890"',
        ];
        $a = CLI::getConsoleArgs($t_argv);
        echo print_r($a, true);
        ok($a['user'], $expected = 'nobody', 'parse arg 1');
        ok($a['password'], $expected = 'secret', 'parse arg 2');
        ok($a['access'], $expected = "host=127.0.0.1 port=890", 'parse arg 3');
        ok($a['p'], true, 'test 4');
    }
    test_args_parse();

    // ANSI test
    warning('warn');
    error('err');
    info('help text');
    colored_ko('ko ko');
    colored_ok('ok ok');

}
