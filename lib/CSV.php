<?php

// astrazione sulla lettura file xls
class CSV {

    public static function read($file, $out_file = null) {
        $path = __DIR__ . '/vendor/PHPExcel';
        $path = realpath($path);
        require_once $path . '/PHPExcel/IOFactory.php';
        require_once $path . '/PHPExcel/Shared/String.php';
        if ($out_file) {
            // scrive in un file riconducibile all'originale per facilitare il debug
            $out_file = $file . '.csv';
        }
        //
        $objPHPExcel = PHPExcel_IOFactory::load($file);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'CSV');
        $objWriter->save($out_file);
        //
        $data = self::CSVToArray($out_file);
        if ($debug = false) {
            $a_repr = var_export($a, 1);
            file_put_contents($out_file . '.data', $a_repr);
        }
        return $data;
    }

    public static function CSVToArray($filename = '', $delimiter = ',') {
        if (!file_exists($filename) || !is_readable($filename)) {
            return FALSE;
        }

        $data = [];
        if (($handle = fopen($filename, 'r')) !== FALSE) {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                //if(!$header)
                //    $header = $row;
                //else
                //    $data[] = array_combine($header, $row);
                $row = array_filter($row, function ($s) {return '' !== $s;});
                $data[] = $row;
            }
            fclose($handle);
        }
        $data = array_filter($data, function ($a) {return !empty($a);});
        return $data;
    }
}