<?php

// helper che permette di formattare un array di dati come "tabella" testuale
class Text {

    //
    public static function table(array $data) {
        // calc len:
        // cicla righe con header
        // cicla colonne e aggiorna il conteggio se si trova un max
        $a_len = [];
        for ($i = 0; $i < count($data); $i++) {
            $a_r = $data[$i];
            for ($k = 0; $k < count($a_r); $k++) {
                // recorded max len
                $a_len[$k] = isset($a_len[$k]) ? $a_len[$k] : 0;
                // cur len
                $len = strlen($data[$i][$k]);
                // is new max?
                if ($len > $a_len[$k]) {
                    $a_len[$k] = $len;
                }
            }
        }

        // format data
        $s_tbl = '';
        for ($i = 0; $i < count($data); $i++) {
            $a_r = $data[$i];
            for ($k = 0; $k < count($a_r); $k++) {
                // applica format
                $a_r[$k] = str_pad($a_r[$k], $a_len[$k], ' ', STR_PAD_LEFT);
            }
            $s_tbl .= implode('  ', $a_r) . "\n";
        }
        return $s_tbl;
    }
    function word_select($text, array $matches, $replace='b') {
        foreach ($matches as $match) {
            switch ($replace) {
            case "u":
            case "b":
            case "i":
                $text = preg_replace("/([^\w]+)($match)([^\w]+)/",
                    "$1<$replace>$2</$replace>$3", $text);
                break;
            default:
                $text = preg_replace("/([^\w]+)$match([^\w]+)/",
                    "$1$replace$2", $text);
                break;
            }
        }
        return $text;
    }
}
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/Test.php';

}