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
    public $loadList = 'plg_RowTools2, plg_Created, plg_Rejected,  plg_SaveAndNew, 
                    trz_Wrapper';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,trz';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
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
    public $listFields = 'id, date, doc=Документ, personId, departmentId, positionId, indicator, value';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'id';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('date',    'date', 'caption=Дата,mandatory,width=100%');
    	$this->FLD('docId',    'int', 'caption=Документ->№,mandatory,width=100%');
    	// Външен ключ към модела (класа). Този клас трябва да реализира
    	// интерфейса, посочен в полето `interfaceId` на мастъра @link acc_Lists
    	$this->FLD('docClass', 'class(interface=trz_SalaryIndicatorsSourceIntf,select=title)',
    	    'caption=Документ->Клас,silent,mandatory,width=100%');
    	$this->FLD('personId',    'key(mvc=crm_Persons,select=name,group=employees)', 'caption=Служител->Име,mandatory,width=100%');
    	$this->FLD('departmentId',    'key(mvc=hr_Departments, select=name)', 'caption=Служител->Отдел,mandatory,width=100%');
    	$this->FLD('positionId',    'key(mvc=hr_Positions, select=name)', 'caption=Служител->Длъжност,mandatory,width=100%');
    	$this->FLD('indicator',    'varchar', 'caption=Индикатор->Наименование,mandatory,width=100%');
    	$this->FLD('value',    'double', 'caption=Индикатор->Стойност,mandatory,width=100%');
    	
    	$this->setDbUnique('date,docId, docClass, personId, indicator');
    	
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
    	
    	// Ако имаме права да видим документа от Премиите
    	if(trz_Bonuses::haveRightFor('single', $rec->docId)){
	    	$name = trz_Bonuses::fetchField("#id = '{$rec->docId}'", 'type');
	    	$row->doc = ht::createLink($name, array ('trz_Bonuses', 'single', 'id' => $rec->docId));
    	}
    	
    	$Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
    	$row->value = $Double->toVerbal($rec->value);
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
        $data->listFilter->FNC('indicators', 'varchar', 'caption=Показател,input,silent, width = 150px');
        $data->listFilter->FNC('group', 'enum(1=,
        									  2=По дати,
        									  3=Обобщено)', 'caption=Групиране,input,silent, width = 150px');

        $ind = $mvc->getIndicatorNames();
        $data->listFilter->setOptions('indicators', $ind);
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
       
        self::pushIndicators($date);
    }
    
    
    public static function act_Test()
    {
    	$date = '2013-07-16';
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

    	    $dataCnt = count($data);
    	    
    	    // По id-то на служителя, намираме от договора му
    	    // в кой отдел и на каква позиция работи
    	    for($i = 0; $i < $dataCnt; $i ++){ 
    	    	$data[$i]->departmentId = hr_EmployeeContracts::fetchField("#personId = '{$data[$i]->personId}'", 'departmentId');
    	    	$data[$i]->positionId = hr_EmployeeContracts::fetchField("#personId = '{$data[$i]->personId}'", 'positionId');
    	    }
            
    	    if(is_array($data)){
    	    	// Сливаме всичко в един масив
    			$indicators = array_merge($indicators, $data);
    	    }
    	}
    
    	return $indicators;
    }
    
    
    /**
     * Пълнене на базата данни
     * 
     * @param date $date
     */
    public static function pushIndicators($date)
    {
    	$indicators = self::fetchIndicators($date);

    	// За всеки един елемент от масива
    	foreach ($indicators as $indicator)
    	{
    		$rec = new stdClass();
    		$rec->date = $date;
	    	$rec->docId = $indicator->docId;
	    	$rec->docClass = $indicator->docClass;
	    	$rec->personId = $indicator->personId;
	    	$rec->departmentId = $indicator->departmentId; 
	    	$rec->positionId = $indicator->positionId;
	    	$rec->indicator = $indicator->indicator;
	    	$rec->value = $indicator->value;
	    	
	    	$self = cls::get(get_called_class());
	    	$exRec = new stdClass();
	    	
	    	// Ако имаме уникален запис го записваме
	    	// в противен слувай го ъпдейтваме
       		if($self->isUnique($rec, $fields,$exRec)){
    			self::save($rec);
    		} else { 
            	$rec->id = $exRec->id;
            	self::save($rec);
            }
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
    	
    	$arrayIndicator = array(""=>"") + $indicatorsName;
    	
    	return $arrayIndicator;
    }

    
    /**
     * Изпълнява се след начално установяване
     */
    public static function on_AfterSetupMvc($mvc, &$res)
    {
        $rec = new stdClass();
        $rec->systemId = "CollectIndicators";
        $rec->description = "Изпращане на данните към показателите за заплатите";
        $rec->controller = "trz_SalaryIndicators";
        $rec->action = "Indicators";
        $rec->period = 3*60;
        $rec->offset = mt_rand(0,60);
        $res .= core_Cron::addOnce($rec);
    }
}