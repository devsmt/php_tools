<?php
require_once __DIR__.'/../lib/sql/mysql.php';
require_once __DIR__.'/../lib/Test.php';
require_once __DIR__.'/../lib/sql/DAL.php';
include_once 'UserTest.php';

$DB = Weasel::getDB();

$DataDictionary = array();
//-- TEST ----------------------------------------------------------------------

function set_up(){
    $sql = "truncate table user";
    $db = Weasel::getDB();
    $rs = $db->qry( $sql, __LINE__, __FILE__ );
}
function tear_down(){
    $sql = "truncate table user";
    $db = Weasel::getDB();
    $rs = $db->qry( $sql, __LINE__, __FILE__ );
}

//-- main ----------------------------------------------------------------------
set_up();

$u = new User();
$u->set('name','a');
$u->save(); // insert 1 record
$u->set('name','test');
$u->save(); // update record

unset ($u);

$rs = DAL::select('Select * from user limit 0,1', $count);

is( $count, 1, 'we have 1 record');
while( $u = DAL::fetch($rs, 'User') ) {
    //var_dump($u);
    is( $u->getTableName(), 'user','table name calculated');

    is( $u->getPKField(), 'id','pk is id');
    is( $u->get('id') , '1','id is 1');
    is( $u->getPK() ,'1','id is 1');
    $u->set('name','test');
    ok( $u->get('name')=='test', 'set/get name' );
}

tear_down();

//-- validation
echo "\n\n--validation\n";
$u = new User();
$u->set('name','a');
$u->requirePresenceOf( array('name') );
ok($u->isValid(), 'requirePresenceOf "a"' );

$u = new User();
$u->requirePresenceOf( array('name') );
ok( !$u->isValid(), '! requirePresenceOf null' );

$u = new User();
$u->set('id',10);
$u->requireNumericallityOf( array('id') );
ok($u->isValid(), 'requireNumericallityOf 10' );

$u = new User();
$u->set('id','10');
$u->requireNumericallityOf( array('id') );
ok( !$u->isValid(), '! requireNumericallityOf "10"' );


$u = new User();
$u->set('name','a');
$u->requireIsStringOf('name', 0, 255);
ok($u->isValid(), 'requireIsStringOf "a"' );

$u = new User();
$u->set('name', 10);
$u->requireIsStringOf('name', 0, 255);
ok( !$u->isValid(), '! requireIsStringOf 10' );

$u = new User();
$u->set('name', 'aaaaaaaaaaaa');
$u->requireIsStringOf('name', 0, 2);
ok( !$u->isValid(), '!requireIsStringOf' );


//-- lambdas
function true_validation_function(){ return true; }
function false_validation_function(){ return false; }

$u = new User();
$u->set('name','a');
$u->requireValidationOf( 'name' , 'true_validation_function');
ok($u->isValid(), 'true_validation_function' );

$u = new User();
$u->set('name','a');
$u->requireValidationOf('name', 'false_validation_function');
ok( !$u->isValid(), 'false_validation_function' );


$u = new User();
$u->set('name','a');
$u->requireUniquenessOf('name');
ok($u->isValid(), 'requireUniquenessOf' );


$u = new User();
$u->set('name','aaaa');
$u->requireFormatOf('name', '/[a-z]{4}/', 'name è nel formato scorretto');
ok($u->isValid(), 'requireFormatOf' );


$u = new User();
$u->set('name',111);
$u->requireFormatOf('name', '/[a-z]{4}/', 'name è nel formato scorretto');
ok( !$u->isValid(), '!requireFormatOf' );

unset ($u);









