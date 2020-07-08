<?php

//
class Dump {
    // mostra i valori di un RS
    /**
     * @param list< array<array-key, mixed> >|resource $rs
     */
    static function RS($rs, string $label='', array $config = []): string {
        if (strtolower(php_sapi_name()) == 'cli') {
            require_once __DIR__.'/Strings/Text.php';
            // @see Text::table for cli
            return Text::table($rs);
        }
        $a = [];
        if (is_resource($rs)) {
            // while ($row = mysql_fetch_array($rs)) {
            //     $a[] = $row;
            // }
        } elseif (is_array($rs)) {
            $a = $rs;
        }
        $html = '';
        foreach ($a as $i => $row) {
            if ($i == 0) {
                $html .= '<tr>';
                foreach ($row as $k => $v) {
                    $html .= "<th>$k</th>";
                }
                $html .= '</tr>';
            }
            $html .= '<tr>';
            foreach ($row as $k => $v) {
                $html .= "<td>$v</td>";
            }
            $html .= '</tr>';
        }
        return $html;
    }
}
