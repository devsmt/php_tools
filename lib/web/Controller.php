<?php

require_once dirname(__FILE__) . '/Request.php';

/*
  classe semplicissima per ottenere che una sottoclasse che implementa
  i methodi 'Action*' vengano invocati a seconda del parametro action della querystring,
  se la query string è vuota viene chiamato ActionIndex
 */

class ActionController {

    var $action = 'index';
    var $response = null;
    var $view = null;

    function ActionController($config = array()) {
        $this->__construct($config = array());
    }

    function __construct($config = array()) {
        if ($action = Request::get('action', false)) {
            // toglie caratteri non sicuri
            $action = preg_replace('/[^a-zA-Z0-9_]/', '_', $action);
            // limite alla lunghezza, previene possibili attacchi
            if (strlen($action) > 10) {
                die('action max 10 char');
            }

            $this->action = $action;
        }
    }

    /* recupera l'azione corrente e
      cerca di chiamare il metodo corrispondete
      il quale ritorna una Response da inviare
     */

    function run() {
        $method = 'Action' . ucfirst($this->action);
        if (method_exists($this, $method)) { // con get_class funziona solo su php5
            echo $this->$method();
        } else {
            // se stanno cercando vulnerabilità, diamo il tempo a fail2ban di passare
            sleep(30);

            ResponseHeader::notFound();
            Weasel::Error(__LINE__, __FILE__, 'azione ' . $method . ' non implementata');
        }
    }

    /*
      get path to the default view
     */

    function defaultViewPath() {
        return Path::join(CURRENT_MODULE, $this->action . '.php');
    }

    /*
      il template di default è /template/$module/$action.php
     */

    function loadView($file = '', $data = null) {
        if (is_null($this->view)) {
            if (empty($file)) {
                $file = $this->defaultViewPath();
            }
            $this->view = new View($file, $data);
        }
        if (!empty($data)) {
            $this->view->data = & $data;
        }
    }

    /*
      method catchall
      inizializza una view
      inserisce nella risposta la view relativa al modulo corrente
      e passa oltre
     */

    function ActionIndex() {
        $this->ExecuteAction();
    }

    /*
      rispondi con il template di default (/template/$module/$action.php)
     */

    function ExecuteAction($file = '', $data = null) {
        $this->loadView($file, $data);
        $r = Weasel::getResponse();
        $r->setView($this->view);
        return $r->Send();
    }

    /*
      Redirects to another method, and terminates the current request.

      Putting the word "Controller" at the end of each controller name is optional.

      $this->redirect('about');
      $this->redirect(array('BlogController', 'post'), array('post_id'=>5));
      $this->redirect(array('Blog', 'post'), array('post_id'=>5));
      $controller_and_method String method name if redirecting to a method in the current controller or array('ControllerName', 'methodName') if redirecting to a method in another controller.
      $arguments Array arguments to resolve the route, or boolean false.

      function redirect( $controller_and_method, $arguments = false )  {
      header('Location: '.Dispatcher::getUrl( $controller_and_method, $arguments ) );
      exit;
      } */
}
