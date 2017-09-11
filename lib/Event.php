<?php

/*
uso:
Event::on('event:test', function(){
echo 'OK';
});
Event::trigger('event:test');
// astrazione sulle Closure, oggetti runnable e funzioni procedurali
Event::is_callback($callback)
Event::run_callback($callback, $context)
 */
class Event {
    public static $events = [];
    public static function on($signal, $callback) {
        if (!isset(self::$events[$signal])) {
            self::$events[$signal] = new EventPubSub();
        }
        self::$events[$signal]->listen($callback);
    }
    public static function trigger($signal) {
        self::$events[$signal]->fire();
    }
}
class EventPubSub {
    public $callbacks = [];
    public function listen($callback) {
        if (!self::is_callback($callback)) {
            var_dump($callback);
            die('is not a valid callback');
            return;
        }
        $is_just_registered = array_search($callback, $this->callbacks);
        if (false === $is_just_registered) {
            $this->callbacks[] = $callback;
        }
    }
    public function unlisten(IRunnable $callback) {
        if (!empty($this->callbacks)) {
            $i = array_search($callback, $this->callbacks);
            if ($i !== false) {
                unset($this->callbacks[$i]);
            }
        }
    }
    public function fire() {
        if (!empty($this->callbacks)) {
            foreach ($this->callbacks as $callback) {
                $this->run_callback($callback, $this);
            }
        }
    }
    public static function is_callback($callback) {
        if ($callback instanceof Closure) {
            return true;
        }
        if (is_object($callback) && $callback instanceof IRunnable) {
            return true;
        }
        if (is_string($callback) && is_callable($callback)) {
            return true;
        }
        // passare un [$this, 'methodName' ]
        if (
            is_array($callback) &&
            isset($callback[0]) && isset($callback[1]) &&
            is_callable($callback[0], true, $callback[1])
        ) {
            return true;
        }
        return false;
    }
    public static function run_callback($callback, $context) {
        if (is_object($callback) && $callback instanceof IRunnable) {
            $callback->run($context);
        }
        if (is_callable($callback)) {
            $callback($context);
        }
        // passare un [$this, 'methodName' ]
        if (is_array($callback)) {
            call_user_function($callback[0], $callback[1]);
        }
        return false;
    }
}
Interface IRunnable {
    public function run(EventPubSub $e);
}


// interfaccia pubblica
class __Event {

    var $owner = null;
    var $_callbacks = [];
    // settato a runtime da Pluggable::getEvents() come il nome della propriete' dell'oggetto che lo istanzia, non va settata manualmente
    var $name = '';

    // occorre passare un riferimento all'oggetto chiamante, in modo da poter accedere dalla collback ai dati dell'oggetto
    function __construct(&$owner) {
        $this->owner = $owner;
    }

    // quando l'evento e' lanciato tutte le funzioni callback registrate vengono lanciate
    function fire() {
        ///echo 'onFire:', var_dump($this->_callbacks);
        foreach ($this->_callbacks as $i => $callback) {
            if (is_string($callback)) {
                call_user_func($callback, $this->owner);
            } else {
                call_user_func([$callback[0], $callback[1]], $this->owner);
            }
        }
    }

    // registra una funzione callback per l'evento corrente
    function attach() {
        if (func_num_args() == 1) {
            $this->_callbacks[] = func_get_arg(0);
        } else {
            $this->_callbacks[] = array(func_get_arg(0), func_get_arg(1));
        }
    }

    function addListener($php_code) {
        if (func_num_args() == 1) {
            $this->_callbacks[] = create_function([$this->owner], $php_code);
        }
    }

}
