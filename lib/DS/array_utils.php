<?php
declare (strict_types = 1); //php7.0+, will throw a catchable exception if call typehints and returns do not match declaration

//----------------------------------------------------------------------------
// underscore like functions
//----------------------------------------------------------------------------
// Reduce a collection to a single value
// reduce aliases: foldl, inject
/**
 * @return mixed
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
 * @param mixed $memo
 */
function array_foldr(array $collection = [], callable $iterator, $memo = null) {
    return array_reduce_right($collection, $iterator, $memo);
}
/**
 * @return mixed
 * @param mixed $memo
 */
function array_reduce_right(array $collection = [], callable $iterator, $memo = null) {
    if (!is_object($collection) && !is_array($collection)) {
        if (is_null($memo)) {
            throw new Exception('Invalid object');
        } else {
            return ($memo);
        }
    }
    krsort($collection);
    return (array_reduce($collection, $iterator, $memo));
}
// Does any values in the collection meet the iterator's truth test?
// any alias: some
function array_some(array $collection = [], callable $iterator): bool {
    return array_any($collection, $iterator);
}
//
// returns true if the given predicate is true for at least one element.
//
// at least one element passes
// $is_x = array_some($a_xxx, function($v){ return $v>10; });
function array_any(array $collection = [], callable $iterator): bool {
    // return (is_int(array_search(true, $collection, false)));
    foreach ($collection as $v) {
        if ($iterator($v)) {
            return true;
        }
    }
    return false;
}
// returns true if the given predicate is true for all elements.
// Do all values in the collection meet the iterator's truth test?
// all alias: every
function array_every(array $collection = [], callable $iterator): bool {
    return array_all($collection, $iterator);
}
function array_all(array $collection = [], callable $callback): bool {
    foreach ($collection as $element) {
        if (!$callback($element)) {
            return false;
        }
    }
    return true;
}
// return an array of values that pass the truth iterator test
//
// array.select {|item| block } ? an_array
// Invokes the block passing in successive elements from array,
// returning an array containing those elements for which the block
// returns a true value (equivalent to Enumerable#select).
// a = %w{ a b c d e f }
// a.select {|v| v =~ /[aeiou]/}   #=> ['a', 'e']
/**
 * @param callable(mixed): bool $block
 */
function array_select(array $array, callable $block): array{
    // false will be skipped
    $array = array_values(array_filter($array, function ($v) use ($block) {
        $test = $block($v);
        return !$test;
    }));
    return $array;
}
//----------------------------------------------------------------------------
// return the value of the first item passing the truth iterator test
// find alias: array_detect
/**
 * Return the first element in an array passing a given truth test.
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
// Get the first element of an array. Passing n returns the first n elements.
// first alias: head
/**
 * @return mixed
 */
function array_head(array $collection = [], int $n = 1) {
    return array_first($collection, $n);
}
/** @return mixed */
function array_first(array $collection = [], int $n = 1) {
    if ($n === 0) {
        return [];
    }
    if (1 === $n) {
        return (current(array_slice($collection, 0, 1)));
    } else {
        return array_slice($collection, 0, $n);
    }
}
// Get the rest of the array elements. Passing n returns from that index onward.
function array_tail(array $collection = [], int $index = 1): array{
    return array_rest($collection, $index);
}
/**
 * all but first element of array
 * list($first, $args_rest) = array_rest( func_get_args() );
 * @return array{array<array-key, mixed>|mixed, array<array-key, mixed>|mixed}
TODO: write tests
 */
// function array_rest(array $collection = [], int $index = 1):array {
//     if ($index > 1) {
//         return [array_slice($collection, 0, $index), array_slice($collection, $index)];
//     } else {
//         $arg_1 = array_shift($args); //first arg
//         // all remaininng args
//         return [$arg_1, $args];
//     }
// }
// return everything but the last array element. Passing n excludes the last n elements.
function array_initial(array $collection = [], int $n = 1): array{
    $first_index = count($collection) - $n;
    return array_first($collection, $first_index);
}
// Get the last element from an array. Passing n returns the last n elements.
/**
 * @return mixed|array
 * @param int $n
 */
