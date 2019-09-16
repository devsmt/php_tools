<?php

//
// funzione:
//   processa/produce più semplicemente un file XML
//
class XML {
    /*
    //this is a sample xml string
    $xml_string="<?xml version='1.0'?>
    <mydb>
    <itemname name='Benzine'>
    <symbol>ben</symbol>
    <code>A</code>
    </itemname>
    <itemname name='Water'>
    <symbol>h2o</symbol>
    <code>K</code>
    </itemname>
    </mydb>";
     */

    public static function strToArray($str) {
        $xml = simplexml_load_string($xml_string);
        $a = [];
        foreach ($xml->itemname as $record) {
            //attribute are accessted by
            echo $record['name'], '  ';
            //node are accessted by -> operator
            echo $record->symbol, '  ';
            echo $record->code, '<br />';
        }
        return $a;
    }

    public static function arrayToStr(array $a) {
        return;
    }

    // formatta una stringa XML valida in modo che sia leggibile
    public static function formatXML($xml) {
        $domxml = new DOMDocument('1.0');
        $domxml->preserveWhiteSpace = false;
        $domxml->formatOutput = true;
        $domxml->loadXML($xml);
        return $domxml->saveXML();
    }
}


// elaborazioni sul formato XML
class XML_clean {
    //
    // dato un documento XML, di tipo risposta
    // lo indenta
    // e toglie le parti che non interessano x il confronto di validità
    //
    static function indent_clean($xml, $page = '') {
        $xml = trim($xml);
        if (empty($xml)) {
            return '';
        }
        // deve almeno iniziare con un tag
        if (substr($xml, 0, 1) != '<') {
            echo "invalid XML:$xml \n";
            return '';
        }
        try {
            $xml = str_replace($substr = '>', $repl = '>' . PHP_EOL, $xml);
            $xml = str_replace_all('  ', ' ', $xml);
            // remove non utf8 encoded chars
            try {
                $xml = utf8_encode($xml);
            } catch (Exception $e) {
                $xml = self::remove_bs($xml);
            }
            //
            $dom = new DOMDocument;
            $dom->formatOutput = TRUE;
            $dom->preserveWhiteSpace = false;
            $dom->loadXML($xml);
            //----------------------------------------------------
            /// some xml processing
            $thedocument = &$dom->documentElement;
            //
            $_erase_tag_attributes = function ($tag, $attribute, $blank = 0) use (&$thedocument) {
                if (empty($thedocument)) {return;}
                $list = $thedocument->getElementsByTagName($tag);
                foreach ($list as $domElement) {
                    $attrValue = $domElement->getAttribute($attribute);
                    $domElement->setAttribute($attribute, $blank);
                    // $thedocument->removeChild($domElement);
                }
            };
            $_erase_tag_content = function ($tag, $blank = '') use (&$thedocument) {
                if (empty($thedocument)) {return;}
                $list = $thedocument->getElementsByTagName($tag);
                foreach ($list as $domElement) {
                    $domElement->textContent = $blank;
                }
            };
            $_erase_tags = function ($tag) use (&$thedocument) {
                if (empty($thedocument)) {return;}
                foreach ($thedocument->getElementsByTagName($tag) as $domElement) {
                    $thedocument->removeChild($domElement);
                }
            };
            $_modify_tag_content = function ($tag, $_f) use (&$thedocument) {
                if (empty($thedocument)) {return;}
                $list = $thedocument->getElementsByTagName($tag);
                foreach ($list as $domElement) { // DOMElement
                    $txt = $_f($domElement->textContent);
                    // echo "found $tag ({$domElement->textContent})=>($txt) \n";
                    $domElement->textContent = $txt;
                }
            };
            // // elimina timestamp
            // $_erase_tag_attributes($tag = 'TimeWriting', $attr = 'value');
            // // elimina timestamp
            // $_erase_tag_attributes($tag = 'Car', $attr = 'time');
            // echo "xml_processing $page \n";
            // if ($page == 'Segnalazione_Anomalie.jsp') {
            //     // toglie da Segnalazione_Anomalie.jsp il id risposta
            //     $_erase_tag_content('SANREG_OUT');
            //     $_erase_tag_content('SALING');
            //     // L’elemento PARPKDIN è la concatenazione di tutti i parametri in INPUT.DATA e contiene anche SALING
            //     $_modify_tag_content('PARPKDOUT', function ($txt) {
            //         $txt = trim($txt);
            //         $txt = substr_replace($txt, $char = '*', $pos = 377, 1); // togliamo il codice lingua,
            //         return $txt = substr($txt, 0, -7); // togliamo il codice prograssivo dalla stringa che è posizionale
            //     });
            //     // PARPKDIN contiene anche SCLING
            //     $_modify_tag_content('PARPKDIN', function ($txt) {
            //         $txt = trim($txt);
            //         $l = strlen($txt); //394
            //         $x = strlen('VIRTCAR2 000000') + 1;
            //         $txt = substr_replace($txt, $char = '*', $pos = ($l - $x), 1); // togliamo il codice lingua, che è posizionale
            //         return $txt;
            //     });
            // }
            // if ($page == 'Scheda_Anomalie.jsp') {
            //     $_erase_tag_content('SCLING');
            //     $_erase_tag_content('SCNREG_OUT');
            //     // PARPKDIN contiene anche SCLING
            //     $_modify_tag_content('PARPKDIN', function ($txt) {
            //         $txt = trim($txt);
            //         $txt = substr_replace($txt, $char = '*', $pos = 1734, 1); // togliamo il codice lingua, che è posizionale
            //         return $txt;
            //     });
            //     // PARPKDOUT
            //     $_modify_tag_content('PARPKDOUT', function ($txt) {
            //         $txt = trim($txt);
            //         $txt = substr($txt, 0, -7); // togliamo il codice prograssivo dalla stringa che è posizionale
            //         $txt = substr_replace($txt, $char = '*', $pos = 1734, 1); // togliamo il codice lingua, che è posizionale
            //         return $txt;
            //     });
            // }
            // // elimina messaggi di log
            // $_erase_tags('MessageLog');
            // // S_DSA_CAR2.jsp
            // $_erase_tag_attributes('HostName', 'value', '');
            // //
            // $_modify_tag_content('File', function ($txt) {
            //     $txt = trim($txt);
            //     $txt = preg_replace('/\s+/', '', $txt);
            //     return $txt;
            // });
            //----------------------------------------------------
            $xml2 = $dom->saveXML();
            return $xml2;
        } catch (Exception $e) {
            $msg = __FUNCTION__ . ' Exception: ' . $e->getMessage() . "\n";
            $msg .= sprintf(
                '%s line:%s ' . "\n" .
                '<trace>%s</trace>' . "\n",
                $e->getFile(), $e->getLine(), $e->getTraceAsString());
            $msg .= sprintf('invalid XML:"%s" ' . "\n", $xml);
            $msg .= '  line:' . self::get_line($xml, $e->getLine()) . "\n\n";
            echo $msg;
            return null;
        }
    }
    static function remove_bs($str) {
        $a_c = str_split($str);
        $str_2 = '';
        foreach ($a_c as $chr) {
            $chr_n = ord($chr);
            if (($chr_n > 31 && $chr_n < 127) || in_array($chr_n, [10, 13])) {
                $str_2 .= $chr;
            }
            // else { echo "XML !UTF8 char: $chr n:$chr_n \n"; }
        }
        return $str_2;
    }
    // estrae la linea errata
    static function get_line($text, $i) {
        $a_txt = preg_split('/[\s]+/', $text);
        return h_get($a_txt, ($i - 1), '');
    }
    // controlla che l'xml sia formalmente valido
    static function xml_is_valid($xml) {
        libxml_use_internal_errors(TRUE);
        $dom = new DOMDocument;
        $dom->loadXML($xml);
        // return $dom->validate();// use DTD
        $a_e = libxml_get_errors();
        //
        $a_e = array_map(function ($e) {
            // LibXMLError
            return sprintf('    XML PARSING ERROR: "%s" l:%s ', trim($e->message), $e->line);
        }, $a_e);
        libxml_clear_errors();
        return empty($a_e) ? [true, ''] : [false, implode("\n", $a_e)];
    }
}
//  C:/Users/TMirandola/Dati/Programmi/php/php C:\Users\TMirandola\Dati\Projects\git\dna_v2\bin\DNA.php
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {

    require_once __DIR__ . '/Test.php';

    //
    ok(LogXML::get_line($text = "text", 1), 'text', 'get_line 1');
    ok(LogXML::get_line($text = "text\ntext2", 1), 'text', 'get_line 2');
    ok(LogXML::get_line($text = "text\ntext2", 2), 'text2', 'get_line 3');



}