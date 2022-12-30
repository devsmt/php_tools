<?php
declare (strict_types = 1);
namespace DB {
    use Exception;

    // wrapper su db
    // uso: Sqlite::qry($sql)   Sqlite::exec($sql)
    class Sqlite {
        /** @var SQLite3|null $conn */
        protected static $conn = null;
        // use:
        // $path = sprintf('%s/%s.db', __DIR__,  $DB = 'XYZ'  );
        // Sqlite::connect( $path );
        public static function connect(string $path = ''):void {
            if (!empty(self::$conn)) {
                return;
            }
            if(empty($path)) {$path = self::getPath();}
            self::$conn = new \SQLite3($path);
            /**
             * @psalm-suppress TypeDoesNotContainType
             * @psalm-suppress MixedMethodCall
             */
            if (empty(self::$conn)) {
                $msg = self::$conn ? (string) self::$conn->lastErrorMsg() : '';
                $msg = sprintf('Errore %s ', $msg);
                throw new \Exception($msg);
            } else {
                // wait a few seconds for the lock to clear when you execute queries
                self::$conn->busyTimeout(5000);
                // WAL mode has better control over concurrency.
                // Source: https://www.sqlite.org/wal.html
                //  (it is defaulting to 'delete', as stated here: https://www.sqlite.org/wal.html (see Activating And Configuring WAL Mode)
                // self::$conn->exec('PRAGMA journal_mode = wal;');// Write-Ahead Logging
                //
                // // init db if needed
                // try {
                //     self::qry('select time from PORTFOLIO where ID=1');
                // } catch (\Exception $e) {
                //     self::_db_init();
                // }
            }
        }
        /*
        // app specific
        abstract public static function getPath() {
        $path = sprintf('%s/data/DB/%s.db', ROOT_PATH, 'project');
        //
        $dir = dirname($path);
        if( !file_exists( $dir ) ) {
        $msg = sprintf('Errore %s ', "$dir non valido");
        throw new \Exception($msg);
        }
        return $path;
        }
         */
        // ritorna stringa adatta a insert
        public static function escape(string $s): string {
            return self::quote($s);
        }
        public static function quote(string $s): string{
            // $i = ord($s);// ascii alpha to int
            // ispeziona una str per trovare caratteri che diano problemi in output
            $_str_sanitize = function ($str) {
                $r = '';
                $a_in = str_split($str);
                foreach ($a_in as $c) {
                    // ascii alpha to int
                    $i = ord($c);
                    // 0-32 128-254 non stampabili
                    // 33-127 stampabili
                    if (10 == $i || ($i >= 32 && $i <= 127)) {
                        $r .= $c; // solo caratteri visibili e innocui
                    } else {
                        // $r .= sprintf('@%s@', $i);// char che possono dare problemi
                    }
                }
                return $r;
            };
            $s2 = $_str_sanitize($s);
            return \SQLite3::escapeString($s2);
        }
        public static function disconnect(): void {
            // echo "SL disconnect \n";
            if (self::$conn) {
                self::$conn->close();
                self::$conn = null;
            }
        }
        // assicura che la connessione sia aperta
        public static function ensureConnection(): void {
            if (empty(self::$conn)) {
                self::connect();
            }
        }
        // memo func param
        public static function qry(string $SQL, array $params = []): array{
            static $__cached_data = [];
            if (!empty($params)) {
                ksort($params);
                $SQL .= json_encode($params);
            }
            if (!array_key_exists($SQL, $__cached_data)) {
                $__cached_data[$SQL] = self::_qry($SQL);
            }
            return $__cached_data[$SQL];
        }
        // param escaping
        // timing
        // logging
        // Sqlite::qry('SELECT bar FROM foo WHERE id=:id',[':id' => (int)$id ])
        static $_sql_log = [];
        /**
         * @param array<array-key, string> $params
         * @return list< array<array-key, mixed> >
         */
        public static function _qry(string $sql, array $params = []): array{
            // assicura che ci sia la connessione aperta
            self::ensureConnection();
            if (self::$conn) {
                if (empty($params)) {
                    $ret = self::$conn->query($sql);
                } else {
                    // 'SELECT bar FROM foo WHERE id=:id'
                    $stmt = self::$conn->prepare($sql);
                    foreach ($params as $key => $val) {
                        // $key like ':id'
                        $stmt->bindValue($key, $val, self::getArgType($val));
                    }
                    $ret = $stmt->execute();
                }
                $rs = [];
                while ($row = $ret->fetchArray(SQLITE3_ASSOC)) {
                    $rs[] = $row;
                }
                return $rs;
            } else {
                return [];
            }
        }
        // // cached query
        // public static function queryc($sql, $params=[], $ttl_secs = TTL_8H  ) {
        //     $_clean = function ($s){ return preg_replace('/[^a-z0-9]/i', '_',$s); };
        //     $key = sprintf('%s_%s_%s', $_clean(__METHOD__), md5($sql), md5(json_encode($params)) );
        //     // @see cache_
        //     return self::cache($key, function() use($sql, $params) {
        //             return self::query($sql, $params);
        //     }, $ttl_secs );
        // }
        //
        // ALWAYS SET THE CORRECT TYPE OF VARS:
        // For example:
        // $st = $db->prepare('SELECT * FROM test WHERE (a+1) = ?');
        // $st->bindValue(1, 2);
        // Will never return any result as it is treated by SQLite as if the query was
        // 'SELECT * FROM test WHERE (a+1) = "2"'.
        //
        // Instead you have to set the type manually:
        // $st = $db->prepare('SELECT * FROM test WHERE (a+1) = ?');
        // $st->bindValue(1, 2, \SQLITE3_INTEGER);
        // will work.
        //
        // SQLITE3_INTEGER: The value is a signed integer, stored in 1, 2, 3, 4, 6, or 8 bytes depending on the magnitude of the value.
        // SQLITE3_FLOAT: The value is a floating point value, stored as an 8-byte IEEE floating point number.
        // SQLITE3_TEXT: The value is a text string, stored using the database encoding (UTF-8, UTF-16BE or UTF-16-LE).
        // SQLITE3_BLOB: The value is a blob of data, stored exactly as it was input.
        // SQLITE3_NULL: The value is a NULL value.
        /**
         * @param mixed $arg
         */
        public static function getArgType($arg): int {
            switch (gettype($arg)) {
            case 'double':
                return SQLITE3_FLOAT;
                break;
            case 'integer':
                return SQLITE3_INTEGER;
                break;
            case 'boolean':
                return SQLITE3_INTEGER;
                break;
            case 'NULL':
                return SQLITE3_NULL;
                break;
            case 'string':
                return SQLITE3_TEXT;
                break;
            default:
                throw new \InvalidArgumentException('Argument is of invalid type ' . gettype($arg));
                break;
            }
        }
        // ritorna un songolo record ed eventualmente un singolo campo del record
        public static function qry_one(string $sql, string $column = '', array $params = []) {
            $recs = self::qry($sql, $params);
            if (!isset($recs[0])) {
                return [];
            }
            $rec = $recs[0];
            if (empty($column)) {
                return $rec;
            } else {
                return $rec[$column];
            }
        }
        //
        // esegue stmt diversi da select, es insert update delete
        //
        /**
         * @param array<array-key, string> $params
         * @return array{0?: SQLite3Result|bool, 1?: int}
         */
        public static function exec(string $sql, array $params = []) {
            if (self::isSelect($sql)) {
                $msg = sprintf('Errore %s ', 'exec() sql è un statement select, usare per insert');
                throw new \Exception($msg);
            }
            // assicura che ci sia la connessione aperta
            self::ensureConnection();
            if (self::$conn) {
                if (empty($params)) {
                    $ok = self::$conn->exec($sql);
                    // if insert or update
                    $ret2 = self::$conn->lastInsertRowID();
                    return [$ok, $ret2];
                } else {
                    // 'SELECT bar FROM foo WHERE id=:id'
                    $stmt = self::$conn->prepare($sql);
                    foreach ($params as $key => $val) {
                        // $key like ':id'
                        $stmt->bindValue($key, $val, self::getArgType($val));
                    }
                    $ok = $stmt->execute();
                    $last_id = self::$conn->lastInsertRowID();
                    return [$ok, $last_id];
                }
            } else {
                return [false, 0];
            }
        }
        // determina se una string è un select stmt
        public static function isSelect(string $sql): bool{
            // TODO: gestire linee di commento all'inizio dello statement
            $a_lines = explode("\n", $sql);
            $a_lines = array_filter($a_lines, function (string $line): bool {
                return substr($line, 0, $len = 2) !== '--'; // true retained, false skipped
            });
            $str = implode("\n", $a_lines);
            //
            $sql = trim($sql);
            $l = strlen('select');
            $sql_begin = strtolower(substr($sql, 0, $l));
            return 'select' == $sql_begin;
        }
    }
}
namespace {
    function db_create() {
        $a_sql = [];
        // test sul db appena aperto
        $a_sql[] = "CREATE TABLE IF NOT EXISTS ORDERS (
            ID INTEGER PRIMARY KEY ,
            ORDER_ID     INT NOT NULL,
            ORDER_DATE   INT NOT NULL,
            COD_AGENT       INT  NOT NULL,
            CLIENT_ID       INT  NOT NULL,
            DESTINATION_ID  INT,
            DATE_DELIVERY   INT  NOT NULL,
            DISCOUNT        DECIMAL(9,2),
            NOTE            CHAR(250)
        ); ";
        $a_sql[] = "CREATE UNIQUE INDEX IF NOT EXISTS ORDERS_IDX ON ORDERS (ORDER_ID, ORDER_DATE); ";
        //
        //
        $a_sql[] = "CREATE TABLE IF NOT EXISTS ORDER_ROW (
            ID INTEGER PRIMARY KEY ,
            ORDER_ID       INT NOT NULL,
            ORDER_DATE     INT NOT NULL,
            ITEM_ID        CHAR(50),
            QTA            INT  NOT NULL,
            PRICE          DECIMAL(9,2),
            INNER_MASTER   INT  NOT NULL,
            INNER          INT  NOT NULL,
            MASTER         INT  NOT NULL,
            NOTE           CHAR(250)
        ); ";
        $a_sql[] = "CREATE UNIQUE INDEX IF NOT EXISTS ORDER_ROW_IDX ON ORDER_ROW (ORDER_ID, ORDER_DATE, ITEM_ID); ";
        //
        foreach ($a_sql as $i => $sql) {
            $ret = \DB\Sqlite::exec($sql);
            echo "sql $i ", var_dump($ret), "\n";
        }
    }
    // test sul db appena aperto
    function _run_test() {
        $sql = "CREATE TABLE IF NOT EXISTS TEST_TBL (
            ID INTEGER PRIMARY KEY ,
            NAME     TEXT NOT NULL,
            AGE      INT  NOT NULL,
            ADDRESS  CHAR(50),
            NUM      REAL  );";
        $sql = "SELECT count(*) as COUNT from TEST_TBL;";
        $cnt = \DB\Sqlite::qry_one($sql, 'COUNT');
        echo var_dump($cnt), "\n";
        //
        $ret = \DB\Sqlite::exec('delete from TEST_TBL ');
        //
        $sql = "
            INSERT INTO TEST_TBL ( NAME, AGE, ADDRESS, NUM ) VALUES ( 'Paul',  32, 'California', 20000.00 );
            INSERT INTO TEST_TBL ( NAME, AGE, ADDRESS, NUM ) VALUES ( 'Allen', 25, 'Texas',      15000.00 );
            INSERT INTO TEST_TBL ( NAME, AGE, ADDRESS, NUM ) VALUES ( 'Teddy', 23, 'Norway',     20000.00 );
            INSERT INTO TEST_TBL ( NAME, AGE, ADDRESS, NUM ) VALUES ( 'Mark',  25, 'Rich-Mond ', 65000.00 ); ";
        //
        $ret = \DB\Sqlite::exec($sql);
        //
        $sql = "UPDATE TEST_TBL set NUM = 25000 where ID=1;";
        $ret = \DB\Sqlite::exec($sql);
        if ($ret) {
            //    echo self::$conn->changes(), " Record updated successfully\n";
        }
        //
        $sql = "SELECT * from TEST_TBL;";
        $a_rows = \DB\Sqlite::qry($sql);
        echo var_dump($a_rows), "\n";
        // select parameter
        $sql = "SELECT * from TEST_TBL where AGE = :age;";
        $a_rows = \DB\Sqlite::qry($sql, [':age' => 23]);
        echo var_dump($a_rows), "\n";
    }
    // if colled directly, run the tests:
    if (isset($argv) && basename($argv[0]) == basename(__FILE__)) {
        /** @psalm-suppress ForbiddenCode  */
        $user = `whoami`;
        if (trim($user) != 'www-data') {
            die("run under www-data user! \n");
        }
        if (!defined('ROOT_PATH')) {
            define('ROOT_PATH', realpath(__DIR__ . '/..'), false);
        }
        \DB\Sqlite::_run_test();
        // db_create();
    }
}
