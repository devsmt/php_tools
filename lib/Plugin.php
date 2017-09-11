<?php

/*
per creare a runtime un oggetto e accoppiare agli eventi esposti i plugin disponibili
occorere
- una lista degli eventi dell'oggetto
- unalista delle funzioni esposte dai plugin e la lora disponibilità ad gestire eventi di quale classe
alla creazione dell'oggetto business, viene fatto l'accoppiamento evento -> callback

nota: se si passa un oggetto come parametro di una funzione, php lo passa per copia a meno che non si definisca la funzione

function test(&$obj){
return true;
}
 */

// una classe pluggable espone alla creazione eventi che un plugin puo' intercettare e gestire
// con le proprie callback
class Pluggable {

    function __construct() {
        $this->connectEvents();
        // il plugin dovrebbe poter modificare l'interfaccia utente
        // aggiungendo i propri menu' ad esempio
    }

    // richiedi i plugin disponibili per gli eventi della classe corrente
    function connectEvents() {
        $classVars = get_object_vars($this);
        $result = [];
        foreach ($classVars as $k => $v) {
            if (is_object($this->$k) && is_a($this->$k, 'Event')) {
                $this->$k->name = $k;
                $GLOBALS['PluginManager']->connectEvent($this->$k);
            }
        }
    }

}

// un plug in e' un modulo di dati e funzioni che svolge compiti indipendenti
// da quelle svolte dagli altri moduli
class Plugin {

    // className: { {event name:'', method:''} }
    var $slots = [];

    function __construct(&$manager) {
        $manager->registerPlugin($this);
    }

    // class name, event name, method
    // un solo metodo per evento per plugin
    function exposeCallback($className, $event_name, $method) {
        $className = strtolower($className);
        if (!array_key_exists($className, $this->slots)) {
            $this->map[$className] = [];
        }
        $this->slots[$className][] = ['event' => $event_name, 'method' => $method];
    }

}

// mantiene una lista delle funzionalite' dinamiche disponibili
// e accoppia plugin e pluggable a runtime
// Application e Gestore sono un candidati per implementare il manager
// singleton
class PluginManager {

    // riferimenti ai plugin installati
    var $plugins = [];

    function registerPlugin($plugin) {
        $this->plugins[] = &$plugin;
        ///echo 'after register plugins:', var_dump($this->plugins); // dbg
        // todo: gestire le parti di interfaccia!
    }

    // di ogni plugin controlla gli handler esposti ed eventualmente connetti gli eventi
    function connectEvent(&$event) {
        ///echo 'onConnectEvent:',var_dump($this->plugins);
        $ownerClass = strtolower(get_class($event->owner));
        ///var_dump($ownerClass);
        for ($i = 0; $i < count($this->plugins); $i++) {
            $plugin = &$this->plugins[$i];
            if (array_key_exists($ownerClass, $this->plugins[$i]->slots)) {
                $mapEvent = &$plugin->slots[$ownerClass];
                //TODO: performance cercare di evitare il for (con una struttura dati + efficiente?)
                for ($j = 0; $j < count($mapEvent); $j++) {
                    if ($mapEvent[$j]['event'] === $event->name) {
                        ///echo 'attach!',var_dump($plugin,$mapEvent[$j]['method']);
                        $event->attach($plugin, $mapEvent[$j]['method']);
                        // e' stato trovato un handler valido, ricerca finita per questo plugin
                        break;
                    }
                }
            }
        }
    }

    // cerca nella directory di configurazione e cerca di inizializzare tutti i plugin(classi) presenti
    function initPlugins($dir = '') {

    }

}
