<?php
/* uso:

// Add `mailer` to the registry array, along with a resolver
IoC::register('mailer', function() {
$mailer = new Mailer;
$mailer->setConfig('...');
return $mailer;
});

// Fetch new mailer instance with dependencies set
$mailer = IoC::resolve('mailer');
 */
class GlobalDIC {
    protected static $registry = [];

    // Add a new resolver to the registry array.
    public static function register($name, Closure $resolver) {
        static::$registry[$name] = $resolver;
    }

    // Create the instance
    public static function resolve($name) {
        if (array_key_exists($name, static::$registry)) {
            $resolver = self::$registry[$name];
            return $resolver();
        }
        throw new Exception('Nothing registered with that name, fool.');
    }
}

/*
minimalistic Dependency Injection Container
 */
class InstanceDIC {

    protected $registry = [];

    public function __set($name, Closure $resolver) {
        $this->registry[$name] = $resolver;
    }

    public function __get($name) {
        return $this->registry[$name]();
    }
}
/* uso:
$c = new DIC;
$c->mailer = function() {
// create new instance of service and configure it
$m = new Mailer();
return $m;
};

// Fetch, boy
$mailer = $c->mailer; // mailer instance
 */