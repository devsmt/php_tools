<?php
declare (strict_types = 1);
//----------------------------------------------------------------------------
//  Decimal
//----------------------------------------------------------------------------
if (!defined('BC_PRECISION')) {
    define('BC_PRECISION', 6);
}
if (!defined('DEC_ZERO')) {
    define('DEC_ZERO', '0.00');
}
bcscale(BC_PRECISION); // setta il default scale, va settato prima delle chiamate
//
/** @psalm-suppress ArgumentTypeCoercion
 * sia 0,10 che 0.10 sono decimal perchè facilemnte castabili a bc str, cioè 0.10
 */
class Dec {
    /**
     * @param float|double|int|string  $val
     * dec::val($any) tenta di convertire in dec
     * str_to_dec con interfaccia più generica
     */
    public static function val($val): string {
        switch (gettype($val)) {
        case 'string':
            return self::str_to_dec($val);
            break;
        default:
            // boolean
            // integer
            // double
            // array
            // object
            // NULL
            // resource
            return strval($val);
            break;
        }
    }
    /*
     * AS400 restituiesce stringhe valore '.3' o ',3' per indicare float 0.3
     * es. -2.000,00 => 2000.00
     */
    public static function str_to_dec(string $val): string{
        $val = trim($val);
        $first_c = substr($val, 0, 1);
        if (in_array($first_c, [',', '.'])) {
            $val = '0' . $val;
        }
        // se è nell aforma +1.000,00
        if ('+' === $first_c) {
            $val = str_replace($sub = '+', $re = '', $val);
        }
        if (self::str_is_bc($val)) {
            return $val;
        }
        $val = str_replace($sub = '.', $re = '', $val);
        $val = str_replace($sub = ',', $re = '.', $val); // da , a . per conversione float
        return $val;
    }
    /**
     * @param int|string|float|double $val
     * str_is_dec con interfaccia più generica
     */
    public static function is_dec($val): bool {
        switch (gettype($val)) {
        case 'string':
            return self::str_is_dec($val);
            break;
        case 'integer':
        case 'double':
            return true;
            break;
        default:
            return false;
            break;
        }
    }
    // dec fmt è qualunque formato stringa facilmente castabile a decimal
    public static function str_is_dec(string $val): bool{
        $val = trim($val);
        if ('' == $val) {
            return false;
        }
        // digits and . or , +-, spazi
        $is_reg = 1 == preg_match('/^([\-\+]?)([0-9\.\,\s]*)$/i', $val);
        if ($is_reg) {
            return true;
        } elseif ($is_reg === false) {
            // regex is bad
            if (preg_last_error() !== PREG_NO_ERROR) {echo preg_last_error_msg();}
        } else {
            return false;
        }
        return false;
    }
    // bc format è esattamente solo il formato bc 0.10, no "," no ' '
    public static function str_is_bc(string $val): bool{
        $val = trim($val);
        // digits and . or ,
        $is_reg = 1 == preg_match('/^(\-?)([0-9\.]*)$/i', $val);
        if ($is_reg) {
            return true;
        } elseif ($is_reg === false) {
            // regex is bad
            if (preg_last_error() !== PREG_NO_ERROR) {echo preg_last_error_msg();}
        } else {
            return false;
        }
        return false;
    }
    /** @param string|float|double|int $val */
    public static function fmt($val, int $d = 2): string{
        $f_val = floatval($val);
        return number_format($f_val, $d, '.', '');
    }
    // alias
    public static function round($val, int $d = 2): string {
        return self::fmt($val, $d);
    }
    /**
    calcola $perc % di $v
    @psalm-suppress ArgumentTypeCoercion
     */
    public static function perc(string $v, string $perc, int $precision = BC_PRECISION): string{
        $v_dec = bcdiv($v, '100', BC_PRECISION);
        $v_perc = bcmul($v_dec, $perc, BC_PRECISION);
        return $v_perc;
    }
    /** @psalm-suppress ArgumentTypeCoercion
     * applica uno sconto $perc %
     */
    public static function perc_sub(string $v, string $perc): string{
        $vp = self::perc($v, $perc, BC_PRECISION);
        $v2 = bcsub($v, $vp, BC_PRECISION);
        return $v2;
    }
    /* dati due valori, ritorna la perc che rappresenta il secondo del primo
     */
    public static function perc_of(string $totale, string $parziale, int $precision = BC_PRECISION): string{
        if( self::is_zero($parziale) ){
            return DEC_ZERO;
        }
        $x = bcdiv($totale, $parziale);
        if (self::empty($x)) {
            return DEC_ZERO;
        } else {
            $x = strval($x);
            $x = bcdiv('100', (string) $x);
            $x = strval($x);
            return $x;
        }
    }
    // coalesce dec: scarta tutti i nn numeric
    // ritorna 0 se nessuno è valido
    public static function coalesce(): string{
        $args = func_get_args();
        foreach ($args as $arg) {
            if (empty($arg)) {
                continue;
            }
            $arg = strval($arg);
            if (self::str_is_dec($arg) && !self::is_zero($arg)) {
                return str_to_dec($arg);
            }
        }
        return DEC_ZERO;
    }
    /**
     * @psalm-suppress ArgumentTypeCoercion
     * somma un array di numeri formato bc
     */
    public static function array_sum(array $a_num): string{
        $final_v = array_reduce($a_num, function (string $carry_v, string $cur_v): string {
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
    /** @psalm-suppress ArgumentTypeCoercion  */
    public static function array_avg(array $a_num): string {
        if (empty($a_num)) {
            return DEC_ZERO;
        }
        $sum = self::array_sum($a_num);
        $num = count($a_num);
        $avg = bcdiv($sum, (string) $num);
        if (empty($avg)) {
            return '';
        } else {
            return $avg;
        }
    }
    //----------------------------------------------------------------------------
    //  wrapped operations
    //----------------------------------------------------------------------------
    /** @psalm-suppress ArgumentTypeCoercion  */
    public static function add(string $a, string $b): string {
        return bcadd(self::str_to_dec($a), self::str_to_dec($b));
    }
    /** @psalm-suppress ArgumentTypeCoercion  */
    public static function sub(string $a, string $b): string {
        return bcsub(self::str_to_dec($a), self::str_to_dec($b));
    }
    /** @psalm-suppress ArgumentTypeCoercion  */
    public static function mul(string $a, string $b): string {
        return bcmul(self::str_to_dec($a), self::str_to_dec($b));
    }
    /** @psalm-suppress ArgumentTypeCoercion  */
    public static function div(string $a, string $b): string{
        $d = bcdiv(self::str_to_dec($a), self::str_to_dec($b));
        return empty($d) ? '' : $d;
    }
    // TODO: bcmod bcpow bcsqrt
    //----------------------------------------------------------------------------
    // comparing
    //----------------------------------------------------------------------------
    //
    /** @psalm-suppress ArgumentTypeCoercion  */
    public static function is_equal(string $a, string $b): bool {
        // compare ==
        // Returns 0 if the two operands are equal, 1 if the left_operand is larger than the right_operand, -1 otherwise.
        // echo bccomp('1.00001', '1', 3); // 0, EQUAL
        // echo bccomp('1.00001', '1', 5); // 1, first operand is bigger
        return bccomp($a, $b) === 0;
    }
    /** @psalm-suppress ArgumentTypeCoercion  */
    public static function is_zero(string $num, int $p = BC_PRECISION): bool {
        if (self::is_dec($num)) {
            if (!self::str_is_bc($num)) {
                $num = self::val($num);
            }
            return bccomp($num, DEC_ZERO, $p) === 0;
        } else {
            // not parsable
            return true;
        }
    }
    /** @param string|int|float|double|null  $num */
    public static function empty($num, int $p = BC_PRECISION): bool {
        switch (gettype($num)) {
        case 'string':
            return empty($num) || self::is_zero($num);
            break;
        case 'integer':
        case 'double':
            return $num == 0;
            break;
        default:
            // boolean
            // array
            // object
            // NULL
            // resource
            return true; // like it is_a not processable
            break;
        }
    }
    /** @psalm-suppress ArgumentTypeCoercion  */
    public static function is_greater(string $a, string $b, int $p = BC_PRECISION): bool {
        return bccomp($a, $b, $p) === 1;
    }
    /** @psalm-suppress ArgumentTypeCoercion  */
    public static function is_smaller(string $a, string $b, int $p = BC_PRECISION): bool {
        return bccomp($a, $b, $p) === -1;
    }
}
//----------------------------------------------------------------------------
//  old interface
//----------------------------------------------------------------------------
// AS400 restituiesce stringhe valore '.3' o ',3' per indicare float 0.3
function str_to_dec(string $val): string {
    return Dec::str_to_dec($val);
}
function str_is_dec(string $val): bool {
    return Dec::str_is_dec($val);
}
function perc_bc(string $v, string $perc): string {
    return Dec::perc($v, $perc);
}
// applica uno sconto $perc %
function perc_sub_bc(string $v, string $perc): string {
    return Dec::perc_sub($v, $perc);
}
// coalesce dec: scarta tutti i nn numeric
// ritorna 0 se nessuno è valido
function coalesce_dec(): string {
    return (string) call_user_func_array([$class_name = 'Dec', $method_name = 'coalesce'], $args = func_get_args());
}
// somma un array di numeri formato bc
function array_sum_dec(array $a_num): string {
    return Dec::array_sum($a_num);
}
//
function s2f(string $input): float{
    $s_clean = preg_replace('/[^-0-9\.]/', '', $input);
    return floatval($s_clean);
}
// from hex number to float
function hex2f(string $hex): string {
    return (unpack("f", pack('H*', $hex))[1]);
}
/** @param int|string|float|double $val */
function is_bc($val): bool {
    return Dec::is_dec($val);
}
// -2.000,00 => 2000.00
function str2bc(string $val): string {
    return Dec::str_to_dec($val);
}
// merge back:
// meld  /data/bin_priv/lib/Dec.php  /home/taz/Dropbox/projects/gh_php_tools/lib/Math/Dec.php
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/Common.php';
    //
    ok(Dec::str_is_bc($val = '0.00'), true, 'str_is_bc ' . $val);
    ok(Dec::str_is_bc($val = '0'), true, 'str_is_bc ' . $val);
    ok(Dec::str_is_bc($val = '2.000,00'), false, 'str_is_bc ' . $val); // non è esattamente in fmt bc
    //--------
    ok(Dec::str_is_dec($val = ''), false, 'str_is_dec ' . $val);
    ok(Dec::str_is_dec($val = '0.00'), true, 'str_is_dec ' . $val);
    ok(Dec::str_is_dec($val = '0'), true, 'str_is_dec ' . $val);
    //
    ok(Dec::str_is_dec($val = '0.10'), true, 'str_is_dec ' . $val);
    ok(Dec::str_is_dec($val = '0,10'), true, 'str_is_dec ' . $val);
    ok(Dec::str_is_dec($val = '+2.000,10'), true, 'str_is_dec ' . $val);
    //--------
    ok(Dec::str_to_dec($val = '0,10'), '0.10', 'str_2_dec ' . $val);
    ok(Dec::str_to_dec($val = '.10'), '0.10', 'str_2_dec ' . $val);
    ok(Dec::str_to_dec($val = '-1,10'), '-1.10', 'str_2_dec ' . $val);
    ok(Dec::str_to_dec($val = '-2.000,00'), '-2000.00', 'str_2_dec ' . $val);
    ok(Dec::str_to_dec($val = '+2.000,00'), '2000.00', 'str_2_dec ' . $val);
    //--------
    ok(Dec::is_zero($val = '0,00'), true, 'is_zero ' . $val);
    ok(Dec::is_zero($val = '1.000,00'), false, 'is_zero ' . $val);
    //--------
    ok(Dec::empty($val = ''), true, 'empty ' . $val . ' ' . gettype($val));
    ok(Dec::empty($val = null), true, 'empty ' . $val . ' ' . gettype($val));
    ok(Dec::empty($val = 0), true, 'empty ' . $val . ' ' . gettype($val));
    ok(Dec::empty($val = 0.0), true, 'empty ' . $val . ' ' . gettype($val));
    ok(Dec::empty($val = '0.00'), true, 'empty ' . $val . ' ' . gettype($val));
    ok(Dec::empty($val = '1.000,00'), false, 'empty ' . $val . ' ' . gettype($val));
    ok(Dec::empty($val = 1), false, 'empty ' . $val . ' ' . gettype($val));
    ok(Dec::empty($val = '-1'), false, 'empty ' . $val . ' ' . gettype($val));

    //--------
    ok(Dec::coalesce('', 0, 0.0, '0.00', 1), '1', 'coalesce_dec()');
}
