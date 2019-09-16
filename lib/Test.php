<?php
/*
inspired by Perl Test::Simple
The goal here is to have a testing utility that's simple to learn, quick to use
and difficult to trip yourself up with while still providing some flexibility
 */
//-----------------------------------------------------------------------------------
//  test formating
//-----------------------------------------------------------------------------------
function diag($l, $data = null) {
    return Test::diag($l, $data);
}
//-----------------------------------------------------------------------------------
//  assertions
//-----------------------------------------------------------------------------------
//
function ok($res, $expected, $label = '') {
    $_colored = function ($str, $foreground_color = 'green') {
        static $a_fg = ['red' => '0;31', 'green' => '0;32', 'brown' => '0;33'];
        return sprintf("\e[%sm", $a_fg[$foreground_color]) . $str . "\033[0m";
    };
    // be careful passing arrays, @see array_compare_
    if ($res === $expected) {
        echo $_colored("OK $label \n", 'green');
    } elseif ($res == $expected) {
        $s = sprintf("OK (but type differ) name:%s | %s<>%s\n", $label, var_export($res, true), var_export($expected, true));
        echo $_colored($s, 'brown');
    } else {
        $s = sprintf("ERROR(%s)  GOT %s <> %s EXP  \n", $label, var_export($res, true), var_export($expected, true));
        echo $_colored($s, 'red');
    }
}
// full version
function ok_($res, $expected, $label = '') {
    @$GLOBALS['test_count']++;
    $is_regexp = is_string($expected) && substr($expected, 0, 1) == '/'; // se la stringa inizia con '/' è interpretata come regexp @try preg_match("/^\/.+\/[a-z0-1]*$/i",$expected)
    $colored = function ($str, $foreground_color = '') {
        static $a_fg = ['red' => '0;31', 'green' => '0;32', 'brown' => '0;33'];
        $s = '';
        if (isset($a_fg[$foreground_color])) {
            $s .= sprintf("\e[%sm", $a_fg[$foreground_color]);
        }
        $s .= $str . "\033[0m";
        return $s;
    };
    $is_hash = function (array $array) {
        return count(array_filter(array_keys($array), 'is_string')) > 0;
    };
    // basic comparison (using $a == $b or $a === $b fails) works for associative arrays but will not work as expected with indexed arrays
    // which elements are in different order, for example:
    // (array("x","y") == array("y","x")) === false;
    $array_equal = function ($a, $b) {
        return (is_array($a) && is_array($b) &&
            count($a) == count($b) &&
            array_diff($a, $b) === array_diff($b, $a));
    };
    // comparazione di array associativi
    $hash_equal = function ($a, $b) {
        return json_encode(ksort($a, SORT_STRING)) === json_encode(ksort($b, SORT_STRING));
    };
    $dmp = function ($v) {return var_export($v, true);};

    if ($res === $expected) {
        echo $colored("OK $label \n", 'green');
    } elseif (!is_array($expected) && $res == $expected) {
        $s = sprintf("OK (but type differ) %s %s<>%s \n", $label, $dmp($res), $dmp($expected));
        echo $colored($s, 'brown');
    } elseif (is_array($expected)) {
        if (!$is_hash($expected)) {
            if ($array_equal($res, $expected)) {
                $s = sprintf("OK array  %s %s %s \n", $label, $dmp($res), $dmp($expected));
                echo $colored($s, 'green');
            } else {
                $s = sprintf("ERROR array  %s %s %s \n", $label, $dmp($res), $dmp($expected));
                echo $colored($s, 'red');
            }
        } elseif ($is_hash($expected)) {
            if ($hash_equal($res, $expected)) {
                $s = sprintf("OK hash  %s %s %s \n", $label, $dmp($res), $dmp($expected));
                echo $colored($s, 'green');
            } else {
                $s = sprintf("ERROR hash  %s %s %s \n", $label, $dmp($res), $dmp($expected));
                echo $colored($s, 'red');
            }
        }
    } elseif ($is_regexp) {
        $m = preg_match($reg = $expected, $str = $res);
        if (1 === $m) {
            $s = sprintf("OK regexp %s %s %s %s \n", $label, $dmp($res), $dmp($expected), $dmp($m));
            echo $colored($s, 'green');
        } else {
            $s = sprintf("ERROR regexp %s %s %s %s \n", $label, $dmp($res), $dmp($expected), $dmp($m));
            echo $colored($s, 'red');
        }
    } else {
        $s = sprintf("ERROR(%s)  GOT %s <> %s EXP  \n", $label, $dmp($res), $dmp($expected));
        echo $colored($s, 'red');
    }
}

