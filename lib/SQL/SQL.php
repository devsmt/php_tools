<?php

//
// TODO: mai ritornare "&&" davanti le clausole where, qusto parametro dovrebbe essere gestito altrove
//
class SQL {
    /* ------------------------------------------------------------------------------
      SQL GENERATION
      ------------------------------------------------------------------------------ */

    // quote adatto ai nomi di campo
    function quote($f) {
        $f = trim($f);
        // se e' gie' quotato, e' ok
        if (substr($f, 0, 1) == '`' && substr($f, -1) == '`') {
            return $f;
        }
        return "`$f`";
    }

    function quotev($v) {
        if (!is_int($v)) {
            // non quotare se sembra una funzione come ad esempio now() o sum()
            // da aggiornare con l'uso di una reg exp?
            // substr($v,-1) != ')' ||
            if (strtolower($v) == 'null') { /* potrebbe non essere necessario campi null */
                $v = 'NULL';
            } elseif (!ereg('[a-z]+\(([a-z]*)\)', $v, $a_regs)) {
                $v = "'" . SQL::escape($v) . "'";
            }
        }
        return $v;
    }

    //
    // previene sql iniection
    // al mmomento utilizza solo mysql
    //
    function escape($s) {
        if (!isset($GLOBALS[W_DB_INSTANCE])) {
            // evitiamo di aprire una connessione solo per fare l'escape di una stringa
            return mysql_escape_string($s);
        } else {
            if (get_magic_quotes_gpc()) {
                return mysql_real_escape_string(stripslashes($s));
            } else {
                return mysql_real_escape_string($s);
            }
        }
    }

    // ritorna una stringa nella forma a=1,b=2,...
    // da un array associativo nella forma 'a'=>1,'b'=>2
    function sequence_val($val) {
        if (!is_array($val)) {
            return '';
        }
        $a_regs = array();
        $field_sep = ',';
        $str = array();
        foreach ($val as $k => $v) {
            $str[] = SQL::quote($k) . "=" . SQL::quotev($v);
        }
        return implode($field_sep, $str);
    }

    // ritorna un parametro della clausola where
    // code
    // es. (a=1 || a=2 || a=3)
    //          ^$c2      ^field
    // /code
    function where_range($field, $a_v, $c2 = '||') {
        $sql = '';
        if (count($a_v) > 0) {
            $a_s = array();
            foreach ($a_v as $i => $v) {
                $v = (is_int($v) ? $v : "'" . $v . "'");
                //il valore va tra virgolette?
                $a_s[] = sprintf('%s=%s', SQL::quote(SQL::escape($field)), SQL::escape($v));
            }
            $sql = sprintf(' ( %s ) ', implode($c2, $a_s));
        }
        return $sql;
    }

    function where_in($field, $a_v) {
        $sql = '';
        // gestire tipi non interi, come le str che richiedono essere quotate
        if (!empty($a_v)) {
            foreach ($a_v as $i => $v) {
                if (is_string($v)) {
                    if (strtolower($v) == 'null') { /* potrebbe non essere necessario campi null */
                        $v = 'NULL';
                    } elseif (!ereg('[a-z]+\(([a-z]*)\)', $v, $a_regs)) {
                        $v = "'" . SQL::escape($v) . "'";
                    }
                }
                $a_v[$i] = $v;
            }
            $in = implode(',', $a_v);
            $sql = SQL::quote($field) . " in ( $in )";
        }
        return $sql;
    }

    // ritorna un parametro della clausola where
    // code
    // es.
    //!(a like "sa" || a LIKE "sb" || a="sc")
    //             ^$c2              ^field
    // /code
    function where_range_like($field, $a_v = null) {
        $c2 = ' || ';
        $sql = '';
        if (!is_array($field)) {
            if (count($a_v) > 0) {
                $a_s = array();
                foreach ($a_v as $i => $v) {
                    $v = SQL::escape($v);
                    $v = SQL::_ensure_like_char($v);
                    $a_s[] = sprintf('%s LIKE "%s"', SQL::quote(SQL::escape($field)), $v);
                }
                $sql = sprintf('( %s )', implode($c2, $a_s));
            }
        } else {
            $a_s = array();
            foreach ($field as $f => $v) {
                $v = SQL::escape($v);
                $v = SQL::_ensure_like_char($v);
                $a_s[] = sprintf('%s LIKE "%s"', SQL::quote(SQL::escape($f)), $v);
            }
            $sql = sprintf('( %s )', implode($c2, $a_s));
        }
        return $sql;
    }

    // assicura che il valore contenga il simbolo di espansione per la clausola LIKE
    function _ensure_like_char($v) {
        if (strpos($v, '%') !== false) {
            return $v;
        } else {
            return "%$v%";
        }
    }

