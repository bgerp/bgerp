<?php


/**
 * Клас 'wtime_Summary'
 * Обобщена информация за разботното време на всеки служител
 *
 * @category  bgerp
 * @package   wtime
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class wtime_Summary extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Обобщение за работно време';


    /**
     * Кой  може да пише?
     */
    public $canWrite = 'no_one';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, wtime';


    /**
     * Кой може да добавя?
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да редактира?
     */
    public $canEdit = 'no_one';


    /**
     * Кой може да редактира?
     */
    public $canRecalc = 'ceo, wtime';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'wtime_Wrapper,plg_Sorting,plg_SelectPeriod,plg_GroupByField';


    /**
     * По-кое поле да се групират листовите данни
     */
    public $groupByField = 'date';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'date,personId,scheduleId=График,onSiteTime,onSiteTimeOffSchedule,onSiteTimeOnHolidays,onSiteTimeOnNonWorkingDays,onSiteTimeNightShift,onlineTime,onlineTimeRemote,onlineTimeOffSchedule,lastCalced';


    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'hr_IndicatorsSourceIntf';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('personId', 'key2(mvc=crm_Persons,select=name,allowEmpty)', 'caption=Служител,silent');
        $this->FLD('date', 'date', 'caption=Ден');
        $this->FLD('onSiteTime', 'time(noSmart,uom=minutes)', 'caption=Време в завода/офиса->На място');
        $this->FLD('onSiteTimeOffSchedule', 'time(noSmart,uom=minutes)', 'caption=Време в завода/офиса->Извънработно');
        $this->FLD('onSiteTimeOnHolidays', 'time(noSmart,uom=minutes)', 'caption=Време в завода/офиса->Празници');
        $this->FLD('onSiteTimeOnNonWorkingDays', 'time(noSmart,uom=minutes)', 'caption=Време в завода/офиса->Неработни');
        $this->FLD('onSiteTimeNightShift', 'time(noSmart,uom=minutes)', 'caption=Време в завода/офиса->Нощем');
        $this->FLD('onlineTime', 'time(noSmart,uom=minutes)', 'caption=Онлайн->Общо');
        $this->FLD('onlineTimeRemote', 'time(noSmart,uom=minutes)', 'caption=Онлайн->Хоум офис');
        $this->FLD('onlineTimeOffSchedule', 'time(noSmart,uom=minutes)', 'caption=Онлайн->Извънработно');
        $this->FLD('lastCalced', 'datetime(format=smartTime)', 'caption=Изчисляване');

        $this->setDbUnique('personId,date');
        $this->setDbIndex('date');
        $this->setDbIndex('personId');
    }


    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->groupByFieldStyles = 'background-color:rgba(0, 0, 255, 0.1);';
        $data->listFilter->FLD('from', 'date', 'caption=От,silent');
        $data->listFilter->FLD('to', 'date', 'caption=До,silent');
        $data->listFilter->FLD('type', 'enum(all=Всички,onSiteTime=На място,onSiteTimeOffSchedule=Извънработно,onSiteTimeOnHolidays=Празници,onSiteTimeOnNonWorkingDay=Неработни,onSiteTimeNightShift=Нощем,onlineTime=Онлайн,onlineTimeRemote=Хоум офис,onlineTimeOffSchedule=Онлайн извънработно)', 'caption=Време,silent');
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->class = 'simpleForm';
        $data->listFilter->defOrder = false;
        $data->listFilter->showFields = 'selectPeriod,personId,from,to,type';
        $data->listFilter->input(null, 'silent');
        $data->listFilter->input();
        $data->query->orderBy('date', 'DESC');

        if($filter = $data->listFilter->rec){
            if(isset($filter->personId)){
                $data->query->where("#personId = {$filter->personId}");
            }

            if (!empty($filter->from)) {
                $data->query->where("#date >= '{$filter->from}'");
            }

            if (!empty($filter->to)) {
                $data->query->where("#date <= '{$filter->to}'");
            }

            if (!empty($filter->type) && $filter->type != 'all') {
                $data->query->where("#{$filter->type} != 0");
            }
        }
    }


    /**
     * Вербализиране на row
     * Поставя хипервръзка на ip-то
     */
    protected function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        if(isset($rec->personId)){
            $personName = crm_Persons::getVerbal($rec->personId, 'name');
            $row->personId = ht::createLink($personName, crm_Persons::getSingleUrlArray($rec->personId));
            $scheduleId = planning_Hr::getSchedule($rec->personId);
            $row->scheduleId = hr_Schedules::getHyperlink($scheduleId, true);
        }
        $row->ROW_ATTR['class'] = ($rec->_isSummary) ? 'state-closed' : 'state-active';

        foreach(arr::make('onSiteTime,onSiteTimeOnHolidays,onSiteTimeOnNonWorkingDays,onSiteTimeNightShift,onSiteTimeOffSchedule,onlineTime,onlineTimeRemote,onlineTimeOffSchedule', true) as $fld){
            if(empty($rec->{$fld})){
                unset($row->{$fld});
            } else {
                $row->{$fld} = ht::styleNumber($row->{$fld}, $rec->{$fld});
            }
        }
    }


    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        if ($mvc->haveRightFor('recalc')) {
            $data->toolbar->addBtn('Преизчисляване', array($mvc, 'recalc', 'ret_url' => true), 'ef_icon=img/16/arrow_refresh.png, title=Преизчисляване');
        }
    }


    /**
     * Преизчисляване на записите
     */
    public function act_Recalc()
    {
        $this->requireRightFor('recalc');

        $form = cls::get('core_Form');
        $form->title = "Преизчисляване на обобщенията";
        $form->FLD('from', 'date', 'caption=От,mandatory');
        $form->setDefault('from', dt::addDays(-2, null, false) . " 00:00:00");
        $form->input();

        if ($form->isSubmitted()) {
            $rec = $form->rec;
            $msg = "Преизчислени са записите след|*: <b>" . dt::mysql2verbal($rec->from) . "</b>";
            if(in_array($form->cmd, array('debug', 'save'))){
                $res = self::recalc($rec->from);
                if($form->cmd == 'debug'){
                    bp($res);
                }
            } elseif($form->cmd == 'truncate'){
                $this->truncate();
                $msg = 'Таблицата е изпразнена';
            }

            followRetUrl(null, $msg);
        }

        // Добавяне на бутони
        $form->toolbar->addSbBtn('Преизчисляване', 'save', 'ef_icon = img/16/arrow_refresh.png, title = Преизчисляване на обобщенията');
        if(haveRole('debug')){
            $form->toolbar->addSbBtn('Дебъг', 'debug', 'ef_icon = img/16/bug.png, title = Дебъгване');
            $form->toolbar->addSbBtn('Изчистване', 'truncate', 'warning=Наистина ли желаете да изпразните таблицата,ef_icon = img/16/arrow_refresh.png, title = Преизчисляване на обобщенията');
        }

        $form->toolbar->addSbBtn('Преизчисляване', 'save', 'ef_icon = img/16/arrow_refresh.png, title = Преизчисляване на обобщенията');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');

        // Записваме, че потребителя е разглеждал този списък
        $this->logInfo('Разглеждане на формата за преизчисляване на обобщенията');

        return $this->renderWrapping($form->renderHtml());
    }


    /**
     * Преизчислява обощенията
     *
     * @param string $from       - от кога
     * @param string|null $to    - до кога
     * @param int|null $personId - ид на лице
     * @return array
     */
    public static function recalc($from, $to = null, $personId = null)
    {
        $to = $to ?? dt::today();
        $scheduleTo = "{$to} 23:59:59";
        $calcFromTime = "{$from} 00:00:00";

        $newRecs = $Schedules = $dateStatus = array();
        core_Debug::startTimer('CAL_ON_SITE_TIME');
        wtime_OnSiteEntries::calcOnSiteTime($calcFromTime, $personId);
        core_Debug::stopTimer('CAL_ON_SITE_TIME');
        core_Debug::log("GET CAL_ON_SITE_TIME " . round(core_Debug::$timers["CAL_ON_SITE_TIME"]->workingTime, 6));

        $entriesArr = wtime_OnSiteEntries::getPersonEntries($calcFromTime, $personId, 'in');
        core_App::setTimeLimit(countR($entriesArr) * 0.2, false, 150);

        // Какъв е статуса на дните от тогава до сега
        $sDate = $from;
        $daysBetween = dt::daysBetween($to, dt::verbal2mysql($from, false));
        for($i = 0; $i < $daysBetween; $i++){
            $sDate = dt::addDays(1, $sDate, false);
            $dateStatus[$sDate] = cal_Calendar::getDayStatus($sDate);
        }

        $toClone = new stdClass();
        foreach(arr::make('onSiteTime,onSiteTimeOnHolidays,onSiteTimeOnNonWorkingDays,onSiteTimeNightShift,onSiteTimeOffSchedule,onlineTime,onlineTimeRemote,onlineTimeOffSchedule', true) as $fld){
            $toClone->{$fld} = 0;
        }

        // Начало и край на нощната смяна
        $nightShiftNextDayEnd = hr_Setup::get('NIGHT_SHIFT_NEXT_DAY_END');
        $nightShiftCurrentDayStart = hr_Setup::get('NIGHT_SHIFT_CUR_DAY_START');

        // За всеки запис за всяко лице
        foreach ($entriesArr as $pId => $entries) {

            // Какъв му е графика
            ksort($entries);

            core_Debug::startTimer('SCHEDULES');
            $scheduleId = planning_Hr::getSchedule($pId);
            $Schedules[$pId] = hr_Schedules::getWorkingIntervals($scheduleId, $calcFromTime, $scheduleTo);
            $Schedule = $Schedules[$pId];
            core_Debug::stopTimer('SCHEDULES');

            // За всяко влизане от вчера и днеска
            foreach ($entries as $entry) {
                core_Debug::startTimer('ON_SITE');
                $date = dt::verbal2mysql($entry->time, false);

                // Ще се сумират по лице+дата
                $key = "{$pId}|{$date}";
                if(!array_key_exists($key, $newRecs)){
                    $clone = clone $toClone;
                    $clone->date = $date;
                    $clone->personId = $pId;
                    $newRecs[$key] = $clone;
                }

                // Сумира се прекараното работно време на място и това, което е извън графика работено на място
                if(!empty($entry->onSiteTime)){
                    $newRecs[$key]->onSiteTime += $entry->onSiteTime;

                    // Ако денят е празник - сумира се колко е работено
                    if($dateStatus[$date]->isHoliday){
                        $newRecs[$key]->onSiteTimeOnHolidays += $entry->onSiteTime;

                        // Ако е неработен или почивен ден
                    } elseif(in_array($dateStatus[$date]->specialDay, array('non-working', 'weekend', 'saturday', 'sunday'))){

                        // И не му е част от графика - тогава се добавя като работа в извънработно време
                        if(!$Schedule->isIn($entry->time)){
                            $newRecs[$key]->onSiteTimeOnNonWorkingDays += $entry->onSiteTime;
                        }
                    }
                }

                // Колко е работено в извън работно време
                core_Debug::startTimer('ON_SITE_OFF_SCHEDULE');
                $onSiteTimeOffSchedule = wtime_OnSiteEntries::getOffScheduleTime($personId, $date, $entry->time, $entry->onSiteTime, $Schedule);
                core_Debug::stopTimer('ON_SITE_OFF_SCHEDULE');

                if(!empty($onSiteTimeOffSchedule)){
                    $newRecs[$key]->onSiteTimeOffSchedule = $onSiteTimeOffSchedule;
                }

                // Кога е нощната смяна
                $prevDay = dt::addDays(-1, $entry->time, false);
                $nsStartTs = strtotime("{$prevDay} {$nightShiftCurrentDayStart}");
                $nsEndTs   = strtotime("{$date} {$nightShiftNextDayEnd}");

                // Колко от прекараното време е в нощната смяна
                $startTs = strtotime($entry->time);
                $endTs   = $startTs + $entry->onSiteTime;

                $ovlStart = max($startTs, $nsStartTs);
                $ovlEnd   = min($endTs,   $nsEndTs);

                // Сумира се работеното време в нощна смяна, ако има такава
                $nighttime = max(0, $ovlEnd - $ovlStart);
                if(!empty($nighttime)){
                    $newRecs[$key]->onSiteTimeNightShift += $nighttime;
                }

                core_Debug::stopTimer('ON_SITE');
            }
        }

        // Извличат се лицата от група служители, които имат потребители
        $userPersons = array();
        $pQuery = crm_Profiles::getQuery();
        $pQuery->show('personId,userId');
        $noTrackUsers = core_Users::getUsersByRoles('noTrackonline');
        if(count($noTrackUsers)){
            $pQuery->notIn("userId", array_keys($noTrackUsers));
        }
        if(isset($personId)){
            $pQuery->where("#personId = {$personId}");
        } else {
            $employeeGroupId = crm_Groups::getIdFromSysId('employees');
            plg_ExpandInput::applyExtendedInputSearch('crm_Persons', $pQuery, $employeeGroupId, 'personId');
        }
        while($pRec = $pQuery->fetch()){
            $userPersons[$pRec->userId] = $pRec->personId;
        }

        $userIds = array_keys($userPersons);
        $logArr = array();

        // Ако има лица от група Служители
        if(countR($userIds)){
            core_Debug::startTimer('USER_LOGS');

            // Извличане на логовете за тези потребители след посоченото време
            $lQuery = log_Data::getQuery();
            $lQuery->EXT('ip', 'log_Ips', 'externalName=ip,externalKey=ipId');
            $lQuery->EXT('roles', 'core_Users', 'externalName=roles,externalKey=userId');
            $lQuery->EXT('userAgent', 'log_Browsers', 'externalName=userAgent,externalKey=brId');
            $lQuery->where(array("#time >= '[#1#]' AND #type != 'login'", dt::mysql2timestamp($calcFromTime)));
            $lQuery->in('userId', $userIds);
            $lQuery->show('userId,type,ip,time,userAgent,roles');
            $lQuery->orderBy('time', 'ASC');
            $lRecs = $lQuery->fetchAll();

            core_App::setTimeLimit(countR($lRecs) * 0.08, false, 150);
            foreach ($lRecs as $lRec) {
                $logArr[$lRec->userId][$lRec->time] = (object)array('time' => $lRec->time, 'type' => $lRec->type, 'ip' => $lRec->ip, 'userId' => $lRec->userId, 'userAgent' => $lRec->userAgent);

            }
            core_Debug::stopTimer('USER_LOGS');

            $readStickMin = wtime_Setup::get('READ_STICK_MIN');
            $writeStickMin = wtime_Setup::get('WRITE_STICK_MIN');
            $wExcludeLocalMin = wtime_Setup::get('EXCLUDE_LOCAL_MIN');

            // Кои са нашите фирмите на нашите офиси
            $ourIps = wtime_Setup::get('SITE_IPS');
            $ipArr = type_Ip::extractIps($ourIps);

            foreach ($logArr as $userId => $logs) {
                ksort($logs);

                $pId = $userPersons[$userId];
                if(!array_key_exists($pId, $Schedules)){
                    core_Debug::startTimer('SCHEDULES');
                    $scheduleId = planning_Hr::getSchedule($pId);
                    $Schedules[$pId] = hr_Schedules::getWorkingIntervals($scheduleId, $calcFromTime, $scheduleTo);
                    core_Debug::stopTimer('SCHEDULES');
                }

                core_Debug::startTimer('CALC_ONLINE_TIME');
                $calcedOnlineTime = self::calculateDailyUserTime($pId, $logs, $ipArr, $wExcludeLocalMin, $readStickMin, $writeStickMin, $Schedules[$pId]);
                core_Debug::stopTimer('CALC_ONLINE_TIME');

                foreach ($calcedOnlineTime as $date => $status){
                    $key = "{$pId}|{$date}";
                    if(!array_key_exists($key, $newRecs)){
                        $clone = clone $toClone;
                        $clone->date = $date;
                        $clone->personId = $pId;
                        $newRecs[$key] = $clone;
                    }

                    if(!empty($status['online'])){
                        $newRecs[$key]->onlineTime += $status['online'];
                    }

                    if(!empty($status['remote'])){
                        $newRecs[$key]->onlineTimeRemote += $status['remote'];
                    }

                    if(!empty($status['offSchedule'])){
                        $newRecs[$key]->onlineTimeOffSchedule += $status['offSchedule'];
                    }
                }
            }
        }

        core_Debug::log("GET SCHEDULES " . round(core_Debug::$timers["SCHEDULES"]->workingTime, 6));
        core_Debug::log("GET CALC_ONLINE_TIME " . round(core_Debug::$timers["CALC_ONLINE_TIME"]->workingTime, 6));
        core_Debug::log("GET USER_LOGS " . round(core_Debug::$timers["USER_LOGS"]->workingTime, 6));
        core_Debug::log("GET ON_SITE " . round(core_Debug::$timers["ON_SITE"]->workingTime, 6));
        core_Debug::log("GET ON_SITE_OFF_SCHEDULE " . round(core_Debug::$timers["ON_SITE_OFF_SCHEDULE"]->workingTime, 6));

        $exQuery = self::getQuery();
        $exQuery->where("#date >= '{$from}'");
        if(isset($personId)){
            $pQuery->where("#personId = {$personId}");
        }

        $now = dt::now();
        foreach ($newRecs as &$r){
            $r->lastCalced = $now;
        }

        $exQuery->orderBy('id', 'ASC');
        $exRecs = $exQuery->fetchAll();
        $syncedArr = arr::syncArrays($newRecs, $exRecs, 'personId,date', 'onSiteTime,onSiteTimeOnHolidays,onSiteTimeOnNonWorkingDays,onSiteTimeNightShift,onSiteTimeOffSchedule,onlineTime,onlineTimeRemote,onlineTimeOffSchedule');

        $me = cls::get(get_called_class());
        if(countR($syncedArr['insert'])){
            $me->saveArray($syncedArr['insert']);
        }

        if(countR($syncedArr['update'])){
            $me->saveArray($syncedArr['update'], 'id,onSiteTime,onSiteTimeOnHolidays,onSiteTimeOnNonWorkingDays,onSiteTimeNightShift,onSiteTimeOffSchedule,onlineTime,onlineTimeRemote,onlineTimeOffSchedule,lastCalced');
        }

        if(countR($syncedArr['delete'])){
            $inStr = implode(',', $syncedArr['delete']);
            $me->delete("#id IN ({$inStr})");
        }

        return array('entries' => $entriesArr, 'logs' => $logArr);
    }


    /**
     * Пресмята дневно „онлайн“ и „remote“ време за един потребител.
     *
     * @param int   $personId         - лице
     * @param array $logs             - логове
     * @param array $ourIpArr         - масив от фирмени IP-та (стрингове или CIDR)
     * @param int   $readStickMin     - минимално за четене
     * @param int   $writeStickMin    - минимално за писане
     * @param int   $wExcludeLocalMin - WTIME_EXCLUDE_LOCAL_MIN в минути
     * @param int|null  $Schedule     - инстанция на график на лицето ако има
     * @return array
     */
    public static function calculateDailyUserTime($personId, $logs, $ourIpArr, $wExcludeLocalMin, $readStickMin, $writeStickMin, $Schedule = null)
    {
        // 1) групиране по дни
        $byDay = array();
        foreach ($logs as $entry) {
            $ts  = (int)$entry->time;
            $day = gmdate('Y-m-d', $ts);
            $byDay[$day][] = array('ts'        => $ts,
                                   'type'      => $entry->type,
                                   'ip'        => $entry->ip,
                                   'userAgent' => $entry->userAgent ?? '',
            );
        }

        $daily = array();

        // 2) за всеки ден пресмятаме
        foreach ($byDay as $day => $entries) {
            usort($entries, function($a, $b) {
                return $a['ts'] <=> $b['ts'];
            });

            $online  = $remote = $offSchedule = 0;
            $prevTs     = null;
            $lastCorpTs = null;

            foreach ($entries as $e) {
                $ts   = $e['ts'];
                $type = $e['type'];
                $ip   = $e['ip'];
                $ua   = $e['userAgent'];

                // stick лимит според типа
                $stickMin = $type == 'read' ? $readStickMin : $writeStickMin;

                // базово време: 1 минута (60 сек)
                $addedSec = 60;
                if ($prevTs !== null) {
                    $dmin = floor(($ts - $prevTs) / 60);
                    if ($dmin > 0 && $dmin <= $stickMin) {
                        $addedSec = $dmin * 60;
                    }
                }

                core_Debug::startTimer('ON_SITE_OFF_SCHEDULE');
                $offSchedule =+ wtime_OnSiteEntries::getOffScheduleTime($personId, $day, dt::timestamp2Mysql($ts), $addedSec, $Schedule);
                core_Debug::stopTimer('ON_SITE_OFF_SCHEDULE');
                $online += $addedSec;

                // фирмено IP?
                $isCorp = type_Ip::isInIps($ip, $ourIpArr);

                // мобилно устройство?
                $isMobile = preg_match('/Mobile|Android|iPhone|iPad/i', $ua) === 1;

                // изключване локално след фирмен запис
                $withinExclude = $lastCorpTs !== null
                    && ($ts - $lastCorpTs) <= $wExcludeLocalMin * 60;

                // добавяме към remote, ако условието е изпълнено
                if (!$isCorp && (!$isMobile || !$withinExclude)) {
                    $remote += $addedSec;
                }

                // обновяваме времето на последен фирмен хит
                if ($isCorp) {
                    $lastCorpTs = $ts;
                }

                $prevTs = $ts;
            }

            $daily[$day] = array('online' => $online, 'remote' => $remote, 'offSchedule' => $offSchedule);
        }

        return $daily;
    }


    /**
     * Преизчисляване на обобщението по разписание
     */
    function cron_Calc()
    {
        $start = dt::addDays(-2, dt::today(), false);

        self::recalc($start);
    }


    /**
     * Интерфейсен метод на hr_IndicatorsSourceIntf
     *
     * @return array $result
     */
    public static function getIndicatorNames()
    {
        $result = array();

        // Показател за делта на търговеца
        $indicatorNames = array('Работно_време_на_място',
                                'Работно_време_на_място_извън_графика',
                                'Онлайн_работно_време',
                                'Отдалечено_онлайн_работно_време',
                                'Онлайн_работно_време_извън_графика',
        );

        $counter = 1;
        foreach ($indicatorNames as $iName){
            $rec = hr_IndicatorNames::force($iName, __CLASS__, $counter);
            $result[$rec->id] = $rec->name;
            $counter++;
        }

        return $result;
    }

    /**
     * Метод за вземане на резултатност на хората. За определена дата се изчислява
     * успеваемостта на човека спрямо ресурса, които е изпозлвал
     *
     * @param datetime $timeline - Времето, след което да се вземат всички модифицирани/създадени записи
     *
     * @return array $result  - масив с обекти
     *
     * 			o date        - дата на стайноста
     * 		    o personId    - ид на лицето
     *          o docId       - ид на документа
     *          o docClass    - клас ид на документа
     *          o indicatorId - ид на индикатора
     *          o value       - стойноста на индикатора
     *          o isRejected  - оттеглена или не. Ако е оттеглена се изтрива от индикаторите
     */
    public static function getIndicatorValues($timeline)
    {
        $map  = array('Работно_време_на_място' => 'onSiteTime',
                     'Работно_време_на_място_извън_графика' => 'onSiteTimeOffSchedule',
                     'Онлайн_работно_време' => 'onlineTime',
                     'Отдалечено_онлайн_работно_време' => 'onlineTimeRemote',
                     'Онлайн_работно_време_извън_графика' => 'onlineTimeOffSchedule',
            );
        $indicators = array_flip(self::getIndicatorNames());

        // Извличане на променените обобщения след посоченото време
        $result = array();
        $query = self::getQuery();
        $query->where("#lastCalced >= '{$timeline}'");
        $classId = cls::get(get_called_class())->getClassId();
        while($rec = $query->fetch()) {

            // За всеки индикатор се сумира стойноста на съответното поле
            foreach ($indicators as $iName => $iId){
                $key = "{$rec->personId}|{$rec->date}|{$iId}";
                if (!array_key_exists($key, $result)) {
                    $result[$key] = (object) array('date'        => $rec->date,
                                                   'personId'    => $rec->personId,
                                                   'indicatorId' => $iId,
                                                   'docId' => 0,
                                                   'docClass' => $classId,
                                                   'value'       => 0);
                }

                $result[$key]->value += $rec->{$map[$iName]};
            }
        }

        return $result;
    }


    /**
     * Подготовка на съмърито
     *
     * @param stdClass $data
     * @return void
     */
    public function prepareSummary(&$data)
    {
        $data->recs = $data->rows = array();

        foreach (array('totalThisMonth', 'totalLastMonth') as $var){
            $data->{$var} = new stdClass();
            $data->{$var}->_isSummary = true;
            foreach(arr::make('onSiteTime,onSiteTimeOnHolidays,onSiteTimeOnNonWorkingDays,onSiteTimeNightShift,onSiteTimeOffSchedule,onlineTime,onlineTimeRemote,onlineTimeOffSchedule', true) as $fld){
                $data->{$var}->{$fld} = 0;
            }
        }

        // Извличат се записите от съмърито за лицето
        $query = self::getQuery();
        $query->where("#personId = {$data->masterId}");
        $query->orderBy('date', 'DESC');
        $firstDay = date('Y-m-01', strtotime('first day of this month'));
        $firstDayPrevMonth = date('Y-m-01', strtotime('first day of last month'));
        $lastDayPrevMonth = dt::getLastDayOfMonth($firstDayPrevMonth);
        $data->Pager = cls::get('core_Pager', array('itemsPerPage' => 10));
        $data->Pager->setPageVar($data->masterMvc->className, $data->masterId);
        $data->Pager->itemsCount = $query->count();

        $this->prepareListFields($data);
        while($rec = $query->fetch()) {
            $data->recs[$rec->id] = $rec;
            if (!$data->Pager->isOnPage()) continue;
            $data->rows[$rec->id] = self::recToVerbal($rec);

            if($rec->date >= $firstDay) {
                foreach(arr::make('onSiteTime,onSiteTimeOnHolidays,onSiteTimeOnNonWorkingDays,onSiteTimeNightShift,onSiteTimeOffSchedule,onlineTime,onlineTimeRemote,onlineTimeOffSchedule', true) as $fld){
                    $data->totalThisMonth->{$fld} += $rec->{$fld};
                }
            }

            if($rec->date >= $firstDayPrevMonth && $rec->date <= $lastDayPrevMonth) {
                foreach(arr::make('onSiteTime,onSiteTimeOnHolidays,onSiteTimeOnNonWorkingDays,onSiteTimeNightShift,onSiteTimeOffSchedule,onlineTime,onlineTimeRemote,onlineTimeOffSchedule', true) as $fld){
                    $data->totalLastMonth->{$fld} += $rec->{$fld};
                }
            }
        }

        if(isset($data->totalThisMonth)){
            $data->totalThisMonthRow = $this->recToVerbal($data->totalThisMonth);
            $data->totalThisMonthRow->date = "<b>" . dt::mysql2verbal($firstDay, 'M') . "</b> (" . tr('Общо') . ")";
        }

        if(isset($data->totalLastMonth)){
            $data->totalLastMonthRow = $this->recToVerbal($data->totalLastMonth);
            $data->totalLastMonthRow->date = "<b>" . dt::mysql2verbal($firstDayPrevMonth, 'M') . "</b> (" . tr('Общо') . ")";
        }
    }


    /**
     * Рендиране на съмърито
     *
     * @param stdClass $data
     * @return core_ET $tpl
     */
    public function renderSummary($data)
    {
        $tpl = new core_ET("");
        unset($data->listFields['personId']);
        unset($data->listFields['lastCalced']);
        unset($data->listFields['scheduleId']);

        $table = cls::get('core_TableView', array('mvc' => $this));
        $data->rows[] = $data->totalThisMonthRow;
        $data->rows[] = $data->totalLastMonthRow;
        $dTable = $table->get($data->rows, $data->listFields);

        //$dTable->append(, ROW_AFTER'')
        $tpl->append($dTable);
        if ($data->Pager) {
            $tpl->append($data->Pager->getHtml());
        }

        if(wtime_Summary::haveRightFor('list')){
            $listBtn = ht::createLink('', array($this, 'list', 'personId' => $data->masterId), false, 'ef_icon=img/16/funnel.png,title=Филтър на информацията за лицето');
            $tpl->append($listBtn, 'wTimeFilter');
        }

        return $tpl;
    }
}