<?php


class RS {
//
// groups an RS by key
//
function group_by($rs, $key, $_rec_mapper = null, $_rs_reducer = null, $initial_v = 0) {
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
            die("incorrect shape of the array, $key missing in " . json_encode(array_keys($rec)));
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
// equivalent to SQL where clausule
function where($rs) {
    // array_values() to discard the non consecutive index
    $_f = array_values(array_filter($rs, function ($v) {
        // false will be skipped
        return empty($v) ? false : true; // filter out if empty
    }));
    return $_f;
}

}

array_where($rs);