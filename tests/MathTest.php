<?php
require_once __DIR__.'/../lib/Test.php';
require_once __DIR__.'/../lib/Math.php';



for ($i = 0;$i < 10;$i++) {
    $c = base_convert_x($i);
    $j = base_convert_x($c, 62, 10);
    diag( "$i => $c => $j\n" );
    is($c, $j , "base_convert_x converting $i");
}
