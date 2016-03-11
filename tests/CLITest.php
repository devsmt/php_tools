<?php


require_once dirname(__FILE__).'/../lib/Test.php';
require_once dirname(__FILE__).'/../lib/CLI.php';

$args = explode(' ','cli_test.php asdf asdf --help --dest=/var/ -asd -h --option mew arf moo -z');
/*
        Array
        (
            [input] => Array
                (
                    [0] => asdf
                    [1] => asdf
                )

            [commands] => Array
                (
                    [help] => 1
                    [dest] => /var/
                    [option] => mew arf moo
                )

            [flags] => Array
                (
                    [0] => asd
                    [1] => h
                    [2] => z
                )

        )
*/
$a_parsed = CLI::_parse($args);



is( $a_parsed['input'][0],           'asdf'            );
is( $a_parsed['input'][1],           'asdf'            );
is( $a_parsed['commands']['help'],   '1'               );
is( $a_parsed['commands']['dest'],   '/var/'           );
is( $a_parsed['commands']['option'], 'mew arf moo'     );
is( $a_parsed['flags'][0],           'asd'             );
is( $a_parsed['flags'][1],           'h'               );
is( $a_parsed['flags'][2],           'z'               );

