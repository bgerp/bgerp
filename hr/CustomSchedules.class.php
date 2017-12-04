<?php 


/**
 * Персонални работни цикли
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Gabriela Petrova <gab4eto@gmial.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hr_CustomSchedules extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Персонални работни графици";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Персонален работен график";
    
    
    /**
     * Страница от менюто
     */
    var $pageMenu = "Персонал";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools2, hr_Wrapper,  plg_Printing';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,hr,trz';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'ceo,hr,trz';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,hr,trz';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'ceo,hr,trz';
    
    /**
     * Полетата, които ще се показват в листов изглед
     */
    var $listFields = 'date,str=Структура / Служител,type=Вид / Документ,start,duration,break';
    
    
    static $map = array('working'=> 'работен',
                        'nonworking'=>'почивен',
                        'leave'=>'отпуска',
                        'traveling'=>'командировка',
                        'sicDay'=>'болничен',);
    /**
     * Описание на модела
     */
    function description()
    {
        // Уникален ключ за събитието
        $this->FLD('key', 'varchar(40)', 'caption=Ключ,input=hidden');
        
        $this->FLD('date', 'date', 'caption=Дата, width=100%,mandatory');
        
        $this->FLD('docId',    'int', 'caption=Документ->№,width=100%,input=hidden');

        $this->FLD('docClass', 'class(interface=doc_DocumentIntf,select=title)',
            'caption=Документ->Клас,silent,width=100%,input=hidden');
        
        $this->FLD('strukture', 'enum(departmenId=структура,
                                      personId=служител,)', 'caption=Персонализация на, width=50px,silent, autoFilter');
        $this->FLD('typeDepartmen', 'enum(working=работен,
                                     nonworking=почивен,)', 'caption=Вид,width=100%,input=none,silent, autoFilter');
        $this->FLD('typePerson', 'enum(working=работен,
                                       nonworking=почивен,
                                       leave=отпуска,
                                       traveling=командировка,
                                       sicDay=болничен,)', 'caption=Вид,width=100%,input=none,silent, autoFilter');
        $this->FLD('departmenId', 'key(mvc=planning_Centers, select=name,allowEmpty)', 'caption=Структура, width=50px,input=none');
        $this->FLD('personId', 'key(mvc=crm_Persons,select=name,group=employees,allowEmpty)', 'caption=Служител,width=100%,input=none,');
        $this->FLD('start', 'time(suggestions=00:00|01:00|02:00|03:00|04:00|05:00|06:00|07:00|08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00|19:00|20:00|21:00|22:00|23:00,format=H:M,allowEmpty)', 'caption=Работен ден->Начало, input=none');
        $this->FLD('duration', 'time(suggestions=00|6:00|6:30|7:00|7:30|8:00|8:30|9:00|9:30|10:00|10:30|11:00|11:30|12:00,allowEmpty)', 'caption=Работен ден->Времетраене, input=none');
        $this->FLD('break',    'time(suggestions=00|0:30|00:45|1:00|00,allowEmpty)', 'caption=Работен ден->в т.ч. Почивка, input=none');  
    }
    
    
    /**
     *
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
		$data->form->setField('strukture', array("removeAndRefreshForm" => 'typeDepartmen|typePerson|departmenId|personId'));
        $data->form->setField('typeDepartmen', array("removeAndRefreshForm" => 'start|duration|break'));
        $data->form->setField('typePerson', array("removeAndRefreshForm" => 'start|duration|break'));
        
        if ($data->form->rec->strukture == 'departmenId') {
            $data->form->setField('typeDepartmen', 'input');
            $data->form->setField('typeDepartmen', 'mandatory');
            $data->form->setField('departmenId', 'input');
            $data->form->setField('departmenId', 'mandatory');
        }
        
        if ($data->form->rec->strukture == 'personId') {
            $data->form->setField('typePerson', 'input');
            $data->form->setField('typePerson', 'mandatory');
            $data->form->setField('personId', 'input');
            $data->form->setField('personId', 'mandatory');
        }

        if ($data->form->rec->typeDepartmen == 'working' || $data->form->rec->typePerson == 'working') { 
            $data->form->setField('start', 'input');
            $data->form->setField('duration', 'input');
            $data->form->setField('break', 'input');
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        // Ако имаме права да видим визитката
        if(crm_Persons::haveRightFor('single', $rec->personId) && isset($rec->personId)){
            $name = crm_Persons::fetchField("#id = '{$rec->personId}'", 'name');
            $row->personId = ht::createLink($name, array ('crm_Persons', 'single', 'id' => $rec->personId), NULL, 'ef_icon = img/16/vcard.png');
        }
        
        // Ако имаме права да видим департамента
        if(planning_Centers::haveRightFor('single', $rec->departmenId) && isset($rec->departmenId)){
            $depName = planning_Centers::fetchField("#id = '{$rec->departmenId}'", 'name');
            $row->departmenId = ht::createLink($depName, array ('planning_Centers', 'single', 'id' => $rec->departmenId), NULL, 'ef_icon = img/16/user_group.png');
        }

        if(isset($rec->docClass) && isset($rec->docId)) {
            $Class = cls::get($rec->docClass);
            $title = $Class->getTitleForId($rec->docId);       
        }
        
        // Ако имаме права да видим документ болничен
        if(hr_Sickdays::haveRightFor('single', $rec->docId) && $rec->typePerson == 'sicDay'){
            $row->typePerson = ht::createLink($title, array ('hr_Sickdays', 'single', 'id' => $rec->docId), NULL, 'ef_icon = img/16/sick.png');
        }
        
        // Ако имаме права да видим документ командировка
        if(hr_Trips::haveRightFor('single', $rec->docId) && $rec->typePerson == 'traveling'){
            $row->typePerson = ht::createLink($title, array ('hr_Trips', 'single', 'id' => $rec->docId), NULL, 'ef_icon = img/16/working-travel.png');
        }
        
        
        if($rec->typePerson) {
            $type = $rec->typePerson;
        } else {
            $type = $rec->typeDepartmen;
        }
        
        if($type == "working" || $type == "nonworking") {
            $typeRow = static::$map[$type];
        }
        
        if(isset($row->departmenId)) {
            $row->str = $row->departmenId;
        }
        
        if(isset($row->personId)) {
            $row->str = $row->personId;
        }
        
        if(isset($row->typePerson)) {
            $row->type = $row->typePerson;
        } else {
            $row->type = $typeRow;
        }
    }
    
    
    /**
     * Обновява събитията в персонални работни цикли
     *
     * @param  array     $events      Масив със събития
     * @param  date      $fromDate    Начало на периода за който се отнасят събитията
     * @param  date      $fromDate    Край на периода за който се отнасят събитията
     * @param  string    $prefix      Префикс на ключовете за събитията от този източник
     *
     * @return array                  Статус на операцията, който съдържа:
     *      о ['updated'] броя на обновените събития
     *
     */
    public static function updateEvents($events, $fromDate, $toDate, $prefix)
    {
        $query    = self::getQuery();
        $fromTime = $fromDate . ' 00:00:00';
        $toTime   = $toDate   . ' 23:59:59';
    
        $query->where("#date >= '{$fromTime}' AND #date <= '{$toTime}' AND #key LIKE '{$prefix}%'");
    
        // Извличаме съществуващите събития за този префикс
        $exEvents = array();
        while($rec = $query->fetch()) {
            $exEvents[$rec->key] = $rec;
        }
    
        // Инициализираме резултатния масив
        $res = array(
            'new' => 0,
            'updated' => 0,
            'deleted' => 0
        );
    
        // Обновяваме информацията за новопостъпилите събития
        if(count($events)) {
            foreach($events as $e) {

                if(($e->id = $exEvents[$e->key]->id) ||
                    ($e->id = self::fetchField(array("#key = '[#1#]'", $e->key), 'id')) ) {
                        unset($exEvents[$e->key]);
                        $res['updated']++;
                    } else {
                        $res['new']++;
                    }

                    self::save($e);
            }
        }
    
        // Изтриваме старите записи, които не са обновени
        foreach($exEvents as $e) {
            self::delete("#key = '{$e->key}'");
            $res['deleted']++;
        }
    
        return $res;
    }
    
    
    /**
     * По зададени  отдел/служител и начална и крайна дата изчислява
     * работни,неработните дни м/у двете дати според работния график
     * изчислява още тези дни и в секунди
     *
     * @param int $departmentId - отдел
     * @param int $personId - служител
     * @param datetime $leaveFrom - от дата
     * @param datetime $leaveTo - да дата
     *
     * @return StdClass array('nonWorking'=>неработни дни,  'leaveDay'=>дни отпуска, 'sicDay'=>дни болнични, 'tripDay'=>дни командировка, 
     *                        'workDays'=>работни дни, 'allDays'=>всичкидни в периода,'workingSecs'=>работни дни в секунди, 
     *                        'rest'=>почивни дни в секунди, 
     *                        'secsPeriod'=>целия период в секунди)
     */
    static public function calcLeaveDaysByCustomSchedule($departmentId = NULL,$personId = NULL,$leaveFrom, $leaveTo)
    {
        $nonWorking = $leaveDay = $sicDay = $tripDay = $workDays = $allDays = 0;
        
        $workingSecs = $rest = $secsPeriod =  0;
    
        // Взимаме конкретния работен график
        if($departmentId) {
            $workingCycles = planning_Centers::fetchField($departmentId, 'schedule');
            $masterId = $departmentId;
        }
        
        if($personId) {
            $data = new stdClass();
            $data->masterData = new stdClass();
            $data->masterMvc = cls::get('crm_Persons');
            $data->masterId = (string) $personId;

            $personsDetails = cls::get('crm_PersonsDetails');
            $personsDetails->preparePersonsDetails($data);
            
            $workingCycles = planning_Centers::fetchField($data->Cycles->masterId, 'schedule');
            $masterId = $data->Cycles->masterId;
           
        }
        
        $state = hr_WorkingCycles::getQuery();
        $state->where("#id='{$workingCycles}'");
        $cycleDetails = $state->fetch();

        // Намираме кога започва графика
        $startingOn = planning_Centers::fetchField($workingCycles,'startingOn');
     
        // Работен цикъл
        $workingCyclesCls = cls::get('hr_WorkingCycles');
        
        $data = new stdClass();
        $data->Cycles = new stdClass();
        $data->masterId = $masterId;
        $data->Cycles->masterId = $masterId;
        
        if($personId) {
            $data->Cycles->personId = $personId;
        }

        $days = $workingCyclesCls->prepareGrafic($data, $curDate)->d;
            
        // Проверяваме всеки ден
        // дали е работен или не
        foreach($days as $day) { 
            $curDate = substr(strstr($day->url, "="), 1);
            // В кой ден от цикъла сме
            $dayIs = (dt::daysBetween($curDate, $startingOn) + 1) % $cycleDetails->cycleDuration;
            
            // Извличане на данните за циклите
            $scheduleDetails = hr_WorkingCycleDetails::getQuery();
            
            // Подробности за конкретния цикъл
            $scheduleDetails->where("#cycleId='{$workingCycles}' AND #day='{$dayIs}'");
            
            // Взимаме записа на точно този избран ден от цикъла
            // Кога за почва режима и с каква продължителност е
            $details = $scheduleDetails->fetch();
            $dayStart = $details->start;
            $dayDuration = $details->duration;
            $dayBreak = $details->break;
            
            if($day->type == 0) {
                $nonWorking++;
                $rest += 24*60*60;
            } elseif($day->type == 5) {
                $leaveDay++;
                $rest += 24*60*60;
            } elseif($day->type == 6) {
                $sicDay++;
                $rest += 24*60*60;
            } elseif($day->type == 7) {
                $tripDay++;
                $workingSecs += $dayDuration - $dayBreak;
            } else {
                $workingSecs += $dayDuration - $dayBreak;
                $workDays++;
            }

            $allDays++;
        }
        
        $secsPeriod = $allDays * (24*60*60);
    
        return (object) array('nonWorking'=>$nonWorking,  'leaveDay'=>$leaveDay, 'sicDay'=>$sicDay, 'tripDay'=>$tripDay, 'workDays'=>$workDays, 'allDays'=>$allDays,'workingSecs'=>$workingSecs, 'rest'=>$rest, 'secsPeriod'=>$secsPeriod);
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
                if($newDate <= $obj->date) {
    
                    //ъпдейтване на събитието
                    $obj->stateInfo = 'leaveDay';
                    $obj->stateDateFrom = $recLeave->leaveFrom;
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
     * Изпълнява се след начално установяване
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        // Нагласяне на Крон
        $rec = new stdClass();
        $rec->systemId = "colectDaysType";
        $rec->description = "Събиране на информацията за персоналния вид на деня";
        $rec->controller = "hr_CustomSchedules";
        $rec->action = "SetPersonDayType";
        $rec->period = 100;
        $rec->offset = 0;
        $res .= core_Cron::addOnce($rec);
    }
}
