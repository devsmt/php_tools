<?php

// calcola la $perc % di $v
function perc($v, $perc, $decimal = 2) {
    return (float) round(($v / 100) * $perc, $decimal);
}

function perc_add($v, $perc, $decimal = 2) {
    return $v + perc($v, $perc, $decimal);
}

function perc_sub($v, $perc, $decimal = 2) {
    return $v - perc($v, $perc, $decimal);
}

// se ad un valore e' gia' stata assegnata l'iva, ritorna al valore di partenza
function perc_extract($v, $perc = 21) {
    return ($v / (100 + $perc)) * 100;
}
// aggiunge 21%
function iva($v, $p = 22, $decimal = 2) {
    return perc($v, 100 + $p, $decimal);
}
// dato $totale e $parziale, ritorna intero 0-100 rappresentante la percentuale
function get_perc($totale, $parziale, $decimal=2){
    $x = 100 / ($totale/$parziale);
    $x = (float) round( $x, $decimal );
    return $x;
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


//----------------------------------------------------------------------------
// BC functions
//----------------------------------------------------------------------------
class HEX {
    // large hex numbers
    public static function bchexdec($hex) {
        if(strlen($hex) == 1) {
            return hexdec($hex);
        } else {
            $remain = substr($hex, 0, -1);
            $last = substr($hex, -1);
            return bcadd(bcmul(16, bchexdec($remain)), hexdec($last));
        }
    }

    public static function bcdechex($dec) {
        $last = bcmod($dec, 16);
        $remain = bcdiv(bcsub($dec, $last), 16);

        if($remain == 0) {
            return dechex($last);
        } else {
            return bcdechex($remain).dechex($last);
        }
    }
}
//------------------------------------------------------------------------------
/*
* Computes the factoral (x!).
* @author Thomas Oldbury.
* @license Public domain.
*/
function bcfact($fact, $scale = 100)
{
    if($fact == 1) return 1;
    return bcmul($fact, bcfact(bcsub($fact, '1'), $scale), $scale);
}

/*
* Computes e^x, where e is Euler's constant, or approximately 2.71828.
* @author Thomas Oldbury.
* @license Public domain.
*/
function bcexp($x, $iters = 7, $scale = 100)
{
    /* Compute e^x. */
    $res = bcadd('1.0', $x, $scale);
    for($i = 0; $i < $iters; $i++)
    {
        $res += bcdiv(bcpow($x, bcadd($i, '2'), $scale), bcfact(bcadd($i, '2'), $scale), $scale);
    }
    return $res;
}

/*
* Computes ln(x).
* @author Thomas Oldbury.
* @license Public domain.
*/
function bcln($a, $iters = 10, $scale = 100)
{
    $result = "0.0";

    for($i = 0; $i < $iters; $i++)
    {
        $pow = bcadd("1.0", bcmul($i, "2.0", $scale), $scale);
        //$pow = 1 + ($i * 2);
        $mul = bcdiv("1.0", $pow, $scale);
        $fraction = bcmul($mul, bcpow(bcdiv(bcsub($a, "1.0", $scale), bcadd($a, "1.0", $scale), $scale), $pow, $scale), $scale);
        $result = bcadd($fraction, $result, $scale);
    }

    $res = bcmul("2.0", $result, $scale);
    return $res;
}


//------------------------------------------------------------------------------


// faster version, more operators implemented
function bc_parse() {
    $argv = func_get_args();
    $string = str_replace(' ', '', "({$argv[0]})");

    $operations = array();
    if (strpos($string, '^') !== false) $operations[] = '\^';
    if (strpbrk($string, '*/%') !== false) $operations[] = '[\*\/\%]';
    if (strpbrk($string, '+-') !== false) $operations[] = '[\+\-]';
    if (strpbrk($string, '<>!=') !== false) $operations[] = '<|>|=|<=|==|>=|!=|<>';

    $string = preg_replace('/\$([0-9\.]+)/e', '$argv[$1]', $string);
    while (preg_match('/\(([^\)\(]*)\)/', $string, $match)) {
        foreach ($operations as $operation) {
            if (preg_match("/([+-]{0,1}[0-9\.]+)($operation)([+-]{0,1}[0-9\.]+)/", $match[1], $m)) {
                switch($m[2]) {
                    case '+':  $result = bcadd($m[1], $m[3]); break;
                    case '-':  $result = bcsub($m[1], $m[3]); break;
                    case '*':  $result = bcmul($m[1], $m[3]); break;
                    case '/':  $result = bcdiv($m[1], $m[3]); break;
                    case '%':  $result = bcmod($m[1], $m[3]); break;
                    case '^':  $result = bcpow($m[1], $m[3]); break;
                    case '==':
                    case '=':  $result = bccomp($m[1], $m[3]) == 0; break;
                    case '>':  $result = bccomp($m[1], $m[3]) == 1; break;
                    case '<':  $result = bccomp($m[1], $m[3]) ==-1; break;
                    case '>=': $result = bccomp($m[1], $m[3]) >= 0; break;
                    case '<=': $result = bccomp($m[1], $m[3]) <= 0; break;
                    case '<>':
                    case '!=': $result = bccomp($m[1], $m[3]) != 0; break;
                }
                $match[1] = str_replace($m[0], $result, $match[1]);
            }
        }
        $string = str_replace($match[0], $match[1], $string);
    }

    return $string;
}



// if colled directly, run the tests:
if (basename($argv[0]) == basename(__FILE__)) {
    require_once 'Test.php';
    bcscale(4);// setta il default scale, va settato prima delle chiamate

    // (10,2+(5,05×6,1))÷3,2 == 12,8140625
    is( bc_parse("10^2") , 100, 'pow');
    is( bc_parse("10 % 2"), 0, 'mod');
    is( bc_parse("(10 / 2)+3"), 8, 'prec');
    is( bc_parse("(10.2+(5.05*6.1))/3.2") , '12.8140', 'complex expression');


}












