<?php
declare (strict_types = 1);
// date format recognize and format
class Date {
    //----------------------------------------------------------------------------
    //  recognize
    //----------------------------------------------------------------------------
    //
    public static function isTimeStamp(string $date): bool{
        // e' un intero composto di 10 cifre
        $rexp = '/^[0-9]{10}$/';
        return 1 == preg_match($rexp, $date);
    }
    // formato yyyy-MM-dd
    public static function isISO(string $date): bool{
        $rexp = '/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/';
        return !empty($date) && 1 == preg_match($rexp, $date);
    }
    //
    public static function isIT(string $date): bool{
        $rexp = '/^[0-9]{1,2}\/[0-9]{1,2}\/[0-9]{2,4}$/';
        $rexp2 = '/^[0-9]{1,2}-[0-9]{1,2}-[0-9]{2,4}$/';
        return !empty($date) && (1 == preg_match($rexp, $date) || 1 == preg_match($rexp2, $date));
    }
    //
    public static function isISODateTime(string $date): bool{
        $rexp = '/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/';
        return !empty($date) && 1 == preg_match($rexp, $date);
    }
    //
    public static function isAS400Date(string $date_int): bool{
        $date_int = trim($date_int);
        if (empty($date_int)) {
            return false;
        }
        if (strlen($date_int) != 8) {
            return false;
        }
        $rexp = '/^[0-9]{8}$/';
        $num_matches = preg_match($rexp, $date_int); // returns 1 pattern matches, 0 if not, FALSE if an error
        if ($num_matches === 0) {
            return false;
        }
        return true;
    }
    //
    public static function isEmpty(string $date): bool {
        return is_null($date) || in_array($date, ['00-00-0000', '00/00/0000', '0000-00-00', '0000/00/00']);
    }
    //----------------------------------------------------------------------------
    //
    //----------------------------------------------------------------------------
    // prova a riconoscere diversi formati e se ne riconosce uno, converte la data
    public static function toTimeStamp(string $date): string {
        if (self::isEmpty($date)) {
            return 0;
        } elseif (self::isTimeStamp($date)) {
            return $date;
        } elseif (self::isIT($date)) {
            list($d, $m, $y) = explode('/', str_replace('-', '/', $date));
            return mktime(0, 0, 0, $m, $d, $y);
        } elseif (self::isISO($date)) {
            list($y, $m, $d) = explode('-', str_replace('/', '-', $date));
            return mktime(0, 0, 0, $m, $d, $y);
        } elseif (self::isISODateTime($date)) {
            list($date_d, $date_t) = explode(' ', $date);
            list($y, $m, $d) = explode('-', str_replace('/', '-', $date_d));
            list($s, $min, $h) = explode($date_t);
            return mktime($s, $min, $h, $m, $d, $y);
        } else {
            return 0;
        }
    }
    //
    public static function toFmt(string $date, string $fmt = 'Y-m-d'): string {
        return date($fmt, Self::toTimeStamp($date));
    }
    //
    public static function toISO(string $date): string {
        return date('Y-m-d', Self::toTimeStamp($date));
    }
    //
    public static function toIT(string $date): string {
        return date('d/m/Y', Self::toTimeStamp($date));
    }
    //----------------------------------------------------------------------------
    //
    //----------------------------------------------------------------------------
    // ultimo giorno di ogni mese
    public static function lastMonthDay(string $month): string {
        return self::daysInMonth($y = date('Y'), $month);
    }
    //
    public static function daysInMonth(int $y, int $m): int {
        // attenzione a indice 1, febbraio ha numero giorni variabile
        static $months = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        if ($m < 1 || $m > 12) {
            throw new \Exception('Invalid month: ' . $m);
        }
        // gestisce anno bisestile
        if (self::isLeapYear($y)) {
            $months[1] = 29;
        }
        return $months[$m - 1];
    }
    // stabilisce se la data e' nel passato
    public static function isPast(string $date): bool{
        $now = date('Y-m-d');
        $now = self::toTimeStamp($now);
        $date = self::toTimeStamp($date);
        return $date < $now;
    }
    // stabilisce se la data e' nel futuro
    public static function isFuture(string $date): bool{
        $now = date('Y-m-d');
        $now = self::toTimeStamp($now);
        $date = self::toTimeStamp($date);
        return $date > $now;
    }
    // stabilisce se la data corrente e' tra le due date
    // in input. ritorna false se uno dei due par e' nullo
    public static function isBetween(string $past, string $future, $date = null): bool {
        if (is_null($past) || is_null($future)) {
            return false;
        }
        $date = self::toTimeStamp($date);
        $future = self::toTimeStamp($future);
        $past = self::toTimeStamp($past);
        return ($date < $future) && ($date > $past);
    }
    // $date format 'Y-m-d' '2000-01-01'
    public static function add(string $date, int $days): string{
        $date = new DateTime($date);
        date_add($date, date_interval_create_from_date_string($days . ' days'));
        return date_format($date, 'Y-m-d');
    }
    //
    public static function sub(string $date, int $days): string{
        $date = new DateTime($date);
        date_sub($date, date_interval_create_from_date_string($days . ' days'));
        return date_format($date, 'Y-m-d');
    }
    // basic implementation of ruby time_ago_in_words()
    public static function time_ago_in_words($time): string{
        $time = (!is_int($time)) ? strtotime($time) : $time;
        $now = time();
        $remainder = $now - $time;
        if ($remainder < 60) {
            return $remainder . ' seconds ago';
        } else if ($remainder < 3600) {
            $number = ceil($remainder / 60);
            $suffix = ($number > 1) ? 's' : '';
            return $number . ' minute' . $suffix . ' ago';
        } else if ($remainder < 86400) {
            $number = floor($remainder / 3600);
            $suffix = ($number > 1) ? 's' : '';
            return $number . ' hour' . $suffix . ' ago';
        } else {
            $number = floor($remainder / 86400);
            $suffix = ($number > 1) ? 's' : '';
            return $number . ' day' . $suffix . ' ago';
        }
    }
    //
    public static function formatAge($value): string{
        $now = new DateTime();
        $created = new DateTime($value);
        $interval = $now->diff($created);
        $fmt = '%s';
        if ($interval->y) {
            $format = '%y ' . __($fmt, 'year|years', $interval->y);
        } else if ($interval->m) {
            $format = '%m ' . __($fmt, 'month|months', $interval->m);
        } else if ($interval->days) {
            $format = '%a ' . __($fmt, 'day|days', $interval->days);
        } else if ($interval->h) {
            $format = '%h ' . __($fmt, 'hour|hours', $interval->h);
        } else {
            $format = '%i ' . __($fmt, 'min', $interval->i);
        }
        return $interval->format($format);
    }
    // formatta un numero elevato di secondi(es sottrazione di due timestamp)
    // secsToStr( time() - $_SERVER['REQUEST_TIME'] )
    public static function secsToStr($secs) {
        if ($secs >= 86400) {
            $days = floor($secs / 86400);
            $secs = $secs % 86400;
            $r = $days . ' day';
            if ($days != 1) {
                $r .= 's';
            }
            if ($secs > 0) {
                $r .= ', ';
            }
        }
        if ($secs >= 3600) {
            $hours = floor($secs / 3600);
            $secs = $secs % 3600;
            $r .= $hours . ' hour';
            if ($hours != 1) {
                $r .= 's';
            }
            if ($secs > 0) {
                $r .= ', ';
            }
        }
        if ($secs >= 60) {
            $minutes = floor($secs / 60);
            $secs = $secs % 60;
            $r .= $minutes . ' minute';
            if ($minutes != 1) {
                $r .= 's';
            }
            if ($secs > 0) {
                $r .= ', ';
            }
        }
        $r .= $secs . ' second';
        if ($secs != 1) {
            $r .= 's';
        }
        return $r;
    }
    // dato un timestamp $ts (operation begin[(es. $_SERVER['REQUEST_TIME'] )])
    // ritorna una stringa leggibile
    public static function duration($ts) {
        $time = time();
        $years = (int) ((($time - $ts) / (7 * 86400)) / 52.177457);
        $rem = (int) (($time - $ts) - ($years * 52.177457 * 7 * 86400));
        $weeks = (int) (($rem) / (7 * 86400));
        $days = (int) (($rem) / 86400) - $weeks * 7;
        $hours = (int) (($rem) / 3600) - $days * 24 - $weeks * 7 * 24;
        $mins = (int) (($rem) / 60) - $hours * 60 - $days * 24 * 60 - $weeks * 7 * 24 * 60;
        $secs = (int) ($time - $ts) - (($mins * 60) + ($hours * 60) + ($days * 24 * 60) + ($weeks * 7 * 24 * 60));
        $str = '';
        if ($years == 1) {
            $str .= "$years year, ";
        }
        if ($years > 1) {
            $str .= "$years years, ";
        }
        if ($weeks == 1) {
            $str .= "$weeks week, ";
        }
        if ($weeks > 1) {
            $str .= "$weeks weeks, ";
        }
        if ($days == 1) {
            $str .= "$days day,";
        }
        if ($days > 1) {
            $str .= "$days days,";
        }
        if ($hours == 1) {
            $str .= " $hours hour and";
        }
        if ($hours > 1) {
            $str .= " $hours hours and";
        }
        if ($mins == 1) {
            $str .= " 1 minute";
        } else {
            $str .= " $mins minutes";
        }
        if (!empty($secs)) {
            $str .= " $secs secs";
        }
        return $str;
    }
    // determina se è l'orario corrente rientra negli orari di lavoro
    public static function isBusinessDayAndHour(): bool{
        $h = date('H');
        $d = date('w'); // w   Numeric representation of the day of the week, 0 (for Sunday) through 6 (for Saturday)
        $is_h = $h >= 7 && $h < 23; //no la notte
        $is_d = $d >= 1; //no la domenica
        return $is_h && $is_d;
    }
    //
    public static function isLeapYear(int $y): bool {
        return ($y % 4 == 0) && (($y % 100 != 0) || ($y % 400 == 0));
    }
    //
    public static function isValidDate(int $y, int $m, int $d): bool {
        return $m >= 1 && $m <= 12 && $d >= 1 && $d <= self::daysInMonth($y, $m);
    }
}
if (isset($argv[0]) && basename($argv[0]) == basename(__FILE__)) {
    require_once __DIR__ . '/Test.php';
    diag("Date\n");
    $date = time();
    ok(Date::isTimeStamp($date), "isTimeStamp $date");
    $date = date('d-m-Y');
    ok(!Date::isTimeStamp($date), "!isTimeStamp $date");
    $date = date('Y-m-d');
    ok(!Date::isTimeStamp($date), "!isTimeStamp $date");
    ok(!Date::isTimeStamp(null), "!isTimeStamp NULL");
    $date = date('Y-m-d');
    ok(Date::isISO($date), "isISO $date");
    $date = time();
    ok(!Date::isISO($date), "!isISO $date");
    $date = date('d-m-Y');
    ok(!Date::isISO($date), "!isISO $date");
    ok(!Date::isISO(null), "!isISO NULL");
    $date = date('d-m-Y');
    ok(Date::isIT($date), "isIT $date");
    $date = date('d/m/Y');
    ok(Date::isIT($date), "isIT $date");
    $date = time();
    ok(!Date::isIT($date), "!isIT $date");
    $date = date('Y-m-d');
    ok(!Date::isIT($date), "!isIT $date");
    ok(!Date::isIT(null), "!isIT NULL");
    $date = null;
    ok(Date::isEmpty($date), "isEmpty NULL");
    $date = '00-00-0000';
    ok(Date::isEmpty($date), "isEmpty $date");
    $date = '00/00/0000';
    ok(Date::isEmpty($date), "isEmpty $date");
    $date = '0000-00-00';
    ok(Date::isEmpty($date), "isEmpty $date");
    $date = '0000/00/00';
    ok(Date::isEmpty($date), "isEmpty $date");
    $date = date('Y-m-d');
    ok(!Date::isEmpty($date), "!isEmpty $date");
    $date = date('d-m-Y');
    $expected = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
    ok(Date::toTimeStamp($date) == $expected, "toTimeStamp $date is $expected");
    $date = date('d/m/Y');
    $expected = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
    ok(Date::toTimeStamp($date) == $expected, "toTimeStamp $date is $expected");
    $date = time();
    $expected = time();
    ok(Date::toTimeStamp($date) == $expected, "toTimeStamp $date is $expected");
    $date = date('Y-m-d');
    $expected = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
    ok(Date::toTimeStamp($date) == $expected, "toTimeStamp($date) " . Date::toTimeStamp($date) . " is $expected");
    $expected = 0;
    ok(Date::toTimeStamp(null) == $expected, "toTimeStamp NULL is $expected");
    $date = date('d-m-Y');
    $expected = date('Y-m-d');
    ok(Date::toISO($date) == $expected, "toISO($date) is $expected");
    $date = date('d/m/Y');
    $expected = date('Y-m-d');
    ok(Date::toISO($date) == $expected, "toISO($date) is $expected");
    $expected = date('d/m/Y');
    $date = date('Y-m-d');
    $r = Date::toIT($date);
    ok($r == $expected, "toIT($date)=$r is $expected");
    /*
$yesterday= date('Y-m-d', mktime( 0, 0, 0, date('m'), date('d')-1, date('Y') ) );
ok( Date::isPast($yesterday) ,"$yesterday isPast");
$tomorrow= date('Y-m-d', mktime( 0, 0, 0, date('m'), date('d')+1, date('Y') ) );
ok( Date::isFuture($tomorrow) ,"$tomorrow isFuture");
ok( Date::isBetween($yesterday, $tomorrow) ,"now we are between $yesterday and $tomorrow");
ok( Date::isBetween($yesterday, $tomorrow, $yesterday) ,"$yesterday  between $yesterday and $tomorrow");
 */
}