<?php


require_once __DIR__.'/../lib/Test.php';
require_once __DIR__.'/../lib/Array.php';

$a = ["key"=>"i'm associative"];
ok( Arr::isAssociative($a) === true , print_r($a, true) );

$a = Arr::del($a, 'key');
ok( count($a) === 0 , implode(',',$a) );

$a = [1,2,3];
ok( Arr::isAssociative($a) === false, implode(',',$a) );

$a = Arr::del($a, 0);
ok( count($a) === 2 , 'del '.implode(',',$a) );

$a = [1,2,3];
ok( Arr::first($a) === 1, implode(',',$a) );
ok( Arr::last($a) === 3, implode(',',$a) );

$a = ['a'=>0,'b'=>1,'c'=>2];
is( Arr::first($a) , 0, 'array first' );
is( Arr::last($a) , 2, 'array last' );

ok( Arr::equals([], []), 'empty array equals' );
ok( Arr::equals([0,1,2,3,4], [0,1,2,3,4]), 'num array equals' );
ok( Arr::equals(['a'=>'a'], ['a'=>'a']), 'associative array equals' );
ok( Arr::equals(['a'=>'a', 'b'=>'b'], ['a'=>'a']), 'different associative array has all the required values' );

ok( !Arr::equals( ['a'=>'a'], ['a'=>'a', 'b'=>'b'] ), 'different associative array (not all the required values)' );


$a = ['a'=>0,'b'=>1,'c'=>2];
ok( Arr::get($a, 'a') == 0, 'get a key' );

$a = ['a'=>0,'b'=>1,'c'=>2];
ok( Arr::get($a, 'unexisting', 1) == 1, 'get a default for a key' );

$a = ['a'=>1, 'b'=>null];
ok(  Arr::equals(Arr::deleteEmpty($a), ['a'=>1]), 'delete empty' );