<?php

// crea header HTTP
class ResponseHeader {

    public static function NotFound($use_html) {
        header("HTTP/1.0 404 Not Found");
        if ($use_html) {
            $html = '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">' . "n" .
                    '<html><head>' . "n" .
                    '<title>404 Not Found</title>' . "n" .
                    '</head><body>' . "n" .
                    '<h1>Not Found</h1>' . "n" .
                    '<p>The requested URL ' .
                    str_replace(strstr($_SERVER['REQUEST_URI'], '?'), '', $_SERVER['REQUEST_URI']) .
                    ' was not found on this server.</p>' . "n" .
                    '</body></html>' . "n";
            die($html);
        }
    }

    // autoset image mime type
    public static function mime($path) {
        // un modo migliore:
        // image_type_to_mime_type(exif_imagetype($path));
        if (file_exists($path) && is_readable($path)) {
            // get the filename extension
            $ext = substr($path, -3);
            // set the MIME type
            switch ($ext) {
                case 'jpg':
                    $mime = 'image/jpeg';
                    break;
                case 'gif':
                    $mime = 'image/gif';
                    break;
                case 'png':
                    $mime = 'image/png';
                    break;
                default:
                    $mime = image_type_to_mime_type(exif_imagetype($path));
            }
            // if a valid MIME type exists, display the image
            // by sending appropriate headers and streaming the file
            header('Content-type: ' . $mime);
            header('Content-length: ' . filesize($path));
        }
    }

