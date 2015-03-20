<?php

// funzione:
class Process {
    // Runs an external command with input and output pipes.
    // Returns the exit code from the process.
    // USO:  $r = io_exec('/usr/local/bin/php', '<?php print_r($_ENV); 
    ?>      ', $output);
// $output = Array
// (
//     [some_option] => aeiou
//     [PWD] => /tmp
//     [SHLVL] => 1
//     [_] => /usr/local/bin/php
// );
function io_exec($cmd, $input, &$output){
$descspec = array(
0=>array('pipe', 'r'),
1=>array('pipe', 'w'),
2=>array('pipe', 'w'));
$P = proc_open($cmd, $descspec, $pipes);
if(!$P) {
return -1;
}
// ignore stderr
fclose($pipes[2]);
fwrite($pipes[0], $input);
fclose($pipes[0]);
$output = stream_get_contents($pipes[1]);
fclose($pipes[1]);
return proc_close($P);
}
}