function array_last(array $collection = [], int $n = 1) {
    if (is_array($collection)) {
        if (array_is_Associative($collection)) {
            $keys = array_keys($collection);
            $last_key = end($keys);
            return $collection[$last_key];
        } else {
            if ($n === 0) {
                $result = [];
            } elseif ($n === 1) {
                $result = array_pop($collection);
            } else {
                $result = array_slice($collection, $n * -1, $n);
            }
            return ($result);
        }
    } else {
        return null;
    }
}
//----------------------------------------------------------------------------
// Make multidimensional array flat
// from [2, 3, [4,5], [6,7], 8] to [2,3,4,5,6,7,8]
/** @param array $args */
function array_flatten(...$args): array{
    $result = [];
    $array = func_get_args();
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $result = array_merge($result, array_flatten($value));
        } else {
            $result = array_merge($result, [$key => $value]);
        }
    }
    return $result;
}
/**
 * returns a copy of the array with all instances of val removed
 * @param mixed $val
 */
function array_without(array $collection = [], $val = null): array{
    $num_args = count($args = func_get_args());
    if ($num_args === 1) {
        return $collection;
    }
    if (count($collection) === 0) {
        return $collection;
    }
    $removes = array_rest($args);
    foreach ($removes as $remove) {
        $remove_keys = array_keys($collection, $remove, true);
        if (count($remove_keys) > 0) {
            foreach ($remove_keys as $key) {
                unset($collection[$key]);
            }
        }
    }
    return $collection;
}
/**
 * Get the index of the first match, -1 if not found
 * @param mixed $item
 * @return array{0: bool, 1: string|int}
 */
