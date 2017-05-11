<?php




// global configurations
define('LIB_PATH', __DIR__);

// il controller sta nella dir root dell'applicazione
define('APP_PATH', dirname($GLOBALS['_SERVER']['SCRIPT_FILENAME']));

define("DBG_PARAM", "__v__", false);
if (!defined('DEBUG')) {
    $DEBUG_def = 0;
    define('DEBUG', (isset($_GET[DBG_PARAM]) ? (int) $_GET[DBG_PARAM] : $DEBUG_def));
}

class Bootstrap {

    public static function init() {
        date_default_timezone_set('Europe/Berlin');
        // require_once 'ScalarTypeHint.php';
        self::initErrorHandling();

        // include minum set of functionality
        self::useLib('Strings', 'Request', 'Path');

        header_remove("X-Powered-By");
        ini_set('expose_php', 'off');
    }


    function initErrorHandling() {
        Error::initErrorHandling();
    }

    /*
      some speed tips:
      - Try to use absolute_path when calling require*().
      - The time difference between require_once() vs. require() is so tiny, it's almost always insignificant in terms of performance.
      The one exception is if you have a very large application that has hundreds of require*() calls.
      - When using APC opcode caching, the speed difference between the two is completely irrelevant.
      - get_required_files() shows what you included so far
      - class_exists('myClass') || require('path/to/myClass.class.php'); seems to be faster than *_once()
     */
    function useLib() {
        $a = func_get_args();
        foreach ($a as $i => $name) {
            // TODO: se esiste una dir con lo stesso nome del componente da caricare
            if (strpos($name, '.php') === false) {
                $name.= '.php';
            }
            // qui non si puo' usare Path::join(), viene chiamato prima di Strings dove viene definito
            require_once LIB_PATH . DIRECTORY_SEPARATOR . $name;
        }
    }

}

Bootstrap::init();







