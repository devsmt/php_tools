<?php
declare (strict_types = 1); //php7.0+, will throw a catchable exception if call typehints and returns do not match declaration
//----------------------------------------------------------------------------
// underscore like functions
//----------------------------------------------------------------------------
// Reduce a collection to a single value
// reduce aliases: foldl, inject
/**
 * @return mixed
 * @param callable(mixed, mixed):mixed $iterator
 * @param mixed $memo
 */
function array_foldl(array $collection = [], callable $iterator, $memo = null) {
    return array_reduce($collection, $iterator, $memo);
}
// function array_inject(array $collection = [], callable $iterator, $memo = null) {
//     return array_reduce($collection, $iterator, $memo);
// }
// Right-associative version of reduce
// reduceRight alias: foldr
/**
 * @return mixed
 * @param callable(mixed, mixed):mixed $iterator
 * @param mixed $memo
 */
function array_foldr(array $collection = [], callable $iterator, $memo = null) {
    return array_reduce_right($collection, $iterator, $memo);
}
/**
 * @param callable(mixed, mixed):mixed $iterator
 * @param mixed $memo
 * @return mixed
 */
function array_reduce_right(array $collection = [], callable $iterator, $memo) {
    if (empty($collection)) {
        return $memo;
    }
    krsort($collection);
    return array_reduce($collection, $iterator, $memo);
}
/**
 * Does at least 1 value in the collection meet the iterator's truth test?
 * @param callable(mixed): bool $iterator
 */
function array_some(array $collection = [], callable $iterator): bool {
    return array_any($collection, $iterator);
}
/**
 * Does at least 1 value in the collection meet the iterator's truth test?
 * @param callable(mixed): bool $iterator
 */
function array_any(array $collection = [], callable $iterator): bool {
    // return (is_int(array_search(true, $collection, false)));
    foreach ($collection as $v) {
        if ($iterator($v)) {
            return true;
        }
    }
    return false;
}
/**
 * @param callable(mixed): bool $iterator
 */
function array_every(array $collection = [], callable $iterator): bool {
    return array_all($collection, $iterator);
}
/**
 * returns true if the given predicate is true for all elements.
 * Do all values in the collection meet the iterator's truth test?
 * @param callable(mixed): bool $iterator
 */
function array_all(array $collection = [], callable $iterator): bool {
    foreach ($collection as $element) {
        if (!$iterator($element)) {
            return false;
        }
    }
    return true;
}
/**
 * return an array of values that pass the truth iterator test
 *
 * array.select {|item| block } ? an_array
 * Invokes the block passing in successive elements from array,
 * returning an array containing those elements for which the block
 * returns a true value (equivalent to Enumerable#select).
 * a = %w{ a b c d e f }
 * a.select {|v| v =~ /[aeiou]/}   #=> ['a', 'e']
 *
 * @param callable(mixed): bool $block
 */
function array_select(array $array, callable $block): array{
    // false will be skipped
    $array = array_values(array_filter($array, function ($v) use ($block) {
        $test = $block($v);
        return $test;
    }));
    return $array;
}
/**
 * @param callable(mixed): bool $block
 */
function array_reject(array $array, callable $block): array{
    $array = array_values(array_filter($array, function ($v) use ($block) {
        $test = !$block($v);
        return $test;
    }));
    return $array;
}
//----------------------------------------------------------------------------
/**
 * Return the first element in an array passing a given truth test.
 *
 * return the value of the first item passing the truth iterator test
 * find alias: array_detect
 *
 * @param mixed $default
 * @return mixed
 */
function array_find_first(array $array, callable $callback = null, $default = null) {
    if (is_null($callback)) {
        if (empty($array)) {
            return $default;
        }
        foreach ($array as $item) {
            return $item;
        }
    }
    foreach ($array as $key => $value) {
        if ($callback && call_user_func($callback, $value, $key)) {
            return $value;
        }
    }
    return $default;
}
//----------------------------------------------------------------------------
/**
 * Get the first element of an array. Passing n returns the first n elements.
 * first alias: head
 * @param list<mixed> $collection
 */
