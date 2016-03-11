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
