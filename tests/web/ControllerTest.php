<?php

require_once dirname(__FILE__).'/../lib/Test.php';
require_once dirname(__FILE__).'/../lib/Controller.php';

//require_once dirname(__FILE__).'/../lib/Controller.php';
class testController extends ActionController{
    function ActionIndex(){
        return 'ok';
    }
}

$c = new testController();
ob_start();
$c->run();

$content = ob_end_clean();

ok($content, 'ok');
