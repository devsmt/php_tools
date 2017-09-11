<?php

require_once __DIR__.'/../lib/Test.php';
require_once __DIR__.'/../lib/Strings.php';

$r=str_template('second: {{second}}; first: {{first}}', [
    'first'  => '1st',
    'second' => '2nd'
]);
$e = 'second: 2nd; first: 1st';
is($r, $e, 'str_template');


$s = str_replace_last('.', ".bb.", '.....aaaa.exe');
is($s, ".....aaaa.bb.exe", "str_replace_last");