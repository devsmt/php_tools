<?php

class ScalarTypeHint {

    const TYPEHINT_PCRE = '/^Argument (\d)+ passed to (?:(\w+)::)?(\w+)\(\) must be an instance of (\w+), (\w+) given/';

    private static $check_funcs = [
        'boolean' => 'is_bool',
        'integer' => 'is_int',
        'float' => 'is_float',
        'string' => 'is_string',
        'resource' => 'is_resource',
    ];
    public static function initializeHandler() {
        // PHP 7 implementa la funzionalità di default
        if (defined('PHP_VERSION_ID') && (PHP_VERSION_ID <= 70006)) {
            return;
        }
        set_error_handler('ScalarTypeHint::handleTypehint');
        return true;
    }

    private static function getTypehintedArgument($back_trace, $function, $i, &$v) {
        foreach ($back_trace as $trace) {
            if (isset($trace['function']) && $trace['function'] == $function) {
                $v = $trace['args'][$i - 1];
                return true;
            }
        }
        return false;
    }

    public static function handleTypehint($err_level, $err_message) {
        if ($err_level == E_RECOVERABLE_ERROR) {
            $is_type_error = preg_match(self::TYPEHINT_PCRE, $err_message, $err_matches);
            if ($is_type_error) {
                list($err_match, $i, $Class, $function, $hint, $type) = $err_matches;
                if (isset(self::$check_funcs[$hint])) {
                    $backtrace = debug_backtrace();
                    $v = null;
                    if (self::getTypehintedArgument($backtrace, $function, $i, $v)) {
                        if (call_user_func(self::$check_funcs[$hint], $v)) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

}

ScalarTypeHint::initializeHandler();

if (basename(__FILE__) == basename($argv[0])) {

    // TEST
    function teststring(string $string) {
        echo $string;
    }

    function testinteger(integer $integer) {
        echo $integer;
    }

    function testfloat(float $float) {
        echo $float;
    }

    teststring([]);
    testinteger([]);
    testfloat([]);

    // This will work for class methods as well.
    class T {

        public static function test(integer $i) {
            return $i;
        }

    }

    T::test([]);
}
