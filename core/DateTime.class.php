<?php



/**
 * Клас 'core_DateTime' ['dt'] - Функции за работа с дата и време
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_DateTime
{
    
    // Дните от седмицата, съкратени
    static $weekDays = array('Пон', 'Вто', 'Сря', 'Чет', 'Пет', 'Съб', 'Нед');


    // Имената на месеците 
    static $months = array("Януари", "Февруари", "Март", "Април", "Май", "Юни",
            "Юли", "Август", "Септември", "Октомври", "Ноември", "Декември");

    /**
     * Превръща MySQL-ска data/време UNIX timestamp
     */
    static function mysql2timestamp($mysqlDate)
    {
        $mysqlDate = trim(strtolower($mysqlDate));
        $mysqlDate = str_replace(".", "/", $mysqlDate);
        $mysqlDate = str_replace("/", "/", $mysqlDate);
        $mysqlDate = str_replace("\\", "/", $mysqlDate);
        $mysqlDate = str_replace(",", "/", $mysqlDate);
        $mysqlDate = str_replace(";", "/", $mysqlDate);
        $mysqlDate = str_replace("  ", " ", $mysqlDate);
        $mysqlDate = str_replace("  ", " ", $mysqlDate);
        $mysqlDate = str_replace("''", ":", $mysqlDate);
        $mysqlDate = str_replace("'", ":", $mysqlDate);
        
        return strtotime($mysqlDate);
    }
    
    
    /**
     * Превръща UNIX timestamp в MySQL-ска дата
     */
    static function timestamp2Mysql($t)
    {
        return date("Y-m-d H:i:s", $t);
    }
    
    
    /**
     * Намира последния ден от месеца на една дата (като unixTimestamp)
     */
    static function lastDayOfMonth($date)
    {
        $month  = date("m", $date);
        $year   = date("Y", $date);
        
        return mktime(12, 59, 59, $month + 1, 0, $year);
    }
    
    
    /**
     * Превръща mySql дата във дни от началото на UNIX ерата
     */
    static function mysql2UnixDays($date)
    {
        return round(dt::mysql2timestamp($date) / (3600 * 24));
    }
    
    
    /**
     * Връща разликата в дни между две дати.
     * Може да работи само с дати в UNIX ерата
     */
    static function daysBetween($date1, $date2)
    {
        return dt::mysql2UnixDays($date1) - dt::mysql2UnixDays($date2);
    }
    
    
    /**
     * Превръща MySQL-ска data/време към вербална дата/време
     */
    static function mysql2verbal($mysqlDate, $mask = "d-m-y H:i", $lg = NULL)
    {
        if (!$mysqlDate || $mysqlDate == '0000-00-00' || $mysqlDate == '0000-00-00 00:00:00') {
            
            return FALSE;
        }
        
        if(!$lg) {
            $lg = core_Lg::getCurrent();
        }
        
        $mysqlDate = trim(strtolower($mysqlDate));
        $mysqlDate = str_replace("  ", " ", $mysqlDate);
        $mysqlDate = str_replace("''", ":", $mysqlDate);
        $mysqlDate = str_replace("'", ":", $mysqlDate);
        
        $time = strtotime($mysqlDate);
        
        $year = date('y', $time);
        $yearNow = date('y', time());
        
        if($mask == 'smartTime') {
            $addColor = TRUE;
            
            if($year != $yearNow) {
                if(Mode::is('screenMode', 'narrow')) {
                    $mask = 'd-m-y H:i';
                } else {
                    $mask = 'd-m-Y H:i';
                }
            } else {
                $smartMode = TRUE;
                $mask = 'd-M H:i';
                $today = dt::mysql2verbal(dt::verbal2mysql(), "d-M", 'en');
                $yesterday = dt::mysql2verbal(dt::addDays(-1), "d-M", 'en');
            }
        }
        
        if (($year == $yearNow) && Mode::is('screenMode', 'narrow')) {
            $mask = str_replace('-YEAR', '', $mask);
            $mask = str_replace('-year', '', $mask);
        }
        
        $mask = str_replace('YEAR', 'Y', $mask);
        $mask = str_replace('year', 'y', $mask);
        
        $verbDate = date($mask, $time);
        
        if($smartMode) {
            
            $fromArr = array($today, $yesterday);
            
            if($lg == 'bg') {
                $toArr = array('Днес', 'Вчера');
            } else {
                $toArr = array('Today', 'Yesterday');
            }
            
            $verbDate = str_replace($fromArr, $toArr, $verbDate);
        }
        
        $weekDaysLongEn = array("Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday");
        $weekDaysShortEn = array("Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun");
        $montsLongEn = array("January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December");
        $montsShortEn = array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");
        
        $weekDaysLongBg = array("Понеделник", "Вторник", "Сряда", "Четвъртък", "Петък", "Събота", "Неделя");
        $weekDaysShortBg = array("Пон", "Вто", "Сря", "Чет", "Пет", "Съб", "Нед");
        $montsLongBg = array("Януари", "Февруари", "Март", "Април", "Май", "Юни",
            "Юли", "Август", "Септември", "Октомври", "Ноември", "Декември");
        $montsShortBg = array("Яну", "Фев", "Мар", "Апр", "Май", "Юни", "Юли", "Авг", "Сеп", "Окт", "Ное", "Дек");
        
        if ($lg == "bg") {
            $verbDate = str_replace($weekDaysLongEn, $weekDaysLongBg, $verbDate);
            $verbDate = str_replace($weekDaysShortEn, $weekDaysShortBg, $verbDate);
            $verbDate = str_replace($montsLongEn, $montsLongBg, $verbDate);
            $verbDate = str_replace($montsShortEn, $montsShortBg, $verbDate);
        }
        
        if($addColor) {
            $dist = time() - $time;
            
            if($dist < 0) {
                $color = '080';
            } else {
                
                if($dist < 20) $dist = 20;
                
                $dist = round(pow(log($dist, 1.85) - log(20, 1.85), 1.85));
                
                if($dist <= 255) {
                    $g = 255 - $dist;
                    $b = $dist;
                    $r = $b / 3;
                    $b = $b - $r;
                } elseif($dist <= 511) {
                    $b = 256 - round($dist / 2);
                    $r = $b / 3;
                    $g = 0;
                    $b = $b - $r;
                } else {
                    $color = '000000';
                }
                
                $r = $r / 1.2; $b = $b / 1.2; $g = $g / 1.5;
                
                $g1 = $g;
                $g = $r;
                $r = $g1;
                
                if(!$color) {
                    $r = dechex($r<0 ? 0 : ($r>255 ? 255 : $r));
                    $g = dechex($g<0 ? 0 : ($g>255 ? 255 : $g));
                    $b = dechex($b<0 ? 0 : ($b>255 ? 255 : $b));
                    
                    $color = (strlen($r) < 2 ? '0' : '') . $r;
                    $color .= (strlen($g) < 2 ? '0' : '') . $g;
                    $color .= (strlen($b) < 2 ? '0' : '') . $b;
                }
            }
            
            $title = dt::mysql2verbal($mysqlDate, "d-M-Y H:i (l)");
            $title = "  title='{$title}'";
            
            $verbDate = "<font color='#$color' $title>{$verbDate}</font>";
        }
        
        return $verbDate;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function getMonthOptions()
    {
        $months = array(
            1 => tr("Януари"),
            2 => tr("Февруари"),
            3 => tr("Март"),
            4 => tr("Април"),
            5 => tr("Май"),
            6 => tr("Юни"),
            7 => tr("Юли"),
            8 => tr("Август"),
            9 => tr("Септември"),
            10 => tr("Октомври"),
            11 => tr("Ноември"),
            12 => tr("Декември")
        );
        
        return $months;
    }
    
    
    /**
     * Превръща вербална дата/време вкъм MySQL-ска data.
     * Ако няма параметър, връща текущото време, в страната, където е часовата зона.
     */
    static function verbal2mysql($verbDate = "", $full = TRUE)
    {
        if ($verbDate != "") {
            $verbDate = trim(strtolower($verbDate));
            
            $verbDate = str_replace(".", "-", $verbDate);
            $verbDate = str_replace("/", "-", $verbDate);
            $verbDate = str_replace("\\", "-", $verbDate);
            $verbDate = str_replace(",", "-", $verbDate);
            $verbDate = str_replace(";", "-", $verbDate);
            $verbDate = str_replace("  ", " ", $verbDate);
            $verbDate = str_replace("  ", " ", $verbDate);
            $verbDate = str_replace("''", ":", $verbDate);
            $verbDate = str_replace("'", ":", $verbDate);
            
            $s = "^([0-9]{1,2})-([0-9]{1,2})-([0-9]{2,4})^";
            preg_match($s, $verbDate, $out);
            
            if (count($out) > 0) {
                $day = $out[1];
                $month = $out[2];
                $year = $out[3];
            } else {
                $s = "^([0-9]{1,2})-([0-9]{1,2})^";
                preg_match($s, $verbDate, $out);
                
                if (count($out) > 0) {
                    $day = $out[1];
                    $month = $out[2];
                    $year = date("Y", time());
                } else {
                    
                    return FALSE;
                }
            }
            
            if ($day > 1900 && $year < 31) {
                $temp = $day;
                $day = $year;
                $year = $temp;
            }
            
            if ($month > 12 && $day < 12) {
                $temp = $day;
                $day = $month;
                $month = $temp;
            }
            
            // Некоректните дати с дни повече от колкото има в месеца ги приравняваме към последния ден
            if ($month == 2) {
                if (($year % 4 == 0) && ($year % 100 > 0)) {
                    // Високосна година
                    $daysInMonth = 29;
                } else {
                    $daysInMonth = 28;
                }
            } elseif ($month == 4 || $month == 6 || $month == 9 || $month == 11) {
                $daysInMonth = 30;
            } else {
                $daysInMonth = 31;
            }
            
            $day = min($day, $daysInMonth);
            
            $s = "^([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})^";
            preg_match($s, $verbDate, $out);
            
            if (count($out) > 0) {
                $hours = $out[1];
                $minutes = $out[2];
                $seconds = $out[3];
            } else {
                $s = "^([0-9]{1,2}):([0-9]{1,2})^";
                preg_match($s, $verbDate, $out);
                
                if (count($out) > 0) {
                    $hours = $out[1];
                    $minutes = $out[2];
                    $seconds = 0;
                } else {
                    $hours = 0;
                    $minutes = 0;
                    $seconds = 0;
                }
            }
            
            if ($year > 70 && $year < 100)
            $year += 1900;
            
            if ($year < 30)
            $year += 2000;
            
            if ($full) {
                //$date1 = date ("Y-m-d H:i:s", mktime ($hours, $minutes,$seconds,$month, $day, $year));
                $date2 = sprintf("%04d-%02d-%02d %02d:%02d:%02d", $year, $month, $day, $hours, $minutes, $seconds);
            } else {
                //$date1 = date ("Y-m-d", mktime ($hours,$minutes,$seconds,$month, $day, $year));
                $date2 = sprintf("%04d-%02d-%02d", $year, $month, $day);
            }
            
            return $date2;
            
            if ($date1 == $date2) {
                
                return $date1;
            } else {
                
                return FALSE;
            }
        } else {
            if ($full) {
                
                return date("Y-m-d H:i:s", time());
            } else {
                
                return date("Y-m-d", time());
            }
        }
    }
    
    
    /**
     * Текуща дата (или текуща дата и час) в MySQL формат.
     *
     * @param boolean $full TRUE - дата и час; FALSE - само дата, без час.
     * @return string
     */
    static function now($full = TRUE)
    {
        return self::verbal2mysql('', $full);
    }
    
    
    /**
     * Текуща дата (без час) в MySQL формат.
     *
     * @return string
     */
    static function today()
    {
        return self::now(false);
    }
    
    
    /**
     * Намира първият работен ден, започвайки от посочения и
     * движейки се напред (1) или назад (-1)
     */
    static function nextWorkingDay($date = NULL, $direction = 1)
    {
        while (dt::isHoliday($date)) {
            $date = dt::addDays($direction, $date);
        }
        
        return $date;
    }
    
    
    /**
     * Добавя дни към дата
     */
    static function addDays($days, $date = NULL)
    {
        if (!$date)
        $date = dt::verbal2mysql();
        $date = dt::mysql2timestamp($date);
        $date += $days * 24 * 60 * 60;
        
        return dt::timestamp2Mysql($date);
    }
    
    
    /**
     * Дали датата е събота или неделя?
     */
    static function isHoliday($date)
    {
        if (!$date) {
            $date = dt::verbal2mysql();
        }
        
        $dayOfWeek = dt::mysql2verbal($date, "w");
        
        if ($dayOfWeek == 0 || $dayOfWeek == 6) {
            return TRUE;
        }
        
        return FALSE;
    }
    
    
    /**
     * Заменя датата с "днес" или "вчера" ако тя се отнася за тези дни.
     */
    static function addVerbal($date, $sep = '-')
    {
        static $months;
        
        if(empty($months)) {
            $months = array(
                '01' => tr("Яну"),
                '02' => tr("Фев"),
                '03' => tr("Мар"),
                '04' => tr("Апр"),
                '05' => tr("Май"),
                '06' => tr("Юни"),
                '07' => tr("Юли"),
                '08' => tr("Авг"),
                '09' => tr("Сеп"),
                '10' => tr("Окт"),
                '11' => tr("Ное"),
                '12' => tr("Дек")
            );
        }
        
        $today = dt::mysql2verbal(dt::verbal2mysql(), "d{$sep}m{$sep}Y");
        $yesterday = dt::mysql2verbal(dt::addDays(-1), "d{$sep}m{$sep}Y");
        $date = str_replace($today, tr('днес'), $date);
        $date = str_replace($yesterday, tr('вчера'), $date);
        
        $today = dt::mysql2verbal(dt::verbal2mysql(), "d{$sep}m ");
        $yesterday = dt::mysql2verbal(dt::addDays(-1), "d{$sep}m ");
        $date = trim(str_replace($today, tr('днес') . " ", $date . " "));
        $date = trim(str_replace($yesterday, tr('вчера') . " ", $date . " "));
        
        $Y = dt::mysql2verbal(dt::verbal2mysql(), "Y");
        $y = dt::mysql2verbal(dt::verbal2mysql(), "y");
        
        foreach($months as $m => $verbal) {
            $date = str_replace($m . '-' . $Y, $verbal, $date);
            $date = str_replace($m . '-' . $y, $verbal, $date);
        }
        
        return $date;
    }
    
    
    /**
     * Връща timestamp в микро секунди, като рационално число
     */
    static function getMicrotime()
    {
        list($usec, $sec) = explode(" ", microtime());
        
        return ((float) $usec + (float) $sec);
    }
}