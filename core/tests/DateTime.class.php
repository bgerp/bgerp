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
class core_tests_DateTime extends unit_Class
{
    
    // Дните от седмицата, съкратени
    public static $weekDays = array('Пон', 'Вто', 'Сря', 'Чет', 'Пет', 'Съб', 'Нед');


    // Имената на месеците на български
    public static $months = array('Януари', 'Февруари', 'Март', 'Април', 'Май', 'Юни',
            'Юли', 'Август', 'Септември', 'Октомври', 'Ноември', 'Декември');

    // Кратки имена на месеците на български
    public static $monthsShort = array('Яну', 'Фев', 'Мар', 'Апр', 'Май', 'Юни',
        'Юли', 'Авг', 'Сеп', 'Окт', 'Ное', 'Дек');
    
    // Имената на месеците на английски
    public static $monthsEn = array('January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December');
    
    // Кратки имена на месеците на английски
    public static $monthsShortEn = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');

     
    /**
     * Връща посочения месец в посочения формат и език
     * static function getMonth($m, $format = 'm', $lg =  NULL)
     *
     */
    public function test_GetMonth()
    {
        ut::expectEqual(dt::getMonth(1, 'M', 'bg'), 'Яну');
        ut::expectEqual(dt::getMonth(12, 'M', 'en'), 'Dec');
        ut::expectEqual(dt::getMonth(6, 'M', '??'), 'Jun');
    }


    /**
     * Превръща MySQL-ска data/време UNIX timestamp
     */
    public static function test_Mysql2Timestamp()
    {
        ut::expectEqual(dt::mysql2timestamp('1990-02-28'), strtotime('28-02-1990'));
    }
    
    
    /**
     * Връща последния ден за зададения месец
     */
    public static function test_GetLastDayOfMonth()
    {
        ut::expectEqual(dt::getLastDayOfMonth('2008-02-15'), '2008-02-29');
        ut::expectEqual(dt::getLastDayOfMonth('2008-02-29', 1), '2008-03-31');
        ut::expectEqual(dt::getLastDayOfMonth('2008-02-29', -1), '2008-01-31');
        ut::expectEqual(dt::getLastDayOfMonth('2013-08-31', -1), '2013-07-31');
        ut::expectEqual(dt::getLastDayOfMonth('2013-03-31', -1), '2013-02-28');
    }

    
    /**
     * Намира първия или последния именован седмичен ден от посочения месец/година
     *
     * @param $month int
     * @param $year int
     * @param $wDay string например 'first-monday', 'last-friday', ....
     *
     * @return string mysql форматирана дата, напр. '2011-02-23'
     */
    public static function test_FirstDayOfMonthTms()
    {
        ut::expectEqual(date('Y-m-d', dt::firstDayOfMonthTms(2, 2012, 'last-friday')), '2012-02-24');
        ut::expectEqual(date('Y-m-d', dt::firstDayOfMonthTms(12, 2012, 'first-friday')), '2012-12-07');
    }


