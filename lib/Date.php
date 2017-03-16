<?php

class Date {

    // ultimo giorno di ogni mese
    public static function last_month_day($month) {
        $a_m = array(1 => 31, 2 => 28, 3 => 31, 4 => 30, 5 => 31, 6 => 30, 7 => 31, 8 => 31, 9 => 30, 10 => 31, 11 => 30, 12 => 31);
        if (array_key_exists($month, $a_m)) {
            return $a_m[$month];
        } else {
            return 0;
        }
    }

    public static function isTimeStamp($date) {
        // e' un intero composto di 10 cifre
        return preg_match('/^[0-9]{10}$/', $date);
    }

    public static function isISO($date) {
        return !empty($date) && preg_match('/^[0-9]{2,4}-[0-9]{1,2}-[0-9]{1,2}$/', $date);
    }

    public static function isIT($date) {
        return !empty($date) && (preg_match('/^[0-9]{1,2}\/[0-9]{1,2}\/[0-9]{2,4}$/', $date) || preg_match('/^[0-9]{1,2}-[0-9]{1,2}-[0-9]{2,4}$/', $date));
    }

    public static function isEmpty($date) {
        return is_null($date) || in_array($date, array('00-00-0000', '00/00/0000', '0000-00-00', '0000/00/00'));
    }

    public static function toTimeStamp($date) {
        if (Self::isTimeStamp($date)) {
            return $date;
        } elseif (Self::isIT($date)) {
            list($d, $m, $y) = explode('/', str_replace('-', '/', $date));
            return mktime(0, 0, 0, $m, $d, $y);
        } elseif (Self::isISO($date)) {
            list($y, $m, $d) = explode('-', str_replace('/', '-', $date));
            return mktime(0, 0, 0, $m, $d, $y);
        } else {
            return 0;
        }
    }

    public static function toISO($date) {
        return date('Y-m-d', Self::toTimeStamp($date));
    }

    public static function toIT($date) {
        return date('d/m/Y', Self::toTimeStamp($date));
    }

    // stabilisce se la data e' nel passato
    public static function isPast($date) {
        $now = date('Y-m-d');
        $now = self::toTimeStamp($now);
        $date = self::toTimeStamp($date);
        return $date < $now;
    }

    // stabilisce se la data e' nel futuro
    public static function isFuture($date) {
        $now = date('Y-m-d');
        $now = self::toTimeStamp($now);
        $date = self::toTimeStamp($date);
        return $date > $now;
    }

    // stabilisce se la data corrente e' tra le due date
    // in input. ritorna false se uno dei due par e' nullo
    public static function isBetween($past, $future, $date = null) {
        if (is_null($past) || is_null($future)) {
            return false;
        }
        $date = self::toTimeStamp($date);
        $future = self::toTimeStamp($future);
        $past = self::toTimeStamp($past);
        return ($date < $future) && ($date > $past);
    }

    // $date format 'Y-m-d' '2000-01-01'
    public static function add($date, $days) {
        $date = new DateTime($date);
        date_add($date, date_interval_create_from_date_string($days . ' days'));
        return date_format($date, 'Y-m-d');
    }

    public static function sub($date, $days) {
        $date = new DateTime($date);
        date_sub($date, date_interval_create_from_date_string($days . ' days'));
        return date_format($date, 'Y-m-d');
    }

    // basic implementation of ruby time_ago_in_words()
    public static function time_ago_in_words($time) {
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

    public static function formatAge($value) {
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

    // dato un timestamp $ts (operation begin[(es. $_SERVER['REQUEST_TIME'] )]) ritorna una stringa leggibile
    public static function duration($ts) {
        $time = time();
        $years = (int) ((($time - $ts) / (7 * 86400)) / 52.177457);
        $rem = (int) (($time - $ts) - ($years * 52.177457 * 7 * 86400));
        $weeks = (int) (($rem) / (7 * 86400));
        $days = (int) (($rem) / 86400) - $weeks * 7;
        $hours = (int) (($rem) / 3600) - $days * 24 - $weeks * 7 * 24;
        $mins = (int) (($rem) / 60) - $hours * 60 - $days * 24 * 60 - $weeks * 7 * 24 * 60;
        $secs = (int) ($time - $ts) - ( ( $mins * 60) + ($hours * 60) + ($days * 24 * 60) + ($weeks * 7 * 24 * 60) );
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
        if( !empty($secs) ) {
            $str .= " $secs secs";
        }
        return $str;
    }

    // determina se è l'orario corrente rientra negli orari di lavoro
    function isBusinessDayAndHour() {
        $h = date('H');
        $d = date('w'); // w   Numeric representation of the day of the week, 0 (for Sunday) through 6 (for Saturday)
        $is_h = $h >= 7 && $h < 23; //no la notte
        $is_d = $d >= 1; //no la domenica
        return $is_h && $is_d;
    }

    function is_leap_year(int $y): bool {
        return ($y % 4 == 0) && (($y % 100 != 0) || ($y % 400 == 0));
    }
    function is_valid_date(int $y, int $m, int $d): bool {
        return $m >= 1 && $m <= 12 && $d >= 1 && $d <= days_in_month($y, $m);
    }
}


function days_in_month(int $y, int $m): int {
    // attenzione a indice 1, febbraio ha numero giorni variabile
    static $months = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

    if ($m < 1 || $m > 12) {
        throw new \Exception('Invalid month: '.$m);
    }

    // gestisce anno bisestile
    if( is_leap_year($y) ) {
        $months[1] = 29;
    }

    return $months[$m - 1];
}

