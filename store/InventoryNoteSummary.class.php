<?php


/**
 * Клас 'store_InventoryNoteSummary'
 *
 * Детайли на мениджър на детайлите на протоколите за инвентаризация (@see store_InventoryNotes)
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_InventoryNoteSummary extends doc_Detail
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
    public $loadList = 'plg_GroupByField, store_Wrapper,plg_AlignDecimals2,plg_Search';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'productId';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, store';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да променя начисляването?
     */
    public $canTogglecharge = 'ceo, store';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, measureId=Мярка,blQuantity, quantitySum=Количество->Установено,delta, charge,group';
    
        
    /**
     * По кое поле да се групира
     */
    public $groupByField = 'group';
    
    
    /**
     * Брой записи на страница
     *
     * @var integer
     */
    public $listItemsPerPage = NULL;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('noteId', 'key(mvc=store_InventoryNotes)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Продукт,mandatory,silent,removeAndRefreshForm=groups');
        $this->FLD('blQuantity', 'double', 'caption=Количество->Очаквано,input=none,notNull,value=0');
        $this->FLD('quantity', 'double(smartRound)', 'caption=Количество->Установено,input=none,size=100');
        $this->FNC('delta', 'double', 'caption=Количество->Разлика');
        $this->FLD('groups', 'keylist(mvc=cat_Groups,select=id)', 'caption=Маркери');
        $this->FLD('charge', 'enum(owner=Собственик,responsible=Отговорник)', 'caption=Начисляване,notNull,value=owner,smartCenter');
        
        $this->setDbUnique('noteId,productId');
    }
    
    
    /**
     * Изчисляване на количеството на реда в брой опаковки
     */
    public function on_CalcDelta(core_Mvc $mvc, $rec)
    {
    	if (!isset($rec->blQuantity)) return;
    
    	$rec->delta = $rec->quantity - $rec->blQuantity;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = $data->form;
    	if(isset($form->rec->id)){
    		$form->setField('productId', 'input=none');
    		$form->setField('groups', 'input=none');
    	} else {
    		$form->setOptions('productId', array('' => '') + cat_Products::getByProperty('canStore'));
    		$form->setField('groups', 'input=hidden');
    		
    		if(isset($form->rec->productId)){
    			$form->setDefault('groups', cat_Products::fetchField($form->rec->productId, 'groups'));
    		}
    	}
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
    	$row->measureId = cat_Products::getVerbal($rec->productId, 'measureId');
    	$row->productId = cat_Products::getShortHyperlink($rec->productId);
    	$row->quantity = $mvc->getFieldType('quantity')->toVerbal($rec->quantity);
    	$quantityArr = array('quantity' => $row->quantity);
    	$quantityTpl = new core_ET("<span><b>[#quantity#]</b></span><!--ET_BEGIN link--><span style='margin-left:3px'>[#link#]</span><!--ET_END link--><!--ET_BEGIN history--><div><small>[#history#]</small></div><!--ET_END history-->");
    	
    	if(!Mode::is('printing') && !Mode::is('text', 'xhtml') && !Mode::is('pdf')){
    		if($history = $mvc->getHistory($rec)){
    			$quantityArr['history'] = $history;
    		}
    	}
    	
    	if(!Mode::get('blank')){
    		
    		if(!Mode::is('printing') && !Mode::is('text', 'xhtml') && !Mode::is('pdf')){
    			if(store_InventoryNoteDetails::haveRightFor('add', (object)array('noteId' => $rec->noteId, 'productId' => $rec->productId))){
    				$url = array('store_InventoryNoteDetails', 'Insert', 'noteId' => $rec->noteId, 'productId' => $rec->productId, 'edit' => TRUE, 'ret_url' => TRUE);
    				$icon = ht::createElement('img', array('src' => sbf('img/16/add1-16.png', '')));
    				$link = ht::createLink($icon, $url, FALSE, 'title=Добавяне на установено количество');
    				$quantityArr['link'] = $link;
    			}
    		}
    		 
    		$quantityTpl->placeArray($quantityArr);
    		$quantityTpl->removeBlocks();
    		$quantityTpl->removePlaces();
    		$row->quantitySum = $quantityTpl;
    		
    		$row->charge = $mvc->renderCharge($rec);
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'togglecharge' && isset($rec)){
    		$state = store_InventoryNotes::fetchField($rec->noteId, 'state');
    		if($state != 'draft'){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    /**
     * След подготовка на детайлите, изчислява се общата цена и данните се групират
     */
    public static function on_AfterPrepareDetail($mvc, $res, $data)
    {
    	$recs = &$data->recs;
    	$rows = &$data->rows;
    	
    	if(!$recs) return;
    	$others = array();
    	
    	$groups = keylist::toArray($data->masterData->rec->groups);
    	$options = cat_Groups::makeArray4Select();
    	$intersect = array_intersect_key($options, $groups);
    	
    	$lastRecs = array();
    	foreach ($rows as $id => &$row){
    		$rec1 = $data->recs[$id];
    		
    		$exGroups = cat_Groups::getDescendantArray($rec1->groups);
    		$firstArr = array_intersect_key($exGroups, $intersect);
    		$key = key($firstArr);
    		if($key){
    			$row->group = $intersect[$key];
    			$data->recs[$id]->group = $intersect[$key];
    		} else {
    			$rec1->group = tr('Други');
    			$row->group = tr('Други');
    			$lastRecs[$id] = $rec1;
    			unset($data->recs[$id]);
    		}
    		
    	}
    	
    	// Сортираме опциите
    	uasort($recs, function($a, $b)
    	{
    		if($a->group == $b->group) return 0;
    		return (strnatcasecmp($a->group, $b->group) < 0) ? -1 : 1;
    	});
    	
    	if(count($lastRecs)){
    		$recs = $recs + $lastRecs;
    	}
    }
    
    
    /**
     *  Преди рендиране на лист таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$res, $data)
    {
    	if(!$data->rows) return;
    	$data->listTableMvc->FLD('measureId', 'varchar', 'smartCenter');
    	$data->listTableMvc->FLD('quantitySum', 'double');
    	if(Mode::get('blank')){
    		$data->listTableMvc->setField('quantitySum', 'tdClass=medium-field');
    	} else {
    		$pager = cls::get('core_Pager',  array('itemsPerPage' => 300));
    		$pager->itemsCount = count($data->rows);
    		$data->pager = $pager;
    	}
    	
    	foreach ($data->rows as $id => &$row){
    		if(isset($data->pager) && !$data->pager->isOnPage()) {
    			unset($data->rows[$id]);
    			continue;
    		}
    		
			$rec = &$data->recs[$id];
    		
    		if(!isset($rec->quantity)){
    			$row->delta = "<span class='red'>{$row->delta}</span>";
    		}
    		 
    		if($rec->blQuantity < 0 ){
    			$row->blQuantity = "<span class='red'>{$row->blQuantity}</span>";
    		}
    	}
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	$data->toolbar->removeBtn('btnAdd');
    	
    	if(store_InventoryNoteDetails::haveRightFor('add', (object)array('noteId' => $data->rec->noteId))){
    		$data->toolbar->addBtn('Артикул', array('store_InventoryNoteDetails', 'Insert', 'noteId' => $data->masterId, 'ret_url' => TRUE), 'ef_icon=img/16/star_2.png,title=Добавяне на нов артикул за опис');
    	}
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    	$data->listFilter->FLD('threadId', 'key(mvc=doc_Threads)', 'input=hidden');
    	$data->listFilter->setDefault('threadId', $data->masterData->rec->threadId);
    	$data->listFilter->showFields = 'search';
    	$data->listFilter->view = 'horizontal';
    	$data->listFilter->input();
    }
    
    
    /**
     * Връща историята на реда
     * 
     * @param stdClass $rec
     * @return core_ET $tpl
     */
    private function getHistory($rec)
    {
    	$data = $this->prepareHistory($rec);
    	$tpl = $this->renderHistory($data);
    	
    	return $tpl;
    }
    
    
    /**
     * Подготвя историята
     * 
     * @param stdClass $rec
     * @return stdClass
     */
    private function prepareHistory($rec)
    {
    	$recs = $rows = array();
    	$dQuery = store_InventoryNoteDetails::getQuery();
    	$dQuery->where("#noteId = {$rec->noteId} AND #productId = {$rec->productId}");
    	while($dRec = $dQuery->fetch()){
    		$recs[$dRec->id] = $dRec;
    		$row = store_InventoryNoteDetails::recToVerbal($dRec);
    		$rows[$dRec->id] = $row;
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
     * Форсира запис
     * 
     * @param int $noteId    - ид на протокол
     * @param int $productId - ид на артикула
     * @return int           - ид на форсирания запис
     */
    public static function force($noteId, $productId)
    {
    	// Ако има запис връщаме го
    	if($rec = store_InventoryNoteSummary::fetch("#noteId = {$noteId} AND #productId = {$productId}")){
    		
    		return $rec->id;
    	}
    	
    	$sRec = (object)array('noteId'    => $noteId, 
    						  'productId' => $productId, 
    						  'groups'    => cat_Products::fetchField($productId, 'groups'));
    	
    	// Ако няма запис, създаваме го
    	return self::save($sRec);
    }
    
    
    /**
     * Връща артикулите, които имат описание
     * 
     * @param int $noteId - ид на протокол
     * @return array $res - масив с артикулите в описанието
     */
    public static function getProductsInSummary($noteId)
    {
    	$res = array();
    	$query = self::getQuery();
    	$query->where("#noteId = {$noteId}");
    	$query->show('productId');
    	while($rec = $query->fetch()){
    		$res[$rec->productId] = cat_Products::getTitleById($rec->productId, FALSE);
    	}
    	
    	return $res;
    }
    
    
    /**
     * Екшън за смяна на начисляването
     */
    function act_ToggleCharge()
    {
    	$this->requireRightFor('togglecharge');
    	
    	if(!$id = Request::get('id', 'int')){
    		core_Statuses::newStatus('|Невалиден ред|*!', 'error');
    		return status_Messages::returnStatusesArray();
    	}
    	
    	if(!$rec = $this->fetch($id)){
    		core_Statuses::newStatus('|Невалиден ред|*!', 'error');
    		return status_Messages::returnStatusesArray();
    	}
    	
    	$this->requireRightFor('togglecharge', $rec);
    	
    	// Сменяме начина на начисляване
    	$rec->charge = ($rec->charge == 'owner') ? 'responsible' : 'owner'; 
    	
    	// Опитваме се да запишем
    	if($this->save($rec, 'charge')){
    		
    		// Ако сме в AJAX режим
    		if(Request::get('ajax_mode')) {
    			$replaceId = md5(str::addHash($rec->id, 6, 'charge'));
    			
    			// Заместваме клетката по AJAX за да визуализираме промяната
    			$resObj = new stdClass();
    			$resObj->func = "html";
    			$resObj->arg = array('id' => $replaceId, 'html' => $this->renderCharge($rec), 'replace' => TRUE);
    			$statusData = status_Messages::returnStatusesArray();
    			
    			$res = array_merge(array($resObj), (array)$statusData);
    			
    			// Връщаме очаквания обект
    			return $res;
    		}
    	} else {
    		core_Statuses::newStatus('|Проблем при запис|*!', 'error');
    	}
    	
    	// Редирект
    	if (Request::get('ajax_mode')) {
    		return status_Messages::returnStatusesArray();
    	} else {
    		redirect(array('store_InventoryNotes', 'single', $rec->noteId));
    	}
    }
    
    
    /**
     * Преди подготовката на полетата за листовия изглед
     */
    public static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {
    	if(Mode::get('blank')){
    		unset($data->listFields['delta']);
    		unset($data->listFields['charge']);
    		unset($data->listFields['blQuantity']);
    		$data->listFields['quantitySum'] = 'Количество';
    	}
    }
    
    
    /**
     * Рендира бутона за смяна на начисляването
     * Вика се и след смяната на начисляването по AJAX
     * 
     * @param stdClass $rec   - записа от модела
     * @return string $charge - бутона за смяна
     */
    private function renderCharge($rec)
    {
    	$charge = $this->getVerbal($rec, 'charge');
    	
    	// Правим линк само ако не сме в някой от следните режими
    	if(!Mode::is('printing') && !Mode::is('text', 'xhtml') && !Mode::is('pdf')){
    		if($this->haveRightFor('togglecharge', $rec)){
    			$type = ($rec->charge == 'owner') ? 'отговорника' : 'собственика';
    	
    			$toggleUrl = toUrl(array($this, 'togglecharge', $rec->id), 'local');
    			$chargeAttr = array('class' => "toggle-charge",
				    				'data-url'       => $toggleUrl,
				    				'data-replaceId' => $attr['id'],
				    				'title'          => "Смяна за сметка на {$type}",
				    				'ef_icon'        => 'img/16/arrow_refresh.png');
    	
    			$charge = ht::createFnBtn($charge, NULL, NULL, $chargeAttr);
    		}
    	}
    		
    	// Слагаме уникално ид на обграждащия div
    	$hashCharge = md5(str::addHash($rec->id, 6, 'charge'));
    	$charge = "<div id='{$hashCharge}'>{$charge}</div>";
    	
    	// Връщаме бутона
    	return $charge;
    }
}