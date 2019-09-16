<?php
declare (strict_types = 1);



/* RC4 symmetric cipher encryption/decryption
 * Copyright (c) 2006 by Ali Farhadi.
 * released under the terms of the Gnu Public License.
 * see the GPL for details.
 *
 * Email: ali[at]farhadi[dot]ir
 * Website: http://farhadi.ir/
 */

/**
 * Encrypt given plain text using the key with RC4 algorithm.
 * All parameters and return value are in binary format.
 *
 *
 * @param string key - secret key for encryption
 * @param string pt - plain text to be encrypted
 * @return string
 */
class CryptDriverRC4  {
    //
    public static function encrypt($key, $text) {
        $s = [];
        for ($i = 0; $i < 256; $i++) {
            $s[$i] = $i;
        }
        $j = 0;
        $x;
        for ($i = 0; $i < 256; $i++) {
            $j = ($j + $s[$i] + ord($key[$i % strlen($key)])) % 256;
            $x = $s[$i];
            $s[$i] = $s[$j];
            $s[$j] = $x;
        }
        $i = 0;
        $j = 0;
        $ct = '';
        $y;
        for ($y = 0; $y < strlen($text); $y++) {
            $i = ($i + 1) % 256;
            $j = ($j + $s[$i]) % 256;
            $x = $s[$i];
            $s[$i] = $s[$j];
            $s[$j] = $x;
            $ct .= $text[$y] ^ chr($s[($s[$i] + $s[$j]) % 256]);
        }
        return $ct;
    }

    // this function is symmetrical. If you pass the encoded string back into the function, you get the original string back.
    public static function decrypt($key, $text) {
        return self::encrypt($key, $text);
    }

}



// non usare mai questa funzione su un testo conosciuto in chiaro dall'utente,
// in quel caso è banale risalire al dizionario
class CryptDriverDict {
    //  Cifra testo con sostituzione a dizionario
    public static function crypt(string $str): string{
        self::init_dict();
        $result = '';
        foreach(str_split($str) as $c ) {
            $result .= self::char_encode($c);
        }
        return $result;
    }
    // ritorna alla stringa originaria
    public static function decrypt(string $str): string{
        self::init_dict();
        $result = '';
        foreach(str_split($str) as $c ) {
            $result .= self::char_decode($c);
        }
        return $result;
    }
    //----------------------------------------------------------------------------
    //  internals
    //----------------------------------------------------------------------------
    // chars = str_shuffle( DICT_KEYS )
    const DICT_KEYS = '0123456789ABCDEFGHIJKLMNQRSTUVWXYZabcdefghijklmnopqrstuvwxyz@,;.:-<>!?"()&^$%=+[]# ';
    const DICT_CHAR = 'Tk54;=BSLh27Roy.1KNd,0AVpeW8zFiZ%3Mx9ctGsIaU$n-E!fHXvqr@jCgDJYwQu): +(>"?<&#6l^mb[]';
    static $dict = [];
    static $reverse_dict = [];
    protected static function init_dict() {
        if (empty($dict)) {
            // TODO: DICT_CHAR dovrebbe essere sovrascritto per ogni progetto, in un file di configurazione,
            // in modo da non essere facilmente rintracciabile
            $enc_len = strlen(self::DICT_KEYS);
            for ($i = 0; $i < $enc_len; $i++) {
                $k = (string) self::DICT_KEYS[$i];
                $v = (string) self::DICT_CHAR[$i];
                self::$dict["$k"] = "$v";
            }
            // crea il dizionario inverso
            self::$reverse_dict = array_flip(self::$dict);
        }
    }
    protected static function char_encode(string $key):string {
        return array_key_exists($key, self::$dict) ? self::$dict[$key] : $key;
    }
    protected static function char_decode(string $char):string {
        return array_key_exists($char, self::$reverse_dict) ? self::$reverse_dict[$char] : $char;
    }
}


// if colled directly in CLI, run the tests:
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/Test.php';


    $r = CryptDriverDict::crypt('0');
    ok($r, 'T', $label = 'test 0');
    $r = CryptDriverDict::crypt('a');
    ok($r, 'M', $label = 'test 1');
    $r = CryptDriverDict::crypt('alabarda');
    ok($r, 'MnMxMXcM', $label = 'test 2');
    $r = CryptDriverDict::crypt('alabarda@navona.it');
    ok($r, 'MnMxMXcMJEM@!EMQaq', $label = 'test 3');
    $r = CryptDriverDict::crypt('');
    ok($r, '', $label = 'test 4');
    $r = CryptDriverDict::decrypt('MnMxMXcM');
    ok($r, 'alabarda', $label = 'decrypt alabarda');

    // cript and back
    $r = CryptDriverRC4::encrypt($K='12345678','alabarda');
    echo base_convert($hex=bin2hex($r), 16, $tobase=36 ) .PHP_EOL;

    // $r is binary
    $r1 = CryptDriverRC4::encrypt($K='12345678', $r );
    ok($r1, 'alabarda', $label = 'RC4 alabarda');
}