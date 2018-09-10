<?php


/**
 * Клас 'core_DateTime' ['dt'] - Функции за работа с дата и време
 *
 *
 * @category  ef
 * @package   core
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class core_DateTime
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
    
    // Кратки имена на дните от седмицата на английски
    public static $weekDaysShortEn = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat',
        'Sun');
    
    
    /**
     * Колко секунди има средно в един месец
     */
    const SECONDS_IN_MONTH = 2629746;
    
    
    /**
     * Връща посочения месец в посочения формат и език
     */
    public static function getMonth($m, $format = 'm', $lg = null)
    {
        if (!$lg) {
            $lg = core_Lg::getCurrent();
        }
        
        if ($format == 'FM') {
            $format = Mode::is('screenMode', 'narrow') ? 'M' : 'F';
        }
        
        switch ($format) {
            case 'F':
                if ($lg == 'bg') {
                    $res = self::$months[$m - 1];
                } elseif ($lg == 'en') {
                    $res = self::$monthsEn[$m - 1];
                } else {
                    $res = tr(self::$monthsEn[$m - 1]);
                }
                break;
            
            case 'M':
                if ($lg == 'bg') {
                    $res = self::$monthsShort[$m - 1];
                } elseif ($lg == 'en') {
                    $res = self::$monthsShortEn[$m - 1];
                } else {
                    $res = tr(self::$monthsShortEn[$m - 1]);
                }
                break;
            
            case 'm':
                $res = str_pad($m, 2, '0', STR_PAD_LEFT);
                break;
        }
        
        return $res;
    }
    
    
    /**
     * Връща месеците на годината, като опции
     */
    public static function getMonthOptions($format = 'm', $lg = null)
    {
        $months = array();
        
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = self::getMonth($i, $format, $lg);
        }
        
        return $months;
    }
    
    
    /**
     * Превръща MySQL-ска data/време UNIX timestamp
     */
    public static function mysql2timestamp($mysqlDate = null)
    {
        if (!$mysqlDate) {
            $mysqlDate = static::now();
        }
        
        return strtotime($mysqlDate);
    }
    
    
    /**
     * Превръща UNIX timestamp в MySQL-ска дата
     */
    public static function timestamp2Mysql($t)
    {
        return date('Y-m-d H:i:s', $t);
    }
    
    
    /**
     * Връща последния ден за зададения месец
     */
    public static function getLastDayOfMonth($date = null, $monthOffset = 0)
    {
        if (!$date) {
            $date = dt::verbal2mysql();
        }
        
        $date = dt::mysql2verbal($date, 'Y-m-1', null, false);
        
        $monthOffset = (int) $monthOffset;
        
        if ($monthOffset > 0) {
            $time = strtotime("{$date} + {$monthOffset} month");
        } elseif ($monthOffset < 0) {
            $time = strtotime("{$date} {$monthOffset} month");
        } else {
            $time = strtotime("{$date}");
        }
        
        $res = date('Y-m-t', $time);
        
        return $res;
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
    public static function firstDayOfMonthTms($month, $year, $wDay)
    {
        list($base, $dayName) = explode('-', $wDay);
        
        expect(in_array($base, array('first', 'second', 'third', 'penultimate', 'last')));
        
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
            } elseif ($base == 'second') {
                $curDay = mktime(0, 0, 0, $month, $i + 7, $year);
            } elseif ($base == 'third') {
                $curDay = mktime(0, 0, 0, $month, $i + 14, $year);
            } elseif ($base == 'penultimate') {
                $curDay = mktime(12, 59, 59, $month + 1, 1 - $i - 7, $year);
            } else {
                $curDay = mktime(12, 59, 59, $month + 1, 1 - $i, $year);
            }
            
            $curWeekDay = date('N', $curDay);
            
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
     * Връща разликата в секунди между две дати.
     * Може да работи само с дати в UNIX ерата
     */
    public static function secsBetween($date1, $date2)
    {
        return dt::mysql2timestamp($date1) - dt::mysql2timestamp($date2);
    }
    
    
    /**
     * Връща разликата в секунди от timezone на сървъра и на потребителя
     */
    public static function getTimezoneDiff()
    {
        $timeZoneDiff = Mode::get('timezoneDiff');
        
        return $timeZoneDiff;
    }
    
    
    /**
     * Връща MySQL времето с TIMEOFFSET;
     *
     * @param datetime $mysqlDate
     *
     * @return datetime
     */
    public static function getDateWithTimeoffeset($mysqlDate)
    {
        $conf = core_Packs::getConfig('core');
        
        if ($conf->EF_DATE_USE_TIMEOFFSET != 'yes') {
            
            return $mysqlDate;
        }
        
        $timeZoneDiff = self::getTimezoneDiff();
        
        if (!$timeZoneDiff) {
            
            return $mysqlDate;
        }
        
        $time = strtotime($mysqlDate);
        
        $time -= $timeZoneDiff;
        
        $mysqlDate = self::timestamp2Mysql($time);
        
        return $mysqlDate;
    }
    
    
    /**
     * Превръща MySQL-ска data/време към вербална дата/време
     */
    public static function mysql2verbal($mysqlDate, $mask = 'd.m.y H:i', $lg = null, $autoTimeZone = null, $callRecursive = true)
    {
        $noTime = false;
        
        if ($mask == 'smartDate') {
            $mask = 'smartTime';
            $noTime = true;
        }
        
        // Опцията "относително време" да не работи в абсолутен или печатен режим
        if (Mode::is('text', 'xhtml') || Mode::is('printing') || Mode::is('text', 'plain') || Mode::is('pdf')) {
            if ($mask == 'smartTime') {
                $mask = 'd.m.y H:i';
            }
        }
        
        // Ако изришно не е зададено да е TRUE, R (за отместване във времето) ще се добавя за смарт време
        if (!isset($autoTimeZone)) {
            if ($mask == 'smartTime') {
                $autoTimeZone = true;
            }
        }
        
        $origMask = $mask;
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
        
        $conf = core_Packs::getConfig('core');
        
        $timeZoneDiff = 0;
        if ($conf->EF_DATE_USE_TIMEOFFSET == 'yes' && $autoTimeZone) {
            $timeZoneDiff = self::getTimezoneDiff();
            $time -= $timeZoneDiff;
        }
        
        $year = date('y', $time);
        $yearNow = date('y', time());
        
        if ($mask == 'smartTime') {
            $addColor = true;
            
            if ($year != $yearNow) {
                if (Mode::is('screenMode', 'narrow')) {
                    $mask = 'd.m.y H:i';
                } else {
                    $mask = 'd.m.Y H:i';
                }
            } else {
                $smartMode = true;
                $mask = 'd M H:i';
                $today = dt::mysql2verbal(dt::verbal2mysql(), 'd M', 'en', false, false);
                $yesterday = dt::mysql2verbal(dt::addDays(-1), 'd M', 'en', false, false);
                $tomorrow = dt::mysql2verbal(dt::addDays(1), 'd M', 'en', false, false);
            }
        }
        
        if ($noTime) {
            $mask = str_replace(' H:i', '', $mask);
        }
        
        if (($year == $yearNow)) {
            $mask = str_replace('-YEAR', '', $mask);
            $mask = str_replace('-year', '', $mask);
            $mask = str_replace('.YEAR', '', $mask);
            $mask = str_replace('.year', '', $mask);
        }
        
        $mask = str_replace('YEAR', 'Y', $mask);
        $mask = str_replace('year', 'y', $mask);
        
        $verbDate = date($mask, $time);
        
        if ($smartMode) {
            $fromArr = array($today, $yesterday, $tomorrow);
            
            if ($lg == 'bg') {
                $toArr = array('Днес', 'Вчера', 'Утре');
            } else {
                $toArr = array('Today', 'Yesterday', 'Tomorrow');
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
        
        if ($addColor && $callRecursive) {
            $color = static::getColorByTime(self::timestamp2Mysql($time));
            
            $title = dt::mysql2verbal($mysqlDate, 'd.m.Y H:i:s (l)', $lg, false, false);
            $title = "  title='{$title}'";
            
            $verbDate = "<span class='timeSpan' style=\"color:#{$color}\" ${title}>{$verbDate}</span>";
        }
        
        if ($callRecursive && $timeZoneDiff &&
            !Mode::is('text', 'plain') && !Mode::is('text', 'xhtml') && !Mode::is('printing')) {
            $origVerbDate = self::mysql2verbal($mysqlDate, $origMask, $lg, false, false);
            
            if ($origVerbDate) {
                if (!$color) {
                    $color = '444';
                }
                $verbDate .= "<span class='timeSpan' style='color: #{$color};' title='{$origVerbDate}'>®</span>";
            }
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
    public static function getColorByTime($datetime, $baseDatetime = null)
    {
        if (!$baseDatetime) {
            $baseDatetime = self::now();
        }
        
        $dist = strtotime($baseDatetime) - strtotime($datetime);
        
        if ($dist < 0) {
            $dist = 1 - $dist / (24 * 60 * 60);
            $g = round(max(4, 9 - $dist * $dist));
            $color = '0' . dechex($g) . '0';
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
            
            for ($i = 1; $i <= 12; $i++) {
                $verbDate = str_replace(mb_strtolower(self::$months[$i - 1]), "-{$i}-", $verbDate);
                $verbDate = str_replace(mb_strtolower(self::$monthsShort[$i - 1]), "-{$i}-", $verbDate);
                $verbDate = str_replace(mb_strtolower(self::$monthsEn[$i - 1]), "-{$i}-", $verbDate);
                $verbDate = str_replace(mb_strtolower(self::$monthsShortEn[$i - 1]), "-{$i}-", $verbDate);
            }
            
            $verbDate = trim($verbDate, '-');
            
            $verbDate = str_replace('.', '-', $verbDate);
            $verbDate = str_replace('/', '-', $verbDate);
            $verbDate = str_replace('\\', '-', $verbDate);
            $verbDate = str_replace('  ', ' ', $verbDate);
            $verbDate = str_replace('  ', ' ', $verbDate);
            $verbDate = str_replace('  ', ' ', $verbDate);
            $verbDate = str_replace('- ', '-', $verbDate);
            $verbDate = str_replace(' -', '-', $verbDate);
            $verbDate = str_replace(' -', '-', $verbDate);
            $verbDate = str_replace('--', '-', $verbDate);
            
            $verbDate = str_replace("''", ':', $verbDate);
            $verbDate = str_replace("'", ':', $verbDate);
            
            $dPtr = '/^(0?[1-9]|1[0-9]|2[0-9]|3[0-1])-(0?[1-9]|1[0-2]|[1-9])(?:-([0-2][0-9][0-9][0-9]|[0-9][0-9]){0,1}){0,1}';
            $tPtr = '(?: ?((0?[0-9]|1[0-9]|2[0-3]):([0-5][0-9])(?:\\:([0-5][0-9])){0,1}(?: ?(pm|am)){0,1})){0,1}$/';
            
            if (preg_match($dPtr . $tPtr, $verbDate, $out)) {
                $day = $out[1];
                $month = $out[2];
                $year = $out[3];
                
                $hours = $out[5];
                $minutes = $out[6];
                $seconds = $out[7];
                $mode = $out[8];
                $found = true;
            } else {
                $dPtr = '/^([0-2][0-9][0-9][0-9]|[0-9][0-9])-(0?[1-9]|1[0-2]|[1-9])-(0?[1-9]|1[0-9]|2[0-9]|3[0-1])';
                if (preg_match($dPtr . $tPtr, $verbDate, $out)) {
                    $year = $out[1];
                    $month = $out[2];
                    $day = $out[3];
                    
                    $hours = $out[5];
                    $minutes = $out[6];
                    $seconds = $out[7];
                    $mode = $out[8];
                    $found = true;
                }
            }
            
            // Ако сме намерили дата/време, правим малко обработки на числата
            if ($found) {
                
                // Ако нямаме година, то това е текущата година
                if (!$year) {
                    $year = date('Y');
                }
                
                // Ако годината е под 30, то приемаме, че е 20??, ако е под 100, приемаме, че е 19??
                if (strlen($year) == 2) {
                    if ($year <= 30) {
                        $year = 2000 + $year;
                    } elseif ($year < 100) {
                        $year = 1900 + $year;
                    }
                }
                
                // Ако денят е по-голям от последния ден за месеца, то денят е равен на последния ден за месеца
                $ldm = date('t', strtotime(sprintf('%04d-%02d-01', $year, $month, $day)));
                
                if ($day > $ldm) {
                    $day = $ldm;
                }
                
                // Ако Mode == 'pm', то към часовете прибавяме 12
                if ($mode == 'pm') {
                    $hours += 12;
                }
                
                $date = sprintf($full ? '%04d-%02d-%02d %02d:%02d:%02d' : '%04d-%02d-%02d', $year, $month, $day, $hours, $minutes, $seconds);
            }
        } else {
            $date = date($full ? 'Y-m-d H:i:s' : 'Y-m-d', time());
        }
        
        return $date;
    }
    
    
    /**
     * Текуща дата (или текуща дата и час) в MySQL формат.
     *
     * @param bool $full TRUE - дата и час; FALSE - само дата, без час.
     *
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
        if (!$date) {
            $date = dt::addDays($direction);
        }
        
        expect($direction);
        
        while (dt::isHoliday($date)) {
            $date = dt::addDays($direction, $date);
            
            if ($maxCnt++ > 10000) break;
        }
        
        return $date;
    }
    
    
    /**
     * Добавя дни към дата
     */
    public static function addDays($days, $date = null, $full = true)
    {
        if (!$date) {
            $date = dt::verbal2mysql();
        }
        
        list($d, $t) = explode(' ', $date);
        
        if (!$t) {
            $t = '00:00:00';
        }
        
        $d = dt::mysql2timestamp($d . ' 12:00:00');
        $d += $days * 24 * 60 * 60;
        
        $res = dt::timestamp2Mysql($d);
        
        list($d1, $t1) = explode(' ', $res);
        
        $res = $d1 . ' ' . $t;
        
        if (!$full) {
            list($res, ) = explode(' ', $res);
        }
        
        return $res;
    }
    
    
    /**
     * Добавя секунди към датата
     */
    public static function addSecs($secs, $date = null, $full = true)
    {
        if (!$date) {
            $date = dt::verbal2mysql();
        }
        
        // Ако добавяме точно количество дни
        if ($secs % (24 * 60 * 60) == 0) {
            
            return self::addDays($secs / (24 * 60 * 60), $date, $full);
        }
        
        // Оставащи секунди до точен брой месеци
        $resDays = $secs % core_DateTime::SECONDS_IN_MONTH;
        
        // Ако добавяме точно количество месеци
        if ($resDays % (24 * 60 * 60) == 0) {
            
            return self::addDays(floor($resDays / (24 * 60 * 60)), self::addMonths(floor($secs / core_DateTime::SECONDS_IN_MONTH), $date, $full), $full);
        }
        
        $date = dt::mysql2timestamp($date);
        $date += $secs;
        
        return dt::timestamp2Mysql($date);
    }
    
    
    /**
     * Премахва секунди от датата
     */
    public static function subtractSecs($secs, $date = null)
    {
        $secs *= -1;
        
        return static::addSecs($secs, $date);
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
    
    
    /**
     * Ф-я добавяща/изваждаща месеци към дадена дата
     *
     * @param int   $num  - брой месеци, които ще се вадят/добавят
     * @param mixed $date - дата или NULL ако е текущата
     */
    public static function addMonths($num, $date = null, $full = true)
    {
        if (!$date) {
            $date = dt::now();
        }
        
        list($d, $t) = explode(' ', $date);
        
        if (!$t) {
            $t = '00:00:00';
        }
        
        list($y, $m, $day) = explode('-', $d);
        
        $num = (int) $num;
        if ($num >= 0) {
            $num = '+' . $num;
        }
        
        $mStart = "{$y}-{$m}-01";
        
        $newDateStamp = strtotime("{$num} months", dt::mysql2timestamp($mStart));
        
        $lastDay = date('t', $newDateStamp);
        
        if ($day > $lastDay) {
            $day = $lastDay;
        }
        
        $newMonth = dt::timestamp2Mysql($newDateStamp);
        
        list($y, $m) = explode('-', $newMonth);
        
        $res = "{$y}-{$m}-{$day} {$t}";
        
        if (!$full) {
            list($res, ) = explode(' ', $res);
        }
        
        return $res;
    }
    
    
    /**
     * Превръща секунди във форматирано време за часове 72:45:56
     */
    public static function sec2hours($sec, $dev = ':')
    {
        return sprintf('%02d%s%02d%s%02d', floor($sec / 3600), $dev, ($sec / 60) % 60, $dev, $sec % 60);
    }
    
    
    /**
     * Дали дадена дата е във формата на подадената маска
     *
     * @param string $date - дата
     * @param string $mask - маска
     *
     * @return bool
     */
    public static function checkByMask($date, $mask)
    {
        // Карта
        $map = array();
        $map['m'] = "(?'month'[0-9]{2})";
        $map['d'] = "(?'day'[0-9]{2})";
        $map['y'] = "(?'yearShort'[0-9]{2})";
        $map['Y'] = "(?'year'[0-9]{4})";
        
        // Генерираме регулярен израз спрямо картата
        $expr = preg_quote($mask, '/');
        $expr = strtr($expr, $map);
        
        // Проверяваме дали датата отговаря на формата
        if (!preg_match("/^{$expr}$/", $date, $matches)) {
            
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Опитва се да обърне подаден стринг с дадена маска, в mysql-ски формат дата
     *
     * @param string $string - стринг
     * @param string $mask   - маска (e.g dd.mm.yyyy)
     *
     * @return string|FALSE - mysql датата в формат 'Y-m-d', или FALSE ако има проблем
     */
    public static function getMysqlFromMask($string, $mask)
    {
        // Подготовка на времевия обект
        $timeFormat = DateTime::createFromFormat($mask, $string);
        
        // Ако успешно е инстанциран датата се обръща във mysql-ски формат
        if (is_object($timeFormat)) {
            
            return $timeFormat->format('Y-m-d');
        }
        
        // В краен случай се връща FALSE
        return false;
    }
    
    
    /**
     * Връща масив с последните $n месеца
     *
     * @param int      $n    - колко месеца на зад (включително текущия)
     * @param datetime $date - дата, NULL за текущата
     *
     * @return array $months - масив с месеците
     */
    public static function getRecentMonths($n, $date = null)
    {
        $months = array();
        $date = new DateTime($date);
        foreach (range(1, $n) as $i) {
            if ($i != 1) {
                $date->modify('last month');
            }
            
            $thisMonth = $date->format('Y-m-01');
            $months[$thisMonth] = dt::mysql2verbal($thisMonth, 'M Y');
        }
        
        return $months;
    }
    
    
    /**
     * Намира месеците между две дати
     *
     * @param date      $startDate - начална дата
     * @param date|NULL $endDate   - дата, NULL за текущата
     *
     * @return string $mask      - маска
     * @return array  $months     - масив с месеци
     */
    public static function getMonthsBetween($startDate, $endDate = null, $mask = 'M Y')
    {
        $endDate = isset($endDate) ? $endDate : dt::today();
        $endDate = new DateTime($endDate);
        $endDate = $endDate->format('Y-m-01');
        
        $months = array();
        $date = new DateTime($startDate);
        $nextDate = $date->format('Y-m-01');
        $months[$nextDate] = dt::mysql2verbal($nextDate, $mask);
        
        while ($nextDate < $endDate) {
            $date->modify('next month');
            $nextDate = $date->format('Y-m-01');
            $months[$nextDate] = dt::mysql2verbal($nextDate, $mask);
        }
        
        return $months;
    }
}
