<?php
//
// valida Shape di un array associativo o Hash
//   testa che l'hash abbia chiavi specifiche e queste siano del tipo definito
//
const bool = 'boolean';
const boolean = 'boolean';
const int = 'integer';
const integer = 'integer';
const float = 'double';
const double = 'double';
const string = 'string';
const arr = 'array';
const any = 'any';
// nullable
const Nbool = '?boolean';
const Nboolean = '?boolean';
const Nint = '?integer';
const Ninteger = '?integer';
const Nfloat = '?double';
const Ndouble = '?double';
const Nstring = '?string';
const Narr = '?array';
//
function nullable(string $type): string {
    return '?' . $type;
}
function optional(string $type): string {return nullable($type);}
//
const SHAPE_REG = '__SHAPE_REGISTRY';
$GLOBALS['__SHAPE_REGISTRY'] = [];
//
//
//
function shape_define(string $shape_name, array $shape_def): void {
    if (substr($shape_name, 0, 1) != 'T') {
        $msg = sprintf('Errore %s ', 'Shape should be defined with beginning uppercase T');
        throw new \Exception($msg); // exceptions_
    }
    define($shape_name, $shape_name, false);
    $GLOBALS[SHAPE_REG][$shape_name] = $shape_def;
}
//
// applica default sensati a un record che si presuppone abbia una determinata forma
//
function shape_init(string $shape_name, array $rec): void{
    $shape_def = $GLOBALS[SHAPE_REG][$shape_name];
    foreach ($shape_def as $key => $expected_type) {
        if (is_object($expected_type)) {
            // TODO? è possibile solo se l'oggetto ha un costruttore vuoto
        } else {
            switch ($expected_type) {
            case int:
            case int:
            case integer:
            case float:
            case double:
                $rec[$key] = 0;
                break;
            //
            case string:
                $rec[$key] = '';
                break;
            //
            case bool:
            case boolean:
                $rec[$key] = false;
                break;
            case arr:
                $rec[$key] = [];
                break;
            case any:
                $rec[$key] = null;
                break;
            //
            default:
                $msg = sprintf('Errore tipo non gestito %s ', $expected_type);
                throw new \Exception($msg);
                break;
            }
        }
    }
}
// crea un record di una determinata forma, inizializzato
function shape_mk(string $shape_name, array $rec): void{
    shape_init($shape_name, $rec = []);
}
//
// definisce un tipo Hash<T>, hash i cui valori possono essere di un solo tipo T
// shape_define_dictionary('THashString', string);
function shape_define_dictionary(string $dictionary_name, string $dictionary_type): void {
    // 'THash', string
    if (substr($dictionary_name, 0, 5) != 'THash') {
        $msg = sprintf('Errore %s ', 'Dictionary should be defined with beginning THash');
        throw new \Exception($msg); // exceptions_
    }
    define($dictionary_name, $dictionary_name, false);
    $GLOBALS[SHAPE_REG][$dictionary_name] = $dictionary_type;
}
//
// definisce una List<T> o Array<T>
function shape_define_list(string $list_name, string $list_type): void {
    // 'THash', string
    if (substr($list_name, 0, 5) != 'TList') {
        $msg = sprintf('Errore %s ', 'List should be defined with beginning THash');
        throw new \Exception($msg); // exceptions_
    }
    define($list_name, $list_name, false);
    $GLOBALS[SHAPE_REG][$list_name] = $list_type;
}
//
// valida la forma di un hash
//
function shape_validate(string $shape_name, array $shaped_array): bool{
    $shape_def = $GLOBALS[SHAPE_REG][$shape_name];
    // gestione shape THash || TList
    if (
        is_array($shape_def)
    ) {
        foreach ($shape_def as $key => $expected_type) {
            // key non esiste
            if (!array_key_exists($key, $shaped_array)) {
                throw new TypeError(sprintf('Missing key: "%s" ', $key));
            }
            $value = $shaped_array[$key];
            // se null, valido solo se nullable type
            if (is_null($value) && '?' === substr($expected_type, 0, 1)) {
                continue;
            }
            // stiamo validando un sottotipo
            if (substr($expected_type, 0, 1) == 'T' || substr($expected_type, 0, 2) == '?T') {
                $r = shape_validate($expected_type, $value);
            } else {
                // se object, valido solo se di tipo compatibile
                if (is_object($value)) {
                    if (!($value instanceof $expected_type)) {
                        throw new TypeError(
                            sprintf('Shape validation %s, Key "%s" must be of type %s, %s given', $shape_name, $key, $expected_type, get_class($value)));
                    }
                    continue;
                } else {
                    $actual_type = gettype($value);
                    if ($actual_type !== $expected_type) {
                        throw new TypeError(
                            sprintf('Shape validation %s, Key "%s" must be of the type %s, %s given', $shape_name, $key, $expected_type, $actual_type));
                    }
                }
            }
        }
    } else {
        // THashString
        // TListString
        $expected_type = $shape_def;
        $t = substr($shape_name, 0, 5);
        switch ($t) {
        case 'TList':
            foreach ($shaped_array as $value) {
                $actual_type = gettype($value);
                if ($actual_type !== $expected_type) {
                    throw new TypeError(
                        sprintf('Shape validation %s, must be of the type %s, %s given', $shape_name, $expected_type, $actual_type));
                }
            }
            break;
        case 'THash':
            foreach ($shaped_array as $key => $value) {
                $actual_type = gettype($value);
                if ($actual_type !== $expected_type) {
                    throw new TypeError(
                        sprintf('Shape validation %s, Key "%s" must be of the type %s, %s given', $shape_name, $key, $expected_type, $actual_type));
                }
            }
            break;
        }
    }
    return true;
}
//----------------------------------------------------------------------------
//  tests
//----------------------------------------------------------------------------

