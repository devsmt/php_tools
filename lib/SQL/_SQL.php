<?php
declare (strict_types = 1);
//
namespace DMS\DB2 {
    use DMS\Functions as F;
    use DMS\Hash as H;
    // generazione dinamica delle query
    class SQL {
        /**
         *  genera insert statement
         */
        public static function insert(string $table, array $h_input): string{
            $_quote = function (string $val): string {return "'$val'";};
            $_map_quote = function (array $data) use ($_quote): array{
                return array_map($_quote, $data);
            };
            $sql_data_fields = implode(',', array_keys($h_input));
            $sql_data_values = implode(',', $_map_quote(array_values($h_input)));
            //
            $sql = "INSERT INTO $table
            ($sql_data_fields)
            VALUES
            ($sql_data_values) ";
            return $sql;
        }
        /**
         * genera un statement sql update
         * @param array<string, scalar>  $a_fields
         */
        public static function update(string $table, array $a_fields, string $sql_where): string{
            $_quote = function (string $val): string {return "'$val'";};
            $_map_quote = function (array $data) use ($_quote): array{
                return array_map($_quote, $data);
            };
            // get key => val pairs && implode
            $_sql_set_fields = function (array $a_fields_f): string{
                $a_fields_pair = array_map(function (string $k, string $v): string {
                    return "$k = '$v'";
                }, array_keys($a_fields_f), array_values($a_fields_f));
                $sql_fields = implode($sep = ',' . PHP_EOL, $a_fields_pair);
                return $sql_fields;
            };
            $sql_fields = $_sql_set_fields($a_fields);
            //
            $sql = "update $table set " .
            $sql_fields .
            " where  $sql_where";
            return $sql;
        }
        // where condition ['a'=>1] -> where a = 1
        public static function where(array $h_filters): string {
            // $_sql_where_fields = function (array $a_fields_f) use ($_map_quote) :string {
            //     $_pair = function (string $k, string $v) use ($_map_quote) :string {
            //         if (is_scalar($v)) {
            //             return "`$k` = '$v'" . PHP_EOL;
            //         } elseif (is_array($v)) {
            //             return sprintf('%s in(%s)', $k, implode(',', $_map_quote($v))) . PHP_EOL;
            //         }
            //     };
            //     $a_fields_pair = array_map($_pair, array_keys($a_fields_f), array_values($a_fields_f));
            //     $sql_fields = implode($sep = ' and ', $a_fields_pair);
            //     return $sql_fields;
            // };
            // $sql_where = $_sql_where_fields($h_filters);
            return '';
        }
        // comprime la stringa sql per non creare logs pesanti da scaricare
        public static function compress(string $sql): string{
            $s_sql = trim($sql);
            $i = 1;
            while ($i > 0) {
                $s_sql = str_replace(["\n", "\r", "\t", '  '], ' ', $s_sql, $num_replacements);
                $i = $num_replacements; //until rep = 0
            }
            return $s_sql;
        }
    }
}
