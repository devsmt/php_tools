<?php

// TODO:
//   utility per inserire record arbitrari (fixtures)
//   utility per eliminare le fixtures inserite
//   assert() un record sia inserito e presente
//   assert() un campo sia modificato rispetto al parametro o a record fixture
//   assert() un campo sia stato eliminato
//   assert() una select abbia restituito un numero corretto di record
class DBUnit {

}
//
if( isset($argv[0]) && basename($argv[0]) == basename(__FILE__) ) {
    require_once __DIR__ . '/../Test.php';
}