    public static function firstDayOfMonthTms($month, $year, $wDay)
    {
        list($base, $dayName) = explode('-', $wDay);
        
        expect(in_array($base, array('first', 'last')));
        
        $weekDayNames = array(
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            'sunday' => 7);

        expect($dayNumb = $weekDayNames[$dayName]);
        
        for ($i = 1; $i <= 7; $i++) {
            if ($base == 'first') {
                $curDay = mktime(0, 0, 0, $month, $i, $year);
            } else {
                $curDay = mktime(12, 59, 59, $month + 1, 1 - $i, $year);
            }

            $curWeekDay = date('w', $curDay);

            if ($curWeekDay == $dayNumb) {
                $res = $curDay;
                break;
            }
        }

        expect($res);

        return $res;
    }

    
    /**
     * Превръща mySql дата във дни от началото на UNIX ерата
     */
    public static function mysql2UnixDays($date)
    {
        return round(dt::mysql2timestamp($date) / (3600 * 24));
    }
    
    
    /**
     * Връща разликата в дни между две дати.
     * Може да работи само с дати в UNIX ерата
     */
    public static function daysBetween($date1, $date2)
    {
        return dt::mysql2UnixDays($date1) - dt::mysql2UnixDays($date2);
    }
    
    
    /**
     * Превръща MySQL-ска data/време към вербална дата/време
     */
    public static function mysql2verbal($mysqlDate, $mask = 'd-m-y H:i', $lg = null)
    {
        if ($mysqlDate === null) {
            $mysqlDate = self::verbal2mysql();
        }

        if (!$mysqlDate || $mysqlDate == '0000-00-00' || $mysqlDate == '0000-00-00 00:00:00') {
            
            return false;
        }
        
        if (!$lg) {
            $lg = core_Lg::getCurrent();
        }
        
        $mysqlDate = trim(strtolower($mysqlDate));
        $mysqlDate = str_replace('  ', ' ', $mysqlDate);
        $mysqlDate = str_replace("''", ':', $mysqlDate);
        $mysqlDate = str_replace("'", ':', $mysqlDate);
        
        $time = strtotime($mysqlDate);
        
        $year = date('y', $time);
        $yearNow = date('y', time());
        
        if ($mask == 'smartTime') {
            $addColor = true;
            
            if ($year != $yearNow) {
                if (Mode::is('screenMode', 'narrow')) {
                    $mask = 'd-m-y H:i';
                } else {
                    $mask = 'd-m-Y H:i';
                }
            } else {
                $smartMode = true;
                $mask = 'd-M H:i';
                $today = dt::mysql2verbal(dt::verbal2mysql(), 'd-M', 'en');
                $yesterday = dt::mysql2verbal(dt::addDays(-1), 'd-M', 'en');
            }
        }
        
        if (($year == $yearNow)) {
            $mask = str_replace('-YEAR', '', $mask);
            $mask = str_replace('-year', '', $mask);
        }
        
        $mask = str_replace('YEAR', 'Y', $mask);
        $mask = str_replace('year', 'y', $mask);
        
        $verbDate = date($mask, $time);
        
        if ($smartMode) {
            $fromArr = array($today, $yesterday);
            
            if ($lg == 'bg') {
                $toArr = array('Днес', 'Вчера');
            } else {
                $toArr = array('Today', 'Yesterday');
            }
            
            $verbDate = str_replace($fromArr, $toArr, $verbDate);
        }
        
        $weekDaysLongEn = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
        $weekDaysShortEn = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');
        $montsLongEn = array('January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December');
        $montsShortEn = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
        
        $weekDaysLongBg = array('Понеделник', 'Вторник', 'Сряда', 'Четвъртък', 'Петък', 'Събота', 'Неделя');
        $weekDaysShortBg = array('Пон', 'Вто', 'Сря', 'Чет', 'Пет', 'Съб', 'Нед');
        $montsLongBg = array('Януари', 'Февруари', 'Март', 'Април', 'Май', 'Юни',
            'Юли', 'Август', 'Септември', 'Октомври', 'Ноември', 'Декември');
        $montsShortBg = array('Яну', 'Фев', 'Мар', 'Апр', 'Май', 'Юни', 'Юли', 'Авг', 'Сеп', 'Окт', 'Ное', 'Дек');
        
        if ($lg == 'bg') {
            $verbDate = str_ireplace($weekDaysLongEn, $weekDaysLongBg, $verbDate);
            $verbDate = str_ireplace($weekDaysShortEn, $weekDaysShortBg, $verbDate);
            $verbDate = str_ireplace($montsLongEn, $montsLongBg, $verbDate);
            $verbDate = str_ireplace($montsShortEn, $montsShortBg, $verbDate);
        }
        
        if ($addColor) {
            $dist = time() - $time;
            
            $color = static::getColorByTime($dist);
          
            $title = dt::mysql2verbal($mysqlDate, 'd-M-Y H:i (l)');
            $title = "  title='{$title}'";
            
            $verbDate = "<font color='#${color}' ${title}>{$verbDate}</font>";
        }
        
        return $verbDate;
    }


    /**
     * Връща релативното име на деня, спрямо текущото време
     *
     * @param $date     mixed   mysql дата или timestamp
     * @param $format   string  'mysql' или 'timestamp'
     * @param $lg       string  двубуквен код на език
     *
     * @return string ('Днес', 'Tommorow' ..., или NULL)
     */
    public static function getRelativeDayName($date, $format = 'mysql', $lg = null)
    {
        // Ако не е зададен език, избираме текущия
        if (!$lg) {
            $lg = core_Lg::getCurrent();
        }
        
        // Според езика, конструираме масивите за релативни дати
        if ($lg == 'bg') {
            $relNames = array(
                -2 => 'Завчера',
                -1 => 'Вчера',
                 0 => 'Днес',
                 1 => 'Утре',
                 2 => 'Вдругиден',
                );
        } else {
            $relNames = array(
                -2 => 'Ereyesterday',
                -1 => 'Yesterday',
                 0 => 'Today',
                 1 => 'Tommorow',
                 2 => 'Overmorrow',
                );
        }

        if ($format == 'mysql') {
            $date = explode(' ', $date);
            $date = $date[0];
        } else {
            expect($format == 'timestamp');
            $date = date('Y-m-d', $date);
        }

        for ($i = -2; $i <= 2; $i++) {
            if (date('Y-m-d', time() + $i * 24 * 60 * 60) == $date) {
                
                return $relNames[$i];
            }
        }
    }
    

