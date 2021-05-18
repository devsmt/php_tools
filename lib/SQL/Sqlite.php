<?php
declare (strict_types = 1);
namespace DB {
    use Exception;

    // wrapper su db
    // uso: Sqlite::qry($sql)   Sqlite::exec($sql)
    class Sqlite {
        protected static $conn = null;
        public static function connect() {
            $path = self::getPath();
            self::$conn = new \SQLite3($path);
            /** @psalm-suppress TypeDoesNotContainType  */
            if (!self::$conn) {
                $msg = sprintf('Errore %s ', self::$conn->lastErrorMsg());
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
        // app specific
        public static function getPath() {
            $path = sprintf('%s/data/DB/%s.db', ROOT_PATH, 'cantini');
            //
            $dir = dirname($path);
            if( !file_exists( $dir ) ) {
                $msg = sprintf('Errore %s ', "$dir non valido");
                throw new \Exception($msg);
            }
            return $path;
        }
        public static function disconnect() {
            // echo "SL disconnect \n";
            self::$conn->close();
            self::$conn = null;
        }
        // assicura che la connessione sia aperta
        public static function ensureConnection() {
            if (empty(self::$conn)) {
                self::connect();
            }
        }
        // param escaping
        // timing
        // logging
        // Sqlite::qry('SELECT bar FROM foo WHERE id=:id',[':id' => (int)$id ])
        public static function qry(string $sql, array $params = []): array{
            // assicura che ci sia la connessione aperta
            self::ensureConnection();
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
            $a_row = [];
            while ($row = $ret->fetchArray(SQLITE3_ASSOC)) {
                $a_row[] = $row;
            }
            return $a_row;
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
        /*
        ALWAYS SET THE CORRECT TYPE OF VARS:
        For example:
        $st = $db->prepare('SELECT * FROM test WHERE (a+1) = ?');
        $st->bindValue(1, 2);
        Will never return any result as it is treated by SQLite as if the query was
        'SELECT * FROM test WHERE (a+1) = "2"'.

        Instead you have to set the type manually:
        $st = $db->prepare('SELECT * FROM test WHERE (a+1) = ?');
        $st->bindValue(1, 2, \SQLITE3_INTEGER);
        will work.
        */
        // SQLITE3_INTEGER: The value is a signed integer, stored in 1, 2, 3, 4, 6, or 8 bytes depending on the magnitude of the value.
        // SQLITE3_FLOAT: The value is a floating point value, stored as an 8-byte IEEE floating point number.
        // SQLITE3_TEXT: The value is a text string, stored using the database encoding (UTF-8, UTF-16BE or UTF-16-LE).
        // SQLITE3_BLOB: The value is a blob of data, stored exactly as it was input.
        // SQLITE3_NULL: The value is a NULL value.
        public static function getArgType($arg) {
            switch (gettype($arg)) {
            case 'double':return SQLITE3_FLOAT;
            case 'integer':return SQLITE3_INTEGER;
            case 'boolean':return SQLITE3_INTEGER;
            case 'NULL':return SQLITE3_NULL;
            case 'string':return SQLITE3_TEXT;
            default:
                throw new \InvalidArgumentException('Argument is of invalid type ' . gettype($arg));
            }
        }

        // ritorna un songolo record ed eventualmente un singolo campo del record
        public static function qry_one(string $sql, string $column='', array $params = []) {
            $recs = self::qry($sql, $params);
            if( !isset($recs[0])  ) {
                return [];
            }
            $rec = $recs[0];
            if( empty($column) ) {
                return $rec;
            } else {
                return $rec[$column];
            }
        }

        //
        // esegue stmt diversi da select, es insert update delete
        //
        /** @return bool|array */
        public static function exec(string $sql, array $params = []) {
            if (self::isSelect($sql)) {
                $msg = sprintf('Errore %s ', 'exec() sql è un statement select, usare per insert');
                throw new \Exception($msg);
            }
            // assicura che ci sia la connessione aperta
            self::ensureConnection();
            $ret = false;
            if (empty($params)) {
                echo "$sql \n";
                $ret = self::$conn->exec($sql);
                /* try {
                    $ret = self::$conn->exec($sql);
                } catch (\Exception $e) {
                    $msg = sprintf('Exception: %s sql:%s', $e->getMessage(), $sql);
                    throw new \Exception($msg);
                    return false;
                }*/
                return $ret;
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
            if (!$ret) {
                $msg = sprintf('Errore %s %s', self::$conn->lastErrorMsg(), $sql);
                throw new \Exception($msg);
            }
            return $ret;
        }
        // determina se una string è un select stmt
        public static function isSelect(string $sql): bool {
            return 'select' == $sql_begin = strtolower(substr($sql = trim($sql), 0, $l = strlen('select')));
        }
        // db creation routine
        public static function _run_test(): int {
            return 0;
        }
    }
}
namespace {

    function db_create(){
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
        foreach($a_sql as $i => $sql) {
            $ret = \DB\Sqlite::exec($sql);
            echo "sql $i ",var_dump( $ret ),"\n";
        }
    }

        // test sul db appena aperto
        public static function _run_test() {
            $sql = "CREATE TABLE IF NOT EXISTS TEST_TBL (
            ID INTEGER PRIMARY KEY ,
            NAME     TEXT NOT NULL,
            AGE      INT  NOT NULL,
            ADDRESS  CHAR(50),
            NUM      REAL  );";

            $sql = "SELECT count(*) as COUNT from TEST_TBL;";
            $cnt = self::qry_one($sql, 'COUNT' );
            echo var_dump( $cnt ), "\n";
            //
            $ret = self::exec('delete from TEST_TBL ');
            //
            $sql = "
            INSERT INTO TEST_TBL ( NAME, AGE, ADDRESS, NUM ) VALUES ( 'Paul',  32, 'California', 20000.00 );
            INSERT INTO TEST_TBL ( NAME, AGE, ADDRESS, NUM ) VALUES ( 'Allen', 25, 'Texas',      15000.00 );
            INSERT INTO TEST_TBL ( NAME, AGE, ADDRESS, NUM ) VALUES ( 'Teddy', 23, 'Norway',     20000.00 );
            INSERT INTO TEST_TBL ( NAME, AGE, ADDRESS, NUM ) VALUES ( 'Mark',  25, 'Rich-Mond ', 65000.00 ); ";
            //
            $ret = self::exec($sql);
            //
            $sql = "UPDATE TEST_TBL set NUM = 25000 where ID=1;";
            $ret = self::exec($sql);
            if ($ret) {
                echo self::$conn->changes(), " Record updated successfully\n";
            }
            //
            $sql = "SELECT * from TEST_TBL;";
            $a_rows = self::qry($sql);
            echo var_dump( $a_rows), "\n";
            // select parameter
            $sql = "SELECT * from TEST_TBL where AGE = :age;";
            $a_rows = self::qry($sql, [':age' => 23 ]);
            echo var_dump(  $a_rows), "\n";
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
