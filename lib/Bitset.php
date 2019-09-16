<?php
//
// un bitset è un numero intero che viene usato come una sequenza di bit, interpretabili
// come una sequenza di valori booleani
//
class BitSet {
    function __construct($v = 0) {
        $this->set = $v;
        // non funziona con interi oltre i 32bit su sistemi a 32bit
        // logaritmo in base a di un numero x è l'esponente da dare ad a per ottenere x
        // (x viene chiamato argomento del logaritmo)
        // ritorna 31 nei sitemi 32bit, indici sicuri da 0 a 30
        $this->sysIntBit = floor(log(PHP_INT_MAX, 2));
    }
    function set($index, $v = 1) {
        if ($index >= 0 && $index <= $this->sysIntBit) {
            $m = (1 << $index);
            if ($v) {
                $this->set = $this->set | $m;
            } elseif (!$v) {
                $this->set = $this->set ^ $m;
            }
            return true;
        } else {
            echo "calling BitSet::set with wrong parameters:", $this->dump($index, $v);
            return false;
        }
    }
    function get($index) {
        if ($index >= 0 && $index <= $this->sysIntBit) {
            $m = (1 << $index); // means "multiply by two"
            $v = $this->set & $m;
            return $v === $m;
        } else {
            echo "calling BitSet::set with wrong parameters:", $this->dump($index, $v);
            return null;
        }
    }
    function reset() {
        $this->set = 0;
    }
    function dump() {
        return "BitSet::set=" . $this->set . "(bin:" . decbin($this->set) . ")";
    }
}
function get_bit(int $int, int $offset): bool {
    return (bool) ((1 << $offset) & $int);
}
function set_bit(int $int, int $offset, bool $value): int {
    return $value ? $int | (1 << $offset) : $int & ~(1 << $offset);
}
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/Test.php';
    $b = new Bitset(0);
    ok($b->get(0) === false);
    ok($b->get(1) === false);
    $b->set(1);
    ok($b->get(1) === true);
    ok($b->get(3) === false, 'set:' . $b->set . ' bit 3, should be false');
    $b->reset();
    diag("resetted.\n");
    ok($b->get(0) === false);
    ok($b->get(4) === false);
    diag($b->dump(), "resetted.\n");
    $b->reset();
    $b->set($b->sysIntBit);
    ok($b->get($b->sysIntBit) === true, 'PHP_INT_MAX=' . PHP_INT_MAX . ' you can safely store ' . $b->sysIntBit . ' values');
    $b->reset();
    diag($b->dump(), "resetted.\n");
    diag("begin set\n");
    for ($i = 0; $i < $b->sysIntBit; $i++) {
        $b->set($i);
        ok($b->get($i) === true, "bit $i, set:" . $b->dump());
    }
    diag("begin unset\n");
    for ($i = 0; $i < $b->sysIntBit; $i++) {
        $b->set($i, false);
        ok($b->get($i) === false, "bit $i, set:" . $b->dump());
    }
}