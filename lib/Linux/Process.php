<?php

// get the number of cores
function getProcs() {
   $procs = 1;
   if (file_exists('/proc/cpuinfo')) {
      $procs = preg_match_all('/^processor\s/m', file_get_contents('/proc/cpuinfo'), $discard);
   }
   $procs <<= 1;
   return $procs;
}


// funzione:
class IOPipesProcess {
    // Runs an external command with input and output pipes.
    // Returns the exit code from the process.
    // USO:  $r = self::exec('/usr/local/bin/php', '<?php print_r($_ENV); ?>', $output);
    // $output = Array
    // (
    //     [some_option] => aeiou
    //     [PWD] => /tmp
    //     [SHLVL] => 1
    //     [_] => /usr/local/bin/php
    // );
    public static function exec($cmd, $input, &$output){
        $descspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
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
