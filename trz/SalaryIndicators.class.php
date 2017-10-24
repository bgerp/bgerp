<?php



/**
 * Мениджър на заплати
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Заплати
 */
class trz_SalaryIndicators extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Показатели';
    
    
    /**
     * Заглавие в единично число
     */
    public $singleTitle = 'Показател';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_SaveAndNew, plg_RowTools2, trz_Wrapper';
                   

    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,trz';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'admin,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo,trz';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,trz';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,trz';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,trz';

    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'date, doc=Документ, personId, departmentId, positionId, indicator, value';
    
     
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('date',    'date', 'caption=Дата,mandatory');
    	$this->FLD('docId',    'int', 'caption=Документ->№,mandatory');
    	// Външен ключ към модела (класа). Този клас трябва да реализира
    	// интерфейса, посочен в полето `interfaceId` на мастъра @link acc_Lists
    	$this->FLD('docClass', 'class(interface=trz_SalaryIndicatorsSourceIntf,select=title)',
    	    'caption=Документ->Клас,silent,mandatory');
    	$this->FLD('personId',    'key(mvc=crm_Persons,select=name,group=employees)', 'caption=Служител->Име,mandatory');
    	$this->FLD('departmentId',    'key(mvc=hr_Departments, select=name)', 'caption=Служител->Отдел,mandatory');
    	$this->FLD('positionId',    'key(mvc=hr_Positions, select=name)', 'caption=Служител->Длъжност,mandatory');
    	$this->FLD('indicator',    'key(mvc=trz_SalaryIndicatorNames, select=name)', 'caption=Индикатор->Наименование,smartCenter,mandatory');
    	$this->FLD('value',    'double(smartRound,decimals=2)', 'caption=Индикатор->Стойност,mandatory');
    	
    	$this->setDbUnique('date,docId,docClass,personId,indicator');
    	
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
    	if(crm_Persons::haveRightFor('single', $rec->personId)){
	    	$name = crm_Persons::fetchField("#id = '{$rec->personId}'", 'name');
	    	$row->personId = ht::createLink($name, array ('crm_Persons', 'single', 'id' => $rec->personId), NULL, 'ef_icon = img/16/vcard.png');
    	}
    	
    	$Class = cls::get($rec->docClass); 
    	
    	$dRec = $Class->fetch($rec->docId);
    	
    	// Ако имаме права да видим документа от Премиите
    	if($Class::haveRightFor('single', $rec->docId)){

    	    if(cls::getClassName($rec->docClass) == 'trz_Bonuses'){
    	        $name = trz_Bonuses::fetchField("#id = '{$rec->docId}'", 'type');
    	        $row->doc = ht::createLink($name, array ('trz_Bonuses', 'single', 'id' => $rec->docId));
    	    } else{
    	        if ($Class->masterKey) {
    	           $row->doc = $Class->Master->getLink($dRec->{$Class->masterKey}, 0);
    	        } else {
    	           $row->doc = $Class->getHyperlink($rec->docId);
    	        }
    	        
    	    }
    	}
    }
 
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListFilter($mvc, $data)
    {
    	
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('from', 'date', 'caption=Дата->От,input,silent, width = 150px');
        $data->listFilter->FNC('to', 'date', 'caption=Дата->До,input,silent, width = 150px');
        $data->listFilter->FNC('person', 'key(mvc=crm_Persons,select=name,group=employees, allowEmpty=true)', 'caption=Служител,input,silent, width = 150px');
        $data->listFilter->FNC('indicators', 'key(mvc=trz_salaryIndicatorNames, select=name)', 'caption=Показател,input,silent');
        $data->listFilter->FNC('group', 'enum(1=,
        									  2=По дати,
        									  3=Обобщено)', 'caption=Групиране,input,silent, width = 150px');

        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
       	$data->listFilter->showFields = 'from, to, person, indicators, group';
        $data->listFilter->input('from, to, person, indicators, group', 'silent');
        
   		$from = $data->listFilter->rec->from;
    	$to = $data->listFilter->rec->to;
    	$person = $data->listFilter->rec->person;
    	$indicators = $data->listFilter->rec->indicators;
    	$group = $data->listFilter->rec->group;

    	if($from && $to){
    		if($from > $to){
    			$newFrom = $from;
    			$from = $to;
    			$to = $newFrom;
    		}
			$data->query->where("#date >= '{$from}' AND #date <= '{$to}'");
	    }
	    
    	if($from){
			$data->query->where("#date >= '{$from}'");
			
	    }
	    
    	if($to){
			$data->query->where("#date <= '{$to}'");
	    }
	    
	    if($person){
	    	$data->query->where("#personId = '{$person}'");
	    }
	    
	    if($indicators){
	    	$data->query->where("#indicator = '{$indicators}'");
	    }
    }
    
    
    /**
     * Изпращане на данните към показателите
     */
    public static function cron_Indicators()
    {
        $date = dt::now(FALSE);
       
        self::saveIndicators($date);
    }
    
     
    /**
     * Събиране на информация от всички класове
     * имащи интерфейс trz_SalaryIndicatorsSourceIntf
     * 
     * @param date $date
     * @return array $indicators
     */
    public static function fetchIndicators($date)
    {
    	// Намираме всички класове съдържащи интерфейса
    	$docArr = core_Classes::getOptionsByInterface('trz_SalaryIndicatorsSourceIntf');
    	
        $indicators = array();
  
    	// Зареждаме всеки един такъв клас
    	foreach ($docArr as $doc){
    		$Class = cls::get($doc);
    		
    		// Взимаме връщания масив от интерфейсния метод
    	    $data = $Class->getSalaryIndicators($date);
    	    
    	    if (is_array($data)) {
        	    // По id-то на служителя, намираме от договора му
        	    // в кой отдел и на каква позиция работи
        	    foreach($data as $id => $rec){
                    $rec->departmentId = hr_EmployeeContracts::fetchField("#personId = '{$rec->personId}'", 'departmentId');
                    $rec->positionId = hr_EmployeeContracts::fetchField("#personId = '{$rec->personId}'", 'positionId');
                    $rec->indicator = trz_SalaryIndicatorNames::getId($rec->indicator);
                    $indicators[$id] = $rec;
        	    }
    	    }
    	}
    
    	return $indicators;
    }
    
    
    /**
     * Пълнене на базата данни
     * 
     * @param date $date
     */
    public static function saveIndicators($date)
    {
    	$indicators = self::fetchIndicators($date);
    	
        $forClean = array();

    	// Записваме индикаторите, като проверяваме за съществуващи записи
    	foreach ($indicators as $id => $rec) {
            
            $key = $rec->docClass . '::' . $rec->docId;
            
            if(!isset($forClean[$key])) {
                $forClean[$key] = array();
            }
            
            // Оттеглените източници ги записваме само за почистване
            if($rec->state == 'rejected') continue;

	    	$exRec = self::fetch(array("#docClass = {$rec->docClass} AND #docId = {$rec->docId} AND #personId = {$rec->personId} AND #indicator = '{$rec->indicator}' AND #date = '($rec->date}'"));

            if($exRec) {
                $rec->id = $exRec->id;
                $forClean[$key][$rec->id] = $rec->id;
                if($rec->value == $exRec->value && $rec->positionId == $exRec->positionId && $rec->departmentId == $exRec->departmentId) {
                    // Ако съществува идентичен стар запис - прескачаме
                    continue;
                }
            }
	  
            // Ако имаме уникален запис го записваме
	        self::save($rec);
            $forClean[$key][$rec->id] = $rec->id;
    	}
        
        // Почистване на непотвърдените записи
        foreach($forClean as $doc => $ids) {
            list($docClass, $docId) = explode('::', $doc);
            $query = self::getQuery();
            $query->where("#docClass = {$docClass} && #docId = {$docId}");
            if(count($ids)) {
                $query->where("#id NOT IN (" . implode(',', $ids) . ")");
            }
            $query->delete();
        }
    }
    
    
    /**
     * Извличаме имената на идикаторите
     */
    public static function getIndicatorNames()
    {
    	$query = static::getQuery();
    	$query->groupBy('indicator');
    	
    	$indicatorsName = array();
    	
    	while($rec = $query->fetch()){
    		$indicatorsName[$rec->indicator] = $rec->indicator;
    	}
    	
    	$arrayIndicator = $indicatorsName;
    	
    	return $arrayIndicator;
    }
}