// if colled directly in CLI, run the tests:
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/../Test.php';
    //----------------------------------------------------------------------------
    //
    //----------------------------------------------------------------------------
    shape_define('TPoint', ['x' => int, 'y' => int]);
    //
    ok_excheption(function () {
        shape_validate('TPoint', $p1 = []);
    }, 'should detect missing x');
    ok_excheption(function () {
        shape_validate('TPoint', $p1 = ['x' => 'string', 'y' => 0]);
    }, 'should detect missing x type');
    ok(true, shape_validate('TPoint', $p1 = ['x' => 1, 'y' => 0]), 'should be ok');
    ok(true, shape_validate('TPoint', $p1 = ['x' => 1, 'y' => 0, 'z' => 3]), 'should be ok if redundant');
    ok_excheption(function () {
        shape_validate('TPoint', $p1 = ['x' => 1, 'y' => null]);
    }, 'should not be valid if null');
    //
    shape_define('TPointN', ['x' => int, 'y' => nullable('int')]);
    ok(true, shape_validate('TPointN', $p1 = ['x' => 1, 'y' => null]), 'should be ok with null');
    //
    // recursive, sub data types
    //
    shape_define('TRecursive', [
        'point' => 'TPoint',
        'id' => int,
    ]);
    ok_excheption(function () {
        shape_validate('TRecursive', $p1 = ['id' => 1, 'point' => []]);
    }, 'recursive should signal an empty substructure');
    ok(true, shape_validate('TRecursive', $p1 = ['id' => 1, 'point' => ['x' => 0, 'y' => 1]]), 'recursive validation ok');
    //
    //
    // TODO:
    shape_define_dictionary('THashString', string);
    ok(true, shape_validate('THashString', ['id' => 'a', 'b' => 'b']), 'THashString ok');
    ok_excheption(function () {
        shape_validate('THashString', ['id' => 0]);
    }, 'THashString should detect invalid type');
    //
    shape_define_list('TListString', string);
    ok(true, shape_validate('TListString', ['a', 'b']), 'TListString ok');
    // more complex type
    shape_define_list('TListPoint', 'TPoint');
    // define complex defs
    shape_define('TInvoice', [
        'head' => 'TPoint',
        'rows' => 'TListPoint',
    ]);
}
