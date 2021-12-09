<?php
// type RS = Array< Hash >
//
// groups an RS by key
//
function RS_group_by(array $rs, string $key, callable $_rec_mapper = null, callable $_rs_reducer = null, int $initial_v = 0): array{
    if (null == $_rec_mapper) {
        $_rec_mapper = function ($rec) {return $rec;};
    }
    $result = [];
    foreach ($rs as $rec) {
        if (array_key_exists($key, $rec)) {
            $val = $rec[$key];
            $result[$val][] = $_rec_mapper($rec);
        } else {
            // $result[""][] = $rec;// incorrect shape of the array
            $msg = ("incorrect shape of the array, $key missing in " . json_encode(array_keys($rec)));
            throw new \Exception($msg); // exceptions_
        }
    }
    // perform reduce on results
    if (null !== $_rs_reducer) {
        $result2 = [];
        foreach ($result as $key => $sub_rs) {
            // perform reducer:
            // function($carry_v, $cur_v) {
            //     $carry_v += $cur_v;
            //     return $carry_v;
            // }
            $final_v = array_reduce($sub_rs, $_rs_reducer, $initial_v);
            $result2[$key] = $final_v;
        }
        return $result2;
    }
    return $result;
}
/*
$data = [
['gender'=> 'M'],
['gender'=> 'M'],
['gender'=> 'F'],
];
$r = array_group_by($data, $key = 'gender');
$r2 = array_group_by($data, $key = 'gender', function ($r) {return strlen($r['name']);}, function ($carry_v, $cur_v) {
$carry_v += $cur_v;
return $carry_v;
});
// count records by having a key
$r3 = array_group_by($data, $key = 'gender', function ($r) {return 1;}, function ($carry_v, $cur_v) {
$carry_v += $cur_v;
return $carry_v;
});
 */
// TODO: equivalent to SQL where clausule
function RS_where(array $rs): array{
    // array_values() to discard the non consecutive index
    $_f = array_values(array_filter($rs, function ($rec) {
        // false will be skipped
        return empty($rec) ? false : true; // filter out if empty
    }));
    return $_f;
}
/*
uso:
$a_cart_qta = reindex($a_cart_rows, 'article_id', function($rec){
return [
'article_id' => $rec['article_id'],
'qta'        => $rec['qta']
];
});
 */
/**
 * @param string|callable(array): string $idx_field
 */
function RS_reindex(array $a_recs, $idx_field, callable $rec_mapper = null): array{
    $a_recs_idx = [];
    $rec_mapper = ($rec_mapper === null) ? (function ($r) {
        return $r;
    }) : $rec_mapper;
    $idx_field_c = $idx_field;
    foreach ($a_recs as $i => $rec) {
        $id = '';
        if (is_array($rec)) {
            if (is_string($idx_field)) {
                $id = (string) h_get($rec, $idx_field, '');
            } else {
                if (is_callable($idx_field)) {
                    $id = (string) call_user_func($idx_field_c, $rec);
                }
            }
            if (!empty($rec_mapper)) {
                $rec = call_user_func($rec_mapper, $rec);
            }
            $a_recs_idx["$id"] = $rec;
        } else {
            $msg = sprintf('Errore: array expected, found %s ', json_encode($rec));
            throw new \Exception($msg);
        }
    }
    return $a_recs_idx;
}
//-------------------------------------------------------------------
// dato un array di dizionari Hash<any>[]  ritorna solo le chiavi indicate, mantenendo le chiavi nel dizionario
// dato un RS ( array di dizionari Hash<any>[] )
// ritorna solo le chiavi indicate, mantenendo le chiavi nel dizionario
// return Array< Hash<String $key , mixed> > con una singola chiave o multipla per hash
// per avvere Array<String> usa __::pluck()
/** @param string|array $key */
/*
public static function pluck(array $a_RS, $key, array $opt = []): array{
$option = array_merge([
'silent' => true,
], $opt);
extract($option);
if (is_string($key)) {
// da un RS ritorna Array<string>
return array_reduce($data, function ($result, $h) use ($key) {
if (isset($h[$key])) {
$result[] = $h[$key];
}
return $result;
}, []);
} elseif (is_array($key)) { // grep a group of keys
return self::select_keys($a_RS, $a_keys, $silent);
} else {
// key is not
return [];
}
}
// di un rs, ritorna rs, le sole chiavi specificate
public static function select_keys(array $a_RS, array $a_keys, bool $silent = true): array{
$return = [];
foreach ($a_RS as $rec) {
$a_tmp = [];
foreach ($a_keys as $cur_key) {
if (isset($rec[$cur_key])) {
$a_tmp[$cur_key] = $rec[$cur_key];
} elseif (!$silent) {
$msg = sprintf('Errore missing key %s ', $cur_key);
throw new \Exception($msg);
}
}
$return[] = $a_tmp;
}
return $return;
}
 */

/*
uso:
$a_cart_qta = reindex($a_cart_rows, 'article_id', function($rec){
return [
'article_id' => $rec['article_id'],
'qta'        => $rec['qta']
];
});
 */
function array_reindex($a_recs, $idx_field, $rec_mapper = null) {
    $a_recs_idx = array();
    $rec_mapper = ($rec_mapper === null) ? (function ($r) {
        return $r;
    }) : $rec_mapper;
    $idx_field_c = $idx_field;
    foreach ($a_recs as $i => $rec) {
        $id = "";
        if (is_array($rec)) {
            if (is_string($idx_field)) {
                $id = (string) h_get($rec, $idx_field, '');
            } else {
                if (is_callable($idx_field)) {
                    $id = (string) call_user_func($idx_field_c, $rec);
                }
            }
            if (!empty($rec_mapper)) {
                $rec = call_user_func($rec_mapper, $rec);
            }
            $a_recs_idx["$id"] = $rec;
        } else {
            $msg = sprintf('Errore: array expected, found %s ', json_encode($rec));
            throw new \Exception($msg);
        }
    }
    return $a_recs_idx;
}

//----------------------------------------------------------------------------
//  RS utils
//----------------------------------------------------------------------------
// somma un RS
function RS_sum(array $rs, string $field_name ): string{
    $a_val = array_map(function ($row) use ($field_name) {
        $r2 = h_select_keys($row, [$field_name]);
        $v = $r2[$field_name];
        return Dec::val($v);
    }, $rs);
    return Dec::array_sum(
        $a_val
    );
}
/** sum an RS
function rs_sum2(array $a_rs, $key = 'amount'): string{
    $a_amounts = array_pluck($key, $a_rs);
    $a_amounts = array_map(function ($val) {
        return $val = str2bc($val);
    }, $a_amounts);
    $total_s = array_reduce($a_amounts, function (string $carry_v, string $cur_v): string{
        $carry_v = bcadd($carry_v, $cur_v);
        return $carry_v;
    }, $initial_v = '0.0');
    return $total_s;
}
*/

// if colled directly in CLI, run the tests:
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/../Test.php';
    $rs = [];
    $rs2 = RS::where($rs);
}