    // ritorna sql necessario a trovare i record corrispondenti ad un intervallo
    // su di un campo date
    function where_range_date($field, $data_da = '', $data_a = '') {
        $field = SQL::escape($field);
        $data_da = SQL::escape($data_da);
        $data_a = SQL::escape($data_a);
        $sql = "(UNIX_TIMESTAMP($field) > UNIX_TIMESTAMP('$data_da')) AND (UNIX_TIMESTAMP($field) < UNIX_TIMESTAMP('$data_a'))";
        return $sql;
    }

    // costruisce la clausola sql ORDER BY
    // la struttura in input deve essere
    //
    // array(
    //   array($field, $flag='ASC')
    // )
    //
    // array(field, field ... )
    //
    // field
    function orderby($a = array()) {
        // assert("is_array($a)")
        // assert("is_array($a[0])")
        $sql = '';
        if (is_string($a)) {
            $a = array(array($a, 'ASC'));
        } elseif (is_array($a) && !empty($a) && isset($a[0]) && !is_array($a[0])) {
            if (strtoupper($a[1]) == 'ASC' || strtoupper($a[1]) == 'DESC') {
                $a = array($a);
            } else {
                $old = $a;
                $a = array();
                foreach ($old as $f) {
                    $a[] = array($f);
                }
            }
        }
        if (!empty($a[0][0]) && is_array($a[0])) {
            $o = ' ORDER BY ';
            for ($i = 0; $i < count($a); $i++) {
                if ($a[$i][0] != '') {
                    $o.= sprintf('%s %s,', SQL::quote(SQL::escape($a[$i][0])), SQL::escape(isset($a[$i][1]) ? $a[$i][1] : 'ASC'));
                }
            }
            $sql = substr($o, 0, -1) . "\n";
        }
        return $sql;
    }

    function limit($start = 0, $offset = null) {
        if (empty($start) && empty($offset)) {
            return '';
        } elseif (is_null($offset)) {
            return " LIMIT $start";
        }
        return sprintf(" LIMIT %s,%s", SQL::escape($start), SQL::escape($offset));
    }

    function page_limit($page, $offset) {
        $start = $page * $offset;
        return SQL::limit($start, $offset);
    }

    //
    // select semplice
    // \code
    // SELECT
    // select_expression, ...
    // FROM table_references
    // [WHERE where_definition]
    // [GROUP BY {unsigned_integer | col_name | formula} [ASC | DESC], ...]
    // [ORDER BY {unsigned_integer | col_name | formula} [ASC | DESC] ,...]
    // [LIMIT [offset,] rows]
    // \endcode
    // \param $s str select_stmt
    // \param $t str table name
    //
    // Query Cache does simple optimization to check if query can be cached. As I mentioned only SELECT queries are cached - so it looks at first letter of the query and if it is e'Se' it proceeds with query lookup in cache if not - skips it.
    function select($t, $opt = array()) {
        extract(array_merge(array('s' => '*', 'where' => null, 'group_by' => null, 'order_by' => null, 'pos' => 0, 'limit' => null), $opt));
        if (is_array($s)) {
            $s = Arr::deleteEmpty($s);
            $s = implode(', ', $s);
        }
        if (is_array($where)) {
            $where = Arr::deleteEmpty($where);
            $where = implode(' && ', $where);
        }
        if (is_array($group_by)) {
            $group_by = Arr::deleteEmpty($group_by);
            $group_by = implode(',', $group_by);
        }
        return "SELECT $s FROM " . SQL::quote($t) . ' ' . ((!empty($where) ? " WHERE $where " : '') . (!empty($group_by) ? " GROUP BY $group_by " : '') . SQL::orderby($order_by) . SQL::limit($pos, $limit));
    }

    //
    // echo '<pre>';
    // echo SQL::simple_select('*',
    // 'table as ta'.SQL::select_join('table2 as tb','ta.id=tb.id'),
    // 'a>0 && a<10',
    // 'a',
    // 'b,a',
    // '0,5');
    // ritorna una str tipo: "left join table2 on a=z"
    function join($t, $on, $join_type = 'left') {
        return "\n $join_type join $t on $on";
    }

    // INSERT [LOW_PRIORITY | DELAYED] [IGNORE]
    // [INTO] tbl_name [(col_name,...)]
    // VALUES (expression,...),(...),...
    // or  INSERT [LOW_PRIORITY | DELAYED] [IGNORE]
    // [INTO] tbl_name [(col_name,...)]
    // SELECT ...
    // or  INSERT [LOW_PRIORITY | DELAYED] [IGNORE]
    // [INTO] tbl_name
    // SET col_name=expression, col_name=expression, ...
    function insert($t, $val = array(), $flags = null) {
        return "INSERT INTO " . SQL::quote($t) . " SET " . SQL::sequence_val($val);
    }

    // UPDATE [LOW_PRIORITY] [IGNORE] tbl_name
    // SET col_name1=expr1, [col_name2=expr2, ...]
    // [WHERE where_definition]
    // [LIMIT #]
    function update($t, $where, $val) {
        return "UPDATE " . SQL::quote($t) . " SET " . SQL::sequence_val($val) . ' WHERE ' . $where;
    }

