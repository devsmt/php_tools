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
        $a = array();
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
