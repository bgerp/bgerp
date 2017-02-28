<?php



/**
 * Мениджър на заплати
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Заплати
 */
class hr_Indicators extends core_Manager
{
    /**
     * Старо име на класа
     */
    public $oldClassname = 'trz_SalaryIndicators';
    

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
    public $loadList = 'plg_SaveAndNew, plg_RowTools2,hr_Wrapper';
                   

    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,hr';
    
    
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
    public $canView = 'ceo,hr';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,hr';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,hr';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,hr';

    
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
    	$this->FLD('docClass', 'class(interface=hr_IndicatorsSourceIntf,select=title)', 'caption=Документ->Клас,silent,mandatory');

    	$this->FLD('personId',    'key(mvc=crm_Persons,select=name,group=employees)', 'caption=Служител->Име,mandatory');

    	$this->FLD('indicator',    'key(mvc=hr_IndicatorNames, select=name)', 'caption=Индикатор->Наименование,smartCenter,mandatory');
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
    	
    	$dMvc = cls::get($rec->docClass); 
    	$row->docId = $dMvc->getLinkForObject($rec->docId);
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
        $data->listFilter->FNC('indicators', 'key(mvc=hr_IndicatorNames, select=name)', 'caption=Показател,input,silent');

        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
       	$data->listFilter->showFields = 'from, to, person, indicators';
        $data->listFilter->input('from, to, person, indicators', 'silent');
        
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
        $timeline = dt::addSecs(-hr_Setup::INDICATORS_UPDATE_PERIOD - 60);
       
        $periods = self::saveIndicators($timeline);

        foreach($periods as $id => $rec) {
            self::calcPeriod($rec);
        }
    }
    
     
    /**
     * Събиране на информация от всички класове
     * имащи интерфейс hr_IndicatorsSourceIntf
     * 
     * @param date $date
     * @return array $indicators
     */
    public static function fetchIndicators($date)
    {
    	// Намираме всички класове съдържащи интерфейса
    	$docArr = core_Classes::getOptionsByInterface('hr_IndicatorsSourceIntf');
    	
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
                    $rec->indicator = hr_IndicatorNames::getId($rec->indicator);
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