<?php

// astrazioni su hash o array associativi
final class H {

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
            return self::get_subkey($h, $k);
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
    function get_subkey(array $h, $k, $def = '') {
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
    function iget($h, $k, $def = false) {
        foreach ($h as $key => $val) {
            if (strtolower($k) == strtolower($key)) {
                return $val;
            }
        }
        return $def;
    }

    // map both keys and values
    function map_keys(array $a1, \Closure $f_k_mapper = null, \Closure $f_v_mapper = null) {
        $f_k_mapper = $f_k_mapper ?? function ($k, $v) {return $k;};
        $f_v_mapper = $f_v_mapper ?? function ($v, $k) {return $v;};
        $a2 = [];
        foreach ($a1 as $k => $v) {
            $a2[$f_k_mapper($k, $v)] = $f_v_mapper($v, $k);
        }
        return $a2;
    }

    // trattiene solo chiavi e valori che passino la funzione di grep
    function h_grep(array $h, \Closure $_grep): array{
        $h2 = [];
        if (empty($h)) {
            return [];
        }
        foreach ($h as $key => $val) {
            $ok = $_grep($key, $val);
            if ($ok) {
                $h2[$key] = $val;
            }
        }
        return $h2;
    }
    //-------------------------------------------------------------------
    // Usage:  $ids = array_pluck('id', $users);
    // ritorna un array dei valori di una chiave
    function pluck($key, $input) {
        if (is_array($key) || !is_array($input)) {
            return [];
        }
        $array = [];
        foreach ($input as $v) {
            if (array_key_exists($key, $v)) {
                $array[] = $v[$key];
            }
        }
        return $array;
    }
    // da un RS ritorna Array<string>
    function array_pluck($key, $data) {
        return array_reduce($data, function ($result, $array) use ($key) {
            isset($array[$key]) &&
            $result[] = $array[$key];
            return $result;
        }, []);
    }
    // dato un array di dizionari Hash<any>[]  ritorna solo le chiavi indicate, mantenendo le chiavi nel dizionario
    function h_pluck($a_RS, $key) {
        if (is_string($key)) {
            return array_reduce($a_RS, function ($result, $rec) use ($key) {
                if (isset($rec[$key])) {
                    $result[] = [$key => $rec[$key]];
                }
                return $result;
            }, []);
        } elseif (is_array($key)) {
            $return = [];
            foreach ($a_RS as $rec) {
                $a_tmp = [];
                foreach ($key as $cur_key) {
                    if (isset($rec[$cur_key])) {
                        $a_tmp[$cur_key] = $rec[$cur_key];
                    }
                }
                $return[] = $a_tmp;
            }
            return $return;
        }
    }
    // ritorna un array dei valori di una chiave
    function getKeyValues($key, $input) {
        return self::pluck($key, $input);
    }

}
// if colled directly in CLI, run the tests:
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    include __DIR__ . '/Test.php';
    //
    $a = ['a' => 0, 'b' => 1, 'c' => 2];
    ok(H::get($a, 'a'), 0, 'get a key');
    //
    $a = ['a' => 0, 'b' => 1, 'c' => 2];
    ok(H::get($a, 'unexisting', $_def = 'null'), 'null', 'get a default for a key');
}