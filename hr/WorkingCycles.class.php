<?php 


/**
 * Работни цикли
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hr_WorkingCycles extends core_Master
{
    
    
    /**
     * Заглавие
     */
    public $title = "Цикли";
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Работни цикли";
    
    
    /**
     * Страница от менюто
     */
    public $pageMenu = "Персонал";
    
    /**
     * @todo Чака за документация...
     */
    public $details = 'hr_WorkingCycleDetails';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, hr_Wrapper,  plg_Printing';
    
    
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
    public $listFields = 'id,name,cycleDuration';
    
    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'hr/tpl/SingleLayoutWorkingCycles.shtml';
    
    
    /**
     * Съкращение на видовете дни
     */
    static $shiftMap = array(
        0 => 'п',
        1 => 'I',
        2 => 'II',
        3 => 'н',
        4 => 'д',
        5 => 'о',
        6 => 'б',
        7 => 'к',
    );
    
    
    /**
     * Съкращение на видовете дни, когато интерфейса е на английски
     */
    static $shiftMapEn = array(
        0 => 'r',
        1 => 'I',
        2 => 'II',
        3 => 'n',
        4 => 'd',
        5 => 'l',
        6 => 's',
        7 => 't',
    );
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование, width=100%,mandatory');
        $this->FLD('cycleDuration', 'int(min=1)', 'caption=Брой дни, width=50px, mandatory');
        $this->FLD('sysId', 'varchar', "caption=Служебно ид,input=none");
        
        // $this->FLD('cycleMeasure', 'enum(days=Дни,weeks=Седмици)', 'caption=Цикъл->Мярка, maxRadio=4,mandatory');
        // $this->FLD('serial', 'text', "caption=Последователност,hint=На всеки ред запишете: \nчасове работа&#44; минути почивка&#44; неработни часове");
        
        $this->setDbUnique('name');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function on_AfterPrepareSingle($mvc, $res, $data)
    {
        $maxNight = 0;
        $rec = $data->rec;
        $tTime = core_Type::getByName("time(format=H:M)");
        
        for($i = 1; $i <= $rec->cycleDuration; $i++) {
            $night = 0;
            
            for($j = 0; $j < 7; $j++) {
                $day = (($i + $j) % $rec->cycleDuration) + 1; 
                $dRec = hr_WorkingCycleDetails::fetch(array("#cycleId = [#1#] AND #day = [#2#]", $rec->id, $day));
                $night += hr_WorkingCycleDetails::getSection($dRec->start, $dRec->duration, 22 * 60 * 60, 7 * 60 * 60);
            }
            
            $maxNight = max($maxNight, $night);
        }
        
        $maxNight = $tTime->toVerbal($maxNight);
        
        if (hr_Departments::haveRightFor('single', $rec)) {
            $url = array('hr_WorkingCycles',"Print", $rec->id);
            $efIcon = 'img/16/printer.png';
            $link = ht::createLink('', $url, FALSE, "title=Печат,ef_icon={$efIcon}");
            $data->row->print = $link;
        }
        
        //$data->row->info = "Max night: $maxNight<br>";
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if($action == 'delete' && isset($rec->id)){
             if(planning_ActivityCenters::fetch(array("#schedule = [#1#]", $rec->id))){
                $requiredRoles = 'no_one';
             }
        }
    }
    
    
    /**
     * Подготвя локациите на контрагента
     */
    function prepareGrafic($data, $start = NULL)
    {
        
        expect($data->masterId); 
        $shift = hr_Departments::fetchField($data->masterId, 'schedule');
        
        return;
        
        $customScheQuery = hr_CustomSchedules::getQuery();
        $custom = array();
        
        if($data->Cycles) {
            $customScheQuery->where("#personId = {$data->Cycles->personId} AND #personId IS NOT NULL");
            
            $shift = hr_Departments::fetchField($data->Cycles->masterId, 'schedule');
            $startingOn = hr_Departments::fetchField($data->Cycles->masterId, 'startingOn');
            $name = hr_Departments::fetchField($data->Cycles->masterId, 'name');
            
        } else {
            $customScheQuery->where("#departmenId = {$data->masterId}");
        
            $shift = hr_Departments::fetchField($data->masterId, 'schedule');
            $startingOn = hr_Departments::fetchField($data->masterId, 'startingOn');
            $name = hr_Departments::fetchField($data->masterId, 'name');
        }
        
        while($customRec = $customScheQuery->fetch()) {
            $custom[] = (object) $customRec;
        }

        if($shift){ 

            $state = self::getQuery();
            $state->where("#id='{$shift}'");
            $cycleDetails = $state->fetch();
            
            if ($start) {
                 $startTms = dt::mysql2timestamp($start);
                     
                    $year = date('Y', $startTms);
                    $month = date('m', $startTms);
     
            } else {
                $month = Request::get('cal_month', 'int');
                $month = str_pad($month, 2, 0, STR_PAD_LEFT);
                $year  = Request::get('cal_year', 'int');
            }
            
            if(!$month || $month < 1 || $month > 12 || !$year || $year < 1970 || $year > 2038) {
                $year = date('Y');
                $month = date('n');
            }
            
            // Добавяне на първия хедър
            $currentMonth = tr(dt::$months[$month-1]) . " " . $year;
            
            $pm = $month-1;
            
            if($pm == 0) {
                $pm = 12;
                $py = $year-1;
            } else {
                $py = $year;
            }
            $prevMonth = tr(dt::$months[$pm-1]) . " " . $py;
            
            $nm = $month + 1;
            
            if($nm == 13) {
                $nm = 1;
                $ny = $year + 1;
            } else {
                $ny = $year;
            }
            
            $nextMonth = tr(dt::$months[$nm-1]) . " " . $ny;
            
            $nextLink = $prevtLink = getCurrentUrl();
            
            $nextLink['cal_month'] = $nm;
            $nextLink['cal_year'] = $ny;
            $nextLink = toUrl($nextLink);
            
            $prevtLink['cal_month'] = $pm;
            $prevtLink['cal_year'] = $py;
            $prevtLink = toUrl($prevtLink);
            
            // Таймстамп на първия ден на месеца
            $firstDayTms = mktime(0, 0, 0, $month, 1, $year);
            
            // Броя на дните в месеца (= на последната дата в месеца);
            $lastDay = date('t', $firstDayTms);
           
            for($i = 1; $i <= $lastDay; $i++){
                $daysTs = mktime(0, 0, 0, $month, $i, $year);
                $date = date("Y-m-d H:i", $daysTs);
                $d[$i] = new stdClass();
                
                $start = explode("-", $startingOn);
     
                if($month < $start[1] && $year == $start[0]) {
                    $d[$i]->html = "";
                } else { 
                    $d[$i]->html = "<span style='float: left;'>" . self::$shiftMap[static::getShiftDay($cycleDetails, $date, $startingOn)] . "</span>";
                }
                
                if(core_Lg::getCurrent() == 'en'){
                    $d[$i]->html = "<span style='float: left;'>" . self::$shiftMapEn[static::getShiftDay($cycleDetails, $date, $startingOn)] . "</span>";
                    
                    if($month < $start[1]){
                        $d[$i]->html = "";
                    }
                }
                
                $d[$i]->type = (string)static::getShiftDay($cycleDetails, $date, $startingOn);
              
                $url = array("cal_Calendar" , "day", "from" => $i . '.' . $month . '.' . $year);
                $url = toUrl($url);
                
                $d[$i]->url = $url;
            }
            
            $data->TabCaption = tr('График');
            $month = str_pad($month, 2, ' ', STR_PAD_LEFT);
  
            if(is_array($custom)) { 
        
                foreach($custom as $cRec) {
                     
                    if (isset($cRec->typeDepartmen)) {
                        $typeDate = $cRec->typeDepartmen;
                    } else {
                        $typeDate = $cRec->typePerson;
                    }

                    $dateTms = dt::mysql2timestamp($cRec->date);
                     
                    $cYear = date('Y', $dateTms);
                    $cMonth = date('m', $dateTms);
                    $cDay = date('d', $dateTms);
                    
                    $jDate = date('j', $dateTms);

                    if ($month == $cMonth) {
                        if ($d[$jDate]) {
                            switch ($typeDate) {
                  
                                case 'working':
                                    $day = hr_WorkingCycleDetails::getWorkingShiftType($cRec->start, $cRec->duration);
                                    $hour = gmdate("H:i", $cRec->start);
                                        
                                    $d[$jDate]->html = "<span style='float: left;'>" . self::$shiftMap[$day] .  "   " .  $hour . "</span>";
                                    $d[$jDate]->type =  $day;
                                        
                                    break;
                                        
                                case 'nonworking':
                                        
                                    $d[$jDate]->html = "<span style='float: left;'>" . self::$shiftMap[0] . "</span>";
                                    $d[$jDate]->type = "0";

                                    break;
                                        
                                case 'leave':
                                        
                                    $d[$jDate]->html = "<span style='float: left;'>" . self::$shiftMap[5] . "</span>";
                                    $d[$jDate]->type = "5";
                                        
                                    break;
                                case 'traveling':
                                        
                                    $d[$jDate]->html = "<span style='float: left;'>" . self::$shiftMap[7] . "</span>";
                                    $d[$jDate]->type = "7";
                                        
                                    break;
                                case 'sicDay':
                                        
                                    $d[$jDate]->html = "<span style='float: left;'>" . self::$shiftMap[6] . "</span>";
                                    $d[$jDate]->type = "6";
                                        
                                    break;
                            } 
                        }
                    }
                }
            }

            return (object) array('year'=>$year,
                'month'=>$month,
                'd'=>$d,
                'prevtLink'=>$prevtLink,
                'prevMonth'=>$prevMonth,
                'currentMonth'=>$currentMonth,
                'nextLink'=>$nextLink,
                'nextMonth'=>$nextMonth,
                'header'=>$header,
                'lastDay'=>$lastDay,
                'name'=>$name,
                'link'=>$linkPrint,
                'name'=>$name,
                'year'=> $year,
                'start'=> $start[1],
            );
        }
    }
    
    
    /**
     * Рендира данните
     */
    function renderGrafic($data)
    {
        $prepareRecs = static::prepareGrafic($data);
  
        $tpl = new ET(getTplFromFile('hr/tpl/SingleLayoutShift.shtml'));
        $tpl->push('hr/tpl/style.css', 'CSS');
        
        $monthOpt = cal_Calendar::prepareMonthOptions();

        if(!Mode::is('printing')) {
            if($prepareRecs){
                $select = ht::createSelect('dropdown-cal', $monthOpt->opt, $prepareRecs->currentMonth, array('onchange' => "javascript:location.href = this.value;", 'class' => 'portal-select'));
                
                $header = "<table class='mc-header' width='100%' cellpadding='0'>
                            <tr>
                                <td style='text-align: left'><a href='{$prepareRecs->prevtLink}'>{$prepareRecs->prevMonth}</a></td>
                                <td style='text-align: center'><b>{$select}</b></td>
                                <td style='text-align: right'><a href='{$prepareRecs->nextLink}'>{$prepareRecs->nextMonth}</a></td>
                            </tr>
                        </table>";
                
                $tpl->append($prepareRecs->link, 'id');
                $tpl->append($prepareRecs->name, 'name');
                
                $monthHeader = dt::getMonth($prepareRecs->month, $format = 'F', $lg = 'bg');
                $tpl->append($monthHeader, 'month');
                
                $calendar = cal_Calendar::renderCalendar($prepareRecs->year, $prepareRecs->month, $prepareRecs->d, $header);
                $tpl->append($calendar, 'calendar');

                // правим url  за принтиране
                $url = array('hr_WorkingCycles', 'Print', 'Printing'=>'yes', 'masterId' => $data->masterId, 'cal_month'=>$prepareRecs->month, 'cal_year' =>$prepareRecs->year);
                $efIcon = 'img/16/printer.png';
                $link = ht::createLink('', $url, FALSE, "title=Печат,ef_icon={$efIcon}");
                $tpl->append($link, 'print');
            }
        }
        
        if(Mode::is('printing')) {
            $curUrl = getCurrentUrl();
  
            
            $month =  mb_convert_case(dt::getMonth($prepareRecs->month, 'F',  'bg'), MB_CASE_LOWER, "UTF-8");
            $tpl->content = str_replace("Работен график", "", $tpl->content);
            
            if ($curUrl['personId']) {
                $personName = crm_Persons::fetchField($curUrl['personId'], 'name');
                $title = "<b class='printing-title'>" . tr("Работен график на ") . tr($personName) . tr(" за месец ") . tr($month) . "<br /></b>";
            } else {
                $title = "<b class='printing-title'>" . tr("Работен график на ") . tr($prepareRecs->name) . tr(" за месец ") . tr($month) . "<br /></b>";
            }
            $tpl->append($title, 'printTitle');
            
            $calendar = cal_Calendar::renderCalendar($prepareRecs->year, $prepareRecs->month, $prepareRecs->d);
            $tpl->append($calendar, 'calendar');
        }
        
        for($j = 0; $j <= 7; $j++){
            for($i = 1; $i <= $prepareRecs->lastDay; $i++){ 
                if($prepareRecs->d[$i]->type == '0' && '0' == $j){
                    $tpl->append(' rest', "shift{$j}");
                } elseif($prepareRecs->d[$i]->type == '1' && '1' == $j){
                    $tpl->append(' first', "shift{$j}");
                } elseif($prepareRecs->d[$i]->type == '2' && '2' == $j){
                    $tpl->append(' second', "shift{$j}");
                } elseif($prepareRecs->d[$i]->type == '3' && '3' == $j){
                    $tpl->append(' third', "shift{$j}");
                } elseif($prepareRecs->d[$i]->type == '4' && '4' == $j){
                    $tpl->append(' diurnal', "shift{$j}");
                } elseif($prepareRecs->d[$i]->type == '5' && '5' == $j){
                    $tpl->append(' leave', "shift{$j}");
                } elseif($prepareRecs->d[$i]->type == '6' && '6' == $j){
                    $tpl->append(' sick', "shift{$j}");
                } elseif($prepareRecs->d[$i]->type == '7' && '7' == $j){
                    $tpl->append(' traveling', "shift{$j}");
                }
            }
        }

        return $tpl;
    }
    
    
    /**
     * По зададена смяна и ден от календара
     * връща режима на смяната
     *
     * @param stdClass $recShift
     * @param mySQL date $date
     */
    static public function getShiftDay($recShift, $date, $startOn)
    {
 
        // По кой цикъл работи смяната
        // Кога започва графика на смяната
        $cycle = $recShift->id;
        
        //$startOn = $recShift->startingOn;
        
        // Продължителността на цикъла в дни
        $cycleDuration = $recShift->cycleDuration;
        
        // В кой ден от цикъла сме
        $dayIs = (dt::daysBetween($date, $startOn) + 1) % $cycleDuration;
 
        // Извличане на данните за циклите
        $stateDetails = hr_WorkingCycleDetails::getQuery();
        
        // Подробности за конкретния цикъл
        $stateDetails->where("#cycleId='{$cycle}' AND #day='{$dayIs}'");
        
        // Взимаме записа на точно този избран ден от цикъла
        // Кога за почва режима и с каква продължителност е
        $cycleDetails = $stateDetails->fetch();
        $dayStart = $cycleDetails->start;
        $dayDuration = $cycleDetails->duration;

        return hr_WorkingCycleDetails::getWorkingShiftType($dayStart, $dayDuration);
    }
    
    
    /**
     * По зададени работен график, отдел и начална и крайна дата изчислява
     * неработните дни м/у двете дати според работния график
     *
     * @param int $id
     * @param int $masterId
     * @param datetime $leaveFrom
     * @param datetime $leaveTo
     */
    static public function calcLeaveDaysBySchedule($id, $departmentId, $leaveFrom, $leaveTo)
    {
        $nonWorking = $workDays = $allDays = 0;

        $dRec = hr_Departments::fetch($departmentId);
        
        if(!$dRec || !$dRec->startingOn || !$dRec->schedule) {
            $res = cal_Calendar::calcLeaveDays($leaveFrom, $leaveTo);
        } else { 
            $days = hr_WorkingCycleDetails::getDayArr($dRec->schedule, $dRec->startingOn, $leaveFrom, $leaveTo);
 
            foreach($days as $d) {
                if($d && $d->duration > 0 && !$d->isHoliday) {
                    $workDays++;
                } else {
                    $nonWorking++;
                }
                $allDays++;
            }

            $res = (object) array('nonWorking' => $nonWorking, 'workDays' => $workDays, 'allDays' => $allDays);
        }

        return $res;
    }


    /**
     * Изчислява изработените часове по график
     * 
     * @param int $schedule - работен цикъл
     * @param string $from - от дата
     * @param string $to - до дата
     * @param string $startingOn - начало на работния график
     * 
     * @return StdClass array(workingSecs=>изработените часове по графика в секунди,
     *                        rest=>почивните дни по графика в часове, 
     *                        secsPeriod=>периода в секунди)
     */
    static public function calcWorkingHoursBySchedule($schedule, $from, $to, $startingOn)
    {
        $workingSecs = $rest = $secsPeriod = $allDays =  0;

        $cycleDetails = self::fetch($schedule);

        $curDate = $from;
        
        // От началната дата до крайната, проверяваме всеки ден
        // каква е работната му продължителност
        while($curDate < dt::addDays(1, $to)){
            
            // В кой ден от цикъла сме
            $dayIs = (dt::daysBetween($curDate, $startingOn) + 1) % $cycleDetails->cycleDuration;
            
            // Извличане на данните за циклите
            $scheduleDetails = hr_WorkingCycleDetails::getQuery();
            
            // Подробности за конкретния цикъл
            $scheduleDetails->where("#cycleId='{$schedule}' AND #day='{$dayIs}'");
            
            // Взимаме записа на точно този избран ден от цикъла
            // Кога за почва режима и с каква продължителност е
            $details = $scheduleDetails->fetch();
            $dayStart = $details->start;
            $dayDuration = $details->duration;
            $dayBreak = $details->break;
            
            // Какъв е типа на деня
            $dateType = static::getShiftDay($cycleDetails, $curDate, $startingOn);
            
            if($dateType == 0) {
                $rest += 24*60*60;
            } else {
                $workingSecs += $dayDuration - $dayBreak;
            }

            $curDate = dt::addDays(1, $curDate);
            
            $allDays++;
        }

        $secsPeriod = $allDays * (24*60*60);
        
        return (object) array('workingSecs'=>$workingSecs, 'rest'=>$rest, 'secsPeriod'=>$secsPeriod);
    }
    
    
    /**
     * Принтирване само на календара с работния график
     */
    function act_Print()
    {
        $data = new stdClass();
        $id = Request::get('masterId', 'int'); 
        $data->masterId  = $id;
        
        if(Mode::is('printing')) {
            
            return self::renderGrafic($data);
        }
    }
    

    /**
     *
     */
    static public function colectPersonDaysType()
    {
        $now = dt::today();
    
        $persons = array();
    
        //масив за проверка с всички данни
        $chekArr = array();
        $next2weeks = dt::addDays(14,dt::today());
    
        $querySick = hr_Sickdays::getQuery();
        $querySick->where("((#startDate <= '{$now}' AND #toDate >= '{$now}') OR (#startDate >= '{$now}' AND #toDate <= '{$next2weeks}')) AND #state = 'active'");
    
        $queryTrip = hr_Trips::getQuery();
        $queryTrip->where("((#startDate <= '{$now}' AND #toDate >= '{$now}') OR (#startDate >= '{$now}' AND #toDate <= '{$next2weeks}')) AND #state = 'active'");
    
        $queryLeave = hr_Leaves::getQuery();
        $queryLeave->where("((#leaveFrom <= '{$now}' AND #leaveTo >= '{$now}') OR (#leaveFrom >= '{$now}' AND #leaveFrom <= '{$next2weeks}')) AND #state = 'active'");
    
        // добавяме болничните
        while($recSick = $querySick->fetch()){
            // ключ за масива ще е ид-то на всеки потребител в системата
            $id = $recSick->personId;
    
            // масив за проверка
            $chekArr[$id][] =
            (object) array ('stateInfo' => 'sickDay',
                'stateDateFrom' => $recSick->startDate,
                'stateDateTo' => $recSick->toDate,
            );
    
            // правим масив с всички служители
            if(!array_key_exists($id, $persons)){
                // ако двете дати са в миналото, това събитие не ни интересува
                if($recSick->startDate <= $now || $recSick->toDate <= $now) { }
    
                // ако двете са в бъдещето, търсим по-малката от двете
                if($recSick->startDate >= $now && $recSick->toDate >= $now) {
                    $date = ($recSick->startDate >= $recSick->toDate) ? $recSick->startDate :  $recSick->toDate;
                    // началната дата след днес ли е?
                } elseif($recSick->startDate >= $now) {
                    $date = $recSick->startDate;
                    // а крайната?
                } else {
                    $date = $recSick->toDate;
                }
    
                // добавяме в масива събитието
                $persons[$id] =
                (object) array ('stateInfo' => 'sickDay',
                    'stateDateFrom' => $recSick->startDate,
                    'stateDateTo' => $recSick->toDate,
                    'date' => $date
                );
                 
                // в противен случай го ъпдейтваме
            } else {
    
                $obj = &$persons[$id];
    
                // ако двете дати са в миналото, това събитие не ни интересува
                if($recSick->startDate <= $now || $recSick->toDate <= $now) { }
    
                // ако двете са в бъдещето, търсим по-малката от двете
                if($recSick->startDate >= $now && $recSick->toDate >= $now) {
                    $newDate = ($recSick->startDate >= $recSick->toDate) ? $recSick->startDate :  $recSick->toDate;
                    // началната дата след днес ли е?
                } elseif($recSick->startDate >= $now) {
                    $newDate = $recSick->startDate;
                    // а крайната?
                } else {
                    $newDate = $recSick->toDate;
                }
    
                // новата дата на събитието по-малак ли е от текущата дата?
                if($newDate <= $obj->date) {
    
                    //ъпдейтване на събитието
                    $obj->stateInfo = 'sickDay';
                    $obj->stateDateFrom = $recSick->startDate;
                    $obj->stateDateTo = $recSick->toDate;
                    $obj->date = $newDate;
                }
                
                // ако има събитие 2 последователни събития, то ги обединяваме
                if(dt::daysBetween(strstr($newDate, " ", TRUE), strstr($obj->date, " ", TRUE)))
                {
                    //ъпдейтване на събитието
                    $obj->stateInfo = 'sickDay';
                    $obj->stateDateTo = $recSick->toDate;
                    $obj->date = $newDate;
                }
            }
        }
    
        // добавяме командировките
        while($recTrip = $queryTrip->fetch()){
            // ключ за масива ще е ид-то на всеки потребител в системата
            $id = $recTrip->personId;
    
            $chekArr[$id][] =
            (object) array ('stateInfo' => 'tripDay',
                'stateDateFrom' => $recTrip->startDate,
                'stateDateTo' => $recTrip->toDate,
            );
    
            // правим масив с всички служители
            if(!array_key_exists($id, $persons)){
                // ако двете дати са в миналото, това събитие не ни интересува
                if($recTrip->startDate <= $now || $recTrip->toDate <= $now) { }
    
                // ако двете са в бъдещето, търсим по-малката от двете
                if($recTrip->startDate >= $now && $recTrip->toDate >= $now) {
                    $date = ($recTrip->startDate >= $recTrip->toDate) ? $recTrip->startDate :  $recTrip->toDate;
                    // началната дата след днес ли е?
                } elseif($recTrip->startDate >= $now) {
                    $date = $recTrip->startDate;
                    // а крайната?
                } else {
                    $date = $recTrip->toDate;
                }
    
                // добавяме в масива събитието
                $persons[$id] =
                (object) array ('stateInfo' => 'tripDay',
                    'stateDateFrom' => $recTrip->startDate,
                    'stateDateTo' => $recTrip->toDate,
                    'date' => $date
                );
    
                // в противен случай го ъпдейтваме
            } else {
    
                $obj = &$persons[$id];
    
                // ако двете дати са в миналото, това събитие не ни интересува
                if($recTrip->startDate <= $now || $recTrip->toDate <= $now) { }
    
                // ако двете са в бъдещето, търсим по-малката от двете
                if($recTrip->startDate >= $now && $recTrip->toDate >= $now) {
                    $newDate = ($recTrip->startDate >= $recTrip->toDate) ? $recTrip->startDate :  $recTrip->toDate;
                    // началната дата след днес ли е?
                } elseif($recTrip->startDate >= $now) {
                    $newDate = $recTrip->startDate;
                    // а крайната?
                } else {
                    $newDate = $recTrip->toDate;
                }
    
                // новата дата на събитието по-малак ли е от текущата дата?
                if($newDate <= $obj->date) {
    
                    //ъпдейтване на събитието
                    $obj->stateInfo = 'tripDay';
                    $obj->stateDateFrom = $recTrip->startDate;
                    $obj->stateDateTo = $recTrip->toDate;
                    $obj->date = $newDate;
                }
                
                // ако има събитие 2 последователни събития, то ги обединяваме
                if(dt::daysBetween(strstr($newDate, " ", TRUE), strstr($obj->date, " ", TRUE)))
                {
                    //ъпдейтване на събитието
                    $obj->stateInfo = 'tripDay';
                    $obj->stateDateTo = $recTrip->toDate;
                    $obj->date = $newDate;
                }
            }
        }
    
        // добавяме и отпуските
        while($recLeave = $queryLeave->fetch()){
            // ключ за масива ще е ид-то на всеки потребител в системата
            $id = $recLeave->personId;
    
            $chekArr[$id][] =
            (object) array ('stateInfo' => 'leaveDay',
                'stateDateFrom' => $recLeave->leaveFrom,
                'stateDateTo' => $recLeave->leaveTo,
            );
             
            // правим масив с всички служители
            if(!array_key_exists($id, $persons)){
                // ако двете дати са в миналото, това събитие не ни интересува
                if( $recLeave->leaveFrom <= $now || $recLeave->leaveTo <= $now) {}
    
                // ако двете са в бъдещето, търсим по-малката от двете
                if( $recLeave->leaveFrom >= $now && $recLeave->leaveTo >= $now) {
                    $date = ( $recLeave->leaveFrom >= $recLeave->leaveTo) ?  $recLeave->leaveFrom :  $recLeave->leaveTo;
                    // началната дата след днес ли е?
                } elseif( $recLeave->leaveFrom >= $now) {
                    $date =  $recLeave->leaveFrom;
                    // а крайната?
                } else {
                    $date = $recLeave->leaveTo;
                }
                 
                // добавяме в масива събитието
                $persons[$id] =
                (object) array ('stateInfo' => 'leaveDay',
                    'stateDateFrom' => $recLeave->leaveFrom,
                    'stateDateTo' => $recLeave->leaveTo,
                    'date' => $date
                );
  
                // в противен случай го ъпдейтваме
            } else {
                 
                $obj = &$persons[$id];
    
                // ако двете дати са в миналото, това събитие не ни интересува
                if($recLeave->leaveFrom <= $now || $recLeave->leaveTo <= $now) { }
    
                // ако двете са в бъдещето, търсим по-малката от двете
                if($recLeave->leaveFrom >= $now && $recLeave->leaveTo >= $now) {
                    $newDate = ($recLeave->leaveFrom >= $recLeave->leaveTo) ? $recLeave->leaveFrom :  $recLeave->leaveTo;
                    // началната дата след днес ли е?
                } elseif($recLeave->leaveFrom >= $now) {
                    $newDate = $recLeave->leaveFrom;
                    // а крайната?
                } else {
                    $newDate = $recLeave->leaveTo;
                }
                
                // новата дата на събитието по-малак ли е от текущата дата?
                if($newDate <= $obj->date ) { 
    
                    //ъпдейтване на събитието
                    $obj->stateInfo = 'leaveDay';
                    $obj->stateDateFrom = $recLeave->leaveFrom;
                    $obj->stateDateTo = $recLeave->leaveTo;
                    $obj->date = $newDate;
                } 
                
                // ако има събитие 2 последователни събития, то ги обединяваме
                if(dt::daysBetween(strstr($newDate, " ", TRUE), strstr($obj->date, " ", TRUE)))
                {
                    //ъпдейтване на събитието
                    $obj->stateInfo = 'leaveDay';
                    $obj->stateDateTo = $recLeave->leaveTo;
                    $obj->date = $newDate;
                }
            }
        }

        // взимаме всички профили
        $query = crm_Profiles::getQuery();
        // които са активни
        $query->where("#state = 'active'");
         
        while($rec = $query->fetch()){
    
            // добавяме полетата
            //тип на деня
            $rec->stateInfo = $persons[$rec->personId]->stateInfo;
            //от дата
            $rec->stateDateFrom = $persons[$rec->personId]->stateDateFrom;
            // до дата
            $rec->stateDateTo = $persons[$rec->personId]->stateDateTo;
    
            // и ги записваме на съответния профил
            crm_Profiles::save($rec,'personId,stateInfo,stateDateFrom,stateDateTo');
        }
    }
    
    
    /**
     * Изпращане на нотификации за започването на задачите
     */
    function cron_SetPersonDayType()
    {
    
        $this->colectPersonDaysType();
    }

    
    /**
     * Създава начални шаблони за трудови договори, ако такива няма
     */
    function on_AfterSetUpMvc($mvc, &$res)
    {
        if(!self::count()) {
            // Стандартен работен график
            $rec = new stdClass();
            
            $rec->name = 'Дневен график';
            $rec->cycleDuration = 7;
            $rec->sysId = 'dayShift';
            $rec->createdBy = -1;
            
            self::save($rec);
        } else{
            $query = self::getQuery();
            
            // Намираме тези, които са създадени от системата
            $query->where("#createdBy = -1");
            $sysContracts = array();
            
            while ($recPrev = $query->fetch()){
                $rec = new stdClass();
                $rec->id = $recPrev->id;
                $rec->name = 'Дневен график';
                
                self::save($rec, 'name');
            }                                                                                         
        }   
    }
}
