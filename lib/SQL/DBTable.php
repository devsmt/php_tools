<?php

// esegui operazioni comuni su una tabella del db
class DBTable {
    public static function insert(string $table, array $data) {
        $sql = SQL::insert($table, $data);
        return db_exec($sql);
    }
    public static function update(string $table, array $data, string $where = '') {
        if (empty($where)) {
            $msg = sprintf('Errore where should be empty %s ', $where);
            throw new \Exception($msg);
        }
        $sql = SQL::update($table, $where, $data);
        return db_exec($sql);
    }
    public static function delete(string $table, string $where = '') {
        $sql = SQL::delete($table, $where);
        return db_exec($sql);
    }
    // se trova il record lo elimina e poi lo inserisce nuovo
    // altrimenti lo inserisce nuovo
    public static function replace(string $table, array $data) {
        $sql = SQL::replace($table, $data);
        return db_exec($sql);
    }
    // cerca di eseguire una update del record e se fallisce tenta un inserimento
    // act=0 significa che sto eseguito un update, 1 un insert
    public static function confirm(string $table, array $data, string $where) {
        $count = self::count($table, $where);
        if (0 === $count) {
            $sql = SQL::insert($table, $data);
            $result = db_exec($sql);
            $act = 1;
        } else {
            $sql = SQL::update($table, $where, $data);
            $result = db_exec($sql);
            $act = 0;
        }
        return $act;
    }

    //----------------------------------------------------------------------------
    //
    //----------------------------------------------------------------------------
    public static function count(string $table, string $sql_where = ''): int{
        $sql = "select count(*) as count from " . $table;
        $sql .= $sql_where != '' ? " where $sql_where" : '';
        $rs = qry($sql);
        return intval($rs[0]['count']);
    }
    public static function select(string $table, string $sql_where = '', array $opt = []) {
        $sql = SQL::select($table, $opt);
        return $rs = db_qry($sql);
    }
    /*
    // ottieni a runtime i valori di un campo enum in un array
    public static function get_enum_vals($f, $line='', $file=''){
    // usa err_object
    if(db_qry('SHOW COLUMNS FROM '.$this->name.' LIKE \''.$f.'\'')){
    $a = $this->db->rs2a();
    $str = $a['Type'];
    $str = substr($str, 6, strlen($str)-8);// tolgo descrizioni
    $str = str_replace("','",',',$str);
    return explode(',', $str);
    } else {
    return false;
    }
    }
     */
    // get fileds of a table
    //public static function get_fields() {
    //}
}
//
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/../Test.php';
}