<?php

// dal concetto espresso qui:
// http://swaxblog.tumblr.com/post/112611863175/who-cares-about-get-vs-post-norest
// - Calling functions on remote web servers should be a simple RPC
// - Function names not mixed in with parameters
// - Functions not prefixed with verbs
// - Parameters all in a single place (POST)
//
//
// espone in GET i metodi specificati, via HTTP secondo la convenzione
//   /namespace/class/publicStaticMethod
// accetta parametri solo in POST, solo quelli della funzione esposta
// rispnde in JSON
// occorre sodddisfare le restrizini della ACL
class AutoAPI {
    public function expose(array $api_whitelist_map=[]){}
    public function serve(array $server, array $post){
        // se la funzione richesta è tra la whitelist
        // se l'utente dispone del permesso per accedere alla risorsa
        // chiama il metodo specificato da whitelist via reflection, con i parametri passati
        // ritorna risposta JSON secondo standard implementato dalla classe


        // Some_Class::someMethod($a, $x, $y, $x, $y)
        $method = new ReflectionMethod('Some_Class', 'someMethod');
        $method->isPublic();
        $method->isStatic();
        $method->getNumberOfRequiredParameters();
        $a_parameters = $method->getParameters();// return array of ReflectionParameter

        $a_call_param[ $a_parameters[0]->getPosition() ] = $_POST[ $a_parameters[0]->geName() ];


        // call
        $res = call_user_func_array(array('Api\OrderList', 'getOrder'), $a_call_param );
        die $this->respond('ok', '', $res);
    }
    protected function respond($status, $msg, $data=[]){}
}
// test code
$api = new AutoAPI();
$api->expose([
    'Api/OrderList/getOrder'
]);
$api->serve($_SERVER,$_POST);