// function is($res, $label ) { return ok($res, $expected=true, $label ); }
// @see test_suite_

// test delle eccezioni
function ok_excheption($operation, $label) {
    $is_e_rised = false;
    $e_msg = '';
    try {
        $operation();
    } catch (Throwable $e) { /*\Exception*/// Throwable $e in php7
        $e_msg = $e->getMessage();
        $is_e_rised = true;
    }
    ok($is_e_rised, true, $label . ' rised:' . $e_msg);
}
//
function is($val, $expected_val, $description = '') {
    $pass = ($val == $expected_val);
    ok($pass, $description);
    if (!$pass) {
        diag("         got: '$val'");
        diag("    expected: '$expected_val'");
    }
    return $pass;
}
function isnt($val, $expected_val, $description = '') {
    $pass = ($val != $expected_val);
    ok($pass, $description);
    if (!$pass) {
        diag("    '$val'");
        diag("        !=");
        diag("    '$expected_val'");
    }
    return $pass;
}
function like($string, $regex, $description = '') {
    $pass = preg_match($regex, $string);
    ok($pass, $description);
    if (!$pass) {
        diag("                  '$string'");
        diag("    doesn't match '$regex'");
    }
    return $pass;
}
function unlike($string, $regex, $description = '') {
    $pass = !preg_match($regex, $string);
    ok($pass, $description);
    if (!$pass) {
        diag("                  '$string'");
        diag("          matches '$regex'");
    }
    return $pass;
}
function cmp_ok($val, $operator, $expected_val, $description = '') {
    eval('$pass = ($val ' . $operator . ' $expected_val);');
    ok($pass, $description);
    if (!$pass) {
        diag("         got: '$val'");
        diag("    expected: '$expected_val'");
    }
    return $pass;
}
function can_ok($object, $methods) {
    $pass = true;
    $errors = [];
    foreach ($methods as $method) {
        if (!method_exists($object, $method)) {
            $pass = false;
            $errors[] = "method_exists(\$object, $method) failed";
        }
    }
    if ($pass) {
        ok(true, "method_exists(\$object, ...)");
    } else {
        ok(false, "method_exists(\$object, ...)");
        diag($errors);
    }
    return $pass;
}
function isa_ok($object, $expected_class, $object_name = 'The object') {
    $got_class = get_class($object);
    if (version_compare(php_version(), '5', '>=')) {
        $pass = ($got_class == $expected_class);
    } else {
        $pass = ($got_class == strtolower($expected_class));
    }
    if ($pass) {
        ok(true, "$object_name isa $expected_class");
    } else {
        ok(false, "$object_name isn't a '$expected_class' it's a '$got_class'");
    }
    return $pass;
}
function pass($description = '') {
    return ok(true, $description);
}
function fail($description = '') {
    return ok(false, $description);
}
function include_ok($module) {
    // Test success of including file, but continue testing if possible even if unable to include
}
function require_ok($module) {
}
function skip($message, $num) {
    global $_num_skips;
    if ($num < 0) {
        $num = 0;
    }
    for ($i = 0; $i < $num; $i++) {
        pass("# SKIP $message");
    }
    $_num_skips = $num;
}
// Recursively check datastructures for equalness
function is_deeply($got, $expected, $test_name) {
    $s_got = serialize($got);
    $s_exp = serialize($expected);
    $pass = $s_got == $s_exp;
    if ($pass) {
        ok(true, " is_deeply $test_name");
    } else {
        ok(false, " !is_deeply $test_name ");
        diag($s_got);
        diag($s_exp);
    }
    return $pass;
}
// usa weblint per assicurarsi che html prodotto sia standard
function html_ok($str, $name = "") {
    $fname = tempnam(getenv("TMP"), 'lint-');
    $fh = fopen($fname, "w");
    fwrite($fh, $str);
    fclose($fh);
    $results = [];
    $results = shell_exec("weblint $fname");
    unlink($fname);
    if ($results) {
        $ok = fail($name);
        diag($results);
    } else {
        $ok = pass($name);
    }
    return $ok;
}
// confrontare Float
// The arguments required for both functions are three numbers: the first and second arguments can be either the calculated value, or the target comparison value, and the third is the precision.
//
// An simple example:
//
// $a = 95.1;
// $b = 100.0;
// is_float_approximately_equal($a, $b, 0.05)); // this is true, 100 * 0.05 > 100 - 95.1
// is_float_essentially_equal($a, $b, 0.05)); // this is false, 95.01 * 0.05 < 100 - 95.1
//
// The approximatelyEqual function uses the larger of the two values and multiples it by epsilon to determine the margin of error.
function is_float_approximately_equal($a, $b, $epsilon) {
    // Abs Returns the absolute value of number. abs(-4.2) -> 4.2;
    $A = abs($a);
    $B = abs($B);
    return abs($A - $B) <= ($A < $B ? $B : $A) * $epsilon;
}
// The essentiallyEqual function uses the smaller of the two values and multiples it by epsilon
// to determine the margin of error. Therefore, unless values A and B are equal, the
// essentiallyEqual function will always require the values to be more precise than the
// approximatelyEqual function.
function is_float_essentially_equal($a, $b, $epsilon) {
    $A = abs($a);
    $B = abs($B);
    return abs($A - $B) <= ($A > $B ? $B : $A) * $epsilon;
}
//-----------------------------------------------------------------------------------
//  moking
//-----------------------------------------------------------------------------------
/*
per utilizzare gli oggetti che hanno interdipendenze, si creano degli oggetti
vuoti da "riempire" all'occorrenza simulando condizioni tipiche
es.
class myObject extends SimpleMock{}
$o = new myObject();
$o->set('isSomething', 'val');
// test
$o->isSomething(); // ritorna "val"
 */
