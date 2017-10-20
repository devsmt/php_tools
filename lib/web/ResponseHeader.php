<?php

// crea header HTTP
class ResponseHeader {
    function header_code_verbose($code) {
        switch($code):
        case 200: return '200 OK';
        case 201: return '201 Created';
        case 204: return '204 No Content';
        case 205: return '205 Reset Content';
        case 400: return '400 Bad Request';
        case 401: return '401 Unauthorized';
        case 403: return '403 Forbidden';
        case 404: return '404 Not Found';
        case 405: return '405 Method Not Allowed';
        case 416: return '416 Requested Range Not Satisfiable';
        case 418: return "418 I'm a teapot";
        case 422: return '422 Unprocessable Entity';
        default:  return '500 Internal Server Error';
        endswitch;
    }
    function response($code,$content=false,$contentType='text/html',$charset='UTF-8') {
        header('HTTP/1.1 '.Http::header_code_verbose($code));
        header('Status: '.Http::header_code_verbose($code)."\r\n");
        header("Connection: Close\r\n");
        $ct = "Content-Type: $contentType";
        if ($charset)
            $ct .= "; charset=$charset";
        header($ct);
        if ($content) {
            header('Content-Length: '.strlen($content)."\r\n\r\n");
            print $content;
            exit;
        }
    }


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
    // forza il redirect gestendo anche il caso in cui già esista output
    function redirect($url) {
        if (!headers_sent()) {
            header('Location: ' . $url);
        } else {
            echo '<script type="text/javascript">';
            echo 'window.location.href="' . $url . '";';
            echo '</script>';
            echo '<noscript>';
            echo '<meta http-equiv="refresh" content="0;url=' . $url . '" />';
            echo '</noscript>';
        }
    }

    // gestione download file di grande dimensione
    protected static function downloadBinary($file_path, Closure $do_after) {

        if (!is_file($file_path)) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
            die('File not found');
        } else if (!is_readable($file_path)) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
            die('File not readable');
        }
        // header($_SERVER['SERVER_PROTOCOL'].' 200 OK');
        // header("Content-Type: application/zip");
        //
        // toglie eventuale output buffer(s)
        while (ob_get_level() > 0) {ob_end_clean();}

        // necessario per gestire files grandi
        set_time_limit(0);
        ini_set("memory_limit", "500M");
        // force download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: binary");
        header('Content-Disposition: attachment; filename=' . basename($file_path));
        // no cache
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        $do_after();
        exit;
    }

    // da usare se si genera JSON
    public static function sendJSON() {
        header('Content-Type: application/json');
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

    public static function cacheable($etag, $modified=false, $ttl=3600) {
        // Thanks, http://stackoverflow.com/a/1583753/1025836
        // Timezone doesn't matter here — but the time needs to be
        // consistent round trip to the browser and back.
        if ($modified) {
            $last_modified = strtotime($modified." GMT");
            header("Last-Modified: ".date('D, d M Y H:i:s', $last_modified)." GMT", false);
        }
        header('ETag: "'.$etag.'"');
        header("Cache-Control: private, max-age=$ttl");
        header('Expires: ' . gmdate('D, d M Y H:i:s', Misc::gmtime() + $ttl)." GMT");
        header('Pragma: private');
        if (($modified && @strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $last_modified)
            || @trim($_SERVER['HTTP_IF_NONE_MATCH'], '" ') == $etag) {
        header("HTTP/1.1 304 Not Modified");
        exit();
            }
    }


    // invia una header 304 Not Modified se il contenuto non è variato, altrimneti rigenera la risposta
    // get the last-modified-date of a file
    // $last_modified=filemtime(__FILE__);// or get the time from DB
    // get a unique hash of a file (etag)
    // $etag = md5_file(__FILE__);// or get from DB
    public static function cacheForGenerator($last_modified, $etag, Closure $generator) {
        //get the HTTP_IF_MODIFIED_SINCE header if set
        $if_modified_since = (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false);
        $if_modified_since = @strtotime($if_modified_since);
        //get the HTTP_IF_NONE_MATCH header if set (etag: unique file hash)
        $etag_header = (isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : false);
        //set last-modified header
        header("Last-Modified: " . gmdate("D, d M Y H:i:s", $last_modified) . " GMT");
        //set etag-header
        header("Etag: $etag");
        //make sure caching is turned on
        header('Cache-Control: public');
        //check if page has changed. If not, send 304 and exit
        if ($if_modified_since == $last_modified || $etag_header == $etag) {
            header("HTTP/1.1 304 Not Modified");
            exit;
        } else {
            // sprintf('<!-- This page was last modified: %s -->', date("d.m.Y H:i:s",time()) );
            die($generator());
        }
    }

    //----------------------------------------------------------------------------
    // content type headers
    //----------------------------------------------------------------------------
    //  force file download, anche su IE
    public static function displayPDF($pdf_path) {
        // toglie eventuale output
        while (ob_get_level() > 0) {ob_end_clean();}
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
        static $stati = [
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
            505 => 'HTTP Version Not Supported',
        ];

        if ($text == '' && isset($stati[$code])) {
            $text = $stati[$code];
        }

        $server_protocol = (isset($_SERVER['SERVER_PROTOCOL'])) ? $_SERVER['SERVER_PROTOCOL'] : false;

        if (substr(php_sapi_name(), 0, 3) == 'cgi') {
            header("Status: {$code} {$text}", true);
        } elseif ($server_protocol == 'HTTP/1.1' OR $server_protocol == 'HTTP/1.0') {
            header($server_protocol . " {$code} {$text}", true, $code);
        } else {
            header("HTTP/1.1 {$code} {$text}", true, $code);
        }
    }
    //
    public static function hidePHP() {
        header_remove('X-Powered-By');
        ini_set('expose_php', 'off');
    }
}

