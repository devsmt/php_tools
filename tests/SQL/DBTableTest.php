<?php
require_once dirname(__FILE__).'/../lib/sql/mysql.php';
require_once dirname(__FILE__).'/../lib/Test.php';
require_once dirname(__FILE__).'/../lib/sql/DBTable.php';
require_once dirname(__FILE__).'/../lib/helpers/HTML.php';



$t = new DBTable('user' /*, Weasel::getDB()*/ );

ok($t->delete(), 'delete' );
is($t->count() , 0, 'count after delete');

ok( $t->insert(array('name'=>'test')), 'insert');
ok($t->update(array('name'=>'test2'), 'name="test"'), 'update');

ok($t->confirm( array('name'=>'test3'), 'name="test"' ),'confirm');
ok($t->replace( array('name'=>'test'), 'name="test"' ), 'replace');
is( mysql_num_rows($t->select( array('where'=>'name="test"') )), 1, 'do select');

echo Dump::rs( $t->select() ) ;