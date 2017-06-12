<?php



/**
 * Мениджър на отчети от Задание за производство
 *
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
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
     * @var varchar
     */
    protected $hashField = 'containerId';
    
    
    /**
     * Кое поле от $data->recs да се следи, ако има нов във новата версия
     *
     * @var varchar
     */
    protected $newFieldToCheck = 'containerId';
    
    
    /**
     * Видовете почивни дни
     */
    static $typeMap = array('sickDay' => 'Болничен',
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
	 * @param frame2_driver_Proto $Driver $Driver
	 * @param embed_Manager $Embedder
	 * @param stdClass $data
	 */
	protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
	{

	}
    
	
	/**
	 * Кои записи ще се показват в таблицата
	 * 
	 * @param stdClass $rec
	 * @param stdClass $data
	 * @return array
	 */
	protected function prepareRecs($rec, &$data = NULL)
	{
		$recs = array();
		$persons = array();
		$date = acc_Periods::fetch($rec->periods);

	
	    $querySick = hr_Sickdays::getQuery();
	    $querySick->where("((#startDate >= '{$date->start}' AND #toDate <= '{$date->end}')) AND #state = 'active'");
	    
	    $queryTrip = hr_Trips::getQuery();
	    $queryTrip->where("((#startDate >= '{$date->start}' AND #toDate <= '{$date->end}')) AND #state = 'active'");
	    
	    $queryLeave = hr_Leaves::getQuery();
	    $queryLeave->where("((#leaveFrom >= '{$date->start}' AND #leaveTo <= '{$date->end}')) AND #state = 'active'");
	    
	    $num = 1;
	    // добавяме болничните
	    while($recSick = $querySick->fetch()){
	        // ключ за масива ще е ид-то на всеки потребител в системата
	        $id = $recSick->personId;

	        // добавяме в масива събитието
	        $recs[$recSick->id.'|'.$id] =
	            (object) array (
	                'num' => $num,
	                'containerId' => $recSick->containerId,
	                'person' => $recSick->personId,
	                'dateFrom' => $recSick->startDate,
	                'dateTo' => $recSick->toDate,
	                'count' => self::getLeaveDays($recSick->startDate, $recSick->toDate, $id)->workDays,
	                'type' => 'sickDay',
	            );
	            
	            $num++;
	    }
	    
	    // добавяме командировките
	    while($recTrip = $queryTrip->fetch()){
	        // ключ за масива ще е ид-то на всеки потребител в системата
	        $id = $recTrip->personId;

	        // добавяме в масива събитието
	        $recs[$recTrip->id.'|'.$id] =
	            (object) array (
	                'num' => $num,
	                'containerId' => $recTrip->containerId,
	                'person' => $recTrip->personId,
	                'dateFrom' => $recTrip->startDate,
	                'dateTo' => $recTrip->toDate,
	                'count' => self::getLeaveDays($recTrip->startDate, $recTrip->toDate, $id)->workDays,
	                'type' => 'tripDay',
	            );
	            
	            $num++;
	    }
	    
	    // добавяме и отпуските
	    while($recLeave = $queryLeave->fetch()){
	        // ключ за масива ще е ид-то на всеки потребител в системата
	        $id = $recLeave->personId;

	        $recs[$recLeave->id.'|'.$id] =
	           (object) array (
	                'num' => $num,
	                'containerId' => $recLeave->containerId,
	                'person' => $recLeave->personId,
	                'dateFrom' => $recLeave->leaveFrom,
	                'dateTo' => $recLeave->leaveTo,
	                'count' =>self::getLeaveDays($recLeave->leaveFrom, $recLeave->leaveTo, $id)->workDays,
	                'type' => 'leaveDay',
	            );
	           
	           $num++;
	    }


		return $recs;
	}
	
	
	/**
	 * Връща фийлдсета на таблицата, която ще се рендира
	 *
	 * @param stdClass $rec      - записа
	 * @param boolean $export    - таблицата за експорт ли е
	 * @return core_FieldSet     - полетата
	 */
	protected function getTableFieldSet($rec, $export = FALSE)
	{
		$fld = cls::get('core_FieldSet');
	
		if($export === FALSE){
			$fld->FLD('num', 'varchar','caption=№');
			$fld->FLD('person', 'varchar', 'caption=Служител');
	    	$fld->FLD('dateFrom', 'varchar', 'caption=Дата->От');
		    $fld->FLD('dateTo', 'varchar', 'smartCenter,caption=Дата->До');
	    	$fld->FLD('count', 'varchar', 'smartCenter,caption=Бр. дни');
	    	$fld->FLD('type', 'varchar', 'smartCenter,caption=Вид');

		} else {
			$fld->FLD('num', 'varchar','caption=№');
			$fld->FLD('person', 'varchar', 'caption=Служител');
	    	$fld->FLD('dateFrom', 'varchar', 'caption=Дата->От');
		    $fld->FLD('dateTo', 'varchar', 'smartCenter,caption=Дата->До');
	    	$fld->FLD('count', 'varchar', 'smartCenter,caption=Бр. дни');
	    	$fld->FLD('type', 'varchar', 'smartCenter,caption=Вид');
		}
	
		return $fld;
	}
	
	
    /**
	 * Вербализиране на редовете, които ще се показват на текущата страница в отчета
	 *
	 * @param stdClass $rec  - записа
	 * @param stdClass $dRec - чистия запис
	 * @return stdClass $row - вербалния запис
	 */
	protected function detailRecToVerbal($rec, &$dRec)
	{
		$isPlain = Mode::is('text', 'plain');
		$Int = cls::get('type_Int');
		$Date = cls::get('type_Date');
		$row = new stdClass();

		// Линк към служителя
		if(isset($dRec->person)) {
		    $row->person = crm_Profiles::createLink($dRec->person);
		}
		
		if($isPlain){
			$row->person = strip_tags(($row->person instanceof core_ET) ? $row->person->getContent() : $row->person);
		}

		if(isset($dRec->num)) {
		    $row->num = $Int->toVerbal($dRec->num);
		}

		if(isset($dRec->dateFrom)) {
		    $row->dateFrom = $Date->toVerbal($dRec->dateFrom);
		}
		
		if(isset($dRec->dateTo)) {
		    $row->dateTo = $Date->toVerbal($dRec->dateTo);
		}
		
	    if(isset($dRec->count)) {
		    $row->count = $Int->toVerbal($dRec->count);
		}
		
		if(isset($dRec->type)) {
			$row->type = self::$typeMap[$dRec->type];
		}

		return $row;
	}
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager $Embedder
     * @param core_ET $tpl
     * @param stdClass $data
     */
    protected static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {

    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager $Embedder
     * @param core_ET $tpl
     * @param stdClass $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
							    <small><div><!--ET_BEGIN dealers-->|Търговци|*: [#dealers#]<!--ET_END dealers--></div></small></fieldset><!--ET_END BLOCK-->"));
      

        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }
    
   
    /**
     * Изчисляване на дните - присъствени, неприсъствени, почивни
     * 
     * @param myslq Date $from
     * @param myslq Date $to
     * @param int $personId
     */
    static public function getLeaveDays($from, $to, $personId)
    {
    
        // изисляване на непресъствените бр дни 
        $state = hr_EmployeeContracts::getQuery();
        $state->where("#personId='{$personId}'");
        
        // данните от договора на служителя
        if($employeeContractDetails = $state->fetch()){
            
            $employeeContract = $employeeContractDetails->id;
            $department = $employeeContractDetails->departmentId;
            
            // има ли график?
            $schedule = hr_EmployeeContracts::getWorkingSchedule($employeeContract);
            
            // изчисляваме дните по него
            if($schedule){
                $days = hr_WorkingCycles::calcLeaveDaysBySchedule($schedule, $department, $from, $to);
            // в противен случай ги изсичляваме на основание на калндара
            } else {
                $days = cal_Calendar::calcLeaveDays($from, $to);
            }
            
        // ако служителя няма договор изчисляваме дните на база календара
        } else {
                 
            $days = cal_Calendar::calcLeaveDays($from, $to);
        }
        
        return $days;   
    }
}