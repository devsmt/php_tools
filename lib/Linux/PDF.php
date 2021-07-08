<?php
/*
uso:
$output = PDF::createThumbnail($path);
 */
/*
uso:
$output = PDF::extractFirstPage($path);
 */
class PDF {
    //
    // se ok, ritorna il path del nuovo thumbnail
    // uso:
    // list($res, $path) = PDF::createThumbnail($path);
    //
    public static function extractFirstPage($pdf_path) {
        if (!file_exists($pdf_path)) {
            $msg = 'wrong pdf path ' . $pdf_path;
            return [false, $msg];
        }
        $test = `which gs`;
        if (empty(trim($test))) {
            $msg = 'bin not installed ';
            return [false, $msg];
        }
        $ext = pathinfo($pdf_path, PATHINFO_EXTENSION);
        if ($ext !== 'pdf') {
            return [false, 'wrong file ext ' . $pdf_path];
        }
        $OUTFILE = str_replace('.pdf', '.jpg', $pdf_path);
        if (file_exists($OUTFILE)) {
            return [true, $OUTFILE];
        }
        $cmd = "gs -dSAFER -dBATCH -dNOPAUSE -sDEVICE=jpeg -r150 -dTextAlphaBits=4 -dGraphicsAlphaBits=4 -dMaxStripSize=8192 -dFirstPage=1 -dLastPage=1 -sOutputFile=" . escapeshellarg($OUTFILE) . " " . escapeshellarg($pdf_path);
        // echo $cmd;
        exec($cmd, $output, $return_var);
        if ($return_var === 0) {
            // ImageTools::optimize($OUTFILE);
            return [true, $OUTFILE];
        } else {
            return [false, 'ret: ' . $return_var];
        }
    }
    // totale pagine del documento
    public static function getPageCount($doc_path) {
        $cmd = "gs -q -dNODISPLAY -c \"($doc_path) (r) file runpdfbegin pdfpagecount = quit\";";
        $res = trim(`$cmd`);
        return $res;
    }
    /*
//
// se ok, ritorna il path del nuovo thumbnail
//
public static function getThumbnail($pdf_path, $opt = []) {
$opt = array_merge(array(
'w' => 150, 'h' => 150, 'scale' => true, 'inflate' => true,
'quality' => 100, 'adapterClass' => null,
'adapterOptions' => [], 'force' => false
// forza la riscrittura della thumb
), $opt);
extract($opt);
// crea un athumbnail dal PDF
$img_path = PDF::extractFirstPage($pdf_path);
// se c'è la pagina esportata, allora crea la miniatura
if (false !== $img_path) {
$img_thumb_path = $img_path . '-' . $w . 'x' . $h . '.jpg';
EVThumb::doThumbnail($img_path, $img_thumb_path, $opt);
return $img_thumb_path;
}
return 'n-a';
}
public static function getThumbnailURL($pdf_path, $opt = []) {
$path = self::getThumbnail($pdf_path, $opt);
$url = str_replace(__DIR__, '', $path);
return $url;
}
 */
}
class ImageTools {
    // come da istruzioni di YUI2 imageCropper
    // convert yui.jpg -crop [200 x 50 + 91 + 145] yui-new.jpg
    //                 Width: 200 Height: 50,  Left: 91 Top: 145,
    public static function crop($path, $new_path, $width, $heigh, $left, $top) {
        $cmd = "convert $path -crop [$width x $heigh + $left + $top] $new_path";
        return `$cmd`;
    }
    // ottimizza files caricati
    public static function optimize($path) {
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        switch ($ext) {
        case 'jpg':
            if (`which jpegtran`) {
                $cmd = "jpegtran -copy none -optimize -progressive -outfile $path $path";
            } else {
                echo "missing jpegtran";
            }
            break;
        case 'png':
            if (`which optipng`) {
                $cmd = "optipng -o7 -strip all $path";
            } else {
                echo "missing optipng";
            }
            break;
        }
        return `$cmd`;
    }
}
//
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/../Test.php';
}