    // DELETE [LOW_PRIORITY] FROM tbl_name
    // [WHERE where_definition]
    // [LIMIT rows]
    function delete($t, $where = '') {
        if (empty($where)) {
            $where = '1';
        }
        return "DELETE FROM $t WHERE $where";
    }

    // REPLACE [LOW_PRIORITY | DELAYED]
    // [INTO] tbl_name [(col_name,...)]
    // {VALUES | VALUE} ({expr | DEFAULT},...),(...),...
    // Or:
    // REPLACE [LOW_PRIORITY | DELAYED]
    // [INTO] tbl_name
    // SET col_name={expr | DEFAULT}, ...
    // Or:
    // REPLACE [LOW_PRIORITY | DELAYED]
    // [INTO] tbl_name [(col_name,...)]
    // SELECT ...
    //
    // REPLACE works exactly like INSERT, except that if an old row in the table has the
    // same value as a new row for a PRIMARY KEY or a UNIQUE index, the old row is deleted before the new row is inserted
    //
    function replace($t, $val = array(), $flags = null) {
        return "REPLACE INTO $t SET " . SQL::sequence_val($val);
    }

    // and( "field=1", "field2!=0", ... )
    function _and_() {
        $a = func_get_args();
        return '(' . implode(' && ', $a) . ')';
    }

    // or( "field=1", "field2!=0", ... )
    function _or_() {
        $a = func_get_args();
        return '(' . implode(' || ', $a) . ')';
    }

    function ifs($condition, $sql) {
        if ($condition)
            return $sql;
        else
            return '';
    }

}

class SQLTable {
    /* ------------------------------------------------------------------------------
      SQL DB MANIPULATION
      ------------------------------------------------------------------------------ */

    // CREATE TABLE `test2` (
    // `id` VARCHAR( 36 ) NOT NULL ,
    // `nome` VARCHAR( 36 ) NOT NULL
    // );
    function table_create($name, $fields, $type = 'varchar(255)') {
        $fields_count = count($fields);
        $sql = "CREATE TABLE " . SQL::quote($name) . " (\n";
        for ($i = 0; $i < $fields_count; $i++) {
            $sql.= SQL::quote($fields[$i]) . " $type ";
            if ($i < ($fields_count - 1)) {
                $sql.= ",\n";
            }
        }
        $sql.= ");";
        return $sql;
    }

    //
    function table_delete($table) {
        $sql = "DELETE from " . SQL::quote($table);
        return $sql;
    }

    //
    function table_drop($table) {
        $sql = "DROP TABLE IF EXISTS " . SQL::quote($table);
        return $sql;
    }

    //
    function table_alter_field($table, $field, $new_tipe = 'VARCHAR( 222 )') { //NOT NULL
        $sql = "ALTER TABLE " . SQL::quote($table) . " CHANGE `$field` `$field` $new_tipe ";
        return $sql;
    }

    // string $field field 1, field2, field3
    function table_add_index($table, $field, $type = '') {
        //ALTER [IGNORE] TABLE tbl_name
        //ADD INDEX [index_name] (index_col_name,...)
        //or    ADD PRIMARY KEY (index_col_name,...)
        //or    ADD UNIQUE [index_name] (index_col_name,...)
        //or    ADD FULLTEXT [index_name] (index_col_name,...)
        //CREATE [UNIQUE|FULLTEXT] INDEX index_name
        //   ON tbl_name (col_name[(length)],... )
        $sql = "CREATE $type INDEX $field ON $table ($field)";
        return $sql;
    }

    function table_add_field($t, $f, $type = 'VARCHAR( 22 )') {
        $sql = "ALTER TABLE `$t` ADD `$f`
        $type NOT NULL ;";
        return $sql;
    }

    //
    // LOAD DATA [LOW_PRIORITY | CONCURRENT] [LOCAL] INFILE 'file_name.txt'
    // [REPLACE | IGNORE]
    // INTO TABLE tbl_name
    // [FIELDS
    // [TERMINATED BY '\t']
    // [[OPTIONALLY] ENCLOSED BY '']
    // [ESCAPED BY '\\' ]
    // ]
    // [LINES TERMINATED BY '\n']
    // [IGNORE number LINES]
    // [(col_name,...)]
    //
    // generale per uploadare files
    //
    function load_file($file, $table, $terminated_by = ',', $enclosed_by = '"', $lines_terminated_by = '\n') {
        delete_table($table);
        $sql = "LOAD DATA  INFILE '$file'
        REPLACE
        INTO TABLE $table
        FIELDS
        TERMINATED BY '$terminated_by'
        ENCLOSED BY '$enclosed_by'
        LINES TERMINATED BY '$lines_terminated_by' ";
        return $sql;
    }

}
