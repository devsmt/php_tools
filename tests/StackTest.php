<?php

require_once dirname(__FILE__).'/../lib/Stack.php';
$stack = new Stack();

$stack->add('apples')
      ->add('oranges')
      ->add('pears')
      ->add('strawberries');


while ($x = $stack->next() ) {
    ok( $x, '$stack->next: "'.$x);
}
