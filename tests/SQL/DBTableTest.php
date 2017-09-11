<?php
require_once __DIR__.'/../lib/sql/mysql.php';
require_once __DIR__.'/../lib/Test.php';
require_once __DIR__.'/../lib/sql/DBTable.php';
require_once __DIR__.'/../lib/helpers/HTML.php';



$t = new DBTable('user' /*, Weasel::getDB()*/ );

ok($t->delete(), 'delete' );
is($t->count() , 0, 'count after delete');

ok( $t->insert(['name'=>'test']), 'insert');
ok($t->update(['name'=>'test2'], 'name="test"'), 'update');

ok($t->confirm( ['name'=>'test3'], 'name="test"' ),'confirm');
ok($t->replace( ['name'=>'test'], 'name="test"' ), 'replace');
is( mysql_num_rows($t->select( ['where'=>'name="test"'] )), 1, 'do select');

echo Dump::rs( $t->select() ) ;