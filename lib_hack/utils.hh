<?hh // strict
namespace Utils;

/*
typed functions to use with hack instead of untyped base lib
*/


/**
 * On PHP7 and HHVM you can use "??". Use this function to support PHP5.
 */
function if_null<T>(?T $x, T $y): T {
  return $x === null ? $y : $x;
}

function concat<T>(array<T> $a, array<T> $b): array<T> {
  return \array_merge($a, $b);
}