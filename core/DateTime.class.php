<?php

/**
 * Клас 'core_DateTime' ['dt'] - Функции за работа с дата и време
 *
 * @category   Experta Framework
 * @package    core
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2010 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class core_DateTime
{
    
    
    /**
     * Превръща MySQL-ска data/време UNIX timestamp
     */
    function mysql2timestamp($mysqlDate)
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
    function timestamp2Mysql($t)
    {
        return date("Y-m-d H:i:s", $t);
    }
    
    
    /**
     * Намира последния ден от месеца на една дата (като unixTimestamp)
     */
    function lastDayOfMonth($date)
    {
        $month = date("m", $date);
        $year  = date("Y", $date);
        
        return mktime(12, 59, 59, $month + 1, 0, $year);
    }
    
    
    /**
     * Превръща mySql дата във дни от началото на UNIX ерата
     */
    function mysql2UnixDays($date)
    {
        return round(dt::mysql2timestamp($date) / (3600 * 24));
    }
    
    
    /**
     * Връща разликата в дни между две дати.
     * Може да работи само с дати в UNIX ерата
     */
    function daysBetween($date1, $date2)
    {
        return dt::mysql2UnixDays($date1) - dt::mysql2UnixDays($date2);
    }
    
    
    /**
     * Превръща MySQL-ска data/време към вербална дата/време
     */
    function mysql2verbal($mysqlDate, $mask = "d-m-y H:i", $lg = "bg")
    {
        if (!$mysqlDate || $mysqlDate == '0000-00-00' || $mysqlDate == '0000-00-00 00:00:00')
        
        return FALSE;
        
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
        
        $year = date('y', strtotime($mysqlDate));
        $yearNow = date('y', time());
        
        if (($year == $yearNow) && Mode::is('screenMode', 'narrow')) {
            $mask = str_replace('-YEAR', '', $mask);
            $mask = str_replace('-year', '', $mask);
        }
        
        $mask = str_replace('YEAR', 'Y', $mask);
        $mask = str_replace('year', 'y', $mask);
        
        $verbDate = date($mask, strtotime($mysqlDate));
        
        if ($lg == "bg") {
            $verbDate = str_replace("Monday", "Понеделник", $verbDate);
            $verbDate = str_replace("Tuesday", "Вторник", $verbDate);
            $verbDate = str_replace("Wednesday", "Сряда", $verbDate);
            $verbDate = str_replace("Thursday", "Четвъртък", $verbDate);
            $verbDate = str_replace("Friday", "Петък", $verbDate);
            $verbDate = str_replace("Saturday", "Събота", $verbDate);
            $verbDate = str_replace("Sunday", "Неделя", $verbDate);
            
            $verbDate = str_replace("Mon", "Пон", $verbDate);
            $verbDate = str_replace("Tue", "Вто", $verbDate);
            $verbDate = str_replace("Wed", "Сря", $verbDate);
            $verbDate = str_replace("Thu", "Чет", $verbDate);
            $verbDate = str_replace("Fri", "Пет", $verbDate);
            $verbDate = str_replace("Sat", "Съб", $verbDate);
            $verbDate = str_replace("Sun", "Нед", $verbDate);
            
            $verbDate = str_replace("January", "Януари", $verbDate);
            $verbDate = str_replace("February", "Февруари", $verbDate);
            $verbDate = str_replace("March", "Март", $verbDate);
            $verbDate = str_replace("April", "Април", $verbDate);
            $verbDate = str_replace("May", "Май", $verbDate);
            $verbDate = str_replace("June", "Юни", $verbDate);
            $verbDate = str_replace("July", "Юли", $verbDate);
            $verbDate = str_replace("August", "Август", $verbDate);
            $verbDate = str_replace("September", "Септември", $verbDate);
            $verbDate = str_replace("October", "Октомври", $verbDate);
            $verbDate = str_replace("November", "Ноември", $verbDate);
            $verbDate = str_replace("December", "Декември", $verbDate);
            
            $verbDate = str_replace("Jan", "Яну", $verbDate);
            $verbDate = str_replace("Feb", "Фев", $verbDate);
            $verbDate = str_replace("Mar", "Мар", $verbDate);
            $verbDate = str_replace("Apr", "Апр", $verbDate);
            $verbDate = str_replace("Jun", "Юни", $verbDate);
            $verbDate = str_replace("Jul", "Юли", $verbDate);
            $verbDate = str_replace("Aug", "Авг", $verbDate);
            $verbDate = str_replace("Sep", "Сеп", $verbDate);
            $verbDate = str_replace("Oct", "Окт", $verbDate);
            $verbDate = str_replace("Nov", "Ное", $verbDate);
            $verbDate = str_replace("Dec", "Дек", $verbDate);
        }
        
        return $verbDate;
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function getMonthOptions()
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
        12 => tr("Декемрви")
        );
        
        return $months;
    }
    
    
    /**
     * Превръща вербала дата/време в България към MySQL-ска data.
     * Ако няма параметър, връща текущото време, в страната, където е часовата зона.
     */
    function verbal2mysql($verbDate = "", $full = TRUE)
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
    function nextWorkingDay($date = NULL, $direction = 1)
    {
        while (dt::isHoliday($date)) {
            $date = dt::addDays($direction, $date);
        }
        
        return $date;
    }
    
    
    /**
     * Добавя дни към дата
     */
    function addDays($days, $date = NULL)
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
    function isHoliday($date)
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
     *  Заменя датата с "днес" или "вчера" ако тя се отнася за тези дни.
     */
    function addVerbal($date, $sep = '-')
    {
        $today = dt::mysql2verbal(dt::verbal2mysql(), "d{$sep}m{$sep}Y");
        $yesterday = dt::mysql2verbal(dt::addDays(-1), "d{$sep}m{$sep}Y");
        $date = str_replace($today, tr('днес'), $date);
        $date = str_replace($yesterday, tr('вчера'), $date);
        
        $today = dt::mysql2verbal(dt::verbal2mysql(), "d{$sep}m{$sep}y");
        $yesterday = dt::mysql2verbal(dt::addDays(-1), "d{$sep}m{$sep}y");
        $date = str_replace($today, tr('днес'), $date);
        $date = str_replace($yesterday, tr('вчера'), $date);
        
        $today = dt::mysql2verbal(dt::verbal2mysql(), "d{$sep}m ");
        $yesterday = dt::mysql2verbal(dt::addDays(-1), "d{$sep}m ");
        $date = trim(str_replace($today, tr('днес') . " ", $date . " "));
        $date = trim(str_replace($yesterday, tr('вчера') . " ", $date . " "));

        $Y = dt::mysql2verbal(dt::verbal2mysql(), "Y");
        $y = dt::mysql2verbal(dt::verbal2mysql(), "y");

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

        foreach($months as $m => $w) {
            $date = str_replace($m . '-' . $Y, $w, $date);
            $date = str_replace($m . '-' . $y, $w, $date);
        }



        
        return $date;
    }
    
    
    /**
     * Връща timestamp в микросекунди, като рационално число
     */
    function getMicrotime()
    {
        list($usec, $sec) = explode(" ", microtime());
        
        return ((float) $usec + (float) $sec);
    }
}