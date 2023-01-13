<?php
/**
 * optional: ottieni una chiave di hash o un defualt
 * ritorna stringa o altro tipo
 * if $k is array
 * return the first of $keys found or default
 * TODO: def potrebbe essere una lambda per decidere che fare con un valore mancante function($h,$k,$def){}
 * @return mixed
 * @param string|int $k
 * @param mixed $def
 * @param array|object $h
 *
 * @psalm-suppress RedundantConditionGivenDocblockType
 * @psalm-suppress DocblockTypeContradiction
ok( h_get(['a'=>1],'a','def'), 1, 'test 1');
ok( h_get(['a'=>1],'x','def'), 'def, 'test 1');
// obj
class test_obj { public a = 'aa'; }
ok( h_get( new test_obj(),'a','def'), 'def', 'test 1');
// subkeys
ok( h_get(['a'=>['b'=>'ab']],'a.b','def'), 'ab, 'test a.b');
// nuls & empty
ok( h_get([],'a','def'), 'def', 'test 1');
ok( h_get(['a'=>1],'','def'), '', 'test 1');
ok( h_get(['a'=>1],false,'def'), 1, 'test 1');
ok( h_get(['a'=>1],null,'def'), 1, 'test 1');
 */
function h_get($h, $k, $def = '') {
    if (empty($h)) {
        return $def;
    }
    if (is_object($h)) {
        // TODO: subkey if (strpos($k, '.') !== false) {}
        return (isset($k->$k)) ? $k->$k : $def;
    } elseif (is_array($h)) {
        if (is_null($k) || $k === '') {
            return $def;
            // boolean, double, integer, or string types are scalar. Array, object, and resource are not
        } elseif (is_numeric($k)) {
            return array_key_exists($k, $h) ? $h[$k] : $def;
        } elseif (is_string($k)) {
            // subkey 'kk.jjj'
            // strpos($k, '.') !== false
            // 1 === preg_match('/^(\w+).(\w+)$/i', $k, $m)
            if ( strpos($k, '.') !== false ) {
                foreach (explode('.', $k) as $segment) {
                    if (is_array($h) && array_key_exists($segment, $h)) {
                        $h = $h[$segment];
                    } else {
                        return $def;
                    }
                }
                return $h;
            } else {
                return array_key_exists($k, $h) ? $h[$k] : $def;
            }
        } elseif (is_array($k)) {
            $h_tmp = $h;
            foreach ($k as $key) {
                if (array_key_exists($key, $h)) {
                    $h_tmp = $h[$key];
                } else {
                }
            }
            return $h_tmp;
        } else {
            $msg = sprintf('Errore %s ', 'unsopported key type ' . gettype($k));
            throw new \Exception($msg);
        }
    } else {
        $msg = sprintf('Errore %s ', 'unsopported source type ' . gettype($h));
        throw new \Exception($msg);
    }
}

/**
 * @param mixed $def
 * @return mixed
 */
