<?php

// Set: unordered collection of objects in which each object can appear only once. "testing for membership"
// Dictionary: store and retrieve objects using key-value pairs.
class Arr {

    function first($a) {
        if (Arr::isAssociative($a)) {
            foreach ($a as $k => $v) {
                return $v;
            }
        } else {
            return reset($a);
        }
    }

    function last($a) {
        if (is_array($a)) {
            if (Arr::isAssociative($a)) {
                $keys = array_keys($a);
                $last_key = end($keys);
                return $a[$last_key];
            } else {
                return end($a);
            }
        }
    }
    // se c'è anche solo una chiave int è assoc
    function isAssociative($a) {
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
    function isAssociative2(array $a): bool{
        $i = 0;
        foreach ($a as $k => $v) {
            if ($k !== $i++) {
                return true;
            }
        }
        return false;
    }

    function isSequential($var) {
        return (array_merge($var) === $var && is_numeric(implode(array_keys($var))));
    }

    // determina se la chiave e' disponibile e se non lo fosse restituisce $default
    // $k può essere un'array di chiavi
    function get($a, $k, $default = '') {
        if (isset($a[$k])) {
            return $a[$k];
        } else {
            return $default;
        }
    }

    // assicura che tutto ciò che è in $compare sia in $a
    function equals($a, $compare) {
        foreach ($compare as $k => $v) {
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

    function del(&$a, $k) {
        unset($a[$k]);
        return $a;
    }

    function deleteEmpty($a) {
        foreach ($a as $i => $value) {
            if (is_null($value) || $value === '') {
                unset($a[$i]);
            }
        }
        return $a;
    }

    /*
    $records = array(array('a' => 'y', 'b' => 'z', 'c' => 'e'), array('a' => 'x', 'b' => 'w', 'c' => 'f'));
    $subset1 = array_collect($records, 'a'); // $subset1 will be: array(array('a' => 'y'), array('a' => 'x'));
    $subset2 = array_collect($records, array('a', 'c')); // $subset2 will be: array(array('a' => 'y', 'c' => 'e'), array('a' => 'x', 'c' => 'f'));
     */

    function collect($array, $params) {
        $return = [];
        if (!is_array($params)) {
            $params = array($params);
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

    function isEmpty($a) {
        return count($a) == 0;
    }

    /* ----------------------------------------------------------------
    Ruby sugar
    ---------------------------------------------------------------- */
    /*
    array.compact ? an_array
    Returns a copy of self with all nil elements removed.

    [ "a", nil, "b", nil, "c", nil ].compact
    #=> [ "a", "b", "c" ]
     */

    function compact($a) {
        return Arr::reject($a, function ($v) {
            return $v == null;
        });
    }

    // array.reject {|item| block } ? an_array
    // Returns a new array containing the items in self for which the block is not true.
    function reject($a, $f) {

    }

    /*
    array.select {|item| block } ? an_array
    Invokes the block passing in successive elements from array, returning an array containing those elements for which the block returns a true value (equivalent to Enumerable#select).

    a = %w{ a b c d e f }
    a.select {|v| v =~ /[aeiou]/}   #=> ["a", "e"]
     */

    function select($a, $f) {

    }

    /*
    array.uniq ? an_array
    Returns a new array by removing duplicate values in self.

    a = [ "a", "a", "b", "b", "c" ]
    a.uniq   #=> ["a", "b", "c"]
     */

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

    function array_pluck($key, $data) {
        return array_reduce($data, function ($result, $array) use ($key) {
            isset($array[$key]) &&
            $result[] = $array[$key];
            return $result;
        }, []);
    }

    // ritorna un array dei valori di una chiave
    function getKeyValues($key, $input) {
        return self::pluck($key, $input);
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
}

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

// persiste un array in un hash file database
// $pa = new PersistentArray(__DIR__.'/test.cdb');
// $pa['key'] = time();
// foreach( $pa as $k => $v) { }
class PersistentArrayF implements ArrayAccess, Iterator {
    private $db;
    private $current;
    function __construct($path) {
        $this->db = dba_popen($path, "c", "flatfile");
        if (!$this->db) {
            throw new Exception("$path could not be opened");
        }
    }
    function __destruct() {
        dba_close($this->db);
    }
    function offsetExists($index) {
        return dba_exists($index, $this->db);
    }
    function offsetGet($index) {
        return unserialize(dba_fetch($index, $this->db));
    }
    function offsetSet($index, $newval) {
        dba_replace($index, serialize($newval), $this->db);
        return $newval;
    }
    function offsetUnset($index) {
        return dba_delete($index, $this->db);
    }
    function rewind() {
        $this->current = dba_firstkey($this->db);
    }
    function current() {
        $key = $this->current;
        if ($key !== false) {
            return $this->offsetGet($key);
        }
    }
    function next() {
        $this->current = dba_nextkey($this->db);
    }
    function valid() {
        return ($this->current == false) ? false : true;
    }
    function key() {
        return $this->current;
    }
    // aggiunge i valori povenienti da un altro hash
    function merge(array $a_hash) {
        foreach ($a_hash as $k => $v) {
            $this->offsetSet($k, $v);
        }
    }
}

// persist an array in memory using APC
/*
class PersistentArrayM implements ArrayAccess, Iterator {
}
 */
