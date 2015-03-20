<?php

// calcola la $perc % di $v
function perc($v, $perc, $decimal = 2) {
    return (float) round(($v / 100) * $perc, 2);
}

function perc_add($v, $perc, $decimal = 2) {
    return $v + perc($v, $perc, $decimal);
}

function perc_sub($v, $perc, $decimal = 2) {
    return $v - perc($v, $perc, $decimal);
}

// se ad un valore e' gie' stata assegnata l'iva, ritorna al valore di partenza
function perc_extract($v, $perc = 20) {
    return ($v / (100 + $perc)) * 100;
}

function iva($v, $p = 20, $decimal = 2) {
    return perc($v, 100 + $p, $decimal);
}

// convert a string/int from any arbitrary base to any arbitrary base,
// up to base 62(0-9,A-Z,a-z are 62 chars)
//
/*
usage:
for ($i = 0;$i < 100000;$i++) {
    $c = base_convert_x($i);
    $j = base_convert_x($c, 62, 10);
    echo "$i => $c => $j\n";
}
*/

function base_convert_x($p_i = '', $p_base = 10, $p_to_base = 62) {
    $_all_chars  = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $_10to62 = str_split($_all_chars);
    $_62to10 = array_flip($_10to62);
    //  convert from from $p_base to base 10
    if ($p_base != 10) {
        $i_in_b10 = 0;
        // power of from base, eg. 1, 8, 64, 512
        $pwr_of_from_base = 1;
        // split input  into chars
        $in_as_chars = str_split($p_i);
        $i_str_len = strlen($p_i);
        $pos = 0;
        while ($pos++ < $i_str_len) {
            $c = $in_as_chars[$i_str_len - $pos];
            $i_in_b10 += (((int) $_62to10[$c]) * $pwr_of_from_base);
            $pwr_of_from_base *= $p_base;
        }
    } else {
        $i_in_b10 = (int) $p_i;
    }

    // Now convert from base-10 to toBase
    // name dividend easier to follow below
    $dividend = (int) $i_in_b10;
    //  number string in toBase
    $i_to_base = '';
    while ($dividend > 0) {
        // eg. 789 / 62  =  12  ( C in base 62 )
        $quotient = (int) ($dividend / $p_to_base);
        // 789 % 62  =  45  ( j in base 62 )
        $remainder = '' . ($dividend % $p_to_base);
        // 789  (in base 10)  =    Cj  (in base 62)
        $i_to_base = $_10to62[$remainder] . $i_to_base;
        // new dividend is the quotient from base division
        $dividend = $quotient;
    }
    if ($i_to_base == '') {
        $i_to_base = '0';
    }
    return $i_to_base;
}
