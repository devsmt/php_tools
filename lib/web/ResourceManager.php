<?php

/*
  gestisce le risorse(css, js) necessarie a rendere correttamente i controlli
  @see https://github.com/kriswallsmith/assetic
 */

class ResourceManager {

    static $_css = [];
    static $_js = [];

    /*
      un widget registra le risorse che gli sono necessarie
      todo: una risorsa pu� specificare nomi delle dipendenze
     */

    function addCSS($name, $url, $depends = []) {
        self::$_css[$name] = $url;
    }

    function addJS($name, $url, $depends = []) {
        self::$_js[$name] = $url;
    }

    /*
      da richiamare nei template dove ritorna i link alle risorse necessarie
     */

    function getHTML() {
        $html = '';
        foreach (self::$_css as $name => $url) {
            $html.= '<link rel="stylesheet" type="text/css" href="' . $url . '" />';
        }
        foreach (self::$_js as $name => $url) {
            $html.= '<script type="text/javascript" src="' . $url . '"></script>';
        }
        return $html;
    }

    function clear() {
        self::$_css = [];
        self::$_js = [];
    }

}
