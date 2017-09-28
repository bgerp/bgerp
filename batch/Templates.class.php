<?php



/**
 * Шаблони на партиди
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class batch_Templates extends embed_Manager {
    
	
	/**
	 * Свойство, което указва интерфейса на вътрешните обекти
	 */
	public $driverInterface = 'batch_BatchTypeIntf';
	
	
    /**
     * Заглавие
     */
    public $title = 'Видове партиди';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, batch_Wrapper, plg_Created, plg_Modified, plg_State2';
    
    
    /**
     * Кои полета да се показват в листовия изглед
     */
    public $listFields = 'name,driverClass=Тип,state,createdOn,createdBy,modifiedOn,modifiedBy';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Вид партидa";
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'batch,ceo';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'batch,ceo';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'batch,ceo';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'batch, ceo';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'batch/tpl/SingleLayoutDefs.shtml';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('name', 'varchar', 'caption=Име,mandatory');
    	
    	$this->FLD('autoAllocate', 'enum(yes=Да,no=Не)', 'caption=Автоматично разпределение в документи->Избор,notNull,value=yes,formOrder=1000');
    	$this->FLD('uniqueProduct', 'enum(no=Не,yes=Да)', 'caption=Партидния № може да се използва само в един артикул->Избор,notNull,value=no,formOrder=1000');
    	
    	$this->setDbUnique('name');
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    function loadSetupData()
    {
    	$file = "batch/csv/Templates.csv";
    	
    	$fields = array(
    			0 => "name",
    			1 => "driverClass",
    			2 => "state",
    			3 => 'csv_params',
    	);
    
    	$cntObj = csv_Lib::importOnce($this, $file, $fields);
    	$res = $cntObj->html;
    	 
    	return $res;
    }
    
    
    /**
     * Изпълнява се преди импортирването на данните
     */
    protected static function on_BeforeImportRec($mvc, &$rec)
    {
    	core_Classes::add($rec->driverClass);
    	$rec->driverClass = cls::get($rec->driverClass)->getClassId();
    
    	// Импортиране на параметри при нужда
    	if(isset($rec->csv_params)){
    		$params = arr::make($rec->csv_params);
    		foreach ($params as $k => $v){
    			if(!isset($rec->{$k})){
    				$rec->{$k} = $v;
    			}
    		}
    	}
    }
    
    
    /**
     * Форсираща функция
     * 
     * @param stdClass $params - параметри
     * @return int $templateId - ид на шаблона
     */
    public static function force($params = array())
    {
    	$params = (array)$params;
    	
    	expect(isset($params['driverClass']), $params);
    	
    	$templates = array();
    	$tQuery = self::getQuery();
    	while($tRec = $tQuery->fetch()){
    		$t = array('driverClass' => $tRec->driverClass) + (array)$tRec->driverRec;
    		$templates[$tRec->id] = $t;
    	}
    	
    	$found = FALSE;
    	$p = $params;
    	unset($p['name']);
    	foreach ($templates as $k => $t){
    		if(arr::areEqual($p, $t)){
    			$found = $k;
    			break;
    		}
    	}
    	
    	if($found){
    		$templateId = $found;
    	} else {
    		$saveRec = (object)$params;
    		$templateId = batch_Templates::save($saveRec);
    		
    		if(empty($saveRec->name)){
    			$saveRec->name = isset($params['name']) ? $params['name'] : core_Classes::getTitleById($params['driverClass']) . "({$templateId})";
    			batch_Templates::save($saveRec, 'id,name');
    		}
    	}
    	
    	return $templateId;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec = &$form->rec;
    	
    	if($rec->createdBy == core_Users::SYSTEM_USER && isset($rec->id)){
    		$fields = $form->selectFields("#input != 'none' AND #input != 'hidden'");
    		foreach ($fields as $name => $fld){
    			if(in_array($name, array('autoAllocate', 'uniqueProduct'))) continue;
    			$form->setReadOnly($name);
    		}
    	}
    	
    	if(isset($rec->driverClass)){
    		$Driver = static::getDriver($rec);
    		if($Driver->canChangeBatchUniquePerProduct() !== TRUE){
    			$form->setField('uniqueProduct', 'input=none');
    		}
    	}
    }
}