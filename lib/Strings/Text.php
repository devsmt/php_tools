<?php
// text helpers/formatters
class Text {
    /**
     * @param list< array<array-key, mixed> > $RS
     */
    public static function table(array $RS, array $option = []): string{
        $option = array_merge($_opt_def = [
            'delegates' => [],
        ], $option);
        extract($option);
        if (empty($RS)) {
            return '';
        }
        // conta solo i char, non i caratteri di controllo usati per generare la colazione
        $_strlen_u = function (string $s): int{
            $s_in = CLI::uncolor($s);
            return strlen($s_in);
        };
        $_padr_u = function (string $s, int $len) use ($_strlen_u): string {
            $cur_len = $_strlen_u($s);
            if ($cur_len >= $len) {
                return $s;
            }
            $pad_len = $len - $cur_len;
            $s2 = $s . str_repeat(' ', $pad_len);
            return $s2;
        };
        $_pad = function (string $s, int $col_max_len) use ($_strlen_u, $_padr_u): string {
            $sp = $s;
            if ($_strlen_u($s) <= $col_max_len) {
                $sp = $_padr_u($s, $col_max_len);
            }
            return $sp;
        };
        $_implode = function (array $a): string {return implode(' | ', $a);};
        $_trim_data = function (array $a): array{
            $a2 = [];
            foreach ($a as $i => $rec) {
                foreach ($a[$i] as $k => $v) {
                    $a2[$i][trim($k)] = trim(strval($v)); // $a[$i][$k]
                }
            }
            return $a2;
        };
        // calc max lenght for each key
        $_calc_max_len = function (array $rs) use ($_strlen_u): array{
            if( empty($rs ) ) { return []; }
            $first_key = array_key_first($rs);
            $first_rec = !is_null($first_key) ? $rs[$first_key] : [];
            $a_keys = array_keys($first_rec);
            //
            $a_max_len = [];
            // $max_len_avl = (int) ceil(330 / count($a_keys));
            // cerca la max len
            foreach ($a_keys as $k) {
                $a_max_len[$k] = $_strlen_u($k);
                foreach ($rs as $i => $cur_val) {
                    $cur_val = $rs[$i][$k];
                    $cur_len = $_strlen_u($cur_val);
                    $max_len = max([$a_max_len[$k], $cur_len]);
                    // non superare una soglia data dalla larghezza del terminale
                    $a_max_len[$k] = (int) $max_len; // min($max_len, $max_len_avl);
                }
            }
            return $a_max_len;
        };
        // main -------------------
        $rs2 = $_trim_data($RS);
        $first_key = array_key_first($rs2);
        $a_max_len = $_calc_max_len($rs2);
        // stampa intestazione
        $head = '';
        $a_head = [];
        foreach ($rs2[$first_key] as $k => $v) {
            $col_name = isset($a_labels[$k]) ? $a_labels[$k] : $k;
            $a_head[] = $_pad($col_name, (int) $a_max_len[$col_name]);
        }
        $head = $_implode($a_head) . "\n";
        // stampa righe
        $trs = '';
        foreach ($rs2 as $i => $cur_val) {
            $tds = '';
            $a_tds = [];
            foreach ($rs2[$i] as $k => $v) {
                $a_tds[] = $_pad($v, $a_max_len[$k]);
            }
            $tds = $_implode($a_tds);
            $trs .= $tds . "\n";
        }
        return $head . $trs;
    }
    // helper che permette di formattare un array di dati come "tabella" testuale
    // data layout: Array<Array<string> >
    //     $a_data = [
    //         ['a','b','c'], // prima riga contiene le headers
    //         [1,2,3],
    //         [1,2,3],
    //     ];
    public static function table_csv(array $data): string{
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
    /**
     * converte un layout RS Aray< Hash > to Array<Array<string> >
     * da usare con Text::table_cvs() se il formato non è quello opportuno
     * @param  list< array<string, mixed> >  $rs
     * @return array<int, list<string> >
     */
    public static function rs_to_csv(array $rs): array{
        if( empty($rs ) ) {
            return [];
        }
        $a_csv = [];
        $a_csv[0] = array_keys($rs[0]);
        foreach ($rs as $i => $rec) {
            $a_v = array_values($rec);
            $a_v = array_map(function ($val): string {
                return trim(strval($val));
            }, $a_v);
            $a_csv[1 + $i] = $a_v;
        }
        return $a_csv;
    }
    // evidenzia una parola del testo
    public static function word_select(string $text, array $matches, string $replace = 'b'): string {
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
    public static function word_wrap(string $str, int $max_len = 14, string $s_indent = '  '): array{
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
    public static function split_multilines(string $item_descr, int $max_len = 40): array{
        $item_descr = trim($item_descr);
        $item_descr = str_replace($sub = '  ', $re = ' ', $item_descr);
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
    public static function str_reminder(string $str, int $maxlen = 50, string $suffisso = ' [...] '): string {
        if (mb_strlen($str) > $maxlen) {
            $result = '';
            $str = str_replace('  ', ' ', $str);
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
    public static function truncate(string $text, int $length = 1024, string $ending = '...', bool $exact = false, bool $considerHtml = true): string{
        $open_tags = [];
        if ($considerHtml) {
            // if the plain text is shorter than the maximum length, return the whole text
            if (mb_strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
                return $text;
            }
            // splits all html-tags to scanable lines
            preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
            $total_length = mb_strlen($ending);
            $ret = '';
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
                    // add html-tag to $ret'd text
                    $ret .= $line_matchings[1];
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
                    $ret .= mb_substr($line_matchings[2], 0, $left + $entities_length);
                    // maximum length is reached, so get off the loop
                    break;
                } else {
                    $ret .= $line_matchings[2];
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
                $ret = mb_substr($text, 0, $length - mb_strlen($ending));
            }
        }
        // if the words shouldn't be cut in the middle...
        if (!$exact) {
            // ...search the last occurrence of a space...
            $spacepos = mb_strrpos($ret, ' ');
            if (!empty($spacepos)) {
                // ...and cut the text in this position
                $ret = mb_substr($ret, 0, $spacepos);
            }
        }
        // add the defined ending to the text
        $ret .= $ending;
        if ($considerHtml) {
            // close all unclosed html-tags
            foreach ($open_tags as $tag) {
                $ret .= "</$tag>";
            }
        }
        return $ret;
    }
    /**
     * Search for links inside html attributes
     *
     * @param string $text
     *
     * @return string[] Array of found links or empty array otherwise
     */
    public static function find_links(string $text): array{
        preg_match_all('/"(http[s]?:\/\/.*)"/Uims', $text, $links);
        return $links[1] ?: [];
    }
    public static function text_auto_link(string $text): string{
        $text = mb_ereg_replace("/([a-zA-Z]+:\/\/[a-z0-9\_\.\-]+" . "[a-z]{2,6}[a-zA-Z0-9\/\*\-\_\?\&\%\=\,\+\.]+)/", " <a href=\"$1\" target=\"_blank\">$1</a>", $text);
        $text = mb_ereg_replace("/[^a-z]+[^:\/\/](www\." . "[^\.]+[\w][\.|\/][a-zA-Z0-9\/\*\-\_\?\&\%\=\,\+\.]+)/", " <a href=\"\" target=\"\">$1</a>", $text);
        $text = mb_ereg_replace("/([\s|\,\>])([a-zA-Z][a-zA-Z0-9\_\.\-]*[a-z" . "A-Z]*\@[a-zA-Z][a-zA-Z0-9\_\.\-]*[a-zA-Z]{2,6})" . "([A-Za-z0-9\!\?\@\#\$\%\^\&\*\(\)\_\-\=\+]*)" . "([\s|\.|\,\<])/i", "$1<a href=\"mailto:$2$3\">$2</a>$4", $text);
        return $text;
    }
    /** $a_links = text_link_extract($page);
     * @return list<list{string, string}>
     */
    public static function text_link_extract(string $s): array{
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
    /** plotta valori
     * disegna un istogramma orizzontale
     * @see https://github.com/JuliaPlots/UnicodePlots.jl
     * @see https://github.com/red-data-tools/unicode_plot.rb
     * @param array<string, int|float> $data
     */
    public static function barplot(array $data, array $opt = []): string {
        if (empty($data)) {
            return '';
        }
        $option = array_merge([
            'scale' => 0,
            'title' => '',
        ], $opt);
        $title = '';
        $scale = -1;
        extract($option, $flgs = EXTR_OVERWRITE);
        // get the max len of array keys
        /** @var list<string> $a_keys */
        $a_keys = array_keys($data);
        $a_l = array_map(fn($val) => strlen($val), $a_keys);
        $max_len = max($a_l);
        // sort the data
        arsort($data, SORT_NUMERIC); // &$a;  sort values! returns bool
        // decide the scale:
        if (empty($scale)) {
            // get the max value
            /** @var  array<int|float> $a_vals */
            $a_vals = array_values($data);
            $max_val = max($a_vals);
            $max_val = floatval($max_val);
            $unit = ceil(($max_val / 80)); //quanto rappresentiamo per ogni step
            if ($unit <= 5) {
                $scale = 5;
            } elseif ($unit <= 10) {
                $scale = 10;
            } elseif ($unit <= 50) {
                $scale = 10;
            } elseif ($unit <= 100) {
                $scale = 100;
            } elseif ($unit <= 250) {
                $scale = 250;
            } elseif ($unit <= 500) {
                $scale = 500;
            } elseif ($unit <= 1000) {
                $scale = 1000;
            } else {
                $msg = sprintf('Errore scale:%s too big ', $scale);
                throw new \Exception($msg);
            }
        } else {
            $scale = (int) $scale;
        }
        $ret = '';
        if (!empty($title)) {
            $ret .= "------ $title ------- \n";
        }
        // plot
        /** @var string $key
         * @var int|float $var */
        foreach ($data as $key => $val) {
            $key_p = str_pad($key, $max_len, ' ', STR_PAD_RIGHT); //no utf8 support
            //
            $inc = (float) (floatval($val) / $scale);
            $inc = (int) floor($inc);
            $val_bar = str_repeat('=', $inc);
            if (($inc * $scale) < $val) { // rimarrebbe un pezzetto non plottato?
                $val_bar .= '_';
            }
            $ret .= "$key_p $val_bar $val \n";
        }
        $ret .= "scale: $scale \n";
        return $ret;
    }
    //
    //
    public static function panel(int $w, int $h, string $text, array $opt = []): string{
        $option = array_merge([
            'title' => '',
            'style' => 'solid',
        ], $opt);
        extract($option);
        $BORDER_SOLID = [
            'tl' => '┌',
            'tr' => '┐',
            'bl' => '└',
            'br' => '┘',
            't' => '─',
            'l' => '│',
            'b' => '─',
            'r' => '│',
        ];
        $BORDER_ASCII = [
            'tl' => '+',
            'tr' => '+',
            'bl' => '+',
            'br' => '+',
            't' => '-',
            'l' => '|',
            'b' => '-',
            'r' => '|',
        ];
        $h_b = $style == 'solid' ? $BORDER_SOLID : $BORDER_ASCII;
        //
        $line_top = str_repeat($char = $h_b['t'], $num = (intval($w) - 2));
        $line_top = $h_b['tl'] . $line_top . $h_b['tr'];
        //
        $line_bottom = str_repeat($char = $h_b['b'], $num = (intval($w) - 2));
        $line_bottom = $h_b['bl'] . $line_bottom . $h_b['br'];
        //
        $a_text = explode("\n", "$text");
        $a_text2 = array_map(function (string $line) use ($w): string {
            $line = trim($line);
            $line = str_pad($line, intval($w) - 4, ' ', STR_PAD_RIGHT);
            return "| $line |";
        }, $a_text);
        $txt_p = implode($sep = "\n", $a_text2);
        //
        return
        $line_top . "\n" .
        $txt_p . "\n" .
        $line_bottom;
    }
}
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/Strings.php';
    require_once __DIR__ . '/../DS/H.php';
    require_once __DIR__ . '/../Test.php';
    $max_len = 14;
    ok(Text::word_wrap(''), [], 'test empty');
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
    //
    $plot = Text::barplot(
        $data = [
            "Paris" => 200, //2_244,
            "New York" => 400, //8_406,
            "Moskau" => 190, //1_192,
            "Madrid" => 165, //3_165,
        ],
        $opt = [
            'title' => "Population",
        ],
    );
    echo Text::panel(150, 10, $plot);
}
