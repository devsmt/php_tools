<?php
require_once dirname(__FILE__).'/../lib/Array.php';
require_once dirname(__FILE__).'/../lib/Cache.php';
require_once dirname(__FILE__).'/../lib/Test.php';
/*



session_start();

$_GET = array( 'a'=>0, 'b'=>2);
$currentKey = PageCache::genKey();
is( $currentKey, $_SERVER['PHP_SELF'].'-a=0&b=2', 'gen key');






$c = new PageCache();


// se esiste una pagina valida viene mostrata altrimenti viene generato del autput da salvare

if( is_main( __FILE__ ) ){
    ok( $c->render() , 'response from cahe!');
} else {
    $c->clear();
    // generiamo autput che possimao controllare
    ob_start();
    $expected = date('H:m:s') ;
    echo $expected;

    // salvo nella cache
    $c->savePage();
    ob_end_clean();

    $result = $c->get($currentKey);

    is( $result, $expected, "confronto pagina corrente e cache");
}
problemi:

come configurare una pagina per non essere gestita in cache?
come gestire la cache per sviluppo vs produzione
*/