<?php

//----------------------------------------------------------------------------
//
//----------------------------------------------------------------------------
// funzione: tempo di esecuzione di una operazione espressa come Closure
class Timer {
    // tempo di esecuzione di una operazione espressa come Closure
    public static function banchmark(Closure $op, &$bm_time) {
        $time_start = microtime(true);
        $res = $op();
        $bm_time = microtime(true) - $time_start;
        $bm_time = round($bm_time, 4);
        return $res;
    }
}

//----------------------------------------------------------------------------------------------------
//  Page elapsed time
//----------------------------------------------------------------------------------------------------
/*
uso nel template:
TimerFrontend::time_elapsed_frontend_init();
TimerFrontend::time_elapsed_frontend_display();
<li >backend site loaded in <?php echo TimerFrontend::format_time_elapsed( get_time_elapsed() ); ?></li>
<li >frontend site loaded in <span id="load-time"></span></li>
 */
class TimerFrontend {
    // fomatta il float e lo colora di rosso se la pagina è lenta
    public static function format_time_elapsed($s_elapsed, $num_decimals = 3, $color = '#f00') {
        $s = number_format($s_elapsed, $num_decimals, ',', '.');
        if ((float) $s_elapsed > 1.0) { // pagina lenta
            return '<span style="color:' . $color . ';">' . $s . 's</span>';
        } else {
            return $s . 's';
        }
    }

    function time_elapsed_frontend_init() {
        /*
        imposta var globale necessaria a mostrare la velocità di caricamento
        var beforeload = (new Date()).getTime();
         */
        return <<<__END__
    <script type="text/javascript">

    var beforeload = (new Date()).getTime();

    function pick(arg, def) {return (typeof arg == 'undefined' ? def : arg);}
    function display_pageload_time(id) {
        var afterload = (new Date()).getTime();
        var id = pick(id , "load-time");
        var secs = (afterload - beforeload)/1000;
        var div_output = document.getElementById(id);
        if( div_output ) {
            div_output.innerHTML = secs+'s';
        }
    }

    </script>
__END__;
    }

    function time_elapsed_frontend_display() {
        return <<<__END__
        <span id="load-time"></span>
    <script type="text/javascript">
    $(function(){
        display_pageload_time();
    });
    </script>
__END__;
    }

}

/* obsolete:

// multiples times Timer
$timer = new Timer();
$timer->start();
$timer->start();
$durationSlice = $timer->stop();
$duration = $timer->stop();
class Timer {
var $_timers = [];
function start() {
$time = $this->_getMicroTime();
$this->_timers[] = $time;
return $time;
}
// return the last counter
function stop() {
$stop = $this->_getMicroTime();
$start = $this->_getLastTimer();
$elapsedTime = $this->_elapsedTime($start, $stop);
return $elapsedTime;
}
function _getLastTimer() {
$i = count($this->_timers) - 1;
if ($i >= 0) {
return $this->_timers[$i];
} else {
return null;
}
}
function _getMicroTime() {
list($micro, $time) = explode(' ', microtime());
return $micro + $time;
}
//difference between 2 times in microseconds
function _elapsedTime($st, $stopt) {
$i = ($stopt - $st);
$i = $i * 1000;
$i = intval($i);
$i = $i / 1000;
return max(0, $i);
}
}
 */

/*
uso:
$stopwatch = new StopWatch();
... some computation
echo "computation took: ".$stopwatch->clock()." seconds<br />";
class StopWatch {
public $total = 0;
public $time = 0;
public function __construct() {
$this->total = $this->time = microtime(true);
}
public function clock() {
return -$this->time + ($this->time = microtime(true));
}
public function elapsed() {
return microtime(true) - $this->total;
}
public function reset() {
$this->total = $this->time = microtime(true);
}
}
 */
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/Test.php';
/*
$stopwatch = new StopWatch();
sleep(5);
//echo "computation took: ".$stopwatch->clock()." seconds<br />";
is(round($stopwatch->clock(),0), 5, 'can mesure computation');
 */
}