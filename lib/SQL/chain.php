<?php
declare (strict_types = 1);
require_once __DIR__.'/SQL.php';

// get by key:
// data una tabella, matcha per la key e processa con il mapper
// è sempre posssibile dare opzioni più sofisticate via $sql_opt
// ritorna un RS
// TODO: if $sql_where is a single value non containing '=' char, is intended to be the primary key value for the table
function chain(string $table, string $sql_where, \Closure $f_mapper = null, array $sql_opt = []): array{
    $sql_opt = array_merge(['where' => $sql_where], $sql_opt );
    $sql = SQL::select($table, $sql_opt );
    $a_rs = DB::qry($select);
    if (empty($a_rs)) {
        return $def_rec;
    }
    if( $f_mapper ) {
        $a_rs_mapped = array_map($f_mapper, $a_rs);
    } else
        $a_rs_mapped = $a_rs;
    return $a_rs_mapped;
}

// recupera solo il primo record
function chainf(string $table, string $sql_where, \Closure $f_mapper = null, array $sql_opt = []): array{
    $rs = chain( $table, $sql_where, $f_mapper, $sql_opt );
    return $rs[0];
}



// if colled directly in CLI, run the tests:
if( isset($argv[0]) && basename($argv[0]) == basename(__FILE__) ) {
    //
    // data una tabella, matcha per la key e processa con il mapper
    //
    $a_rec_mapped_1 = chain('table', sprintf('key=%s', $value=10), function (array $a_rec): array{
        return $a_rec;
    });

    $a_rec_mapped_3 = chainf('table', 'key=10');
}



