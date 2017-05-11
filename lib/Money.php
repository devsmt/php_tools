<?php

//
// incorpora le operazioni tipiche da fare con la valuta
//

class Money {

    private $amount;

    const SCALE = 2;

    public function __construct($amount) {
        $this->amount = $amount;
    }

    public function __toString() {
        return $this->amount;
    }

    public function add(Money $another) {
        return new Money(bcadd($this->amount, $another->amount, self::SCALE));
    }

    public function multiply(float $factor) {
        // TODO: gestire $factor di tipo Money
        $factor = $factor;
        return new Money(bcmul($this->amount, $factor, self::SCALE));
    }

    public static function format($v) {
        //setlocale(LC_MONETARY, 'it_IT.utf8');
        $italian_format_number = number_format($number, 2, ',', '.');
        return $italian_format_number;
    }

    /*
// USO: bc("(sqrt(7 + $1^2) / 4 + $2) % 4 + 0.5", "3", "5");
static function bc() {
$functions = 'sqrt';
// list of | separated functions
// sqrt refer to bcsqrt etc.
// function must take exactly 1 argument

$argv = func_get_args();
$string = str_replace(' ', '', '('.$argv[0].')');
$string = preg_replace('/\$([0-9\.]+)/e', '$argv[$1]', $string);
while (preg_match('/(('.$functions.')?)\(([^\)\(]*)\)/', $string, $match)) {
while (
preg_match('/([0-9\.]+)(\^)([0-9\.]+)/', $match[3], $m) ||
preg_match('/([0-9\.]+)([\*\/\%])([0-9\.]+)/', $match[3], $m) ||
preg_match('/([0-9\.]+)([\+\-])([0-9\.]+)/', $match[3], $m)
) {
switch($m[2]) {
case '+': $result = bcadd($m[1], $m[3]); break;
case '-': $result = bcsub($m[1], $m[3]); break;
case '*': $result = bcmul($m[1], $m[3]); break;
case '/': $result = bcdiv($m[1], $m[3]); break;
case '%': $result = bcmod($m[1], $m[3]); break;
case '^': $result = bcpow($m[1], $m[3]); break;
}
$match[3] = str_replace($m[0], $result, $match[3]);
}
if (!empty($match[1]) && function_exists($func = 'bc'.$match[1]))  {
$match[3] = $func($match[3]);
}
$string = str_replace($match[0], $match[3], $string);
}
return $string;
}
 */
}
