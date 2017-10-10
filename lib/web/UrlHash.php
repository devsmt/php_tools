<?php

// funzione: dato un intero, lo codifica in base 62 (usa i 62 caratteri sicuri)
// per codificare gli ID nelle URL
// è possibile ottenere dei risultati offuscati cambiando mappa carateri
require_once __DIR__ . '/Math.php';

class UrlHash {

    public static function encode($i) {
        return base_convert_x($i, 10, 62);
    }

    public static function decode($hash) {
        return base_convert_x($hash, 62, 10);
    }

}

// funzione: genera un parametro checksum dato un parametro id associato
// assicura che il parametro oggetto(es. un id) non sia manomesso dal utente
// uso:
//     url('page.php', array('cod_cliente'=>$id, 'C'=>UrlChecksum::getCheck($id)));
//     if( UrlChecksum::isValid($id, $_GET['C'] ) ){ ok }
class UrlChecksum {

    // variare questa costante in modo che
    // 1) non possa essere indovinata facilmente
    // 2) sia costante tra le 2 richieste
    // es.
    //const SCRT = __FILE__;
    //const SCRT = `uname -a`;
    //const SCRT = $_SERVER['HOST_NAME'];
    //const SCRT = implode(',', apache_get_modules() );
    const SCRT = 'str123SDFGHJ';

    // detrermina se il parametro passato ha il corretto checkSum
    public static function isValid($parameter, $check_hash) {
        return $check_hash == self::getCheck($parameter);
    }

    public static function getCheck($parameter) {
        $s = md5(self::SCRT . $parameter);
        $s = base64_encode($s);
        // 8 dovrebbe essere abbastanza da scoraggiare tentativi
        $s = substr($s, 0, 8);
        return $s;
    }

}
