<?php
require_once dirname(__FILE__).'/../lib/Timer.php';
require_once dirname(__FILE__).'/../lib/Test.php';


$stopwatch = new StopWatch();
sleep(5);
//echo "computation took: ".$stopwatch->clock()." seconds<br />";
is(round($stopwatch->clock(),0), 5, 'can mesure computation');
