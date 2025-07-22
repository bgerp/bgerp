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
    public $loadList = 'plg_RowTools,plg_Created,plg_Search,wtime_Wrapper';


    /**
     * Полета, по които ще се търси
     */
    public $searchFields = "personId,type,place";


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('time', 'datetime(format=smartTime)', 'caption=Време,mandatory');
        $this->FLD('personId', 'key2(mvc=crm_Persons,select=names,allowEmpty)', 'caption=Служител,mandatory');
        $this->FLD('type', 'enum(in=Влиза,out=Излиза)', 'caption=Вид');
        $this->FLD('place', 'varchar(64)', 'caption=Място,mandatory,tdClass=leftCol');
        $this->FLD('onSiteTime', 'time', 'caption=Време на място,input=none');
        $this->FLD('sourceClassId', 'class', 'caption=Източник,input=none');
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
        }
    }


    /**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $fields = null, $mode = null)
    {
        if($rec->_fromForm){
            self::calcOnSiteTime(null, $rec->personId);
            core_Statuses::newStatus("Преизчислено е прекараното време на място на|*: <b>" . crm_Persons::getTitleById($rec->personId). "</b>");
        }
    }


    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->title = 'Търсене';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->setFieldType('type', 'enum(all=Влиза/Излиза,in=Влиза,out=Излиза)');
        $data->listFilter->showFields = 'search,personId,type';
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
        }
    }


    /**
     * Вербализиране на row
     * Поставя хипервръзка на ip-то
     */
    protected function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->personId = crm_Persons::getHyperlink($rec->personId, true);

        $color = $rec->type == 'in' ? 'green' : 'darkred';
        $row->type = "<div style='color:{$color};font-weight:bold;'>{$row->type}</div>";
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
                             'classId'  => $classId);

        return static::save($rec);
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
        $query = static::getQuery();
        $query->orderBy('time', 'ASC');
        if(isset($from)){
            $query->where(array("#time >= '[#1#]'", $from));
        }
        if(isset($personId)){
            $query->where("#personId = {$personId}");
        }

        $onSiteTimes = array();
        while($rec = $query->fetch()){
            $onSiteTimes[$rec->personId][$rec->time] = $rec;
        }

        $toSave = array();
        $now = dt::now();
        $nowDateTime = new DateTime();

        // Графиците ще се гледат от вчера до края на деня днес
        $scheduleTo = dt::today() . " 23:59:59";
        $scheduleFrom = dt::addSecs(-1 * 24 * 60 * 60);

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
                        $prev->onSiteTime = $dt2->getTimestamp() - $dt1->getTimestamp();
                        $toSave[$prev->id] = $prev;
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
}
