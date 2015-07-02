<?php



/**
 * Мениджър на ресурсите на предприятието
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Ресурси на предприятието
 */
class planning_Resources extends core_Master
{
    
    
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'mp_Resources';
	
	
	/**
	 * Интерфейси, поддържани от този мениджър
	 */
	public $interfaces = 'planning_ResourceAccRegIntf,acc_RegisterIntf';
	
	
    /**
     * Заглавие
     */
    public $title = 'Ресурси на предприятието';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_Created, plg_Sorting, plg_Search, plg_Rejected, planning_Wrapper, acc_plg_Registry, plg_State';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,planning';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,planning';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'ceo,planning';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,planning';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canReject = 'ceo,planning';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,planning';
    
	
	/**
	 * Икона на единичния изглед
	 */
	public $singleIcon = 'img/16/package.png';
	
	
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт,title,type,lastUsedOn,createdOn,createdBy';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Поле за еденичен изглед
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Ресурс';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'title, systemId';
    
    
    /**
     * Шаблон за еденичен изглед
     */
    public $singleLayoutFile = 'planning/tpl/SingleLayoutResource.shtml';
    
    
    /**
     * Детайли на документа
     */
    public $details = 'AccReports=acc_ReportDetails';
    
    
    /**
     * По кои сметки ще се правят справки
     */
    public $balanceRefAccounts = '61101';
    
    
    /**
     * По кой итнерфейс ще се групират сметките
     */
    public $balanceRefGroupBy = 'planning_ResourceAccRegIntf';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('title', 'varchar', 'caption=Наименование,mandatory');
    	$this->FLD('type', 'enum(equipment=Оборудване,labor=Труд,material=Материал)', 'caption=Вид,mandatory,silent');
    	$this->FLD('measureId', 'key(mvc=cat_UoM,select=name,allowEmpty)', 'caption=Мярка,mandatory');
    	$this->FLD('selfValue', 'double(decimals=2)', 'caption=Себестойност');
    	$this->FLD('systemId', 'varchar', 'caption=Системен №,input=none');
    	$this->FLD('lastUsedOn', 'datetime(format=smartTime)', 'caption=Последна употреба,input=none,column=none');
    	$this->FLD('bomId', 'key(mvc=cat_Boms)', 'input=none');
    	$this->FLD('state', 'enum(active=Активиран,rejected=Оттеглен)', 'caption=Състояние,input=none,notNull,default=active');
    	
