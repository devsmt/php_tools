<?php
/*
function Response_fromCache(){
    // output the cache
    readfile($cache_filename); // The cached copy is still valid, read it into the output buffer
    die(); // die() automatically call ob_end_flush() so the buffered output is automatically printed
}

function Response_Cache(){
    $contents = ob_get_contents();
    // metti in cache la risposta corrente
    file_put_contents($cache_filename, $contents );

    // invia la risposta
    ob_end_flush();
}

function Request_isCashed(){

    ob_start(); // Turns on output buffering


    $cache_time = 3*60*60; // Time in seconds to keep a page cached
    $cache_folder = '/cache'; // Folder to store cached files (no trailing slash)
    $cache_filename = $cache_folder.md5($_SERVER['REQUEST_URI']); // Location to lookup or store cached file
    //Check to see if this file has already been cached
    // If it has get and store the file creation time
    $cache_created  = file_exists($cache_filename) ? filemtime($this->filename) : 0;

    // determina se la cache e' valida
    return ((time() - $cache_created) < $cache_time) ;
}


//------------------------------------------------------------------------------
// TEST
//------------------------------------------------------------------------------

if( Request_isCashed() ){
    Response_fromCache();
}

// do the output
Response_Cache();

*/
