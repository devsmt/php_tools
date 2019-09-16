<?php
class URL {
    function getSelf() {
        if (isset($_SERVER['PHP_SELF'])) {
            return $_SERVER['PHP_SELF'];
        } else {
            return '';
        }
    }
    // costruisce una url, a partire dalla pagina inviata e dai dati inviati
    // non appende GET automaticamente, se non presente il par $page usa PHP_SELF
    // $page='', $data=[]
    function get() {
        $args = func_get_args();
        $c = func_num_args();
        switch ($c) {
        case 0:
            $page = URL::getSelf();
            $data = [];
            break;
        case 1:
            if (is_array($args[0])) {
                $page = URL::getSelf();
                $data = $args[0];
            } else {
                $page = $args[0];
                $data = [];
            }
            break;
        case 2:
            $page = $args[0];
            $data = $args[1];
            break;
        default:
            $page = $args[0];
            $data = $args[1];
            for ($i = 2; $i < $c; $i++) {
                $data = array_merge($data, $args[$i]);
            }
            break;
        }
        if (count($data)) {
            // removes all NULL, FALSE and Empty Strings but leaves 0 (zero) values
            $data = array_filter($data, 'strlen');
            return $page . '?' . str_replace('&amp;', '&', http_build_query($data));
        } else {
            return $page;
        }
    }
}
// if colled directly in CLI, run the tests:
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    include __DIR__ . '/../Test.php';
    $u = URL::get();
    ok($u == URL::getSelf(), 'for self');
    $u = URL::get('a.php');
    ok($u == 'a.php', "$u == 'a.php'");
    $u = URL::get('a.php', ['action' => 'index']);
    is($u, 'a.php?action=index', "1 param");
    $u = URL::get('a.php', ['action' => 'index', 'empty' => '']);
    is($u, 'a.php?action=index', "empty");
    $u = URL::get('a.php', ['action' => 'index', 'nonempty' => 0]);
    is($u, 'a.php?action=index&nonempty=0', "nonempty");
    $u = URL::get('a.php', ['action' => 'index'], ['test' => 1], ['test2' => 2]);
    is($u, 'a.php?action=index&test=1&test2=2', "multiple array of params");
}