    /**
     * Връща цвят, според разтояние в секунди
     */
    public static function getColorByTime($dist)
    {
        if ($dist < 0) {
            $dist = round(pow(log(-$dist, 1.85) - log(20, 1.85), 1.85));
            $g = round(max(4, 8 - $dist / 50));
            $color = "0{$g}0";
        } else {
            if ($dist < 20) {
                $dist = 20;
            }
            
            $dist = round(pow(log($dist, 1.85) - log(20, 1.85), 1.85));
            
            if ($dist <= 255) {
                $g = 255 - $dist;
                $b = $dist;
                $r = $b / 3;
                $b = $b - $r;
            } elseif ($dist <= 511) {
                $b = 256 - round($dist / 2);
                $r = $b / 3;
                $g = 0;
                $b = $b - $r;
            } else {
                $color = '000000';
            }
            
            $r = $r / 1.2;
            $b = $b / 1.2;
            $g = $g / 1.5;
            
            $g1 = $g;
            $g = $r;
            $r = $g1;
            
            if (!$color) {
                $r = dechex($r < 0 ? 0 : ($r > 255 ? 255 : $r));
                $g = dechex($g < 0 ? 0 : ($g > 255 ? 255 : $g));
                $b = dechex($b < 0 ? 0 : ($b > 255 ? 255 : $b));
                
                $color = (strlen($r) < 2 ? '0' : '') . $r;
                $color .= (strlen($g) < 2 ? '0' : '') . $g;
                $color .= (strlen($b) < 2 ? '0' : '') . $b;
            }
        }

        return $color;
    }


