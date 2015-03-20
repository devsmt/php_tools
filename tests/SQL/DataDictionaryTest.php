<?php

require_once dirname(__FILE__).'/../lib/DataDictionary.php';
require_once dirname(__FILE__).'/../lib/Test.php';



//include '../lib/sql/sql.php';
ok( DataDictionary::getPK('content_nodes') == 'id' , 'pk of "content_nodes" should be "id"');
ok( DataDictionary::isNull('content_nodes','id') === false , 'content_nodes.id is not null');
ok( DataDictionary::getDefault('content_nodes','id') === null, 'content_nodes.id default is null' );

ok( DataDictionary::getType('content_nodes','id', $l) == 'int', 'content_nodes.id  is int '.$l );
ok( DataDictionary::getType('content_nodes','parent_id', $l) == 'string', 'content_nodes.parent_id  is string '.$l );

ok( DataDictionary::getMaxLen('content_nodes','id') == 11, 'content_nodes.id  is int 11 long' );
ok( DataDictionary::getMaxLen('content_nodes','parent_id') == 50, 'content_nodes.parent_id  is string 50 long' );

echo 'DUMP:',
DataDictionary::dump();
