<?php



class Autoloader {

    public static function register() {
        // $namespace\$class.php loader
        spl_autoload_register(function ($pClassName) {
            $path = str_replace('\\', DIRECTORY_SEPARATOR, $pClassName);
            require_once (__DIR__ . DIRECTORY_SEPARATOR . $path . ".php");
            return true;
        });
    }
    // register special Class names
    public static function registerMVC() {
        spl_autoload_register(function ($pClassName) {
            if (preg_match('/[a-zA-Z]+Controller$/', $pClassName)) {
                require_once __DIR__ . '/controllers/' . $pClassName . '.php';
                return true;
            } elseif (preg_match('/[a-zA-Z]+Model$/', $pClassName)) {
                require_once __DIR__ . '/models/' . $pClassName . '.php';
                return true;
            } elseif (preg_match('/[a-zA-Z]+View$/', $pClassName)) {
                require_once __DIR__ . '/views/' . $pClassName . '.php';
                return true;
            }
        });
    }

    // PSR4 style
    public static function registerPSR4() {
        /**
         * @see PSR-4 autoload reference implementation
         *
         * new \Foo\Bar\Baz\Qux;
         * \Foo\Bar\Baz\Qux class => /path/to/project/src/Baz/Qux.php:
         */
        spl_autoload_register(function ($pClassName) {
            // project-specific namespace prefix
            $prefix = 'Mobile';// Foo\\Bar\\
            // base directory for the namespace prefix
            $base_dir = __DIR__; // . '/src/';
            // does the class use the namespace prefix?
            $len = strlen($prefix);
            if (strncmp($prefix, $pClassName, $len) !== 0) {
                // DBG echo " autoloading NS prefix: $pClassName \n";
                // no, move to the next registered autoloader
                return;
            }
            // get the relative class name
            $relative_class = substr($pClassName, $len);
            // replace the namespace prefix with the base directory, replace namespace
            // separators with directory separators in the relative class name, append
            // with .php
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
            if (file_exists($file)) {
                require $file;
            } else {
                // DBG echo " autoloading KO: $file\n";
            }
        });
    }

}
