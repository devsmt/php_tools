<?php
declare (strict_types = 1);
require_once __DIR__ . '/SQL.php';



// memo func param
// function cache_mem($customer_id) {
//     static $__cached_data = [];
//     if (!array_key_exists($customer_id, $__cached_data)) {
//         $__cached_data[$customer_id] = '';
//     }
//     return $__cached_data[$customer_id];
// }

// get by key:
// data una tabella, matcha per la key e processa con il mapper
// è sempre posssibile dare opzioni più sofisticate via $sql_opt
// ritorna un RS
// TODO: if $sql_where is a single value non containing '=' char, is intended to be the primary key value for the table
function chain(string $table, /*string|array*/ $sql_where, \Closure $f_mapper = null, array $sql_opt = []): array{
    //
    $sql_opt = array_merge([
        'cache' => true,
    ], $opt );
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
    } else {
        $msg = sprintf('Errore %s ', 'invalid type for where');
        throw new \Exception($msg);
    }
    $sql_opt = array_merge(['where' => $sql_where], $sql_opt);
    $sql = SQL::select($table, $sql_opt);
    // debug SQL
    $a_rs = $sql_opt['cache'] ? DB::qryc($select) : DB::qry($select);
    //
    if (empty($a_rs)) {
        return $def_rec;
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
    return $rs[0];
}
// if colled directly in CLI, run the tests:
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    //
    // data una tabella, matcha per la key e processa con il mapper
    //
    $a_rec_mapped_1 = chain('table', sprintf('key=%s', $value = 10), function (array $a_rec): array{
        return $a_rec;
    });
    $a_rec_mapped_3 = chainf('table', 'key=10');
}
