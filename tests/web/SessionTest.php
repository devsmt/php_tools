<?php

//header('Content-Type: text/plain');
require_once dirname(__FILE__).'/../lib/Test.php';
require_once dirname(__FILE__).'/../lib/Session.php';

SessionDriverPHP5::set('var', 'test');
$v = SessionDriverPHP5::get('var',0);
is( $v, 'test', "$v == test");


// niente aoutput altrimenti non si inizializza la sessione

Session::set('a','test');

ok( Session::get('a','') == 'test', Session::get('a',null) );
Session::dump();

Session::clear();
ok( Session::get('a','') == '', 'session clear');


Session::destroy();
is( count($_SESSION), 0, 'session empty');
