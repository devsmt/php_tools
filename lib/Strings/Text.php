<?php

// text helpers/formatters
class Text {

    // helper che permette di formattare un array di dati come "tabella" testuale
    public static function table(array $data) {
        // calc len:
        // cicla righe con header
        // cicla colonne e aggiorna il conteggio se si trova un max
        $a_len = [];
        for ($i = 0; $i < count($data); $i++) {
            $a_r = $data[$i];
            for ($k = 0; $k < count($a_r); $k++) {
                // recorded max len
                $a_len[$k] = isset($a_len[$k]) ? $a_len[$k] : 0;
                // cur len
                $len = strlen($data[$i][$k]);
                // is new max?
                if ($len > $a_len[$k]) {
                    $a_len[$k] = $len;
                }
            }
        }

        // format data
        $s_tbl = '';
        for ($i = 0; $i < count($data); $i++) {
            $a_r = $data[$i];
            for ($k = 0; $k < count($a_r); $k++) {
                // applica format
                $a_r[$k] = str_pad($a_r[$k], $a_len[$k], ' ', STR_PAD_LEFT);
            }
            $s_tbl .= implode('  ', $a_r) . "\n";
        }
        return $s_tbl;
    }
    // evidenzia una parola del testo
    function word_select($text, array $matches, $replace = 'b') {
        foreach ($matches as $match) {
            switch ($replace) {
            case "u":
            case "b":
            case "i":
                $text = preg_replace("/([^\w]+)($match)([^\w]+)/",
                    "$1<$replace>$2</$replace>$3", $text);
                break;
            default:
                $text = preg_replace("/([^\w]+)$match([^\w]+)/",
                    "$1$replace$2", $text);
                break;
            }
        }
        return $text;
    }
    // spezza una stringa in n substr di lunghezza massima $max_len
    // mantenendo le parole complete(non le taglia a metà)
    // return string[]
    public static function word_wrap($str, $max_len = 14, $s_indent = '  ') {
        if (empty($str)) {
            return [];
        }
        $str = str_replace_all($s2s = '  ', $s1s = ' ', $str);
        $str = trim($str);
        // the simplest impl. but can't reliably indent following strings
        // return $lines = explode("\n", wordwrap($str, $max_len));
        $len = mb_strlen($str);
        if ($len > $max_len) {
            $a_str = explode(' ', $str);
            $a_r = [];
            $cur_i = 0;
            foreach ($a_str as $i => $sub_str) {
                // get len of current buffer str
                $cur_len = mb_strlen(H::get($a_r, $cur_i, ''));
                // se aggiungo la str supera la lunghezza stabilita?
                $virtual_len = $cur_len + 1 + mb_strlen($sub_str);
                if ($virtual_len > $max_len) {
                    $cur_i++;
                    $a_r[$cur_i] = $s_indent . $sub_str;
                } else {
                    // se esiste, appendi, altrimenti crea
                    if (isset($a_r[$cur_i])) {
                        $a_r[$cur_i] .= ' ' . $sub_str;
                    } else {
                        $a_r[$cur_i] = $sub_str;
                    }
                }
            }
            return $a_r;
        } else {
            return [$str];
        }
    }
    // da una stringa molto lunga
    // restituisce array di n substr di lunghezza massina $max_len
    // senza troncare le parole intere
    function split_multilines($item_descr, $max_len = 40) {
        $item_descr = trim($item_descr);
        $item_descr = mb_str_replace($sub = '  ', $re = ' ', $item_descr);
        $len = mb_strlen($item_descr);
        if ($len < $max_len) {
            return [$item_descr];
        } else {
            $a_str = explode(' ', $item_descr);
            $res = [];
            $idx = 0;
            foreach ($a_str as $_i => $str) {
                if (!isset($res[$idx])) {
                    $res[$idx] = $str;
                    continue;
                }
                // +1 dello spazio di separazione
                $new_len = (mb_strlen($res[$idx]) + 1 + mb_strlen($str));
                $max_len_c = ($idx + 1) * $max_len; //current maxlen
                if ($new_len <= $max_len_c) {
                    $res[$idx] .= " $str";
                } else {
                    $idx++; // next index
                    if (isset($res[$idx])) {
                        $res[$idx] .= " $str";
                    } else {
                        $res[$idx] = $str;
                    }
                }
            }
            $res = array_map(function ($val) {return trim($val);}, $res);
            return $res;
        }
    }

