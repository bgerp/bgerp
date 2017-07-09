<?php



/**
 * Мениджър на отчети за Индикаторите
 *
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Персонал » Индикатори
 */
class hr_reports_IndicatorsRep extends frame2_driver_TableData
{                  
	
	
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'manager,ceo';

    
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
    protected $hashField = '$recIndic';
    
    
    /**
     * Кое поле от $data->recs да се следи, ако има нов във новата версия
     *
     * @var varchar
     */
    protected $newFieldToCheck = 'docId';

    
    /**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
	    //$fieldset->FLD('personId', 'type_UserList', 'caption=Потребител,after=title,single=none');
	    //$fieldset->FLD('userId', 'keylist(mvc=crm_Persons,select=name,group=employees)', 'caption=Служител,after=title,single=none');
	    $fieldset->FLD('departments', 'keylist(mvc=hr_Departments,select=name)', 'caption=Отдел,after=title,single=none');
	    $fieldset->FLD('positionId',    'keylist(mvc=hr_Positions, select=name)', 'caption=Длъжност,after=title,single=none');
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
	    $form = &$data->form;
	    
        $periodToday = acc_Periods::fetchByDate(dt::now());
        $form->setDefault('periods', $periodToday->id);
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
		$personsId = array();
		$flg = FALSE;
		$date = acc_Periods::fetch($rec->periods);

	    // Обръщаме се към трудовите договори
		$query = hr_EmployeeContracts::getQuery();
		// ТОДО
        $query->where("#state = 'active'");
		
		// има ли избран отдел
		if(isset($rec->departments)) { 
		    $departments = keylist::toArray($rec->departments);
		    $departments = implode(',', $departments);
		    $query->where("#departmentId IN ({$departments})");
		}
	
		// има ли избрана позиция
		if(isset($rec->positionId)) { 
		    $positionId = keylist::toArray($rec->positionId);
		    $positionId = implode(',', $positionId);
		    $query->where("#positionId IN ({$positionId})");
		}

		// намираме отдоварящите ТД
		while($contract = $query->fetch()) { 
		    // записваме персонала в един масив
		    array_push($persons, $contract->personId);		
		}

	    if(count($persons)){ 
	        $queryIndic = hr_Indicators::getQuery();
	        
	        // ограничаваме по дата
	        $queryIndic->where("(#date >= '{$date->start}' AND #date <= '{$date->end}')");
	        $persons = implode(',', $persons); 
	        $queryIndic->where("#personId IN ({$persons})"); 
	        $flg = TRUE;
	    }
	
	    if($flg == TRUE) {
    	    // за всеки един индикатор
    	    while($recIndic = $queryIndic->fetch()){ 
    	        $id = $recIndic->personId."|".$recIndic->indicatorId;
    	        // добавяме в масива събитието
    	        if(!array_key_exists($id,$recs)) { 
    	            $recs[$id]=
    	            (object) array (
    	                'num' => 0,
    	                'date' => $recIndic->date,
    	                'docId' => $recIndic->docId,
    	                'person' => $recIndic->personId,
    	                'indicatorId' => $recIndic->indicatorId,
    	                'value' => $recIndic->value,
    	            );
    	            
    	        } else {
    	            $obj = &$recs[$id];
    	            $obj->value += $recIndic->value;
    	        }  
    	    }
    	    
    	    $num = 1;
    	    foreach($recs as $r) {
    	        $r->num = $num;
    	        $num++;
    	    }
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
	    	$fld->FLD('indicator', 'varchar', 'caption=Показател');
		    $fld->FLD('value', 'double(smartRound,decimals=2)', 'smartCenter,caption=Стойност');

		} else {
			$fld->FLD('num', 'varchar','caption=№');
			$fld->FLD('person', 'varchar', 'caption=Служител');
	    	$fld->FLD('indicator', 'varchar', 'caption=Показател');
		    $fld->FLD('value', 'double(smartRound,decimals=2)', 'smartCenter,caption=Стойност');
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
		$Double = cls::get('type_Double');
		$Double->params['decimals'] = 2;
		$row = new stdClass();

		
		// Линк към служителя
		if(isset($dRec->person)) {
		    $userId = crm_Profiles::fetchField("#personId = '{$dRec->person}'",'userId');
		    
		    $nick = crm_Profiles::createLink($userId)->getContent();
		    //crm_Profiles::fetchField("#personId = '{$rec->alternatePerson}'", 'userId');
		    if($userId) {
		        $row->person = crm_Persons::fetchField($dRec->person, 'name') . " (" . $nick .")";
		    } else {
		        $row->person = crm_Persons::fetchField($dRec->person, 'name');
		    }
		}
		
		if($isPlain){
			$row->person = strip_tags(($row->person instanceof core_ET) ? $row->person->getContent() : $row->person);
		}

		if(isset($dRec->num)) {
		    $row->num = $Int->toVerbal($dRec->num);
		}

		if(isset($dRec->indicatorId)) {
		    $row->indicator = hr_IndicatorNames::fetchField($dRec->indicatorId,'name');
		}

	    if(isset($dRec->value)) {
		    $row->value = $Double->toVerbal($dRec->value);
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
        $posArr = array();
        $depArr = array();
            
        if(isset($rec->departments)){
            // избраният отдел
            $departments = keylist::toArray($rec->departments);
            foreach ($departments as &$d) {
                $dep = hr_Departments::fetchField("#id = '{$d}'", 'name');
                array_push($depArr, $dep);
            }
            $row->departments = implode(', ', $depArr); 
        }

        if(isset($rec->positionId)){
            // избраната позиция
            $position = keylist::toArray($rec->positionId);
            foreach ($position as &$p) {
                $pos = hr_Positions::fetchField("#id = '{$p}'", 'name');
                array_push($posArr, $pos);
            }
            
            $row->position = implode(', ', $posArr);
        }
        

        if(isset($rec->periods)){
            // избраният месец
            $row->month = acc_Periods::fetchField("#id = '{$rec->periods}'", 'title');
        }
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
							    <small><div><!--ET_BEGIN departments-->|Отдел|*: [#departments#]<!--ET_END departments--></div></small>
                                <small><div><!--ET_BEGIN position-->|Длъжност|*: [#position#]<!--ET_END position--></div></small>
                                <small><div><!--ET_BEGIN month-->|Месец|*: [#month#]<!--ET_END month--></div></small>
                                </fieldset><!--ET_END BLOCK-->"));
//bp($data->row->month,$data->row->departments,$data->row->position);
        if(isset($data->rec->departments)){
            $fieldTpl->append($data->row->departments, 'departments');
        }
        
        if(isset($data->rec->positionId)){
            $fieldTpl->append($data->row->position, 'position');
        }

        if(isset($data->rec->periods)){
            $fieldTpl->append($data->row->month, 'month');
        }

        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }
}