    // helper function: Send headers and returns an image.
    public static function sendImage($filename, $browser_cache) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($extension, array('png', 'gif', 'jpeg'))) {
            header("Content-Type: image/".$extension);
        } else {
            header("Content-Type: image/jpeg");
        }
        header("Cache-Control: public, max-age=".$browser_cache);
        header('Expires: '.gmdate('D, d M Y H:i:s', time()+$browser_cache).' GMT');
        header('Content-Length: '.filesize($filename));
        readfile($filename);
        exit();
    }

    //----------------------------------------------------------------------------
    //  cache Headers
    //----------------------------------------------------------------------------
    // forza il browser a scaricare la risorsa ad ogni chiamata
    public static function cacheNone() {
        $gm_mod = gmdate('D, d M Y H:i:s') . ' GMT';
        header("Last-Modified: $gm_mod");
        header("Pragma: no-cache");
        //-------------------------------------------------
        // Backwards Compatibility for HTTP/1.0 clients
        header("Expires: 0");
        // header("Expires: $gm_mod");//expires now
        // header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // expires Date in the past
        //-------------------------------------------------
        // HTTP/1.1 support
        header("Cache-Control: no-cache,no-store,max-age=0,s-maxage=0");
        // header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
    }

    /*
      verificare come si usa
      public static function noValidate($s_interval = 60) {
      $t_now = time();
      $gm_lmtime = gmdate('D, d M Y H:i:s', $t_now) . ' GMT';
      $gm_extime = gmdate('D, d M Y H:i:s', $t_now + $s_interval) . ' GMT';
      // Backwards Compatibility for HTTP/1.0 clients
      header("Last Modified: $gm_lmtime");
      header("Expires: $gm_extime");
      // HTTP/1.1 support
      header("Cache-Control: public,max-age=$s_interval");
      }
     */

    /*
      // usa ::cacheControl() o ::ImgSend()
      // forza il browser a 1) assegnare all'oggetto la data di modifica, 2) inviare l'header HTTP_IF_MODIFIED_SINCE
      public static function validate_cache_headers($t_mod) {
      $gm_mod = gmdate('D, d M Y H:i:s', $t_mod) . ' GMT';
      if ($_SERVER['IF_MODIFIED_SINCE'] == $gm_mod) {
      header("HTTP/1.1 304 Not Modified");
      exit;
      } else {
      header("Cache-Control: must-revalidate");
      header("Last-Modified: $gm_mod");
      // followsw document content
      }
      }
     */

    /*
      Some information on the Cache-Control header is as follows

      HTTP 1.1. Allowed values = PUBLIC | PRIVATE | NO-CACHE | NO-STORE.

      Public - may be cached in public shared caches.
      Private - may only be cached in private cache.
      No-Cache - may not be cached.
      No-Store - may be cached but not archived.

      The directive CACHE-CONTROL:NO-CACHE indicates cached information should not be
      used and instead requests should be forwarded to the origin server.
      This directive has the same semantics as the PRAGMA:NO-CACHE.

      Clients SHOULD include both PRAGMA: NO-CACHE and CACHE-CONTROL: NO-CACHE when
      a no-cache request is sent to a server not known to be HTTP/1.1 compliant.
      Also see EXPIRES.
     */

    public static function cacheControl($do_cache, $s_delay = null) {
        //set headers to NOT cache a page
        if (!$do_cache) {
            header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
            header("Pragma: no-cache"); //HTTP 1.0
            header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
        } else {
            // or, if you DO want a file to cache, use:
            if (empty($s_delay)) {
                $s_delay = (60 * 60 * 24 * 1);
            }
            // Client is told to cache these results for set duration
            header('Cache-Control: public,max-age='.$s_delay.',must-revalidate');
            header('Expires: '.gmdate('D, d M Y H:i:s',(time()+$s_delay)).' GMT');
            header('Last-modified: '.gmdate('D, d M Y H:i:s',time()).' GMT');
            // Pragma header removed should the server happen to set it automatically
            // Pragma headers can make browser misbehave and still ask data from server
            header_remove('Pragma');
        }
    }

    // se il browser detiene una copia valida, rispondiamo di usarla
    public static function cacheControl($file_path = null, $t_mod = null) {
        if (empty($file_path)) {
            $file_path = $_SERVER['SCRIPT_FILENAME'];
        }

        // se non è specificato un mtime arbitrario(es. prov. del DB), usa mtime del path
        if (empty($t_mod)) {
            $t_mod = filemtime($file_path);
        }
        $gm_mod = gmdate('D, d M Y H:i:s', $t_mod) . ' GMT';


        // cache per n gg
        $s_delay = (60 * 60 * 24 * 7);
        $t_delay = time() + $s_delay;
        $gm_delay = date('D, d M Y H:i:s', $t_delay);

        // del file diamo sempre le info necessarie al browser per validarsi i propri oggetti
        header("Cache-Control: max-age=$s_delay, public");
        header("Pragma: cache");
        header("Last-Modified: $gm_mod");
        header("Expires: $gm_delay GMT");


        $if_modified_since = '';
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $if_modified_since = preg_replace('/;.*$/', '', $_SERVER['HTTP_IF_MODIFIED_SINCE']);
        }
        // se il documento è stato inviato da noi(ha il nostro gm_mod), riusa da cache
        if ($if_modified_since == $gm_mod) {
            header("HTTP/1.0 304 Not Modified");
            exit;
        } else {
            // now send the document
        }
    }

    // basata sulle precedenti, assume che $path sia un'immagine
    public static function cacheImg($path) {

        $t_mod = filemtime($path);
        $gm_mod = gmdate('D, d M Y H:i:s', $t_mod) . ' GMT';

        //--- std header con date dell'oggetto
        header('Content-type: ' . 'image/jpeg');
        header('Content-length: ' . filesize($path));
        // indichiamo a browser e proxy la data dell'immagine
        header("Last-Modified: $gm_mod");

        // set expires +12h
        $s_delay = (60 * 60 * 24 * 1);
        $t_tomorrow = time() + $s_delay;
        header('Expires: ' . date('D, d M Y H:i:s', $t_tomorrow) . ' GMT');

        // cache per n gg
        header("Cache-Control: max-age=" . $s_delay . ', public');
        header("Pragma: cache");

        //--- controlla se l'oggetto del browser è valido e risponde con 304 o il docuemnto
        $if_modified_since = '';
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $if_modified_since = preg_replace('/;.*$/', '', $_SERVER['HTTP_IF_MODIFIED_SINCE']);
        }
        if ($if_modified_since == $gm_mod) {
            header("HTTP/1.0 304 Not Modified");
            die();
        } else {
            fpassthru(fopen($path, 'rb'));
            die();
        }
    }

    // da usare se si genera JSON
    public static function isJSON() {
        header('Content-Type: application/json');
    }


    // invia una header 304 Not Modified se il contenuto non è variato, altrimneti rigenera la risposta
    // get the last-modified-date of a very file
    // $last_modified=filemtime(__FILE__);
    // get a unique hash of a file (etag)
    // $etag = md5_file(__FILE__);
    public static function cacheForGenerator($last_modified, $etag, Closure $generator) {

        //get the HTTP_IF_MODIFIED_SINCE header if set
        $ifModifiedSince=(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false);
        //get the HTTP_IF_NONE_MATCH header if set (etag: unique file hash)
        $etagHeader=(isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : false);

        //set last-modified header
        header("Last-Modified: ".gmdate("D, d M Y H:i:s", $last_modified)." GMT");
        //set etag-header
        header("Etag: $etag");
        //make sure caching is turned on
        header('Cache-Control: public');

        //check if page has changed. If not, send 304 and exit
        if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])==$last_modified || $etagHeader == $etag) {
               header("HTTP/1.1 304 Not Modified");
               exit;
        } else {
            // sprintf('<!-- This page was last modified: %s -->', date("d.m.Y H:i:s",time()) );
            die( $generator() );
        }
    }



    //----------------------------------------------------------------------------
    // content type headers
    //----------------------------------------------------------------------------
    //  force file download, anche su IE
    public static function displayPDF($pdf_path) {
        header('Content-type: application/pdf');
        header(sprintf('Content-Disposition: attachment; filename="%s"', basename($pdf_path)));
        header('Pragma: no-cache');
        readfile($pdf_path);
    }

    // indica che stiamo producendo html e codifichiamo in utf-8
    public static function displayHTML() {
        header('Content-Type: text/html; charset=utf-8');
    }

    //-----------------------------------------------------------------------------------
    //
    //-----------------------------------------------------------------------------------
    /**
     * Set the HTTP response status and takes care of the used PHP SAPI
     */
    function http_status($code = 200, $text = '') {
        static $stati = array(
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',

            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            307 => 'Temporary Redirect',

            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',

            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported'
        );

        if($text == '' && isset($stati[$code])) {
            $text = $stati[$code];
        }

        $server_protocol = (isset($_SERVER['SERVER_PROTOCOL'])) ? $_SERVER['SERVER_PROTOCOL'] : false;

        if(substr(php_sapi_name(), 0, 3) == 'cgi'  ) {
            header("Status: {$code} {$text}", true);
        } elseif($server_protocol == 'HTTP/1.1' OR $server_protocol == 'HTTP/1.0') {
            header($server_protocol." {$code} {$text}", true, $code);
        } else {
            header("HTTP/1.1 {$code} {$text}", true, $code);
        }
    }

}
