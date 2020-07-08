<?php
// astrazioni su hash o array associativi
final class H {
    // ottieni una chiave di hash o un defualt
    // ritorna stringa o altro tipo
    // if $k is array
    // return the first of $keys found or default
    /**
     * @param mixed $def
     * @param string|int|array $k
     * @return mixed
     */
    public static function get(array $h, $k, $def = '') {
        // se $h == null
        if (empty($h) || empty($k)) {
            return $def;
        }
        // boolean, double, integer, or string types are scalar. Array, object, and resource are not
        if (is_scalar($k)) {
            if (array_key_exists($k, $h)) {
                return $h[$k];
            } else {
                return $def;
            }
        } elseif (is_array($k)) {
            // @see h_get_subkey
            foreach ($k as $key) {
                if (array_key_exists($key, $h)) {
                    return $h[$key];
                }
            }
            return $def;
        } else {
            $msg = sprintf('Errore %s ', 'unsopported key type ' . var_export($k, true));
            throw new \Exception($msg);
        }
    }
    /**
     * @param mixed $def
     * @return mixed
     */
    // ottieni una chiave di hash o un defualt
    public static function get_subkey(array $h, string $k, $def = '') {
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
    public static function iget(array $h, $k, $def = false) {
        foreach ($h as $key => $val) {
            if (strtolower($k) == strtolower($key)) {
                return $val;
            }
        }
        return $def;
    }
    // map both keys and values
    public static function map_keys(array $a1, callable $f_k_mapper = null, callable $f_v_mapper = null): array{
        $f_k_mapper = $f_k_mapper ? $f_k_mapper : function ($k, $v) {return $k;};
        $f_v_mapper = $f_v_mapper ? $f_v_mapper : function ($v, $k) {return $v;};
        $a2 = [];
        foreach ($a1 as $k => $v) {
            $a2[$f_k_mapper($k, $v)] = $f_v_mapper($v, $k);
        }
        return $a2;
    }
    // trattiene solo chiavi e valori che passino la funzione di grep
    public static function grep(array $h, \Closure $_grep): array{
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
    public static function filter(array $h_1, \Closure $_f): array{
        $h_2 = [];
        foreach ($h_1 as $key => $val) {
            if ($_f($key, $val)) {
                $h_2[$key] = $val;
            }
        }
        return $h_2;
    }
    // $promo_qta = h_query($rec, ['promo', 0, 'scaglioni', 0, 'acquistati'], $def=0);
    /**
     * @param mixed $default
     * @return mixed
     */
    public static function query(array $h, array $a_query, $default) {
        $arg_1 = array_shift($a_query); //first arg
        if (array_key_exists($arg_1, $h)) {
            $h2 = $h[$arg_1];
            if (is_array($h2)) {
                return static::query($h2, $a_query, $default);
            } else {
                return $h[$arg_1]; // element found
            }
        } else {
            return $default;
        }
    }
    // primo elemento di un hash
    /** @return mixed */
    public static function first(array $h) {
        if (empty($h)) {
            return [];
        }
        // works only if array has alements
        $first_k = array_keys($h)[0];
        return $h[$first_k];
    }
    // array_merge fa casino con le chiavi, se numeriche,ad esempio i codici articolo o altro risultato da query
    public static function merge(): array{
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
    public static function select_keys(array $h, array $a_keys) {
        $a2 = [];
        foreach ($a_keys as $key) {
            $a2[$key] = H::get($h, $key, null);
        }
        return $a2;
    }
    // Get a value from the array, and remove it.
    /**
     * @param mixed $default
     * @return mixed
     */
    public static function pull(array &$array, string $key, $default = null) {
        $value = self::get($array, $key, $default);
        unset($array[$key]);
        return $value;
    }
}
// if colled directly in CLI, run the tests:
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/../Test.php';
    //
    $a = ['a' => 0, 'b' => 1, 'c' => 2];
    ok(H::get($a, 'a'), 0, 'get a key');
    //
    $a = ['a' => 0, 'b' => 1, 'c' => 2];
    ok(H::get($a, 'unexisting', $_def = 'null'), 'null', 'get a default for a key');
}