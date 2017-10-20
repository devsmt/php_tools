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
function get_perc($totale, $parziale, $decimal = 2) {
    $x = 100 / ($totale / $parziale);
    $x = (float) round($x, $decimal);
    return $x;
}

// convert a string/int from any arbitrary base to any arbitrary base,
// up to base 62(0-9,A-Z,a-z are 62 chars)
//
/*
usage:
for ($i = 0;$i < 100000;$i++) {
$str = base_convert_x($i, 10, 62);
$i2 = base_convert_x($str, 62, 10);
// $i == $i2
echo "$i => $str => $i2 \n";
}
 */
function base_convert_x(string $p_num = '', int $p_base = 10, int $p_to_base = 62,
    string $CODESET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
): string {
    // $p_to_base depends from the char set choosen
    if ($p_to_base < 0 || $p_to_base > strlen($CODESET)) {
        throw new \Exception("$p_to_base must be < than " . strlen($CODESET));
    }
    $a_chars_b62 = str_split($CODESET); // char[]
    $h_b62to10 = array_flip($a_chars_b62); // hash<char, int> { ... 'a' =>11 ...}
    // decode: convert from from $p_base to base 10
    if ($p_base != 10) {
        // $num_b10 = (int) base_convert($p_num, $p_base, 10 );
        $num_b10 = 0;
        // power of from base, eg. 1, 8, 64, 512
        $pwr_of_from_base = 1;
        // split input  into chars
        $a_num_chars = str_split($p_num);
        $num_strlen = strlen($p_num);
        for ($back_pos = ($num_strlen - 1); $back_pos >= 0; $back_pos--) {
            $char = $a_num_chars[$back_pos];
            $pwr_b10 = ((int) $h_b62to10[$char]);
            $num_b10 += ($pwr_b10 * $pwr_of_from_base);
            $pwr_of_from_base *= $p_base;
        }
    } else {
        $num_b10 = (int) $p_num;
    }

    // encode: convert from base-10 to $p_to_base
    // number string in base $p_to_base
    $i_to_base = '';
    while ($num_b10 > 0) {
        // eg. 789 / 62  =  12  ( C in base 62 )
        $quotient = (int) ($num_b10 / $p_to_base);
        // 789 % 62  =  45  ( j in base 62 )
        $remainder = '' . ($num_b10 % $p_to_base);
        // 789  (in base 10)  =    Cj  (in base 62)
        $char = $a_chars_b62[$remainder];
        $i_to_base = $char . $i_to_base;
        // new dividend is the quotient from base division
        $num_b10 = $quotient;
    }
    if ($i_to_base == '') {
        $i_to_base = '0';
    }
    return $i_to_base;
}

// dato un valore massimo e uno minimo, ritorna un valore compreso trai limiti
function clamp($current, $min=0, $max=999) {
    return max($min, min($max, $current));
}

// similar to base_convert_x, this alg:
// uses only readable chars, decides base from lenght of CODESET
// works with very big INT (bc math functions)
class BigIntToStr {
    // readable character set excluded (0,O,1,l)
    const CODESET = "23456789abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ";
    static function encode(int $n): string{
        $base = strlen(self::CODESET);
        $converted = '';
        while ($n > 0) {
            $i_pos = bcmod($n, $base);
            $char = substr(self::CODESET, $i_pos, 1);
            $converted = $char . $converted;
            $n = bcdiv($n, $base);
            $n = bcmul($n, '1', 0); //floor
        }
        return $converted;
    }
    static function decode(string $code): int{
        $base = strlen(self::CODESET);
        $c = '0';
        for ($i = strlen($code); $i; $i--) {
            $i_pos = (-1 * ($i - strlen($code)));
            $s_j = substr($code, $i_pos, 1);
            $i_x = strpos(self::CODESET, $s_j);
            $i_z = bcmul($i_x, bcpow($base, $i - 1));
            $c = bcadd($c, $i_z);
        }
        return bcmul($c, 1, 0);
    }
}

// takes a decimal number and returnrs roman
function dec2roman($num) {
    $a_chars = 'IVXLCDM';
    $c_len = strlen($a_chars);
    $b = 0;
    $roman = '';
    for ($i = 5; $num > 0; $b++, $i ^= 7) {
        for ($j = $num % $i,
            $num = $num / $i ^ 0;
            $j--;
        ) {
            if ($j > 2) {
                $j = 1;
                $idx = $b + $num - ($num &= -2) + $j;
            } else {
                $idx = $b;
            }
            $roman = $a_chars[$idx] . $roman;
        }
    }
    return $roman;
}

