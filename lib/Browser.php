<?php

class Browser {
    // da iniettare per i test
    static $USER_AGENT = '';
    // usa lo useragent inviato al server o quello indicato per i test
    public static function getAgent() {
        if( !empty(self::$USER_AGENT) ) {
            return self::$USER_AGENT;
        } else {
            return $_SERVER['HTTP_USER_AGENT'];
        }
    }

    public static function is($nav) {
        return (strpos(self::getAgent(), $nav) !== false);
    }
    // lista user agent inviati da IE
    // http://blogs.msdn.com/b/ie/archive/2011/04/15/the-ie10-user-agent-string.aspx
    // http://msdn.microsoft.com/it-it/library/ie/hh869301(v=vs.85).aspx
    public static function isIE() {
        return self::is('MSIE') || self::is('Trident');
    }

    public static function getIEVersion() {
        if( self::isIE() ) {
            if( self::is('MSIE') ) {
                preg_match('/MSIE (.*?);/', self::getAgent(), $matches);
                if (count($matches) > 1) {
                    // è una versione di IE
                    $version = $matches[1];
                    return $version;
                }
                // return (float) substr(self::getAgent(), strpos($sAgent, 'MSIE') + 5, 3);
            } elseif( self::is('Trident') ) {
                // è IE 10 o 11
                // IE11 Mozilla/5.0 (Windows NT 6.1; Trident/7.0; rv:11.0) like Gecko
                // IE10 Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)
                if( self::is('Trident/7.0') ) {
                    return 11;
                } elseif( self::is('Trident/6.0') ) {
                    return 10;
                }
            }
        }
        return false;
    }

    public static function isIEge($v) {
        $cv = self::getIEVersion();
        return self::isIE() && ($cv >= $v);
    }

    public static function isIE6() {
        return self::isIEge(6);
    }

    public static function isIE7() {
        return self::isIEge(7);
    }

    public static function isIE8() {
        return self::isIEge(8);
    }

    public static function isFF() {
        return self::is('Firefox');
    }

    public static function isGecko() {
        return self::is('Gecko');
    }

    // public static function getFFVersion() {
    //     $s = self::getAgent();
    //     return (int) substr($s, strpos($s, 'Gecko/') + 6, 8);
    // }
    // public static function isGeckoGe($v = 20030210) {
    //     $cv = self::getFFVersion();
    //     return self::isGecko() && ($cv >= $v);
    // }

    public static function isWebkit() {
        return self::is('Webkit');
    }

    public static function isOpera() {
        return self::is('Opera');
    }

    public static function isSafari() {
        return self::is('Safari');
    }

    public static function isRobot() {
        return (preg_match(',google|yahoo|msnbot|crawl|lycos|voila|slurp|jeeves|teoma,i', self::getAgent()));
    }

    // cerca di tradurre lo UserAgent in qualcosa di leggibile
    public static function translate() {
        $browsers = array(
            // major browser
            'msie', 'firefox', 'chrome', 'safari', 'mozilla', 'opera',
            // major engines
            'gecko','webkit', 'trident',
            // minor browsers
            'seamonkey', 'konqueror', 'netscape',
            'navigator', 'mosaic', 'lynx', 'amaya',
            'omniweb', 'avant', 'camino', 'flock', 'aol'
            );

        $user_agent = strtolower(self::getAgent());
        foreach($browsers as $_browser) {
            if (preg_match("/($_browser)[\/ ]?([0-9.]*)/", $user_agent, $match)) {
                $browser['name'] = $match[1];
                $browser['version'] = $match[2];
                return sprintf('%s %s', $browser['name'], $browser['version']);
            }
        }
        return '';
    }

    public static function detect(){
        $userAgent = strtolower(self::getAgent());

        // Identify the browser engine. Check Opera and Safari first in case of spoof. Let Google Chrome be identified as Safari.
        if (preg_match('/opera/', $userAgent)) {
            $name = 'opera';
        } elseif (preg_match('/webkit/', $userAgent)) {
            $name = 'webkit';
        } elseif (preg_match('/msie/', $userAgent)) {
            $name = 'msie';
        } elseif (preg_match('/mozilla/', $userAgent) && !preg_match('/compatible/', $userAgent)) {
            $name = 'gecko';
        } else {
            $name = 'unrecognized';
        }

        //  version
        if (preg_match('/.+(?:rv|it|ra|ie)[\/: ]([\d.]+)/', $userAgent, $matches)) {
            $version = $matches[1];
        } else {
            $version = 'unknown';
        }

        return $name;
    }

