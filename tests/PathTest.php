<?php

require_once __DIR__.'/../lib/Test.php';
require_once __DIR__.'/../lib/Path.php';

diag( "Path::real");
is( Path::real('/var'), '/var', Path::real('/var') );
ok( Path::real('/var/') == '/var/', Path::real('/var/') );
ok( Path::real('/var/../var/www') == '/var/www', Path::real('/var/../var/www') );
ok( Path::real('/var/../var/../../../var/www') == '/var/www', Path::real('/var/../var/../../../var/www') );

diag( "Path::join");
ok( Path::join('var','www') == '/var/www' );
ok( Path::join(array('var','www')) == '/var/www' );
ok( Path::join('var/www','public') == '/var/www/public' );
ok( Path::join(array('var/www','public')) == '/var/www/public' );

diag( "Path::join + real");
ok( Path::join(array('var/www/mydir','..','public')) == '/var/www/public', Path::join(array('var/www/mydir','..','public')) );
ok( Path::join(array('var/www/..','www','public')) == '/var/www/public',Path::join(array('var/www/..','www','public')) );
ok( Path::join(array('var/www/..','www/../www/public')) == '/var/www/public', Path::join(array('var/www/..','www/../www/public')) );

/*
diag( "Path::join for windows");
// win tests
is( Path::join('D:\www\webroot', 'template') , 'D:\www\webroot\template' );
is( Path::join(array('D:\www\webroot', 'template', 'index.php')) , 'D:\www\webroot\template\index.php' );
*/


//diag( "Path::toAbsUrl");
//ok( Path::toAbsUrl(  ) == dirname($_SERVER['PHP_SELF']), Path::toAbsUrl( __FILE__ ));
//ok( Path::toAbsUrl( ) == '/apache2-default/pweasel/tests', Path::toAbsUrl( __DIR__ ));

/*


diag( '<pre style="line-height: 25px;">';


// Loads PathManager class
require_once "class.pathparser.php";


$path = 'http://www.php.net:80/./donwloads/../faq/.././manual/en/install.php?version=502#top';

$Path = new PathParser($path);

diag( "\nOriginal path: " . $path;
diag( "\nResolved path: " . $Path->path;
diag( "\n\nPath parts: ");
print_r($Path->parse());



diag( "\n\n\n    Dirty paths:");

$paths[] = 'C://////////Windows//////System';               //  C:/Windows/System/
$paths[] = 'C:\HTML\javascript\..\examples\colors.html';    //  C:/HTML/examples/colors.html
$paths[] = '/root/./wwwroot/scripts/../././webpage';        //  /root/wwwroot/webpage/
$paths[] = 'wwwroot/webpage/../index.php?querystring';      //  wwwroot/index.php?querystring
$paths[] = 'http://www.php.net/manual/en/../../downloads';  //  http://www.php.net/downloads/
$paths[] = 'http://www.php.net/downloads/.././docs.php';    //  http://www.php.net/docs.php
$paths[] = '../downloads/../docs.php';                      //  ../docs.php
$paths[] = 'localhost//projetos/../_arquivos/../';          //  localhost/
$paths[] = 'C:/downloads/../../../';                        //  C:/
$paths[] = 'downloads/../../../';                           //  ../../

foreach ($paths as $path)
{
    diag( "\n&quot;" . $path . "&quot;  =  &quot;" . $Path->fix($path) . "&quot;";
}



diag( "\n\n\n    Finding relative paths:");


$path_a = 'http://www.php.net/manual/en/install.php';
$path_b = 'http://www.php.net/downloads';

diag( "\nPath A:  " . $path_a;
diag( "\nPath B:  " . $path_b;
diag( "\nA to B:  " . $Path->findRelativePath($path_a, $path_b); //  ../../downloads/
diag( "\nB to A:  " . $Path->findRelativePath($path_b, $path_a); //  ../manual/en/install.php



diag( '</pre>';

*/