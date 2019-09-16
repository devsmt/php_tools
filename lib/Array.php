<?php
//
// Set: unordered collection of objects in which each object can appear only once. "testing for membership"
// Dictionary: store and retrieve objects using key-value pairs.
//
class A {
    public static function first($a) {
        if (self::isAssociative($a)) {
            foreach ($a as $k => $v) {
                return $v;
            }
        } else {
            return reset($a);
        }
    }
    public static function last($a) {
        if (is_array($a)) {
            if (self::isAssociative($a)) {
                $keys = array_keys($a);
                $last_key = end($keys);
                return $a[$last_key];
            } else {
                return end($a);
            }
        }
    }
    // se c'è anche solo una chiave int è assoc
    public static function isAssociative($a) {
        $a_k = array_keys($a);
        for ($i = 0; $i < count($a_k); $i++) {
            if (is_int($a_k[$i])) {
                return false;
            }
        }
        return true;
    }
    // se c'è una chiave che non sia un int in sequenza di scorrimento, è associativo
    // $a array<mixed, mixed>
    public static function isAssociative2(array $a): bool{
        $i = 0;
        foreach ($a as $k => $v) {
            if ($k !== $i++) {
                return true;
            }
        }
        return false;
    }
    //
    // Determines if an array is associative.
    // An array is "associative" if it doesn't have sequential numerical keys beginning with zero.
    //
    public static function isAssociative3(array $array) {
        $keys = array_keys($array);
        return array_keys($keys) !== $keys;
    }
    public static function isSequential($var) {
        return is_numeric(implode(array_keys($var)));
    }
    // determina se la chiave e' disponibile e se non lo fosse restituisce $default
    // $k può essere un'array di chiavi
    // ottieni una chiave di hash o un defualt
    public static function get(array $h, string $k, string $def = '') {
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
    // assicura che tutto ciò che è in $a2 sia in $a
    public static function equals($a, $a2) {
        foreach ($a2 as $k => $v) {
            if (isset($a[$k])) {
                if ($a[$k] != $v) {
                    return false;
                }
            } else {
                return false;
            }
        }
        return true;
    }
    public static function del(&$a, $k) {
        unset($a[$k]);
        return $a;
    }
    // @see compact
    public static function deleteEmpty($a) {
        foreach ($a as $i => $value) {
            if (is_null($value) || $value === '') {
                unset($a[$i]);
            }
        }
        return $a;
    }
    // $records = [['a' => 'y', 'b' => 'z', 'c' => 'e'], ['a' => 'x', 'b' => 'w', 'c' => 'f']];
    // $subset1 = array_collect($records, 'a'); // $subset1 will be: [['a' => 'y'], ['a' => 'x']];
    // $subset2 = array_collect($records, ['a', 'c']); // $subset2 will be: [['a' => 'y', 'c' => 'e'], ['a' => 'x', 'c' => 'f']];
    public static function collect($array, $params) {
        $return = [];
        if (!is_array($params)) {
            $params = [$params];
        }
        foreach ($array as $record) {
            $rec_ret = [];
            foreach ($params as $search_term) {
                if (array_key_exists($search_term, $record)) {
                    $rec_ret[$search_term] = $record[$search_term];
                }
            }
            if (count($rec_ret) > 0) {
                $return[] = $rec_ret;
            }
        }
        return $return;
    }
    public static function isEmpty($a) {
        return count($a) == 0;
    }
    /* ----------------------------------------------------------------
    Ruby sugar
    ---------------------------------------------------------------- */
    //
    // array.compact ? an_array
    // Returns a copy of self with all nil elements removed.
    // [ "a", nil, "b", nil, "c", nil ].compact
    // #=> [ "a", "b", "c" ]
    //
    // public static function compact($a) {
    //     return self::reject($a, function ($v) {
    //         return $v == null;
    //     });
    // }
    // Returns a copy of array with all empty elements removed.
    public static function compact($a) {
        // array_values() to discard the non consecutive index
        $array_f = array_values(array_filter($a, function ($v) {
            // false will be skipped
            return !empty($v);
        }));
        return $array_f;
    }
    // array.reject {|item| block } ? an_array
    // Returns a new array containing the items in self for which the block is not true.
    public static function reject($a, $f) {
        return self::delete_if($a, $f);
    }
    // Deletes every element of self for which block evaluates to true.
    // The array is changed instantly every time the block is called and not after the iteration is over.
    // See also reject
    public static function delete_if($array, $block) {
        // false will be skipped
        $array = array_values(array_filter($array, $block));
        return $array;
    }
    //
    // array.select {|item| block } ? an_array
    // Invokes the block passing in successive elements from array, returning an array containing those elements for which the block returns a true value (equivalent to Enumerable#select).
    // a = %w{ a b c d e f }
    // a.select {|v| v =~ /[aeiou]/}   #=> ["a", "e"]
    //
    function select($array, $block) {
        // false will be skipped
        $array = array_values(array_filter($array, function ($v) use ($block) {
            $test = $block($v);
            return !$test;
        }));
        return $array;
    }
    //
    // array.uniq ? an_array
    // Returns a new array by removing duplicate values in self.
    // a = [ "a", "a", "b", "b", "c" ]
    // a.uniq   #=> ["a", "b", "c"]
    //
    function uniq($a) {
        return array_unique($a);
    }
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
    //
    // Checks array is an hash
    //
    function is_array_assoc($array) {
        if (!is_array($array) || empty($array)) {
            return false;
        }
        $count = count($array);
        for ($i = 0; $i < $count; ++$i) {
            if (!array_key_exists($i, $array)) {
                return true;
            }
        }
        return false;
    }
    function is_array_indexed($array) {
        if (!is_array($array) || empty($array)) {
            return false;
        }
        return !is_array_assoc($array);
    }
    // map both keys and values
    function array_map_keys(array $a1, \Closure $f_k_mapper = null, \Closure $f_v_mapper = null) {
        $f_k_mapper = $f_k_mapper ?? function ($k, $v) {return $k;};
        $f_v_mapper = $f_v_mapper ?? function ($v, $k) {return $v;};
        $a2 = [];
        foreach ($a1 as $k => $v) {
            $a2[$f_k_mapper($k, $v)] = $f_v_mapper($v, $k);
        }
        return $a2;
    }
    // returns the first argument that is not empty()
    function coalesce() {
        $args = func_get_args();
        foreach ($args as $arg) {
            if (!empty($arg)) {
                return $arg;
            }
        }
        return null;
    }
    // returns the first argument that is not == false.
    function coalesce_f() {
        return array_shift(array_filter(func_get_args()));
    }
    // returns the first argument that is not strictly NULL
    function coalesce_n() {
        $args = func_get_args();
        foreach ($args as $arg) {
            if (!is_null($arg)) {
                return $arg;
            }
        }
        return null;
    }
    // se non ci sono match ritorna l'ultimo argomento passato
    // coalesce_l(null, [] ) => []
    function coalesce_l() {
        $args = func_get_args();
        foreach ($args as $arg) {
            if (!empty($arg)) {
                return $arg;
            }
        }
        return $args[$i = count($args) - 1];
    }
    public static function prepend($array, $value, $key = null) {
        if (is_null($key)) {
            array_unshift($array, $value);
        } else {
            $array = [$key => $value] + $array;
        }
        return $array;
    }
    // Get a value from the array, and remove it.
    public static function pull(&$array, $key, $default = null) {
        $value = self::get($array, $key, $default);
        unset($array[$key]);
        return $value;
    }
    // from [2, 3, [4,5], [6,7], 8] to [2,3,4,5,6,7,8]
    function flatten($array = null) {
        $result = [];
        if (!is_array($array)) {
            $array = func_get_args();
        }
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, array_flatten($value));
            } else {
                $result = array_merge($result, [$key => $value]);
            }
        }
        return $result;
    }
    //
    // Returns true if the given predicate is true for all elements.
    //
    public static function every(callable $callback, array $arr) {
        foreach ($arr as $element) {
            if (!$callback($element)) {
                return false;
            }
        }
        return true;
    }
    //
    // Returns true if the given predicate is true for at least one element.
    //
    public static function some(callable $callback, array $arr) {
        foreach ($arr as $element) {
            if ($callback($element)) {
                return true;
            }
        }
        return false;
    }
    // array_merge fa casino con le chiavi, se numeriche, ad esempio i codici articolo o altro risultato da query
    public static function merge() {
        $arg_list = func_get_args();
        $res = [];
        foreach ($arg_list as $arg) {
            foreach ($arg as $k => $v) {
                $res[$k] = $v;
            }
        }
        return $res;
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
}
//
//
//
class ArrayPaginator {
    /*
    es.
    $pagelen = 30;
    $this->view->userCount = count($a_items);
    $this->view->page = $page;
    $this->view->pagelen = $pagelen;
    if( $a_items ) {
    $paginated_usr = array_slice($a_items, (($page-1)*$pagelen), $pagelen);
    $this->view->userList = $paginated_usr;
    }
     */
    // ritorna una pagina di una determinata lunghezza partendo dall'array
    public static function paginate($a_items, $pagelen, $page = 1) {
        return array_slice($a_items, (($page - 1) * $pagelen), $pagelen);
    }
    // rende i paginatori per un array di records
    public static function render($count, $pagelen, $page) {
        $add_pages = 5;
        $html = '
        <ul class="pagination">
          <li class="arrow"><a href="?page=1">&laquo;</a></li>
        ';
        $num_pages = ceil($count / $pagelen);
        $render_from_page = max($page - $add_pages, 1);
        $render_till_page = min($page + $add_pages, $num_pages);
        for ($i = $render_from_page; $i <= $render_till_page; $i++) {
            $cl = ($page == $i ? 'current' : '');
            $html .= '
              <li class="' . $cl . '">
                <a href="?page=' . $i . '">' . $i . '</a>
              </li>';
        }
        $html .= '
          <li class="arrow "><a href="?page=' . $num_pages . '">&raquo;</a></li>
        </ul>';
        return $html;
    }
}
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/Test.php';
    $a = ["key" => "i'm associative"];
    ok(A::isAssociative($a) === true, print_r($a, true));
    $a = A::del($a, 'key');
    ok(count($a) === 0, implode(',', $a));
    $a = [1, 2, 3];
    ok(A::isAssociative($a) === false, implode(',', $a));
    $a = A::del($a, 0);
    ok(count($a) === 2, 'del ' . implode(',', $a));
    $a = [1, 2, 3];
    ok(A::first($a) === 1, implode(',', $a));
    ok(A::last($a) === 3, implode(',', $a));
    $a = ['a' => 0, 'b' => 1, 'c' => 2];
    is(A::first($a), 0, 'array first');
    is(A::last($a), 2, 'array last');
    ok(A::equals([], []), 'empty array equals');
    ok(A::equals([0, 1, 2, 3, 4], [0, 1, 2, 3, 4]), 'num array equals');
    ok(A::equals(['a' => 'a'], ['a' => 'a']), 'associative array equals');
    ok(A::equals(['a' => 'a', 'b' => 'b'], ['a' => 'a']), 'different associative array has all the required values');
    ok(!A::equals(['a' => 'a'], ['a' => 'a', 'b' => 'b']), 'different associative array (not all the required values)');
    $a = ['a' => 0, 'b' => 1, 'c' => 2];
    ok(A::get($a, 'a') == 0, 'get a key');
    $a = ['a' => 0, 'b' => 1, 'c' => 2];
    ok(A::get($a, 'unexisting', 1) == 1, 'get a default for a key');
    $a = ['a' => 1, 'b' => null];
    ok(A::equals(A::deleteEmpty($a), ['a' => 1]), 'delete empty');
}