<?php



function iss($is, $expected, $desc = ''){
    $is = trim(str_replace("\n",'',$is));
    $expected = trim(str_replace("\n",'',$expected));
    return is(strtolower($is), strtolower($expected), $desc);
}

iss( SQL::quote('a'), '`a`', 'quote_field');
iss( SQL::quote('`a`'), '`a`', 'quote_field');

iss( SQL::quotev('a'), "'a'", 'quote_value');
iss( SQL::quotev(1), '1', 'quote_value');
iss( SQL::quotev(0), '0', 'quote_value');
iss( SQL::quotev(1.5), "'1.5'", 'quote_value');// cosa deve fare esattamente con i float?
iss( SQL::quotev('null'), 'NULL', 'quote_value');// cosa deve fare con null? con str "Null" ok

iss( SQL::quotev('now()'), 'now()', 'quote_value');

iss( SQL::_ensure_like_char('a'), '%a%', '_ensure_like_char' );
iss( SQL::_ensure_like_char('%a'), '%a', '_ensure_like_char' );
iss( SQL::_ensure_like_char('%a%'), '%a%', '_ensure_like_char' );
iss( SQL::_ensure_like_char('%a%z'), '%a%z', '_ensure_like_char' );


iss( SQL::sequence_val(array('a'=>1) ), '`a`=1', 'sequence_val');
iss( SQL::sequence_val(array('a'=>1, 'b'=>2)) , '`a`=1,`b`=2', 'sequence_val');
iss( SQL::sequence_val(array('a'=>"a") ), "`a`='a'", 'sequence_val');
iss( SQL::sequence_val(array('a'=>1.5)) , "`a`='1.5'", 'sequence_val');
iss( SQL::sequence_val(array()) , '', 'sequence_val');
iss( SQL::sequence_val(null) , '', 'sequence_val');

iss( SQL::where_range('test', array(0,1)) ,'( `test`=0||`test`=1 ) ', 'where_range');

iss( SQL::where_range_like('test', array('a','b')) ,' ( `test` like "%a%" || `test` like "%b%" ) ', 'where_range_like');
iss( SQL::where_range_like( array( 'testa' => 'a', 'testb' => 'b')) ,' ( `testa` like "%a%" || `testb` like "%b%" ) ', 'where_range_like');

iss( SQL::where_in('test', array(0,1)) , '`test` in ( 0,1 )', 'where_in' );


iss( SQL::where_range_date('date','2009-01-01', '2009-12-12') , "(UNIX_TIMESTAMP(date) > UNIX_TIMESTAMP('2009-01-01')) AND (UNIX_TIMESTAMP(date) < UNIX_TIMESTAMP('2009-12-12'))", 'where_range_date');
iss( SQL::orderby( array(array('ordine','ASC') ) ), 'ORDER BY `ordine` ASC', 'orderby' );
iss( SQL::orderby( array('ordine','ASC') ), 'ORDER BY `ordine` ASC', 'orderby' );
iss( SQL::orderby( 'ordine' ), 'ORDER BY `ordine` ASC', 'orderby' );


iss( SQL::insert('test', array('a'=>0) ), "INSERT INTO `test` SET `a`=0", 'insert' );
iss( SQL::update('test', 'id=1', array('a'=>0)), "UPDATE `test` SET `a`=0 WHERE id=1", 'update' );


iss( SQL::select('test',array(
    's'=>'*',
    'where'=>null,
    'group_by'=>null,
    'order_by'=>null,
    'pos'=>0,
    'limit'=>null
)),
'select * from `test`',
'simple_select' );