function array_head(array $collection = [], int $n = 1): array{
    return array_first($collection, $n);
}
/**
 * @param list<mixed> $collection
 */
function array_first(array $collection = [], int $n = 1): array{
    if ($n === 0) {
        return [];
    }
    return array_slice($collection, 0, $n);
}
/** return everything but the last $n elements.
 * @param list<mixed> $collection
 */
function array_except_initial(array $collection = [], int $n = 1): array{
    return array_slice($collection, $n, count($collection));
}
//----------------
// Get the rest of the array elements. Passing n returns from that index onward.
function array_tail(array $collection = [], int $index = 1): array{
    return array_last($collection, $index);
}
/**
function array_rest(array $collection = [], int $n = 1):array {
// list($first, $args_rest) = array_rest( func_get_args() );
//     if ($n > 1) {
//         return [array_slice($collection, 0, $n), array_slice($collection, $n)];
//     } else {
//         $arg_1 = array_shift($args); //first arg
//         // all remaininng args
//         return [$arg_1, $args];
//     }
}
 */
/**
 * Get the last element from an array. Passing n returns the last n elements.
 * all but first $n element of array
 * @param list<mixed>|array<array-key, mixed> $collection
 * @return list<mixed>
 */
function array_last(array $collection = [], int $n = 1) {
    if (array_is_list($collection)) {
        if ($n === 0) {
            return [];
        }
        return array_slice($collection, $n * -1, $n);
    } else {
        return array_values($collection);
    }
}
//----------------------------------------------------------------------------
// Make multidimensional array flat
// from [2, 3, [4,5], [6,7], 8] to [2,3,4,5,6,7,8]
/** @param array $args */
function __array_flatten(): array{
    // flatten an array, not variadic args
    $_array_flatten = function (array $array, $depth = INF) use (&$_array_flatten): array{
        $result = [];
        foreach ($array as $item) {
            if (!is_array($item)) {
                $result[] = $item;
            } else {
                if (!array_is_list($item)) {
                    $msg = sprintf('Errore %s ', "array_flatten cant flatten hashes " . json_encode($item));
                    throw new \Exception($msg);
                }
                if ($depth === 1) {
                    /** @psalm-suppress RedundantFunctionCall  */
                    $values = array_values($item);
                } else {
                    $values = $_array_flatten($item, $depth - 1);
                }
                foreach ($values as $value) {
                    $result[] = $value;
                }
            }
        }
        return $result;
    };
    return $_array_flatten(func_get_args());
}
/**
 *
 */
function array_flatten(): array{
    $result = func_get_args();
    // check all elements of $list are values, not arrays
    $_is_flat = function (array $list): bool {
        foreach ($list as $val) {
            if (is_array($val)) {
                return false;
            }
        }
        return true;
    };
    do {
        $tmp = [];
        foreach ($result as $k => $val) {
            if (is_array($val)) {
                if (!array_is_list($val)) {
                    throw new \Exception(sprintf("array_flatten can't handle associative arrays: %s", json_encode($val)));
                }
                $tmp = array_merge($tmp, $val);
            } else {
                $tmp[] = $val;
            }
        }
        $result = $tmp;
    } while (!$_is_flat($result));
    return $result;
}

/**
 * this is tested for hashes
 * @param array<string, mixed> $hash
 */
