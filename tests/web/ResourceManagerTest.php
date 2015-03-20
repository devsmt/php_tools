<?php
require_once dirname(__FILE__).'/../lib/ResourceManager.php';
//require_once dirname(__FILE__).'/../lib/helpers/HTML.php';
//require_once dirname(__FILE__).'/../lib/Weasel.php';
require_once dirname(__FILE__).'/../lib/Test.php';

// aggiunge all'array?
ResourceManager::addCSS('test','/css/test.css');
ok(count(ResourceManager::$_css)==1, 'aggiunta un css');

// genera html?
$html = ResourceManager::getHTML();
ok($html == '<link rel="stylesheet" type="text/css" href="/css/test.css" />', 'possiamo includere il css');


ResourceManager::clear();
ok(count(ResourceManager::$_css)==0, 'possiamo azzerare il dizionario');

ResourceManager::addJS('test','/js/test.css');
ok(count(ResourceManager::$_js)==1, 'aggiunta una lib js');

$html = ResourceManager::getHTML();
ok($html == '<script type="text/javascript" src="/js/test.css"></script>', 'possiamo includere il css');


ResourceManager::addCSS('test','/css/test.css');
ResourceManager::addJS('test','/js/test.css');
$html = ResourceManager::getHTML();
echo $html;
ok($html != '', 'abbiamo le necessarie dipendenze');