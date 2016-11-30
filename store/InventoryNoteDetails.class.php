<?php


/**
 * Клас 'store_InventoryNoteDetails'
 *
 * Детайли на мениджър на детайлите на протоколите за инвентаризация (@see store_InventoryNotes)
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_InventoryNoteDetails extends doc_Detail
{
	
	
    /**
     * Заглавие
     */
    public $title = 'Детайли на протокола за инвентаризация';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'артикул за опис';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'noteId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'store_Wrapper';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, store';
    
    
    /**
     * Кой има достъп до листовия изглед?
     */
    public $canList = 'no_one';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canInsert = 'ceo, storeMaster';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
        
    /**
     * Активен таб
     */
    public $currentTab = 'Документи->Инвентаризация';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('noteId', 'key(mvc=store_InventoryNotes)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Продукт,input=none,mandatory,silent,refreshForm');
        $this->FLD('packagingId', 'key(mvc=cat_UoM, select=name)', 'caption=Мярка,mandatory,smartCenter,input=hidden,tdClass=small-field nowrap');
        $this->FLD('quantity', 'double(min=0)', 'caption=Количество,input=none');
        $this->FLD('quantityInPack', 'double(decimals=2)', 'input=none,column=none');
        $this->FNC('packQuantity', 'double(decimals=2)', 'caption=Количество,input,mandatory');
    
        $this->setDbUnique('noteId,productId,packagingId');
    }
    
    
    /**
     * Изчисляване на количеството на реда в брой опаковки
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    protected static function on_CalcPackQuantity(core_Mvc $mvc, $rec)
    {
    	if (!isset($rec->quantity) || !isset($rec->quantityInPack)) {
    		return;
    	}
    
    	$rec->packQuantity = $rec->quantity / $rec->quantityInPack;
    }
    
    
    /**
     * Изпълнява се след опаковане на детайла от мениджъра
     *
     * @param stdClass $data
     */
    function renderDetail($data)
    {
    	return new core_ET("");
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->packagingId = cat_UoM::getShortName($rec->packagingId);
    }
    
    
    /**
     * Екшън за добавяне на записи към инвентаризационния опис
     */
    public function act_Insert()
    {
    	$this->requireRightFor('insert');
    	
    	if(Request::get('ajax_mode')) {
    		if(!$noteId = Request::get('noteId', 'key(mvc=store_InventoryNotes)')){
    			core_Statuses::newStatus('|Невалиден протокол|*!', 'error');
    			return status_Messages::returnStatusesArray();
    		}
    		 
    		if(!$productId = Request::get('productId', 'key(mvc=cat_Products)')){
    			core_Statuses::newStatus('|Невалиден артикул|*!', 'error');
    			return status_Messages::returnStatusesArray();
    		}
    	} else {
    		expect($noteId = Request::get('noteId', 'key(mvc=store_InventoryNotes)'));
    	}
    	
    	$rec = (object)array('noteId' => $noteId, 'productId' => $productId);
    	$this->requireRightFor('insert', $rec);
    	
    	// Подготвяме формата
    	$form = $this->getInsertForm($rec);
    	$form->class = 'inventoryNoteInsertForm';
    	
    	// Задаваме екшън на формата
    	$form->setAction(array($this, 'insert', 'noteId' => $rec->noteId, 'productId' => $rec->productId));
    	$form->input();
    	
    	// Ако е събмитната формата
    	if($form->isSubmitted()){
    		$rec = $form->rec;
    		$arr = (array)$rec;
    		$masterRec = store_InventoryNotes::fetch($rec->noteId);
    		
    		$quantity = NULL;
    		
    		$date = dt::now();
    		
    		// Артикулът трябва да има себестойност
    		$price = cat_Products::getWacAmountInStore(1, $rec->productId, $date, $masterRec->storeId);
    		if(!$price){
    			$price = cat_Products::getSelfValue($rec->productId);
    		}
    		
    		if(!$price){
    			$form->setError('productId', 'Артикулът няма себестойност');
    		}
    		
    		if(!$form->gotErrors()){
    			foreach ($arr as $key => $value){
    				$recToClone = (object)array('noteId' => $rec->noteId, 'productId' => $rec->productId);
    				 
    				// За всяка опаковка
    				if(strpos($key, 'pack') !== FALSE){
    					$packagingId = str_replace('pack', '', $key);
    			
    					// Ако има стойност я добавяме
    					if(isset($value)){
    						$dRec = clone $recToClone;
    						$dRec->packagingId = $packagingId;
    						$dRec->quantityInPack = ($rec->{"quantityInPack{$packagingId}"}) ? $rec->{"quantityInPack{$packagingId}"} : 1;
    						$dRec->quantity = $value * $dRec->quantityInPack;
    							
    						$this->isUnique($dRec, $fields, $exRec);
    						if($exRec){
    							$dRec->id = $exRec->id;
    						}
    							
    						// Сумираме и записваме новата стойност
    						$quantity += $dRec->quantity;
    						store_InventoryNoteDetails::save($dRec);
    					} else {
    							
    						// Ако за опаковката няма стойност изтриваме я
    						store_InventoryNoteDetails::delete("#noteId = {$rec->noteId} AND #productId = {$rec->productId} AND #packagingId = {$packagingId}");
    					}
    				}
    			}
    			
    			// Форсираме съмарито на записа
    			$summeryId = store_InventoryNoteSummary::force($rec->noteId, $rec->productId);
    			
    			// Обновяваме количеството
    			$now = dt::now();
    			$sRec = (object)array('id' => $summeryId, 'quantity' => $quantity, 'modifiedOn' => $now);
    			cls::get('store_InventoryNoteSummary')->save_($sRec);
    			
    			// Ако сме в AJAX режим
    			if(Request::get('ajax_mode')) {
    			
    				// Ще рендираме наново колоните за количество и разлика
    				$replaceHtml = store_InventoryNoteSummary::renderQuantityCell($summeryId)->getContent();
    				$replaceDeltaHtml = store_InventoryNoteSummary::renderDeltaCell($summeryId)->getContent();
    			
    				// Заместваме клетката по AJAX за да визуализираме промяната
    				$resObj = new stdClass();
    				$resObj->func = "html";
    				$resObj->arg = array('id' => "summary{$summeryId}", 'html' => $replaceHtml, 'replace' => TRUE);
    			
    				$resObj1 = new stdClass();
    				$resObj1->func = "html";
    				$resObj1->arg = array('id' => "delta{$summeryId}", 'html' => $replaceDeltaHtml, 'replace' => TRUE);
    			
    				$resObj2 = new stdClass();
    				$resObj2->arg = array('nextelement' => $rec->nextelement);
    			
    				$resObj3 = new stdClass();
    				$resObj3->func = "html";
    				$resObj3->arg = array('id' => "charge{$summeryId}", 'html' => store_InventoryNoteSummary::renderCharge($summeryId), 'replace' => TRUE);
    				
    				// Връщаме дали ще скрием класа на реда
    				$showClass = false;
    				if(is_null($quantity)){
    					$showClass = true;
    				}
    				
    				$res = array_merge(array($resObj), array($resObj1), array($resObj2), array($resObj3), array($showClass));
    			
    				// Връщаме очаквания обект
    				core_App::getJson($res);
    			} else {
    				store_InventoryNotes::invalidateCache($rec->noteId);
    				// Ако не сме по аякс правим редирект
    				followRetUrl();
    			}
    		}
    	}
    	
    	// Ако сме в аякс режим добавяме JS бутони
    	if(Request::get('ajax_mode') && $form->cmd != 'refresh'){
    		if(isset($form->rec->nextelement)){
    			$form->toolbar->addFnBtn('Запис и Нов', "submitShowAddForm(this.form)", "id=saveAjaxAndNew,ef_icon = img/16/disk.png");
    		}
    		$form->toolbar->addFnBtn('Запис', "submitAndCloseForm(this.form)", "id=saveAjax,ef_icon = img/16/disk.png");
    		$form->toolbar->addFnBtn('Отказ', "cancelForm()", "id=cancelAjax, ef_icon = img/16/close-red.png");
    	} else {
    		
    		// Иначе добавяме нормални бутони
    		//$form->toolbar->addFnBtn('Запис и Нов', "saveAndNew", "id=saveAndNew,ef_icon = img/16/disk.png");
    		$form->toolbar->addSbBtn('Запис', 'save', 'id=save, ef_icon = img/16/disk.png', 'title=Запис на документа');
    		$form->toolbar->addBtn('Отказ', array('store_InventoryNotes', 'single', $noteId),  'id=cancel, ef_icon = img/16/close-red.png', 'title=Прекратяване на действията');
    	}
    	
    	// Получаваме изгледа на формата
        $tpl = $form->renderHtml();
        
        // Ако сме в аякс мод
        if (Request::get('ajax_mode') && $form->cmd != 'refresh') {
        	
        	// Къде ще реплейснем формата
        	$replaceId = Request::get('replaceId', 'varchar');
        	$id = store_InventoryNoteSummary::fetchField("#noteId = {$noteId} AND #productId = {$productId}");
        	
        	// Подготвяме данните за реплейсване на формата
        	$resObj = new stdClass();
        	$resObj->func = "html";
        	$resObj->arg = array('id' => 'ajax-form', 'html' => $tpl->getContent(), 'replace' => TRUE, 'hasError' => TRUE);
        	
        	// Ако няма грешки
        	if(!$form->gotErrors()){
        		unset($resObj->arg['hasError']);
        	}
        	
        	$resObj2 = new stdClass();
        	$resObj2->func = "setFocus";
        	$resObj2->arg = array('id' => 'focusAjaxField');
        	
        	$res = array_merge(array($resObj), array($resObj2));
        	
        	// Връщаме очаквания обект
        	core_App::getJson($res);
        } else {
        	
        	// Опаковаме изгледа
        	$tpl = $this->renderWrapping($tpl);
        
        	// Връщаме шаблона ако не сме в AJAX режим
        	return $tpl;
        }
    }
    
    
    /**
     * Подготвя формата за инсъртване
     * 
     * @return core_Form $form - формата
     */
    private function getInsertForm()
    {
    	$form = cls::get('core_Form');
    	$form->FLD('noteId', 'key(mvc=store_InventoryNotes)', 'mandatory,silent,input=hidden');
    	$form->FLD('productId', 'key(mvc=cat_Products, select=name)', 'mandatory,silent,caption=Артикул,removeAndRefreshForm');
    	$form->FLD('edit', 'int', 'silent,input=hidden');
    	$form->FLD('nextelement', 'varchar', 'silent,input=hidden');
    	$form->FNC('ret_url', 'varchar(1024)', 'input=hidden,silent');
    	
    	$form->input(NULL, 'silent');
    	
    	$isAjax = (Request::get('ajax_mode') && $form->cmd != 'refresh');
    	if($isAjax){
    		$form->fieldsLayout = getTplFromFile('store/tpl/InventoryNote/FormFieldsAjax.shtml');
    	}
    	
    	$rec = &$form->rec;
    	if($rec->edit){
    		$pTitle = cat_Products::getTitleById($rec->productId);
    		$form->title = "|*<b>{$pTitle}</b>";
    		$form->info = tr('Установено количество');
    		$form->setField('productId', 'input=hidden');
    	} else {
    		$form->title = core_Detail::getEditTitle('store_InventoryNotes', $rec->noteId, $this->singleTitle, NULL);
    		$products = cat_Products::getByProperty('canStore');
			$productsInSummary = store_InventoryNoteSummary::getProductsInSummary($rec->noteId);
			$notUsedProducts = array_diff_key($products, $productsInSummary);
			
			$form->setOptions('productId', array('' => '') + $notUsedProducts);
    	}
    	
    	if(isset($rec->productId)){
    		$refreshForm = array();
    		$packs = cat_Products::getPacks($rec->productId);
    		
    		$count = 1;
    		foreach ($packs as $packId => $value){
    			$attr = array('attr' => array('autocomplete' => 'off'));
    			if($count == 1){
    				$attr['attr']['id'] = 'focusAjaxField';
    			}
    			$form->FLD("pack{$packId}", 'double(min=0)', $attr);
    			
    			$exRec = store_InventoryNoteDetails::fetch("#noteId = {$rec->noteId} AND #productId = {$rec->productId} AND #packagingId = {$packId}");
    			if($exRec){
    				$quantityInPack = $exRec->quantityInPack;
    				$form->setDefault("pack{$packId}", core_Math::roundNumber($exRec->quantity / $quantityInPack));
    			} else {
    				$pRec = cat_products_Packagings::getPack($rec->productId, $packId);
    				$quantityInPack = ($pRec) ? $pRec->quantity : 1;
    			}
    			
    			$value = cat_UoM::getShortName($packId);
    			deals_Helper::getPackInfo($value, $rec->productId, $packId, $quantityInPack);
    			$value = strip_tags($value);
    			$value = str_replace('&nbsp;', '', $value);
    			
    			if($isAjax === TRUE){
    				$tplBlock = clone $form->fieldsLayout->getBlock('field_name');
    				$tplBlock->placeArray(array('field_name' => new core_ET("[#pack{$packId}#]"), 'caption' => $value));
    				$form->fieldsLayout->append($tplBlock, 'CONTENT');
    			}
    			$form->setField("pack{$packId}", "caption=|*{$value}");
    			
    			$form->FLD("quantityInPack{$packId}", 'double', "input=hidden");
    			$form->setDefault("quantityInPack{$packId}", $quantityInPack);
    			$refreshForm[] = "pack{$packId}";
    			$refreshForm[] = "quantityInPack{$packId}";
    			$count++;
    		}
    		
    		$refreshForm = implode('|', $refreshForm);
    		$form->setField('productId', "removeAndRefreshForm={$refreshForm}");
    	}
    	
    	
    	return $form;
    }
    
    
    /**
     * Връща историята на реда
     * 
     * @param stdClass $rec
     * @return core_ET $tpl
     */
    public static function getHistory($summaryRec)
    {
    	$self = cls::get(get_called_class());
    	$data = $self->prepareHistory($summaryRec);
    	$tpl = $self->renderHistory($data);
    	
    	return $tpl;
    }
    
    
    /**
     * Подготвя историята
     * 
     * @param stdClass $rec
     * @return stdClass
     */
    private function prepareHistory($summaryRec)
    {
    	$recs = $rows = array();
    	$dQuery = $this->getQuery();
    	$dQuery->where("#noteId = {$summaryRec->noteId} AND #productId = {$summaryRec->productId}");
    	while($rec = $dQuery->fetch()){
    		$recs[$rec->id] = $rec;
    		$row = $this->recToVerbal($rec);
    		$rows[$rec->id] = $row;
    	}
    	
    	return (object)array('recs' => $recs, 'rows' => $rows);
    }
    
    
    /**
     * Рендира историята
     * 
     * @param stdClass $data
     * @return core_ET $tpl
     */
    private function renderHistory($data)
    {
    	$tpl = new core_ET("<!--ET_BEGIN BLOCK--><div style='color:darkgreen'>[#packQuantity#] <span class='quiet'>[#packagingId#]</span></div><!--ET_END BLOCK-->");
    	foreach ($data->rows as $id => $row){
    		$blockTpl = clone $tpl->getBlock('BLOCK');
    		$blockTpl->placeObject($row);
    		$blockTpl->removeBlocks();
    		$blockTpl->removePlaces();
    		$blockTpl->append2Master();
    	}
    	
    	return $tpl;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    protected static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'insert' && isset($rec)){
    		$state = store_InventoryNotes::fetchField($rec->noteId, 'state');
    		if($state != 'draft'){
    			$requiredRoles = 'no_one';
    		} else {
    			if(!store_InventoryNotes::haveRightFor('edit', $rec->noteId)){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    }
}
