<?php 

/**
 * Работни графици
 *
 *
 * @category  bgerp
 * @package   hr
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class hr_Schedules extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Работни графици';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Работен график';
    
    
    /**
     * Страница от менюто
     */
    public $pageMenu = 'Персонал';
    
    
    /**
     * @todo Чака за документация...
     */
    public $details = 'hr_ScheduleDetails,Calendar=hr_Schedules';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, hr_Wrapper,  plg_Printing, plg_TreeObject';
    
    
    /**
     * Единична икона
     */
    public $singleIcon = 'img/16/timespan.png';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,hrMaster';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,hrMaster';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,hrMaster';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'ceo,hrMaster';
    
    
    /**
     * Кой може да го изтрие?
     *
     */
    public $canDelete = 'ceo,hrMaster';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,name';

     
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование, width=100%,mandatory');
        $this->FLD('nonWorking', 'set(holiday=Празниците,non-working=Неработните,saturday=Съботите,sunday=Неделите,working=Работните)', 'caption=Изключване на');
        $this->FLD('parentId', 'key(mvc=hr_Schedules,select=name,allowEmpty)', 'caption=Наследява,autohide');

        $this->FLD('sysId', 'varchar', 'caption=Служебно ид,input=none,autohide');
        $this->FNC('makeDescendantsFeatures', 'enum(yes=Да,no=Не)', 'caption=Наследниците да бъдат ли счетоводни признаци?->Избор,notNull,value=yes,input=none');
        $this->setDbUnique('name');
    }

    /**
     * Връща обект от тип core_Intervals в който са включени само работните периоди за дадения работен цикъл
     */
    public static function getWorkingIntervals($id, $from, $to, $forStatistic = false, $doCache = true)
    { 
        static $cache = array();
        
        $key = "{$id}:{$from}:{$to}";

        if(isset($cache[$key]) && $doCache) {

           return $cache[$key];
        }

        $res = core_Cache::get('work_schedule', $key);
        if(isset($res) && $res != false && $doCache) {

            $cache[$key] = $res;
            
            return $res;
        }

        $from   = dt::addTimeIfNot($from);
        $fromTs = dt::mysql2timestamp($from);

        $to   = dt::addTimeIfNot($to, '23:59:59');
        $toTs = dt::mysql2timestamp($to);

        $h24 = 24*60*60; // Секунди в едно денонощие
 
        $rec = self::fetch($id);
        
        // Ако имаме родителски график, вземаме го него за база
        if(isset($rec->parentId)) {
            $ints = self::getWorkingIntervals($rec->parentId, $from, $to);
        } else {
            $ints = new core_Intervals();
        }

        $dQuery = hr_ScheduleDetails::getQuery();
        $dQuery->where("#scheduleId = {$id} AND #state != 'rejected'");
        $dQuery->orderBy('#start', 'DESC');
        $gr1 = $gr2 = $gr3 = array();
        while($dRec = $dQuery->fetch()) {
            if(isset($dRec->repeat) && isset($dRec->until)) {
                $gr2[] = $dRec;
            } elseif(isset($dRec->repeat)) {
                $gr1[] = $dRec;
            } else {
                $gr3[] = $dRec;
            }
        }
 
        // Периодични интервали
        foreach($gr1 as $dRec) {
            self::addInterval($ints, $dRec, $fromTs, $toTs, $forStatistic);
        }
 

        if(isset($rec->nonWorking)) {
            $cutArr = explode(',', $rec->nonWorking);
            if($forStatistic) {
                $k = array_search('holiday', $cutArr);
                if($k !== false) {
                    unset($cutArr[$k]);
                }
                $k = array_search('non-working', $cutArr);
                if($k !== false) {
                    unset($cutArr[$k]);
                }
            }
 
            // Празници и почивни дни от календара
            for($t = $fromTs; $t <= $toTs + $h24; $t += $h24) {
                $day =  date('Y-m-d', $t);
                $dayDesc = cal_Calendar::getDayStatus($day);
                if(!isset($dayDesc->specialDay)) {
                    $dayDesc->specialDay = 'working';
                }

                if( in_array($dayDesc->specialDay, $cutArr) || 
                    (in_array('saturday', $cutArr) && date('N', $t) == 6) || 
                    in_array('sunday', $cutArr) && date('N', $t) == 7) {
                    $sd[] = $day;
                    $cutFrom = dt::mysql2timestamp(dt::addTimeIfNot($day));
                    $cutTo   = dt::mysql2timestamp(dt::addTimeIfNot($day, '23:59:59')); 
                    
                    // Ако интервала в поелдната секунда на празничния ден е започнал в него, премахваме целия интервал
                    $wInt = $ints->getByPoint($cutTo);
 
                    if(isset($wInt[0]) && $wInt[0] > $cutFrom && $wInt[1] < 12*60*60 + $cutTo) {
                         
                        $cutTo = max($wInt[1], $cutTo); 
                    }


                    $ints->cut($cutFrom, $cutTo);
                }
            }
        }
 
        // Периодични интервали с краен срок
        foreach($gr2 as $dRec) {
            self::addInterval($ints, $dRec, $fromTs, $toTs, $forStatistic);
        }

        // Непериодични интервали
        foreach($gr3 as $dRec) {
            self::addInterval($ints, $dRec, $fromTs, $toTs, $forStatistic);
        }

        if($doCache){
            $cache[$key] = $ints;
            core_Cache::set('work_schedule', $key, $ints, 10);
        }

        return $ints;
    }
    

    /**
     * Добавя записа към списъка с интервали
     */
    private static function addInterval($ints, $dRec, $fromTs, $toTs, $forStatistic = false)
    { 
        $duration = $forStatistic ? ($dRec->duration - $dRec->break) : $dRec->duration;
        $repeat = $dRec->repeat ? $dRec->repeat : 0;
        $startTs = dt::mysql2timestamp($dRec->start);
        $untilTs = $dRec->until ? dt::mysql2timestamp($dRec->until) : null;
        if($repeat > 0) {
            $min = max(0, floor(($fromTs - $duration - $startTs) / $repeat));
            $max = max(0, min(round(($toTs + $duration - $startTs) / $repeat), 
                              floor((($untilTs ? $untilTs : $toTs) - $startTs - 1) / $repeat)));
        } else {
            $min = $max = 0;
        }
 
        $i = $min;
        while($i <= $max) {
            $begin = dt::mysql2timestamp(dt::addSecs($i * $repeat, $dRec->start));
            $i++;

            if(($untilTs > 0 && $begin > $untilTs) || $begin > $toTs || $toTs < ($fromTs - $duration)) continue;
            $end   = $begin + $duration;
            if(core_Intervals::comp(array($fromTs, $toTs), array($begin, $end)) === 0) {
            if($dRec->type == 'working') {
                    $ints->add($begin, $end);
                } else {
                    $ints->cut($begin, $end-1);
               }
           }
        }
        
        // Оформяме краищата
        $ints->cut($fromTs - $duration -1, $fromTs - 1);
        $ints->cut($toTs + 1, $toTs + 1 + $duration);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function on_AfterPrepareSingle($mvc, $res, $data)
    {

    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'delete' && isset($rec->id)) {
            if (planning_Centers::fetch(array('#scheduleId = [#1#]', $rec->id))) {
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * Връща началните часове за всеки ден от посочения интервал
     */
    public static function getStartingTimes($id, $from, $to)
    {
        if(!$id) {
            $id = self::fetchField("#sysId = 'dayShift'", 'id');
        }

        $res = array();

        $ints = self::getWorkingIntervals($id, dt::addDays(-1, $from), dt::addDays(1, $to));
 
        while($from <= $to) {
            $begin = dt::addTimeIfNot($from, '00:00:00');
            $end   = dt::addTimeIfNot($from, '23:59:59');
            $fromSaved = $from;
            $from = dt::addDays(1, $from, false);

            $frame = $ints->getFrame(dt::mysql2timestamp($begin), dt::mysql2timestamp($end));

            if(isset($frame[0])) {
                list($d, $t) = explode(' ', dt::timestamp2mysql($frame[0][0]));
                if($t == '00:00:00') { 
                    // Ако интервалът, към който е точката в началото на денонощието е започнал в предходното денонощие, това не се взема предвид
                    $int = $ints->getByPoint($frame[0][0]);
                    if(isset($int) && ($frame[0][0] - $int[0] > 0) && ($frame[0][0] - $int[0] < 24*60*60)) {
                        if(count($frame) > 1) {
                            list($d, $t) = explode(' ', dt::timestamp2mysql($frame[1][0]));
                        } else {
                            continue;
                        }
                    }
                }
                $res[$fromSaved] = $t;
            }
        }

        return $res;
    }


    /**
     * Подготвя локациите на контрагента
     */
    public static function prepareCalendar($data)
    {
        $firstDay = Request::get('month', 'date');

        if(!isset($firstDay)) {
            $firstDay = date('Y-m-01');
        }

        $lastDay = date("Y-m-t", strtotime($firstDay));

        $startingTimes = self::getStartingTimes($data->masterId, $firstDay, $lastDay);
     
        foreach($startingTimes as $day => $time) {
            $data->Calendar[(int) substr($day, 8)] = substr($time, 0, 5);
        }

        list($data->CalendarYear, $data->CalendarMonth) = explode('-', $firstDay);

        $data->CalendarFirstDay = $firstDay;
        $data->CalendarFirstWeekDay = date("N", strtotime($firstDay));
        $data->CalendarLastDayOfMonth = (int) substr($lastDay, 8);
    }
    
    
    /**
     * Рендира данните
     */
    public function renderCalendar($data)
    {   
        $cUrl = getCurrentUrl();
        $prev = dt::addSecs(0-core_DateTime::SECONDS_IN_MONTH, $data->CalendarFirstDay, false);
        $cUrl['month'] = $prev;
        $prevUrl = toUrl($cUrl);

        $next = dt::addSecs(core_DateTime::SECONDS_IN_MONTH, $data->CalendarFirstDay, false);
        $cUrl['month'] = $next;
        $nextUrl = toUrl($cUrl);

        $month = "<a href='{$prevUrl}'>&#9664;</a>&nbsp;" . dt::getMonth((int) $data->CalendarMonth, 'F') . "&nbsp;<a href='{$nextUrl}'>&#9654;</a>" ;

        $year = $data->CalendarYear;

        $html = "\n<table class='listTable' style='border:none; width:100%;'>";
        $html .= "\n<tr><th colspan=7><h2 style='margin:10px !important;'>{$month} &nbsp;&nbsp; {$year}</h2></th></tr>";
        $html .= "\n<tr><th>Пн</th><th>Вт</th><th>Ср</th><th>Чт</th><th>Пт</th><th>Сб</th><th>Нд</th></tr>";

        $d = 0;
        $today = dt::today();
        $thisMont = $data->CalendarYear . '-' . dt::getMonth((int) $data->CalendarMonth) . '-';
            
  
        while(($d + 2 - $data->CalendarFirstWeekDay) <= $data->CalendarLastDayOfMonth) {
            $db[] = " -" . $d;
            $html .= "\n<tr>";
            for($i = 1; $i <= 7; $i++) {
                $cDay = $d + $i - $data->CalendarFirstWeekDay + 1;  
                $db[] = $cDay;
                $cDate = $thisMont . sprintf("%02d", $cDay);
                $dStatus = cal_Calendar::getDayStatus($cDate);

                $add = '';
                if(isset($data->Calendar[$cDay])) {
                    $time = $data->Calendar[$cDay];
                    $h = (int) $time;
                    $c = (360 + 240 - round(($h+1) * 3600/240)) % 360;
                    if($h >= 20 || $h < 4) {
                        $color = "background-color: hsl(0, 0%, 10%);color : hsl($c, 100%, 80%)";
                    } else {
                        $color = "background-color:  hsl(0, 0%, 90%); color : hsl($c, 100%, 20%)";
                    }    
                    $add = "<span class='add' style='font-size:0.8em; padding:2px; border-radius:5px; {$color}'>" .  $time  . "</span>";
                    $dayColor = '';
                } else {
                    if(isset($dStatus->specialDay) && $dStatus->specialDay == 'holiday') {
                        $dayColor = 'color:red;';
                    } else {
                        $dayColor = 'color:green;';
                    }
                }

                $date = '&nbsp;';
                $outline = '';
         
                if($cDay > 0 && $cDay <= $data->CalendarLastDayOfMonth) {
                    $date = "<div class='mc-day' style='font-size:2em;{$dayColor}'>{$cDay}</div>";
                    if($cDate == $today) {
                        $outline = 'outline:solid 3px red; outline-offset:-3px;';
                    }
                }
 
                $html .= "<td valign=top style='vertical-align:top !important;padding:5px;text-align:center;{$outline}'>{$date}{$add}</td>";
            }

            $d += 7;
            
            $html .= "\n</tr>";
        }
    
        $html .= '</table>';

        return $html;
    }

    /**
     * Изтриване на кеша при обновяване на детайла
     */
    public static function on_AfterUpdateMaster($mvc, &$res, $id)
    {
       core_Cache::removeByType('work_schedule');
    }
    
    /**
     * Изтриване на кеша при обновяване на мастера
     */
    public static function on_AfterUpdate($mvc, &$res, $id)
    {
       core_Cache::removeByType('work_schedule');
    }

    /**
     * По зададени начална и крайна дата изчислява неработните дни 
     * м/у двете дати според зададения работния график
     *
     * @param int      $id
     * @param datetime $from
     * @param datetime $to
     */
    public static function calcLeaveDaysBySchedule($id, $from, $to)
    {
        $from = substr($from, 0, 10);
        $to = substr($to, 0, 10);

        $workDays = $allDays = 0;
        
        $sTimes = self::getStartingTimes($id, $from, $to);
 
        $workDays = count($sTimes);

        $res = (object) array('nonWorking' => $nonWorking, 'workDays' => $workDays, 'allDays' => $allDays);
       
        return $res;
    }


    /**
     * Връща общото работно време за посочения период за посочения график
     */
    public static function getTotalTime($scheduleId, $start, $duration, $forStatistic = true)
    {
        $end = dt::addSecs($duration, $start);
        $ints = self::getWorkingIntervals($scheduleId, $start, $end, $forStatistic);

        $time = $ints->getTotalSum();
 
        return $time;
    }
    
    
    
    /**
     * Създава начални шаблони за трудови договори, ако такива няма
     */
    public function on_AfterSetUpMvc($mvc, &$res)
    {
        if (!self::count("#sysId = 'dayShift'")) {
            // Стандартен работен график
            $rec = new stdClass();
            $rec->name = '5 дни/8 ч./От 9:00';
            $rec->nonWorking = 'holiday,non-working,saturday,sunday';
            $rec->sysId = 'dayShift';
            $rec->createdBy = -1;
            $id = self::save($rec);

            $rec = new stdClass();
            $rec->scheduleId = $id;
            $rec->type = 'working';
            $rec->start = '2015-01-01 09:00:00';
            $rec->duration = (8*60+30)*60;
            $rec->break = 30*60;
            $rec->repeat = 24*60*60;
            $rec->createdBy = -1;
            hr_ScheduleDetails::save($rec);

            $rec = new stdClass();
            $rec->name = '5 дни/8 ч./От 8:00';
            $rec->nonWorking = 'holiday,non-working,saturday,sunday';
            $rec->sysId = 'dayShift';
            $rec->createdBy = -1;
            $id = self::save($rec);

            $rec = new stdClass();
            $rec->scheduleId = $id;
            $rec->type = 'working';
            $rec->start = '2015-01-01 08:00:00';
            $rec->duration = (8*60+30)*60;
            $rec->break = 30*60;
            $rec->repeat = 24*60*60;
            $rec->createdBy = -1;
            hr_ScheduleDetails::save($rec);

            $rec = new stdClass();
            $rec->name = 'Nonstop 24/7 (без офиц. празници)';
            $rec->nonWorking = 'holiday';
            $rec->sysId = 'nonstoph';
            $rec->createdBy = -1;
            $id = self::save($rec);

            $rec = new stdClass();
            $rec->scheduleId = $id;
            $rec->type = 'working';
            $rec->start = '2015-01-01 00:00:00';
            $rec->duration = (24*60)*60;
            $rec->break = 0;
            $rec->repeat = 24*60*60;
            $rec->createdBy = -1;
            hr_ScheduleDetails::save($rec);

            $res .= "<li class='debug-new'>Създадени са графиците <b>5 дни/8 ч./От 9:00, 5 дни/8 ч./От 8:00,Nonstop 24/7 (без офиц. празници)</b></li>";
        }
    }
}
