<?php
// file, nomi file, percorsi
class File {
    /**
     * Returns true if the string is a valid filename
     * File names that start with a-Z or 0-9 and contain a-Z, 0-9, underscore(_), dash(-), and dot(.) will be accepted.
     * File names beginning with anything but a-Z or 0-9 will be rejected (including .htaccess for example).
     * File names containing anything other than above mentioned will also be rejected (file names with spaces won't be accepted).
     *
     * @param string $filename
     * @return bool
     */
    public static function isValidFilename($filename) {
        return (0 !== preg_match('/(^[a-zA-Z0-9]+([a-zA-Z_0-9.-]*))$/D', $filename));
    }
    // TEST: if(eregi('\.[a-z]{1,4}$', $file, $a_extension))  return $a_extension[0];
    // $filenameext = pathinfo($filename, PATHINFO_EXTENSION);
    function getExtension($f) {
        $a = explode('.', $f);
        $i = count($a) - 1;
        if ($i > 0) {
            return $a[$i];
        }
        return "";
    }
    // ottiene l'estensione del file, funziona solo se il file esiste, non se è una stringa arbitraria
    function getFileExtension($filename) {
        return pathinfo($filename, PATHINFO_EXTENSION);
    }
    function changeExtension($f, $ext) {
        $old = "." . self::getExtension($f);
        $ext = ".$ext";
        return str_replace($old, $ext, $f);
    }
    function stripExtension($file) {
        $ext = strrchr($file, '.'); // estraggo l'estensione
        $out_file = substr($file, 0, -strlen($ext)); // tolgo l'estensione
        return $out_file;
    }
    /*
    // creates a file ensuring every dir on the path provided is writable
    function write($file, $data) {
    $_dirname = dirname($file);
    //If the $file finish with / just createDir
    if ((($lastChar = substr($file, -1)) == '/') || ($lastChar == '\\')) {
    self::createDir($file);
    return true;
    } else {
    //asking to create the directory structure if needed.
    self::createDir($_dirname);
    }
    if (!@is_writable($_dirname)) {
    // cache_dir not writable, see if it exists
    if (!@is_dir($_dirname)) {
    trigger_error("directoryNotExists $_dirname");
    return false;
    }
    trigger_error("not Writable $file, $_dirname");
    return false;
    }
    // write to tmp file, then rename it to avoid
    // file locking race condition
    $_tmp_file = tempnam($_dirname, 'wrt');
    if (!($fd = @fopen($_tmp_file, 'wb'))) {
    $_tmp_file = $_dirname . '/' . uniqid('wrt');
    if (!($fd = @fopen($_tmp_file, 'wb'))) {
    trigger_error("error While Writing File: $file, $_tmp_file");
    return false;
    }
    }
    fwrite($fd, $data);
    fclose($fd);
    // Delete the file if it allready exists (this is needed on Win,
    // because it cannot overwrite files with rename())
    if (DIRECTORY_SEPARATOR == '/') { //unix
    if (file_exists($file)) {
    @unlink($file);
    }
    @copy($_tmp_file, $file);
    @unlink($_tmp_file);
    } else {
    @rename($_tmp_file, $file);
    }
    @chmod($file, 0644);
    return true;
    }
     */
    // assicura che la dir esista in ogni sua parte e sia scrivibile
    function createDir($dir) {
        if (!file_exists($dir)) {
            $_open_basedir_ini = ini_get('open_basedir');
            if (DIRECTORY_SEPARATOR == '/') {
                // unix-style paths
                $_dir = $dir;
                $_dir_parts = preg_split('!/+!', $_dir, -1, PREG_SPLIT_NO_EMPTY);
                $_new_dir = ($_dir{0} == '/') ? '/' : getcwd() . '/';
                if ($_use_open_basedir = !empty($_open_basedir_ini)) {
                    $_open_basedirs = explode(':', $_open_basedir_ini);
                }
            } else {
                // other-style paths
                $_dir = str_replace('\\', '/', $dir);
                $_dir_parts = preg_split('!/+!', $_dir, -1, PREG_SPLIT_NO_EMPTY);
                if (preg_match('!^((//)|([a-zA-Z]:/))!', $_dir, $_root_dir)) {
                    // leading "//" for network volume, or "[letter]:/" for full path
                    $_new_dir = $_root_dir[1];
                    // remove drive-letter from _dir_parts
                    if (isset($_root_dir[3])) {
                        array_shift($_dir_parts);
                    }
                } else {
                    $_new_dir = str_replace('\\', '/', getcwd()) . '/';
                }
                if ($_use_open_basedir = !empty($_open_basedir_ini)) {
                    $_open_basedirs = explode(';', str_replace('\\', '/', $_open_basedir_ini));
                }
            }
            // all paths use "/" only from here
            foreach ($_dir_parts as $_dir_part) {
                $_new_dir .= $_dir_part;
                if ($_use_open_basedir) {
                    // do not attempt to test or make directories outside of open_basedir
                    $_make_new_dir = false;
                    foreach ($_open_basedirs as $_open_basedir) {
                        if (substr($_new_dir, 0, strlen($_open_basedir)) == $_open_basedir) {
                            $_make_new_dir = true;
                            break;
                        }
                    }
                } else {
                    $_make_new_dir = true;
                }
                if ($_make_new_dir && !file_exists($_new_dir) && !@mkdir($_new_dir, 0771) && !is_dir($_new_dir)) {
                    trigger_error("error creating dir:$_new_dir");
                    return false;
                }
                $_new_dir .= '/';
            }
        }
    }
    /*
    assicura che la dir esista in ogni sua parte e sia scrivibile
    function createDir($dir) {
    if (!file_exists($dir)) {
    $_open_basedir_ini = ini_get('open_basedir');
    if (DIRECTORY_SEPARATOR == '/') {
    // unix-style paths
    $_dir = $dir;
    $_dir_parts = preg_split('!/+!', $_dir, -1, PREG_SPLIT_NO_EMPTY);
    $_new_dir = ($_dir{0} == '/') ? '/' : getcwd() . '/';
    if ($_use_open_basedir = !empty($_open_basedir_ini)) {
    $_open_basedirs = explode(':', $_open_basedir_ini);
    }
    } else {
    // other-style paths
    $_dir = str_replace('\\', '/', $dir);
    $_dir_parts = preg_split('!/+!', $_dir, -1, PREG_SPLIT_NO_EMPTY);
    if (preg_match('!^((//)|([a-zA-Z]:/))!', $_dir, $_root_dir)) {
    // leading "//" for network volume, or "[letter]:/" for full path
    $_new_dir = $_root_dir[1];
    // remove drive-letter from _dir_parts
    if (isset($_root_dir[3])) array_shift($_dir_parts);
    } else {
    $_new_dir = str_replace('\\', '/', getcwd()) . '/';
    }
    if ($_use_open_basedir = !empty($_open_basedir_ini)) {
    $_open_basedirs = explode(';', str_replace('\\', '/', $_open_basedir_ini));
    }
    }
    // all paths use "/" only from here
    foreach ($_dir_parts as $_dir_part) {
    $_new_dir.= $_dir_part;
    if ($_use_open_basedir) {
    // do not attempt to test or make directories outside of open_basedir
    $_make_new_dir = false;
    foreach ($_open_basedirs as $_open_basedir) {
    if (substr($_new_dir, 0, strlen($_open_basedir)) == $_open_basedir) {
    $_make_new_dir = true;
    break;
    }
    }
    } else {
    $_make_new_dir = true;
    }
    if ($_make_new_dir && !file_exists($_new_dir) && !@mkdir($_new_dir, 0771) && !is_dir($_new_dir)) {
    trigger_error("error creating dir:$_new_dir");
    return false;
    }
    $_new_dir.= '/';
    }
    }
    } */
    // determina se $file è + recente di $file_compare
    function is_newer($file, $file_compare) {
        if (filemtime($file) > filemtime($file_compare)) {
            return true;
        } else {
            return false;
        }
    }
    /*
    quando il file da includere è in qualche modo indicato dall'utente,
    occorre assicurarsi che sia all'interno di una whitelist
    $file = $_GET['filename']
    $allowedFiles = ['file1.txt','file2.txt','file3.txt'];
     */
    function whitelist_include($file, $allowedFiles) {
        //Include only files that are allowed.
        if (in_array((string) $file, $allowedFiles)) {
            include $file;
        } else {
            exit('not allowed');
        }
    }
    // elimina i file più vecchi di n giorni
    // è più vecchio di un numero specifico di giorni
    // filectime: Gets inode change time of file
    // filemtime: Gets file modification time
    public static function unlink_if_older($path, $n_days = 7, $mod = 'c') {
        if ($mod == 'c') {
            $s_diff = (time() - filectime($path));
        } elseif ($mod == 'm') {
            $s_diff = (time() - filemtime($path));
        } else {
            throw new Exception(sprintf('Errore %s "%s"', "modalità non accettata", $mod));
        }
        $s_days = 60 * 60 * 24 * $n_days;
        if (is_file($path)) {
            if ($s_diff > $s_days) {
                unlink($path);
            }
        } else {
            // file dont exists?
        }
    }
    // alias
    public static function removeOlder($path, $n_days = 7) {
        return self::unlink_if_older($path, $n_days, $mod = 'c');
    }
    // stabilisce se il file è più vecchio di una determinata soglia
    public static function isOlderThan($file_path, $hours) {
        if (!file_exists($file_path)) {
            // non esiste, va ricreato
            return true;
        }
        if (filesize($file_path) == 0) {
            // è vuoto, andrà ricreato
        }
        // time span di n ore
        $timespan = $hours * 60 * 60;
        $file_is_old = (time() - filectime($file_path)) >= $timespan;
        return $file_is_old;
    }
    // Windows compatible rename
    // rename() can not overwrite existing files on Windows
    // this function will use copy/unlink instead
    function rename($from, $to) {
        if (!@rename($from, $to)) {
            if (@copy($from, $to)) {
                chmod($to, 0777);
                @unlink($from);
                return true;
            }
            return false;
        }
        return true;
    }
    //
    // Search a file for matching lines
    //
    // This is probably not faster than file()+preg_grep() but less
    // memory intensive because not the whole file needs to be loaded
    // at once.
    //
    //
    // @param  string $file    The file to search
    // @param  string $pattern PCRE pattern
    // @param  int    $max     How many lines to return (0 for all)
    // @param  bool   $backref When true returns array with backreferences instead of lines
    // @return array matching lines or backref, false on error
    //
    public static function grep($file, $pattern, $max = 0, $backref = false) {
        $fh = @fopen($file, 'r');
        if (!$fh) {
            return false;
        }
        $matches = [];
        $cnt = 0;
        $line = '';
        while (!feof($fh)) {
            $line .= fgets($fh, 4096); // read full line
            if (substr($line, -1) != "\n") {
                continue;
            }
            // check if line matches
            if (preg_match($pattern, $line, $match)) {
                if ($backref) {
                    $matches[] = $match;
                } else {
                    $matches[] = $line;
                }
                $cnt++;
            }
            if ($max && $max == $cnt) {
                break;
            }
            $line = '';
        }
        fclose($fh);
        return $matches;
    }
    //
    // Get canonicalized absolute path or callback on non existing path
    //
    public static function realpath($path, $cb_on_missing) {
        if (file_exists($path)) {
            return realpath($path);
        } else {
            return $cb_on_missing($path);
        }
    }
}
class Dir {
    /**
     * Computes the difference of directories. Compares $target against $source and returns a relative path to all files
     * and directories in $target that are not present in $source.
     *
     * @param $source
     * @param $target
     *
     * @return string[]
     */
    public static function directoryDiff($source, $target) {
        $sourceFiles = self::globr($source, '*');
        $targetFiles = self::globr($target, '*');
        $sourceFiles = array_map(function ($file) use ($source) {
            return str_replace($source, '', $file);
        }, $sourceFiles);
        $targetFiles = array_map(function ($file) use ($target) {
            return str_replace($target, '', $file);
        }, $targetFiles);
        $diff = array_diff($targetFiles, $sourceFiles);
        return array_values($diff);
    }
    /**
     * Recursively find pathnames that match a pattern.
     *
     * See {@link http://php.net/manual/en/function.glob.php glob} for more info.
     *
     * @param string $sDir directory The directory to glob in.
     * @param string $sPattern pattern The pattern to match paths against.
     * @param int $nFlags `glob()` . See {@link http://php.net/manual/en/function.glob.php glob()}.
     * @return array The list of paths that match the pattern.
     * @api
     */
    public static function globr($sDir, $sPattern, $nFlags = null) {
        if (($aFiles = \_glob("$sDir/$sPattern", $nFlags)) == false) {
            $aFiles = array();
        }
        if (($aDirs = \_glob("$sDir/*", GLOB_ONLYDIR)) != false) {
            foreach ($aDirs as $sSubDir) {
                if (is_link($sSubDir)) {
                    continue;
                }
                $aSubFiles = self::globr($sSubDir, $sPattern, $nFlags);
                $aFiles = array_merge($aFiles, $aSubFiles);
            }
        }
        return $aFiles;
    }
}