class SimpleMock {
    var $data = [];
    function getValue($i) {
        return $this->data[$i];
    }
    function get($i) {
        return $this->getValue($i);
    }
    function set($i, $v) {
        $this->data[$i] = $v;
    }
    function __call($name, $arguments) {
        return $this->get($name);
    }
}
//-----------------------------------------------------------------------------------
// suite functionality
//-----------------------------------------------------------------------------------
//
// base interface for test classes
// es. class OrderTest implements ITestCommand {}
//
interface ITestCommand {
    public function __construct();
    // run test cases
    public function run();
    public function cleanup();
}
// da CLI o pagina statica, leggge tutte le classi definite come tests e le esegue
class TestSuite {
    public function __construct() {
    }
    // classe da far girare
    public function getClass() {
        // implementazione cli
        return isset($argv[1]) ? $argv[1] : null;
    }
    // esegui i test scelti
    public function run() {
        $class = $this->getClass();
        switch ($class) {
        case 'all':
            // fa girare tutte le classi di test in sequenza
            foreach ($a = get_declared_classes() as $class_name) {
                if (substr($class_name, -4) == 'Test') {
                    $logger->info("running $class_name");
                    $t = new $class_name();
                    $t->run();
                    $t->cleanup();
                }
            }
            break;
        default:
            $is_valid_class = !empty($class) && class_exists($class) && class_implements('ITestCommand');
            if ($is_valid_class) {
                $t = new $class();
                $t->run();
                $t->cleanup();
            } else {
                die($this->render_usage());
            }
            break;
        }
        $this->render_result();
    }
    // si occupa di rendere leggibile il risultato dei test nel container(cli,web)
    // di esecuzione prescelto
    public function render_result() {
    }
    // uso del test
    public function render_usage() {
    }
    function listAll() {
        $dir_path = dirname(__FILE__ . DIRECTORY_SEPARATOR . 'tests');
        $dir = dir($dir_path);
        echo "<h3>tests:</h3>\n";
        while ($d = $dir->read()) {
            $d_path = "$dir_path/$d";
            if (is_dir($d_path)) {
                if ($d != '.' && $d != '..') {
                    echo "<a href='$d'>$d</a><br>";
                }
            } else {
                echo "<a href='$d'>$d</a><br>";
            }
        }
    }
}
//-----------------------------------------------------------------------------------
// minimalistic test for the web
//-----------------------------------------------------------------------------------
//
// minimalistic in page test harness
//
// uso:
// echo Test::css();
// ...do your tests here
// Test::ok();
// // segnala la presenza di errori
// Test::alarm();
//
//
//  test init
//  register_shutdown_function(function () {
//      if (PHP_SAPI != 'cli') {
//          echo Test::css();
//          Test::alarm();
//      }
//  });
//
/*
class Test {
static $errc = 0;
public static function ok($test, $label, $data = null) {
if (PHP_SAPI != 'cli') {
if ($test == false) {
echo "<p class=\"error\">ERROR $label: $test</p>\n\n";
if (!empty($data)) {
echo "<pre class=\" dump\">" . var_export($data, 1) . "</pre>\n\n";
Test::$errc++;
}
} else {
echo "<p class=\"success\">OK $label </p>\n\n";
}
} else {
if ($test) {
echo "ok $label\n";
} else {
echo "ERROR($label)    " . var_export($data, 1) . " \n";
}
}
}
public static function diag($l, $data = '') {
if (!empty($data)) {
echo '<pre class="dump">' . $l . '</pre>';
} else {
echo '<pre class="dump">' . $l . ':' . var_export($data, 1) . '</pre>';
}
}
// segnala la presenza di errori
public static function alarm() {
if (Test::$errc) {
echo '<style type="text/css">body{background-color:#ff9999}</style>';
} else {
echo '<style type="text/css">body{background-color:#dbffdb}</style>';
}
}
public static function css() {
$html = <<<__END__
<style type="text/css">
body {
font-family: Arial, Helvetica, sans-serif;
font-size: 11px;
}
p,pre {
padding:5px;
margin:5px;
}
.info{
background-color: #ccccff;
}
.error{
background-color: #ff3333;
}
.success{
background-color: #66ff99;
}
.dump{
font-size:8px;
background-color: #dedede;
}
</style>
__END__;
return $html;
}
}
 */
