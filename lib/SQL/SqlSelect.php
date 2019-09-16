<?php

/*

funzione: costruisce uno stm select in modo OOP
          NOTA:  viene preferito il modo procedurale/funzionale

// SELECT
//     [ALL | DISTINCT | DISTINCTROW ]
//       [HIGH_PRIORITY]
//       [STRAIGHT_JOIN]
//       [SQL_SMALL_RESULT] [SQL_BIG_RESULT] [SQL_BUFFER_RESULT]
//       [SQL_CACHE | SQL_NO_CACHE] [SQL_CALC_FOUND_ROWS]
//     select_expr, ...
//     [INTO OUTFILE 'file_name' export_options
//       | INTO DUMPFILE 'file_name']
//     [FROM table_references
//       [WHERE where_definition]
//       [GROUP BY {col_name | expr | position}
//         [ASC | DESC], ... [WITH ROLLUP]]
//       [HAVING where_definition]
//       [ORDER BY {col_name | expr | position}
//         [ASC | DESC] , ...]
//       [LIMIT {[offset,] row_count | row_count OFFSET offset}]
//       [PROCEDURE procedure_name(argument_list)]
//       [FOR UPDATE | LOCK IN SHARE MODE]]


// vedi sql_select in sql.lib.php
class sqlSelectStmnt {
    var $fields = '';
    var $table = '';
    var $where = '';
    var $group_by = '';
    var $having = '';
    var $orderby = '';
    var $limit = '';
    var $_fields = ;
    var $_table = ;
    var $_where = ;
    var $_group_by = ;
    var $_having = ;
    var $_orderby = ;
    var $_limit = ;

    // function parseFields() {
    //     if($this->fields=='*')
    //         $this->_fields = array('*');
    //     else
    //         $this->_fields = explode(',',$this->fields);
    // }
    //
    // function parseTable(){}
    //
    // function parseWhere(){}
    // function parseGroup_by(){}
    // function parseHaving(){}
    // function parseOrderby(){}
    // function parseLimit(){}
    //
    // // fai il lavoro sporco
    // function doParse(){
    //     $this->arseFields();
    //     $this->parseTable();
    //     $this->parseWhere();
    //     $this->parseGroup_by();
    //     $this->parseHaving();
    //     $this->parseOrderby();
    //     $this->parseLimit();
    // }

}
class sqlParser extends sqlSelectStmnt {
    var $sql = '';
    // Query Cache does simple optimization to check if query can be cached. As I mentioned only SELECT queries are cached - so it looks at first letter of the query and if it is “S” it proceeds with query lookup in cache if not - skips it.
    var $format = 'SELECT %s FROM %s';
    var $result = ;
    var $stm = null;
    function sqlParser($sql) {
        $this->sql = $this->clear($sql);
        $this->stm = new sqlSelectStmnt();
    }
    function parse() {
        $this->buildFormat();
        //var_dump($this->sql, $this->format);
        $this->result = sscanf($this->sql, $this->format);
        //var_dump($this->result);
        $this->interpreteResult();
    }
    // indica se il comando contiene una proprietŕ indicata
    function hasStatement($stmtStr) {
        return (strpos($this->sql, $stmtStr) > 1);
    }
    function buildFormat() {
        if ($this->hasStatement('WHERE')) {
            $this->format.= ' WHERE %s';
        }
        if ($this->hasStatement('GROUP BY')) {
            $this->format.= ' GROUP BY %s';
        }
        if ($this->hasStatement('HAVING')) {
            $this->format.= ' HAVING %s';
        }
        if ($this->hasStatement('ORDERBY')) {
            $this->format.= ' ORDERBY %s';
        }
        if ($this->hasStatement('LIMIT')) {
            $this->format.= ' LIMIT %s';
        }
    }
    function interpreteResult() {
        $i = 0;
        $this->stm->fields = $this->result[$i];
        $i++;
        $this->stm->table = $this->result[$i];
        if ($this->hasStatement('WHERE')) {
            $i++;
            $this->stm->where = $this->result[$i];
        }
        if ($this->hasStatement('GROUP BY')) {
            $i++;
            $this->stm->group_by = $this->result[$i];
        }
        if ($this->hasStatement('HAVING')) {
            $i++;
            $this->stm->having = $this->result[$i];
        }
        if ($this->hasStatement('ORDERBY')) {
            $i++;
            $this->stm->orderby = $this->result[$i];
        }
        if ($this->hasStatement('LIMIT')) {
            $i++;
            $this->stm->limit = $this->result[$i];
        }
    }
    function clear($str) {
        $str = str_replace("\n", '', $str);
        $str = str_replace("\l", '', $str);
        $str = str_replace("\t", '', $str);
        $str = str_replace("  ", ' ', $str);
        // uppercase
        $str = str_replace('select', 'SELECT', $str);
        $str = str_replace('from', 'FROM', $str);
        $str = str_replace('where', 'WHERE', $str);
        $str = str_replace('group by', 'GROUP BY', $str);
        $str = str_replace('having', 'HAVING', $str);
        $str = str_replace('orderby', 'ORDERBY', $str);
        $str = str_replace('limit', 'LIMIT', $str);
        return trim($str);
    }
}

// $sql = "select * from tabella where ciccio and baluccio";
// $p = new sqlParser($sql);
// $p->parse();
// var_dump($p->stm);


// SELECT [STRAIGHT_JOIN] [SQL_SMALL_RESULT] [SQL_BIG_RESULT] [SQL_BUFFER_RESULT]
//        [HIGH_PRIORITY]
//        [DISTINCT | DISTINCTROW | ALL]
//     select_expression,...
//     [INTO {OUTFILE | DUMPFILE} 'file_name' export_options]
//     [FROM table_references
//         [WHERE where_definition]
//         [GROUP BY {unsigned_integer | col_name | formula} [ASC | DESC], ...]
//         [HAVING where_definition]
//         [ORDER BY {unsigned_integer | col_name | formula} [ASC | DESC] ,...]
//         [LIMIT [offset,] rows]
//         [PROCEDURE procedure_name]
//         [FOR UPDATE | LOCK IN SHARE MODE]]
define('SQL_SELECT_LOCK_SHARE', 1);
define('SQL_SELECT_LOCK_FOR_UPDATE', 2);
class sqlSelect extends sqlSelectStmnt {

    // per compatibilitŕ con parser
    // var $fields ='';
    // var $table = '';
    // var $where ='';
    // var $group_by ='';
    // var $having ='';
    // var $orderby ='';
    // var $limit ='';

    var $_fields = ;
    var $_tables = ; //[$table,$on,$join_type='left']
    var $_where = ; // [WHERE where_definition]
    var $_group_by = ; // [GROUP BY {unsigned_integer | col_name | formula} [ASC | DESC], ...]
    var $_having = ; // [HAVING where_definition]
    var $_order_by = ; // [ORDER BY {unsigned_integer | col_name | formula} [ASC | DESC] ,...]
    // [['field','flag']]
    var $pos = 0; // [LIMIT [offset,] rows]
    var $limit = null; // se settato, usa clausola limit
    var $_procedure; // [PROCEDURE procedure_name]
    var $_lock = 0; // [FOR UPDATE | LOCK IN SHARE MODE]]
    function sqlSelect($t = '') {
        $this->addTable($t);
    }
    function addTable($t, $on = '', $join_type = 'left') {
        if ($t != '') {
            if (count($this->_tables) == 0) {
                $this->_tables[] = [$t, '', ''];
            } else {
                if ($on != '') $this->_tables[] = [$t, $on, $join_type];
                else $this->err_msg[] = "per la tabella [$t] occorre specificare il punto di join";
            }
        }
    }
    function addField($str) {
        $this->_fields[] = $str;
    }
    function addFields($a) {
        if (!empty($a)) {
            for ($i = 0;$i < count($a);$i++) {
                $this->addField($a[$i]);
            }
        }
    }
    function addWhere($str = '', $i = null) {
        if (is_null($i)) {
            if ($str != '') {
                $this->_where[] = $str;
            } else {
                //echo " where = ''; !!! ";

            }
        } else {
            if (isset($this->_where[$i])) {
                $this->_where[] = $this->_where[$i];
            }
            $this->_where[$i] = $str;
        }
    }
    // capita di costruire gruppi di query in cui varia solo un parametro(all'inteno di un ciclo...)
    function popWhere($index) {
        unset($this->_where[$index]);
    }
    function addGroupBy($str) {
        $this->_group_by[] = $str;
    }
    function addHaving($str) {
        $this->_having[] = $str;
    }
    function addOrderby($str = '', $flag = 'ASC') {
        if ($str != '') $this->_order_by[] = [$str, $flag];
    }
    function getTables() {
        $s = $this->_tables[0][0];
        for ($i = 1;$i < count($this->_tables);$i++) {
            $s.= sql_select_join($this->_tables[$i][0], $this->_tables[$i][1], $this->_tables[$i][2]);
        }
        return $s;
    }
    function getFields() {
        return (!empty($this->_fields)) ? implode(',', $this->_fields) : '*';
    }
    function getWhere() {
        // se per caso entra un valore nullo evito di aggiungere clausole '&&' errate
        foreach ($this->_where as $k => $v) {
            if ($v == '') unset($this->_where[$k]);
        }
        return implode(' && ', $this->_where);
    }
    function getGroupBy() {
        return implode(',', $this->_group_by);
    }
    // occorre un controllo per ogni campo, che sia anche trai group by
    function getHavings() {
        foreach ($this->_having as $k => $v) {
            if (empty($v)) unset($this->_having[$k]);
        }
        return implode(' && ', $this->_having);
    }
    function getOrderBy() {
        $a = ;
        foreach ($this->_order_by as $k => $order) {
            $a[] = $order[0] . ' ' . $order[1];
        }
        return implode(',', $a);
    }
    function getLimit() {
        return !empty($this->limit) ? ' ' . $this->pos . ',' . $this->limit : '';
    }
    function toString() {
        if (empty($this->_tables)) {
            app_error(' specificare almeno un nome di tabella valido');
            return $this->getErrMsg();
        }
        $limit = $this->getLimit();
        $where = $this->getWhere();
        $group_by = $this->getGroupBy();
        $order_by = $this->getOrderBy();
        $havings = $this->getHavings();
        return 'SELECT ' . $this->getFields() . " \n FROM " . $this->getTables() . (!empty($where) ? "\n WHERE $where " : '') . (!empty($group_by) ? "\n GROUP BY $group_by " : '') . (!empty($havings) ? "\n HAVING $havings " : '') . (!empty($order_by) ? "\n ORDER BY $order_by " : '') . (!empty($limit) ? "\n LIMIT $limit " : '');
    }
}
*/