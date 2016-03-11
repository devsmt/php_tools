<?php

// A CSRF attack usually relies on a vulnerable GET URL.
// The rule of thumb is to make GET requests they can be run over and over without
// modifing the state of the application.
// If you want the user to change something or give you data, you want to POST
// A CSRF token is a piece of data that's embedded into the form data, that's a randomly generated
/*
  $token = rand(0,100);
  $_SESSION['token'] = $token;
  // $token è passato come parametro nella form
  if ($_SESSION['token'] !== $_POST['token']) {
  die('fail, stai riutilizzando una form senza richiederla!');
  }
  you can check to see if the request did in fact come from the page on your site
 */
class CSRF {
    
}
