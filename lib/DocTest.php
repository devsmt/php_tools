<?php

//----------------------------------------------------------------------------
// doctest implemented in PHP
//----------------------------------------------------------------------------
/** doctest
 * dato un commento, se contiene la stringa doctest nella prima riga, estrae i test presenti nel commento
 *
 * $_mk_dt=function($code){ return '/** doctest: '.$code.' *'.'/'; };
 * ok(doctest_extract_test_code($_mk_dt('aaa')), 'aaa');
 * @return string
 */
function doctest_extract_test_code(string $text): string{
    // echo $text;
    // is "doctest" contained in firts line?
    // get remaining lines
    $a_lines = explode("\n", $text);
    $first_line = $a_lines[0];
    $is_doctest = stripos($first_line, 'doctest') !== false;
    if ($is_doctest) {
        $code = '';
        foreach ($a_lines as $i => $line) {
            $line = trim($line);
            $line = trim($line, '*/'); //ignoriamo ultima linea
            $line = ltrim($line, '*');
            $line = trim($line);
            if ($i == 0) { // gestisce formato prima linea
                $reg = '/^doctest(:+) (.*)$/i';
                $is_reg = preg_match($reg, $line, $a_matched);
                if (1 === $is_reg) { //code found
                    $code .= trim($a_matched[2]); // code found
                } elseif ($is_reg === false) { // regex is bad
                    if (preg_last_error() !== PREG_NO_ERROR) {echo preg_last_error_msg();}
                } else {
                    continue; // no code to extract here
                }
            } elseif ($line == '*/' || empty($line)) { // formato ultima linea
                continue;
            } else {
                $line = preg_replace($regex = '#//(.*)#i', '', $line); // rimuovi commenti single line nella riga se presenti
                $last_char = $line[strlen($line) - 1];
                if (';' === $last_char) {
                    $code .= $line . "\n";
                }
            }
        }
        return $code;
    }
    return '';
};
// estrae i metodi pubblici, estrae il codice di test presente nei commenti e se presente lo esegue
function doctest_run(): void{
    // extract testing functions
    $functions = get_defined_functions();
    $functions_list = [];
    foreach ($functions['user'] as $func) {
        $f = new ReflectionFunction($func);
        $code = doctest_extract_test_code($f->getDocComment());
        if (!empty($code)) {
            $functions_list[$func] = $code;
        }
    }
    // extract testing classes
    $classes = get_declared_classes();
    foreach ($classes as $class_name) {
        $r_class = new ReflectionClass($class_name);
        // skip internal classes, we want user defined classes
        if ($r_class->isInternal()) {
            continue;
        }
        $a_functions = $r_class->getMethods($i_filter = ReflectionMethod::IS_STATIC | ReflectionMethod::IS_PUBLIC);
        foreach ($a_functions as $f) {
            $func_name = $f->name;
            $code = doctest_extract_test_code($f->getDocComment());
            if (!empty($code)) {
                $functions_list[$class_name . '::' . $func_name] = $code;
            }
        }
    }
    // run extracted code:
    foreach ($functions_list as $f_name => $test_code) {
        echo "=== testing: $f_name === \n";
        // eval() throws a ParseError exception
        try {
            echo eval($test_code), "\n";
        } catch (Throwable $e) { // Throwable $e in php7
            $fmt = "Eval ERROR: %s\n file:%s line:%s\n trace: %s\n";
            $msg_full = sprintf($fmt, $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
            $msg_full .= "--- code:\n$test_code \n---\n";
            die($msg_full);
        }
    }
}

// if colled directly in CLI, run the tests:
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    error_reporting(-1); // E_ALL Report all PHP errors / E_ALL & ~E_NOTICE =>Report all errors except E_NOTICE
    ini_set('display_errors', '1'); // PROD shoul only log, not displaying achitecture informations
    //----------------------------------------------------------------------------
    //  SUT
    //----------------------------------------------------------------------------
    /**
     * usual function documentation
     */
    /** doctest:
     * ok(bar(1),1);
    ok(bar(2),2);// other valid format
     */
    function bar($foo) {return $foo;}
    /** empty comment */
    function ok($a, $b, $label = '') {
        if ($a === $b) {
            echo "OK $a === $b $label\n";
        } elseif ($a == $b) {
            echo "OK but differ types: $a == $b $label\n";
        } else {
            echo "ERROR: $a != $b $label\n";
        }
    }
    class MyClass {
        /** doctest:
         * this comment of the test should not be executed
         * ok(  MyClass::myfunc(1), 1 );
         */
        public static function myfunc($i): int {
            return $i;
        }
    }
    // run own doctests
    doctest_run();
}
