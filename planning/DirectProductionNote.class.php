<?php


/**
 * Клас 'planning_DirectProductionNote' - Документ за производство
 *
 * 
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_DirectProductionNote extends planning_ProductionDocument
{
	
	
	/**
	 * Заглавие
	 */
	public $title = 'Протоколи за производство';
	
	
	/**
	 * Абревиатура
	 */
	public $abbr = 'Mpn';
	
	
	/**
	 * Поддържани интерфейси
	 */
	public $interfaces = 'acc_TransactionSourceIntf=planning_transaction_DirectProductionNote,batch_MovementSourceIntf=batch_movements_ProductionDocument';
	
	
	/**
	 * Плъгини за зареждане
	 * 
	 * , acc_plg_Contable
	 */
	public $loadList = 'plg_RowTools2, planning_Wrapper, acc_plg_DocumentSummary, acc_plg_Contable,
                    doc_DocumentPlg, plg_Printing, plg_Clone, plg_Search, bgerp_plg_Blank';
	
	
	/**
	 * Кой има право да чете?
	 */
	public $canConto = 'ceo,planning';
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,planning';
	
	
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,planning';
	
	
	/**
	 * Кой има право да променя?
	 */
	public $canEdit = 'ceo,planning';
	
	
	/**
	 * Кой има право да добавя?
	 */
	public $canAdd = 'ceo,planning';
	
	
	/**
	 * Заглавие в единствено число
	 */
	public $singleTitle = 'Протокол за производство';
	
	
	/**
	 * Файл за единичния изглед
	 */
	public $singleLayoutFile = 'planning/tpl/SingleLayoutDirectProductionNote.shtml';
	
	
	/**
	 * Детайл
	 */
	public $details = 'planning_DirectProductNoteDetails';
	
	
	/**
	 * Кой е главния детайл
	 * 
	 * @var string - име на клас
	 */
	public $mainDetail = 'planning_DirectProductNoteDetails';
	
	
	/**
	 * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
	 * (@see plg_Clone)
	 */
	public $cloneDetailes = 'planning_DirectProductNoteDetails';
	
	
	/**
	 * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
	 */
	public $rowToolsSingleField = 'title';
	
	
	/**
	 * Икона на единичния изглед
	 */
	public $singleIcon = 'img/16/page_paste.png';
	
	
	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = 'valior, title=Документ, productId, quantity=К-во, storeId=В склад,expenseItemId=Разход за, folderId, deadline, createdOn, createdBy';
	
	
	/**
	 * Кои полета от листовия изглед да се скриват ако няма записи в тях
	 */
	public $hideListFieldsIfEmpty = 'deadline,expenseItemId,storeId';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		parent::setDocumentFields($this);
		$this->setField('deadline', 'input=none');
		$this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,mandatory,before=storeId');
		$this->FLD('batch', 'text', 'input=none,caption=Партида,after=productId,forceField');
		$this->FLD('jobQuantity', 'double(smartRound)', 'caption=Задание,input=hidden,mandatory,after=productId');
		$this->FLD('quantity', 'double(smartRound,Min=0)', 'caption=Количество,mandatory,after=jobQuantity');
		$this->FLD('expenses', 'percent', 'caption=Реж. разходи,after=quantity');
		$this->setField('storeId', 'caption=Складове->Засклаждане в,after=expenses');
		$this->FLD('inputStoreId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Складове->Влагане от,after=storeId,input');
		$this->FLD('debitAmount', 'double(smartRound)', 'input=none');
		$this->FLD('expenseItemId', 'acc_type_Item(select=titleNum,allowEmpty,lists=600,allowEmpty)', 'input=none,after=expenses,caption=Вътрешнофирмен разход->За');
		
		$this->setDbIndex('productId');
	}
	
	
	/**
	 * Подготвя формата за редактиране
	 */
	public function prepareEditForm_($data)
	{
		parent::prepareEditForm_($data);
		
		$form = &$data->form;
		
		if(isset($form->rec->id)){
			$form->setField('inputStoreId', 'input=none');
		}
		
		$originRec = doc_Containers::getDocument($form->rec->originId)->rec();
		$form->setDefault('productId', $originRec->productId);
		$form->setReadOnly('productId');
		$shortUom = cat_UoM::getShortName(cat_Products::fetchField($originRec->productId, 'measureId'));
		$form->setField('quantity', "unit={$shortUom}");
		$form->setDefault('jobQuantity', $originRec->quantity);
		
		$quantityFromTasks = planning_TaskActions::getQuantityForJob($originRec->id, 'product');
		$quantityToStore = $quantityFromTasks - $originRec->quantityProduced;
		if($quantityToStore > 0){
			$form->setDefault('quantity', $quantityToStore);
		}
		
		$bomRec = cat_Products::getLastActiveBom($originRec->productId, 'production');
		if(!$bomRec){
			$bomRec = cat_Products::getLastActiveBom($originRec->productId, 'sales');
		}
		
		if(isset($bomRec->expenses)){
			$form->setDefault('expenses', $bomRec->expenses);
		}
		
		$productInfo = cat_Products::getProductInfo($form->rec->productId);
		
		if(!isset($productInfo->meta['canStore'])){
			
			// Ако артикула е нескладируем и не е вложим и не е ДА, показваме полето за избор на разходно перо
			if(!isset($productInfo->meta['canConvert']) && !isset($productInfo->meta['fixedAsset'])){
				$form->setField('expenseItemId', 'input,mandatory');
			}
			
			// Ако заданието, към което е протокола е към продажба, избираме я по дефолт
			if(empty($form->rec->id) && isset($originRec->saleId)){
				$saleItem = acc_Items::fetchItem('sales_Sales', $originRec->saleId);
				$form->setDefault('expenseItemId', $saleItem->id);
			}
			
			$form->setField('storeId', 'input=none');
			$form->setField('inputStoreId', array('caption' => 'Допълнително->Влагане от'));
		}
		
		return $data;
	}
	
	
	/**
	 * Извиква се след въвеждането на данните от Request във формата ($form->rec)
	 *
	 * @param core_Mvc $mvc
	 * @param core_Form $form
	 */
	protected static function on_AfterInputEditForm($mvc, &$form)
	{
		$rec = &$form->rec;
		
		if($form->isSubmitted()){
			$productInfo = cat_Products::getProductInfo($form->rec->productId);
			if(!isset($productInfo->meta['canStore'])){
				$rec->storeId = NULL;
			} else {
				$rec->dealId = NULL;
			}
		}
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид
	 */
	protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		$row->productId = cat_Products::getShortHyperlink($rec->productId);
		$shortUom = cat_UoM::getShortName(cat_Products::fetchField($rec->productId, 'measureId'));
		$row->quantity .= " {$shortUom}";
		
		if(!empty($rec->batch)){
			batch_Defs::appendBatch($rec->productId, $rec->batch, $batch);
			$row->batch = cls::get('type_RichText')->toVerbal($batch);
		}
		
		if(isset($rec->debitAmount)){
			$baseCurrencyCode = acc_Periods::getBaseCurrencyCode($rec->valior);
			$row->debitAmount .= " <span class='cCode'>{$baseCurrencyCode}</span>, " . tr('без ДДС');
		}
		
		if(isset($rec->expenseItemId)){
			$row->expenseItemId = acc_Items::getVerbal($rec->expenseItemId, 'titleLink');
		}
		
		$row->subTitle = (isset($rec->storeId)) ? 'Засклаждане на продукт' : 'Производство на услуга';
		$row->subTitle = tr($row->subTitle);
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	protected static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'add'){
			if(isset($rec)){
				
				// Трябва да има ориджин
				if(empty($rec->originId)){
					$requiredRoles = 'no_one';
				} else {
					
					// Ориджина трябва да е задание за производство
					$originDoc = doc_Containers::getDocument($rec->originId);
					
					if(!$originDoc->isInstanceOf('planning_Jobs')){
						$requiredRoles = 'no_one';
					} else {
						
						// Което не е чернова или оттеглено
						$state = $originDoc->fetchField('state');
						if($state == 'rejected' || $state == 'draft'){
							$requiredRoles = 'no_one';
						} else {
							
							// Ако артикула от заданието не е производим не можем да добавяме документ
							$productId = $originDoc->fetchField('productId');
							$canManifacture = cat_Products::fetchField($productId, 'canManifacture');
							if($canManifacture != 'yes'){
								$requiredRoles = 'no_one';
							}
						}
					}
				}
			}
		}
		
		if($action == 'adddebitamount'){
			$requiredRoles = $mvc->getRequiredRoles('conto', $rec, $userId);
			if($requiredRoles != 'no_one'){
				if(isset($rec)){
					if(planning_DirectProductNoteDetails::fetchField("#noteId = {$rec->id}", 'id')){
						$requiredRoles = 'no_one';
					}
				}
			}
		}
	}
	
	
	/**
	 * Извиква се след подготовката на toolbar-а за табличния изглед
	 */
	protected static function on_AfterPrepareListToolbar($mvc, &$data)
	{
		$data->toolbar->removeBtn('btnAdd');
	}
	
	
	/**
	 * Намира количествата за влагане от задачите
	 * 
	 * @param stdClass $rec
	 * @return array $res
	 */
	protected function getDefaultDetails($rec)
	{
		$res = array();
		
		// Намираме детайлите от задачите и рецеоптите
		$bomDetails = $this->getDefaultDetailsFromBom($rec, $bomId);
		$taskDetails = $this->getDefaultDetailsFromTasks($rec);
		
		// Ако има рецепта
		if($bomId){
			
			// И тя има етапи
			$bomQuery = cat_BomDetails::getQuery();
			$bomQuery->where("#bomId = {$bomId}");
			$bomQuery->where("#type = 'stage'");
			$stages = array();
			while($bRec = $bomQuery->fetch()){
				$stages[$bRec->resourceId] = $bRec->resourceId;
			}
			
			// Махаме от артикулите от задачите, тези които са етапи в рецептата, защото
			// реално те няма да се влагат от склада а се произвеждат на място
			if(count($stages)){
				foreach ($taskDetails as $i => $det){
					if(in_array($det->productId, $stages)){
						unset($taskDetails[$i]);
					}
				}
			}
		}
		
		// За всеки артикул от рецептата добавяме го
		foreach ($bomDetails as $index => $bRec){
			$obj = clone $bRec;
			$obj->quantityFromTasks = $taskDetails[$index]->quantityFromTasks;
			
			$res[$index] = $obj;
		}
		
		// За всеки артикул от задачата добавяме го
		foreach ($taskDetails as $index => $tRec){
			$obj = clone $tRec;
			if(!isset($res[$index])){
				$res[$index] = $obj;
			}
			$res[$index]->quantityFromBom = $bomDetails[$index]->quantityFromBom;
		}
		
		// За всеки детайл намираме дефолтното к-во ако има такова от рецепта, взимаме него иначе от задачите
		foreach ($res as &$detail){
			$detail->quantity = (isset($detail->quantityFromBom)) ? $detail->quantityFromBom : $detail->quantityFromTasks;
		}
		
		// Връщаме намерените дефолтни детайли
		return $res;
	}
	
	
	/**
	 * Намира количествата за влагане от задачите
	 * 
	 * @param stdClass $rec
	 * @return array $details
	 */
	protected function getDefaultDetailsFromTasks($rec)
	{
		$details = array();
		$originRec = doc_Containers::getDocument($rec->originId)->rec();
		
		// Намираме всички непроизводствени действия от задачи
		//@TODO да не се гледа само от този модел
		$aQuery = planning_drivers_ProductionTaskProducts::getQuery();
		$aQuery->EXT('taskState', 'planning_Tasks', 'externalName=state,externalKey=taskId');
		$aQuery->EXT('originId', 'planning_Tasks', 'externalName=originId,externalKey=taskId');
		$aQuery->where("#originId = {$rec->originId}");
		
		// Сумираме ги по тип и ид на продукт
		$aQuery->where("#taskState != 'rejected'");
		$aQuery->XPR('sumQuantity', 'double', "SUM(#realQuantity)");
		$aQuery->groupBy("productId,type");
		
		// Събираме ги в масив
		while($aRec = $aQuery->fetch()){
			$obj = new stdClass();
			$obj->productId = $aRec->productId;
			$obj->type = ($aRec->type == 'input') ? 'input' : 'pop';
			$obj->quantityInPack = 1;
			$obj->quantityFromTasks = $aRec->sumQuantity;
			$obj->packagingId = cat_Products::fetchField($obj->productId, 'measureId');
			$obj->measureId = $obj->packagingId;
			
			$index = $obj->productId . "|" . $obj->type;
			$details[$index] = $obj;
		}
		
		// Връщаме намерените детайли
		return $details;
	}
	
	
	/**
	 * Връща дефолт детайлите на документа, които съотвестват на ресурсите
	 * в последната активна рецепта за артикула
	 * 
	 * @param stdClass $rec   - запис
	 * @return array $details - масив с дефолтните детайли
	 */
	protected function getDefaultDetailsFromBom($rec, &$bomId)
	{
		$details = array();
		$originRec = doc_Containers::getDocument($rec->originId)->rec();
		
		// Ако артикула има активна рецепта
		$bomId = cat_Products::getLastActiveBom($rec->productId, 'production')->id;
		if(!$bomId){
			$bomId = cat_Products::getLastActiveBom($rec->productId, 'sales')->id;
		}
		
		// Ако ням рецепта, не могат да се определят дефолт детайли за влагане
		if(!$bomId) return $details;
		
		// К-ко е произведено до сега и колко ще произвеждаме
		$quantityProduced = $originRec->quantityProduced;
		$quantityToProduce = $rec->quantity + $quantityProduced;
		
		// Извличаме информацията за ресурсите в рецептата за двете количества
		$bomInfo1 = cat_Boms::getResourceInfo($bomId, $quantityProduced, dt::now());
		$bomInfo2 = cat_Boms::getResourceInfo($bomId, $quantityToProduce, dt::now());
		
		// За всеки ресурс
		foreach($bomInfo2['resources'] as $index => $resource){
			
			// Задаваме данните на ресурса
			$dRec = new stdClass();
			$dRec->productId      = $resource->productId;
			$dRec->type           = $resource->type;
			$dRec->packagingId    = $resource->packagingId;
			$dRec->quantityInPack = $resource->quantityInPack;
			
			// Дефолтното к-вво ще е разликата между к-та за произведеното до сега и за произведеното в момента
			$dRec->quantityFromBom  = $resource->propQuantity - $bomInfo1['resources'][$index]->propQuantity;
			
			$pInfo = cat_Products::getProductInfo($resource->productId);
			$dRec->measureId = $pInfo->productRec->measureId;
			$index = $dRec->productId . "|" . $dRec->type;
			$details[$index] = $dRec;
		}
	
		// Връщаме генерираните детайли
		return $details;
	}


	/**
	 * Изпълнява се след създаване на нов запис
	 */
	protected static function on_AfterCreate($mvc, $rec)
	{
		// Ако записа е клониран не правим нищо
		if($rec->_isClone === TRUE) return;
		
		// Ако могат да се генерират детайли от артикула да се
		$details = $mvc->getDefaultDetails($rec);
	
		if($details !== FALSE){
				
			// Ако могат да бъдат определени дефолт детайли според артикула, записваме ги
			if(count($details)){
				foreach ($details as $dRec){
					$dRec->noteId = $rec->id;
					if(isset($rec->inputStoreId)){
						$dRec->storeId = $rec->inputStoreId;
					}
					planning_DirectProductNoteDetails::save($dRec);
				}
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
		// При активиране/оттегляне
		if($rec->state == 'active' || $rec->state == 'rejected'){
			$origin = doc_Containers::getDocument($rec->originId);
			
			planning_Jobs::updateProducedQuantity($origin->that);
			doc_DocumentCache::threadCacheInvalidation($rec->threadId);
		}
	}


	/**
	 * След подготовка на тулбара на единичен изглед
	 */
	protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
	{
		$rec = $data->rec;
	
		if($rec->state == 'active'){
			if(planning_DirectProductNoteDetails::fetchField("#noteId = {$rec->id}")){
				if(cat_Boms::haveRightFor('add', (object)array('productId' => $rec->productId, 'originId' => $rec->originId))){
					$bomUrl = array($mvc, 'createBom', $data->rec->id);
					$data->toolbar->addBtn('Рецепта', $bomUrl, NULL, 'ef_icon = img/16/add.png,title=Създаване на нова рецепта по протокола');
				}
			}
		}
		
		if($data->toolbar->hasBtn('btnConto')){
			if($mvc->haveRightFor('adddebitamount', $rec)){
				$data->toolbar->removeBtn('btnConto');
				$data->toolbar->addBtn('Контиране', array($mvc, 'addDebitAmount', $rec->id, 'ret_url' => array($mvc, 'single', $rec->id)), "id=btnConto{$error}", 'ef_icon = img/16/tick-circle-frame.png,title=Контиране на протокола за производствo');
			}
		}
	}
	
	
	/**
	 * Екшън изискващ подаване на себестойност, когато се опитваме да произведем артикул
	 * без да сме специфицирали неговите материали
	 * 
	 * @return unknown
	 */
	public function act_addDebitAmount()
	{
		// Проверка на параметрите
		$this->requireRightFor('adddebitamount');
		expect($id = Request::get('id', 'int'));
		expect($rec = $this->fetch($id));
		$this->requireRightFor('adddebitamount', $rec);
		
		$form = cls::get('core_Form');
		$url = $this->getSingleUrlArray($id);
		$docTitle = ht::createLink($this->getTitleById($id), $url, FALSE, "ef_icon={$this->singleIcon},class=linkInTitle");
		
		// Подготовка на формата
		$form->title = "Въвеждане на себестойност за|* <b style='color:#ffffcc;'>{$docTitle}</b>";
		$form->info = tr('Не може да се определи себестойноста, защото няма посочени материали');
		$form->FLD('debitAmount', 'double(Min=0)', 'caption=Себестойност,mandatory');
		$baseCurrencyCode = acc_Periods::getBaseCurrencyCode($rec->valior);
		$form->setField('debitAmount', "unit=|*{$baseCurrencyCode} |без ДДС|*");
		$form->input();
		
		if($form->isSubmitted()){
			
			// Ъпдейъваме подадената себестойност
			$rec->debitAmount = $form->rec->debitAmount;
			$this->save($rec, 'debitAmount');
			
			// Редирект към екшъна за контиране
			redirect($this->getContoUrl($id));
		}
		
		$form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png, title = Запис на документа');
		$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close16.png, title=Прекратяване на действията');
		
		$tpl = $form->renderHtml();
		$tpl = $this->renderWrapping($tpl);
		
		return $tpl;
	}
	
	
	/**
	 * Екшън създаващ нова рецепта по протокола
	 */
	public function act_CreateBom()
	{
		cat_Boms::requireRightFor('add');
		expect($id = Request::get('id', 'int'));
		expect($rec = $this->fetch($id));
		cat_Boms::requireRightFor('add', (object)array('productId' => $rec->productId, 'originId' => $rec->originId));
		
		// Подготвяме детайлите на рецептата
		$details = array();
		$dQuery = planning_DirectProductNoteDetails::getQuery();
		$dQuery->where("#noteId = {$id}");
		
		$recsToSave = array();
		
		while ($dRec = $dQuery->fetch()){
			$index = "{$dRec->productId}|{$dRec->type}";
			if(!array_key_exists($index, $recsToSave)){
				$recsToSave[$index] = (object)array('resourceId'     => $dRec->productId, 
													'type'           => $dRec->type,
													'propQuantity'   => 0,
													'packagingId'    => $dRec->packagingId, 
													'quantityInPack' => $dRec->quantityInPack);}
			
			$recsToSave[$index]->propQuantity += $dRec->quantity;
			if($dRec->quantityInPack < $recsToSave[$index]->quantityInPack){
				$recsToSave[$index]->quantityInPack = $dRec->quantityInPack;
				$recsToSave[$index]->packagingId = $dRec->packagingId;
			}
		}
		
		foreach ($recsToSave as &$pRec){
			$pRec->propQuantity /= $pRec->quantityInPack;
		}
		
		// Създаваме новата рецепта
		$newId = cat_Boms::createNewDraft($rec->productId, $rec->quantity, $rec->originId, $recsToSave, NULL, $rec->expenses);
		
		// Записваме, че потребителя е разглеждал този списък
		cat_Boms::logWrite("Създаване на рецепта от протокол за производство", $newId);
		
		// Редирект
		return new Redirect(array('cat_Boms', 'single', $newId), '|Успешно е създадена нова рецепта');
	}
	
	
	/**
	 * Документа винаги може да се активира, дори и да няма детайли
	 */
	public static function canActivate($rec)
	{
		$rec = static::fetchRec($rec);
		
		if(isset($rec->id)){
			$input = planning_DirectProductNoteDetails::fetchField("#noteId = {$rec->id} AND #type = 'input'", 'id');
			$pop = planning_DirectProductNoteDetails::fetchField("#noteId = {$rec->id} AND #type = 'pop'", 'id');
			if($pop && !$input){
			
				return FALSE;
			}
		}
		
		return TRUE;
	}
}
