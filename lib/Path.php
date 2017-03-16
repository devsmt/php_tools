<?php

/* cross platform dir manager */
class Path {

    // lista di directory o array lista di directory o stringhe da spezzare per avere una directory
    // Path::join('var','www');
    // Path::join(array('var','www'));
    // Path::join('var/www','public');
    // Path::join(array('var/www','public'));
    // Path::join('D:\www\webroot', 'template', index.php)
    function join() {
        $num_args = func_num_args();
        $args = func_get_args();
        $path = '';
        if (is_string($args[0])) {
            $a = $args;
        } elseif (is_array($args[0])) {
            $a = $args[0];
        }
        for ($i = 0; $i < count($a); $i++) {
            //se il primo carattere e' uno slash, toglilo
            if ($a[$i][0] == DIRECTORY_SEPARATOR) {
                $a[$i] = substr($a[$i], 1);
            }
            //se l'ultimo carattere e' uno slash, toglilo
            if (substr($a[$i], -1, 1) == DIRECTORY_SEPARATOR) {
                $a[$i] = substr($a[$i], 0, -1);
            }
            if (stristr(PHP_OS, 'win')) {
                // win non inizia il path con /
                if ($i > 0) {
                    $path.= DIRECTORY_SEPARATOR;
                }
            } else {
                // unix style path
                $path.= DIRECTORY_SEPARATOR;
            }
            $path.= $a[$i];
        }
        // elimina possibili doppi slash dovuti a concatenazioni precedenti
        $path = str_replace(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $path);
        return Path::real($path);
    }

    // Because realpath() does not work on files *that do not already exist*
    // It replaces (consecutive) occurences of / and \\ with
    // whatever is in DIRECTORY_SEPARATOR, and processes /. and /.. fine.
    //
    // (back)slash at position 0 (beginning of the string) or position -1 (ending)
    // are preserved
    function real($path) {
        $result = '';
        $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
        if ($path[0] == '/') {
            $result.= '/';
        }
        $result_append = '';
        if (substr($path, -1) == '/') {
            $result_append = '/';
        }
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $absolutes = array();
        foreach ($parts as $part) {
            if ('.' == $part) {
                continue;
            }
            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }
        $result.= implode(DIRECTORY_SEPARATOR, $absolutes);
        return $result . $result_append;
    }

    // toglie document root dal path del file passato come argomento
    // in modo da ottenere un link url assoluto
    function toAbsUrl($path) {


        /*
          //$realpath=str_replace("\\", "/", realpath($path));

          // path alla cartella dove è intallata la lib
          $lib_root_dir = Path::join(__DIR__, '..');
          // trova la sottocartella dove è installata la lib
          $root = str_replace($_SERVER['DOCUMENT_ROOT'], '', $lib_root_dir);
         */
        return '/' . str_replace($_SERVER['DOCUMENT_ROOT'], '', $path);
    }

    function toAbsURI($path) {
        if (preg_match("/^(\w+)[:]\/\//i", $path, $matches)) {
            return $path;
        } else {
            $protocol = "http" . (isset($_SERVER["HTTPS"]) && strtolower($_SERVER["HTTPS"]) == "on" ? "s" : "");
            if ($path{0} == '/') {
                $directory = "";
            } else {
                $directory = dirname($_SERVER["SCRIPT_NAME"]);
            }
            return "$protocol://" . $_SERVER["HTTP_HOST"] . $directory . (!in_array(substr($directory, -1), array("\\", "/")) && !in_array(substr($path, 0, 1), array("\\", "/")) ? "/" : "") . $path;
        }
    }

    // mappa la struttura del ramo di dir, ogni nodo è rappresentato in memoria
    // come un array
    // @param filter permette di definire una funzione per filtrare la lista
    // vedi fnmatch()
    //  togli file che iniziano con '.'
    // function ls_filter_hidden($d,$f){  return !eregi('^\.[a-z]',$f); }
    // @param action permette di specificare una funzione da eseguire sul file specificato
    function ls($dir, $filter = '', $action = '') {
        $dir_struct = array();
        if (@is_dir($dir)) {
            $dir_h = @opendir($dir);
            while ($file = readdir($dir_h)) {
                if (($file != ".") && ($file != "..") && (($filter != '') ? ($filter($dir, $file)) : (true))) {
                    $cur_file = "$dir/$file";
                    $dir_struct[$file] = stat($cur_file);
                    //$dir_struct[$file]['filetype'] = filetype($cur_file);//fifo, char, dir, block, link, file e unknown
                    $dir_struct[$file]['extension'] = file_extension($cur_file);
                    if (@is_dir($cur_file)) {
                        $dir_struct[$file] = ls($cur_file);
                        //$dir_struct[$file]['filetype'] = filetype($cur_file);//fifo, char, dir, block, link, file e unknown
                    }
                }
            } // end while
            closedir($dir_h);
        }
        return $dir_struct;
    }

    //     Normalize the case of a pathname. On Unix, this returns the path unchanged; on case-insensitive filesystems, it converts the path to lowercase.
    //  On Windows, it also converts forward slashes to backward slashes.
    function normcase($path) {

    }

