<?php


/**
 * Мениджър на отчети от Задание за производство
 *
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Персонал » Присъствена форма 76
 */
class hr_reports_LeaveDaysRep extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'hrMaster,ceo,';
    
    
    /**
     * Полета от таблицата за скриване, ако са празни
     *
     * @var int
     */
    //protected $filterEmptyListFields = 'deliveryTime';
    
    
    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField = 'person';
    
    
    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     *
     * @var string
     */
    protected $hashField = 'containerId';
    
    
    /**
     * Коя комбинация от полета от $data->recs да се следи, ако има промяна в последната версия
     *
     * @var string
     */
    protected $newFieldsToCheck = 'containerId';
    
    
    /**
     * Видовете почивни дни
     */
    public static $typeMap = array('sickDay' => 'Болничен',
        'tripDay' => 'Командировка',
        'leaveDay' => 'Отпуск');
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('periods', 'key(mvc=acc_Periods,select=title)', 'caption=Месец,after=title,single=none');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver   $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
    }
    
    
    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec
     * @param stdClass $data
     *
     * @return array
     */
    protected function prepareRecs($rec, &$data = null)
    {    
        $recs = array();
        $persons = array();
        $date = acc_Periods::fetch($rec->periods);

        // Включително краищата: leaveFrom <= $date <= leaveTo
        //$q->where(array("#startDate <= '[#1#]' AND #toDate >= '[#1#]'", $date));
        $querySick = hr_Sickdays::getQuery();
        $querySick->where(array("#startDate <= '[#2#]' AND #toDate >= '[#1#]' AND #state = 'active'", $date->start,$date->end));
        
        $queryTrip = hr_Trips::getQuery();
        $queryTrip->where(array("#startDate <= '[#2#]' AND #toDate >= '[#1#]' AND #state = 'active'", $date->start,$date->end));
        
        $queryLeave = hr_Leaves::getQuery();
        $queryLeave->where(array("#leaveFrom <= '[#2#]' AND #leaveTo >= '[#1#]' AND #state = 'active'", $date->start,$date->end));
        
        $num = 1;
      
        // добавяме болничните
        while ($recSick = $querySick->fetch()) {
            // ключ за масива ще е ид-то на всеки потребител в системата
            $id = $recSick->personId;

            // коригираме датите според границите на месеца
            $realFrom = max($recSick->startDate, $date->start);
            $realTo   = min($recSick->toDate,   $date->end);
         
            // ако няма припокриване — пропускаме
            if ($realFrom > $realTo) {
                continue;
            }
            
            // добавяме в масива събитието
            $recs[$recSick->id.'|'.$id] =
                (object) array(
                    'num' => $num,
                    'containerId' => $recSick->containerId,
                    'person' => $recSick->personId,
                    'dateFrom' => $recSick->startDate,
                    'dateTo' => $recSick->toDate,
                    'count' => self::getLeaveDays($realFrom, $realTo, $id)->workDays,
                    'type' => 'sickDay',
                );
            
            $num++;
        }

        // добавяме командировките
        while ($recTrip = $queryTrip->fetch()) {
            // ключ за масива ще е ид-то на всеки потребител в системата
            $id = $recTrip->personId;
            
            // коригираме датите според границите на месеца
            $realFrom = max($recTrip->startDate, $date->start);
            $realTo   = min($recTrip->toDate,   $date->end);
            
            // ако няма припокриване — пропускаме
            if ($realFrom > $realTo) {
                continue;
            }
            
            // добавяме в масива събитието
            $recs[$recTrip->id.'|'.$id] =
                (object) array(
                    'num' => $num,
                    'containerId' => $recTrip->containerId,
                    'person' => $recTrip->personId,
                    'dateFrom' => $recTrip->startDate,
                    'dateTo' => $recTrip->toDate,
                    'count' => self::getLeaveDays($realFrom, $realTo, $id)->workDays,
                    'type' => 'tripDay',
                );
            
            $num++;
        }
        
        // добавяме и отпуските
        while ($recLeave = $queryLeave->fetch()) {
            // ключ за масива ще е ид-то на всеки потребител в системата
            $id = $recLeave->personId;
            
            // коригираме датите според границите на месеца
            $realFrom = max($recLeave->leaveFrom, $date->start);
            $realTo   = min($recLeave->leaveTo,   $date->end);
            
            // ако няма припокриване — пропускаме
            if ($realFrom > $realTo) {
                continue;
            }
            
            $recs[$recLeave->id.'|'.$id] =
               (object) array(
                   'num' => $num,
                   'containerId' => $recLeave->containerId,
                   'person' => $recLeave->personId,
                   'dateFrom' => $recLeave->leaveFrom,
                   'dateTo' => $recLeave->leaveTo,
                   'count' => self::getLeaveDays($realFrom, $realTo, $id)->workDays,
                   'type' => 'leaveDay',
               );
            
            $num++;
        }
        
        return $recs;
    }
    
    
    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec    - записа
     * @param bool     $export - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');
        
        $fld->FLD('num', 'varchar', 'caption=№');
        $fld->FLD('person', 'key(mvc=crm_Persons,select=name)', 'caption=Служител');
        $fld->FLD('dateFrom', 'date', 'caption=Дата->От');
        $fld->FLD('dateTo', 'date', 'smartCenter,caption=Дата->До');
        $fld->FLD('count', 'int', 'smartCenter,caption=Бр. дни');
        $fld->FLD('type', 'enum(sickDay=Болничен,tripDay=Командировка,leaveDay=Отпуск)', 'smartCenter,caption=Вид');
        
        return $fld;
    }
    
    
    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec  - записа
     * @param stdClass $dRec - чистия запис
     *
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $Int = cls::get('type_Int');
        $Date = cls::get('type_Date');
        $row = new stdClass();
        
        // Линк към служителя
        $row->person = crm_Persons::fetchField($dRec->person, 'name');
        $row->person = strip_tags(($row->person instanceof core_ET) ? $row->person->getContent() : $row->person);
        
        if (isset($dRec->num)) {
            $row->num = $Int->toVerbal($dRec->num);
        }
        
        if (isset($dRec->dateFrom)) {
            $row->dateFrom = $Date->toVerbal($dRec->dateFrom);
        }
        
        if (isset($dRec->dateTo)) {
            $row->dateTo = $Date->toVerbal($dRec->dateTo);
        }
        
        if (isset($dRec->count)) {
            $row->count = $Int->toVerbal($dRec->count);
        }
        
        if (isset($dRec->type)) {
            $row->type = self::$typeMap[$dRec->type];
        }
        
        return $row;
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param core_ET           $tpl
     * @param stdClass          $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN periods--><div>|Период|*: [#periods#]</div><!--ET_END periods-->
                                        <!--ET_BEGIN employee--><div>|Служители|*: [#employee#]</div><!--ET_END employee-->
                                    </div>
							    </fieldset><!--ET_END BLOCK-->"));
        
        
        if (isset($data->rec->periods)) { 
            $date = acc_Periods::fetch($data->rec->periods);
            $fieldTpl->append('<b>' . $date->title . '</b>', 'periods');
        }

        $fieldTpl->append('<b>' . 'Всички' . '</b>', 'employee');

        
        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }
    
    
    /**
     * Изчисляване на дните - присъствени, неприсъствени, почивни
     *
     * @param myslq Date $from
     * @param myslq Date $to
     * @param int        $personId
     */
    public static function getLeaveDays($from, $to, $personId)
    {

        $scheduleId = planning_Hr::getSchedule($personId);
        $days = hr_Schedules::calcLeaveDaysBySchedule($scheduleId, $from, $to);
        
        return $days;
    }
}
