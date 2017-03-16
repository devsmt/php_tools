<?php

require_once __DIR__.'/../lib/Event.php';
require_once __DIR__.'/../lib/Plugin.php';
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
