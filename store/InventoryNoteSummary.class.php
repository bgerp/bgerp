<?php


/**
 * Клас 'store_InventoryNoteSummary'
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
    public $listFields = 'code=Код, productId, measureId=Мярка,blQuantity, quantitySum=Количество->Установено,delta,charge,groupName';
    
        
    /**
     * По кое поле да се групира
     */
    public $groupByField = 'groupName';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'groupName';
    
    
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
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Продукт,mandatory,silent,removeAndRefreshForm=groups,tdClass=large-field');
        $this->FLD('blQuantity', 'double', 'caption=Количество->Очаквано,input=none,notNull,value=0');
        $this->FLD('quantity', 'double(smartRound)', 'caption=Количество->Установено,input=none,size=100');
        $this->FNC('delta', 'double', 'caption=Количество->Разлика');
        $this->FLD('groups', 'keylist(mvc=cat_Groups,select=name)', 'caption=Маркери');
        $this->FLD('charge', 'enum(owner=Собственик,responsible=Отговорник)', 'caption=Начисляване,notNull,value=owner,smartCenter');
        $this->FLD('modifiedOn', 'datetime(format=smartTime)', 'caption=Модифициране||Modified->На,input=none,forceField');
        
        $this->setDbUnique('noteId,productId');
    }
    
    
    /**
     * Изчисляване на количеството на реда в брой опаковки
     */
    protected static function on_CalcDelta(core_Mvc $mvc, $rec)
    {
    	if (!isset($rec->blQuantity) || !isset($rec->quantity)) return;
    
    	$rec->delta = $rec->quantity - $rec->blQuantity;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
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
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->code = $rec->verbalCode;
    	
    	$singleUrlArray = cat_Products::getSingleUrlArray($rec->productId);
    	if(!Mode::is('text', 'xhtml') && !Mode::is('printing') && !Mode::is('pdf')){
    		$row->productId = ht::createLinkRef($row->productId, $singleUrlArray);
    	}
    	
    	if(!Mode::is('blank')){
    		$row->quantitySum = $mvc->renderQuantityCell($rec);
    		$row->quantitySum = "<div id='summary{$rec->id}'>{$row->quantitySum}</div>";
    	}
    	
    	$row->charge = $mvc->renderCharge($rec);
    	
    	// Записваме датата на модифициране в чист вид за сравнение при инвалидирането на кеширането
    	$row->modifiedDate = $rec->modifiedOn;
    	$row->groupName = $rec->groupName;
    	
    	if(Mode::is('blank')){
    		$packs = cat_Products::getPacks($rec->productId);
    		$measureId = key($packs);
    	} else {
    		$measureId = cat_Products::fetchField($rec->productId, 'measureId');
    	}
    	
    	$row->measureId = cat_UoM::getShortName($measureId);
    }
    
    
    /**
     * Рендира разликата
     * 
     * @param stdClass $rec - запис
     * @return core_ET      - стойноста на клетката
     */
    public static function renderDeltaCell($rec)
    {
    	$rec = static::fetchRec($rec);
    	$Double = cls::get('type_Double', array('params' => array('decimals' => 2)));
    	$deltaRow = $Double->toVerbal($rec->delta);
    	if($rec->delta < 0){
    		$deltaRow = "<span class='red'>{$deltaRow}</span>";
    	}
    	
    	return new core_ET($deltaRow);
    }
    
    
    /**
     * Рендира установеното количество
     *
     * @param stdClass $rec - запис
     * @return core_ET      - стойноста на клетката
     */
    public static function renderQuantityCell($rec)
    {
    	$rec = self::fetchRec($rec);
    	$quantity = cls::get('type_Double', array('params' => array('smartRound' => 'smartRound')))->toVerbal($rec->quantity);
    	$newQuantity = $quantity;
    	
    	$quantityArr = array('quantity' => $quantity);
    	$quantityTpl = new core_ET("<span><b>[#quantity#]</b></span>[#test#]<!--ET_BEGIN link--><span style='margin-left:3px'>[#link#]</span><!--ET_END link--><!--ET_BEGIN history--><div><small>[#history#]</small></div><!--ET_END history-->");
    
    	if(!Mode::is('printing') && !Mode::is('text', 'xhtml') && !Mode::is('pdf')){
    		if($history = store_InventoryNoteDetails::getHistory($rec)){
    			$quantityArr['history'] = $history;
    		}
    	}
    
    	if(!Mode::is('blank')){
    		if(!Mode::is('printing') && !Mode::is('text', 'xhtml') && !Mode::is('pdf')){
    			if(store_InventoryNoteDetails::haveRightFor('insert', (object)array('noteId' => $rec->noteId, 'productId' => $rec->productId))){
    				$url = array('store_InventoryNoteDetails', 'insert', 'noteId' => $rec->noteId, 'productId' => $rec->productId, 'edit' => TRUE, 'replaceId' => "inlineform{$rec->id}");
    				
    				if(Mode::is('screenMode', 'narrow')){
    					unset($url['replaceId']);
    					if(isset($rec->nextId)){
    						$nextRec = static::fetch($rec->nextId, 'productId,noteId');
    						$retUrl = $url;
    						$retUrl['noteId'] = $nextRec->noteId;
    						$retUrl['productId'] = $nextRec->productId;
    						$url['ret_url'] = $retUrl;
    					}
    					
    					$link = ht::createLink('', $url, FALSE, 'ef_icon=img/16/add1-16.png,title=Промяна на установените количества');
    				} else {
    					$url = toUrl($url, 'local');
    					$attr = array('src'      => sbf('img/16/add1-16.png', ''),
    								  'id'       => "inventoryNoteShowAddForm{$rec->id}",
    								  'data-url' => $url, 
    								  'class'    => 'inventoryNoteShowAddForm', 
    								  'title'    => 'Промяна на установените количества');
    					
    					if(isset($rec->nextId)){
    						$attr['data-nextelement'] = "inventoryNoteShowAddForm{$rec->nextId}";
    					}
    					
    					$link = ht::createElement('img', $attr);
    				}
    				
    				$link = "<span class='ajax-form-holder'><span class='ajax-form' id='inlineform{$rec->id}'></span>{$link}</span>";
    				$quantityArr['link'] = $link;
    			}
    		}
    	
    		$quantityTpl->placeArray($quantityArr);
    		$quantityTpl->removeBlocks();
    		$quantityTpl->removePlaces();
    		$newQuantity = $quantityTpl;
    	}
    	
    	return $newQuantity;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    protected static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'togglecharge' && isset($rec)){
    		$state = store_InventoryNotes::fetchField($rec->noteId, 'state');
    		if($state != 'draft'){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    /**
     *  Преди рендиране на лист таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$res, $data)
    {
    	if(!$data->rows) return;
    	$data->listTableMvc->FLD('code', 'varchar', 'smartCenter,tdClass=small-field');
    	$data->listTableMvc->FLD('measureId', 'varchar', 'smartCenter,tdClass=small-field');
    	$data->listTableMvc->FLD('quantitySum', 'double');
    	
    	if(Mode::get('blank')){
    		$data->listTableMvc->setField('quantitySum', 'tdClass=large-field');
    	} else {
    		$data->listFields['charge'] = "Начет|*<br>|МОЛ|*";
    		$pager = cls::get('core_Pager',  array('itemsPerPage' => 200));
    		$pager->itemsCount = count($data->rows);
    		$data->pager = $pager;
    	}
    	
    	foreach ($data->rows as $id => &$row){
    		$rec = &$data->recs[$id];
    		
    		if(isset($rec)){
    			$row->delta = static::renderDeltaCell($rec);
    			$row->delta = "<div id='delta{$rec->id}'>{$row->delta}</div>";
    		}
    		
    		if(isset($data->pager) && !$data->pager->isOnPage()) {
    			unset($data->rows[$id]);
    			continue;
    		}
    		
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
    	
    	if(store_InventoryNoteDetails::haveRightFor('insert', (object)array('noteId' => $data->masterId))){
    		$data->toolbar->addBtn('Артикул', array('store_InventoryNoteDetails', 'insert', 'noteId' => $data->masterId, 'ret_url' => TRUE), 'ef_icon=img/16/star_2.png,title=Добавяне на нов артикул за опис');
    	}
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
    	if($data->masterData->rec->state == 'rejected') return;
    	
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    	$data->listFilter->FLD('threadId', 'key(mvc=doc_Threads)', 'input=hidden');
    	$data->listFilter->setDefault('threadId', $data->masterData->rec->threadId);
    	$data->listFilter->showFields = 'search';
    	$data->listFilter->view = 'horizontal';
    	$data->listFilter->input();
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
    	$rec->modifiedOn = dt::now();
    	
    	// Опитваме се да запишем
    	if($this->save($rec, 'charge,modifiedOn')){
    		
    		// Ако сме в AJAX режим
    		if(Request::get('ajax_mode')) {
    			
    			// Заместваме клетката по AJAX за да визуализираме промяната
    			$resObj = new stdClass();
    			$resObj->func = "html";
    			$resObj->arg = array('id' => "charge{$rec->id}", 'html' => $this->renderCharge($rec), 'replace' => TRUE);
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
    protected static function on_AfterPrepareListFields($mvc, &$res, &$data)
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
    	$icon = ($rec->charge != 'owner') ? 'img/16/checked.png' : 'img/16/unchecked.png';
    	$attr = array('src' => sbf($icon, ''));
    	
    	// Правим линк само ако не сме в някой от следните режими
    	if(!Mode::is('printing') && !Mode::is('text', 'xhtml') && !Mode::is('pdf') && !Mode::is('blank')){
    		if($this->haveRightFor('togglecharge', $rec)){
    			$type = ($rec->charge == 'owner') ? 'отговорника' : 'собственика';
    	
    			$attr['class']    = "toggle-charge";
    			$attr['data-url'] = toUrl(array($this, 'togglecharge', $rec->id), 'local');
    			$attr['title']    = "Смяна за сметка на {$type}";
    		}
    	}
    	
    	$charge = ht::createElement('img', $attr);
    	
    	// Слагаме уникално ид на обграждащия div
    	$charge = "<div id='charge{$rec->id}'>{$charge}</div>";
    	
    	// Връщаме бутона
    	return $charge;
    }
    
    
    /**
     * След извличане на записите от базата данни
     */
    public static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
    {
    	if(!count($data->recs)) return;
    	
    	// Извличаме наведнъж записите за всички артикули в протокола
    	$allProducts = array_map(create_function('$o', 'return $o->productId;'), $data->recs);
    	$productIds = array_values($allProducts);
    	
    	$pQuery = cat_Products::getQuery();
    	$pQuery->show('isPublic,code,name,createdOn');
    	$pQuery->in('id', $productIds);
    	$tmpRecs = $pQuery->fetchAll();
    	
    	// Добавяме в река данни така че да ни е по-лесно за филтриране
    	foreach ($data->recs as $id => &$rec){
    		
    		// Взимаме записа от кеша
    		$pRec = $tmpRecs[$rec->productId];
    		
    		// Вербализираме и нормализираме кода, за да можем да подредим по него
    		$rec->orderCode = cat_Products::getVerbal($pRec, 'code');
    		$rec->verbalCode = $rec->orderCode;
    		$rec->orderCode = strtolower(str::utf2ascii($rec->orderCode));
    		
    		// Вербализираме и нормализираме името, за да можем да подредим по него
    		$rec->orderName = cat_Products::getVerbal($pRec, 'name');
    		$rec->orderName = strtolower(str::utf2ascii($rec->orderName));
    	}
    	
    	//bp($data->recs);
    }
    
    
    /**
     * Филтрираме записи по подходящ начин
     * 
     * @param stdClass $masterRec
     * @param array $recs
     * @return void
     */
    private function filterRecs($masterRec, &$recs)
    {
    	// Ако няма записи не правим нищо
    	if(!is_array($recs)) return;
    	$ordered = array();
    	
    	// Вербализираме и подреждаме маркерите
    	$groups = keylist::toArray($masterRec->groups);
    	cls::get('cat_Groups')->invoke('AfterMakeArray4Select', array(&$groups));
    	
    	// За всеки маркер
    	foreach ($groups as $grId => $groupName){
    		
    		// Отделяме тези записи, които съдържат текущия маркер
    		$res = array_filter($recs, function (&$e) use ($grId, $groupName) {
    			if(keylist::isIn($grId, $e->groups)){
    				$e->groupName = $groupName;
    				return TRUE;
    			} else {
    				return FALSE;
    			}
    		});
    		
    		// Ако има намерени резултати
    		if(count($res)  && is_array($res)){
    			
    			// От $recs, премахваме отделените записи, да не се обхождат отново
    			$recs = array_diff_key($recs, $res);
    			
    			// Проверяваме как трябва да се сортират артикулите вътре по код или по име
    			$orderProductBy = cat_Groups::fetchField($grId, 'orderProductBy');
    			$field = ($orderProductBy === 'code') ? 'orderCode' : 'orderName';
    			
    			// Сортираме артикулите в маркера
    			arr::natOrder($res, $field);
    			
    			// Добавяме артикулите към подредените
    			$ordered += $res;
    		}
    	}
    	
    	// В $recs трябва да са останали несортираните
    	$rest = $recs;
    	if(count($rest) && is_array($rest)){
    		
    		// Ще ги показваме в маркер 'Други'
    		foreach ($rest as &$r1){
    			$r1->groupName = tr('Други'); 
    		}
    		
    		// Подреждаме ги по име
    		arr::natOrder($rest, 'orderName');
    	
    		// Добавяме ги най-накрая
    		$ordered += $rest;
    	}
    	
    	// След всеки подреден запис, записваме кой следва след него
    	$orderedValues = array_values($ordered);
    	for ($i = 0; $i < count($orderedValues); $i++) {
    		$r = $orderedValues[$i];
    		$nextId = $orderedValues[$i + 1]->id;
    	
    		// Записваме в река ид-то на записа, който е след него
    		$ordered[$r->id]->nextId = $nextId;
    	}
    	
    	// Заместваме намерените записи
    	$recs = $ordered;
    }
    
    
    /**
     * Подготвя редовете във вербална форма.
     * Правим кеширане на всичко в $data->rows,
     * и само променените записи ще ги подготвяме наново
     * 
     * @param stdClass $data
     */
    function prepareListRows_(&$data)
    {
    	// Филтрираме записите
    	$this->filterRecs($data->masterData->rec, $data->recs);
    	return parent::prepareListRows_($data);
    	// Ако сме в режим за принтиране/бланка не правим кеширане
    	if(Mode::is('printing')){
    		return parent::prepareListRows_($data);
    	}
    	
    	// Подготвяме ключа за кеширане
    	$key = store_InventoryNotes::getCacheKey($data->masterData->rec);
    	
    	// Проверяваме имали кеш за $data->rows
    	$cache = core_Cache::get($this->Master->className, $key);
    	$cacheRows = !empty($data->listFilter->rec->search) ? FALSE : TRUE;
    	
    	// Ако има кеш за зашисите
    	if(!empty($cache)){
    		$data->rows = $cache;
    		
    		// Обхождаме ги
    		if(is_array($data->rows)){
    			foreach ($data->rows as $id => $row){
    				$rec = $data->recs[$id];
    				
    				if(is_null($rec)){
    					unset($data->rows[$id]);
    					continue;
    				}
    				
    				// Тези които са с дата на модификация по-малка от тази на река им
    				if($rec->modifiedOn > $row->modifiedDate){
    					
    					// Регенерираме им $row-а наново
    					$data->rows[$id] = $this->recToVerbal($rec, arr::combine($data->listFields, '-list'));
    				}
    			}
    		}
    	} else {
    		
    		// Ако няма кеш подготвяме $data->rows стандартно
    		$data = parent::prepareListRows_($data);
    	}
    	
    	// Кешираме $data->rows
    	if($cacheRows === TRUE){
    		core_Cache::set($this->Master->className, $key, $data->rows, 1440);
    	}
    	
    	// Връщаме $data
    	return $data;
    }
    
    
    /**
     * След генериране на ключовите думи
     */
    protected static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
    	$code = cat_Products::getVerbal($rec->productId, 'code');
    		
    	$res .= " " . plg_Search::normalizeText($code);
    }
}