    //  Normalize a pathname. This collapses redundant separators and up-level references so that A//B, A/./B and A/foo/../B all become A/B.
    //  It does not normalize the case (use normcase() for that). On Windows, it converts forward slashes to backward slashes. It should be understood that this
    //  may change the meaning of the path if it contains symbolic links!
    /*
      ritorna un path pulito di eventuali irregolarità
     */
    function normpath($path) {
        $path = ($path != "") ? $path : $this->path;
        // Sanity check
        if ($path == "") {
            return false;
        }
        // Converts all "\" to "/", and erases blank spaces at the beginning and the ending of the string
        $path = trim(preg_replace("/\\\\/", "/", (string) $path));
        /*  Checks if last parameter is a directory with no slashs ("/") in the end. To be considered a dir,
         *   it can't end on "dot something", or can't have a querystring ("dot something ? querystring")
         */
        if (!preg_match("/(\.\w{1,4})$/", $path) && !preg_match("/\?[^\\/]+$/", $path) && !preg_match("/\\/$/", $path)) {
            $path.= '/';
        }
        /*   Breaks the original string in to parts: "root" and "dir".
         *    "root" can be "C:/" (Windows), "/" (Linux) or "http://www.something.com/" (URLs). This will be the start of output string.
         *    "dir" can be "Windows/System", "root/html/examples/", "includes/classes/class.validator.php", etc.
         */
        preg_match_all("/^(\\/|\w:\\/|(http|ftp)s?:\\/\\/[^\\/]+\\/)?(.*)$/i", $path, $matches, PREG_SET_ORDER);
        $path_root = $matches[0][1];
        $path_dir = $matches[0][3];
        /*  If "dir" part has one or more slashes at the beginning, erases all.
         *   Then if it has one or more slashes in sequence, replaces for only 1.
         */
        $path_dir = preg_replace(array("/^\\/+/", "/\\/+/"), array("", "/"), $path_dir);
        // Breaks "dir" part on each slash
        $path_parts = explode("/", $path_dir);
        // Creates a new array with the right path. Each element is a new dir (or file in the ending, if exists) in sequence.
        for ($i = $j = 0, $real_path_parts = array(); $i < count($path_parts); $i++) {
            if ($path_parts[$i] == '.') {
                continue;
            } else if ($path_parts[$i] == '..') {
                if ((isset($real_path_parts[$j - 1]) && $real_path_parts[$j - 1] != '..') || ($path_root != "")) {
                    array_pop($real_path_parts);
                    $j--;
                    continue;
                }
            }
            array_push($real_path_parts, $path_parts[$i]);
            $j++;
        }
        return $path_root . implode("/", $real_path_parts);
    }

    //Return True if both pathname arguments refer to the same file or directory (as indicated by device number and i-node number). Raise an exception if a os.stat() call on either pathname fails. Availability: Macintosh, Unix.
    function samefile($path1, $path2) {

    }

    //Split the pathname path into a pair, (head, tail) where tail is the last pathname component and head is everything leading up to that. The tail part will never contain a slash; if path ends in a slash, tail will be empty. If there is no slash in path, head will be empty. If path is empty, both head and tail are empty. Trailing slashes are stripped from head unless it is the root (one or more slashes only). In nearly all cases, join(head, tail) equals path (the only exception being when there were multiple slashes separating head from tail).
    function split($path) {

    }

    /*

      date due url, trova il path per raggiungere 2 partendo da 1

      function findRelativePath($path_1, $path_2)
      {
      if ($path_1 == ""  ||  $path_2 == "")
      {
      return false;
      }

      $path_1 = $this->fix($path_1);
      $path_2 = $this->fix($path_2);

      preg_match_all("/^(\\/|\w:\\/|https?:\\/\\/[^\\/]+\\/)?(.*)$/i", $path_1, $matches_1, PREG_SET_ORDER);
      preg_match_all("/^(\\/|\w:\\/|https?:\\/\\/[^\\/]+\\/)?(.*)$/i", $path_2, $matches_2, PREG_SET_ORDER);

      if ($matches_1[0][1] != $matches_2[0][1])
      {
      return false;
      }

      $path_1_parts = explode("/", $matches_1[0][2]);
      $path_2_parts = explode("/", $matches_2[0][2]);


      while (isset($path_1_parts[0])  &&  isset($path_2_parts[0]))
      {
      if ($path_1_parts[0] != $path_2_parts[0])
      {
      break;
      }

      array_shift($path_1_parts);
      array_shift($path_2_parts);
      }


      for ($i = 0, $path = ""; $i < count($path_1_parts)-1; $i++)
      {
      $path .= "../";
      }

      return $path . implode("/", $path_2_parts);
      }
     */
    /*
      assicura che la dir esista in ogni sua parte e sia scrivibile
     */

    function ensure($dir) {
        return File::createDir($dir);
    }

    function stripExtension($file) {
        return File::stripExtension($file);
    }

    // TEST: if(eregi('\.[a-z]{1,4}$', $file, $a_extension))  return $a_extension[0];
    function getExtension($f) {
        return File::getExtension($f);
    }

    function changeExtension($f, $ext) {
        return File::changeExtension($f, $ext);
    }

}
