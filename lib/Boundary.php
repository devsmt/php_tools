<?php

// funzione:
// max NUM tentativi(per login o altro) per IP al giorno
// -scivere in un file, con nome contenente la data odierna, una riga per ogni login(o altra azione)
// -contare le righe che contengono l'ip
class Boundary {

    // si puo usare ip o username
    public static function log($id) {
        $time = date('Y-m-d_H:i:s');
        file_put_contents(self::getFileName(), "$id:$time\n");
    }

    //
    public static function count($id) {
        $s = file_get_contents(self::getFileName());
        if (empty($s)) {
            return 0;
        }
        $a = explode("\n", $s);
        $a = array_filter($a, function ($s) {
            return !empty($s) && (strpos($s, $id) !== false);
        });
        return count($a);
    }

    protected function getFileName() {
        return sprintf('/tmp/%s_%s.txt', __CLASS__, date('Ymd'));
    }

}
