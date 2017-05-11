<?php

// usa template per rendere il contenuto all'interno di un layout
// la classe deve conosce i percorsi ai template
// se non
class View {

    protected $layout = null;
    protected $contentTemplate = null;
    protected $file = '';
    protected $data = null;

    function __construct($file = '', $data = null) {
        /*
        if ($file == '') {
            $file = CURRENT_MODULE . '.php';
        }
        if (is_readable($file)) {
            $path = $file;
        } else {
            $path = Path::join(TEMPLATE_PATH, $file);
        }
        */
        $this->layout = new Template('layout.php', $data);
        // TODO:config
        $this->file = $file;
        $this->data = $data;
    }

    // output fatto generalmente da Response
    function render() {
        $this->contentTemplate = new Template($this->file, $this->data);
        $c = $this->contentTemplate->render();
        $this->layout->assign('body_content', $c);
        return $this->layout->render();
    }

}