function array_index_of(array $collection = [], $item = null): array{
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
 */
function array_last_index_of(array $collection = [], $item = null) {
    krsort($collection);
    list($present, $key) = array_index_of($collection, $item);
    return [$present, $key];
}
//----------------------------------------------------------------------------
// Sort the collection by return values from the iterator
function array_sort_by(array $collection = [], callable $iterator): array{
    $results = [];
    foreach ($collection as $k => $item) {
        $results[$k] = $iterator($item);
    }
    asort($results);
    foreach ($results as $k => $v) {
        $results[$k] = $collection[$k];
    }
    return (array_values($results));
}
// Group the collection by return values from the iterator
function array_group_by(array $collection = [], callable $iterator): array{
    $result = [];
    foreach ($collection as $k => $v) {
        $key = (is_callable($iterator)) ? $iterator($v, $k) : $v[$iterator];
        if (!array_key_exists($key, $result)) {
            $result[$key] = [];
        }
        $result[$key][] = $v;
    }
    return $result;
}

//
// groups an RS by key
//
function array_group_by_key(array $rs, string $key, $_rec_mapper = null, $_rs_reducer = null, $initial_v = 0): array{
    if (null == $_rec_mapper) {
        $_rec_mapper = function ($rec) {return $rec;};
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
    return (array_key_exists($key, $collection));
}
/**
 * @param string|int $key
 */
function array_contains(array $collection = [], $key): bool {
    return array_has($collection, $key);
}
//----------------------------------------------------------------------------
// se c'è anche solo una chiave int è assoc
function array_is_associative(array $a): bool {
    return is_array_assoc($a);
}
// Checks array is an hash
function is_array_assoc(array $array): bool {
    if (empty($array)) {
        return false;
    }
    return !is_numeric(implode('', array_keys($array)));
}
function is_array_indexed(array $array): bool {
    if (empty($array)) {
        return false;
    }
    return is_numeric(implode('', array_keys($array)));
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
        if (is_null($value) || $value === '') {
            unset($a[$i]);
        }
    }
    // discard non contiguos values
    if (is_array_indexed($a)) {
        return array_values($a);
    }
    return $a;
}
//----------------------------------------------------------------------------
// $records = [['a' => 'y', 'b' => 'z', 'c' => 'e'], ['a' => 'x', 'b' => 'w', 'c' => 'f']];
// $subset1 = array_collect($records, 'a'); // $subset1 will be: [['a' => 'y'], ['a' => 'x']];
// $subset2 = array_collect($records, ['a', 'c']); // $subset2 will be: [['a' => 'y', 'c' => 'e'], ['a' => 'x', 'c' => 'f']];
/** @param array|string $params */
function array_collect(array $array, array $a_keys): array{
    $return = [];
    if (!is_array($a_keys)) {
        $a_keys = [$a_keys];
    }
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


/* ----------------------------------------------------------------
Ruby sugar
---------------------------------------------------------------- */
//
// array.compact ? an_array
// returns a copy of self with all nil elements removed.
// [ 'a', nil, 'b', nil, 'c', nil ].compact
// #=> [ 'a', 'b', 'c' ]
//
// function array_compact($a) {
// return array_reject($a, function ($v) {
// return $v == null;
//     });
// }
// returns a copy of array with all empty elements removed.
function array_compact(array $a): array{
    // array_values() to discard the non consecutive index
    $array_f = array_values(array_filter($a, function ($v) {
        // false will be skipped
        return !empty($v);
    }));
    return $array_f;
}
// array.reject {|item| block } ? an_array
// returns a new array containing the items in self for which the block is not true.
/**
 * @param callable(mixed): bool $f
 */
function array_reject(array $a, callable $f): array{
    return array_delete_if($a, $f);
}
// Deletes every element of self for which block evaluates to true.
// The array is changed instantly every time the block is called and not after the iteration is over.
// See also reject
// return an array where the items failing the truth test are removed
/**
 * @param callable(mixed): bool $block
 */
function array_delete_if(array $array, callable $block): array{
    // $return = [];
    // foreach($collection as $val) {
    //     if(!call_user_func($iterator, $val)) {$return[] = $val;}
    // }
    // return $return;
    // false will be skipped
    $array = array_values(array_filter($array, $block));
    return $array;
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
// $a_eq = ($a == $b); // TRUE if $a and $b have the same key/value pairs.
// $a_eq = ($a === $b); // TRUE if $a and $b have the same key/value pairs in the same order and of the same types.
//
// use array_values($a) == array_values($b)
// unique values, so remember that exist array_unique()
// two indexed arrays, which elements are in different order, using $a == $b or $a === $b fails, for example:
// ( ['x','y'] ==  ['y','x' ) === false;
// basic comparison works for associative arrays but will not work as expected with indexed arrays
// to compare either of them
function array_equal(array $a, array $b): bool {
    return (
        is_array($a) && is_array($b) &&
        count($a) == count($b) &&
        array_diff($a, $b) === array_diff($b, $a)
    );
}
// alias:
function array_is_equal(array $a, array $b): bool {return array_equal($a, $b);}
function array_equals(array $a, array $b): bool {return array_equal($a, $b);}
//
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
//Calculate the average
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
    ok(is_array_indexed([1, 2]), true, 'is_array_indexed 1');
    ok(is_array_indexed(['a' => 1, 'b' => 2]), false, 'is_array_indexed 2');
    ok(is_array_indexed([]), false, 'is_array_indexed empty');
    //
    $a = [1, 2, 3];
    $a = array_del($a, 0);
    ok(count($a), 2, 'array_del ' . implode(',', $a));
    //
    $a = [1, 2, 3];
    ok(array_first($a), 1, 'array_first');
    ok(array_last($a), 3, 'array_last');
    //
    ok(array_first($a, 2), [1, 2], 'array_first 2');
    ok(array_last($a, 2), [2, 3], 'array_last 2');
    //
    $a = ['a' => 0, 'b' => 1, 'c' => 2];
    ok(array_first($a), 0, 'array first');
    ok(array_last($a), 2, 'array last');
    ok(array_equals([], []), true, 'empty array equals');
    ok(array_equals([0, 1, 2, 3, 4], [0, 1, 2, 3, 4]), true, 'num array equals');
    ok(array_equals(['a' => 'a'], ['a' => 'a']), true, 'associative array equals');
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
}