    /**
     * Превръща вербална дата/време вкъм MySQL-ска data.
     * Ако няма параметър, връща текущото време, в страната, където е часовата зона.
     */
    public static function verbal2mysql($verbDate = '', $full = true)
    {
        if ($verbDate != '') {
            $verbDate = trim(strtolower($verbDate));
            
            $verbDate = str_replace('.', '-', $verbDate);
            $verbDate = str_replace('/', '-', $verbDate);
            $verbDate = str_replace('\\', '-', $verbDate);
            $verbDate = str_replace(',', '-', $verbDate);
            $verbDate = str_replace(';', '-', $verbDate);
            $verbDate = str_replace('  ', ' ', $verbDate);
            $verbDate = str_replace('  ', ' ', $verbDate);
            $verbDate = str_replace("''", ':', $verbDate);
            $verbDate = str_replace("'", ':', $verbDate);
            
            $s = '^([0-9]{1,2})-([0-9]{1,2})-([0-9]{2,4})^';
            preg_match($s, $verbDate, $out);
            
            if (count($out) > 0) {
                $day = $out[1];
                $month = $out[2];
                $year = $out[3];
            } else {
                $s = '^([0-9]{1,2})-([0-9]{1,2})^';
                preg_match($s, $verbDate, $out);
                
                if (count($out) > 0) {
                    $day = $out[1];
                    $month = $out[2];
                    $year = date('Y', time());
                } else {
                    
                    return false;
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
            
            $s = '^([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})^';
            preg_match($s, $verbDate, $out);
            
            if (count($out) > 0) {
                $hours = $out[1];
                $minutes = $out[2];
                $seconds = $out[3];
            } else {
                $s = '^([0-9]{1,2}):([0-9]{1,2})^';
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
            
            if ($year > 70 && $year < 100) {
                $year += 1900;
            }
            
            if ($year < 30) {
                $year += 2000;
            }
            
            if ($full) {
                //$date1 = date ("Y-m-d H:i:s", mktime ($hours, $minutes,$seconds,$month, $day, $year));
                $date2 = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hours, $minutes, $seconds);
            } else {
                //$date1 = date ("Y-m-d", mktime ($hours,$minutes,$seconds,$month, $day, $year));
                $date2 = sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
            
            return $date2;
        }
        if ($full) {
            
            return date('Y-m-d H:i:s', time());
        }

        return date('Y-m-d', time());
    }
    
    
    /**
     * Текуща дата (или текуща дата и час) в MySQL формат.
     *
     * @param  boolean $full TRUE - дата и час; FALSE - само дата, без час.
     * @return string
     */
    public static function now($full = true)
    {
        return self::verbal2mysql('', $full);
    }
    
    
    /**
     * Текуща дата (без час) в MySQL формат.
     *
     * @return string
     */
    public static function today()
    {
        return self::now(false);
    }


    
    
    /**
     * Намира първият работен ден, започвайки от посочения и
     * движейки се напред (1) или назад (-1)
     */
    public static function nextWorkingDay($date = null, $direction = 1)
    {
        while (dt::isHoliday($date)) {
            $date = dt::addDays($direction, $date);
        }
        
        return $date;
    }
    
    
    /**
     * Добавя дни към дата
     */
    public static function addDays($days, $date = null)
    {
        if (!$date) {
            $date = dt::verbal2mysql();
        }
        
        $date = dt::mysql2timestamp($date);
        $date += $days * 24 * 60 * 60;
        
        return dt::timestamp2Mysql($date);
    }
    
    
    /**
     * Дали датата е събота или неделя?
     */
    public static function isHoliday($date)
    {
        if (!$date) {
            $date = dt::verbal2mysql();
        }
        
        $dayOfWeek = dt::mysql2verbal($date, 'w');
        
        if ($dayOfWeek == 0 || $dayOfWeek == 6) {
            
            return true;
        }
        
        return false;
    }
    
    
    /**
     * Заменя датата с "днес" или "вчера" ако тя се отнася за тези дни.
     */
    public static function addVerbal($date, $sep = '-')
    {
        static $months;
        
        if (empty($months)) {
            $months = array(
                '01' => tr('Яну'),
                '02' => tr('Фев'),
                '03' => tr('Мар'),
                '04' => tr('Апр'),
                '05' => tr('Май'),
                '06' => tr('Юни'),
                '07' => tr('Юли'),
                '08' => tr('Авг'),
                '09' => tr('Сеп'),
                '10' => tr('Окт'),
                '11' => tr('Ное'),
                '12' => tr('Дек')
            );
        }
        
        $today = dt::mysql2verbal(dt::verbal2mysql(), "d{$sep}m{$sep}Y");
        $yesterday = dt::mysql2verbal(dt::addDays(-1), "d{$sep}m{$sep}Y");
        $date = str_replace($today, tr('днес'), $date);
        $date = str_replace($yesterday, tr('вчера'), $date);
        
        $today = dt::mysql2verbal(dt::verbal2mysql(), "d{$sep}m ");
        $yesterday = dt::mysql2verbal(dt::addDays(-1), "d{$sep}m ");
        $date = trim(str_replace($today, tr('днес') . ' ', $date . ' '));
        $date = trim(str_replace($yesterday, tr('вчера') . ' ', $date . ' '));
        
        $Y = dt::mysql2verbal(dt::verbal2mysql(), 'Y');
        $y = dt::mysql2verbal(dt::verbal2mysql(), 'y');
        
        foreach ($months as $m => $verbal) {
            $date = str_replace($m . '-' . $Y, $verbal, $date);
            $date = str_replace($m . '-' . $y, $verbal, $date);
        }
        
        return $date;
    }
    
    
    /**
     * Връща timestamp в микро секунди, като рационално число
     */
    public static function getMicrotime()
    {
        list($usec, $sec) = explode(' ', microtime());
        
        return ((float) $usec + (float) $sec);
    }


    /**
     * Връща датата на православния Великден за указаната година
     */
    public static function getOrthodoxEasterTms($year)
    {
        $r1 = $year % 19;
        $r2 = $year % 4;
        $r3 = $year % 7;
        $ra = 19 * $r1 + 16;
        $r4 = $ra % 30;
        $rb = 2 * $r2 + 4 * $r3 + 6 * $r4;
        $r5 = $rb % 7;
        $rc = $r4 + $r5;
        
        // Православния Великден за тази година се пада $rc дни след 3-ти Април
        return strtotime("3 April ${year} + ${rc} days");
    }
    

    /**
     * Връща датата на западния Великден за указаната година
     */
    public static function getEasterTms($year)
    {
        return strtotime("{$year}-03-21 +".easter_days($year).' days');
    }
}
