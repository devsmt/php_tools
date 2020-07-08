<?php
/**
 * Jared Hancock <jared@osticket.com>
 * Copyright (c)  2014
 *
 * Lightweight implementation of the Python list in PHP. This allows for
 * treating an array like a simple list of items. The numeric indexes are
 * automatically updated so that the indeces of the list will alway be from
 * zero and increasing positively.
 *
 * Negative indexes are supported which reference from the end of the list.
 * Therefore $queue[-1] will refer to the last item in the list.
 */
class ListObject implements IteratorAggregate, ArrayAccess, Serializable, Countable {
    protected $storage = [];
    function __construct(array $array = []) {
        if (!is_array($array) && !$array instanceof Traversable) {
            throw new InvalidArgumentException('Traversable object or array expected');
        }
        foreach ($array as $v) {
            $this->storage[] = $v;
        }
    }
    /** @param mixed $what */
    function append($what): void {
        if (is_array($what)) {
            $this->extend($what);
        }
        $this->storage[] = $what;
    }
    /** @param mixed $what */
    function add($what): void{
        $this->append($what);
    }
    function extend(array $iterable): void {
        foreach ($iterable as $v) {
            $this->storage[] = $v;
        }
    }
    /** @param mixed $value */
    function insert(int $i, $value): void {
        if ($i < 0) {
            $i += count($this->storage) + 1;
        }
        array_splice($this->storage, $i, 0, [$value]);
    }
    /** @param mixed $value */
    function remove($value): void {
        if (!($k = $this->index($value))) {
            throw new OutOfRangeException('No such item in the list');
        }
        unset($this->storage[$k]);
    }
    /** @param mixed $at
     * @return mixed */
    function pop($at = false) {
        if ($at === false) {
            return array_pop($this->storage);
        } elseif (!isset($this->storage[$at])) {
            throw new OutOfRangeException('Index out of range');
        } else {
            $rv = array_splice($this->storage, $at, 1);
            return $rv[0];
        }
    }
    /** @return mixed */
    function slice(int $offset, int $length = null) {
        return array_slice($this->storage, $offset, $length);
    }
    /** @param mixed $replacement
     * @return mixed */
    function splice(int $offset, int $length = 0, $replacement = []) {
        return array_splice($this->storage, $offset, $length, $replacement);
    }
    /** @param mixed $value */
    function index($value): int {
        return array_search($this->storage, $value);
    }
    /**
     * Sort the list in place.
     *
     * Parameters:
     * $key - (callable|int) A callable function to produce the sort keys
     *      or one of the SORT_ constants used by the array_multisort
     *      function
     * $reverse - (bool) true if the list should be sorted descending
     *
     * @param callable|int $key
     * @param bool $reverse
     */
    function sort($key = 0, $reverse = false): void {
        if (is_callable($key)) {
            $keys = array_map($key, $this->storage);
            array_multisort($keys, $this->storage,
                $reverse ? SORT_DESC : SORT_ASC);
        } elseif ($key) {
            array_multisort($this->storage,
                $reverse ? SORT_DESC : SORT_ASC, $key);
        } elseif ($reverse) {
            rsort($this->storage);
        } else {
            sort($this->storage);
        }
    }
    function reverse(): array{
        return array_reverse($this->storage);
    }
    function filter(callable $callable): self{
        $new = new static();
        foreach ($this->storage as $i => $v) {
            if ($callable($v, $i)) {
                $new->add($v);
            }
        }
        return $new;
    }
    // IteratorAggregate
    function getIterator(): ArrayIterator {
        return new ArrayIterator($this->storage);
    }
    // Countable
    function count(int $mode = COUNT_NORMAL): int {
        return count($this->storage, $mode);
    }
    // ArrayAccess
    /** @return mixed */
    function offsetGet(int $offset) {
        if (!is_int($offset)) {
            throw new InvalidArgumentException('List indices should be integers');
        } elseif ($offset < 0) {
            $offset += count($this->storage);
        }
        if (!isset($this->storage[$offset])) {
            throw new OutOfBoundsException('List index out of range');
        }
        return $this->storage[$offset];
    }
    /**
    @param int|null $offset
    @param mixed $value
     */
    function offsetSet($offset, $value): void {
        if ($offset === null) {
            $this->storage[] = $value;
        } elseif (!is_int($offset)) {
            throw new InvalidArgumentException('List indices should be integers');
        } elseif ($offset < 0) {
            $offset += count($this->storage);
        }
        if (!isset($this->storage[$offset])) {
            throw new OutOfBoundsException('List assignment out of range');
        }
        $this->storage[$offset] = $value;
    }
    function offsetExists(int $offset): bool {
        if (!is_int($offset)) {
            throw new InvalidArgumentException('List indices should be integers');
        } elseif ($offset < 0) {
            $offset += count($this->storage);
        }
        return isset($this->storage[$offset]);
    }
    function offsetUnset(int $offset): void {
        if (!is_int($offset)) {
            throw new InvalidArgumentException('List indices should be integers');
        } elseif ($offset < 0) {
            $offset += count($this->storage);
        }
        unset($this->storage[$offset]);
    }
    // Serializable
    function serialize(): string {
        return serialize($this->storage);
    }
    /** @param mixed $what */
    function unserialize($what): void{
        $this->storage = unserialize($what);
    }
    function __toString(): string {
        return '[' . implode(', ', $this->storage) . ']';
    }
}
