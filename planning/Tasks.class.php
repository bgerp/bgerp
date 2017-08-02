<?php


/**
 * Мениджър на Производствени операции
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Производствени операции
 */
class planning_Tasks extends tasks_Tasks
{
    
    
	/**
	 * Интерфейси
	 */
    public $interfaces = 'label_SequenceIntf';
    
    
	/**
	 * Свойство, което указва интерфейса на вътрешните обекти
	 */
	public $driverInterface = 'planning_DriverIntf';
	
	
	/**
	 * Шаблон за единичен изглед
	 */
	public $singleLayoutFile = 'planning/tpl/SingleLayoutTask.shtml';
	
	
	/**
	 * Полета от които се генерират ключови думи за търсене (@see plg_Search)
	 */
	public $searchFields = 'title';
	
	
	/**
	 * Плъгини за зареждане
	 */
	public $loadList = 'doc_plg_BusinessDoc,doc_plg_Prototype,doc_DocumentPlg, planning_plg_StateManager, planning_Wrapper, acc_plg_DocumentSummary, plg_Search, plg_Clone, plg_Printing,plg_RowTools2,bgerp_plg_Blank';
	
	
	/**
	 * Заглавие
	 */
	public $title = 'Производствени операции';
	
	
	/**
	 * Единично заглавие
	 */
	public $singleTitle = 'Производствена операция';
	
	
	/**
	 * Абревиатура
	 */
	public $abbr = 'Pts';
	
	
	/**
	 * Групиране на документите
	 */
	public $newBtnGroup = "3.8|Производство";
	
	
	/**
	 * Клас обграждащ горния таб
	 */
	public $tabTopClass = 'portal planning';
	
	
	/**
	 * Да не се кешира документа
	 */
	public $preventCache = TRUE;
	
	
	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = 'expectedTimeStart,title, originId=Задание, progress, folderId,state,modifiedOn,modifiedBy';
	
	
	/**
	 * Дали винаги да се форсира папка, ако не е зададена
	 * 
	 * @see doc_plg_BusinessDoc
	 */
	public $alwaysForceFolderIfEmpty = TRUE;
	
	
	/**
	 * Поле за търсене по потребител
	 */
	public $filterFieldUsers = FALSE;
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,planning,taskWorker';
	
	
	/**
	 * Може ли да се редактират активирани документи
	 */
	public $canEditActivated = TRUE;
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Master &$mvc)
	{
		expect(is_subclass_of($mvc->driverInterface, 'tasks_DriverIntf'), 'Невалиден интерфейс');
		$mvc->FLD('fixedAssets', 'keylist(mvc=planning_AssetResources,select=code,makeLinks)', 'caption=Произвеждане->Оборудване,after=packagingId');
	}
	
	
	/**
	 * Подготовка на формата за добавяне/редактиране
	 */
	public static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$rec = &$data->form->rec;
    
		if(isset($rec->systemId)){
			$data->form->setField('prototypeId', 'input=none');
		}
		
		if(empty($rec->id)){
			if($folderId = Request::get('folderId', 'key(mvc=doc_Folders)')){
				unset($rec->threadId);
				$rec->folderId = $folderId;
			}
		}
	}
	
	
	/**
	 * След рендиране на задачи към задание
	 * 
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 * @return void
	 */
	public static function on_AfterPrepareTasks($mvc, &$data)
	{
		if(Mode::isReadOnly()) return;
		$masterRec = $data->masterData->rec;
		$containerId = $data->masterData->rec->containerId;
		$defDriver = planning_drivers_ProductionTask::getClassId();
		
		// Може ли на артикула да се добавят задачи за производство
		$defaultTasks = cat_Products::getDefaultProductionTasks($data->masterData->rec->productId, $data->masterData->rec->quantity);
		
		$departments = keylist::toArray($masterRec->departments);
		if(!count($departments) && !count($defaultTasks)){
			$departments = array('' => NULL);
		}
		
		$sysId = (count($defaultTasks)) ? key($defaultTasks) : NULL;
		
		$draftRecs = array();
		foreach ($departments as $depId){
			$depFolderId = isset($depId) ? hr_Departments::forceCoverAndFolder($depId) : NULL;
			if(!doc_Folders::haveRightFor('single', $depFolderId)) continue;
			
			$r = new stdClass();
			$r->folderId    = $depFolderId;
			$r->title       = cat_Products::getTitleById($masterRec->productId);
			$r->systemId    = $sysId;
			$r->driverClass = $defDriver;
			
			if(!$sysId){
				$r->productId = $masterRec->productId;
			}
			
			$draftRecs[]    = $r;
		}
		
		if(count($defaultTasks)){
			foreach ($defaultTasks as $index => $taskInfo){
		
				// Имали от създадените задачи, такива с този индекс
				$foundObject = array_filter($data->recs, function ($a) use ($index) {
					return $a->systemId == $index;
				});
		
				// Ако има не показваме дефолтната задача
				if(is_array($foundObject) && count($foundObject)) continue;
			
				$r = new stdClass();
				$r->title       = $taskInfo->title;
				$r->systemId    = $index;
				$r->driverClass = $taskInfo->driver;
				$draftRecs[]    = $r;
			}
		}
		
		// Вербализираме дефолтните записи
		foreach ($draftRecs as $draft){
			if(!$mvc->haveRightFor('add', (object)array('originId' => $containerId, 'driverClass' => $draft->driverClass))) continue;
		
			$url = array('planning_Tasks', 'add', 'folderId' => $draft->folderId, 'originId' => $containerId, 'driverClass' => $draft->driverClass, 'title' => $draft->title, 'ret_url' => TRUE);
			if(isset($draft->systemId)){
				$url['systemId'] = $draft->systemId;
			} else {
				$url['productId'] = $draft->productId;
			}
			
			$row = new stdClass();
			core_RowToolbar::createIfNotExists($row->_rowTools);
			$row->_rowTools->addLink('', $url, array('ef_icon' => 'img/16/add.png', 'title' => "Добавяне на нова задача за производство"));
				
			$row->title = cls::get('type_Varchar')->toVerbal($draft->title);
			$row->ROW_ATTR['style'] .= 'background-color:#f8f8f8;color:#777';
			if(isset($draft->folderId)){
				$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($draft->folderId))->title;
			}
				
			$data->rows[] = $row;
		}
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if(isset($rec) && empty($rec->originId)){
			$requiredRoles = 'no_one';
		}
		
		if($action == 'add' && isset($rec->originId)){
			// Може да се добавя само към активно задание
			if($origin = doc_Containers::getDocument($rec->originId)){
				if(!$origin->isInstanceOf('planning_Jobs')){
					$requiredRoles = 'no_one';
				}
			}
		}
	}
	
	
	/**
	 * Преди запис на документ
	 */
	public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
	{
		$rec->classId = ($rec->classId) ? $rec->classId : $mvc->getClassId();
		if(!$rec->productId) return;
		
		$productFields = self::getFieldsFromProductDriver($rec->productId);
		$rec->additionalFields = array();
		 
		// Вкарване на записите специфични от драйвера в блоб поле
		if(is_array($productFields)){
			foreach ($productFields as $name => $field){
				if(isset($rec->{$name})){
					$rec->additionalFields[$name] = $rec->{$name};
				}
			}
		}
		 
		$rec->additionalFields = count($rec->additionalFields) ? $rec->additionalFields : NULL;
	}
	
	
	/**
	 * Генерира баркод изображение от даден сериен номер
	 * 
	 * @param string $serial - сериен номер
	 * @return core_ET $img - баркода
	 */
	public static function getBarcodeImg($serial)
	{
		$attr = array();
		
		$conf = core_Packs::getConfig('planning');
		$barcodeType = $conf->PLANNING_TASK_LABEL_COUNTER_BARCODE_TYPE;
		$size = array('width' => $conf->PLANNING_TASK_LABEL_WIDTH, 'height' => $conf->PLANNING_TASK_LABEL_HEIGHT);
		$attr['ratio'] = $conf->PLANNING_TASK_LABEL_RATIO;
		if ($conf->PLANNING_TASK_LABEL_ROTATION == 'yes') {
			$attr['angle'] = 90;
		}
		
		if ($conf->PLANNING_TASK_LABEL_COUNTER_SHOWING == 'barcodeAndStr') {
			$attr['addText'] = array();
		}
		
		// Генериране на баркод от серийния номер, според зададените параметри
		$img = barcode_Generator::getLink($barcodeType, $serial, $size, $attr);
		
		// Връщане на генерираното изображение
		return $img;
	}
	
	
	/**
	 * Информация за произведения артикул по задачата
	 *
	 * @param stdClass $rec
	 * @return stdClass $arr
	 * 			  o productId       - ид на артикула
	 * 			  o packagingId     - ид на опаковката
	 * 			  o quantityInPack  - количество в опаковка
	 * 			  o plannedQuantity - планирано количество
	 * 			  o wastedQuantity  - бракувано количество
	 * 			  o totalQuantity   - прозведено количество
	 * 			  o storeId         - склад
	 * 			  o fixedAssets     - машини
	 * 			  o indTime         - време за пускане
	 * 			  o startTime       - време за прозиводство
	 */
	public static function getTaskInfo($id)
	{
		$rec = static::fetchRec($id);
		
		$Driver = static::getDriver($rec);
		$info = $Driver->getProductDriverInfo($rec);
		
		return $info;
	}
	
	
	/**
	 * Връща масив с плейсхолдърите, които ще се попълват от getLabelData
	 *
	 * @param mixed $id - ид или запис
	 * @return array $fields - полета за етикети
	 */
	public function getLabelPlaceholders($id)
	{
		expect($rec = planning_Tasks::fetchRec($id));
		$fields = array('JOB', 'NAME', 'BARCODE', 'MEASURE_ID', 'QUANTITY', 'ИЗГЛЕД', 'PREVIEW', 'SIZE_UNIT', 'DATE');
		expect($origin = doc_Containers::getDocument($rec->originId));
		$jobRec = $origin->fetch();
		if(isset($jobRec->saleId)){
			$fields[] = 'ORDER';
			$fields[] = 'COUNTRY';
		}
		
		// Извличане на всички параметри на артикула
		$params = static::getTaskProductParams($rec, TRUE);
		
		$params = array_keys(cat_Params::getParamNameArr($params, TRUE));
		$fields = array_merge($fields, $params);
		
		// Добавяне на допълнителни плейсхолдъри от драйвера на артикула
		$tInfo = planning_Tasks::getTaskInfo($rec);
		if($Driver = cat_Products::getDriver($tInfo->productId)){
			$additionalFields = $Driver->getAdditionalLabelData($tInfo->productId, $this);
			if(count($additionalFields)){
				$fields = array_merge($fields, array_keys($additionalFields));
			}
		}
		
		return $fields;
	}
	
	
	/**
	 * Връща данни за етикети
	 * 
	 * @param int $id - ид на задача
	 * @param number $labelNo - номер на етикета
	 * 
	 * @return array $res - данни за етикетите
     * 
     * @see label_SequenceIntf
	 */
	public function getLabelData($id, $labelNo = 0)
	{
		$res = array();
		expect($rec = planning_Tasks::fetchRec($id));
		expect($origin = doc_Containers::getDocument($rec->originId));
		$jobRec = $origin->fetch();
		$tInfo = planning_Tasks::getTaskInfo($rec);
		
		// Информация за артикула и заданието
		$res['JOB'] = "#" . $origin->getHandle();
		$res['NAME'] = cat_Products::getTitleById($tInfo->productId);
		
		// Генериране на баркод
		$serial = planning_TaskSerials::force($id, $labelNo, $tInfo->productId);
		$res['BARCODE'] = self::getBarcodeImg($serial)->getContent();
		
		// Информация за артикула
		$measureId = cat_Products::fetchField($tInfo->productId, 'measureId');
		$res['MEASURE_ID'] = tr(cat_UoM::getShortName($measureId));
		$res['QUANTITY'] = cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($tInfo->quantityInPack);
		if(isset($jobRec->saleId)){
			$res['ORDER'] =  "#" . sales_Sales::getHandle($jobRec->saleId);
			$logisticData = cls::get('sales_Sales')->getLogisticData($jobRec->saleId);
			$res['COUNTRY'] = drdata_Countries::fetchField("#commonName = '{$logisticData['toCountry']}'", 'letterCode2');
		}
		
		// Извличане на всички параметри на артикула
		Mode::push('text', 'plain');
		$params = static::getTaskProductParams($rec, TRUE);
		Mode::pop('text');
		
		$params = cat_Params::getParamNameArr($params, TRUE);
		$res = array_merge($res, $params);
		
		// Генериране на превю на артикула за етикети
		$previewWidth = planning_Setup::get('TASK_LABEL_PREVIEW_WIDTH');
		$previewHeight = planning_Setup::get('TASK_LABEL_PREVIEW_HEIGHT');
		
		// Ако в задачата има параметър за изглед, взима се той
		$previewParamId = cat_Params::fetchIdBySysId('preview');
		if($prevValue = cat_products_Params::fetchField("#classId = {$this->getClassId()} AND #productId = {$rec->id} AND #paramId = {$previewParamId}", 'paramValue')){
			$Fancybox = cls::get('fancybox_Fancybox');
			$preview = $Fancybox->getImage($prevValue, array($previewWidth, $previewHeight), array('550', '550'))->getContent();
		} else {
			
			// Иначе се взима от дефолтния параметър
			$preview = cat_Products::getPreview($tInfo->productId, array($previewWidth, $previewHeight));
		}
		
		if(!empty($preview)){
			$res['ИЗГЛЕД'] = $preview;
			$res['PREVIEW'] = $preview;
		}
		
		$res['SIZE_UNIT'] = 'cm';
		$res['DATE'] = dt::mysql2verbal(dt::today(), 'm/y');
		
		// Ако от драйвера идват още параметри, добавят се с приоритет
		if($Driver = cat_Products::getDriver($tInfo->productId)){
			$additionalFields = $Driver->getAdditionalLabelData($tInfo->productId, $this);
			if(count($additionalFields)){
				$res = $additionalFields + $res;
			}
		}
		
		// Връщане на масива, нужен за отпечатването на един етикет
		return $res;
	}
    
    
	/**
	 * Помощна функция извличаща параметрите на задачата
	 * 
	 * @param stdClass $rec     - запис
	 * @param boolean $verbal   - дали параметрите да са вербални
	 * @return array $params    - масив с обеднението на параметрите на задачата и тези на артикула
	 */
	public static function getTaskProductParams($rec, $verbal = FALSE)
	{
		// Кои са параметрите на артикула
		$classId = planning_Tasks::getClassId();
		$tInfo = planning_Tasks::getTaskInfo($rec);
		$productParams = cat_Products::getParams($tInfo->productId, NULL, TRUE);
		
		// Кои са параметрите на задачата
		$params = array();
		$query = cat_products_Params::getQuery();
		$query->where("#classId = {$classId} AND #productId = {$rec->id}");
		$query->show('paramId,paramValue');
		while($dRec = $query->fetch()){
			$dRec->paramValue = ($verbal === TRUE) ? cat_Params::toVerbal($dRec->paramId, $classId, $rec->id, $dRec->paramValue) : $dRec->paramValue;
			$params[$dRec->paramId] = $dRec->paramValue;
		}
		
		// Обединяване на параметрите на задачата с тези на артикула
		$params = $params + $productParams;
		
		// Връщане на параметрите
		return $params;
	}
	
	
    /**
     * Броя на етикетите, които могат да се отпечатат
     * 
     * @param integer $id
     * @param string $allowSkip
     * 
     * @return integer
     * 
     * @see label_SequenceIntf
     */
    public function getEstimateCnt($id, &$allowSkip)
    {
		// Планираното количество
    	$tInfo = static::getTaskInfo($id);
		
        return $tInfo->plannedQuantity;
    }
    
    
    /**
     * Ф-я връщаща полетата специфични за артикула от драйвера
     *
     * @param int $productId
     * @return array
     */
    public static function getFieldsFromProductDriver($productId)
    {
    	$form = cls::get('core_Form');
    	if($driver = cat_Products::getDriver($productId)){
    		$driver->addTaskFields($productId, $form);
    	}
    	 
    	return $form->selectFields();
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
    	// Филтър по всички налични департаменти
    	$departmentOptions = hr_Departments::makeArray4Select('name', "type = 'workshop' AND #state != 'rejected'");
    	
    	if(count($departmentOptions)){
    		$data->listFilter->FLD('departmentId', 'int', 'caption=Звено');
    		$data->listFilter->setOptions('departmentId', array('' => '') + $departmentOptions);
    		$data->listFilter->showFields .= ',departmentId';
    		
    		// Ако потребителя е служител и има само един департамент, той ще е избран по дефолт
    		$cPersonId = crm_Profiles::getProfile(core_Users::getCurrent())->id;
    		$departments = crm_ext_Employees::fetchField("#personId = {$cPersonId}", 'departments');
    		$departments = keylist::toArray($departments);
    		
    		if(count($departments) == 1){
    			$defaultDepartment = key($departments);
    			$data->listFilter->setDefault('departmentId', $defaultDepartment);
    		}
    		
    		$data->listFilter->input('departmentId');
    	}
    	
    	// Добавяне на оборудването към филтъра
    	$fixedAssets = planning_AssetResources::makeArray4Select('name', "#state != 'rejected'");
    	if(count($fixedAssets)){
    		$data->listFilter->FLD('assetId', 'int', 'caption=Оборудване');
    		$data->listFilter->setOptions('assetId', array('' => '') + $fixedAssets);
    		$data->listFilter->showFields .= ',departmentId,assetId';
    		
    		$data->listFilter->input('assetId');
    	}
    	
    	// Филтър по департамент
    	if($departmentFolderId = $data->listFilter->rec->departmentId){
    		$folderId = hr_Departments::fetchField($departmentFolderId, 'folderId');
    		$data->query->where("#folderId = {$folderId}");
    		
    		unset($data->listFields['folderId']);
    	}
    	
    	if($assetId = $data->listFilter->rec->assetId){
    		$data->query->where("LOCATE('|{$assetId}|', #fixedAssets)");
    	}
    }
}
