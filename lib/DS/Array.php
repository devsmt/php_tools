<?php
//
// Set: unordered collection of objects in which each object can appear only once. "testing for membership"
// Dictionary: store and retrieve objects using key-value pairs.
//
class A {
    /** @return mixed */
    public static function first(array $a) {
        if (self::isAssociative($a)) {
            foreach ($a as $k => $v) {
                return $v;
            }
        } else {
            return reset($a);
        }
    }
    /** @return mixed */
    public static function last(array $a) {
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
    public static function isAssociative(array $a): bool{
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
    public static function isAssociative3(array $array): bool{
        $keys = array_keys($array);
        return array_keys($keys) !== $keys;
    }
    public static function isSequential(array $a): bool {
        return is_numeric(implode('', array_keys($a)));
    }
    //-------------------------------------------
    // Checks array is an hash
    //
    function is_array_assoc(array $array): bool {
        if (empty($array)) {
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
    function is_array_indexed(array $array): bool {
        if (empty($array)) {
            return false;
        }
        return !self::is_array_assoc($array);
    }
    //------------------------------------------
    // @see H::get
    // // determina se la chiave e' disponibile e se non lo fosse restituisce $default
    // // $k può essere un'array di chiavi
    // // ottieni una chiave di hash o un defualt
    // public static function get(array $h, string $k, string $def = '') {
    //     if (array_key_exists($k, $h)) {
    //         return $h[$k];
    //     }
    //     // cerca una sottochiave
    //     if (strpos($k, '.') !== false) {
    //         foreach (explode('.', $k) as $segment) {
    //             if (is_array($h) && array_key_exists($h, $segment)) {
    //                 $h = $h[$segment];
    //             } else {
    //                 return $def;
    //             }
    //         }
    //         return $h;
    //     }
    //     // no match
    //     return $def;
    // }
    // assicura che tutto ciò che è in $a2 sia in $a
    public static function equals(array $a, array $a2): bool {
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
    /**
     * @param int|string $k
     */
    public static function del(array &$a, $k): array{
        unset($a[$k]);
        return $a;
    }
    // @see compact
    public static function deleteEmpty(array $a): array{
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
    /** @param array|string $params */
    public static function collect(array $array, $params): array{
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
    public static function isEmpty(array $a): bool {
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
    public static function compact(array $a): array{
        // array_values() to discard the non consecutive index
        $array_f = array_values(array_filter($a, function ($v) {
            // false will be skipped
            return !empty($v);
        }));
        return $array_f;
    }
    // array.reject {|item| block } ? an_array
    // Returns a new array containing the items in self for which the block is not true.
    /**
     * @param callable(mixed): bool $f
     */
    public static function reject(array $a, callable $f): array{
        return self::delete_if($a, $f);
    }
    // Deletes every element of self for which block evaluates to true.
    // The array is changed instantly every time the block is called and not after the iteration is over.
    // See also reject
    /**
     * @param callable(mixed): bool $block
     */
    public static function delete_if(array $array, callable $block): array{
        // false will be skipped
        $array = array_values(array_filter($array, $block));
        return $array;
    }
    //
    // array.select {|item| block } ? an_array
    // Invokes the block passing in successive elements from array, returning an array containing those elements for which the block returns a true value (equivalent to Enumerable#select).
    // a = %w{ a b c d e f }
    // a.select {|v| v =~ /[aeiou]/}   #=> ["a", "e"]
    /**
     * @param callable(mixed): bool $block
     */
    function select(array $array, callable $block): array{
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
    function uniq(array $a): array{
        return array_unique($a);
    }
    // returns the first argument that is not empty()
    /** @return mixed */
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
    /** @return mixed */
    function coalesce_f() {
        $args = func_get_args();
        $arg2 = array_filter($args);
        return array_shift($arg2);
    }
    // returns the first argument that is not strictly NULL
    /** @return mixed */
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
    /** @return mixed */
    function coalesce_l() {
        $args = func_get_args();
        foreach ($args as $arg) {
            if (!empty($arg)) {
                return $arg;
            }
        }
        return $args[$i = count($args) - 1];
    }
    /** @param mixed $value */
    public static function prepend(array $array, $value, string $key = ''): array{
        if (empty($key)) {
            array_unshift($array, $value);
        } else {
            $array = [$key => $value] + $array;
        }
        return $array;
    }
    // from [2, 3, [4,5], [6,7], 8] to [2,3,4,5,6,7,8]
    /** @param array $args */
    function flatten(...$args): array{
        $result = [];
        $array = func_get_args();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, self::flatten($value));
            } else {
                $result = array_merge($result, [$key => $value]);
            }
        }
        return $result;
    }
    //
    // Returns true if the given predicate is true for all elements.
    //
    public static function every(callable $callback, array $arr): bool {
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
    public static function some(callable $callback, array $arr): bool {
        foreach ($arr as $element) {
            if ($callback($element)) {
                return true;
            }
        }
        return false;
    }
    // array_merge fa casino con le chiavi, se numeriche, ad esempio i codici articolo o altro risultato da query
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

    //----------------------------------------------------------------------------
    //  Array come coda o stak
    //----------------------------------------------------------------------------

    /** @param mixed $v */
    public static function append_l(array $a, $v): array{
        $count = array_unshift($a, $v);
        return $a;
    }
    /** @param mixed $v */
    public static function append_r(array $a, $v): array{
        $count = array_push($a, $v);
        return $a;
    }
    /** @return mixed */
    public static function pop_l(array $a) {
        $v = array_shift($a); //get and remove first el
        return $v;
    }
    /** @return mixed */
    public static function pop_r(array $a) {
        $v = array_pop($a); //get and remove last el
        return $v;
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
    public static function paginate(array $a_items, int $pagelen, int $page = 1): array{
        return array_slice($a_items, (($page - 1) * $pagelen), $pagelen);
    }
    // rende i paginatori per un array di records
    public static function render(int $count, int $pagelen, int $page): string{
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
    require_once __DIR__ . '/../Test.php';
    $a = ["key" => "i'm associative"];
    ok(A::isAssociative($a), true, print_r($a, true));
    $a = A::del($a, 'key');
    ok(count($a), 0, implode(',', $a));
    $a = [1, 2, 3];
    ok(A::isAssociative($a), false, implode(',', $a));
    $a = A::del($a, 0);
    ok(count($a), 2, 'del ' . implode(',', $a));
    $a = [1, 2, 3];
    ok(A::first($a), 1, implode(',', $a));
    ok(A::last($a), 3, implode(',', $a));
    $a = ['a' => 0, 'b' => 1, 'c' => 2];
    ok(A::first($a), 0, 'array first');
    ok(A::last($a), 2, 'array last');
    ok(A::equals([], []), true, 'empty array equals');
    ok(A::equals([0, 1, 2, 3, 4], [0, 1, 2, 3, 4]), true, 'num array equals');
    ok(A::equals(['a' => 'a'], ['a' => 'a']), true, 'associative array equals');
    ok(A::equals(['a' => 'a', 'b' => 'b'], ['a' => 'a']), true, 'different associative array has all the required values');
    ok(!A::equals(['a' => 'a'], ['a' => 'a', 'b' => 'b']), true, 'different associative array (not all the required values)');
    //
    $a = ['a' => 1, 'b' => null];
    ok(A::equals(A::deleteEmpty($a), ['a' => 1]), true, 'delete empty');
    //
    $ar = A::append_l([2, 3], 1);
    ok($ar, [1, 2, 3]);
    //
    $ar = A::append_r([1, 2], 3);
    ok($ar, [1, 2, 3]);
    //
    $v1 = A::pop_l([1, 2]);
    ok($v1, 1);
    //
    $v2 = A::pop_r([1, 2]);
    ok($v2, 2);
}