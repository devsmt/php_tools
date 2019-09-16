<?php
declare (strict_types = 1);
//
// TODO: mai ritornare "&&" davanti le clausole where, qusto parametro dovrebbe essere gestito altrove
//
class SQL {
    // -------------------------------------------------------------------------
    // SQL GENERATION
    // -------------------------------------------------------------------------
    // quote adatto ai nomi di campo
    public static function quote($f) {
        $f = trim($f);
        // se e' gie' quotato, e' ok
        if (substr($f, 0, 1) == '`' && substr($f, -1) == '`') {
            return $f;
        }
        return "`$f`";
    }
    public static function quotev($v) {
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
    public static function escape($s) {
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
    public static function sequence_val(array $val) {
        if (!is_array($val)) {
            return '';
        }
        $a_regs = [];
        $field_sep = ',';
        $a_str = [];
        foreach ($val as $k => $v) {
            $a_str[] = SQL::quote($k) . "=" . SQL::quotev($v);
        }
        return implode($field_sep, $a_str);
    }
    // ritorna un parametro della clausola where
    // code
    // es. (a=1 || a=2 || a=3)
    //          ^$c2      ^field
    // /code
    public static function where_range($field, $a_v, $c2 = '||') {
        $sql = '';
        if (count($a_v) > 0) {
            $a_s = [];
            foreach ($a_v as $i => $v) {
                $v = (is_int($v) ? $v : "'" . $v . "'");
                //il valore va tra virgolette?
                $a_s[] = sprintf('%s=%s', SQL::quote(SQL::escape($field)), SQL::escape($v));
            }
            $sql = sprintf(' ( %s ) ', implode($c2, $a_s));
        }
        return $sql;
    }
    public static function where_in($field, $a_v) {
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
    public static function where_range_like($field, $a_v = null) {
        $c2 = ' || ';
        $sql = '';
        if (!is_array($field)) {
            if (count($a_v) > 0) {
                $a_s = [];
                foreach ($a_v as $i => $v) {
                    $v = SQL::escape($v);
                    $v = SQL::_ensure_like_char($v);
                    $a_s[] = sprintf('%s LIKE "%s"', SQL::quote(SQL::escape($field)), $v);
                }
                $sql = sprintf('( %s )', implode($c2, $a_s));
            }
        } else {
            $a_s = [];
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
    public static function _ensure_like_char($v) {
        if (strpos($v, '%') !== false) {
            return $v;
        } else {
            return "%$v%";
        }
    }
    // ritorna sql necessario a trovare i record corrispondenti ad un intervallo
    // su di un campo date
    public static function where_range_date($field, $data_da = '', $data_a = '') {
        $field = SQL::escape($field);
        $data_da = SQL::escape($data_da);
        $data_a = SQL::escape($data_a);
        $sql = "(UNIX_TIMESTAMP($field) > UNIX_TIMESTAMP('$data_da')) AND (UNIX_TIMESTAMP($field) < UNIX_TIMESTAMP('$data_a'))";
        return $sql;
    }
    // costruisce la clausola sql ORDER BY
    // la struttura in input deve essere
    //
    // [
    //   [$field, $flag='ASC']
    // ]
    //
    // [field, field ... ]
    //
    // field
    public static function orderby($a = []) {
        // assert("is_array($a)")
        // assert("is_array($a[0])")
        $sql = '';
        if (is_string($a)) {
            $a = [[$a, 'ASC']];
        } elseif (is_array($a) && !empty($a) && isset($a[0]) && !is_array($a[0])) {
            if (strtoupper($a[1]) == 'ASC' || strtoupper($a[1]) == 'DESC') {
                $a = [$a];
            } else {
                $old = $a;
                $a = [];
                foreach ($old as $f) {
                    $a[] = [$f];
                }
            }
        }
        if (!empty($a[0][0]) && is_array($a[0])) {
            $o = ' ORDER BY ';
            for ($i = 0; $i < count($a); $i++) {
                if ($a[$i][0] != '') {
                    $o .= sprintf('%s %s,', SQL::quote(SQL::escape($a[$i][0])), SQL::escape(isset($a[$i][1]) ? $a[$i][1] : 'ASC'));
                }
            }
            $sql = substr($o, 0, -1) . "\n";
        }
        return $sql;
    }
    public static function limit($start = 0, $offset = null) {
        if (empty($start) && empty($offset)) {
            return '';
        } elseif (is_null($offset)) {
            return " LIMIT $start";
        }
        return sprintf(" LIMIT %s,%s", SQL::escape($start), SQL::escape($offset));
    }
    public static function page_limit($page, $offset) {
        $start = $page * $offset;
        return SQL::limit($start, $offset);
    }
    // and( "field=1", "field2!=0", ... )
    public static function _and_() {
        $a = func_get_args();
        return '(' . implode(' && ', $a) . ')';
    }
    // or( "field=1", "field2!=0", ... )
    public static function _or_() {
        $a = func_get_args();
        return '(' . implode(' || ', $a) . ')';
    }
    public static function ifs($condition, $sql) {
        if ($condition) {
            return $sql;
        } else {
            return '';
        }
    }
    // determina se è una query select
    public static function isSelect($sql) {
        $sql = trim($sql);
        $l = strlen('select');
        $sql_begin = strtolower(substr($sql, 0, $l));
        return $sql_begin == 'select';
    }
    //----------------------------------------------------------------------------
    //
    //----------------------------------------------------------------------------
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
    // Query Cache does simple optimization to check if query can be cached.
    // As I mentioned only SELECT queries are cached - so it looks at first letter of the query and if it is e 'Se'
    // it proceeds with query lookup in cache if not - skips it.
    public static function select(string $table, array $opt = []): string{
        $rm_empty = function ($a_data) {
            return array_values(array_filter($a_data, function ($v) {
                // false will be skipped
                return !empty($v);
            }));
        };
        $prepend_and = function ($a_where) {
            // per ogni $where condition se non inizia con '&&' o '||' lo aggiunge automaticamente
            return array_map(function ($val) {
                $val = trim($val);
                $b = substr($val, 0, 2);
                if (in_array($b, ['&&', '||'])) {
                    $val = "&& $val";
                }
                return $val;
            }, $a_where);
        };
        extract(array_merge([
            'fields' => '*',
            'where' => null,
            'group_by' => null,
            'order_by' => null,
            // paging:
            // 'pos' => 0,
            // 'limit' => null,
            // 'page' => 1,
            // 'page_len' => 30
        ], $opt));
        if (is_array($fields)) {
            $fields = $rm_empty($fields);
            $fields = implode(', ', $fields);
        }
        if (is_array($where)) {
            $where = $rm_empty($where);
            $where = $prepend_and($where);
            $where = implode(' ', $where);
        }
        if (is_array($group_by)) {
            $group_by = $rm_empty($group_by);
            $group_by = implode(',', $group_by);
        }
        $sql_limit = '';
        if (isset($pos) && isset($limit)) {
            $sql_limit = SQL::limit($pos, $limit);
        } elseif (isset($page) && isset($page_len)) {
            $sql_limit = SQL::page_limit($page, $page_len);
        }
        //
        return sprintf(
            "SELECT %s FROM %s WHERE (1=1) %s %s %s",
            $fields,
            SQL::quote($table),
            $where,
            (!empty($group_by) ? " GROUP BY $group_by " : ''),
            SQL::orderby($order_by),
            $sql_limit
        );
    }
    //
    // echo '<pre>';
    // echo SQL::select('*',
    // 'table as ta'.SQL::select_join('table2 as tb','ta.id=tb.id'),
    // 'a>0 && a<10',
    // 'a',
    // 'b,a',
    // '0,5');
    // ritorna una str tipo: "left join table2 on a=z"
    public static function join($t, $on, $join_type = 'left') {
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
    public static function insert($t, $val = [], $flags = null) {
        return "INSERT INTO " . SQL::quote($t) . " SET " . SQL::sequence_val($val);
    }
    // UPDATE [LOW_PRIORITY] [IGNORE] tbl_name
    // SET col_name1=expr1, [col_name2=expr2, ...]
    // [WHERE where_definition]
    // [LIMIT #]
    public static function update($t, $where, $val) {
        return "UPDATE " . SQL::quote($t) . " SET " . SQL::sequence_val($val) . ' WHERE ' . $where;
    }
    // DELETE [LOW_PRIORITY] FROM tbl_name
    // [WHERE where_definition]
    // [LIMIT rows]
    public static function delete($t, $where = '') {
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
    public static function replace($t, $val = [], $flags = null) {
        return "REPLACE INTO $t SET " . SQL::sequence_val($val);
    }
    //
    // crea un stmt insert bulk anx exec it
    public static function bulk_insert($table, $labels = null, $data, $truncate = FALSE) {
        if (empty($labels)) {
            $labels = array_keys($data[0]);
        }
        $i = 0;
        if ($truncate) {
            self::truncate($table);
        }
        $sql = "INSERT INTO $table ($labels) VALUES ";
        foreach ($data as $key => $value) {
            $i++;
            $sql .= '(\'' . implode('\', \'', array_map('addslashes', array_values($value))) . '\')' .
            (count($data) > $i ? ', ' : '');
        }
        return $sql;
    }
}
//
//
//
class SQLTable {
    /* ------------------------------------------------------------------------------
    SQL DB MANIPULATION
    ------------------------------------------------------------------------------ */
    // CREATE TABLE `test2` (
    // `id` VARCHAR( 36 ) NOT NULL ,
    // `nome` VARCHAR( 36 ) NOT NULL
    // );
    public static function table_create($name, $fields, $type = 'varchar(255)') {
        $fields_count = count($fields);
        $sql = "CREATE TABLE " . SQL::quote($name) . " (\n";
        for ($i = 0; $i < $fields_count; $i++) {
            $sql .= SQL::quote($fields[$i]) . " $type ";
            if ($i < ($fields_count - 1)) {
                $sql .= ",\n";
            }
        }
        $sql .= ");";
        return $sql;
    }
    //
    public static function table_delete($table) {
        $sql = "DELETE from " . SQL::quote($table);
        return $sql;
    }
    //
    public static function table_drop($table) {
        $sql = "DROP TABLE IF EXISTS " . SQL::quote($table);
        return $sql;
    }
    //
    public static function table_alter_field($table, $field, $new_tipe = 'VARCHAR( 222 )') { //NOT NULL
        $sql = "ALTER TABLE " . SQL::quote($table) . " CHANGE `$field` `$field` $new_tipe ";
        return $sql;
    }
    // string $field field 1, field2, field3
    public static function table_add_index($table, $field, $type = '') {
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
    public static function table_add_field($t, $f, $type = 'VARCHAR( 22 )') {
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
    public static function load_file($file, $table, $terminated_by = ',', $enclosed_by = '"', $lines_terminated_by = '\n') {
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
/*
// funzione: filtra alcuni tipi di dato
class SQLFilter {
// toglie caratteri pericolosi da un input che debba essere processato con SQL
// DB::sanitize($s);
public static function alphanum($s, $len = 0) {
$s = preg_replace('/[^a-zA-Z0-9\-_]/', '', $s);
// opzionalmente applica troncamento per lunghezza
if (!empty($len)) {
$s = substr($s, 0, $len);
}
return $s;
}
public static function str($s, $len = 0) {
$s = self::quote($s);
$s = preg_replace('/[^a-zA-Z0-9_\,\;\-\+\*\/\(\)\[\]\:\.\!\?#= ]/', '', $s);
$s = filter_var($s, FILTER_SANITIZE_STRING,
FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH
);
// opzionalmente applica troncamento per lunghezza
if (!empty($len)) {
$s = substr($s, 0, $len);
}
return $s;
}
public static function int($s, $len = 15) {
$s = preg_replace('/[^0-9]/', '', $s);
// elimina input eccessivo
$s = substr($s, 0, $len);
return $s;
}
public static function num($s, $len = 15) {
$s = preg_replace('/[^0-9\.,]/', '', $s);
// elimina input eccessivo
$s = substr($s, 0, $len);
return $s;
}
}
 */
//--------------
// sql template
// prevent that a NON filterd input str can end up in a DB query
// by binding togheter sql and its required validations+filters
// default filter alphachar
class SafeSQL {
    const int = 'int';
    const str = 'str';
    //const safe = 'safe';//skip filter, already safe
    //
    public static function template($sql_template, array $h_filters, array $data): string{
        $sql = $sql_template;
        $data_m = [];
        foreach ($data as $name => $val) {
            $filter_type = H::get($h_filters, $name, null);
            $data_m[$name] = self::_filter_func($val, $filter_type);
        }
        $sql = self::_tmpl($sql, $data_m);
        return $sql;
    }
    //
    // definisci come filtrare la variabile
    protected static function _filter_func($val, $filter_type) {
        // gestione oggetti?
        if (gettype($val) === 'object') {
            if (method_exists($val, 'toString')) {
                $val = $val->toString();
            } else {
                die(implode('/', [__FUNCTION__, __METHOD__, __LINE__]) . ' > object passed must be stringifiable ');
            }
        }
        //
        if (is_callable($filter_type, false)) {
            $val = $filter_type($val);
            return $val;
        }
        $t = strtolower(gettype($filter_type));
        switch ($t) {
        case 'null':
            // use default filtering
            break;
        case 'string':
            switch ($filter_type) {
            case self::int:
                return Safe::int($val);
                break;
            case self::str:
                return SQL::quote(Safe::str($val));
                break;
            default:
                echo __METHOD__ . ' unhandled type:' . sprintf("<pre>%s() L:%s F:%s\n", __FUNCTION__, __LINE__, __FILE__), var_dump(
                    $filter_type
                ), "</pre>\n";
                die();
                break;
            }
            break;
        default:
            echo __METHOD__ . ' unhandled filter type:' . sprintf("%s() L:%s F:%s\n", __FUNCTION__, __LINE__, __FILE__), var_dump(
                $t, $filter_type
            ), "\n";
            die();
            break;
        }
        // TODO: verificare se occorre rilassare questa logica
        if (ctype_digit($val)) {
            return Safe::int($val);
        } else {
            return Safe::alphanum($val);
        }
    }
    //
    // data una stringa interpola i valori passati in a_binds
    // espressi con la sintassi {{nome_var}}
    public static function _tmpl($str_template, $a_binds ) {
        $substitute = function ($buffer, $name, $val) {
            $reg = sprintf('{{%s}}', $name);
            $reg = preg_quote($reg, '/');
            return preg_replace('/' . $reg . '/i', $val, $buffer);
        };
        $buffer = $str_template;
        foreach ($a_binds as $name => $val) {
            $buffer = $substitute($buffer, $name, $val);
        }
        return $buffer;
    }
}
// if colled directly in CLI, run the tests:
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/../Test.php';
    require_once __DIR__ . '/../H.php';
    require_once __DIR__ . '/../Safe.php';
    //
    //
    $sql_template = '{{id}}';
    $data_i = ['id' => 1];
    //
    $safe_sql = SafeSQL::template($sql_template, $h_filters = ['id' => SafeSQL::int], $data_i);
    ok($safe_sql, '1', 'test 1');
    $safe_sql = SafeSQL::template($sql_template, $h_filters = ['id' => SafeSQL::str], $data = ['id' => 'aa']);
    ok($safe_sql, "`aa`", 'test 2');
    //
    // test call
    $safe_sql = SafeSQL::template($sql_template, $h_filters = ['id' => function ($v) {return '*' . $v;}], $data_i);
    ok($safe_sql, '*1', 'test 3');
    //
    $safe_sql = SafeSQL::template($sql_template, $h_filters = ['id' => 'Safe::int'], $data_i);
    ok($safe_sql, '1', 'test 4');
    //
    //
    $safe_sql = SafeSQL::template($sql_template, $h_filters = ['id' => SafeSQL::int], $data = ['id' => 'xx']);
    ok($safe_sql, '0', 'test 1b');
    $safe_sql = SafeSQL::template($sql_template, $h_filters = ['id' => SafeSQL::str], $data = ['id' => '"`ab']);
    ok($safe_sql, '`ab`', 'test 2b');
    //
    //
    $safe_sql = SafeSQL::template($sql_template, $h_filters = ['id' => SafeSQL::int], $data_i = ['id' => null]);
    ok($safe_sql, '0', 'test 3b');
    //
    $safe_sql = SafeSQL::template($sql_template, [], $data_i = ['id' => " '' "]);
    ok( trim($safe_sql), '', 'test inject');
    //
    // $safe_sql = SafeSQL::template($sql_template, $h_filters = ['id' => SafeSQL::int], $data_i = ['id' => new stdClass()]);
    // ok($safe_sql, '', 'test 4b');
    //
}