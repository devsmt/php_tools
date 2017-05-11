<?php

/*
  remember: implementation simplicity is more important than interface simplicity.

  TODO:
  - /etc/schema.php defining table properties
  - /script/genModel.php to create a model
  - etc/schema.php from existing db configuration and db
  - lib/data/* classes

  1) Entity do calcs and validate on record data
  2) List retrieves RecordSet via SQL
  3) no on the fly code generation
*/



// -contiene le query di selezione
// -ritorna sempre $rs
// -no DB abstraction
class DAL {

    // return a recordset
    function select($sql, &$count) {
        $db = self::getDB();
        $rs = $db->qry($sql);
        $count = $db->num_rows($rs);
        return $rs;
    }

    //
    // ritorna il primo valore del primo record
    //
    function selectValue($sql) {
        $db = self::getDB();
        $rs = $db->qry($sql);
        $a = ;
        $record = $db->rs2a($rs);
        if ( !empty($record) ) {
            return $a[0][0];
        } else {
            return null;
        }
    }

    //
    // ritorna array di entità
    //
    function fetch(array $rs, $model_class='Entity') {
        $data = mysql_fetch_array($rs);
        if ( !empty($data) ) {
            $o = new $model_class($data);
            return $o;
        } else {
            return false;
        }
    }

}
