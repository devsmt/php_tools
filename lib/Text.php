<?php

// helper che permette di formattare un array di dati come "tabella" testuale
class Text {

    //
    public static function table(array $data) {
        // calc len:
        // cicla righe con header
        // cicla colonne e aggiorna il conteggio se si trova un max
        $a_len = array();
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
                $a_r[$k]=str_pad($a_r[$k], $a_len[$k], ' ', STR_PAD_LEFT);
            }
            $s_tbl .= implode('  ', $a_r) . "\n";
        }
        return $s_tbl;
    }

}
