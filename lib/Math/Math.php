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
function clamp($current, $min = 0, $max = 999) {
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
/**
 * converts decimal numbers to roman numerals
 *
 * @param int $num
 * @return string
 */
function dec2roman($num) {
    static $ones = ['', 'i', 'ii', 'iii', 'iv', 'v', 'vi', 'vii', 'viii', 'ix'];
    static $tens = ['', 'x', 'xx', 'xxx', 'xl', 'l', 'lx', 'lxx', 'lxxx', 'xc'];
    static $hund = ['', 'c', 'cc', 'ccc', 'cd', 'd', 'dc', 'dcc', 'dccc', 'cm'];
    static $thou = ['', 'm', 'mm', 'mmm'];
    if (!is_numeric($num)) {
        throw new Exception('dec2roman() requires a numeric argument.');
    }
    if ($num > 4000 || $num < 0) {
        return '(out of range)';
    }
    $num = strrev((string) $num);
    $ret = '';
    switch (mb_strlen($num)) {
    case 4:
        $ret .= $thou[$num[3]];
    case 3:
        $ret .= $hund[$num[2]];
    case 2:
        $ret .= $tens[$num[1]];
    case 1:
        $ret .= $ones[$num[0]];
        default:break;
    }
    return $ret;
}
// Returns the least common multiple of two numbers.
// Use the greatest common divisor (GCD) formula and Math.abs() to determine the least common multiple. The GCD formula uses recursion.
function lcm($x, $y) {
    $_gcd = function ($x, $y) {
        return (!$y ? $x : $_gcd($y, $x % $y));
    };
    return abs($x * $y) / $_gcd($x, $y);
}
// lcm(12, 7); // 84
/*
Find the middle of the array, use Array.sort() to sort the values. Return the number at the midpoint if length is odd, otherwise the average of the two middle numbers.
const median = arr => {
const mid = Math.floor(arr.length / 2),
nums = [...arr].sort((a, b) => a - b);
return arr.length % 2 !== 0 ? nums[mid] : (nums[mid - 1] + nums[mid]) / 2;
};
median([5, 6, 50, 1, -5]); // 5
median([0, 10, -2, 7]); // 3.5
 */
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
function m2km($m) {return $m / 1000.0;}
function m2m($m) {return $m;}
function m2cm($m) {return $m * 100.0;}
function m2mm($m) {return $m * 1000.0;}
function m2ft($m) {return $m * 3.28084;}
//
function cm2km($cm) {return m2km(cm2m($cm));}
function cm2m($cm) {return m2m($cm / 100.0);}
function cm2cm($cm) {return m2cm(cm2m($cm));}
function cm2mm($cm) {return m2mm(cm2m($cm));}
function cm2ft($cm) {return m2ft(cm2m($cm));}
//
function mm2km($mm) {return m2km(mm2m($mm));}
function mm2m($mm) {return m2m($mm / 1000.0);}
function mm2cm($mm) {return m2cm(mm2m($mm));}
function mm2mm($mm) {return m2mm(mm2m($mm));}
function mm2ft($mm) {return m2ft(mm2m($mm));}
//----------------------------------------------------------------------------
//
//----------------------------------------------------------------------------
// the euclidean algorithm
function gcd($a, $b) {
    if ($b === 0) {
        return $a;
    } else {
        return gcd($b, ($a % $b));
    }
}
function media_aritmetica($val) {
    return array_sum($val) / count($val);
}
/*
test1:
media_ponderata( array(2,4,6,8), array(15,20,25,40) );
 */
function media_ponderata(array $a_val, array $a_ponder_perc) {
    if (count($a_val) != count($a_ponder_perc)) {
        die(__FUNCTION__ . ' called with wrong parameters.');
    }
    if (array_sum($a_ponder_perc) != 100) {
        die(__FUNCTION__ . ' needs $a_ponder_perc to sum up to 100.');
    }
    $result = 0;
    for ($i = 0; $i < count($a_val); $i++) {
        $result += $a_val[$i] * $a_ponder_perc / 100;
    }
    return $result;
}
/*
media ponderata, senza percentuali
Esame 1: voto 27 crediti 8
Esame 2: voto 21 crediti 12
Esame 3: voto 28 crediti 4
(27x8)+(21x12)+(28x4)/(8+12+4)=  media 24,16
 */
function media_ponderata_abs(array $a_val, array $a_crediti) {
}
//Returns the Pearson correlation coefficient for p1 and p2
function pearson_correlation($prefs, $p1, $p2) {
    // Get the list of mutually rated items
    $si = [];
    foreach ($prefs[$p1] as $item) {
        if (in_array($item, $prefs[$p2])) {
            $si[$item] = 1;
        }
    }
    // Find the number of elements
    $n = count($si);
    // if they are no ratings in common, return 0
    if ($n == 0) {
        return 0;
    }
    // Add up all the preferences
    foreach ($si as $it) {
        $sum1 += $prefs[$p1][$it];
    }
    foreach ($si as $it) {
        $sum2 += $prefs[$p2][$it];
    }
    // Sum up the squares
    foreach ($si as $it) {
        $sum1Sq += pow($prefs[$p1][$it], 2);
    }
    foreach ($si as $it) {
        $sum2Sq += pow($prefs[$p2][$it], 2);
    }
    // Sum up the products
    foreach ($si as $it) {
        $pSum = $prefs[$p1][$it] * $prefs[$p2][$it];
    }
    // Calculate Pearson score
    $num = $pSum - ($sum1 * $sum2 / $n);
    $den = sqrt(($sum1Sq - pow($sum1, 2) / n) * ($sum2Sq - pow($sum2, 2) / $n));
    if ($den == 0) {
        return 0;
    }
    $r = $num / $den;
    return $r;
}
function pearson_correlation_2(array $x, array $y) {
    if (count($x) !== count($y)) {return -1;}
    $x = array_values($x);
    $y = array_values($y);
    $xs = array_sum($x) / count($x);
    $ys = array_sum($y) / count($y);
    $a = 0;
    $bx = 0;
    $by = 0;
    for ($i = 0; $i < count($x); $i++) {
        $xr = $x[$i] - $xs;
        $yr = $y[$i] - $ys;
        $a += $xr * $yr;
        $bx += pow($xr, 2);
        $by += pow($yr, 2);
    }
    $b = sqrt($bx * $by);
    return $a / $b;
}
function rendimento($initial, $final) {
    return (($final - $initial) / $initial) * 100;
}
function rendimento_annuo($initial, $final, $spese, $giorni) {
    // 360 giorni, anno commerciale
    return (($final - $initial) / (($initial + $spese) * ($giorni / 360))) * 100;
}
// https://en.wikipedia.org/wiki/Percentile
// @see http://php.net/manual/en/function.stats-stat-percentile.php
function percentile($data, $percentile) {
    if (0 < $percentile && $percentile < 1) {
        $p = $percentile;
    } else if (1 < $percentile && $percentile <= 100) {
        $p = $percentile * .01;
    } else {
        return "";
    }
    $count = count($data);
    $allindex = ($count - 1) * $p;
    $intvalindex = intval($allindex);
    $floatval = $allindex - $intvalindex;
    sort($data);
    if (!is_float($floatval)) {
        $result = $data[$intvalindex];
    } else {
        if ($count > $intvalindex + 1) {
            $result = $floatval * ($data[$intvalindex + 1] - $data[$intvalindex]) + $data[$intvalindex];
        } else {
            $result = $data[$intvalindex];
        }

    }
    return $result;
}

 // according the Wikipedia Second varitant, which is the one used in Excel and NumPi(https://en.wikipedia.org/wiki/Percentile#Second_variant). 
function Percentile_2v($array, $percentile)
{
    $percentile = min(100, max(0, $percentile));
    $array = array_values($array);
    sort($array);
    $index = ($percentile / 100) * (count($array) - 1);
    $fractionPart = $index - floor($index);
    $intPart = floor($index);
    $percentile = $array[$intPart];
    $percentile += ($fractionPart > 0) ? $fractionPart * ($array[$intPart + 1] - $array[$intPart]) : 0;
    return $percentile;
}


//----------------------------------------------------------------------------
//
//----------------------------------------------------------------------------
// if colled directly, run the tests:
if (isset($_SERVER['argv']) && basename($_SERVER['argv'][0]) == basename(__FILE__)) {
    require_once __DIR__ . '/../Test.php';
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

    require_once __DIR__ . '/Test.php';
    for ($i = 0; $i < 10; $i++) {
        $c = base_convert_x($i);
        $j = base_convert_x($c, 62, 10);
        diag("$i => $c => $j\n");
        is($c, $j, "base_convert_x converting $i");
    }
}