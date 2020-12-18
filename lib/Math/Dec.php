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
/** @psalm-suppress ArgumentTypeCoercion  */
class Dec {
    // AS400 restituiesce stringhe valore '.3' o ',3' per indicare float 0.3
    public static function str_to_dec(string $val): string{
        $val = (string) $val;
        $first_c = substr($val, 0, 1);
        if (in_array($first_c, [',', '.'])) {
            $val = '0' . $val;
        }
        $val = str_replace($sub = ',', $re = '.', $val); // da , a . per conversione float
        return $val;
    }
    public static function str_is_dec(string $val): bool{
        $val = trim($val);
        if (empty($val)) {
            return false;
        }
        if (1 == preg_match('/^([0-9\.\,]*)$/i', $val)) {
            // digits and . or ,
            return true;
        }
        return false;
    }
    //
    /** @psalm-suppress ArgumentTypeCoercion  */
    public static function perc(string $v, string $perc, int $precision= BC_PRECISION): string{
        $v_dec = bcdiv($v, '100', BC_PRECISION);
        $v_perc = bcmul($v_dec, $perc, BC_PRECISION);
        return $v_perc;
    }
    // applica uno sconto $perc %
    /** @psalm-suppress ArgumentTypeCoercion  */
    public static function perc_sub(string $v, string $perc): string{
        $vp = self::perc($v, $perc, BC_PRECISION);
        $v2 = bcsub($v, $vp, BC_PRECISION);
        return $v2;
    }
    // coalesce dec: scarta tutti i nn numeric
    // ritorna 0 se nessuno è valido
    public static function coalesce(): string{
        $args = func_get_args();
        foreach ($args as $arg) {
            if (empty($arg)) {
                continue;
            }
            $arg = '' . $arg;
            if (str_is_dec($arg) && !self::is_zero($arg)) {
                return str_to_dec($arg);
            }
        }
        return DEC_ZERO;
    }
    // somma un array di numeri formato bc
    /** @psalm-suppress ArgumentTypeCoercion  */
    public static function array_sum(array $a_num): string{
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
    //
    /** @psalm-suppress ArgumentTypeCoercion  */
    public static function array_avg(array $a_num): string{
        $sum = self::array_sum($a_num);
        $num = count($a_num);
        $avg = bcdiv($sum, (string) $num);
        if( empty($avg) ){
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
    public static function div(string $a, string $b): string {
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
    public static function is_zero(string $a, int $p = BC_PRECISION): bool {
        return bccomp($a, DEC_ZERO, $p) === 0;
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
function str_to_dec(string $val):string {
    return Dec::str_to_dec($val);
}
function str_is_dec(string $val):bool {
    return Dec::str_is_dec($val);
}
function perc_bc(string $v, string $perc):string {
    return Dec::perc($v, $perc);
}
// applica uno sconto $perc %
function perc_sub_bc(string $v, string $perc):string {
    return Dec::perc_sub($v, $perc);
}
// coalesce dec: scarta tutti i nn numeric
// ritorna 0 se nessuno è valido
function coalesce_dec():string {
    return (string) call_user_func_array([$class_name = 'Dec', $method_name = 'coalesce'], $args = func_get_args());
}
// somma un array di numeri formato bc
function array_sum_dec(array $a_num):string {
    return Dec::array_sum($a_num);
}

/*
function s2f(string $input): float {
return floatval(preg_replace("/[^-0-9\.]/", '', $input));
}
 */
// merge back:
// meld  /data/bin_priv/lib/Dec.php  /home/taz/Dropbox/projects/gh_php_tools/lib/Math/Dec.php
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    ok(Dec::str_is_dec($val = ''), false, 'empty');
    ok(Dec::coalesce('', 0, 0.0, '0.00', 1), '1', 'coalesce_dec()');
}
