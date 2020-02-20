<?php
declare(strict_types=1);



class EAN13 {
    // calcola check di un numero
    public static function get_check_digit(string $code):int {
        // code must be even
        if (strlen($code) % 2 !== 0) {
            $msg = sprintf('Errore: code(%s) must be even, len:%s found ', $code,  strlen($code) );
            throw new \Exception($msg); // exceptions_
        }
        $code = str_pad($code, 12, "0", STR_PAD_LEFT);
        // get the weighted sum
        $sum = self::get_sum($code);
        // 5. The check character is the smallest number which, when added to $sum, produces a multiple of 10.
        $ean_chk = self::get_complement_multiple_of_10($sum);
        return $ean_chk;
    }
    //
    // 5. The check character is the smallest number which, when added to $sum, produces a multiple of 10.
    //
    public static function get_complement_multiple_of_10(int $sum):int {
        $next_ten = (int) (ceil($sum / 10)) * 10; // 53 => 6 => 60
        if( $next_ten == 0 )
            return 10;
        else
        $ean_chk = $next_ten - $sum;
        // alternative alg ok
        // $ean_chk = (10 - ($sum % 10));// nn funziona per multipli di dieci
        return $ean_chk;
    }

    // calcola somma moltiplicata
    // 1. Add the values of the digits in the even-numbered positions: 2, 4, 6, etc.
    // 2. Multiply this result by 3.
    // 3. Add the values of the digits in the odd-numbered positions: 1, 3, 5, etc.
    // 4. Sum the results of steps 2 and 3.
    // 5. The check character is the smallest number which, when added to $sum,  produces a multiple of 10.
    public static function get_sum( string $code):int {
        $a_digits = str_split($code);

        $_is_even = function (int $i): bool {
            $j = $i + 1; // i è zero based
            $is_even = ( ($j % 2) == 0 );
            return $is_even;
        };
        $even_sum = 0;
        $odd_sum = 0;
        for ($i = 0; $i < count($a_digits); $i++) {
            $is_even = $_is_even($i);
            if ($is_even) {
                $even_sum += $a_digits[$i];
            } else {
                $odd_sum += $a_digits[$i];
            }
        }
        /* alternative alg
        $a_digits_even =  (array_filter($a_digits, function ($i) use($_is_even) {
        return $_is_even($i);// false will be skipped
        }, ARRAY_FILTER_USE_KEY));
        $a_digits_odd =  (array_filter($a_digits, function ($i) use($_is_even) {
        return !$_is_even($i);// false will be skipped
        }, ARRAY_FILTER_USE_KEY));
        $even_sum = array_sum($a_digits_even);
        $odd_sum = array_sum($a_digits_odd);
        */
        $sum = ($even_sum * 3) + $odd_sum;
        return $sum;
    }

    // Test validity of check digit
    function validate(string $ean_code):bool {
        if (!preg_match("/^[0-9]{13}$/", $ean_code)) {
            return [false, "The mentioned EAN13 code does not have 13 numeric characters"];
        }
        $code = substr($ean_code, 0, 12);
        // alternative to check validity:
        //  return  ($ean_code[12] + $ean_chk) % 10 == 0;
        $ean_chk = self::get_check_digit($code);
        if ($ean_chk == $ean_code[12]) {
            return [true, 'Valid EAN13'];
        } else {
            return [false, sprintf('Invalid check digit %s<>%s', $ean_chk, $ean_code[12])];
        }
    }

}






// function is($res, $label) {return ok($res, $expected = true, $label);}

if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/../Test.php';



    ok($sum = EAN13::get_sum('000000000000'), 0, 'sum 0');
    ok($sum = EAN13::get_sum('0101'), 6, 'sum 6');
    ok($sum = EAN13::get_sum('1111'), 8, 'sum 8');

    ok(EAN13::get_complement_multiple_of_10(0),  10, 'complement 10 ');
    ok(EAN13::get_complement_multiple_of_10(10),  0, 'complement 10 b');
    ok(EAN13::get_complement_multiple_of_10(20),  0, 'complement 10 c');
    ok(EAN13::get_complement_multiple_of_10(53),  7, 'complement 7 ');
    ok(EAN13::get_complement_multiple_of_10(62),  8, 'complement 8 ');
    ok(EAN13::get_complement_multiple_of_10(91),  9, 'complement 9 ');
    ok(EAN13::get_complement_multiple_of_10(4),   6, 'complement 6 ');
    ok(EAN13::get_complement_multiple_of_10(115), 5, 'complement 5 ');

    ok($ean_chk = EAN13::get_check_digit('200123456789'), 3, 'ean_chk 3');
    ok($ean_chk = EAN13::get_check_digit('236789234567'), 0, 'ean_chk 0');
    ok($ean_chk = EAN13::get_check_digit('2123456789234567'), 6, 'ean_chk long');

    try {
        $ean_chk = null;
        $exp = null;
        $msg='ean_chk exchept len';
        ok($ean_chk = EAN13::get_check_digit('0'), null , 'should not result here' );
    } catch (\Exception $e) { // Throwable $e in php7
        // should rise exception
        ok($ean_chk, $exp, $msg);
    }

}
