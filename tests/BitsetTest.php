<?php


require_once __DIR__.'/../lib/Test.php';
require_once __DIR__.'/../lib/Bitset.php';

$b = new Bitset(0);

ok( $b->get(0) === false );
ok( $b->get(1) === false );


$b->set(1);
ok( $b->get(1) === true );

ok( $b->get(3) === false, 'set:'.$b->set.' bit 3, should be false' );

$b->reset();
diag( "resetted.\n");
ok( $b->get(0) === false );



ok( $b->get(4) === false );


diag( $b->dump(), "resetted.\n" );
$b->reset();

$b->set( $b->sysIntBit );
ok( $b->get( $b->sysIntBit ) === true, 'PHP_INT_MAX='.PHP_INT_MAX.' you can safely store '.$b->sysIntBit.' values' );

$b->reset();
diag( $b->dump(), "resetted.\n" );

diag( "begin set\n");
for($i=0;$i<$b->sysIntBit;$i++){
    $b->set($i);
    ok( $b->get($i) === true, "bit $i, set:".$b->dump() );
}

diag( "begin unset\n");
for($i=0;$i<$b->sysIntBit;$i++){
    $b->set($i, false);
    ok( $b->get($i) === false, "bit $i, set:".$b->dump() );
}

?></pre>