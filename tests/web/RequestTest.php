<?php

require_once __DIR__.'/../lib/Test.php';
require_once __DIR__.'/../lib/Request.php';

if( Request::get('test','1') == 1 ){

    ok(Request::get('string','') == 'test',  'string == test');
    $i = Request::getI('int',0);

    ok( $i === 1, "int $i = 1");

} elseif( Request::get('test','2') == 2 ){
    $metched_dir = null;
    Request::hasDirectory('tests', $metched_dir);
    ok( $metched_dir =='tests', "detect dir 'tests': $metched_dir");
}


