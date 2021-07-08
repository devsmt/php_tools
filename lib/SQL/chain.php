<?php
declare (strict_types = 1);
require_once __DIR__ . '/SQL.php';
// acceso ai dati
/*
// ritorna records matching
$rec = chain('tabella', ['id'=>1])
$rec = chain('tabella', ['id'=>1, 'id2'=>2]);
$rec = chain('tabella', 'id = 1 or id2 > 2' );//complex clausule
$rec = chain('tabella', function(){ return 'id = 1 or id2 > 2'; } );// even more complex clausule
// legge una lista
$a = select('tabella', '*', ['id'=>1]  );
$a = select('tabella', '*', ['id'=>1], ['class' => 'rec_class'] );
$a = select('tabella', '*', function() { return 'id > 1'; } );
$a = select('tabella', '*', [], 'order by ID ASC'  );
$last_inserted_id = insert('table', []);
$num_updated_recs = update('table', $key, []);
$num_deleted_recs = delete('table', $key, []);
 */
// get by key:
// data una tabella, matcha per la key e processa con il mapper
// è sempre posssibile dare opzioni più sofisticate via $sql_opt
// ritorna un RS
// TODO: if $sql_where is a single value non containing '=' char, is intended to be the primary key value for the table
function chain(string $table, /*string|array*/ $sql_where, array $p_opt = []): array{
    //
    $sql_opt = array_merge([
        // sql generation
        'fields' => '*',
        // 'where' => null,
        'group_by' => null,
        'order_by' => null,
        // fetching
        'cache' => true,
        'f_mapper' => null, // // \Closure
    ], $p_opt);
    extract($sql_opt);
    //
    // handle where
    if (is_string($sql_where)) {
        // where expected
    } elseif (is_array($sql_where)) {
        // serialize array as key
        // 1) [ 'k_field' => 1122  ]
        // 2) [ 'k_field' => 1122, 'k2' => 34  ]
        // 3) [ 'k_field' => [1122, 34]  ]
        // only EQ accepted
        $h_key = $sql_where;
        $sql_where = '';
        foreach ($h_key as $key => $val) {
            if ($sql_where != '') {
                $sql_where .= ' && ';
            }
            $sql_where .= sprintf('%s=%s', $key, $val);
        }
    } elseif( is_callable( $where ) ) {
        $where = $where ();
    } else {
        $msg = sprintf('Errore %s type=%s', 'invalid type for $where', gettype($sql_where) );
        throw new \Exception($msg);
    }
    $sql_opt = array_merge( $sql_opt, ['where' => $sql_where] );
    $sql = SQL::select($table, $sql_opt);
    // debug SQL
    if (class_exists('DB', $autoload = false)) {
        $a_rs = $sql_opt['cache'] ? DB::qryc($select) : DB::qry($select);
    } else {
        $a_rs = [];
        echo sprintf("<pre>%s() L:%s F:%s\n", __FUNCTION__, __LINE__, __FILE__), var_dump(
            $sql
        ), "</pre>\n";
        return [];
    }
    //
    if (empty($a_rs)) {
        return $def_rec = [];
    }
    if ($f_mapper) {
        $a_rs_mapped = array_map($f_mapper, $a_rs);
    } else {
        $a_rs_mapped = $a_rs;
    }
    return $a_rs_mapped;
}
// alias
function byKey(string $table, string $sql_where, \Closure $f_mapper = null, array $sql_opt = []): array{
    return chain($table, $sql_where, $f_mapper, $sql_opt);
}
// recupera solo il primo record
function chain_f(string $table, string $sql_where, \Closure $f_mapper = null, array $sql_opt = []): array{
    $rs = chain($table, $sql_where, $f_mapper, $sql_opt);
    if (!empty($rs)) {
        return $rs[0];
    } else {
        return [];
    }
}
// if colled directly in CLI, run the tests:
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/../Test.php';
    //
    // data una tabella, matcha per la key e processa con il mapper
    //
    $a_rec_mapped_1 = chain('table', sprintf('key=%s', $value = 10) );
    //
    $rec = chain('tabella', ['id' => 1]);
    $rec = chain('tabella', ['id' => 1, 'id2' => 2]);
    $rec = chain('tabella', 'id = 1 orid2 > 2'); //complex clausule
    $rec = chain('tabella', function () {return 'c_id = 1 or c_id2 > 2';}); // even more complex clausule
    //
    $a_rec_mapped_3 = chain_f('table', 'key=10');
}