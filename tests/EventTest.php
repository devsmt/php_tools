<?php
require_once dirname(__FILE__).'/../lib/Event.php';
// caso d'uso


// funzione che include la logica applicativa del plugin
function my_call_back($sender){
  diag( "I'm my_call_back!\n my sender is:". print_r($sender,1));
}

// e' possibile registrare e chiamare un metodo
class plugin_test{
  function my_call_back($sender){
    diag("I'm plugin_test::my_call_back!\n my sender is:". print_r($sender,1) );
  }
}

// classse di esempio
class Auth {
  // evento di esempio
  // gli eventi dovrebbero avere i prefissi standard before, on e after
  var $onLogin = null;

  function Auth(){
    // costruttore, istanzia l'oggetto evento
    $this->onLogin = new Event($this);
  }

  // funzione che esemplifica la logica applicative
  function login(){
    $login_success = true;

    if($login_success){
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

