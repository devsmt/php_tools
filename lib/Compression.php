<?php

/*

NOTA:

compress.zlib://file.gz
compress.bzip2://file.bz2
zip://archive.zip#dir/file.txt

$input = "test.txt";
$output = $input.".gz";
file_put_contents("compress.zlib://$output", file_get_contents($input));

`bzip2 -z -k -f -v --best /path/to/file.log`;
 */

abstract class CompressionEngine {

    function compress($file, $destination = '', $options = []) {

    }

    function expand($file, $out_file = '') {

    }

}

class CompressionEngineGZ extends CompressionEngine {

    //
    function compress($file, $destination = '', $options = []) {
        $level = 5;
        //echo "compressing $file \n";
        if (empty($destination)) {
            $destination = $file . ".gz";
        }
        if (file_exists($file)) {
            $filesize = filesize($file);
            $file_handle = fopen($file, "r");
            chmod($file, 755);
            if (!file_exists($destination)) {
                $destination_handle = gzopen($destination, "w$level");
                while (!feof($file_handle)) {
                    $chunk = fread($file_handle, 2048);
                    gzwrite($destination_handle, $chunk);
                }
                fclose($file_handle);
                gzclose($destination_handle);
                return true;
            } else {
                error_log("$destination already exists, deleting...");
                if (unlink($destination)) {
                    return compress($file, $level, $destination);
                }
            }
        } else {
            error_log("$file doesn't exist");
        }
        return false;
    }

    /*
    // GZIPs a file on disk (appending .gz to the name)
    function gzCompressFile($source, $dest='', $opt = []) {

    $option = array_merge( array(
    'level' => 9
    ), $opt );
    extract($option);

    if( empty($dest) ) {
    $dest = $source . '.gz';
    }

    $mode = 'wb' . $level;
    $error = false;
    if ($fp_out = gzopen($dest, $mode)) {
    if ($fp_in = fopen($source,'rb')) {
    while (!feof($fp_in)){
    gzwrite($fp_out, fread($fp_in, 1024 * 512));
    }
    fclose($fp_in);
    } else {
    $error = true;
    }
    gzclose($fp_out);
    } else {
    $error = true;
    }
    if ($error) {
    return false;
    } else {
    return $dest;
    }
    }
     */

    // un file con estensione .gz, viene letto decompresso in memoria e salvato senza estensione
    function expand($file, $out_file = '') {
        //echo "expanding $file \n";
        $buffer = '';
        $gzh = gzopen($file, "r");
        if ($gzh) {
            while (!gzeof($gzh)) {
                $buffer .= gzgets($gzh, 4096);
            }
            gzclose($gzh);
            if (empty($out_file)) {
                $ext = strrchr($file, '.'); // estraggo l'estensione
                $out_file = substr($file, 0, -strlen($ext)); // tolgo l'estensione
            }
            return file_put_contents($out_file, $buffer);
        } else {
            //echo "ko reading $file\n";
        }
    }

}

class CompressionEngineBZ extends CompressionEngine {

    function expand($file, $out_file = '') {
        $bz = bzopen($file, "r");
        $str = '';
        while (!feof($bz)) {
            //8192 seems to be the maximum buffersize?
            $str = $str . bzread($bz, 8192);
        }
        bzclose($bz);
        return file_put_contents($out_file, $str);
    }

}

class Compression {

    static function compress($f, $nf) {
        return $c->compress($f, $nf);
    }

    static function expand($f, $nf) {
        return $c->expand($f, $nf);
    }

}
