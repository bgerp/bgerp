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
    public $loadList = 'plg_RowTools, batch_Wrapper, plg_AlignDecimals2, plg_Search, plg_Sorting';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'productId, batch, storeId';
    
    
    /**
     * Кои полета да се показват в листовия изглед
     */
    public $listFields = 'id=Пулт, batch, productId, storeId, quantity';
    
    
    /**
     * Поле за показване на пулта за редакция
     */
    public $rowToolsField = 'id';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'batch';
    
    
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
     * Файл с шаблон за единичен изглед на статия
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
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(haveRole('debug')){
    		$data->toolbar->addBtn('TEST', array($mvc, 'test', 'ret_url' => TRUE), 'title=Дебъг');
    		$data->toolbar->addBtn('Изтрий', array($mvc, 'Truncate', 'ret_url' => TRUE), 'title=Дебъг');
    	}
    }
    
    
    /**
     * Тестово създаване на записи за партиди
     * @TODO да се махне, когато записите започнат да се генерират от документите
     */
    function act_Test()
    {
    	requireRole('debug');
    	
    	$form = $this->getForm();
    	$form->FLD('operation', 'enum(in=Влиза, out=Излиза, stay=Стои)', 'caption=Операция,mandatory,before=quantity');
    	$form->title = 'Тест';
    	
    	$form->setOptions('productId', array('' => '') + self::getProductsWithDefs());
    	$form->setField('batch', 'input=hidden');
    	$form->setDefault('quantity', rand(1, 2000));
    	
    	$form->setField('productId', 'removeAndRefreshForm,silent');
    	$form->input(NULL, 'silent');
    	$rec = &$form->rec;
    	
    	if(isset($rec->productId)){
    		$BatchClass = batch_Defs::getBatchDef($rec->productId);
    		if($BatchClass){
    			$form->setField('batch', 'input');
    			$form->setDefault('batch', $BatchClass->getAutoValue($this, 1));
    		}
    	}
    	
    	$form->input();
    	
    	if($form->isSubmitted()){
    		if(!$BatchClass->isValid($rec->batch, $msg)){
    			$form->setError('batch', $msg);
    		}
    		
    		if(!$form->gotErrors()){
    			
    			expect($itemId = self::forceItem($rec->productId, $rec->batch, $rec->storeId));
    			$movementRec = (object)array('itemId' => $itemId, 
    										 'operation' => $rec->operation, 
    										 'quantity' => $rec->quantity,
    										 'docType' => store_ShipmentOrders::getClassId(),
    										 'docId'  => 1,
    										 'date' => dt::now(),
    			);
    			$dRec = batch_Movements::save($movementRec);
    			if($dRec){
    				redirect(array($this, 'list'), FALSE, 'Успех');
    			}
    		}
    	}
    	
    	$form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png, title = Запис на документа');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close16.png, title=Прекратяване на действията');
    	
    	$tpl = $form->renderHtml();
    	$tpl = $this->renderWrapping($tpl);
    	
    	return $tpl;
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
    		
    		// Връщаме ид-то на записа
    		return $rec->id;
    	}
    	
    	// Ако няма записваме го
    	$rec = (object)array('productId' => $productId, 'batch' => $batch, 'storeId' => $storeId);
    	$id = self::save($rec);
    	
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
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
    	$row->storeId = store_Stores::getHyperlink($rec->storeId, TRUE);
    	
    	$measureId = cat_Products::fetchField($rec->productId, 'measureId');
    	$measureShort = cat_UoM::getShortName($measureId);
    	$row->quantity .= " {$measureShort}";
    	
    	$row->quantity = "<span class='red'>{$row->quantity}</span>";
    	$row->ROW_ATTR['class'] = 'state-active';
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
    	$this->save_($rec, 'quantity');
    }
    
    
    /**
     * Изчиства записите
     * @TODO да се махне, когато минат тестовете
     */
    function act_Truncate()
    {
    	requireRole('debug');
    	
    	batch_Movements::truncate();
    	batch_Items::truncate();
    	
    	redirect(array($this, 'list'), FALSE, 'Успех');
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
    	$data->listFilter->showFields = 'search';
    	$data->listFilter->view = 'horizontal';
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
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
     * @return array $storable - масив с артикули
     */
    public static function getBatches($productId, $storeId = NULL)
    {
    	$res = array();
    	
    	$query = self::getQuery();
    	$query->where("#productId = {$productId}");
    	if(isset($storeId)){
    		$query->where("#storeId = {$storeId}");
    	}
    	$query->show('batch');
    	
    	while($rec = $query->fetch()){
    		$res[$rec->batch] = self::getVerbal($rec, 'batch');
    	}
    	
    	return $res;
    }
}