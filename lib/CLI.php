<?php

ini_set('register_argc_argv', true);
ini_set('max_execution_time', 0);
ini_set('html_errors', false);
ini_set('implicit_flush', false);

class CLI {

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
    // verifica che sia stato passato un valore in cli
    // uso: echo has_flag($argv, 'production-ws') ? 'si':'no';
    public static function hasFlag($argv, $flag) {
        $s_argv = implode(' ', $argv);
        $substr = "--$flag";
        return strpos($s_argv, $substr) !== false;
    }

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
        return (trim($b) == 'Y') ? true : false;
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

    //------------------------------------------------------------------------------
    //  colored output / CliUI
    //------------------------------------------------------------------------------

    // ForeGround colors
    static $a_fg = [
        'black' => '0;30',
        'dark_gray' => '1;30',
        'blue' => '0;34',
        'light_blue' => '1;34',
        'green' => '0;32',
        'light_green' => '1;32',
        'cyan' => '0;36',
        'light_cyan' => '1;36',
        'red' => '0;31',
        'light_red' => '1;31',
        'purple' => '0;35',
        'light_purple' => '1;35',
        'brown' => '0;33',
        'yellow' => '1;33',
        'light_gray' => '0;37',
        'white' => '1;37',
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

    // usa FG o BG
    public static function getColoredString($str, $foreground_color = '', $background_color = '') {
        $s = '';
        // FG color
        if (isset(self::$a_fg[$foreground_color])) {
            $s .= "\e[" . self::$a_fg[$foreground_color] . 'm';
        }
        // BG color
        if (isset(self::$a_bg[$background_color])) {
            $s .= "\033[" . self::$a_bg[$background_color] . 'm';
        }
        $s .= $str . "\033[0m";
        return $s;
    }
    // stampa stringa colorata
    public static function printc($str, $foreground_color = 'green') {
        echo self::sprintc($str, $foreground_color) . "\n";
    }

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

    function progressBar($progress, $qta, $pl_len = 10) {
        $dec_progress = floor($progress * $pl_len / $qta);
        return sprintf('[%s]', str_pad(str_repeat('=', $dec_progress), $pl_len, '-', STR_PAD_RIGHT));
    }

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
        $descspec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w"));
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
// use: CLIMonitor::registerMonitoringMailHook(array('test@gmail.com'));
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

class CLITest {
    static $errc = 0;
    public static function ok($test, $label, $data = null) {
        if ($test == false) {
            echo CLI::sprintc("ERROR $label: $test", 'red') . "\n\n";
            if (!empty($data)) {
                echo var_export($data, 1);
            }
            self::$errc++;
        } else {
            echo CLI::sprintc("OK $label", 'green') . "\n\n";
        }
    }
    public static function diag($l, $data = '') {
        if (!empty($data)) {
            echo CLI::sprintc($l);
        } else {
            echo CLI::sprintc($l . ':' . var_export($data, 1));
        }
        echo "\n\n";
    }
}

if (!function_exists('is')) {
    function is($val, $expected_val, $description = '') {
        $pass = ($val == $expected_val);
        CLITest::ok($pass, $description);
        if (!$pass) {
            CLITest::diag("         got: '$val'");
            CLITest::diag("    expected: '$expected_val'");
        }
        return $pass;
    }
}
