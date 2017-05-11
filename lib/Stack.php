<?php

// assicura che l'ultimo elemento inserito sia il primo ad essere ritornato
/* uso

$stack = new Stack();

$stack->add('apples')
->add('oranges')
->add('pears')
->add('strawberries');

echo '<pre>', print_r($stack), "\n\n";

while ($x = $stack->next() ) {
echo '$stack->next: "', $x, "\"\n";
}
echo '</pre>';
 */
class Stack {

    private $stack = [];

    public function add($item) {
        $this->stack[] = $item;
        return $this;
    }

    public function next() {
        return array_pop($this->stack);
    }

    public function count() {
        return ($c = count($this->stack) >= 1) ? $c : 0;
    }

    public function contains($item, $strict = false) {
        return in_array($item, $this->stack, $strict) ? true : false;
    }

}

/*
$q = new SplQueue();
$q->setIteratorMode(SplQueue::IT_MODE_DELETE);

// ... enqueue some tasks on the queue ...

// process them
foreach ($q as $task) {
// ... process $task ...

// add new tasks on the queue
$q[] = $newTask;
// ...
}
 */

class Queue extends SplQueue {

}
