<?php


class Dump {

    // mostra i valori di un RS
    static function RS($rs, $label = "", $config = array()) {
        $a = array();
        while ($row = mysql_fetch_array($rs)) {
            $a[] = $row;
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