// ottieni una chiave di hash o un defualt
function h_get_subkey(array $h, string $k, $def = '') {
    if (array_key_exists($k, $h)) {
        return $h[$k];
    }
    // cerca una sottochiave
    if (strpos($k, '.') !== false) {
        foreach (explode('.', $k) as $segment) {
            if (is_array($h) && array_key_exists($segment, $h)) {
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
/**
 * @param mixed $k
 * @param mixed $def
 * @return mixed
 */
function h_iget(array $h, $k, $def = false) {
    foreach ($h as $key => $val) {
        if (strtolower($k) == strtolower($key)) {
            return $val;
        }
    }
    return $def;
}
// array map per array associativi, mantiene invariata la chiave
// $_mapper = function ($k, $v) { return $v; }
function h_map(array $h, callable $_mapper): array{
    $a_m = array_map(
        $_mapper
        , $a_keys = array_keys($h), array_values($h));
    // $a_m è un array, non un hash
    // $h2 è hash
    $h2 = array_combine($a_keys, $a_m);
    return $h2;
}
// map both keys and values
function h_map_keys(array $a1, callable $f_k_mapper = null, callable $f_v_mapper = null): array{
    $f_k_mapper = $f_k_mapper ? $f_k_mapper : function ($k, $v) {return $k;};
    $f_v_mapper = $f_v_mapper ? $f_v_mapper : function ($v, $k) {return $v;};
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
// dato un Hash, trattiene solo le chiavi/valori che passino il test
// keep elements where predicate indicates so, by returning true
// se php recente php5.6+
// if ( PHP_VERSION_ID >= 50600) { $get_defined_vars = array_filter($get_defined_vars, $_filter, ARRAY_FILTER_USE_BOTH); // php5.6+ }
function h_filter(array $h, callable $_f): array{
    $new = [];
    foreach ($h as $k => $v) {
        if ($_f($k, $v)) {
            $new[$k] = $v;
        } else {
            // skip it
        }
    }
    return $new;
}
/**
 * query the array for subkeys
 * $promo_qta = h_query($rec, ['promo', 0, 'scaglioni', 0, 'acquistati'], $def=0);
 * @param mixed $default
 * @return mixed
 */
function h_query(array $h, array $a_query, $default) {
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
/**
 * primi elementi di un hash, ritorna le chiavi, cosa che non fa array_first()
 * @return array
 */
function h_first(array $h, int $n = 1): array{
    if (empty($h)) {
        return [];
    }
    $h2 = [];
    foreach ($h as $key => $val) {
        if (count($h2) < $n) {
            $h2[$key] = $val;
        } else {
            break;
        }
    }
    return $h2;
}
// array_merge fa casino con le chiavi, se numeriche,ad esempio i codici articolo o altro risultato da query
function h_merge(): array{
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
/** @return mixed */
function h_select_keys(array $h, array $a_keys) {
    $a2 = [];
    foreach ($a_keys as $key) {
        $a2[$key] = h_get($h, $key, null);
    }
    return $a2;
}
// rewrite the keys of the hash
// h_rewrite_keys($h,['a'=>'b']);// will rewrite key 'a' to 'b'
function h_rewrite_keys(array $h, array $h_key_to_key) {
    $h2 = $h;
    foreach ($h_key_to_key as $key_old => $key_new) {
        if (isset($h2[$key_old])) {
            $val = $h2[$key_old];
            unset($h2[$key_old]);
            $h2[$key_new] = $val;
        }
    }
    return $h2;
}
// Get a value from the array, and remove it.
/**
 * @param mixed $default
 * @return mixed
 */
function h_pull(array &$array, string $key, $default = null) {
    $value = h_get($array, $key, $default);
    unset($array[$key]);
    return $value;
}

// delete elements where predicate indicates so, by returning true
function h_reject(array $h, callable $_f): array{
    $new = [];
    foreach ($h as $k => $v) {
        if ($_f($k, $v)) {
            // skip it
        } else {
            $new[$k] = $v;
        }
    }
    return $new;
}

/**
 * Return array with only the keys in $keys
 *
 * @param array $array The source
 * @param mixed $keys Keys of $array to return
 * @return array
 */
function h_keys_pick(array $array, array $keys): array{
    return array_intersect_key($array, array_flip($keys));
}

//
// comparazione di array associativi
function h_equal($a, $b) {
    $rksort = function ($a) {
        if (!is_array($a)) {
            return $a;
        }
        foreach (array_keys($a) as $key) {
            $a[$key] = ksort($a[$key]);
        }
        // SORT_STRING seems required, as otherwise
        // numeric indices (e.g. "0") aren't sorted.
        ksort($a, SORT_STRING);
        return $a;
    };
    return json_encode($rksort($a)) === json_encode($rksort($b));
}
//
function array_is_h(array $array) {
    return count(array_filter(array_keys($array), 'is_string')) > 0;
}

// dato un RS ( array di dizionari Hash<any>[] )
// ritorna solo le chiavi indicate, mantenendo le chiavi nel dizionario
// return Array< Hash<String $key , mixed> > con una singola chiave o multipla per hash
// per avvere Array<String> usa __::pluck()
/**
 * @param list< array<array-key, mixed> > $a_RS
 * @return list< array<array-key, mixed> >
 */
function h_pluck(array $a_RS, string $key, array $opt = []): array{
    $option = array_merge([
        'silent' => true,
    ], $opt);
    extract($option);
    if (is_string($key)) {
        return array_reduce($a_RS, function ($result, $rec) use ($key, $silent) {
            if (isset($rec[$key])) {
                $result[] = [$key => $rec[$key]];
            } elseif (!$silent) {
                $msg = sprintf('Errore missing key %s ', $key);
                throw new \Exception($msg);
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
                } elseif (!$silent) {
                    $msg = sprintf('Errore missing key cur_key:%s key:%s', $cur_key, json_encode($key));
                    throw new \Exception($msg);
                }
            }
            $return[] = $a_tmp;
        }
        return $return;
    }
    return [];
}

// $h_sorted = h_sort_by( $rs, function( $rec ) { return $rec['score']; });
// come array_sort_by ma mantiene l'associatività delle chiavi
function h_sort_by(array $rs, callable $_fn, string $ord = 'ASC'): array{
    $ord = strtolower($ord);
    $s_rs = $rs;
    uasort($s_rs, function (array $a, array $b) use ($_fn, $ord): int {
        if ($_fn($a) == $_fn($b)) {
            return 0;
        }
        if ($ord === 'asc') {
            return ($_fn($a) < $_fn($b)) ? -1 : 1; //ASC
        } elseif ($ord === 'desc') {
            return ($_fn($a) < $_fn($b)) ? 1 : -1; //DESC
        } else {
            die(implode('/', [__FUNCTION__, __METHOD__, __LINE__]) . ' > error ' . $ord);
        }
    });
    return $s_rs;
}

/** ricerca tra le key con "*"
 * @return array{0: bool, 2: array<mixed>}
 */
function h_key_like(array $db, string $_2): array{
    $ok = false;
    $a_keys_like = [];
    foreach ($db as $key => $val) {
        if (str_like($_2, $key)) {
            $a_keys_like[$key] = $val;
        }
    }
    return [$ok = (count($a_keys_like) > 0), $a_keys_like];
}

// polifill 7.3
if (!function_exists('array_key_first')) {
    function array_key_first(array $array) {foreach ($array as $key => $value) {return $key;}}
}
if (!function_exists('array_key_last')) {
    function array_key_last(array $array): string{
        $key = '';
        foreach ($array as $key => $value) {
        }
        return $key;
    }
}

//
// if colled directly in CLI, run the tests:
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/../Test.php';
    //
    $a = ['a' => 0, 'b' => 1, 'c' => 2];
    ok(h_get($a, 'a'), 0, 'get a key');
    //
    $a = ['a' => 0, 'b' => 1, 'c' => 2];
    ok(h_get($a, 'unexisting', $_def = 'null'), 'null', 'get a default for a key');
    //----------------------------------------------------------------------------
    //  h_get
    //----------------------------------------------------------------------------
    ok(h_get(['a' => 1], 'a', 'def'), 1, 'test 1');
    ok(h_get(['a' => 1], 'x', 'def'), 'def', 'test 2');
    // obj
    class test_obj {public $a = 'aa';}
    ok(h_get(new test_obj(), 'a', 'def'), 'def', 'test obj');
    // subkeys
    /// ok(h_get(['a' => ['b' => 'ab']], 'a.b', 'def'), 'ab', 'test a.b');
    // nuls & empty
    ok(h_get([], 'a', 'def'), 'def', 'test  []');
    ok(h_get(['a' => 1], '', 'def'), '', 'test empty key 1');
    /** @psalm-suppress PossiblyFalseArgument */
    ok(h_get(['a' => 1], false, 'def'), 1, 'test empty key 2');
    /** @psalm-suppress NullArgument */
    ok(h_get(['a' => 1], null, 'def'), 1, 'test empty key 3');
}
