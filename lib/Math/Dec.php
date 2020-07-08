<?php
declare (strict_types = 1);

//----------------------------------------------------------------------------
//  Decimal
//----------------------------------------------------------------------------
define('BC_PRECISION', 6);
define('DEC_ZERO', '0.00');
bcscale(BC_PRECISION); // setta il default scale, va settato prima delle chiamate
//
class Dec {
    // AS400 restituiesce stringhe valore '.3' o ',3' per indicare float 0.3
    function str_to_dec($val) {
        $val = (string) $val;
        $first_c = substr($val, 0, 1);
        if (in_array($first_c, [',', '.'])) {
            $val = '0' . $val;
        }
        $val = str_replace($sub = ',', $re = '.', $val); // da , a . per conversione float
        return $val;
    }
    function str_is_dec($val) {
        $val = trim($val);
        if (1 == preg_match('/^([0-9\.\,]*)$/i', $val)) {
            // digits and . or ,
            return true;
        }
        return false;
    }
    function perc_bc($v, $perc) {
        $v_dec = bcdiv($v, 100, BC_PRECISION);
        $v_perc = bcmul($v_dec, $perc, BC_PRECISION);
        return $v_perc;
    }
    // applica uno sconto $perc %
    function perc_sub_bc($v, $perc) {
        $vp = perc_bc($v, $perc, BC_PRECISION);
        $v2 = bcsub($v, $vp, BC_PRECISION);
        return $v2;
    }
    // coalesce dec: scarta tutti i nn numeric
    // ritorna 0 se nessuno è valido
    function coalesce_dec() {
        $args = func_get_args();
        foreach ($args as $arg) {
            if (str_is_dec($arg) && (DEC_ZERO != $arg)) {
                return str_to_dec($arg);
            }
        }
        return DEC_ZERO;
    }
    // somma un array di numeri formato bc
    function array_sum_dec(array $a_num) {
        $final_v = array_reduce($a_num, function ($carry_v, $cur_v) {
            if (self::str_is_dec($cur_v)) {
                $carry_v = bcadd($carry_v, $cur_v);
            } else {
                $msg = sprintf('Errore %s ', 'must be dec ' . print_r($cur_v, true));
                throw new \Exception($msg);
            }
            return $carry_v;
        }, $initial_v = '0.00');
        return $final_v;
    }
}

if( isset($argv[0]) && basename($argv[0]) == basename(__FILE__) ) {
}

