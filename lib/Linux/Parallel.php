<?php
declare (strict_types = 1); //php7.0+, will throw a catchable exception if call typehints and returns do not match declaration
//----------------------------------------------------------------------------
// Poor man Parallelism
//   date n closures
//   wrappa ciascuna in uno script tmp
//   esegue in parallelo ogni script
//   raccoglie gli output
//----------------------------------------------------------------------------
class P {
    static $a_closures = []; // name => [closure,data]
    static $a_code = []; // name => code
    public static function go(string $name, Closure $c, array $data = []) {
        self::$a_closures[$name] = [$c, $data];
        $code = PGen::closure_dump($c);
        $file_path = sprintf('/tmp/__process_%s.php', $name);
        $file_path_json = $file_path . '.json';
        self::$a_code[$name] = $file_path;
        // compose source
        $code_exe = PGen::generate($code, $file_path_json, $data);
        //
        file_put_contents($file_path, $code_exe); // default is overwriting file
        // elimina output se presente
        if (file_exists($file_path_json) && unlink($file_path_json)) {}
    }
    // esegue la coda
    public static function execute(): array{
        $a_res = [];
        // lanch processes in BG
        foreach (self::$a_code as $name => $file_path) {
            self::exec($file_path, $name);
        }
        // accept integer or a decimal.
        // msleep(1.5); // delay for 1.5 seconds
        $_msleep = function (float $time) {
            usleep((int) ($time * 1000000));
        };
        // receive
        $c = count(self::$a_code);
        $i = 0;
        $delay = 0.1;
        $max_sec = 30; // num massimo s di attesa che i precessi completino
        $max_iter = 300; // 30s / 0.1;
        while (count($a_res) < $c) { // cerca finchè ci sono tutti gli output
            if ($i <= $max_iter) {
                foreach (self::$a_code as $name => $file_path) {
                    if (isset($a_res[$name])) {
                        // got it
                    } else {
                        $file_path_json = $file_path . '.json';
                        if (file_exists($file_path_json)) {
                            $json_out = file_get_contents($file_path_json);
                            $a_res[$name] = json_decode($json_out, $assoc = true);
                            self::clean_up($file_path, $esito = $a_res[$name]['ok']);
                        }
                    }
                }
                // trovate tutte le chiavi
                if (count(self::$a_code) == count($a_res)) {
                    return $a_res;
                } else {
                    $_msleep($delay); // delay for 100 milliseconds
                    $i++;
                    // if ( false ) { echo '.'; }
                }
            } else {
                // time out error
                echo "time out error" . "\n";
                return $a_res;
            }
        }
        return $a_res;
    }
    // exec a tmp script
    protected static function exec($file_path, $name, $argv_parameter = '') {
        // verifica il codice prima di eseguirlo
        $cmd = "php -l $file_path";
        $last_line = exec($cmd, $a_output, $exit_code);
        if ($exit_code != 0) {
            echo "lint ERROR! code:$exit_code  file:$file_path  name:$name \n";
        } else {
            // lancia l'esecuzione
            passthru("/usr/bin/php $file_path " . $argv_parameter . " > $file_path.log 2>&1 &");
            // or exec($cmd . " > /dev/null &");
        }
    }
    // rimuovi files tmp
    public static function clean_up($file_path, bool $esito) {
        if ($esito) {
            foreach ([
                $file_path,
                "$file_path.json",
                "$file_path.log",
                "$file_path.phplog",
            ] as $path) {
                if (file_exists($path) && unlink($path)) {}
            }
        } else {
            // ci sono stati errori, lascia la traccia per debug
        }
    }
    // esegui sequenzialmente il codice per testare che funzioni correttamente prima di parallelizzare
    public static function execute_sequential(): array{
        $__out= [] ;
        foreach (self::$a_closures as $name => $t) {
            list($c, $__in) = $t;
            try {
                $__out[$name] = $c($__in);
            } catch (\Exception $e) {
                $fmt = 'Exception:  %s file:%s line:%s  trace: %s';
                $msg = sprintf($fmt, $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
                echo ($msg);
            }
        }
        return $__out;
    }
}
//
// genera il wrapper code per gestire il processo
class PGen {
    //
    public static function generate(string $code, string $file_path_json, array $data): string {
        return
        $code_exe = '<?php' . "\n"
        . self::wrap_in_main($code)
        . self::runtime($file_path_json, $data);
    }
    // main code
    public static function wrap_in_main(string $code): string {
        return $code_main = <<<__END__
function __main(array \$__in):array {
    \$__op = function() use(\$__in):array {
//--- begin code ---------------------------------------------------------------
    $code
//--- end code   ---------------------------------------------------------------
    };
    \$__out = \$__op();
    return \$__out;
}
__END__;
    }
    // runtime code
    /** @psalm-suppress UndefinedConstant  */
    public static function runtime(string $file_path_json, array $data): string{
        $json_data = json_encode($data);
        // the runtime of the script
        /** @psalm-suppress UndefinedConstant */
        $__prelude = function () {
            //---------------------------- runtime IPC
            error_reporting(-1); // E_ALL Report all PHP errors
            ini_set('display_errors', '1');
            // log app errors
            ini_set('log_errors', '1');
            ini_set('error_log', __FILE__ . '.phplog');
            // report whatever as exception
            set_error_handler(function ($errno, $errstr, $errfile, $errline, array $errcontext) {
                throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
                return false;
            },
                // on which error report level the user-defined error will be shown. Default is "E_ALL"
                E_ALL
            );
            // catch fatal errors (es. memory problems, sintax error)
            register_shutdown_function(function () {
                $error = error_get_last();
                if ($error !== NULL) {
                    $error['trace'] = debug_backtrace(false);
                    __send(__envelope(false, $error));
                }
            });
            // send an exception
            /**
             * @param mixed $e
             */
            function __Exception_send( $e):void {
                __send(__envelope(false, [
                    'Exception' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'output' => ob_get_clean(),
                ]));
            }
            set_exception_handler('__Exception_send');
            // wrap result in consistent format
            function __envelope(bool $ok, array $result): array{
                return [
                    'ok' => $ok,
                    'result' => $result,
                    'output' => ob_get_clean(),
                    'time_from_begin' => round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4),
                ];
            }
            // send the result to a json file
            function __send(array $result):void {
                $json = json_encode($result,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_PRETTY_PRINT
                );
                file_put_contents(PATH_JSON_OUT, $json); // default is overwriting file
            }
        };
        // the main funcytion of the script
        /**
        * @psalm-suppress UndefinedConstant
        * @psalm-suppress UndefinedFunction
        */
        $__main = function () {
            //-------- main
            try {
                ob_start();
                $__in = json_decode(JSON_IN, $_use_assoc = true);
                $result = __main($__in);
                __send(__envelope(true, $result));
            } catch (\Exception $e) {
                __Exception_send($e);
            }
        };
        // add runtime for IPC
        $runtime = "\n" .
        self::closure_dump($__prelude) . "\n" .
        <<<__END__
        define('PATH_JSON_OUT', '$file_path_json');
        define('JSON_IN', '$json_data');
__END__
        ."\n".
        self::closure_dump($__main)."\n";
        return $runtime;
    }
    public static function closure_dump(Closure $c): string{
        // $str = 'function (';
        $str = '';
        $r = new ReflectionFunction($c);
        // $params = $r->getParameters();
        $lines = file($r->getFileName());
        for ($l = $r->getStartLine(); $l < ($r->getEndLine() - 1); $l++) {
            $str .= ($lines[$l]);
        }
        if (empty($str)) {
            die('ERROR: empty closure block, inline closures not allowed ' . PHP_EOL);
        }
        return $str;
    }
    // test code for closure dump
    // echo var_dump(
    //   closure_dump(function () {
    //     $a = ''; $b = 0;return 1 * 4;
    //   }
    //   )
    // ), " \n";
}
//----------------------------------------------------------------------------
//  test
//----------------------------------------------------------------------------
// if colled directly in CLI, run the tests:
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    P::go('test_error', function (array $__in): array{
        trigger_error("Test exception");
        return [];
    });
    P::go('test_in_return', function (array $__in): array{
        return $__in;
    }, ['a' => 1, 'b' => 2]);
    P::go('test_cpu_time', function (array $__in): array{
        // test a timout error
        $result = [];
        for ($j = 0; $j < (3); $j++) {
            for ($i = 0; $i < (100); $i++) {
                $result[] = password_hash("a$i", PASSWORD_DEFAULT);
            }
            $result = [];
        }
        return $result;
    });
    P::go('test2', function (array $__in): array{
        $result = [];
        $c = 6;
        for ($i = 0; $i < $c; $i++) {
            $result[] = "b$i";
            sleep(2);
        }
        return $result;
    });
    P::go('test_echo', function (array $__in): array{
        for ($i = 0; $i < 5; $i++) {
            echo '.'; //will be captured
        }
        return [];
    });
    $a_result = P::execute();
    // $a_result = P::execute_sequential();
    var_export($a_result);
    echo "\n";
    $time_from_begin = round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4);
    echo $time_from_begin . "\n";
    // todo: partition big array of data and split it to multiple processes
    // todo: handle php dependency and lib files
}
