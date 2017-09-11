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
    /*
      se ok, ritorna il path del nuovo thumbnail
     */

    function extractFirstPage($pdf) {
        if (!file_exists($pdf)) {
            die('wrong pdf path ' . $pdf);
            return false;
        }
        $OUTFILE = str_replace('.pdf', '.jpg', $pdf);
        if (file_exists($OUTFILE)) {
            return $OUTFILE;
        }
        $cmd = "gs -dSAFER -dBATCH -dNOPAUSE -sDEVICE=jpeg -r150 -dTextAlphaBits=4 -dGraphicsAlphaBits=4 -dMaxStripSize=8192 -dFirstPage=1 -dLastPage=1 -sOutputFile=$OUTFILE " . escapeshellarg($pdf);
        exec($cmd, $output, $return_var);
        return ($return_var === 0 ? $OUTFILE : false);
    }

    /*
      se ok, ritorna il path del nuovo thumbnail
     */

    public static function getThumbnail($pdf_path, $opt = ) {
        $opt = array_merge(
            [
                'w' => 150, 'h' => 150, 'scale' => true, 'inflate' => true, 'quality' => 100, 'adapterClass' => null, 'adapterOptions' => , 'force' => false
                // forza la riscrittura della thumb
                ], $opt);
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

    public static function getThumbnailURL($pdf_path, $opt = ) {
        $path = self::getThumbnail($pdf_path, $opt);
        $url = str_replace(sfConfig::get('sf_root_dir') . '/web', '', $path);
        return $url;
    }

    // totale pagine del documento
    public static function getPageCount($doc_path) {
        $cmd = "gs -q -dNODISPLAY -c \"($doc_path) (r) file runpdfbegin pdfpagecount = quit\";";
        $res = trim( `$cmd` );
        return $res;
    }

}
