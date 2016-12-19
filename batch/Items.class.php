<?php



/**
 * Партиди
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class batch_Items extends core_Master {
    
	
    /**
     * Заглавие
     */
    public $title = 'Партиди';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, batch_Wrapper, plg_AlignDecimals2, plg_Search, plg_Sorting, plg_State2';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'productId, batch, storeId';
    
    
    /**
     * Кои полета да се показват в листовия изглед
     */
    public $listFields = 'id=Пулт, batch, productId, storeId, quantity, state';
    
    
    /**
     * Поле за показване на пулта за редакция
     */
    public $rowToolsField = 'id';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Партида";
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'batch, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'batch,ceo';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'batch_Movements';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'batch/tpl/SingleLayoutItem.shtml';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,mandatory');
    	$this->FLD('batch', 'varchar(128)', 'caption=Партида,mandatory,smartCenter');
    	$this->FLD('storeId', 'key(mvc=store_Stores,select=name)', 'caption=Склад,mandatory');
    	$this->FLD('quantity', 'double(smartRound)', 'caption=Наличност');
    	
    	$this->setDbUnique('productId,batch,storeId');
    }
    
    
    /**
     * Връща наличното количество от дадена партида
     * 
     * @param int $productId    - артикул
     * @param string $batch     - партида
     * @param int $storeId      - склад
     * @return double $quantity - к-во на партидата в склада
     */
    public static function getQuantity($productId, $batch, $storeId)
    {
    	$quantity = self::fetchField(array("#productId = {$productId} AND #batch = '[#1#]' AND #storeId = {$storeId}", $batch), 'quantity');
    
    	$quantity = empty($quantity) ? 0 : $quantity;
    	
    	return $quantity;
    }
    
    
    /**
     * Форсира запис за партида
     * 
     * @param int $productId - ид на артикул
     * @param string $batch - партиден номер
     * @param int $storeId - ид на склад
     * @return int $id - ид на форсирания запис
     */
    public static function forceItem($productId, $batch, $storeId)
    {
    	expect($productId);
    	expect($batch);
    	expect($storeId);
    	
    	// Имали запис за тази партида
    	if($rec = self::fetch("#productId = '{$productId}' AND #batch = '{$batch}' AND #storeId = '{$storeId}'")){
    		batch_Features::sync($rec->id);
    		
    		// Връщаме ид-то на записа
    		return $rec->id;
    	}
    	
    	// Ако няма записваме го
    	$rec = (object)array('productId' => $productId, 'batch' => $batch, 'storeId' => $storeId);
    	$id = self::save($rec);
    	batch_Features::sync($rec->id);
    	
    	// Връщаме ид-то на записа
    	return $id;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
    	$row->storeId = store_Stores::getHyperlink($rec->storeId, TRUE);
    	
    	$measureId = cat_Products::fetchField($rec->productId, 'measureId');
    	$measureShort = cat_UoM::getShortName($measureId);
    	$row->quantity .= " {$measureShort}";
    	
    	$row->quantity = "<span class='red'>{$row->quantity}</span>";
    	$Definition = batch_Defs::getBatchDef($rec->productId);
    	$row->batch = $Definition->toVerbal($rec->batch);
    	
    	if(isset($fields['-single'])){
    		$row->state = $mvc->getFieldType('state')->toVerbal($rec->state);
    	}
    	
    	if(batch_Movements::haveRightFor('list')){
    		$link = array('batch_Movements', 'list', 'batch' => $rec->batch);
    		if(isset($fields['-list'])){
    			$link += array('productId' => $rec->productId, 'storeId' => $rec->storeId);
    		}
    		
    		$row->batch = ht::createLink($row->batch, $link);
    	}
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
    	
    	if(!$rec) return;
    	$quantity = 0;
    	
    	// Ъпдейтваме к-та спрямо движенията по партидата
    	$dQuery = batch_Movements::getQuery();
    	$dQuery->where("#itemId = {$rec->id}");
    	while($dRec = $dQuery->fetch()){
    		
    		// Ако операцията е 'влизане' увеличаваме к-то
    		if($dRec->operation == 'in') {
    			$quantity += $dRec->quantity;
    		} elseif($dRec->operation == 'out') {
    			
    			// Ако операцията е 'излизане' намаляваме к-то
    			$quantity -= $dRec->quantity;
    		}
    		
    		// Ако операцията е 'стои', не правим нищо
    	}
    	
    	// Опресняваме количеството
    	$rec->quantity = $quantity;
    	$fields = 'quantity';
    	
    	// Ако количеството е 0 проверяваме дали можем да затворим партидата
    	if($rec->quantity == 0 && $rec->state != 'closed'){
    		
    		// Проверяваме имали движения по партидата в зададения интервал
    		$dQuery1 = batch_Movements::getQuery();
    		$dQuery1->where("#itemId = {$rec->id}");
    		$before = core_Packs::getConfigValue('batch', 'BATCH_CLOSE_OLD_BATCHES');
    		$before = dt::addSecs(-1 * $before, dt::today());
    		$dQuery1->where("#createdOn >= '{$before}'");
    		
    		// Ако няма движения през зададеното време по тази партида, затваряме я
    		if(!$dQuery1->fetch()){
    			$rec->state = 'closed';
    			$fields = 'quantity,state';
    		}
    		
    	} else {
    		
    		// Активираме партидата
    		if($rec->state != 'active'){
    			$rec->state = 'active';
    			$fields = 'quantity,state';
    		}
    	}
    	
    	$this->save_($rec, $fields);
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
    	$data->listFilter->view = 'horizontal';
    	$data->listFilter->FLD('store', 'key(mvc=store_Stores,select=name,allowEmpty)', 'placeholder=Всички складове');
    	$data->listFilter->FLD('filterState', 'enum(active=Активни,closed=Затворени,expired=Изтичащ срок)', 'placeholder=Състояние');
    	$data->listFilter->showFields = 'search,store,filterState';
    	$data->listFilter->input();
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
    
    	if($filter = $data->listFilter->rec){
    		
    		// Филтрираме по склад
    		if(isset($filter->store)){
    			$data->query->where("#storeId = {$filter->store}");
    		}
    		
    		// Филтрираме по състояние
    		if(isset($filter->filterState)){
    			if($filter->filterState == 'expired'){
    				
    				// Намираме всички артикули с дефиниции
    				$productsWithDates = array();
    				$classId = batch_definitions_ExpirationDate::getClassId();
    				$defQuery = batch_Defs::getQuery();
    				$defQuery->where("#driverClass = '{$classId}'");
    				$defQuery->show('productId');
    				while ($defRec = $defQuery->fetch()){
    					$productsWithDates[$defRec->productId] = $defRec->productId;
    				}
    				
    				// Оставяме само тези партиди, които са с тип срок на годност
    				$data->query->in('productId', $productsWithDates);
    				$data->query->where("#state = 'active'");
    				
    				// Ако няма налични артикули, не искаме да намериме записи
    				if(!count($productsWithDates)){
    					$data->query->where("1 != 1");
    				}
    				
    				// Подреждаме ги от най-старата към най-новата
    				$data->query->orderBy('batch', 'ASC');
    			} else {
    				$data->query->where("#state = '{$filter->filterState}'");
    			}
    		}
    	}
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    public static function on_BeforeRenderListTable($mvc, &$res, $data)
    {
    	if(!count($data->rows)) return;
    	
    	foreach ($data->rows as $id => &$row){
    		if($data->recs[$id]->quantity < 0){
    			$row->quantity = "<span class='red'>{$row->quantity}</span>";
    		}
    	}
    }
    
    
    /**
     * Чръща всички складируеми артикули с дефинирани видове партидност
     * 
     * @return array $storable - масив с артикули
     */
    public static function getProductsWithDefs()
    {
    	$storable = array();
    	$dQuery = batch_Defs::getQuery();
    	while($dRec = $dQuery->fetch()){
    		$storable[$dRec->productId] = cat_Products::getTitleById($dRec->productId, FALSE);
    	}
    	
    	return $storable;
    }
    
    
    /**
     * Чръща всички складируеми артикули с дефинирани видове партидност
     *
     * @param int $productId    - артикул
     * @param int|NULL $storeId - склад
     * @return array $res       - масив с артикули
     */
    public static function getBatches($productId, $storeId = NULL)
    {
    	$res = array();
    	
    	$query = self::getQuery();
    	$query->where("#productId = {$productId}");
    	$query->where("#state != 'closed' AND #quantity != 0");
    	if(isset($storeId)){
    		$query->where("#storeId = {$storeId}");
    	}
    	
    	$query->show('batch');
    	
    	while($rec = $query->fetch()){
    		$res[$rec->batch] = self::getVerbal($rec, 'batch');
    	}
    	
    	return $res;
    }
    

	/**
     * Подготовка на наличните партиди за един артикул
     * 
     * @param stdClass $data
     * @return void
     */
    public function prepareBatches(&$data)
    {
    	// Ако артикула няма партидност, не показваме таба
    	if(!batch_Defs::getBatchDef($data->masterId)){
    		$data->hide = TRUE;
    		return;
    	}
    	 
    	// Име на таба
    	$data->TabCaption = 'Партиди';
    	$data->Tab = 'top';
    	$data->recs = $data->rows = array();
    	
        $attr = array('title' => "История на движенията");
        $attr = ht::addBackgroundIcon($attr, 'img/16/clock_history.png');

        // Подготвяме формата за филтър по склад
        $form = cls::get('core_Form');
        
        $form->FLD("storeId{$data->masterId}", 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,silent');
        $form->view = 'horizontal';
        $form->setAction(getCurrentUrl());
        $form->toolbar->addSbBtn('', 'default', 'id=filter', 'ef_icon=img/16/funnel.png');
        
        // Инпутваме формата
        $form->input();
        $data->form = $form;
        
        // Намираме наличните партиди на артикула
    	$query = $this->getQuery();
    	$query->where("#productId = {$data->masterId}");
    	
    	// Ако филтрираме по склад, оставяме само тези в избрания склад
    	if(isset($data->form->rec->{"storeId{$data->masterId}"})){
    		$data->storeId = $data->form->rec->{"storeId{$data->masterId}"};
    		$query->where("#storeId = {$data->storeId}");
    	}
    	
    	$data->recs = $query->fetchAll();
    	
    	// Подготвяме страницирането
    	$pager = cls::get('core_Pager',  array('itemsPerPage' => 10));
    	$pager->setPageVar($data->masterMvc->className, $data->masterId);
    	$pager->itemsCount = count($data->recs);
    	$data->pager = $pager;
    	
    	// Обръщаме записите във вербален вид
    	foreach ($data->recs as $id => $rec){
    		
    		// Пропускаме записите, които не трябва да са на тази страница
    		if(!$pager->isOnPage()) continue;
    		
    		// Вербално представяне на записа
    		$row = $this->recToVerbal($rec);
    		$row->batch = "<span style='float:left'>{$row->batch}</span>";
    		
    		// Линк към историята защитена
    		Request::setProtected('batch,productId,storeId');
    		$histUrl = array('batch_Movements', 'list', 'batch' => $rec->batch, 'productId' => $rec->productId, 'storeId' => $rec->storeId);
    		$row->icon = ht::createLink('', $histUrl, NULL, $attr);
    		Request::removeProtected('batch,productId,storeId');
    		
    		$data->rows[$rec->id] = $row;
    	}
    }
    
    
    /**
     * Рендиране на наличните партиди за един артикул
     * 
     * @param stdClass $data
     * @return NULL|core_Et $tpl;
     */
    public function renderBatches($data)
    {
    	// Ако не рендираме таба, не правим нищо
    	if($data->hide === TRUE) return;
    	
    	// Кой е шаблона?
    	$tpl = getTplFromFile('batch/tpl/ProductItemDetail.shtml');
    	
    	// Ако има филтър форма, показваме я
    	if(isset($data->form)){
    		$tpl->append($data->form->renderHtml(), 'FILTER');
    	}
    	
    	$fieldSet = cls::get('core_FieldSet');
    	$fieldSet->FLD('batch', 'varchar', 'tdClass=leftCol,smartCenter');
    	$fieldSet->FLD('storeId', 'varchar', 'tdClass=leftCol');
    	$fieldSet->FLD('quantity', 'double');
    	
    	// Подготвяме таблицата за рендиране
    	$table = cls::get('core_TableView', array('mvc' => $fieldSet));
    	$fields = arr::make("batch=Партида,storeId=Склад,quantity=Количество", TRUE);
    	if(count($data->rows)){
    		$fields = array('icon' => ' ') + $fields;
    	}
    	
    	// Ако е филтрирано по склад, скриваме колонката на склада
    	if(isset($data->storeId)){
    		unset($fields['storeId']);
    	}
    	
    	// Рендиране на таблицата с резултатите
    	$dTpl = $table->get($data->rows, $fields);
    	$tpl->append($dTpl, 'content');
    	
    	// Ако има пейджър го рендираме
    	if(isset($data->pager)){
    		$tpl->append($data->pager->getHtml(), 'content');
    	}
    	
    	// Връщаме шаблона
    	return $tpl;
    }
}