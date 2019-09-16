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
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/Test.php';

    // caso d'uso
    // funzione che include la logica applicativa del plugin
    function my_call_back($sender) {
        diag("I'm my_call_back!\n my sender is:" . print_r($sender, 1));
    }
    // e' possibile registrare e chiamare un metodo
    class plugin_test {
        function my_call_back($sender) {
            diag("I'm plugin_test::my_call_back!\n my sender is:" . print_r($sender, 1));
        }
    }
    // classse di esempio
    class Auth {
        // evento di esempio
        // gli eventi dovrebbero avere i prefissi standard before, on e after
        var $onLogin = null;
        function Auth() {
            // costruttore, istanzia l'oggetto evento
            $this->onLogin = new Event($this);
        }
        // funzione che esemplifica la logica applicative
        function login() {
            $login_success = true;
            if ($login_success) {
                // viene lanciato l'evento
                $this->onLogin->fire();
            }
        }
    }
    // istanza del plugin
    $plugin = new plugin_test();
    $auth = new Auth();
    // il plugin registra la propria callback
    $auth->onLogin->attach('my_call_back');
    // sintassi alternativa, per registrare un metodo( $obj->my_call_back()  )
    $auth->onLogin->attach($plugin, 'my_call_back');
    // chiamando il metodo
    $auth->login();

/*
//------------------------------------------------------------------------------
//  caso d'uso
//------------------------------------------------------------------------------
class Model extends Pluggable {
    var $onDoAction = null;
    function Model(){
        $this->onDoAction =& new Event($this);
        // esponi eventi, dopo averli creati
        $this->Pluggable();
    }
    function doAction(){
        ///echo "doAction $Model->onDoAction:", var_dump($this->onDoAction), '<br>';
        $this->onDoAction->fire();
    }
}
class PluginTest extends Plugin {
    function PluginTest(&$manager){
        $this->exposeCallback( 'Model', 'onDoAction', 'act_Model_doAction');
        ///echo "after exposeCallback plugin->slots:", var_dump($this->slots);
        // costruttore, che registra le callback del plugin, va eseguito dopo expose
        $this->Plugin($manager);
    }
    function act_Model_doAction(&$sender){
        echo " <b>callback act_Model_doAction eseguita!!!</b>";
    }
}
$PluginManager = &new PluginManager();
$pluginTest = &new PluginTest($PluginManager);// i plugin vanno creati prima degli oggetti core per registrare i metodi disponibili
$model = new Model();
$model->doAction();
*/
}