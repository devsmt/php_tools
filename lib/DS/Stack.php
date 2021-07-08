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
    /**
     * @param mixed $item
     */
    public function add($item): Stack{
        $this->stack[] = $item;
        return $this;
    }
    /** @return mixed */
    public function next() {
        return array_pop($this->stack);
    }
    public function count(): int{
        $c = count($this->stack);
        return ($c >= 1) ? $c : 0;
    }
    /**
     * @param mixed $item
     */
    public function contains($item, bool $strict = false): bool {
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
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/../Test.php';
    $stack = new Stack();

    $stack->add('apples')
        ->add('oranges')
        ->add('pears')
        ->add('strawberries');

    while ($x = $stack->next()) {
        ok($x, '$stack->next: "' . $x);
    }
}