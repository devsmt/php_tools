<?php

interface ICryptDriver {

    function crypt();

    function decrypt();
}

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
 * @param string key - secret key for encryption
 * @param string pt - plain text to be encrypted
 * @return string
 */
class CryptDriverRC4 implements ICryptDriver {

    function crypt($key, $pt) {
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
        for ($y = 0; $y < strlen($pt); $y++) {
            $i = ($i + 1) % 256;
            $j = ($j + $s[$i]) % 256;
            $x = $s[$i];
            $s[$i] = $s[$j];
            $s[$j] = $x;
            $ct .= $pt[$y] ^ chr($s[($s[$i] + $s[$j]) % 256]);
        }
        return $ct;
    }

    /**
     * Decrypt given cipher text using the key with RC4 algorithm.
     * All parameters and return value are in binary format.
     *
     * @param string key - secret key for decryption
     * @param string ct - cipher text to be decrypted
     * @return string
     */
    function decrypt($key, $ct) {
        return RC4::crypt($key, $ct);
    }

}

class Crypt {

    static function docrypt() {
        $d = new CryptDriverRC4();
        return $d->crypt($k, $s);
    }

    static function decrypt() {
        $d = new CryptDriverRC4();
        return $d->decrypt($k, $s);
    }

}
