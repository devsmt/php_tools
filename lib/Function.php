<?php
// Tries a series of functions and returns array of their results.
function tryall(array $a_clusure, array $params = []) {
}

// Tries a series of functions and returns the first non empty result
function trysome(array $a_clusure, array $params = []) {
}
//  run the tests:
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/Test.php';
}