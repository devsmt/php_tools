<?php


require_once __DIR__.'/../lib/Test.php';
require_once __DIR__.'/../lib/Array.php';

$a = array("key"=>"i'm associative");
ok( Arr::isAssociative($a) === true , print_r($a, true) );

$a = Arr::del($a, 'key');
ok( count($a) === 0 , implode(',',$a) );

$a = array(1,2,3);
ok( Arr::isAssociative($a) === false, implode(',',$a) );

$a = Arr::del($a, 0);
ok( count($a) === 2 , 'del '.implode(',',$a) );

$a = array(1,2,3);
ok( Arr::first($a) === 1, implode(',',$a) );
ok( Arr::last($a) === 3, implode(',',$a) );

$a = array('a'=>0,'b'=>1,'c'=>2);
is( Arr::first($a) , 0, 'array first' );
is( Arr::last($a) , 2, 'array last' );

ok( Arr::equals(array(), array()), 'empty array equals' );
ok( Arr::equals(array(0,1,2,3,4), array(0,1,2,3,4)), 'num array equals' );
ok( Arr::equals(array('a'=>'a'), array('a'=>'a')), 'associative array equals' );
ok( Arr::equals(array('a'=>'a', 'b'=>'b'), array('a'=>'a')), 'different associative array has all the required values' );

ok( !Arr::equals( array('a'=>'a'), array('a'=>'a', 'b'=>'b') ), 'different associative array (not all the required values)' );

$a = Arr::range(0,5);
ok( Arr::equals($a, array(0,1,2,3,4)), 'sequence 0..5:'.implode(',',$a) );

$a = array('a'=>0,'b'=>1,'c'=>2);
ok( Arr::get($a, 'a') == 0, 'get a key' );

$a = array('a'=>0,'b'=>1,'c'=>2);
ok( Arr::get($a, 'unexisting', 1) == 1, 'get a default for a key' );

$a = array('a'=>1, 'b'=>null);
ok(  Arr::equals(Arr::deleteEmpty($a), array('a'=>1)), 'delete empty' );