<?php
require_once dirname(__FILE__).'/../lib/Test.php';
require_once dirname(__FILE__).'/../lib/Date.php';

diag( "Date\n");

$date = time();
ok( Date::isTimeStamp($date), "isTimeStamp $date");
$date = date('d-m-Y');
ok( !Date::isTimeStamp($date), "!isTimeStamp $date");
$date = date('Y-m-d');
ok( !Date::isTimeStamp($date), "!isTimeStamp $date");
ok( !Date::isTimeStamp(null), "!isTimeStamp NULL");

$date = date('Y-m-d');
ok( Date::isISO($date), "isISO $date");
$date = time();
ok( !Date::isISO($date), "!isISO $date");
$date = date('d-m-Y');
ok( !Date::isISO($date), "!isISO $date");
ok( !Date::isISO(null), "!isISO NULL");


$date = date('d-m-Y');
ok( Date::isIT($date), "isIT $date");
$date = date('d/m/Y');
ok( Date::isIT($date), "isIT $date");
$date = time();
ok( !Date::isIT($date), "!isIT $date");
$date = date('Y-m-d');
ok( !Date::isIT($date), "!isIT $date");
ok( !Date::isIT(null), "!isIT NULL");


$date = null;
ok( Date::isEmpty($date), "isEmpty NULL");
$date = '00-00-0000';
ok( Date::isEmpty($date), "isEmpty $date");
$date = '00/00/0000';
ok( Date::isEmpty($date), "isEmpty $date");
$date = '0000-00-00';
ok( Date::isEmpty($date), "isEmpty $date");
$date = '0000/00/00';
ok( Date::isEmpty($date), "isEmpty $date");
$date = date('Y-m-d');
ok( !Date::isEmpty($date), "!isEmpty $date");



$date = date('d-m-Y');
$expected = mktime( 0, 0, 0, date('m'), date('d'), date('Y') );
ok( Date::toTimeStamp($date) == $expected, "toTimeStamp $date is $expected");
$date = date('d/m/Y');
$expected = mktime( 0, 0, 0, date('m'), date('d'), date('Y') );
ok( Date::toTimeStamp($date) == $expected, "toTimeStamp $date is $expected");
$date = time();
$expected = time();
ok( Date::toTimeStamp($date) == $expected, "toTimeStamp $date is $expected");
$date = date('Y-m-d');
$expected = mktime( 0, 0, 0, date('m'), date('d'), date('Y') );
ok( Date::toTimeStamp($date) == $expected, "toTimeStamp($date) ".Date::toTimeStamp($date)." is $expected");
$expected = 0;
ok( Date::toTimeStamp(null) == $expected, "toTimeStamp NULL is $expected");


$date = date('d-m-Y');
$expected = date('Y-m-d');
ok( Date::toISO($date) == $expected, "toISO($date) is $expected");

$date = date('d/m/Y');
$expected = date('Y-m-d');
ok( Date::toISO($date) == $expected, "toISO($date) is $expected");


$expected = date('d/m/Y');
$date =  date('Y-m-d');
$r = Date::toIT($date);
ok( $r == $expected, "toIT($date)=$r is $expected");
/*
$yesterday= date('Y-m-d', mktime( 0, 0, 0, date('m'), date('d')-1, date('Y') ) );
ok( Date::isPast($yesterday) ,"$yesterday isPast");

$tomorrow= date('Y-m-d', mktime( 0, 0, 0, date('m'), date('d')+1, date('Y') ) );
ok( Date::isFuture($tomorrow) ,"$tomorrow isFuture");

ok( Date::isBetween($yesterday, $tomorrow) ,"now we are between $yesterday and $tomorrow");
ok( Date::isBetween($yesterday, $tomorrow, $yesterday) ,"$yesterday  between $yesterday and $tomorrow");

*/







