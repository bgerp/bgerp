<?php


/**
 * Клас 'store_InventoryNoteDetails'
 *
 * Детайли на мениджър на детайлите на протоколите за инвентаризация (@see store_InventoryNotes)
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2017 Experta OOD
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
    public $canEdit = 'ceo, storeMaster';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, storeMaster';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canInsert = 'ceo, storeMaster';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, storeMaster';
    
        
    /**
     * Активен таб
     */
    public $currentTab = 'Документи->Инвентаризация';
    
    
    /**
     * Кои полета да се извличат при изтриване
     */
    public $fetchFieldsBeforeDelete = 'noteId, productId';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('noteId', 'key(mvc=store_InventoryNotes)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,mandatory,silent');
        $this->FLD('packagingId', 'key(mvc=cat_UoM, select=name)', 'caption=Мярка,mandatory,smartCenter,tdClass=small-field nowrap,removeAndRefreshForm=quantity|quantityInPack');
        $this->FLD('quantity', 'double(min=0)', 'caption=Количество,input=none');
        $this->FLD('quantityInPack', 'double(decimals=2)', 'input=none,column=none');
        $this->FNC('packQuantity', 'double(decimals=2)', 'caption=Количество,input');
    
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
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    protected static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'insert' || $action == 'add' || $action == 'edit') && isset($rec)){
    		$state = store_InventoryNotes::fetchField($rec->noteId, 'state');
    		if($state != 'draft'){
    			$requiredRoles = 'no_one';
    		} else {
    			if(!store_InventoryNotes::haveRightFor('edit', $rec->noteId)){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    	
    	if($requiredRoles == 'no_one') return;
    	
    	if($action == 'add'){
    		if(empty($rec->productId) || empty($rec->noteId)){
    			$requiredRoles = 'no_one';
    		} elseif(!store_InventoryNoteSummary::fetch("#noteId = {$rec->noteId} AND #productId = {$rec->productId}")) {
    			$requiredRoles = 'no_one';
    		} else {
    			$packs = $mvc->getFreeproductPacks($rec->noteId, $rec->productId);
    			if(!count($packs)){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    }
    
    
    /**
     * Връща свободните опаковки за артикула
     * 
     * @param int $noteId    - ид на протокол
     * @param int $productId - ид на артикул
     * @return array $diff   - масив с опции за свободните опаковки
     */
    private function getFreeProductPacks($noteId, $productId)
    {
    	$packs = cat_Products::getPacks($productId);
    	
    	$query = $this->getQuery();
    	$query->where("#noteId = {$noteId} AND #productId = {$productId}");
    	$query->show('packagingId');
    	$alreadyInPack = arr::extractValuesFromArray($query->fetchAll(), 'packagingId');
    	
    	$diff = array_diff_key($packs, $alreadyInPack);
    	
    	return $diff;
    }
    
    
    public static function getExpandedRows(&$summaryRecs, &$summaryRows, &$cache = array())
    {
    	if(!count($summaryRows)) return;
    	
    	$res = array();
    	$recs = array();
    	foreach ($summaryRows as $id => $sRow){
    		$sRec = $summaryRecs[$id];
    		$sRec->measureId = cat_Products::fetchField($sRec->productId, 'measureId');
    		core_RowToolbar::createIfNotExists($sRow->_rowTools);
    		
    		if(self::haveRightFor('add', (object)array('noteId' => $sRec->noteId, 'productId' => $sRec->productId))){
    			$sRow->_rowTools->addLink('Добави', array('store_InventoryNoteDetails', 'add', 'noteId' => $sRec->noteId, 'productId' => $sRec->productId, 'ret_url' => TRUE), "ef_icon=img/16/add.png,title=Добавяне на установено количество,id=add{$id}");
    		}
    		
    		$cache[] = $id;
    		$res[$id] = $sRow;
    		$recs[$id] = $sRec;
    		
    		$query = self::getQuery();
    		$query->where("#noteId = {$sRec->noteId} AND #productId = {$sRec->productId}");
    		while($rec = $query->fetch()){
    			$key = "{$id}|{$rec->id}";
    			$cache[] = $key;
    			
    			$newRec = clone $sRec;
    			unset($newRec->delta, $newRec->blQuantity);
    			$newRec->quantity = $rec->packQuantity;
    			
    			$recs[$key] = $newRec;
    			$row = clone $sRow;
    			unset($row->_rowTools);
    			core_RowToolbar::createIfNotExists($row->_rowTools);
    			
    			if(self::haveRightFor('edit', $rec)){
    				$row->_rowTools->addLink('Редакция', array('store_InventoryNoteDetails', 'edit', $rec->id, 'ret_url' => TRUE), "ef_icon=img/16/edit.png,title=Редакция на установено установено количество,id=edit{$rec->id}");
    			}
    			
    			if(self::haveRightFor('delete', $rec)){
    				$row->_rowTools->addLink('Изтриване', array('store_InventoryNoteDetails', 'delete', $rec->id, 'ret_url' => TRUE), "ef_icon=img/16/delete.png,title=Изтриване на установено количество,id=delete{$rec->id},warning=Наистина ли желаете да изтриете реда|*?");
    			}
    			
    			$row->measureId = cat_UoM::getShortName($rec->packagingId);
    			deals_Helper::getPackInfo($row->measureId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
    			
    			$row->quantity = cls::get('type_Double')->toVerbal($rec->packQuantity);
    			unset($row->delta, $row->blQuantity, $row->charge, $row->code, $row->productId);
    			
    			$res[$key] = $row;
    		}
    	}
    	
    	$summaryRecs = $recs;
    	$summaryRows = $res;
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
    	$rec = &$form->rec;
    	expect($rec->productId);
    	
    	if(empty($rec->id)){
    		$packs = $mvc->getFreeProductPacks($rec->noteId, $rec->productId);
    		$form->setOptions('packagingId', $packs);
    		$form->setDefault('packagingId', key($packs));
    	} else {
    		$form->setReadOnly('packagingId');
    		$form->setDefault('packQuantity', $rec->quantity / $rec->quantityInPack);
    	}
    	
    	$form->setOptions('productId', array($rec->productId => cat_Products::getTitleByid($rec->productId, FALSE)));
    	$form->setField('productId', $rec->productId);
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
    		$rec = $form->rec;
    		
    		$productInfo = cat_Products::getProductInfo($rec->productId);
    		$rec->quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
    		
    		if(isset($rec->packQuantity)){
    			$rec->quantity = $rec->packQuantity * $rec->quantityInPack;
    		} else {
    			$rec->quantity = NULL;
    		}
    	}
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а на формата за редактиране/добавяне
     */
    protected static function on_AfterPrepareEditToolbar($mvc, $data)
    {
    	$data->form->toolbar->addSbBtn('Запис и следващ', 'next', 'id=saveAndNew,order=0.1, ef_icon = img/16/disk.png', 'title=Запис на документа');
    	
    	$freePacks = $mvc->getFreeProductPacks($data->form->rec->noteId, $data->form->rec->productId);
    	unset($freePacks[$data->form->rec->packagingId]);
    	
    	if(is_array($freePacks) && count($freePacks)){
    		$data->form->toolbar->addSbBtn('Запис и нова опаковка', 'newPack', 'id=addPack,order=9, ef_icon = img/16/add.png', 'title=Запис на документа и доабвяне на нова опаковка');
    	}
    	
    	$arr = Mode::get("InventoryNotePrevArray{$data->form->rec->noteId}");
    	if(count($arr)){
    		$data->form->toolbar->addSbBtn("Назад", 'back', 'id=backBtn,order=9, ef_icon = img/16/back_arrow.png', 'title=Към предния запис');
    	}
    }
    
    
    /**
     * Логика за определяне къде да се пренасочва потребителския интерфейс.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareRetUrl($mvc, $data)
    {
    	if(!isset($data->form) || !$data->form->isSubmitted()) return;
    	
    	$rec = $data->form->rec;
    	
    	$cache = array();
    	if(isset($rec->noteId) && isset($rec->productId)){
    		$summeryId = store_InventoryNoteSummary::force($rec->noteId, $rec->productId);
    		$index = "{$summeryId}|{$rec->id}";
    		$cache = store_InventoryNotes::fetchField($rec->noteId, 'cache');
    	}
    	
    	if($data->form->cmd == 'back'){
    		$arr = Mode::get("InventoryNotePrevArray{$rec->noteId}");
    		$prevId = array_pop($arr);
    		
    		if(isset($prevId)){
    			list(, $id) = explode('|', $prevId);
    			$data->retUrl = array($mvc, 'edit', $id, 'ret_url' => $data->retUrl);
    		}
    		
    		Mode::setPermanent("InventoryNotePrevArray{$rec->noteId}", $arr);
    		
    	} elseif($data->form->cmd == 'next' || $data->form->cmd == 'newPack'){
    		$keys = $cache;
    		$count = count($keys);
    		
    		$k = array_search($index, $keys, TRUE);
    		if(!$k){
    			list($sId,) = explode('|', $index);
    			
    			$arr = Mode::get("InventoryNotePrevArray{$rec->noteId}");
    			$prevId = end($arr);
    			if(isset($prevId) && strpos($prevId, "{$sId}|") !== FALSE){
    				$sId = $prevId;
    			}
    			
    			$values = arr::make($keys, TRUE);
    			arr::placeInAssocArray($values, $index, NULL, $sId);
    			$keys = array_values($values);
    			$k = array_search($index, $keys, TRUE);
    			
    			$uRec = (object)array('id' => $rec->noteId, 'cache' => $keys);
    			cls::get('store_InventoryNotes')->save_($uRec);
    		}
    		
    		if($data->form->cmd == 'next'){
    			$url = array();
    			
    			$i = $k;
    			for ($i; $i <= $count - 1; $i++){
    				$k = $keys[$i];
    				$nextKey = $keys[$i + 1];
    				 
    				if(isset($nextKey)){
    					if(strpos($nextKey, '|') === FALSE){
    							
    						$nextNextKey = $keys[$i + 2];
    						if(isset($nextNextKey) && strpos($nextNextKey, '|') !== FALSE){
    							list(, $id) = explode('|', $nextNextKey);
    							$url = array('store_InventoryNoteDetails', 'edit', $id, 'ret_url' => $data->retUrl);
    							break;
    						} else {
    							$sRec = store_InventoryNoteSummary::fetch($nextKey);
    							$freePacks = $mvc->getFreeProductPacks($sRec->noteId, $sRec->productId);
    							if(count($freePacks)){
    								$url = array('store_InventoryNoteDetails', 'add', 'noteId' => $sRec->noteId, 'productId' => $sRec->productId, 'ret_url' => $data->retUrl);
    								break;
    							}
    						}
    							
    					} else {
    						list(, $id) = explode('|', $nextKey);
    						$url = array('store_InventoryNoteDetails', 'edit', $id, 'ret_url' => $data->retUrl);
    						break;
    					}
    				}
    			}
    		} else {
    			$sRec = store_InventoryNoteSummary::fetch($summeryId);
    			$url = array('store_InventoryNoteDetails', 'add', 'noteId' => $sRec->noteId, 'productId' => $sRec->productId, 'ret_url' => $data->retUrl);
    		}
    		
    		if(count($url)){
    			$data->retUrl = $url;
    			
    			if(isset($rec->quantity)){
    				$arr = Mode::get("InventoryNotePrevArray{$rec->noteId}");
    				$arr[] = $index;
    				Mode::setPermanent("InventoryNotePrevArray{$rec->noteId}", $arr);
    			}
    		} else {
    			$data->retUrl = array('store_InventoryNotes', 'single', $rec->noteId);
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
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	if(is_null($rec->quantity)){
    		$mvc->delete("#noteId = {$rec->noteId} AND #productId = {$rec->productId} AND #packagingId = {$rec->packagingId}");
    	}
    	
    	$summeryId = store_InventoryNoteSummary::force($rec->noteId, $rec->productId);
    	store_InventoryNoteSummary::recalc($summeryId);
    }
    
    
    /**
     * След изтриване на запис
     */
    public static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {
    	foreach ($query->getDeletedRecs() as $id => $rec) {
    		$summeryId = store_InventoryNoteSummary::force($rec->noteId, $rec->productId);
    		store_InventoryNoteSummary::recalc($summeryId);
    	}
    }
}
