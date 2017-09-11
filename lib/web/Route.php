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
  echo '<pre>',var_dump( $matches ),'</pre>';
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




class RegexRouter {
    private static $routes = [];
    public function route($pattern, $callback) {
        self::$routes[$pattern] = $callback;
    }
    public function execute($uri) {
        foreach (self::$routes as $pattern => $callback) {
            if (preg_match($pattern, $uri, $params) === 1) {
                array_shift($params);
                return call_user_func_array($callback, array_values($params));
            }
        }
    }
}
/*
RegexRouter::route('/^\/blog\/(\w+)\/(\d+)\/?$/', function($category, $id) {
    echo "category={$category}, id={$id}";
});
*/


