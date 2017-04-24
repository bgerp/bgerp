<?php


/**
 * Клас 'store_InventoryNotes'
 *
 * Мениджър за документ за инвентаризация на склад
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov<ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_InventoryNotes extends core_Master
{
    
    
	/**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=store_transaction_InventoryNote';
    
    
    /**
     * Заглавие
     */
    public $title = 'Протоколи за инвентаризация';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Ivn';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,store';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,store';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,storeMaster';
    
    
    /**
     * Кой може да създава продажба към отговорника на склада?
     */
    public $canMakesale = 'ceo,sale';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,storeMaster';
    
    
    /**
     * Кой може да го контира?
     */
    public $canConto = 'ceo,storeMaster';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Протокол за инвентаризация';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/invertory.png';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.8|Логистика";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, store_Wrapper,acc_plg_Contable,doc_DocumentPlg, plg_Printing, acc_plg_DocumentSummary, plg_Search,bgerp_plg_Blank';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'store_InventoryNoteSummary,store_InventoryNoteDetails';
    
    
    /**
     * Главен детайл на модела
     */
    public $mainDetail = 'store_InventoryNoteSummary';
   
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'store/tpl/InventoryNote/SingleLayout.shtml';
    

    /**
     * Да се забрани ли кеширането на документа
     */
    public $preventCache = TRUE;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'valior,title=Документ,storeId,folderId,createdOn,createdBy,modifiedOn,modifiedBy';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'storeId,groups,folderId';
    
    
    /**
     * Име на документа в бързия бутон за добавяне в папката
     */
    public $buttonInFolderTitle = 'Инвентаризация';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('valior', 'date', 'caption=Вальор, mandatory');
    	$this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад, mandatory');
    	$this->FLD('groups', 'keylist(mvc=cat_Groups,select=name)', 'caption=Групи');
    	$this->FLD('hideOthers', 'enum(yes=Да,no=Не)', 'caption=Показване само на избраните групи->Избор, mandatory, notNULL,value=yes,maxRadio=2');
    	$this->FLD('cache', 'blob(serialize, compress)', 'input=none');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'makesale' && isset($rec->id)){
    		if($rec->state != 'active'){
    			$requiredRoles = 'no_one';
    		} else {
    			$responsible = $mvc->getSelectedResponsiblePersons($rec);
    			if(!count($responsible)){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    	
    	if(($action == 'add' || $action == 'edit') && isset($rec)){
    		if(isset($rec->folderId)){
    			if(!doc_Folders::haveRightToFolder($rec->folderId, $userId)){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    }
    
    
    /**
     * Намира МОЛ-те на които ще начитаме липсите
     * 
     * @param stdClass $rec
     * @return array $options
     */
    private static function getSelectedResponsiblePersons($rec)
    {
    	$options = array();
    	
    	$dQuery = store_InventoryNoteSummary::getResponsibleRecsQuery($rec->id);
    	$dQuery->show('charge');
    	while($dRec = $dQuery->fetch()){
    		$options[$dRec->charge] = core_Users::getVerbal($dRec->charge, 'nick');
    	}
    	
    	return $options;
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
    	$form->setDefault('valior', dt::today());
    	
    	$form->setDefault('storeId', doc_Folders::fetchCoverId($form->rec->folderId));
    	$form->setReadOnly('storeId');
    	$form->setDefault('hideOthers', 'yes');
    	
    	if(isset($form->rec->id)){
    		$form->setReadOnly('storeId');
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
    		$rec = &$form->rec;
    		if(isset($rec->groups)){
    			$error = FALSE;
    			
    			// Кои са недопустимите групи
    			$notAllowed = array();
    			$groups = keylist::toArray($rec->groups);
    			
    			foreach ($groups as $grId){
    				
    				// Ако текущия маркер е в недопустимите сетваме грешка
    				if(array_key_exists($grId, $notAllowed)){
    					$error = TRUE;
    					break;
    				}
    				
    				// Иначе добавяме него и наследниците му към недопустимите групи
    				$descendant = cat_Groups::getDescendantArray($grId);
    				$notAllowed += $descendant;
    			}
    			
    			if($error === TRUE){
    				
    				// Сетваме грешка ако са избрани групи, които са вложени един в друг
    				$form->setError('groups', 'Избрани са вложени групи');
    			}
    		}
    	}
    }
    
    
    /**
     * Можели документа да се добави в посочената папка
     * 
     * @param $folderId int ид на папката
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
    	$folderClass = doc_Folders::fetchCoverClassName($folderId);
    	
    	return ($folderClass == 'store_Stores') ? TRUE : FALSE;
    }
    
    
    /**
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
    	expect($rec = $this->fetch($id));
    	$title = $this->getRecTitle($rec);
    
    	$row = (object)array(
    			'title'    => $title,
    			'authorId' => $rec->createdBy,
    			'author'   => $this->getVerbal($rec, 'createdBy'),
    			'state'    => $rec->state,
    			'recTitle' => $title
    	);
    
    	return $row;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$self = cls::get(get_called_class());
    	 
    	return tr("|{$self->singleTitle}|* №") . $rec->id;
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = $data->rec;
    	
    	if($rec->state != 'rejected'){
    		if($mvc->haveRightFor('single', $rec->id)){
    			$url = array($mvc, 'getBlankForm', $rec->id, 'ret_url' => TRUE);
    			$data->toolbar->addBtn('Бланка||Blank', $url, 'ef_icon = img/16/print_go.png,title=Разпечатване на бланка,target=_blank');
    		}
    	}
    	
    	if($mvc->haveRightFor('makesale', $rec)){
    		$url = array($mvc, 'makeSale', $rec->id, 'ret_url' => TRUE);
    		$data->toolbar->addBtn('Начет', $url, 'ef_icon = img/16/cart_go.png,title=Начисляване на излишъците на МОЛ-а');
    	}
    	
    	$data->toolbar->removeBtn('btnPrint');
    }
    
    
    /**
     * Екшън създаващ продажба в папката на избран МОЛ
     */
    function act_makeSale()
    {
    	// Проверка за права
    	$this->requireRightFor('makesale');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	$this->requireRightFor('makesale', $rec);
    	
    	// Имали пторебители за начет
    	$options = $this->getSelectedResponsiblePersons($rec);
    	
    	// Подготвяме формата
    	$form = cls::get('core_Form');
    	$form->title = "Избор на МОЛ за начет";
    	$form->FLD('userId', 'key(mvc=core_Users,select=nick)', 'caption=МОЛ,mandatory');
    	
    	$form->setOptions('userId', array('' => '') + $options);
    	if(count($options) == 1){
    		$form->setDefault('userId', key($options));
    	}
    	$form->input();
    	
    	// Ако е събмитната
    	if($form->isSubmitted()){
    		
    		// Кой е избрания потребител?
    		$userId = $form->rec->userId;
    		$personId = crm_Profiles::fetchField("#userId = {$userId}", 'personId');
    		
    		// Създаваме продажба в папката му
    		$fields = array('shipmentStoreId' => $rec->storeId, 'valior' => $rec->valior, 'originId' => $rec->containerId);
    		$saleId = sales_Sales::createNewDraft('crm_Persons', $personId, $fields);
    		
    		// Добавяме редовете, които са за неговото начисляване
    		$dQuery = store_InventoryNoteSummary::getResponsibleRecsQuery($rec->id);
    		$dQuery->where("#charge = {$userId}");
    		while($dRec = $dQuery->fetch()){
    			$quantity = abs($dRec->delta);
    			sales_Sales::addRow($saleId, $dRec->productId, $quantity);
    		}
    		
    		// Редирект при успех
    		redirect(array('sales_Sales', 'single', $saleId));
    	}
    	
    	// Добавяме бутони
    	$form->toolbar->addSbBtn('Продажба', 'save', 'id=save, ef_icon = img/16/cart_go.png', 'title=Създаване на продажба');
    	$form->toolbar->addBtn('Отказ', getRetUrl(),  'id=cancel, ef_icon = img/16/close-red.png', 'title=Прекратяване на действията');
    	
    	// Рендираме формата
    	$tpl = $form->renderHtml();
    	$tpl = $this->renderWrapping($tpl);
    	
    	// Връщаме шаблона
    	return $tpl;
    }
    
    
    /**
     * Преди подготовка на сингъла
     */
    protected static function on_BeforePrepareSingle(core_Mvc $mvc, &$res, $data)
    {
    	if(Request::get('Blank', 'varchar')){
    		Mode::set('blank');
    	}
    }
    
    
    /**
     * След подготовка на сингъла
     */
    protected static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
    	$rec = &$data->rec;
    	$row = &$data->row;
    	
    	$headerInfo = deals_Helper::getDocumentHeaderInfo(NULL, NULL);
    	$row = (object)((array)$row + (array)$headerInfo);
    	$row->storeId = store_Stores::getHyperlink($rec->storeId, TRUE);
    	
    	$toDate = dt::addDays(-1, $rec->valior);
    	$toDate = dt::verbal2mysql($toDate, FALSE);
    	$row->toDate = $mvc->getFieldType('valior')->toVerbal($toDate);
    	
    	if($storeLocationId = store_Stores::fetchField($data->rec->storeId, 'locationId')){
    		$row->storeAddress = crm_Locations::getAddress($storeLocationId);
    	}
    	
    	$row->sales = array();
    	
    	if(!Mode::is('blank')){
    		$sQuery = sales_Sales::getQuery();
    		$sQuery->where("#originId = {$rec->containerId}");
    		$sQuery->show('id,contragentClassId,contragentId,state');
    		while ($sRec = $sQuery->fetch()){
    			$index = $sRec->contragentClassId . "|" . $sRec->contragentId;
    			if(!array_key_exists($index, $row->sales)){
    				$userId = crm_Profiles::fetchField("#personId = {$sRec->contragentId}", 'userId');
    				$row->sales[$index] = (object)array('sales' => array(), 'link' => crm_Profiles::createLink($userId));
    			}
    		
    			$class = "state-{$sRec->state}";
    			$link = sales_Sales::getLink($sRec->id, 0, FALSE);
    			$row->sales[$index]->sales[] = "<span class='{$class}'>{$link}</span>";
    		}
    	}
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	foreach ($data->row->sales as $saleObject){
    		$saleObject->sales = implode(', ', $saleObject->sales);
    		$block = clone $tpl->getBlock('link');
    		$block->placeObject($saleObject);
    		$block->removeBlocks();
    		$block->removePlaces();
    		$tpl->append($block, 'SALES_BLOCK');
    	}
    }


    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    protected static function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
    	if(!Mode::is('printing')){
    		$tpl->removeBlock('COUNTER');
    	}
    }

    
    /**
     * Връща артикулите в протокола
     * 
     * @param stdClass $rec - ид или запис
     * @return array $res - масив с артикули
     */
    private function getCurrentProducts($rec)
    {
    	$res = array();
    	$rec = $this->fetchRec($rec);
    	
    	$query = store_InventoryNoteSummary::getQuery();
    	$query->where("#noteId = {$rec->id}");
    	$query->show('noteId,productId,blQuantity,groups,modifiedOn');
    	
    	while($dRec = $query->fetch()){
    		$res[] = $dRec;
    	}
    	
    	return $res;
    }
    
    
    /**
     * Масив с артикулите срещани в счетоводството
     * 
     * @param stClass $rec
     * @return array
     * 		o productId      - ид на артикул
     * 	    o groups         - в кои групи е
     *  	o blQuantity     - к-во
     *  	o searchKeywords - ключови думи
     *  	o modifiedOn     - текуща дата
     */
    private function getProductsFromBalance($rec)
    {
    	$res = array();
    	$rGroup = cat_Groups::getDescendantArray($rec->groups);
    	$rGroup = keylist::toArray($rGroup);
    	
    	$Summary = cls::get('store_InventoryNoteSummary');
    	
    	// Търсим артикулите от два месеца назад
    	$to = dt::addDays(-1, $rec->valior);
    	$to = dt::verbal2mysql($to, FALSE);
    	
    	$from = dt::addMonths(-2, $to);
    	$from = dt::verbal2mysql($from, FALSE);
    	
    	$now = dt::now();
    	
    	// Изчисляваме баланс за подадения период за склада
    	$storeItemId = acc_Items::fetchItem('store_Stores', $rec->storeId)->id;
    	$Balance = new acc_ActiveShortBalance(array('from' => $from, 'to' => $to, 'accs' => '321', 'cacheBalance' => FALSE, 'item1' => $storeItemId));
    	$bRecs = $Balance->getBalance('321');
    	
    	$productPositionId = acc_Lists::getPosition('321', 'cat_ProductAccRegIntf');
    	
    	// Подготвяме записите в нормален вид
    	if(is_array($bRecs)){
    		foreach ($bRecs as $bRec){
    			
    			// Записите, които не са от избрания склад ги пропускаме
    			if($bRec->ent1Id != $storeItemId) continue;
    			
    			$productId = acc_Items::fetchField($bRec->{"ent{$productPositionId}Id"}, 'objectId');
    			$aRec = (object)array("noteId"     => $rec->id,
    								  "productId"  => $productId,
    								  "groups"     => NULL,
    								  "modifiedOn" => $now,
    								  "blQuantity" => $bRec->blQuantity,);
    			$aRec->searchKeywords = $Summary->getSearchKeywords($aRec);
    			
    			$groups = cat_Products::fetchField($productId, 'groups');
    			if(count($groups)){
    				$aRec->groups = $groups;
    			}
    			
    			$add = TRUE;
    			
    			// Ако е указано че искаме само артикулите с тези групи
    			if($rec->hideOthers == 'yes'){
    				if(!keylist::isIn($rGroup, $aRec->groups)){
    					$add = FALSE;
    				}
    			}
    			
    			if($add === TRUE){
    				$res[] = $aRec;
    			}
    		}
    	}
    	
    	// Връщаме намерените артикули
    	return $res;
    }
    
    
    /**
     * Синхронизиране на множеството на артикулите идващи от баланса
     * и текущите записи.
     * 
     * @param stdClass $rec
     * @return void
     */
    public function sync($id)
    {
    	expect($rec = $this->fetchRec($id));
    	
    	// Дигаме тайм лимита
    	core_App::setTimeLimit(600);
    	
    	// Извличаме артикулите от баланса
    	$balanceArr = $this->getProductsFromBalance($rec);
    	
    	// Извличаме текущите записи
    	$currentArr = $this->getCurrentProducts($rec);
    	 
    	// Синхронизираме двата масива
    	$syncedArr = arr::syncArrays($balanceArr, $currentArr, 'noteId,productId', 'blQuantity,groups,modifiedOn');
    	 
    	$Summary = cls::get('store_InventoryNoteSummary');
    	
    	// Ако има нови артикули, добавяме ги
    	if(count($syncedArr['insert'])){
    		$Summary->saveArray($syncedArr['insert']);
    	}
    	 
    	// На останалите им обновяваме определени полета
    	if(count($syncedArr['update'])){
    		$Summary->saveArray($syncedArr['update'], 'id,noteId,productId,blQuantity,groups,modifiedOn,searchKeywords');
    	}
    	 
    	$deleted = 0;
    	
    	// Ако трябва да се трият артикули
    	if(count($syncedArr['delete'])){
    		foreach ($syncedArr['delete'] as $deleteId){
    			
    			// Трием само тези, които нямат въведено количество
    			$quantity = store_InventoryNoteSummary::fetchField($deleteId, 'quantity');
    			if(!isset($quantity)){
    				$deleted++;
    				store_InventoryNoteSummary::delete($deleteId);
    			}
    		}
    	}
    	 
    	// Дебъг информация
    	if(haveRole('debug')){
    		core_Statuses::newStatus("Данните са синхронизирани");
    		if($deleted){
    			core_Statuses::newStatus("Изтрити са {$deleted} реда");
    		}
    	
    		if($added = count($syncedArr['insert'])){
    			core_Statuses::newStatus("Добавени са {$added} реда");
    		}
    	}
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int $id първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	// Синхронизираме данните само в чернова
    	if($rec->state == 'draft'){
    		$mvc->sync($rec);
    	} elseif($rec->state == 'active' || ($rec->state == 'rejected' && $rec->brState == 'active')) {
    		cls::get('store_InventoryNoteDetails')->invoke('AfterContoOrReject', array($rec));
    	}
    	
    	static::invalidateCache($rec);
    }
    
    
    /**
     * Инвалидиране на кеша на документа
     * 
     * @param mixed $rec – ид или запис
     * @return void 
     */
    public static function invalidateCache($rec)
    {
    	$rec = static::fetchRec($rec);
    	$key = self::getCacheKey($rec);
    	
    	core_Cache::remove('store_InventoryNotes', $key);
    }
    
    
    /**
     * Връща ключа за кеширане на данните
     * 
     * @param stdClass $rec - запис
     * @return string $key  - уникален ключ
     */
    public static function getCacheKey($rec)
    {
    	// Подготвяме ключа за кеширане
    	$cu = core_Users::getCurrent();
    	$lg = core_Lg::getCurrent();
    	$isNarrow = (Mode::is('screenMode', 'narrow')) ? TRUE : FALSE;
    	$key = "ip{$cu}|{$lg}|{$rec->id}|{$isNarrow}|";
    	
    	// Връщаме готовия ключ
    	return $key;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     * @param array $fields
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(isset($fields['-list'])){
    		$row->storeId = store_Stores::getHyperlink($rec->storeId, TRUE);
    		$row->title = $mvc->getLink($rec->id, 0);
    	}
    }
    
    
    /**
     * Документа не може да се активира ако има детайл с количество 0
     */
    public static function on_AfterCanActivate($mvc, &$res, $rec)
    {
    	$res = TRUE;
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	$data->toolbar->removeBtn('btnAdd');
    }
    
    
    /**
     * Рендиране на формата за избор на настройките на бланката
     * 
     * @return core_ET
     */
    public function act_getBlankForm()
    {
    	// Проверка за входни данни
    	$this->requireRightFor('single');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	$this->requireRightFor('single', $rec);
    	
    	$url = array($this, 'single', $id, 'Printing' => TRUE, 'Blank' => TRUE);
    	$groupName = Request::get('groupName', 'varchar');
    	if($groupName){
    		$url['groupName'] = $groupName;
    	}
    	
    	$directRedirect = TRUE;
    	
    	// Подготовка на формата
    	$form = cls::get('core_Form');
    	$form->title = "Настройки за принтиране на бланка от|* <b>" . static::getHyperlink($id, TRUE) . "</b>";
    	
    	if(haveRole('ceo,storeMaster')){
    		$directRedirect = FALSE;
    		$form->FLD('showBlQuantities', 'enum(no=Скриване,yes=Показване)', 'caption=Очаквани количества,mandatory');
    		$form->setDefault('showBlQuantities', 'no');
    	}
    	
    	if(core_Packs::isInstalled('batch')){
    		$directRedirect = FALSE;
    		$form->FLD('batches', 'enum(no=Скриване,yes=Показване)', 'caption=Партиди,mandatory');
    		$form->setDefault('batches', 'yes');
    	}
    	
    	if($directRedirect === TRUE) return new Redirect($url);
    	
    	// Изпращане на формата
    	$form->input();
    	if($form->isSubmitted()){
    		$rec = $form->rec;
    		
    		if($rec->batches == 'yes'){
    			$url['showBatches'] = TRUE;
    		}
    		
    		if($rec->showBlQuantities == 'yes'){
    			$url['showBlQuantities'] = TRUE;
    		}
    		
    		$this->logWrite('Настройки на бланката', $id);
    		
    		// Редирект към урл-то за бланката
    		return new Redirect($url);
    	}
    	
    	// Добавяне на бутоните на формата
    	$form->toolbar->addSbBtn('Бланка', 'save', 'ef_icon = img/16/disk.png, title = Генериране на бланка');
    	$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
    	 
    	// Рендиране на обвивката и формата
    	return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     * @return int $id ид-то на обновения запис
     */
    function updateMaster_($id)
    {
    	$rec = $this->fetchRec($id);
    	
    	$this->save($rec, 'isContable');
    }
    
    
    /**
     * Ре-контиране на счетоводен документ
     */
    public static function on_AfterReConto(core_Mvc $mvc, &$res, $id)
    {
    	$rec = $mvc->fetchRec($id);
    	cls::get('store_InventoryNoteDetails')->invoke('AfterContoMaster', array($rec));
    }
    
    
    /**
     * Контиране на счетоводен документ
     */
    public static function on_AfterConto(core_Mvc $mvc, &$res, $id)
    {
    	$rec = $mvc->fetchRec($id);
    	cls::get('store_InventoryNoteDetails')->invoke('AfterContoMaster', array($rec));
    }
    
    
    /**
     * Оттегляне на документ
     */
    public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
    	$rec = $mvc->fetchRec($id);
    	cls::get('store_InventoryNoteDetails')->invoke('AfterRejectMaster', array($rec));
    }
    
    
    /**
     * Метод за създаване на нов протокол за инвентаризация
     * 
     * @param int $storeId         - склад
     * @param date|NULL $valior    - вальор
     * @param boolean $loadCurrent - дали да се заредят всички артикули в склада
     * @return int $id             - ид на протокола
     */
    public static function createDraft($storeId, $valior = NULL, $loadCurrent = FALSE)
    {
    	$valior = (isset($valior)) ? $valior : dt::today();
    	expect(store_Stores::fetch($storeId), "Няма склад с ид {$storeId}");
    	
    	$rec = (object)array('storeId'    => $storeId, 
    			             'valior'     => $valior, 
    						 'hideOthers' => (!$loadCurrent) ? 'yes' : 'no',
    			             'folderId'   => store_Stores::forceCoverAndFolder($storeId));
    	
    	static::route($rec);
    	
    	$id = static::save($rec);
    	doc_ThreadUsers::addShared($rec->threadId, $rec->containerId, core_Users::getCurrent());
    	
    	return $id;
    }
    
    
    /**
     * Добавяне на ред към протокол за производство
     * 
     * @param int $noteId                       - ид на протокол
     * @param int $productId                    - ид на артикул
     * @param int $packagingId                  - ид на мярка/опаковка
     * @param double $quantityInPack            - к-во в опаковката
     * @param double $foundPackQuantity         - намерено количество опаковки
     * @param double|NULL $expectedPackQuantity - очаквано количество опаковка, ако не се зададе е 0
     * @param string|NULL $batch                - партиден номер, опционален
     * @return int                              - ид на записа                               
     */
    public static function addRow($noteId, $productId, $packagingId, $quantityInPack, $foundPackQuantity, $expectedPackQuantity = NULL, $batch = NULL)
    {
    	// Проверки на параметрите
    	expect($noteRec = store_InventoryNotes::fetch($noteId), "Няма протокол с ид {$noteId}");
    	expect($noteRec->state == 'draft', 'Протокола трябва да е чернова');
    	expect($productRec = cat_Products::fetch($productId), "Няма артикул с ид {$productId}");
    	expect($productRec->canStore == 'yes', 'Артикулът трябва да е складируем');
    	expect($packagingId, "Няма мярка/опаковка");
    	expect(cat_UoM::fetch($packagingId), "Няма опаковка/мярка с ид {$packagingId}");
    	
    	$packs = cat_Products::getPacks($productId);
    	expect(isset($packs[$packagingId]), "Артикулът не поддържа мярка/опаковка с ид {$packagingId}");
    	
    	$Double = cls::get('type_Double');
    	expect($quantityInPack = $Double->fromVerbal($quantityInPack));
    	expect($foundPackQuantity = $Double->fromVerbal($foundPackQuantity));
    	$quantity = $quantityInPack * $foundPackQuantity;
    	if(isset($expectedPackQuantity)){
    		$exQuantity = $quantity * $expectedPackQuantity;
    	}
    	
    	if(isset($expectedPackQuantity)){
    		expect($expectedPackQuantity = $Double->fromVerbal($expectedPackQuantity));
    	}
    	
    	// Подготовка на записа
    	$rec = (object)array('noteId'         => $noteId, 
    			             'productId'      => $productId, 
    			             'packagingId'    => $packagingId, 
    			             'quantityInPack' => $quantityInPack,
    					     'quantity'       => $quantity,
    	);
    	
    	// Валидация на партидния номер ако има
    	if($batch){
    		if(core_Packs::isInstalled('batch')){
    			expect($Def = batch_Defs::getBatchDef($productId), "Опит за задаване на партида на артикул без партида");
    			$Def->isValid($batch, $quantity, $msg);
    			if($msg){
    				expect(FALSE, tr($msg));
    			}
    			
    			$rec->batch = $Def->normalize($batch);
    		}
    	}
    	
    	// Запис на реда
    	store_InventoryNoteDetails::save($rec);
    	
    	// Задаване на очакваното количество
    	if(isset($expectedPackQuantity)){
    		$sId = store_InventoryNoteSummary::force($noteId, $productId);
    		store_InventoryNoteSummary::save((object)array('id' => $sId, 'blQuantity' => $expectedPackQuantity), 'id,blQuantity');
    	}
    	
    	// Връщане на записа
    	return $rec->id;
    }
}