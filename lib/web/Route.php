<?php

/*
  .htaccess necessario

  Options +FollowSymLinks +ExecCGI
  Options -Indexes -MultiViews +FollowSymLinks
  RewriteEngine On
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule ^$    index.php  [L]
  RewriteRule (.*)  index.php?r=$1  [QSA,L]
  RewriteBase /blog

  esempio:
  route('/blog/', function($matches){
  die('coming soon');
  });
  route('/blog/tag/(.*)', function($matches){
  echo '<pre>',var_dump( $matches ),'</pre>'; // DEBUG
  die('ok 2');
  });
 */

function route($regex, $cb) {
    // applica escape alla regex
    $regex = str_replace('/', '\/', $regex);
    //
    $is_match = preg_match('/^' . ($regex) . '$/', $_SERVER['REQUEST_URI'], $matches, PREG_OFFSET_CAPTURE);
    //
    if ($is_match) {
        $cb($matches);
    }
}