    	// Поставяме уникален индекс
    	$this->setDbUnique('title');
    	$this->setDbUnique('systemId');
    }
    
    
    /**
     * Можели записа да се добави в номенклатура при активиране
     */
    public function canAddToListOnActivation($rec)
    {
    	return TRUE;
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    function loadSetupData()
    {
    	$file = "planning/csv/Resources.csv";
    	$fields = array(0 => "title", 1 => 'type', '2' => 'systemId', '3' => 'measureId', '4' => 'state');
    	
    	$cntObj = csv_Lib::importOnce($this, $file, $fields);
    	
    	return $cntObj->html;
    }
    
    
    /**
     * Изпълнява се преди импортирването на данните
     */
    public static function on_BeforeImportRec($mvc, &$rec)
    {
    	$rec->createdBy = '-1';
    	$rec->measureId = cat_UoM::fetchBySinonim($rec->measureId)->id;
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
    	$data->listFilter->FNC('rType', 'enum(all=Всички,equipment=Оборудване,labor=Труд,material=Материал)', 'caption=Тип,placeholder=aa');
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    	$data->listFilter->setDefault('rType', 'all');
    	$data->listFilter->showFields = 'search,rType';
    	$data->listFilter->view = 'horizontal';
    	
    	$data->listFilter->input();
    	
    	if($type = $data->listFilter->rec->rType){
    		if($type != 'all'){
    			$data->query->where("#type = '{$type}'");
    		}
    	}
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::getItemRec
     * @param int $objectId
     */
    public static function getItemRec($objectId)
    {
    	$self = cls::get(__CLASS__);
    	$result = NULL;
    
    	if ($rec = $self->fetch($objectId)) {
    		$result = (object)array(
    				'num' => $rec->id . " r",
    				'title' => $rec->title,
    		);
    	}
    
    	return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     * @param int $objectId
     */
    public static function itemInUse($objectId)
    {
    	// @todo!
    }
    
    
    /**
     * Изпълнява се след подготовката на титлата в единичния изглед
     */
    public static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
    	$dQuery = planning_ObjectResources::getQuery();
    	$dQuery->where("#resourceId = {$data->rec->id}");
    	if(isset($data->rec->selfValue)){
    		$data->row->currencyId = acc_Periods::getBaseCurrencyCode();
    	}
    	
    	$data->detailRows = $data->detailRecs = array();
    	while($dRec = $dQuery->fetch()){
    		$data->detailRecs[$dRec->id] = $dRec;
    		$data->detailRows[$dRec->id] = planning_ObjectResources::recToVerbal($dRec);
    	}
    }
    
    
    /**
     * След рендиране на единичния изглед
     */
    public static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	$table = cls::get('core_TableView');
    	$detailTpl = $table->get($data->detailRows, 'tools=Пулт,objectId=Обект,conversionRate=Конверсия');
    	$tpl->append($detailTpl, 'OBJECT_RESOURCES');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	if($form->rec->createdBy == '-1'){
    		foreach(array('title', 'type', 'measureId') as $fld){
    			$form->setReadOnly($fld);
    		}
    	}
    	
    	$cCode = acc_Periods::getBaseCurrencyCode();
    	$form->setField('selfValue', "unit={$cCode}");
    	$form->setDefault('state', 'active');
    	
    	if(isset($form->rec->id)){
    		if(planning_ObjectResources::fetch("#resourceId = {$form->rec->id}")){
    			$form->setReadOnly('type');
    		}
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
    		if(!empty($form->rec->selfValue)){
    			$form->rec->selfValue = currency_CurrencyRates::convertAmount($form->rec->selfValue);
    		}
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $res
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'delete' || $action == 'reject') && isset($rec)){
    		if(planning_ObjectResources::fetchField("#resourceId = '{$rec->id}'")){
    			$res = 'no_one';
    		}
    	}
    	
    	if(($action == 'delete') && isset($rec)){
    		if(isset($rec->lastUsedOn)){
    			$res = 'no_one';
    		}
    	}
    	
    	if(($action == 'edit') && isset($rec)){
    		if($rec->state == 'rejected'){
    			$res = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Преди запис на документ, изчислява стойността на полето `isContable`
     *
     * @param core_Manager $mvc
     * @param stdClass $rec
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
    	if(empty($rec->measureId)){
    		if($rec->type != 'labor'){
    			$rec->measureId = cat_UoM::fetchBySinonim('pcs')->id;
    		} else {
    			$rec->measureId = cat_UoM::fetchBySinonim('h')->id;
    		}
    	}
    }
    
    
    /**
     * Поставя изискване да се селектират само активните записи
     */
    public static function on_BeforeMakeArray4Select($mvc, &$optArr, $fields = NULL, &$where = NULL)
    {
    	$where .= ($where ? " AND " : "") . " #state != 'rejected'";
    }
    
    
    /**
     * Връща себестойността на ресурса, ако има забита себестойност връща нея
     * Ако няма и ресурса е материал, мъчи се да изчисли цената според средно притеглените цени
     * на артикулите свързани с нея във всички складове
     * иначе намира средно претеглената цена за текущия период от сметка 6111
     * 
     * @param int $id - ид на ресурса
     * @param string|NULL $date - към коя дата, NULL за текущата
     * @return double $selfValue - себестойността (цената в основна валута без ддс)
     */
    public static function getSelfValue($id, $date = NULL)
    {
    	// Първо проверяваме имали себестойност в модела
    	$selfValue = planning_Resources::fetchField($id, 'selfValue');
    	
    	// Ако е намерена твърдо забита себестойност, връщаме я
    	if($selfValue) return $selfValue;
    	
    	// Кой баланс ще вземем
    	//@TODO да е според датата
    	$lastBalance = acc_Balances::getLastBalance();
    	
    	// Всички материали обвързани към този ресурс
    	$objects = planning_ObjectResources::fetchRecsByClassAndType($id, cat_Products::getClassId(), 'material');
    	
    	// Ако има баланс
    	if($lastBalance){
    		
    		$bQuery = acc_BalanceDetails::getQuery();
    		$value = NULL;
    		$allQuantity = NULL;
    		
    		// За всеки материал обвързан с ресурса
    		foreach ($objects as $obRec){
    			$itemRec = acc_Items::fetchItem($obRec->classId, $obRec->objectId);
    			
    			// Ако артикула е перо
    			if($itemRec){
    				
    				// Изчисляваме средно притеглената му цена във всички складове
    				$query = clone $bQuery;
    				acc_BalanceDetails::filterQuery($query, $lastBalance->id, '321');
    				$prodPositionId = acc_Lists::getPosition('321', 'cat_ProductAccRegIntf');
    				$query->where("#ent{$prodPositionId}Id = {$itemRec->id}");
    				$query->XPR('totalQuantity', 'double', 'SUM(#blQuantity)');
    				$query->XPR('totalAmount', 'double', 'SUM(#blAmount)');
    				$res = $query->fetch();
    				
    				// Ако има някакво количество и суми в складовете, натрупваме ги
    				if(!is_null($res->totalQuantity) && !is_null($res->totalAmount)){
    					$totalQuantity = round($res->totalQuantity, 2);
    					$totalAmount = round($res->totalAmount, 2);
    					
    					$value += $totalAmount;
    					$allQuantity += $totalQuantity;
    				}
    			}
    		}
    		
    		// Опитваме се да изчислим средно притеглената цена според съставните артикули
    		if(!is_null($value) && !is_null($allQuantity)){
    			@$selfValue = $value / $allQuantity;
    		}
    		
    		// Ако не е намерено от материали
    		if(!$selfValue){
    			
    			// Намираме последния баланс, и перото на ресурса
    			$itemRec = acc_Items::fetchItem(__CLASS__, $id);
    			
    			// Ако има перо и баланс намираме записа за ресурса от сметка 61101
    			if($itemRec){
    				$query2 = clone $bQuery;
    				
    				acc_BalanceDetails::filterQuery($query2, $lastBalance->id, '61101');
    				$resourcePositionId = acc_Lists::getPosition('61101', 'planning_ResourceAccRegIntf');
    				$query2->where("#ent{$resourcePositionId}Id = {$itemRec->id}");
    				$query2->show("ent{$resourcePositionId}Id,blAmount,blQuantity");
    				 
    				$bRec = $query2->fetch();
    				 
    				// Изчисляваме колко е счетоводната средно притеглена цена
    				if(!is_null($bRec->blAmount) && !is_null($bRec->blQuantity)){
    					@$selfValue = $bRec->blAmount / $bRec->blQuantity;
    				}
    			}
    		}
    	}
    	
    	// Връщаме цената, ако сме я намерили
    	return $selfValue;
    }
}