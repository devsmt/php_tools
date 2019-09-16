<?php

// funzione:
final class H
{

    // ottieni una chiave di hash o un defualt
    // ritorna stringa o altro tipo
    // if $k is array
    // return the first of $keys found or default
    public static function get($h, $k, $def = '') {
        // se $h == null
        if (empty($h)) {
            return $def;
        }
        // boolean, double, integer, or string types are scalar. Array, object, and resource are not
        if (is_scalar($k) || is_null($k)) {
            return array_key_exists($k, $h) ? $h[$k] : $def;
        } elseif (is_array($k)) {
            // @see h_get_subkey
            foreach ($k as $key) {
                if (array_key_exists($key, $h)) {
                    return $h[$key];
                }
            }
            return $def;
        } else {
            $msg = sprintf('Errore %s ', 'unsopported key type ' . var_export54($k));
            throw new \Exception($msg);
        }
    }
    // ottieni una chiave di hash o un defualt
    function h_get_subkey(array $h, $k, $def = '') {
        if (array_key_exists($k, $h)) {
            return $h[$k];
        }
        // cerca una sottochiave
        if (strpos($k, '.') !== false) {
            foreach (explode('.', $k) as $segment) {
                if (is_array($h) && array_key_exists($h, $segment)) {
                    $h = $h[$segment];
                } else {
                    return $def;
                }
            }
            return $h;
        }
        // no match
        return $def;
    }
    //
    // case insensitive h_get
    //
    function h_iget( $h, $k, $def=false ) {
        foreach ($h as $key => $val ) {
            if (strtolower($k) == strtolower($key)) {
                return $val;
            }
        }
        return $def;
    }
}