//----------------------------------------------------------------------------
//  image cache
//----------------------------------------------------------------------------

class ImageResponseHeader {
    // autoset image mime type of images, of an existing file
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
            header('Cache-control: private');
            header('Content-type: ' . $mime);
            header('Content-length: ' . filesize($path));
        }
    }
    // helper function: Send headers and returns an image.
    public static function sendImage($filename, $s_browser_cache = 60 * 60 * 24) {
        // toglie eventuale output
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($extension, ['png', 'gif', 'jpeg'])) {
            header("Content-Type: image/" . $extension);
        } else {
            header("Content-Type: image/jpeg");
        }
        header("Cache-Control: public, max-age=" . $s_browser_cache);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $s_browser_cache) . ' GMT');
        header('Content-Length: ' . filesize($filename));
        while (ob_get_level() > 0) {ob_end_clean();}
        readfile($filename);
        exit();
    }

    /*
    // usa ::cacheControl() o ::ImgSend()
    // forza il browser a
    1) assegnare all'oggetto la data di modifica,
    2) inviare l'header HTTP_IF_MODIFIED_SINCE
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
    // deprecated
    public static function __cacheControl($do_cache, $s_delay = null) {
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
            header('Cache-Control: public,max-age=' . $s_delay . ',must-revalidate');
            header('Expires: ' . gmdate('D, d M Y H:i:s', (time() + $s_delay)) . ' GMT');
            header('Last-modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
            // Pragma header removed should the server happen to set it automatically
            // Pragma headers can make browser misbehave and still ask data from server
            header_remove('Pragma');
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

        $file_hash = md5_file($path);
        header('ETag: ' . $file_hash);

        // set expires +1 day
        $s_delay = (60 * 60 * 24 * 1);
        $t_tomorrow = time() + $s_delay;
        header('Expires: ' . date('D, d M Y H:i:s', $t_tomorrow) . ' GMT');

        // cache per n gg
        header("Cache-Control: max-age=" . $s_delay . ', public');
        header("Pragma: cache");

        // if client data is ok, nothing to do
        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            if ($_SERVER['HTTP_IF_NONE_MATCH'] == $file_hash) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified');
                die();
            }
        }

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
}