//----------------------------------------------------------------------------
// minimalistic test for API
//----------------------------------------------------------------------------
class APIClient {
    public static function get($method, $param = []) {
        $param_auth = self::getAuth();
        $a_param = array_merge($param_auth, $param);
        $url = sprintf('%s/%s?%s', self::URL, $method, http_build_query($a_param));
        $json_str = file_get_contents($url);
        if (DEBUG) {
            echo "## URL: $url \n";
        }
        $data = json_decode($json_str, $use_assoc = true);
        if (empty($data)) {
            if (DEBUG) {
                echo "## UNPARSABLE RESPONSE --------------------------------------\n";
                echo "$json_str\n";
                echo "## END RESPONSE    ------------------------------------------\n";
            }
            return $json_str;
        }
        if (DEBUG && (isset($data['exec_time']) || isset($data['memory']))) {
            echo sprintf('## time:%s mem:%s data_len:%s' . PHP_EOL,
                @$data['exec_time'], @$data['memory'], @count($data['data']));
        }
        return $data;
    }
    protected static function getAuth() {
        $time = time();
        $hash = ''; // some hashing algorithm like sha1(self::KEY.'-'.$time);
        $a = [
            'client_id' => self::CLIENT_ID,
            'time' => $time,
            'hash' => $hash,
        ];
        return $a;
    }
}
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    // test OK func
    ok(0, 0, 'ok for same value'); // should pass
    ok(0, null, 'type warning'); //should pass with type warning
    ok(['b' => 2, 'a' => 1], ['a' => 1, 'b' => '2']);
    ok([1, 2], [2, 1]);
    ok(['a', 'b'], ['b', 'a']);
    // this should be true in all impelemetations
    ok([1, 2], [1, 2]);
    ok(['a' => 1, 'b' => 2], ['a' => 1, 'b' => 2]);
    ok('aaa000', '/^[A-Z0-1]*$/i');

    // test OK func
    ok_(0, 0, 'ok for same value'); // should pass
    ok_(0, null, 'type warning'); //should pass with type warning
    ok_(['b' => 2, 'a' => 1], ['a' => 1, 'b' => '2']);
    ok_([1, 2], [2, 1]);
    ok_(['a', 'b'], ['b', 'a']);
    // this should be true in all impelemetations
    ok_([1, 2], [1, 2]);
    ok_(['a' => 1, 'b' => 2], ['a' => 1, 'b' => 2]);
    ok_('aaa000', '/^[A-Z0-1]*$/i');

}