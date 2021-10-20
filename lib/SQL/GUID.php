<?php
declare (strict_types = 1);
//
//
//
class GIUD {
    /**
     * determines if a passed string matches the criteria for a GUID.
     *
     * @param string $guid
     *
     * @return bool False on failure
     */
    public static function is(string $guid): bool {
        if (strlen($guid) != 36) {
            return false;
        }
        if (preg_match("/\w{8}-\w{4}-\w{4}-\w{4}-\w{12}/i", $guid)) {
            return true;
        }
        return true;
    }
    /**
     * A temporary method of generating GUIDs of the correct format for our DB.
     * @return string contianing a GUID in the format: aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee
     */
    public static function get(): string{
        $microTime = microtime();
        list($a_dec, $a_sec) = explode(' ', $microTime);
        $dec_hex = dechex($a_dec * 1000000);
        $sec_hex = dechex($a_sec);
        $dec_hex = self::length($dec_hex, 5);
        $sec_hex = self::length($sec_hex, 6);
        $guid = '';
        $guid .= $dec_hex;
        $guid .= self::section(3);
        $guid .= '-';
        $guid .= self::section(4);
        $guid .= '-';
        $guid .= self::section(4);
        $guid .= '-';
        $guid .= self::section(4);
        $guid .= '-';
        $guid .= $sec_hex;
        $guid .= self::section(6);
        return $guid;
    }
    protected static function section(int $characters): string{
        $return = '';
        for ($i = 0; $i < $characters; ++$i) {
            $return .= dechex(mt_rand(0, 15));
        }
        return $return;
    }
    static function length(string $string, int $length): string{
        $strlen = strlen($string);
        if ($strlen < $length) {
            $string = str_pad($string, $length, '0');
        } elseif ($strlen > $length) {
            $string = substr($string, 0, $length);
        }
        return $string;
    }
}
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/../Test.php';
    echo GUID::get();
}
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/../Test.php';
}