//----------------------------------------------------------------------------
// BC functions
//----------------------------------------------------------------------------
class HEX {
    // large hex numbers
    public static function bchexdec($hex) {
        if (strlen($hex) == 1) {
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

        if ($remain == 0) {
            return dechex($last);
        } else {
            return bcdechex($remain) . dechex($last);
        }
    }
}
//------------------------------------------------------------------------------
/*
 * Computes the factoral (x!).
 * @author Thomas Oldbury.
 * @license Public domain.
 */
function bcfact($fact, $scale = 100) {
    if ($fact == 1) {
        return 1;
    }

    return bcmul($fact, bcfact(bcsub($fact, '1'), $scale), $scale);
}

/*
 * Computes e^x, where e is Euler's constant, or approximately 2.71828.
 * @author Thomas Oldbury.
 * @license Public domain.
 */
function bcexp($x, $iters = 7, $scale = 100) {
    /* Compute e^x. */
    $res = bcadd('1.0', $x, $scale);
    for ($i = 0; $i < $iters; $i++) {
        $res += bcdiv(bcpow($x, bcadd($i, '2'), $scale), bcfact(bcadd($i, '2'), $scale), $scale);
    }
    return $res;
}

/*
 * Computes ln(x).
 * @author Thomas Oldbury.
 * @license Public domain.
 */
function bcln($a, $iters = 10, $scale = 100) {
    $result = "0.0";

    for ($i = 0; $i < $iters; $i++) {
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

    $operations = [];
    if (strpos($string, '^') !== false) {
        $operations[] = '\^';
    }

    if (strpbrk($string, '*/%') !== false) {
        $operations[] = '[\*\/\%]';
    }

    if (strpbrk($string, '+-') !== false) {
        $operations[] = '[\+\-]';
    }

    if (strpbrk($string, '<>!=') !== false) {
        $operations[] = '<|>|=|<=|==|>=|!=|<>';
    }

    $string = preg_replace('/\$([0-9\.]+)/e', '$argv[$1]', $string);
    while (preg_match('/\(([^\)\(]*)\)/', $string, $match)) {
        foreach ($operations as $operation) {
            if (preg_match("/([+-]{0,1}[0-9\.]+)($operation)([+-]{0,1}[0-9\.]+)/", $match[1], $m)) {
                switch ($m[2]) {
                case '+':$result = bcadd($m[1], $m[3]);
                    break;
                case '-':$result = bcsub($m[1], $m[3]);
                    break;
                case '*':$result = bcmul($m[1], $m[3]);
                    break;
                case '/':$result = bcdiv($m[1], $m[3]);
                    break;
                case '%':$result = bcmod($m[1], $m[3]);
                    break;
                case '^':$result = bcpow($m[1], $m[3]);
                    break;
                case '==':
                case '=':$result = bccomp($m[1], $m[3]) == 0;
                    break;
                case '>':$result = bccomp($m[1], $m[3]) == 1;
                    break;
                case '<':$result = bccomp($m[1], $m[3]) == -1;
                    break;
                case '>=':$result = bccomp($m[1], $m[3]) >= 0;
                    break;
                case '<=':$result = bccomp($m[1], $m[3]) <= 0;
                    break;
                case '<>':
                case '!=':$result = bccomp($m[1], $m[3]) != 0;
                    break;
                }
                $match[1] = str_replace($m[0], $result, $match[1]);
            }
        }
        $string = str_replace($match[0], $match[1], $string);
    }

    return $string;
}

//----------------------------------------------------------------------------
//  metric conversion
//----------------------------------------------------------------------------
// da metri ad altre unit
function m2km($m) {   return $m / 1000.0;  }
function m2m ($m) {   return $m;           }
function m2cm($m) {   return $m * 100.0;   }
function m2mm($m) {   return $m * 1000.0;  }
function m2ft($m) {   return $m * 3.28084; }
//
function cm2km($cm) { return m2km( cm2m($cm)   );   }
function cm2m ($cm) { return m2m ( $cm / 100.0 );   }
function cm2cm($cm) { return m2cm( cm2m($cm)   );   }
function cm2mm($cm) { return m2mm( cm2m($cm)   );   }
function cm2ft($cm) { return m2ft( cm2m($cm)   );   }
//
function mm2km($mm) { return m2km( mm2m($mm)    );  }
function mm2m ($mm) { return m2m ( $mm / 1000.0 );  }
function mm2cm($mm) { return m2cm( mm2m($mm)    );  }
function mm2mm($mm) { return m2mm( mm2m($mm)    );  }
function mm2ft($mm) { return m2ft( mm2m($mm)    );  }


//----------------------------------------------------------------------------
//
//----------------------------------------------------------------------------

// if colled directly, run the tests:
if (isset($_SERVER['argv']) && basename($_SERVER['argv'][0]) == basename(__FILE__)) {
    require_once 'Test.php';
    bcscale(4); // setta il default scale, va settato prima delle chiamate

    switch ($argv[1]) {
    case 'base':
        for ($i = 0; $i < 1000; $i++) {
            $str = base_convert_x($i, 10, 62);
            $i2 = base_convert_x($str, 62, 10);
            is($i, $i2, "$i == $i2 $str ");
        }
        for ($i = 0; $i < 100; $i++) {
            $r = dec2roman($i);
            ok($r, "$i == $r   ");

        }
        break;
    default:
        // (10,2+(5,05×6,1))÷3,2 == 12,8140625
        is(bc_parse("10^2"), 100, 'pow');
        is(bc_parse("10 % 2"), 0, 'mod');
        is(bc_parse("(10 / 2)+3"), 8, 'prec');
        is(bc_parse("(10.2+(5.05*6.1))/3.2"), '12.8140', 'complex expression');
        break;
    }

}
