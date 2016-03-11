<?php

/*
 * interpretes database schema and knows details about
 *   how to talk to DB
 *   how to validate data
 * non così utile se anche le DAL class conoscono questi dettagli
class DataDictionary {


    // function  __construct($host,$db_name) {
    //
    // }

    function getPK($table) {
        $d = Weasel::GetDataDictionary();
        if (isset($d[$table])) {
            foreach ($d[$table] as $field => $att) {
                if ($att['Key'] == 'PRI') return $field;
            }
        }
        return null;
        // 'Key' => 'PRI',
        // 'Extra' => 'auto_increment',

    }
    function getType($table, $field, &$fieldMaxLen) {
        //'Type' => 'int(11)',
        //"varchar(50)"
        $d = Weasel::GetDataDictionary();
        $fieldMaxLen = null;
        if (isset($d[$table]) && isset($d[$table][$field])) {
            $t = $d[$table][$field]['Type'];
            if (preg_match('/int\((\d*)\)/', $t, $fieldMaxLen)) {
                $fieldMaxLen = $fieldMaxLen[1];
                return 'int';
            } elseif (preg_match('/varchar\((\d*)\)/', $t, $fieldMaxLen)) {
                $fieldMaxLen = $fieldMaxLen[1];
                return 'string';
            }
        }
        return null;
    }
    function getMaxLen($table, $field) {
        $fieldMaxLen = 0;
        DataDictionary::getType($table, $field, $fieldMaxLen);
        if ($fieldMaxLen) {
            return $fieldMaxLen;
        }
        return null;
    }
    function isNull($table, $field) {
        $d = Weasel::GetDataDictionary();
        if (isset($d[$table]) && isset($d[$table][$field])) {
            return $d[$table][$field]['Null'] == 'YES';
        }
        return null;
    }
    function getDefault($table, $field) {
        $d = Weasel::GetDataDictionary();
        if (isset($d[$table]) && isset($d[$table][$field])) {
            $s = $d[$table][$field]['Default'];
            if ($s == 'Null') {
                return null;
            } else {
                return $s;
            }
        }
        return null;
    }
    function dump() {
        $d = Weasel::GetDataDictionary();
        echo '<pre>', var_dump($d, true), '</pre>';
    }
}

// gerare un file in cui sono specificati i dettagli di ogni tabella
class DataDictionaryGenerator {}

*/
