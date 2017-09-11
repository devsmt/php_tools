<?php


require_once __DIR__.'/../lib/Test.php';
require_once __DIR__.'/../lib/web/Url.php';
require_once __DIR__.'/../lib/Array.php';

$u = URL::get();
ok( $u == URL::getSelf(), 'for self' );

$u = URL::get('a.php');
ok( $u == 'a.php', "$u == 'a.php'" );

$u = URL::get('a.php', ['action'=>'index']);
is( $u, 'a.php?action=index', "1 param" );

$u = URL::get('a.php', ['action'=>'index', 'empty'=>'']);
is( $u , 'a.php?action=index', "empty" );

$u = URL::get('a.php', ['action'=>'index', 'nonempty'=>0]);
is( $u, 'a.php?action=index&nonempty=0', "nonempty" );

$u = URL::get('a.php', ['action'=>'index'], ['test'=>1], ['test2'=>2] );
is( $u, 'a.php?action=index&test=1&test2=2', "multiple array of params" );
