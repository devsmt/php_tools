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
    function grep(array $h, \Closure $_grep): array{
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
    // dato un Hash, trattiene solo le chiavi/valori che passino il test
    // se php recente php5.6+
    // if ( PHP_VERSION_ID >= 50600) { $get_defined_vars = array_filter($get_defined_vars, $_filter, ARRAY_FILTER_USE_BOTH); // php5.6+ }
    function filter(array $h_1, \Closure $_f) {
        $h_2 = [];
        foreach ($h_1 as $key => $val) {
            if ($_f($key, $val)) {
                $h_2[$key] = $val;
            }
        }
        return $h_2;
    }
    // $promo_qta = h_query($rec, ['promo', 0, 'scaglioni', 0, 'acquistati'], $def=0);
    function h_query(array $h, $a_query, $default) {
        $arg_1 = array_shift($a_query); //first arg
        if (array_key_exists($arg_1, $h)) {
            $h2 = $h[$arg_1];
            if (is_array($h2)) {
                return h_query($h2, $a_query, $default);
            } else {
                return $h[$arg_1]; // element found
            }
        } else {
            return $default;
        }
    }
    // primo elemento di un hash
    function h_first($h) {
        if (empty($h)) {
            return [];
        }
        // works only if array has alements
        $first_k = array_keys($h)[0];
        return $h[$first_k];
    }
    // map both keys and values
    function array_map_keys(array $a1, \Closure $f_k_mapper = null, \Closure $f_v_mapper = null) {
        $f_k_mapper = $f_k_mapper ? $f_k_mapper : function ($k, $v) {return $k;};
        $f_v_mapper = $f_v_mapper ? $f_v_mapper : function ($v, $k) {return $v;};
        $a2 = [];
        foreach ($a1 as $k => $v) {
            $a2[$f_k_mapper($k, $v)] = $f_v_mapper($v, $k);
        }
        return $a2;
    }
    // array_merge fa casino con le chiavi, se numeriche,ad esempio i codici articolo o altro risultato da query
    function h_merge() {
        $arg_list = func_get_args();
        $res = [];
        foreach ($arg_list as $arg) {
            foreach ($arg as $k => $v) {
                $res[$k] = $v;
            }
        }
        return $res;
    }
    // return and hash that has only the specified keys
    // h_select_keys( $promo_group, [
    //               "items_qta_tot" ,
    //               "promo_desc" ,
    //               "promo_id" ,
    //               "promo_target" ,
    //               "promo_txt"])
    function select_keys(array $h, array $a_keys) {
        $a2 = [];
        foreach ($a_keys as $key) {
            $a2[$key] = h_get($h, $key, null);
        }
        return $a2;
    }
}
// Array< Hash >
class RS {
/*
uso:
$a_cart_qta = reindex($a_cart_rows, 'article_id', function($rec){
return [
'article_id' => $rec['article_id'],
'qta'        => $rec['qta']
];
});
 */
    function array_reindex($a_recs, $idx_field, $rec_mapper = null) {
        $a_recs_idx = array();
        $rec_mapper = ($rec_mapper === null) ? (function ($r) {
            return $r;
        }) : $rec_mapper;
        $idx_field_c = $idx_field;
        foreach ($a_recs as $i => $rec) {
            $id = "";
            if (is_array($rec)) {
                if (is_string($idx_field)) {
                    $id = (string) h_get($rec, $idx_field, '');
                } else {
                    if (is_callable($idx_field)) {
                        $id = (string) call_user_func($idx_field_c, $rec);
                    }
                }
                if (!empty($rec_mapper)) {
                    $rec = call_user_func($rec_mapper, $rec);
                }
                $a_recs_idx["$id"] = $rec;
            } else {
                $msg = sprintf('Errore: array expected, found %s ', json_encode($rec));
                throw new \Exception($msg);
            }
        }
        return $a_recs_idx;
    }
    //-------------------------------------------------------------------

    // dato un array di dizionari Hash<any>[]  ritorna solo le chiavi indicate, mantenendo le chiavi nel dizionario
    // dato un RS ( array di dizionari Hash<any>[] )
    // ritorna solo le chiavi indicate, mantenendo le chiavi nel dizionario
    // return Array< Hash<String $key , mixed> > con una singola chiave o multipla per hash
    // per avvere Array<String> usa __::pluck()
    function pluck($a_RS, $key, array $opt = []) {
        $option = array_merge([
            'silent' => true,
        ], $opt);
        extract($option);
        if (is_string($key)) {
            return self::pluck_a($a_RS, $key, $silent);
            // return array_reduce($a_RS, function ($result, $rec) use ($key, $silent) {
            //     if (isset($rec[$key])) {
            //         $result[] = [$key => $rec[$key]];
            //     } elseif (!$silent) {
            //         $msg = sprintf('Errore missing key %s ', $key);
            //         throw new \Exception($msg);
            //     }
            //     return $result;
            // }, []);
        } elseif (is_array($key)) { // grep a group of keys
            // $return = [];
            // foreach ($a_RS as $rec) {
            //     $a_tmp = [];
            //     foreach ($key as $cur_key) {
            //         if (isset($rec[$cur_key])) {
            //             $a_tmp[$cur_key] = $rec[$cur_key];
            //         } elseif (!$silent) {
            //             $msg = sprintf('Errore missing key %s ', $key);
            //             throw new \Exception($msg);
            //         }
            //     }
            //     $return[] = $a_tmp;
            // }
            // return $return;
            return self::select_keys($a_RS, $a_keys, $silent);
        }
    }
    // Usage:  $ids = array_pluck('id', $users);
    // ritorna un array dei valori di una chiave
    // function _pluck($key, $input) {
    //     if (is_array($key) || !is_array($input)) {
    //         return [];
    //     }
    //     $array = [];
    //     foreach ($input as $v) {
    //         if (array_key_exists($key, $v)) {
    //             $array[] = $v[$key];
    //         }
    //     }
    //     return $array;
    // }
    // da un RS ritorna Array<string>
    function pluck_a($data, $key, $silent = true) {
        return array_reduce($data, function ($result, $array) use ($key) {
            isset($array[$key]) &&
            $result[] = $array[$key];
            return $result;
        }, []);
    }
    // di un rs, ritorna rs, le sole chiavi specificate
    function select_keys(array $a_RS, array $a_keys, $silent = true) {
        $return = [];
        foreach ($a_RS as $rec) {
            $a_tmp = [];
            foreach ($a_keys as $cur_key) {
                if (isset($rec[$cur_key])) {
                    $a_tmp[$cur_key] = $rec[$cur_key];
                } elseif (!$silent) {
                    $msg = sprintf('Errore missing key %s ', $key);
                    throw new \Exception($msg);
                }
            }
            $return[] = $a_tmp;
        }
        return $return;
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