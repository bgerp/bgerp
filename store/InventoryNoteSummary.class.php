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
     * Кой има достъп до листовия изглед
     */
    public $canList = 'no_one';
    
    
    /**
     * Кой има право да променя начисляването?
     */
    public $canSetresponsibleperson = 'ceo, storeMaster';
    
    
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
    public $listFields = 'code=Код, productId, measureId=Мярка,blQuantity, quantity=Количество->Установено,delta,charge,groupName';
    
        
    /**
     * По кое поле да се групира
     */
    public $groupByField = 'groupName';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'groupName,charge';
    
    
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
        $this->FLD('groups', 'keylist(mvc=cat_Groups,select=name)', 'caption=Групи');
        $this->FLD('charge', 'user', 'caption=Начет');
        $this->FLD('modifiedOn', 'datetime(format=smartTime)', 'caption=Модифициране||Modified->На,input=none,forceField');
        
        $this->setDbUnique('noteId,productId');
    }
    
    
    /**
     * Заявка за редовете за начет към МОЛ
     * 
     * @param int $noteId - ид на протокол
     * @return core_Query $query - заявка
     */
    public static function getResponsibleRecsQuery($noteId)
    {
    	// Връщаме заявка селектираща само редовете с количество, и избран МОЛ за начет
    	$query = static::getQuery();
    	$query->where("#noteId = {$noteId}");
    	$query->where("#quantity IS NOT NULL");
    	$query->where("#charge IS NOT NULL");
    	$query->XPR('diff', 'double', 'ROUND(#quantity - #blQuantity, 2)');
    	$query->where("#diff < 0");
    	
    	return $query;
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
    	$row->ROW_ATTR['id'] = "row->{$rec->id}";
    	
    	$singleUrlArray = cat_Products::getSingleUrlArray($rec->productId);
    	if(!Mode::is('text', 'xhtml') && !Mode::is('printing') && !Mode::is('pdf')){
    		$row->productId = ht::createLinkRef($row->productId, $singleUrlArray);
    	}
    	
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
    	
    	if(!isset($rec->quantity) && !Mode::is('printing')){
    		$row->ROW_ATTR['class'] = " quiet";
    	}
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
    	$class = ($rec->delta < 0) ? 'red' : (($rec->delta > 0) ? 'green' : 'quiet');
    	$deltaRow = "<span class='{$class}'>{$deltaRow}</span>";
    	
    	return new core_ET($deltaRow);
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    protected static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'setresponsibleperson' && isset($rec)){
    		$state = store_InventoryNotes::fetchField($rec->noteId, 'state');
    		if($state != 'draft'){
    			$requiredRoles = 'no_one';
    		}

    		if($requiredRoles != 'no_one'){
    			if(!isset($rec->delta) || (isset($rec->delta) && $rec->delta >= 0)){
    				$requiredRoles = 'no_one';
    			}
    		}
    		
    		if(!store_InventoryNotes::haveRightFor('edit', $rec->noteId)){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    /**
     * След рендиране на името на групата
     *
     * @see plg_GroupByField
     * @param core_Mvc $mvc           - модела
     * @param string $res             - името на групата
     * @param stdClass $data          - датата
     * @param string $groupName       - вътршното представяне на групата
     * @param string $groupVerbalName - текущото вербално име на групата
     */
    public static function on_AfterRenderGroupName($mvc, &$res, $data, $groupName, $groupVerbalName)
    {
    	$blankUrl = array();
    	$masterRec = $data->masterData->rec;
    	if($masterRec->state != 'rejected'){
    		if(!Mode::is('printing') && !Mode::is('text', 'xhtml') && !Mode::is('pdf') && !Mode::is('blank')){
    			if(store_InventoryNotes::haveRightFor('single', $masterRec)){
    				$blankUrl = array('store_InventoryNotes', 'single', $data->masterId);
    				$blankUrl['Printing'] = 'yes';
    				$blankUrl['Blank'] = 'yes';
    				$blankUrl[$mvc->groupByField] = $groupName;
    			}
    		}
    	}
    	
    	// Ако можем добавяме към името на раздела бутон за принтиране на бланка само за артикулите с въпросната група
    	if(count($blankUrl)){
    		$title = "Принтиране на бланка за|* '{$groupName}'"; 
    		$link = ht::createLink('', $blankUrl, FALSE, "target=_blank,title={$title},ef_icon=img/16/print_go.png");
    		$res .= " <span style='margin-left:7px'>{$link}</span>";
    	}
    }
    
    
    /**
     *  Преди рендиране на лист таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$res, $data)
    {
    	if(!$data->rows) return;
    	
    	$data->listTableMvc->FLD('code', 'varchar', 'tdClass=small-field');
    	$data->listTableMvc->FLD('measureId', 'varchar', 'tdClass=small-field nowrap');
    	$data->listTableMvc->setField('charge', 'tdClass=charge-td');
    	$masterRec = $data->masterData->rec;
    	
    	$filterByGroup = FALSE;
    	if(Mode::get('blank')){
    		$data->listTableMvc->FLD('quantitySum', 'varchar');
    		$data->listTableMvc->setField('quantitySum', 'tdClass=large-field');
    		
    		$filterName = Request::get($mvc->groupByField, 'varchar');
    		if($filterName){
    			$filterByGroup = TRUE;
    		}
    	} else {
    		$data->listTableMvc->FLD('quantitySum', 'double');
    		if(!Mode::get('printing')){
    			$Pager = cls::get('core_Pager',  array('itemsPerPage' => 200));
    			$Pager->setPageVar($data->masterMvc->className, $data->masterId);
    			$Pager->itemsCount = count($data->rows);
    			$data->pager = $Pager;
    		}
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
    		
    		if($filterByGroup === TRUE && isset($filterName)){
    			if((!$row instanceof core_ET) && isset($rec)){
    				
    				if($rec->{$mvc->groupByField} != $filterName){
	    				unset($data->rows[$id]);
	    				continue;
    				}
    			} else {
    				$fId = "|{$filterName}";
    				if($id != $fId){
    					unset($data->rows[$id]);
    					continue;
    				}
    			}
    		}
    		
    		if(isset($rec)){
    			$row->charge = static::renderCharge($rec);
    		}
    		
    		if($rec->blQuantity < 0 ){
    			$row->blQuantity = "<span class='red'>{$row->blQuantity}</span>";
    		}
    	}
    	
    	plg_RowTools2::on_BeforeRenderListTable($mvc, $res, $data);
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
    	
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png,title=Филтриране на данните');
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
    function act_SetResponsibleperson()
    {
    	$this->requireRightFor('setresponsibleperson');
    	
    	if(!$id = Request::get('id', 'int')){
    		core_Statuses::newStatus('|Невалиден ред|*!', 'error');
    		return status_Messages::returnStatusesArray();
    	}
    	
    	if(!$rec = $this->fetch($id)){
    		core_Statuses::newStatus('|Невалиден ред|*!', 'error');
    		return status_Messages::returnStatusesArray();
    	}
    	
    	$userId = Request::get('userId', 'int');
    	$this->requireRightFor('setresponsibleperson', $rec);
    	if(!$userId){
    		$userId = NULL;
    	}
    	
    	// Сменяме начина на начисляване
    	$rec->charge = $userId; 
    	$rec->modifiedOn = dt::now();
    	
    	$this->save($rec);
    	
    	// Опитваме се да запишем
    	if($this->save($rec)){
    		
    		// Ако сме в AJAX режим
    		if(Request::get('ajax_mode')) {
    			
    			// Заместваме клетката по AJAX за да визуализираме промяната
    			$resObj = new stdClass();
    			$resObj->func = "html";
    			$resObj->arg = array('id' => "charge{$rec->id}", 'html' => static::renderCharge($rec), 'replace' => TRUE);
    			
    			$res = array_merge(array($resObj));
    			
    			return $res;
    		}
    	}
    	
    	redirect(array('store_InventoryNotes', 'single', $rec->noteId));
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
    		
    		// Вербализираме и нормализираме името, за да можем да подредим по него
    		$rec->orderName = cat_Products::getVerbal($pRec, 'name');
    	}
    }
    
    
    /**
     * Рендира колонката за начисляване на МОЛ-а
     */
    public static function renderCharge($rec)
    {
    	$rec = static::fetchRec($rec);
    	$charge = '';
    	$masterRec = store_InventoryNotes::fetch($rec->noteId);
    	
    	$responsibles = array();
    	$chiefs = keylist::toArray(store_Stores::fetchField($masterRec->storeId, 'chiefs'));
    	if(isset($rec->charge)){
    		$chiefs[$rec->charge] = $rec->charge;
    	}
    	
    	foreach ($chiefs as $c){
    		$responsibles[$c] = core_Users::getVerbal($c, 'nick');
    	}
    	
    	$responsibles = array('' => '') + $responsibles;
    	
    	if($masterRec->state == 'draft'){
    		$unsetCharge = TRUE;
    		if(!Mode::isReadOnly() && !Mode::is('blank')){
    			if(static::haveRightFor('setresponsibleperson', $rec)){
    				$attr = array();
    				$attr['class']       = "toggle-charge";
    				$attr['data-url']    = toUrl(array('store_InventoryNoteSummary', 'setResponsiblePerson', $rec->id), 'local');
    				$attr['title']       = "Избор на материално отговорно лице";
    				
    				$charge = ht::createSelect('charge', $responsibles, $rec->charge, $attr);
    				$charge->removePlaces();
    				
    				$unsetCharge = FALSE;
    			}
    		}
    	} else {
    		if((isset($rec->delta) && $rec->delta <= 0 && isset($rec->charge))){
    			$charge = crm_Profiles::createLink($rec->charge);
    		}
    	}
    	
    	if($masterRec->state == 'draft'){
    		$charge = "<span id='charge{$rec->id}'>{$charge}</span>";
    	}
    	
    	return $charge;
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
    	
    	// Вербализираме и подреждаме групите
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
    	
    	// Ако сме в режим за принтиране/бланка не правим кеширане
    	if(Mode::is('printing')){
    		return parent::prepareListRows_($data);
    	}
    	
    	// Подготвяме ключа за кеширане
    	$key = store_InventoryNotes::getCacheKey($data->masterData->rec);
    	
    	// Проверяваме имали кеш за $data->rows
    	//$cache = core_Cache::get($this->Master->className, $key);
    	$cacheRows = !empty($data->listFilter->rec->search) ? FALSE : TRUE;
    	
    	// Ако има кеш за записите
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
    		
    		store_InventoryNoteDetails::getExpandedRows($data->recs, $data->rows, $data->cache);
    	}
    	
    	$uRec = (object)array('id' => $data->masterId, 'cache' => $data->cache);
    	$data->masterMvc->save($uRec);
    	
    	// Кешираме $data->rows
    	if($cacheRows === TRUE){
    		core_Cache::set($this->Master->className, $key, $data->rows, 1440);
    	}
    	
    	Mode::setPermanent("InventoryNotePrevArray{$data->masterId}", array());
    	
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
    
    
    public static function recalc($id)
    {
    	expect($id);
    	$rec = self::fetch($id);
    	$query = store_InventoryNoteDetails::getQuery();
    	$query->where("#noteId = {$rec->noteId} AND #productId = {$rec->productId}");
    	$query->XPR('sumQuantity', 'double', 'SUM(#quantity)');
    	
    	$quantity = $query->fetch()->sumQuantity;
    	
    	$sRec = (object)array('id' => $rec->id, 'quantity' => $quantity, 'modifiedOn' => dt::now());
    	cls::get('store_InventoryNoteSummary')->save_($sRec);
    }
}
