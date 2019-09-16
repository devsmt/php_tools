<?php
declare (strict_types = 1);
//
// works only in CLI where pcntl can be installed.
// takes a map of key => computations, exec them in parallel, return a map of key => results
function parallel_map(array $map, $numcores = 5) {
    $ipc = new IPC($map, $numcores);
    $ipc->start();
    return $ipc->getResult();
}
//
class IPC {
    private $a_ops, $numcores, $max_wait_time;
    private $a_results = [];
    function __construct(array $a_closures, $numcores = 5) {
        $this->a_results = [];
        // create a worker foreach closure operation
        foreach ($a_closures as $k => $w) {
            $this->a_ops[$k] = new ClosureWorker($w);
        }
        $this->numcores = $numcores;
        $this->max_wait_time = 0;
    }
    //
    // Sets the maximum number of Child Processes that can be running at any one time.
    // If set to 0, There is no limit.
    //
    // If $max is not a integer of is less than 0 an InvalidArgumentException is thrown
    //
    public function numcores($max) {
        if (!is_int($max) || $max < 0) {
            throw new \InvalidArgumentException("max must be greater than or equal to 0");
        }
        $this->numcores = $max;
        return $this;
    }
    //
    // Sets the maximum amount of time a child process can run for before the process is terminated.
    // If set to 0, There is no limit.
    //
    // If $seconds is not a integer of is less than 0 an InvalidArgumentException is thrown
    //
    public function maxWaitTime($seconds) {
        if (!is_int($seconds) || $seconds < 0) {
            throw new \InvalidArgumentException("seconds must be greater than or equal to 0");
        }
        $this->max_wait_time = $seconds;
        return $this;
    }
    public function start() {
        $pids = [];
        $sockets = [];
        foreach ($this->a_ops as $key => $cWorker) {
            $socketPair = $this->makeSocketPair($key);
            if (!$socketPair) {
                continue;
            }
            $pid = pcntl_fork();
            if ($pid === 0) {
                $this->childProcess($socketPair, $cWorker);
                exit(0);
            } else if ($pid > 0) {
                $sockets[$pid] = $socketPair;
                if ($this->numcores > 0 && (count($sockets) >= $this->numcores)) {
                    $this->reduceProcessCount($sockets, $this->numcores - 1);
                }
            }
        }
        $this->reduceProcessCount($sockets, 0);
        return $this;
    }
    // get compute results
    public function getResult() {
        return $this->a_results;
    }
    //----------------------------------------------------------------------------
    //  private
    //----------------------------------------------------------------------------
    protected function childProcess(SocketPair $socketPair, ClosureWorker $cWorker) {
        $socketPair->closeClient();
        $output = $cWorker->produce();
        socket_set_nonblock($socketPair->serverSock());
        $output = ($output) ? trim($output) : '';
        while ((strlen($output) > 0) && ($wrote = socket_write($socketPair->serverSock(), $output))) {
            $output = substr($output, $wrote);
        }
        $socketPair->closeServer();
    }
    protected function parentProcess(SocketPair $socketPair) {
        $socketPair->closeServer();
        $content = '';
        while ($line = socket_read($socketPair->clientSock(), 1129)) {
            $len = strlen($content);
            $content .= $line;
        }
        $socketPair->closeClient();
        $this->a_results[$socketPair->getKey()] = $content;
    }
    protected function makeSocketPair($key) {
        $pair = [];
        if (socket_create_pair(AF_UNIX, SOCK_STREAM, 0, $pair) === false) {
            $msg = "socket_create_pair failed. Reason: " . socket_strerror(socket_last_error());
            throw new \Exception($msg); // exceptions_
        }
        return new SocketPair($key, $pair[0], $pair[1]);
    }
    protected function reduceProcessCount(array &$sockets, $to) {
        while (count($sockets) > $to) {
            $pid = pcntl_wait($status, WNOHANG);
            if ($pid > 0) {
                $this->parentProcess($sockets[$pid]);
                unset($sockets[$pid]);
            } else {
                $this->killExpiredProcesses($sockets);
                // echo "sockets count:", var_dump(count($sockets));
                usleep(200000);
            }
        }
    }
    protected function killExpiredProcesses(array &$sockets) {
        if ($this->max_wait_time) {
            foreach ($sockets as $pid => $pair) {
                if ($pair->passedAllotedTime($this->max_wait_time)) {
                    $pair->closeServer();
                    $pair->closeClient();
                    unset($sockets[$pid]);
                    // TODO: install a logger, echoing is not cool
                    echo "PID: $pid took to long\n";
                    posix_kill($pid, SIGINT);
                }
            }
        }
    }
}
//----------------------------------------------------------------------------
//  support classes
//----------------------------------------------------------------------------
class ClosureWorker {
    public function __construct($f) {
        $this->f = $f;
    }
    public function produce(): string{
        $_f = $this->f;
        return $_f();
    }
}
// an astraction for a socket communication
class SocketPair {
    private $key, $clientSock, $serverSock, $createTime;
    function __construct($key, $clientSock, $serverSock, $createTime = null) {
        $this->key = $key;
        $this->clientSock = $clientSock;
        $this->serverSock = $serverSock;
        $this->createTime = isset($createTime) ? $createTime : time();
    }
    // the name of the operation
    function getKey() {
        return $this->key;
    }
    function passedAllotedTime($allotedTime) {
        return $this->createTime + $allotedTime <= time();
    }
    function clientSock() {
        return $this->clientSock;
    }
    function serverSock() {
        return $this->serverSock;
    }
    function closeClient() {
        socket_close($this->clientSock);
    }
    function closeServer() {
        socket_close($this->serverSock);
    }
}
//----------------------------------------------------------------------------
// main
//----------------------------------------------------------------------------
// if colled directly in CLI, run the tests:
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    // a function that takes long
    function curl_get($url): string{
        //echo "b\n";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $content = curl_exec($ch);
        curl_close($ch);
        sleep(2);
        //echo "e\n";
        return $content;
    }
    // a map of computations
    $map = [
        'a' => function () {
            return curl_get('https://www.google.ca/');},
        'b' => function () {
            return curl_get('http://php.net/');},
        'c' => function () {
            sleep(2);
            return 'test';
        },
        'd' => function () {
            return curl_get('http://localhost/');
        },
        'e' => function () {
            return curl_get('http://agenti4.lampa.test/');
        },
    ];
    //--- DBG -----------------------------------------------------------
    // define profile macro
    $__profile = function ($label, \Closure $op) {
        $mt_begin = microtime(true);
        $res = $op();
        echo sprintf('<pre>[%s] time:%F ms</pre>',
            $label,
            microtime(true) - $mt_begin
        );
        return $res;
    };
    //--- end DBG ------------------------------------------------------
    $ipc_r = $__profile('__LABEL__', function () use ($map) {
        return $ipc_r = parallel_map($map);
    });
    $r = array_map(function ($val) {
        return strlen($val);
    }, $ipc_r);
    var_dump($r);
}