    // viene dato un messaggio agli utenti che accedono con vechie versioni di IE
    // IE8 =>  5 marzo 2008,  ultimo compatibile XP
    // IE9 =>  16 marzo 2010, compatibile Vista e win7
    // IE10 => 26 ottobre 2012, win8
    // IE11 => 7 novembre 2013, win7 e win8
    static public static function isOldIE($opt = array()) {
        $option = array_merge(array(
            'min_version' => 7,
            'USER_AGENT' => self::getAgent()
                ), $opt);
        extract($option);

        $version = false;
        preg_match('/MSIE (.*?);/', $USER_AGENT, $matches);
        if (count($matches) > 1) {
            // è una versione di IE
            $version = $matches[1];
        }
        $version = self::getIEVersion();
        if ($version <= $min_version) {
            // è una vecchia versione di IE
            return true;
        }
        return false;
    }


    //----------------------------------------------------------------------------
    //   other browser utils
    //----------------------------------------------------------------------------

    // insert these classes on the html element of the document to eneble smarter css served to the current client
    public static function getClasses() {
        $s = '';
        if (self::isIE()) {
            $s = 'ie ie' . self::getIEVersion();
        } elseif (self::isGecko()) {
            $s = 'gecko ';
            if (self::isFF()) {
                $s.= 'ff' . self::getFFVersion();
            }
        } elseif (self::isWebkit()) {
            $s = 'webkit';
            if (self::isSafari()) {
                $s.= 'safari';
            }
        }
        return $s;
    }

    public static function getLang() {
        $l = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        // TODO: where to find accepted languages?
        $accepted_languages = array('en', 'it');
        if (in_array($l, $accepted_languages)) {
            return $l;
        }
        return 'en';
    }


    public static function isMobile($UA='') {
        if( empty($UA) ) {
            $UA = $_SERVER['HTTP_USER_AGENT'];
        }
        $iPod    = strpos($UA, 'iPod'    ) !== false;
        $iPhone  = strpos($UA, 'iPhone'  ) !== false;
        $iPad    = strpos($UA, 'iPad'    ) !== false;
        $android = strpos($UA, 'Android' ) !== false;
        //
        // log per trovare UA particolari
        // file_put_contents('./public/upload/install_log/agent',$_SERVER['HTTP_USER_AGENT']);
        //
        if( $iPad || $iPhone || $iPod || $android ) {
            return true;
        } else {
            return false;
        }
    }
    public static function isAndroid($UA='') {
        if( empty($UA) ) {
            $UA = $_SERVER['HTTP_USER_AGENT'];
        }

        return strpos($UA, 'Android' ) !== false;
    }
    public static function isIOS($UA='') {
        if( empty($UA) ) {
            $UA = $_SERVER['HTTP_USER_AGENT'];
        }

        $iPod   = strpos($UA, 'iPod'   ) !== false;
        $iPhone = strpos($UA, 'iPhone' ) !== false;
        $iPad   = strpos($UA, 'iPad'   ) !== false;
        return ($iPod || $iPhone || $iPad);
    }
    // classe usata sul body, aiuta la getione del css
    // @see Modernizer.js
    public static function getCSSClass($UA='') {
        $n = sscanf(self::translate(), "%s %s", $browser_name , $browser_version  );
        $os = '';
        if( self::isMobile($UA) ){
            $os .= self::isAndroid($UA) ? 'android' : '';
            $os .= self::isIOS($UA) ? 'ios' : '';
        }
        return " $os $browser_name";
    }

    // posiziona un cookie contenente la dimensione massima dello schermo, in questo modo la variabile è sempre disponibile
    // in alternativa si può usare anche una veriabile di sessione
    // si accede via $_COOKIE['resolution']
    public static function getResolutionHTML(){
        $html=<<<__END__
        <script>document.cookie='resolution='+Math.max(screen.width,screen.height)+'; expires=; path=/';</script>
__END__;
        return $html;
    }


}