    // mostra solo n char di un testo lungo, evitando di spezzare le parole
    // brutalmente, ma non fa nulla di particolare per funzionare con html
    function str_reminder($str, $maxlen = 50, $suffisso = ' [...] ') {
        if (mb_strlen($str) > $maxlen) {
            $result = '';
            $str = mb_str_replace('  ', ' ', $str);
            $a = explode(' ', mb_substr($str, 0, $maxlen + 10)); // per migliorare le prestazioni vado a fare l'explode di una stringa ragionevolmente ridimensionata
            for ($i = 0; $i < count($a); $i++) {
                if (mb_strlen($result . $a[$i] . ' ') < $maxlen) {
                    $result .= $a[$i] . ' ';
                } else {
                    break;
                }
            }
            return trim($result) . ' ' . $suffisso;
        } else {
            return $str;
        }
    }
    /**
     * Truncates text
     *
     * Cuts a string to the length of <i>$length</i> and replaces the last characters
     * with the ending if the text is longer than length.
     * Function from CakePHP
     *
     * @license Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
     *
     * @param string $text         String to truncate
     * @param int    $length       Length of returned string, including ellipsis
     * @param string $ending       Ending to be appended to the trimmed string
     * @param bool   $exact        If <b>false</b>, $text will not be cut mid-word
     * @param bool   $considerHtml If <b>true</b>, HTML tags would be handled correctly
     *
     * @return string Truncated string
     */
    function truncate($text, $length = 1024, $ending = '...', $exact = false, $considerHtml = true) {
        $open_tags = [];
        if ($considerHtml) {
            // if the plain text is shorter than the maximum length, return the whole text
            if (mb_strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
                return $text;
            }
            // splits all html-tags to scanable lines
            preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
            $total_length = mb_strlen($ending);
            $truncate = '';
            foreach ($lines as $line_matchings) {
                // if there is any html-tag in this line, handle it and add it (uncounted) to the output
                if (!empty($line_matchings[1])) {
                    // if it's an "empty element" with or without xhtml-conform closing slash (f.e. <br/>)
                    if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|col|frame|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
                        // do nothing
                        // if tag is a closing tag (f.e. </b>)
                    } else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
                        // delete tag from $open_tags list
                        $pos = array_search($tag_matchings[1], $open_tags);
                        if ($pos !== false) {
                            unset($open_tags[$pos]);
                        }
                        // if tag is an opening tag (f.e. <b>)
                    } else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
                        // add tag to the beginning of $open_tags list
                        array_unshift($open_tags, mb_strtolower($tag_matchings[1]));
                    }
                    // add html-tag to $truncate'd text
                    $truncate .= $line_matchings[1];
                }
                // calculate the length of the plain text part of the line; handle entities as one character
                $content_length = mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
                if ($total_length + $content_length > $length) {
                    // the number of characters which are left
                    $left = $length - $total_length;
                    $entities_length = 0;
                    // search for html entities
                    if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) {
                        // calculate the real length of all entities in the legal range
                        foreach ($entities[0] as $entity) {
                            if ($entity[1] + 1 - $entities_length <= $left) {
                                $left--;
                                $entities_length += mb_strlen($entity[0]);
                            } else {
                                // no more characters left
                                break;
                            }
                        }
                    }
                    $truncate .= mb_substr($line_matchings[2], 0, $left + $entities_length);
                    // maximum length is reached, so get off the loop
                    break;
                } else {
                    $truncate .= $line_matchings[2];
                    $total_length += $content_length;
                }
                // if the maximum length is reached, get off the loop
                if ($total_length >= $length) {
                    break;
                }
            }
        } else {
            if (mb_strlen($text) <= $length) {
                return $text;
            } else {
                $truncate = mb_substr($text, 0, $length - mb_strlen($ending));
            }
        }
        // if the words shouldn't be cut in the middle...
        if (!$exact) {
            // ...search the last occurrence of a space...
            $spacepos = mb_strrpos($truncate, ' ');
            if (isset($spacepos)) {
                // ...and cut the text in this position
                $truncate = mb_substr($truncate, 0, $spacepos);
            }
        }
        // add the defined ending to the text
        $truncate .= $ending;
        if ($considerHtml) {
            // close all unclosed html-tags
            foreach ($open_tags as $tag) {
                $truncate .= "</$tag>";
            }
        }
        return $truncate;
    }

    /**
     * Search for links inside html attributes
     *
     * @param string $text
     *
     * @return string[] Array of found links or empty array otherwise
     */
    function find_links($text) {
        preg_match_all('/"(http[s]?:\/\/.*)"/Uims', $text, $links);
        return $links[1] ?: [];
    }
    function text_auto_link($text) {
        $text = mb_ereg_replace("/([a-zA-Z]+:\/\/[a-z0-9\_\.\-]+" . "[a-z]{2,6}[a-zA-Z0-9\/\*\-\_\?\&\%\=\,\+\.]+)/", " <a href=\"$1\" target=\"_blank\">$1</a>", $text);
        $text = mb_ereg_replace("/[^a-z]+[^:\/\/](www\." . "[^\.]+[\w][\.|\/][a-zA-Z0-9\/\*\-\_\?\&\%\=\,\+\.]+)/", " <a href=\"\" target=\"\">$1</a>", $text);
        $text = mb_ereg_replace("/([\s|\,\>])([a-zA-Z][a-zA-Z0-9\_\.\-]*[a-z" . "A-Z]*\@[a-zA-Z][a-zA-Z0-9\_\.\-]*[a-zA-Z]{2,6})" . "([A-Za-z0-9\!\?\@\#\$\%\^\&\*\(\)\_\-\=\+]*)" . "([\s|\.|\,\<])/i", "$1<a href=\"mailto:$2$3\">$2</a>$4", $text);
        return $text;
    }
    // $a_links = text_link_extract($page);
    function text_link_extract($s) {
        $a = [];
        if (preg_match_all('/<a\s+.*?href=[\"\']?([^\"\' >]*)[\"\']?[^>]*>(.*?)<\/a>/i',
            $s, $matches, PREG_SET_ORDER)
        ) {
            foreach ($matches as $match) {
                array_push($a, [$match[1], $match[2]]);
            }
        }
        return $a;
    }
}
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/Strings.php';
    require_once __DIR__ . '/../DS/H.php';
    require_once __DIR__ . '/../Test.php';
    $max_len = 14;
    ok(Text::word_wrap(''), [], 'test empty');
    ok(Text::word_wrap(null), [], 'test empty');
    ok(Text::word_wrap('Berlingo 08>18'), ['Berlingo 08>18'], 'test len 14');
    ok(Text::word_wrap('Berlingo     08>18'), ['Berlingo 08>18'], 'too much spaces');
    ok(Text::word_wrap('Berlingo Multispace 08>18'), ['Berlingo', '  Multispace', '  08>18'], 'test len 25');
    ok(Text::word_wrap('Logan xxlaxf 06>11'), ['Logan xxlaxf', '  06>11'], 'test 18');
    // other tests
    Text::word_wrap('Tourneo Courier 14>');
    Text::word_wrap('Tourneo Connect 02>13');
    Text::word_wrap('Astra J SportsTourer 10>16');
    Text::word_wrap('Sandero Stepway 13>');
    Text::word_wrap('Logan MCV Stepway 17>');
    Text::word_wrap('Logan MCV 5p 07>13');
}
