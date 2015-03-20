<?php

// interfaccia pubblica
class Event {

    var $owner = null;
    var $_callbacks = array();
    // settato a runtime da Pluggable::getEvents() come il nome della propriete' dell'oggetto che lo istanzia, non va settata manualmente
    var $name = '';

    // occorre passare un riferimento all'oggetto chiamante, in mod da poter accedere dalla collback ai dati dell'oggetto
    function Event(&$owner) {
        $this->__construct($owner);
    }

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
                call_user_func(array($callback[0], $callback[1]), $this->owner);
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
            $this->_callbacks[] = create_function(array($this->owner), $php_code);
        }
    }

}
