<?php

class OS {
    public static function isWindows(){
        return substr(PHP_OS, 0, 3) == 'WIN';
    }


    /*
    $cmd = 'php';
    $input =  <<<__END__
    <?php print_r( ['a'=>1, 'b'=>2] );
    __END__;
    list($success, $result) = OS_call( $cmd, $input );
    echo "command returned $success $result \n";
    */
    // richiama un programma,
    // passa una stringa sul STDIN del child,
    // legge una stringa da STDOUT del child
    // ritorna bool success e la stringa di risultato
    function call( $cmd, $input ) {
        $err_file = sprintf("/tmp/%s_error_%s.txt", __FUNCTION__, date('Ymd_His') );
        $descriptorspec = [
            0 => ["pipe", "r"],  // stdin is a pipe that the child will read from
            1 => ["pipe", "w"],  // stdout is a pipe that the child will write to
            2 => ["file", $err_file, "a"] // stderr is a file to write to
            ];
        $cwd = '/tmp';
        $env = [];
        $process = proc_open($cmd, $descriptorspec, $pipes, $cwd, $env);
        if (is_resource($process)) {
            // $pipes now looks like this:
            // 0 => writeable handle connected to child stdin
            // 1 => readable handle connected to child stdout
            // Any error output will be appended to $err_file
            fwrite($pipes[0], $input);
            fclose($pipes[0]);
            //
            $result = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            // It is important that you close any pipes before calling
            // proc_close in order to avoid a deadlock
            $return_value = proc_close($process);
            if( $return_value == 0 ) {
                unlink($err_file);
                return [true, $result];
            } else {
                return [false, file_get_contents( $err_file ) ];
            }
        }
    }
    // passa un array di dati serializzato al programma che ci si aspetti restituisca una strina json
    // OS_call_response: programma chiamante e chiamato devono accordarsi su un protocollo comune di risposta
    function call_serial(string $command, array $data):array {
        $input = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if( $json === false ) { die( json_last_error_msg() ); }
        list($return_value, $json_str) = OS_call( $cmd, $input );
        if( $return_value ) {
            $a_result = json_decode($json_str, $use_assoc=true);
            return $a_result;
        }
    }
    // implementa una response standard da usare sia nel Host che nel Guest
    function call_response(bool $success, $data, array $errors ){}

}