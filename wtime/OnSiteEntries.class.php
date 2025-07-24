<?php


/**
 * Клас 'wtime_OnSiteEntries'
 * Клас-мениджър за записи за вход/изход на място
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
class wtime_OnSiteEntries extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Записи за вход/изход';


    /**
     * Кой  може да пише?
     */
    public $canWrite = 'ceo, wtime';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, wtime';


    /**
     * Кой може да добавя?
     */
    public $canAdd = 'ceo, wtime';


    /**
     * Кой може да редактира?
     */
    public $canEdit = 'ceo, wtime';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools,plg_Created,plg_Search,wtime_Wrapper,plg_SelectPeriod';


    /**
     * Полета, по които ще се търси
     */
    public $searchFields = "personId,type,place";


    /**
     * Кои записи да се рекалкулират на шътдаун
     */
    protected $recalcOnShutdown = array();


    /**
     * Кои полета да се извличат при изтриване
     */
    public $fetchFieldsBeforeDelete = 'id,personId';


    /**
     * @var string
     */
    public $canTrackonline = 'user';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('time', 'datetime(format=smartTime)', 'caption=Време,mandatory');
        $this->FLD('personId', 'key2(mvc=crm_Persons,select=names,allowEmpty)', 'caption=Служител,mandatory');
        $this->FLD('type', 'enum(in=Влиза,out=Излиза)', 'caption=Вид');
        $this->FLD('place', 'varchar(64)', 'caption=Място,mandatory,tdClass=leftCol');
        $this->FLD('onSiteTime', 'time(noSmart,uom=minutes)', 'caption=Време на място,input=none');
        $this->FLD('sourceClassId', 'class', 'caption=Източник,input=none');

        $this->setDbIndex('time');
        $this->setDbIndex('personId');
        $this->setDbIndex('time,personId');
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;

        $emplGroupId = crm_Groups::getIdFromSysId('employees');
        $form->setFieldTypeParams('personId', array('groups' => keylist::addKey('', $emplGroupId)));
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $rec = $form->rec;
            $rec->_fromForm = true;

            if($rec->time > dt::now()){
                $form->setError('time', "Датата не може да е в бъдешето|*!");
            }
        }
    }


    /**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $fields = null, $mode = null)
    {
        if($rec->_fromForm){
            $mvc->recalcOnShutdown[] = $rec;
        }
    }


    /**
     * След изтриване на запис
     */
    protected static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {
        foreach ($query->getDeletedRecs() as $rec) {
            $mvc->recalcOnShutdown[] = $rec;
        }
    }


    /**
     * Рутинни действия, които трябва да се изпълнят в момента преди терминиране на скрипта
     */
    protected static function on_AfterSessionClose($mvc)
    {
        // За кои лица ще се преизчисли обобщението
        foreach ($mvc->recalcOnShutdown as $rec){
            $from = dt::addDays(-1, $rec->time, false);

            wtime_Summary::recalc($from, null, $rec->personId);
            $fromVerbal = dt::mysql2verbal($from, 'd.m.y');
            core_Statuses::newStatus("Преизчислено е обобщението от|* <b>{$fromVerbal}</b> |на|* <b>" . crm_Persons::getVerbal($rec->personId, 'name'). "</b>");
        }
    }


    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->FLD('from', 'date', 'caption=От,silent');
        $data->listFilter->FLD('to', 'date', 'caption=До,silent');
        $data->listFilter->setFieldType('type', 'enum(all=Влиза / Излиза,in=Влиза,out=Излиза)');
        $data->listFilter->setField('type', 'maxRadio=0');
        $data->listFilter->class = 'simpleForm';
        $data->listFilter->defOrder = false;
        $data->listFilter->showFields = 'selectPeriod,search,personId,type';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input();
        $data->listFilter->setDefault('type', 'all');
        $data->query->orderBy('time', 'DESC');

        if($filter = $data->listFilter->rec){
            if(isset($filter->personId)){
                $data->query->where("#personId = {$filter->personId}");
            }

            if($filter->type != 'all'){
                $data->query->where("#type = {$filter->type}");
            }

            if (!empty($filter->from)) {
                $data->query->where("#time >= '{$filter->from} 00:00:00'");
            }

            if (!empty($filter->to)) {
                $data->query->where("#time <= '{$filter->to} 23:59:59'");
            }
        }
    }


    /**
     * Вербализиране на row
     * Поставя хипервръзка на ip-то
     */
    protected function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $personName = crm_Persons::getVerbal($rec->personId, 'name');
        $row->personId = ht::createLink($personName, crm_Persons::getSingleUrlArray($rec->personId));

        $color = $rec->type == 'in' ? 'green' : 'darkred';
        $row->type = "<div style='color:{$color};font-weight:bold;'>{$row->type}</div>";

        $color = $rec->type == 'out' ? 'rgba(255, 0, 0, 0.1)' : 'rgba(0, 255, 0, 0.1)';
        $row->ROW_ATTR['style'] = "background-color:{$color};";
    }


    /**
     * Добавя нов запис за вход/изход
     *
     * @param int $personId     - ид на лице от група "Служители"
     * @param datetime $time    - кога е лицето е влязло/излязло
     * @param string $type      - 'in' или 'out'
     * @param string $zoneName  - име на зоната/мястото или null ако е онлайн
     * @param null|int $classId - ид на класа от, който е добавен записа
     *
     * @return int
     * @throws core_exception_Expect
     */
    public static function addEntry($personId, $time, $type, $zoneName, $classId = null)
    {
        expect(in_array($type, array('in', 'out')), 'Типът не е позволен');
        expect($personRec = crm_Persons::fetch($personId, 'groupList'), "Няма такова лице");

        $employeeGroupId = crm_Groups::getIdFromSysId('employees');
        expect(keylist::isIn($employeeGroupId, $personRec->groupList), 'Лицето не е в група "Служители"');

        $expectedTime = DateTime::createFromFormat('Y-m-d H:i:s', $time);
        expect($expectedTime->format('Y-m-d H:i:s') == $time, 'Датата не е във валиден формат');

        $rec = (object)array('personId' => $personId,
                             'time'     => $time,
                             'type'     => $type,
                             'place'    => $zoneName,
                             'sourceClassId'  => $classId);

        return static::save($rec);
    }


    /**
     * Връща записите групирани по лице
     *
     * @param datetime|null $from - от коя дата
     * @param int|null $personId  - ид на лице
     * @param string $type        -
     * @return array
     */
    public static function getPersonEntries($from = null, $personId = null, $type = null)
    {
        $query = static::getQuery();
        $query->orderBy('time', 'ASC');
        if(isset($from)){
            $query->where(array("#time >= '[#1#]'", $from));
        }
        if(isset($personId)){
            $query->where("#personId = {$personId}");
        }

        if(isset($type)){
            $query->where("#type = '{$type}'");
        }

        $onSiteTimes = array();
        while($rec = $query->fetch()){
            $onSiteTimes[$rec->personId][$rec->time] = $rec;
        }

        return $onSiteTimes;
    }

    /**
     * Преизчисляване на прекараното време на място.
     *
     * @param null|string $from  - от, null за всички
     * @param null|int $personId - за кое лице
     * @return void
     */
    public static function calcOnSiteTime($from = null, $personId = null)
    {
        // Извличане на заявките
        $onSiteTimes = self::getPersonEntries($from, $personId);

        $toSave = array();
        $now = dt::now();
        $nowDateTime = new DateTime();

        // Графиците ще се гледат от вчера до края на деня днес
        $scheduleTo = dt::today() . " 23:59:59";
        $scheduleFrom = dt::addDays(-1, $from, false) . " 00:00:00";

        foreach ($onSiteTimes as $personId => &$events) {

            // Сортиране на събитията
            ksort($events);

            // Извличане на графика на лицето
            $scheduleId = planning_Hr::getSchedule($personId);
            $Interval = hr_Schedules::getWorkingIntervals($scheduleId, $scheduleFrom, $scheduleTo);

            // Ако има само едно събитие
            if (countR($events) == 1) {
                $only = reset($events);

                // Ако текущото събитие е Вход и е последното за дадения служител, ако то попада в работния график
                // за дадения човек и в момента също сме в работния график - записваме към него от момента
                // на събитието до сега, като прекарано време. Иначе - 0.
                if ($only->type == 'in') {
                    $dtStart = new DateTime($only->time);
                    if ($Interval->isIn($now) && $Interval->isIn($only->time)){
                        $only->onSiteTime = $nowDateTime->getTimestamp() - $dtStart->getTimestamp();
                    } else {
                        $only->onSiteTime = 0;
                    }
                    $toSave[$only->id] = $only;
                }
                continue; // минаваме на следващия служител
            }

            // Ако има повече от един запис, обхождат се
            $list = array_values($events);
            $n    = count($list);

            for ($i = 0; $i < $n; $i++) {
                $curr = $list[$i];

                // Aко предишният е 'in', записва се времето до текущия
                if ($i > 0) {
                    $prev = $list[$i - 1];
                    if ($prev->type === 'in') {
                        $dt1 = new DateTime($prev->time);
                        $dt2 = new DateTime($curr->time);

                        if($curr->type == 'in'){
                            if($Interval->isIn($prev->time)){
                                $prev->onSiteTime = $dt2->getTimestamp() - $dt1->getTimestamp();
                                $toSave[$prev->id] = $prev;
                            } else {
                                $prev->onSiteTime = 0;
                                $toSave[$prev->id] = $prev;
                            }
                        } else {
                            $prev->onSiteTime = $dt2->getTimestamp() - $dt1->getTimestamp();
                            $toSave[$prev->id] = $prev;
                        }
                    }
                }

                //  Aко текущият е 'in' и е последен за този служител
                if ($curr->type === 'in' && $i === $n - 1) {
                    $dtStart = new DateTime($curr->time);

                    // Ако текущото събитие е Вход и е последното за дадения служител, ако то попада в работния график
                    // за дадения човек и в момента също сме в работния график - записваме към него от момента
                    // на събитието до сега, като прекарано време. Иначе - 0.
                    if ($Interval->isIn($now) && $Interval->isIn($curr->time)){
                        $curr->onSiteTime = $nowDateTime->getTimestamp() - $dtStart->getTimestamp();
                    } else {
                        $curr->onSiteTime = 0;
                    }
                    $toSave[$curr->id] = $curr;
                }
            }
        }

        // Запис на пррекараното време на място
        if(countR($toSave)){
            $me = cls::get(get_called_class());
            $me->saveArray($toSave, 'id,onSiteTime');
        }
    }


    /**
     * Изчислява работното време прекарано извън графика на служителя за дадения интервал
     *
     * @param int $personId                 - ид на служител
     * @param string $date                  - за коя дата
     * @param $startOn                      - начало
     * @param $duration                     - продължителност
     * @param core_Intervals|null $Schedule - готов интервал с графика, null - ще се вземе сега
     * @return int $offTimeSchedule         - прекарано време извън графика
     */
    public static function getOffScheduleTime($personId, $date, $startOn, $duration, core_Intervals $Schedule = null)
    {
        // Ако няма интервал търси се графика на служителя за работното му време
        if(!isset($Schedule)){
            $scheduleTo = "{$date} 23:59:59";
            $calcFromTime = dt::addDays(-1, $scheduleTo, false) . " 00:00:00";
            $scheduleId = planning_Hr::getSchedule($personId);
            $Schedule = hr_Schedules::getWorkingIntervals($scheduleId, $calcFromTime, $scheduleTo);
        }

        // Началото и края на интервала, който ще засичаме
        $begin =  strtotime($startOn);
        $end = $begin + $duration;

        $workingTimeInFrames = $Schedule->getFrame($begin, $end);
        $workTimeOnSchedule = 0;
        foreach($workingTimeInFrames as $t) {
            $workTimeOnSchedule += $t[1] - $t[0];
        }

        // От цялата продължителност се вади прекараното работно време по график - остатъка е извън графика
        $offTimeSchedule = $duration - $workTimeOnSchedule;

        return $offTimeSchedule;
    }


    /**
     * Връща последния запис за дадено лице
     *
     * @param integer $personId
     *
     * @return object|null
     */
    public static function getLastState($personId)
    {
        $query = static::getQuery();
        $query->where(array("#personId = '[#1#]'", $personId));
        $query->orderBy('time', 'DESC');
        $query->limit(1);

        if ($rec = $query->fetch()) {
            return (object)array('type' => $rec->type, 'time' => $rec->time);
        }

        return null;
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'trackonline') {
            if (haveRole('noTrackonline', $userId)) {
                $requiredRoles = 'no_one';
            }

            if ($requiredRoles != 'no_one') {
                // Ако не са позволените IP-та, не се позволява
                $sIps = wtime_Setup::get('SITE_IPS');
                if (trim($sIps) && !core_String::checkExist(core_Users::getRealIpAddr(), $sIps, '*')) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }


    /**
     * Действие за затваряне на изскачащ прозорец
     */
    public function act_SkipPopup()
    {
        $retUrl = getRetUrl();
        if (empty($retUrl)) {
            $retUrl = array('Portal', 'show');
        }

        expect($uId = Request::get('id'));

        expect(core_Users::getCurrent() == $uId);

        $this->requireRightFor('trackonline', null, $uId);

        $type = Request::get('type');
        $type = ucfirst(strtolower($type));

        Mode::setPermanent('trackonline', 'skipPopup' . $type);

        return new Redirect($retUrl);
    }


    /**
     * Действие за потвърждение на изскачащ прозорец
     *
     * @return Redirect
     */
    public function act_ConfirmPopup()
    {
        $retUrl = getRetUrl();
        if (empty($retUrl)) {
            $retUrl = array('Portal', 'show');
        }

        expect($uId = Request::get('id'));

        expect(core_Users::getCurrent() == $uId);

        $this->requireRightFor('trackonline', null, $uId);

        $personId = crm_Profiles::getPersonByUser($uId);
        $type = Request::get('type');
        $classId = core_Users::getClassId();

        $zRec = $this->addEntry($personId, dt::now(), $type, '', $classId);

        expect($zRec);

        Mode::setPermanent('trackonline', 'confirmPopup');

        return new Redirect($retUrl);
    }
}
