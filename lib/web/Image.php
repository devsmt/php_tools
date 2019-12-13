<?php
// funzione: raccoglie procedure per generare immagini
// questa è una implementazione leggera, per una implementazione più astratta
// @see http://wideimage.sourceforge.net/
// @see http://stefangabos.ro/php-libraries/zebra-image/#installation
// @see https://github.com/mikeemoo/ColorJizz-PHP library for manipulating and converting colors
// @see https://github.com/avalanche123/Imagine
class Image {
    // Data URI per incorporare immagini
    // <img src="<?=data_uri('elephant.png', 'image/png')?>" alt="An elephant" />
    // header('Content-Type:text/css'); ?>
    // div.menu { background-image:url('<?= data_uri('elephant.png', 'image/png') ?>'); }
    function embed($file, $mime='image/png') {
        return "data:$mime;base64," . base64_encode(file_get_contents($file));
    }
    // crea un'innagine da un testo, un'idea per proteggere una email dagli spider
    public static function txt($string){
        $font  = 4;
        //  create
        $height = imagefontheight($font);
        $width  = imagefontwidth($font) * strlen($string);
        $image = imagecreatetruecolor ($width,$height);
        // todo: gestire il colore di BG
        $color_BG = imagecolorallocate($image,255,255,255);
        $color_TXT = imagecolorallocate($image,0,0,0);
        // fill color
        imagefill($image,0,0,$color_BG);
        imagestring ($image,$font,0,0,$string,$color_TXT);
        // out
        header("Content-type: image/png");
        imagepng($image);
        imagedestroy($image);
    }
    /*
    // crea un thumbnail delle dimensioni richieste
    public static function doThumb($load_path, $save_path, $w,$h){
    $imagine = new \Imagine\Gd\Imagine();
    $image = $imagine->open($load_path);
    $thumbnail = $image->thumbnail(new Imagine\Image\Box($w, $h));
    $thumbnail->save($save_path);
    }
    */
    // sends image $resource descriptor to browser and destroy the resource if headers not sent.
    // use php constants IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG
    final public static function show_resource($resource, $type){
        if(!headers_sent()){
            switch($type){
            case IMAGETYPE_GIF :
                header('Content-type: image/gif');
                header('Content-Disposition: filename='.basename(__FILE__).'.gif');
                imagegif($resource);
                break;
            case IMAGETYPE_JPEG :
                header('Content-type: image/jpeg');
                header('Content-Disposition: filename='.basename(__FILE__).'.jpg');
                imagejpeg($resource, NULL, 99);
                break;
            case IMAGETYPE_PNG :
                header('Content-type: image/png');
                header('Content-Disposition: filename='.basename(__FILE__).'.png');
                imagepng($resource, NULL, 0,  NULL);
                break;
            }
            imagedestroy($resource);
            exit;
        }
    }
    // $file_path: The file you are grayscaling
    // output
    // This sets it to a .jpg, but you can change this to png or gif if that is what you are working with
    // header('Content-type: image/jpeg');
    // imagejpeg($buffered_img);
    public static function doGrayScale($file_path) {
        list($width, $height) = getimagesize($file_path);
        // Define our source image
        $source_buffer = imagecreatefromjpeg($file_path);
        // Creating the Canvas
        $buffered_img = imagecreate($width, $height);
        //palette di 256 grigi
        for ($c = 0; $c < 256; $c++) {
            $palette[$c] = imagecolorallocate($buffered_img, $c, $c, $c);
        }
        //Reads the origonal colors pixel by pixel
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($source_buffer, $x, $y);
                // dati rgb ritorna il grigio + prossimo da 0 a 256
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                // conversione del pixel in grayscale
                $gs = (($r * 0.299) + ($g * 0.587) + ($b * 0.114));
                imagesetpixel($buffered_img, $x, $y, $palette[$gs]);
            }
        }
        return $buffered_img;
    }
    /*
     * croppa una immagine
     * $img_file_path path all'immagine originale
     */
    public static function Crop($img_file_path, $result_file_path, $dim) {
        $result = false;
        if (isset($dim['cropWidth']) &&
            isset($dim['cropHeight']) &&
            isset($dim['cropStartX']) &&
            isset($dim['cropStartY'])
        ) {
            $imgData = @GetImageSize($img_file_path);
            $imgTypes = [
                'image/jpeg',
                'image/png',
                'image/gif',
            ];
            $imgLoaders = [
                'image/jpeg' => 'imagecreatefromjpeg',
                'image/png' => 'imagecreatefrompng',
                'image/gif' => 'imagecreatefromgif',
            ];
            $imgCreators = [
                'image/jpeg' => 'imagejpeg',
                'image/png' => 'imagepng',
                'image/gif' => 'imagegif',
            ];
            if ($imgData) {
                if (in_array($imgData['mime'], $imgTypes)) {
                    $create_f = $imgLoaders[$imgData['mime']];
                    $dump_f = $imgCreators[$imgData['mime']];
                } else {
                    // non � un'immagine
                    return false;
                }
                // Create two images
                $origimg = $create_f($img_file_path);
                $cropimg = imagecreatetruecolor($dim['cropWidth'], $dim['cropHeight']);
                // Get the original size
                list($width, $height) = getimagesize($img_file_path);
                // Crop
                imagecopyresized($cropimg, $origimg, 0, 0, $dim['cropStartX'], $dim['cropStartY'], $width, $height, $width, $height);
                $result = imagejpeg($cropimg, $result_file_path);
                // destroy the images
                imagedestroy($cropimg);
                imagedestroy($origimg);
            }
        } else {
            // non � stato passato tutti i dati necessari
            return false;
        }
        return $result;
    }
    //
    public static function blur($path, $path_out) {
        `convert $path -blur 0x5 $path_out`;
    }
}
// funzione: genera una immagine adatta a una detrerminata risoluzione
/* uso:
if (file_exists($cache_file)) {
    Image::send($cache_file, $browser_cache);
} else {
    $file = Image::generate($source_file, $cache_file, $resolution);
    Image::send($file, $browser_cache);
}
*/
class ImageResizer {
    // helper function: Send headers and returns an image.
    public static function send($filename, $browser_cache = 60*60*24*7 ) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($extension, ['png', 'gif', 'jpeg'])) {
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
    // helper function: Create and send an image with an error message.
    public static function sendError($message) {
        $im         = ImageCreateTrueColor(800, 200);
        $text_color = ImageColorAllocate($im, 233, 14, 91);
        ImageString($im, 1, 5, 5, $message, $text_color);
        header("Cache-Control: no-cache");
        header('Expires: '.gmdate('D, d M Y H:i:s', time()-1000).' GMT');
        header('Content-Type: image/jpeg');
        ImageJpeg($im);
        ImageDestroy($im);
        exit();
    }
    // sharpen images function
    public static function findSharp($intOrig, $intFinal) {
        $intFinal = $intFinal * (750.0 / $intOrig);
        $intA     = 52;
        $intB     = -0.27810650887573124;
        $intC     = .00047337278106508946;
        $intRes   = $intA + $intB * $intFinal + $intC * $intFinal * $intFinal;
        return max(round($intRes), 0);
    }
    // generates the given cache file for the given source file with the given resolution
    public static function generate($source_file, $cache_file, $resolution = null) {
        $sharpen = true;
        $jpg_quality = 90;
        $resolution = self::CalcResolution();
        $extension = strtolower(pathinfo($source_file, PATHINFO_EXTENSION));
        // Check the image dimensions
        $dimensions   = GetImageSize($source_file);
        $width        = $dimensions[0];
        $height       = $dimensions[1];
        // Do we need to downscale the image?
        if ($width <= $resolution) { // no, because the width of the source image is already less than the client width
            return $source_file;
        }
        // We need to resize the source image to the width of the resolution breakpoint we're working with
        $ratio      = $height/$width;
        $new_width  = $resolution;
        $new_height = ceil($new_width * $ratio);
        switch ($extension) {
        case 'png':
            $src = @ImageCreateFromPng($source_file); // original image
            break;
        case 'gif':
            $src = @ImageCreateFromGif($source_file); // original image
            break;
        default:
            $src = @ImageCreateFromJpeg($source_file); // original image
            break;
        }
        $dst = ImageCreateTrueColor($new_width, $new_height); // re-sized image
        if($extension=='png'){
            imagealphablending($dst, false);
            imagesavealpha($dst,true);
            $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
            imagefilledrectangle($dst, 0, 0, $new_width, $new_height, $transparent);
        }
        ImageCopyResampled($dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height); // do the resize in memory
        ImageDestroy($src);
        // sharpen the image?
        if ($sharpen == TRUE) {
            $intSharpness = ImageResizer::findSharp($width, $new_width);
            $arrMatrix = [
                [-1, -2, -1],
                [-2, $intSharpness + 12, -2],
                [-1, -2, -1]
            ];
            imageconvolution($dst, $arrMatrix, $intSharpness, 0);
        }
        $cache_dir = dirname($cache_file);
        // does the directory exist already?
        if (!is_dir($cache_dir)) {
            if (!mkdir($cache_dir, 0777, true)) {
                // check again if it really doesn't exist to protect against race conditions
                if (!is_dir($cache_dir)) {
                    // uh-oh, failed to make that directory
                    ImageDestroy($dst);
                    ImageResizer::sendError("Failed to create directory: $cache_dir");
                }
            }
        }
        if (!is_writable($cache_dir)) {
            ImageResizer::sendError("The cache directory is not writable: $cache_dir");
        }
        // save the new file in the appropriate path, and send a version to the browser
        switch ($extension) {
        case 'png':
            $gotSaved = ImagePng($dst, $cache_file);
            break;
        case 'gif':
            $gotSaved = ImageGif($dst, $cache_file);
            break;
        default:
            $gotSaved = ImageJpeg($dst, $cache_file, $jpg_quality);
            break;
        }
        ImageDestroy($dst);
        if (!$gotSaved && !file_exists($cache_file)) {
            ImageResizer::sendError("Failed to create image: $cache_file");
        }
        return $cache_file;
    }
    // trova una risoluzione valida
    function calcResolution() {
        global $mobile_first;
        $resolutions   = [1382, 992, 768, 480, 320]; // the resolution break-points to use (screen widths, in pixels)
        /* Check to see if a valid cookie exists */
        if (isset($_COOKIE['resolution'])) {
            if (is_numeric($_COOKIE['resolution'])) {
                $client_width = (int) $_COOKIE["resolution"]; // store the cookie value in a variable
                /* the client width in the cookie is valid, now fit that number into the correct resolution break point */
                rsort($resolutions); // make sure the supplied break-points are in reverse size order
                $resolution = $resolutions[0]; // by default it's the largest supported break-point
                foreach ($resolutions as $break_point) { // filter down
                    if ($client_width <= $break_point) {
                        $resolution = $break_point;
                    }
                }
            } else {
                setcookie("resolution", "", time() -1); // delete the mangled cookie
            }
        }
        /* No resolution was found (no cookie or invalid cookie) */
        if (!$resolution) {
            // We send the lowest resolution for mobile-first approach, and highest otherwise
            $resolution = $mobile_first ? min($resolutions) : max($resolutions);
        }
        return $resolution;
    }
}
/*
based on:
    URL:        http://github.com/jamiebicknell/Sparkline
    Author:     Jamie Bicknell
    Twitter:    @jamiebicknell
*/
class SparklineGenerator {
    function __construct(array $data, array $opt) {
        // dependency check
        if (!extension_loaded('gd')) {
            die('GD extension is not installed');
        }
        //--- param init
        $size = isset($opt['size']) ? str_replace('x', '', $opt['size']) != '' ? $opt['size'] : '80x20' : '80x20';
        $back = isset($opt['back']) ? Color::isHex($opt['back']) ? $opt['back'] : 'ffffff' : 'ffffff';
        $line = isset($opt['line']) ? Color::isHex($opt['line']) ? $opt['line'] : '1388db' : '1388db';
        $fill = isset($opt['fill']) ? Color::isHex($opt['fill']) ? $opt['fill'] : 'e6f2fa' : 'e6f2fa';
        $salt = __CLASS__;
        $opt['data'] = implode(',',$data);// data fa parte della chiave di cache
        ksort($opt);//in-place sort!
        $this->hash = md5($salt . implode(',', $opt ) );
        // if client data is ok, nothing to do
        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            if ($_SERVER['HTTP_IF_NONE_MATCH'] == $this->hash) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified');
                die();
            }
        }
        $path = APPLICATION_PATH.'/../var/sparkline';
        if( !file_exists($path) ) {
            die( "$path non esiste" )
        }
        $this->file_path = sprintf("%s/sparkline_%s.png", $path, $this->hash );
    }
    // legge file su disco
    public function render() {
        if( !file_exists($this->file_path) ) {
            $this->generateImageFile($data, $size, $back, $line, $fill);
        }
        return self::cacheImg($this->file_path);
    }
    protected function generateImageFile(array $data, $size, $back, $line, $fill) {
        list($w, $h) = explode('x', $size);
        $w = floor(max(50, min(800, $w)));
        $h = !strstr($size, 'x') ? $w : floor(max(20, min(800, $h)));
        $t = 1.75;
        $s = 4;
        $w *= $s;
        $h *= $s;
        $t *= $s;
        $data = (count($data) < 2) ? array_fill(0, 2, $data[0]) : $data;
        $count = count($data);
        $step = $w / ($count - 1);
        $max = max($data);
        //--- rendering
        $im = imagecreatetruecolor($w, $h);
        list($r, $g, $b) = Color::hexToRgb($back);
        $bg = imagecolorallocate($im, $r, $g, $b);
        list($r, $g, $b) = Color::hexToRgb($line);
        $fg = imagecolorallocate($im, $r, $g, $b);
        list($r, $g, $b) = Color::hexToRgb($fill);
        $lg = imagecolorallocate($im, $r, $g, $b);
        imagefill($im, 0, 0, $bg);
        imagesetthickness($im, $t);
        foreach ($data as $k => $v) {
            $v = $v > 0 ? round($v / $max * $h) : 0;
            $data[$k] = max($s, min($v, $h - $s));
        }
        $x1 = 0;
        $y1 = $h - $data[0];
        $line = [];
        $poly = [0, $h + 50, $x1, $y1];
        for ($i = 1; $i < $count; $i++) {
            $x2 = $x1 + $step;
            $y2 = $h - $data[$i];
            array_push($line, [$x1, $y1, $x2, $y2]);
            array_push($poly, $x2, $y2);
            $x1 = $x2;
            $y1 = $y2;
        }
        array_push($poly, $x2, $h + 50);
        imagefilledpolygon($im, $poly, $count + 2, $lg);
        foreach ($line as $k => $v) {
            list($x1, $y1, $x2, $y2) = $v;
            imageline($im, $x1, $y1, $x2, $y2, $fg);
        }
        $this->om = imagecreatetruecolor($w / $s, $h / $s);
        imagecopyresampled($this->om, $im, 0, 0, 0, 0, $w / $s, $h / $s, $w, $h);
        imagedestroy($im);
        // scrive file su disco
        imagepng($this->om, $this->file_path);
        imagedestroy($this->om);
    }
    // basata sulle precedenti, assume che $path sia un'immagine
    protected static function cacheImg($path) {
        $t_mod = filemtime($path);
        $gm_mod = gmdate('D, d M Y H:i:s', $t_mod) . ' GMT';
        //--- std header con date dell'oggetto
        header('Content-type: ' . 'image/png');
        header('Content-length: ' . filesize($path));
        // indichiamo a browser e proxy la data dell'immagine
        header("Last-Modified: $gm_mod");
        $file_hash = md5_file($path);
        header('ETag: ' .$file_hash );
        // set expires +1 day
        $s_delay = (60 * 60 * 24 * 1);
        $t_tomorrow = time() + $s_delay; //strtotime("+2 day")
        header("Expires: " . date(DATE_RFC822, $t_tomorrow ));
        // cache per n gg
        header("Cache-Control: max-age=" . $s_delay . ', public');
        header("Pragma: cache");
        // if client data is ok, nothing to do
        # abilita HTTP_IF_MODIFIED_SINCE in htaccess
        # RewriteRule .* - [E=HTTP_IF_MODIFIED_SINCE:%{HTTP:If-Modified-Since}]
        # RewriteRule .* - [E=HTTP_IF_NONE_MATCH:%{HTTP:If-None-Match}]
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


function img_resize($path_in, $path_out, $new_width){
    $im_php = imagecreatefromjpeg( $path_in );
    $im_php = imagescale($im_php, $new_width, $new_height = -1 , $mode = IMG_BILINEAR_FIXED );
    $new_height = imagesy($im_php);
    imagejpeg($im_php, $path_out, 100);
    return $new_height;// return H of scaled img
}
// img_resize($path_in = "input.jpg", $path_out="output.jpg", function($width, $height){
//     return [ $newwidth = $width/5,	$newheight = $height/5  ];
// } );
function img_resize($path_in, $path_out, $_calc_new_dim ){
	$source = imagecreatefromjpeg($path_in);
	list($width, $height) = getimagesize($path_in);
	list( $newwidth , $newheight) = $_calc_new_dim($width, $height)
	$destination = imagecreatetruecolor($newwidth, $newheight);
	imagecopyresampled($destination, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
	imagejpeg($destination, $path_out, 100);
}





// very basic and not secure captcha code
// @see http://www.mperfect.net/aiCaptcha/ for more information
//
// some security tips:
//   - render the characters with different colors
//   - make some characters darker than the background, and some lighter
//   - use gradient colors for the backgrounds and the characters
//   - dont align all the characters vertically
//   - dont make the answers words, so that a dictionary could be used
//   - use more characters and symbols
//   - use uppercase and lowercase characters
//   - use a different number of characters each time
//   - rotate some of the characters more drastically (i.e. upside down)
//   - do more overlapping of characters
//   - make some pixels of a single character not touching
//   - have grid lines that cross over the characters with their same color
//   - consider asking natural language questions like 2+2
class CAPTCHA {
    /*
    <form method="POST" action="form-handler.php" onsubmit="return checkForm(this);">
    ...
    <p><img src="/captcha.php" width="120" height="30" border="1" alt="CAPTCHA"></p>
    <p><input type="text" size="6" maxlength="5" name="captcha" value=""><br>
    <small>copy the digits from the image into this box</small></p>
    ...
    </form>
    <script type="text/javascript">
    function checkForm(form) {
    ...
    if(!form.captcha.value.match(/^\d{5}$/)) {
    alert('Please enter the CAPTCHA digits in the box provided');
    form.captcha.focus();
    return false;
    }
    ...
    return true;
    }
    </script>
    <?php
    // form-handler.php
    if($_POST && all required variables are present) {
    ...
    session_start();
    if($_POST['captcha'] != $_SESSION['digit'])
        die("Sorry, the CAPTCHA code entered was incorrect!");
    session_destroy();
    ...
    }
    ?>
    */
    public static function render() {
        // initialise image with dimensions of 120 x 30 pixels
        $image = @imagecreatetruecolor(120, 30) or die("Cannot Initialize new GD image stream");
        // set background and allocate drawing colours
        $background = imagecolorallocate($image, 0x66, 0x99, 0x66);
        imagefill($image, 0, 0, $background);
        $linecolor = imagecolorallocate($image, 0x99, 0xCC, 0x99);
        $textcolor1 = imagecolorallocate($image, 0x00, 0x00, 0x00);
        $textcolor2 = imagecolorallocate($image, 0xFF, 0xFF, 0xFF);
        // draw random lines on canvas
        for($i=0; $i < 6; $i++) {
            imagesetthickness($image, rand(1,3));
            imageline($image, 0, rand(0,30), 120, rand(0,30) , $linecolor);
        }
        session_start();
        // add random digits to canvas using random black/white colour
        $rand_str = '';
        for($x = 15; $x <= 95; $x += 20) {
            $textcolor = (rand() % 2) ? $textcolor1 : $textcolor2;
            $rand_char = self::get_rand_char();
            $rand_str .= $rand_char;
            imagechar($image, rand(3, 5), $x, rand(2, 14), $rand_char, $textcolor);
        }
        // record digits in session variable
        $_SESSION['digit'] = $rand_str;
        // display image and clean up
        header('Content-type: image/png');
        imagepng($image);
        imagedestroy($image);
    }
    //
    public static function get_rand_char() {
        $s_chars = '0123456789qwertyuipkjhgfdsazxcvbnm';
        $a_chars = explode('', $s_chars);
        $i = rand(0, count($a_chars));
        $rand_char = $a_chars[$i];
        $rand_char = rand(0, 4) === 1 ? strtoupper($rand_char) : $rand_char;
        return $rand_char;
    }
}
class Color {
    public static function isHex($string) {
        return preg_match('/^#?+[0-9a-f]{3}(?:[0-9a-f]{3})?$/i', $string);
    }
    public static function hexToRgb($hex) {
        $hex = ltrim(strtolower($hex), '#');
        $hex = isset($hex[3]) ? $hex : $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        $dec = hexdec($hex);
        return array(0xFF & ($dec >> 0x10), 0xFF & ($dec >> 0x8), 0xFF & $dec);
    }
}
