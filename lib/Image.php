<?php
// funzione: rccoglie funzioni per generare immagini
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
            $arrMatrix = array(
                array(-1, -2, -1),
                array(-2, $intSharpness + 12, -2),
                array(-1, -2, -1)
                );
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
        $resolutions   = array(1382, 992, 768, 480, 320); // the resolution break-points to use (screen widths, in pixels)
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
