<?php



// sfrutta php come parser di template
// nel template usare una ref tipo $this->chiave
class Template {

    protected $_path = '';
    protected $_partial_file;
    protected $_partial_vars;


    function __constructor($path, array $data = []) {
        // assign default vars
        if ($path != '') {
            if (!$this->setPath($path)) {
                $msg = sprintf('Errore %s ', 'template non trovato' );
                throw new Exception($msg);
            }
        }
        if (!is_null($data)) {
            $this->assign($data);
        }
    }

    function setPath($path) {
        if (file_exists($path) && is_readable($path)) {
            $this->_path = $path;
            return true;
        } else {
            return false;
        }
    }

    // possibile assegnare oggetti, array associativi e la classica
    // stringa chiave/valore
    // le var che iniziano per '_' sono sempre ignorate
    function assign() {
        // this method is overloaded.
        $arg = func_get_args();
        // must have at least one argument. no error, just do nothing.
        if (!isset($arg[0])) {
            return;
        }
        // assign by object
        if (is_object($arg[0])) {
            // assign public properties
            foreach (get_object_vars($arg[0]) as $key => $val) {
                if (substr($key, 0, 1) != '_') {
                    $this->$key = $val;
                }
            }
            return;
        }
        // assign by associative array
        if (is_array($arg[0])) {
            foreach ($arg[0] as $key => $val) {
                if (substr($key, 0, 1) != '_') {
                    $this->$key = $val;
                }
            }
            return;
        }
        // assign by string name and mixed value.
        //
        // we use array_key_exists() instead of isset() becuase isset()
        // fails if the value is set to null.
        if (is_string($arg[0]) && substr($arg[0], 0, 1) != '_' && array_key_exists(1, $arg)) {
            $key = $arg[0];
            $this->$key = $arg[1];
        } else {
            // errore! non puoi assegnare la var $arg[0]
        }
    }

    // lasciamo fare a php il il suo lavoro... di parser
    function parse() {
        // assicura che sia utilizzabile la sintassi corta <?=
        if (!ini_get('short_open_tag')) {
            ini_set('short_open_tag', 1);
        }

        // con questo settaggio al volo viene riconosciuto da php il percorso corrente in modo da poter scrivere nei template
        // include('_my_patial.php')
        $cur_include_path = ini_get('include_path');
        ini_set('include_path', $cur_include_path . PATH_SEPARATOR . dirname($this->_path) . PATH_SEPARATOR . TEMPLATE_PATH);
        ob_start();

        require ($this->_path);
        $b = ob_get_contents();
        ob_end_clean();
        $this->buffer = $b;
        ini_set('include_path', $cur_include_path);
        $this->buffer = $this->htmlButTags($this->buffer);
        //$this->buffer = $this->minimize($this->buffer);
        return $this->buffer;
    }

    function render() {
        $this->parse();
        return $this->buffer;
    }

    // Executes a partial template in its own scope, optionally with
    // variables into its within its scope.
    //
    // Note that when you don't need scope separation, using a call to
    // "include $this->template($name)" is faster.
    function partial($file, $vars = null) {
        $this->_partial_file = $file;
        // remove the partial name from local scope
        unset($name);
        // save partial vars externally. special cases for different types.
        // if ($vars instanceof Solar_Struct) { $this->_partial_vars = $vars->toArray(); } else
        if (is_object($vars)) {
            $this->_partial_vars = get_object_vars($vars);
        } else {
            $this->_partial_vars = (array) $vars;
        }
        // remove the partial vars from local scope
        unset($vars);
        // disallow resetting of $this and inject vars into local scope
        unset($this->_partial_vars['this']);
        extract($this->_partial_vars);
        // run the partial template
        ob_start();
        require $this->_partial_file;
        return ob_get_clean();
    }

    function beginContentFor($section_name) {
        ob_start();
    }

    function endContentFor($section_name) {
        if (isset($this->__content_blocks__[$section_name])) {
            $this->__content_blocks__[$section_name].= ob_get_clean();
        } else {
            $this->__content_blocks__[$section_name] = ob_get_clean();
        }
    }

    function addToHeader($html) {
        $this->header_content.= $html;
        $this->assign('header_content', $this->header_content);
    }

    /* --------------------------------------------------------------------------
    post processing functions
    -------------------------------------------------------------------------- */
    // encode entities, but preserve tags!
    function htmlButTags($str) {
        if( $this->doEncoding ) {
            // Take all the html entities
            $caracteres = get_html_translation_table(HTML_ENTITIES);
            // Find out the "tags" entities
            $remover = get_html_translation_table(HTML_SPECIALCHARS);
            // Spit out the tags entities from the original table
            $caracteres = array_diff($caracteres, $remover);
            // Translate the string....
            $str = strtr($str, $caracteres);
        }
        return $str;
    }

    // function minimize($b) {
    //   if($this->doMininize){
    //     $s = $b;
    //     // strip comments
    //     // strip spaces
    //     // TODO: non funziona --></div><!-- -->
    //     // TODO: gestire = @
    //     $s = preg_replace(array('/\r\n|\n|\r|\t|\s\s/', '/<!--([\s\w\.!#\$%\-+\'\"\<\>\\.\/]+)-->/'), '', $s);
    //     //$s = str_replace(array('  ',"\n", "\r", "\t"),'',$s );
    //     return $s;
    //   }
    // }

    /*
    // $html_body = preg_replace("/(<\/?)(\w+)([^>]*>)/e", "'\\1'.strtoupper('\\2').'\\3'", $html_body);
    function addAfterBeginHeader($v) {
    $this->buffer = preg_replace("/(<head)([^>]*>)/e", "'<head>\n$v'", $this->buffer);
    }
    function addBeforeEndHeader($v) {
    $this->buffer = preg_replace("/(<\/?)(head)([^>]*>)/e", "'$v\n</head>'", $this->buffer);
    }
    function addAfterBeginBody($v) {
    $this->buffer = preg_replace("/(<body)([^>]*>)/e", "'<body'.'\\2'.'\n$v'", $this->buffer);
    }
    function addBeforeEndBoby($v) {
    $rep = "'$v\n</body>'";
    $this->buffer = preg_replace("/(<\/body)([^>]*>)/e", $rep, $this->buffer);
    }
    */
}