function hash_flatten(array $hash) {
    $hash2 = [];
    foreach ($hash as $key => $value) {
        if (is_array($value) && array_is_flattable($value)) {
            $hash2[] = array_flatten($value);
        } else {
            if( is_string($key) ){
                $hash2[$key] = $value;
            } else {
                $hash2[] = $value;
            }
        }
    }
    $return = [];
    array_walk_recursive($hash2, function ($value, $key) use (&$return) {
        $return[$key] = $value;
    });
    return $return;
}
// doeasn't contain any hash 
function array_is_flattable(array $array) {
    try {
        array_flatten($array);
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * returns a copy of the array with all instances of $removes values removed
 * @param list<mixed>|array<array-key,mixed>  $collection
 */
function array_without(array $collection = [], array $removes = []): array{
    if (empty($collection)) {
        return [];
    }
    foreach ($removes as $remove) {
        $remove_keys = array_keys($collection, $remove, true);
        if (count($remove_keys) > 0) {
            foreach ($remove_keys as $key) {
                unset($collection[$key]);
            }
        }
    }
    // if (array_is_list($collection)) {
    //     return array_values($collection);
    // }
    return $collection;
}
/**
 * Get the index of the first match, -1 if not found
 * @param mixed $item
 * @return array{0: bool, 1: string|int}
 * TODO:test
 */
function array_index_of(array $collection = [], $item): array{
    $key = array_search($item, $collection, true);
    if (is_bool($key)) {
        return [false, -1];
    } else {
        return [true, (string) $key];
    }
}
/**
 * Get the index of the last match
 * @param mixed $item
 * @return array{0: bool, 1: string|int}
 * TODO:test
 */
function array_last_index_of(array $collection = [], $item = null) {
    krsort($collection);
    list($present, $key) = array_index_of($collection, $item);
    return [$present, $key];
}
//----------------------------------------------------------------------------
/**
 * Sort the collection by return values from the iterator
 * @param callable(mixed): scalar $iterator
 */
function array_sort_by(array $collection = [], callable $iterator): array{
    $results = [];
    foreach ($collection as $k => $item) {
        $results[$k] = $iterator($item);
    }
    arsort($results); //Sort an array in descending order and maintain index association
    $ret = [];
    foreach ($results as $k => $_v) {
        $ret[$k] = $collection[$k];
    }
    return array_values($ret);
}
// Group the collection by return values from the iterator
/** @param callable|string  $iterator */
function array_group_by(array $collection, $iterator): array{
    $result = [];
    foreach ($collection as $k => $v) {
        $key = $iterator($v, $k);
        // if group not exists, create group
        if (!array_key_exists($key, $result)) {
            $result[$key] = [];
        }
        $result[$key][] = $v;
        sort($result[$key], SORT_STRING); // &$a;  sort values! returns bool
    }
    ksort($result); //in-place sort keys!
    return $result;
}
/**
 * groups an RS by key
 * @param list< array<string, string> >  $rs
 * @param callable(array): array $_rec_mapper
 * @param callable(mixed, mixed): mixed $_rs_reducer
 * @return array<string, mixed>
 */
function array_group_by_key(array $rs, string $key, callable $_rec_mapper = null, callable $_rs_reducer = null, int $initial_v = 0): array{
    if (null == $_rec_mapper) {
        $_rec_mapper = function (array $rec): array{return $rec;};
    }
    $result = [];
    foreach ($rs as $rec) {
        if (array_key_exists($key, $rec)) {
            $val = $rec[$key];
            $result[$val][] = $_rec_mapper($rec);
        } else {
            // $result[""][] = $rec;// incorrect shape of the array
            die("incorrect shape of the array, $key missing in " . json_encode(array_keys($rec)));
        }
    }
    // perform reduce on results
    if (null !== $_rs_reducer) {
        $result2 = [];
        foreach ($result as $key => $sub_rs) {
            // perform reducer:
            // function($carry_v, $cur_v) {
            //     $carry_v += $cur_v;
            //     return $carry_v;
            // }
            $final_v = array_reduce($sub_rs, $_rs_reducer, $initial_v);
            $result2[$key] = $final_v;
        }
        return $result2;
    }
    return $result;
}
/**
 * Does the given key exist?
 * @param string|int $key
 */
function array_has(array $collection = [], $key): bool {
    // return ((array_search($val, $collection, true) !== false));
    return array_key_exists($key, $collection);
}
/**
 * @param string|int $key
 */
function array_contains(array $collection = [], $key): bool {
    return array_has($collection, $key);
}
// assicura che tutto ciò che è in $a2 sia in $a
function array_contains_all(array $a, array $a2): bool {
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
//----------------------------------------------------------------------------
if (!function_exists('array_is_list')) { // php8.1
    function array_is_list(array $a): bool {
        foreach ($a as $k => $v) {
            if (is_string($k)) {
                return false;
            }
        }
        return true;
    }
}
// Checks array is an hash
function array_is_associative(array $array): bool {
    if (empty($array)) {
        return false;
    }
    return !is_numeric(implode('', array_keys($array)));
}
//----------------------------------------------------------------------------
/**
 * @param int|string $k
 */
function array_del(array &$a, $k): array{
    unset($a[$k]);
    return $a;
}
/**
 * delete values from a list
 * @param array|string $keys
 */
function array_remove(array $collection = [], $keys): array{
    if (!is_array($keys)) {
        $keys = [$keys];
    }
    foreach ($keys as $key) {
        //delete element in array by value
        if (($key = array_search($key, $collection)) !== false) {
            unset($collection[$key]);
        }
    }
    return $collection;
}
// @see array_compact
function array_delete_empty(array $a): array{
    foreach ($a as $i => $value) {
        if (is_null($value) || $value === '' || $value === false) {
            unset($a[$i]);
        }
    }
    // discard non contiguos values
    if (array_is_list($a)) {
        /** @psalm-suppress RedundantFunctionCall  */
        return array_values($a);
    }
    return $a;
}
//
// array.compact ? an_array
// returns a copy of self with all nil elements removed.
// [ 'a', nil, 'b', nil, 'c', nil ].compact
// #=> [ 'a', 'b', 'c' ]
// returns a copy of array with all empty elements removed.
function array_compact(array $a): array{
    // array_values() to discard the non consecutive index
    $array_f = array_values(array_filter($a, function ($v) {
        // false will be skipped
        return !empty($v);
    }));
    return $array_f;
}
//----------------------------------------------------------------------------
/**
 * @param array $a_keys
 * $records = [['a' => 'y', 'b' => 'z', 'c' => 'e'], ['a' => 'x', 'b' => 'w', 'c' => 'f']];
 * $subset1 = array_collect($records, 'a'); // $subset1 will be: [['a' => 'y'], ['a' => 'x']];
 * $subset2 = array_collect($records, ['a', 'c']); // $subset2 will be: [['a' => 'y', 'c' => 'e'], ['a' => 'x', 'c' => 'f']];
 */
function array_collect(array $array, array $a_keys): array{
    $return = [];
    foreach ($array as $record) {
        $rec_ret = [];
        foreach ($a_keys as $search_term) {
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
// Extract an array of values for a given property
// da RS ritorna Array<string>
// RS => Array<string>
// [ ['a' => 1], ['a' => 2] ] => [1,2]
function array_pluck(array $collection = [], string $key): array{
    $return = [];
    foreach ($collection as $item) {
        foreach ($item as $k => $v) {
            if ($k === $key) {
                $return[] = $v;
            }
        }
    }
    return ($return);
}
// NOTA: a differenza di h_pluck che ritorna Array< Hash<String,mixed> >
// da RS ritorna Array<string>
// function array_pluck(string $key, array $data): array{
//     return array_reduce($data, function ($result, $array) use ($key) {
//         isset($array[$key]) &&
//         $result[] = $array[$key];
//         return $result;
//     }, []);
// }
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
/**
 * returns the first argument that is not strictly NULL
 * @return mixed
 */
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
//
// basic comparison === works, but will fail if values are in different order
//   ( ['x','y'] === ['y','x' ) === false;
//
// all keys and values equal and of same type, irregardless of item order or key order
function array_equal(array $a, array $b): bool {
    if (count($a) != count($b)) {
        return false;
    }
    /** @var callable */
    $_sort = function (array $a) use (&$_sort): array{
        if (array_is_list($a)) {
            sort($a); // sort discarding index association
            return $a;
        }
        ksort($a);
        // sort keys, multi-dimensional recursive
        return array_map(fn($v) => is_array($v) ? $_sort($v) : $v, $a);
    };
    // === checks that the types and order of the elements are the same
    return $_sort($a) === $_sort($b);
}
// alias:
function array_is_equal(array $a, array $b): bool {return array_equal($a, $b);}
function array_equals(array $a, array $b): bool {return array_equal($a, $b);}
//
//----------------------------------------------------------------------------
//  Array come coda o stak
//----------------------------------------------------------------------------
// arrow or closure: f__##
// f_go_##
// $queue = ['orange', 'banana'];
// $q2 = array_unshift($queue, 'apple', 'raspberry');
// => [ apple, raspberry, orange, banana ]
/** @param mixed $v */
function array_append_left(array $a, $v): array{
    return array_append_l($a, $v);
}
/** @param mixed $v */
function array_append_l(array $a, $v): array{
    $count = array_unshift($a, $v);
    return $a;
}
/** @param mixed $v */
function array_append_r(array $a, $v): array{
    $count = array_push($a, $v);
    return $a;
}
/** @return mixed */
function array_pop_l(array $a) {
    $v = array_shift($a); //get and remove first el
    return $v;
}
/** @return mixed */
function array_pop_r(array $a) {
    $v = array_pop($a); //get and remove last el
    return $v;
}
/** @param mixed $value */
function array_prepend(array $array, $value, string $key = ''): array{
    if (empty($key)) {
        array_unshift($array, $value);
    } else {
        $array = [$key => $value] + $array;
    }
    return $array;
}
// Calculate the average
function array_avg(array $a): float {
    if (count($a) == 0) {
        return 0;
    }
    $avg = array_sum($a) / count($a);
    return $avg;
}
//
//
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
        $html = <<<__END__
        <ul class='pagination'>
        <li class='arrow'><a href="?page=1">&laquo;</a></li>
__END__;
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
    $a = ['key' => "i'm associative"];
    ok(array_is_associative($a), true, 'assoc 1');
    ok(array_is_associative([1, 2]), false, 'assoc 2');
    ok(array_is_associative([]), false, 'assoc empty');
    //
    ok(array_is_list([1, 2]), true, 'array_is_list 1');
    ok(array_is_list(['a' => 1, 'b' => 2]), false, 'array_is_list 2');
    ok(array_is_list([]), true, 'array_is_list empty');
    //
    $a = [1, 2, 3];
    $a = array_del($a, 0);
    ok(count($a), 2, 'array_del ' . json_encode($a));
    //
    ok(array_remove([1, 2, 3, 4], [3, 4]), [1, 2], 'array_remove 1');
    ok(array_delete_empty([1, 2, null, false, 0]), [1, 2, 0], 'array_delete_empty 1');
    ok(array_compact([1, 2, null, false, 0]), [1, 2], 'array_compact 1');
    //
    ok(array_contains_all([1, 2, null, false, 0], [1, 2]), true, 'array_contains_all 1');
    ok(array_contains_all([1, 2, null, false, 0], [1, 2, 3]), false, 'array_contains_all 2');
    //
    $a = [1, 2, 3];
    ok(array_first($a, 1), [1], 'array_first 1');
    ok(array_first($a, 2), [1, 2], 'array_first 2');
    //
    ok(array_last([1, 2, 3], 2), [2, 3], 'array_last 1');
    ok(array_last([1, 2, 3], 1), [3], 'array_last 2');
    // do not work with hashes
    // $a = ['a' => 0, 'b' => 1, 'c' => 2];
    // ok(array_first($a), [0], 'array first');
    // ok(array_last($a, 1), [2], 'array last');
    //
    // array_equals()
    //
    ok(array_equals([], []), true, 'empty array equals');
    assertEquals(array_equal([1], [1]), true, 'simple eq');
    assertEquals(array_equal([0], [false]), false, 'simple eq');
    assertEquals(array_equal([0], [null]), false, 'simple eq');
    assertEquals(array_equal([0, 1], [1, 0]), true, 'simple eq, diff order');
    assertEquals(array_equal([0, 1, 2], [1, 0]), false, 'diff count');
    assertEquals(array_equal([0, 1], [0, 1, 2]), false, 'diff count 2');
    assertEquals(array_equal([1, 2], [1, 2, 'hello']), false, 'diff count 3');
    //
    assertEquals(array_equal([1, 2, 2], [2, 1, 1]), false, 'same vals repeated');
    assertEquals(array_equal([1, 2, 2], [2, 2, 1]), true, 'same vals, different order');
    //
    assertEquals(array_equal([1, 2, 3], ['1', '2', '3']), false, 'int should not be eq string');
    assertEquals(array_equal([0 => 'a', 1 => 'b'], [0 => 'b', 1 => 'a']), true, 'same vals, diff order');
    assertEquals(array_equal(['a', 'b'], [3 => 'b', 5 => 'a']), true, 'same vals, diff indexes');
    // associative arrays whose members are ordered differently
    assertEquals(array_equal(['aa' => 'a', 'bb' => 'b'], ['bb' => 'b', 'aa' => 'a']), true, 'dict with different order');
    assertEquals(array_equal(['aa' => 'a', 'bb' => 'b'], ['aa' => 'a']), false, 'a key is missing');
    // nested arrays with keys in different order
    assertEquals(array_equal(
        ['aa' => 'a', 'bb' => ['bb' => 'b', 'aa' => 'a']],
        ['aa' => 'a', 'bb' => ['aa' => 'a', 'bb' => 'b']]
    ), true, 'dict multi 2 level, keys in different order');
    assertEquals(array_equal(
        ['aa' => 'a', 'bb' => ['aa2' => 'a', 'bb2' => ['aa3' => 'a', 'bb3' => 'b']]],
        ['aa' => 'a', 'bb' => ['aa2' => 'a', 'bb2' => ['aa3' => 'a', 'bb3' => 'b']]]
    ), true, 'dict multi 3 level');
    assertEquals(array_equal(
        ['aa' => 'a', 'bb' => [0, 1]],
        ['aa' => 'a', 'bb' => [1, 0]]
    ), true, 'dict multi 2 level, 2^ level sequential in different order');
    //
    //
    ok(array_contains_all(['a' => 'a', 'b' => 'b'], ['a' => 'a']), true, 'different associative array has all the required values');
    ok(!array_contains_all(['a' => 'a'], ['a' => 'a', 'b' => 'b']), true, 'different associative array (not all the required values)');
    //
    $a = ['a' => 1, 'b' => null];
    ok(array_equals(array_delete_Empty($a), ['a' => 1]), true, 'delete empty');
    //
    $ar = array_append_l([2, 3], 1);
    ok($ar, [1, 2, 3], 'array_append_l');
    //
    $ar = array_append_r([1, 2], 3);
    ok($ar, [1, 2, 3], 'array_pop_r');
    //
    $v1 = array_pop_l([1, 2]);
    ok($v1, 1, 'array_pop_l');
    //
    $v2 = array_pop_r([1, 2]);
    ok($v2, 2, 'array_pop_r');
    //
    $a_sel = array_find_first([1, 2, 10, 2, 5, 2], function ($v): bool {return $v == 2;});
    ok($a_sel, $expected = 2, 'array_find_first');
    //
    $is_present = array_some([1, 2, 10], function ($v): bool {return $v >= 10;});
    ok($is_present, $expected = true, 'array_some');
    //
    $all_positive = array_every([1, 2, 10], function ($v): bool {return $v > 0;});
    ok($all_positive, $expected = true, 'array_every');
    //
    $a_sel = array_select([1, 2, 10], function ($v): bool {return $v == 2;});
    ok($a_sel, $expected = [2], 'array_select');
    //
    $a_sel = array_reject([1, 2, 10], function ($v): bool {return $v == 2;});
    ok($a_sel, $expected = [1, 10], 'array_reject');
    //
    $a_sel = array_first([1, 2, 10, 2, 5, 2], 2);
    ok($a_sel, $expected = [1, 2], 'array_first');
    //
    $a_sel = array_except_initial([1, 2, 10, 2, 5, 2], 2);
    ok($a_sel, $expected = [10, 2, 5, 2], 'array_except_initial');
    //
    $a_sel = array_tail([1, 2, 10, 2, 5, 2], 2);
    ok($a_sel, $expected = [5, 2], 'array_tail');
    //
    //
    //
    assertEquals(array_flatten(1, 2), $expected = [1, 2], 'array_flatten 1a');
    assertEquals(array_flatten([1], [2]), $expected = [1, 2], 'array_flatten 1b');
    assertEquals(array_flatten([1], [[2], 3]), $expected = [1, 2, 3], 'array_flatten 1c');
    assertEquals(array_flatten(1, [2, 3], [4, 5]), $expected = [1, 2, 3, 4, 5], 'array_flatten 2');
    assertEquals(array_flatten(2, 3, [4, 5], [6, 7], 8), $expected = [2, 3, 4, 5, 6, 7, 8], 'array_flatten 3');
    assertEquals(array_flatten([2, 3, [4, 5], [6, 7], 8]), $expected = [2, 3, 4, 5, 6, 7, 8], 'array_flatten 4');
    assertEquals(array_flatten([2, [3, [4, [5]], [6, [7]], 8]]), $expected = [2, 3, 4, 5, 6, 7, 8], 'array_flatten complex');
    //
    // do not work with hashes
    $hash = [
        "a" => "a",
        ['a' => 'a'],
        ['b' => 'b'],
    ];
    assertEquals(hash_flatten($hash), ['a' => 'a', 'b' => 'b'], 'array_flatten hash');
    //
    $hash = [
        "a" => "a",
        ['b' => 'b'],
        [1, 2],
        3, 4,
    ];
    assertEquals(hash_flatten($hash), ['a' => 'a', 'b' => 'b', 1, 2, 3, 4], 'array_flatten hash 2');
    // questo caso non funziona ancora
    $nested_hash = [
        [1],
        [2, [3, [4, [5]]]],
        "a" => "a",
        "rs" => [
            ["id" => 1, "name" => "name1"],
            ["id" => 2, "name" => "name2"],
        ],
    ];
    assertEquals(hash_flatten($nested_hash), [1, 2, 3, 4, 5,
        'a' => 'a',
        "id" => 2,
        "name" => "name2",
    ], 'array_flatten hash complex');
    //
    //
    //
    $a = [1, 2, 3];
    ok(array_without($a, [3]), [1, 2], 'array_without');
    //
    $a = ['c', 'd', 'a', 'e'];
    ok(array_sort_by($a, function ($v): int {
        if (in_array($v, ['a', 'e', 'i', 'o', 'u'])) {
            return 10;
        }
        return 0;
    }), ['a', 'e', 'c', 'd'], 'array_sort_by');
    //
    $a = ['c', 'z', 'd', 'a', 'u', 'e'];
    ok(array_group_by($a, function ($v): string {
        if (in_array($v, ['a', 'e', 'i', 'o', 'u'])) {
            return 'vocals';
        } else {
            return 'consonants';
        }
    }), ['consonants' => ['c', 'd', 'z'], 'vocals' => ['a', 'e', 'u']], 'array_sort_by');
    //
    $rs1 = [
        ['a' => 'y', 'b' => 'z', 'c' => 'e'],
        ['a' => 'x', 'b' => 'w', 'c' => 'f'],
    ];
    $rs2 = array_collect($rs1, ['a']);
    $rs_e = [['a' => 'y'], ['a' => 'x']];
    ok($rs2, $rs_e, 'array_collect');
    //
    $rs2 = array_pluck($rs1, 'a');
    ok($rs2, ['y', 'x'], 'array_pluck');
}
