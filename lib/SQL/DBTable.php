<?php

// esegui operazioni comuni su una tabella del db
class DBTable {

    var $table = '';
    var $db = null; //oggetto database, a cui postare le richieste

    function __construct($t, $db = null) {
        if (!empty($t)) {
            $this->table = $t;
        }
        if (empty($this->table)) {
            die(__LINE__ . '#' . __FILE__ . ' DBTable constructed without a table name');
        }
        if (!is_null($db)) {
            $this->db = $db;
        } else {
            $msg = sprintf('Errore %s ', 'manca istanza database' );
            throw new Exception($msg);
        }
    }

    function count($sql_where = '', $line = '', $file = '') {
        $sql = "select count(*) from " . $this->table;
        $sql.= $sql_where != '' ? " where $sql_where" : '';
        if ($this->db->qry($sql, $line, $file)) {
            return $this->db->rs2i();
        } else {
            return false;
        }
    }

    /* // ottieni a runtime i valori di un campo enum in un array
      function get_enum_vals($f, $line='', $file=''){
      // usa err_object
      if($this->db->qry('SHOW COLUMNS FROM '.$this->name.' LIKE \''.$f.'\'', $line, $file)){
      $a = $this->db->rs2a();
      $str = $a['Type'];
      $str = substr($str, 6, strlen($str)-8);// tolgo descrizioni
      $str = str_replace("','",',',$str);
      return explode(',', $str);
      } else {
      return false;
      }
      } */

    function insert($a, $line = '', $file = '') {
        $sql = SQL::insert($this->table, $a) . "\n";
        // usa err_object
        return $this->db->qry_cmd($sql, $line, $file);
    }

    function update($a, $where = '', $line = '', $file = '') {
        $sql = SQL::update($this->table, $where, $a) . "\n";
        return $this->db->qry_cmd($sql, $line, $file);
    }

    function delete($where = '', $line = '', $file = '') {
        $sql = SQL::delete($this->table, $where) . "\n";
        return $this->db->qry_cmd($sql, $line, $file);
    }

    // cerca di eseguire una update del record e se fallisce tenta un inserimento
    // act=0 significa che sto eseguito un update, 1 un insert
    function confirm($a, $where, $line = '', $file = '', $act = 0) {
        $sql = SQL::update($this->table, $where, $a);
        $result = $this->db->qry_cmd($sql, $line, $file);
        $act = 0;
        if ($this->db->lines == 0) {
            $sql = SQL::insert($this->table, $a);
            $result = $this->db->qry_cmd($sql, $line, $file);
            $act = 1;
        }
        return $result;
    }

    // se trova il record lo elimina e poi lo inserisce nuovo
    // altrimenti lo inserisce nuovo
    function replace($a, $line = '', $file = '') {
        $sql = SQL::replace($this->table, $a) . "\n";
        return $this->db->qry_cmd($sql, $line, $file);
    }

    function select($opt = ) {
        $sql = SQL::select($this->table, $opt);
        if ($this->db->qry($sql, __LINE__, __FILE__)) {
            return $this->db->rs;
        } else {
            echo $this->db->getErrMsg();
            return false;
        }
    }

    //function get_fields() {
    //    $fields = mysql_list_fields($this->table, $this->connection->name, $this->connection->_con_id);
    //    $fields_num = mysql_num_fields($fields);
    //
    //    for ($i = 0; $i < $fields_num; $i++) {
    //        // mysql_field_flags(), mysql_field_len(), mysql_field_name(), and mysql_field_type()
    //        //echo mysql_field_name($fields, $i) . "\n";
    //    }
    //}
}
