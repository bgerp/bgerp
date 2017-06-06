<?php



/**
 * Клас 'store_ReserveStockDetails'
 *
 * Детайли на мениджър на детайлите на резервирането на складовите наличности
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_ReserveStockDetails extends doc_Detail
{
	
	
    /**
     * Заглавие
     */
    public $title = 'Детайли на резервираните складови наличност';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Артикул';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'reserveId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Sorting, store_Wrapper, plg_AlignDecimals2,plg_SaveAndNew,plg_RowZebra, plg_RowNumbering';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, store, planning, sales';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, store, planning, sales';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, store, planning, sales';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId, inStockPackQuantity=Наличност,packQuantity=Запазено,freeStockPackQuantity=Разполагаемо';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('reserveId', 'key(mvc=store_ReserveStocks)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('productId', 'key(mvc=cat_Products)', 'caption=Продукт,mandatory,silent,removeAndRefreshForm=packagingId|quantity|quantityInPack,tdClass=productCell leftCol wrap');
        $this->FLD('packagingId', 'key(mvc=cat_UoM, select=name)', 'caption=Мярка,mandatory,smartCenter,input=hidden,tdClass=small-field nowrap');
        $this->FLD('quantity', 'double(Min=0)', 'caption=Количество,input=none');
        $this->FLD('quantityInPack', 'double', 'input=none,column=none');
        $this->FNC('packQuantity', 'double(Min=0)', 'caption=Количество,input,mandatory');
        $this->FNC('inStockPackQuantity', 'double', 'caption=Запазено');
        $this->FNC('freeStockPackQuantity', 'double', 'caption=Разполагаемо');
        $this->FLD('notes', 'richtext(rows=3,bucket=Notes)', 'caption=Забележки');
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
    	$rec = &$form->rec;
    	$masterRec = $data->masterRec;
    	
    	// Всички складируеми артикули
    	$products = cat_Products::getByProperty('canStore');
    	$form->setOptions('productId', array('' => '') + $products);

    	// Ако е избран артикул, показват се опаковките му
    	if(isset($rec->productId)){
    		$fromStoreId = $mvc->Master->fetchField($rec->reserveId, 'storeId');
    		$storeInfo = deals_Helper::checkProductQuantityInStore($rec->productId, $rec->packagingId, $rec->packQuantity, $fromStoreId);
    		$form->info = $storeInfo->formInfo;
    		
    		$packs = cat_Products::getPacks($rec->productId);
    		$form->setField('packagingId', 'input');
    		$form->setOptions('packagingId', $packs);
    		$form->setDefault('packagingId', key($packs));
    	}
    }
    
    
    /**
     * След обработка на записите от базата данни
     */
    protected static function on_AfterPrepareListRows(core_Mvc $mvc, $data)
    {
    	if(!count(count($data->rows))) return;
        $now = dt::now();
    	
        // Количествата в склада
        $productsInStore = store_Products::getQuantitiesInStore($data->masterData->rec->storeId);
        
        // За всеки запис
    	foreach ($data->rows as $i => &$row) {
    		$rec = &$data->recs[$i];
    		
    		$row->productId = cat_Products::getAutoProductDesc($rec->productId, $now, 'short', 'public');
    		deals_Helper::addNotesToProductRow($row->productId, $rec->notes);
    		
    		// Показваме подробната информация за опаковката при нужда
    		deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
    		
    		// Изчисляване на допълнителните полета
    		$rec->inStockPackQuantity = isset($productsInStore[$rec->productId]) ? $productsInStore[$rec->productId] : 0;
    		$rec->inStockPackQuantity /= $rec->quantityInPack;
    		$rec->freeStockPackQuantity = $rec->inStockPackQuantity - $rec->packQuantity;
    		$row->inStockPackQuantity = $mvc->getFieldType('inStockPackQuantity')->toVerbal($rec->inStockPackQuantity);
    		$row->freeStockPackQuantity = $mvc->getFieldType('freeStockPackQuantity')->toVerbal($rec->freeStockPackQuantity);
    	}
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
    	if(!count($data->rows)) return;
    	
    	foreach ($data->rows as $id => $row){
    		$rec = $data->recs[$id];
    		
    		foreach (array('packQuantity', 'inStockPackQuantity', 'freeStockPackQuantity') as $fld){
    			if($rec->{$fld} < 0){
    				$row->{$fld} = "<span class='red'>{$row->{$fld}}</span>";
    			} elseif($rec->{$fld} == 0){
    				$row->{$fld} = "<span class='quiet'>{$row->{$fld}}</span>";
    			}
    		}
    		
    		if($rec->freeStockPackQuantity < 0){
    			$row->packQuantity = ht::createHint($row->packQuantity, 'Резервирано е по-голямо количество от наличното', 'warning', FALSE);
    		}
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
    	$rec = &$form->rec;
    	
    	if ($form->isSubmitted()){
    		$pInfo = cat_Products::getProductInfo($rec->productId);
    		$rec->quantityInPack = ($pInfo->packagings[$rec->packagingId]) ? $pInfo->packagings[$rec->packagingId]->quantity : 1;
    		$rec->quantity = $rec->packQuantity * $rec->quantityInPack;
    	}
    }
    
    
    /**
     * Изчисляване на количеството на реда в брой опаковки
     */
    protected function on_CalcPackQuantity(core_Mvc $mvc, $rec)
    {
    	if(empty($rec->quantity) || empty($rec->quantityInPack)) return;
    
    	$rec->packQuantity = $rec->quantity / $rec->quantityInPack;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    protected static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if(in_array($action, array('add', 'edit', 'delete')) && isset($rec)){
    		$state = store_ReserveStocks::fetchField("#id = {$rec->reserveId}", 'state');
    		if($state == 'rejected'){
    			$requiredRoles = 'no_one';
    		}
    	}
    	
    	// Дали може да се импортира от източника
    	if($action == 'importfromorigin'){
    		$requiredRoles = $mvc->getRequiredRoles('add', $rec, $userId);
    		
    		// Ако е към задание, то трябва да има рецепта
    		if($requiredRoles != 'no_one' && isset($rec->reserveId)){
    			$originId = store_ReserveStocks::fetchField($rec->reserveId, 'originId');
    			$origin = doc_Containers::getDocument($originId);
    			if($origin->isInstanceOf('planning_Jobs')){
    				$bomId = $mvc->getBomFromOrigin($origin);
    				if(!$bomId){
    					$requiredRoles = 'no_one';
    				}
    			}
    		}
    	}
    }
    
    
    /**
     * След подготовка на лист тулбара
     */
    protected static function on_AfterPrepareListToolbar($mvc, $data)
    {
    	if (!empty($data->toolbar->buttons['btnAdd'])) {
    		unset($data->toolbar->buttons['btnAdd']);
    		$products = cat_Products::getByProperty('canStore', NULL, 1);
    		$error = (!count($products)) ? "error=Няма складируеми артикули, " : '';
    
    		$data->toolbar->addBtn('Артикул', array($mvc, 'add', $mvc->masterKey => $data->masterId, 'ret_url' => TRUE),
    				"id=btnAdd,{$error} order=10,title=Добавяне на артикул", 'ef_icon = img/16/shopping.png');
    	}
    	
    	// Добавяне на бутон за импортиране на артикулите директно от договора
		if($mvc->haveRightFor('importfromorigin', (object)array("{$mvc->masterKey}" => $data->masterId))){
			$origin = doc_Containers::getDocument($data->masterData->rec->originId);
			$btnTitle = ($origin->isInstanceOf('planning_Jobs')) ? 'рецептата' : 'договора';
			
			$arr = array('ef_icon' => 'img/16/shopping.png');
			if(!$origin->isInstanceOf('planning_Jobs')){
				$arr['warning'] = 'Ще се попълнят редовете от договора|*!';
			}
			
			$data->toolbar->addBtn("От {$btnTitle}", array($mvc, 'importfromorigin', "{$mvc->masterKey}" => $data->masterId, 'ret_url' => TRUE),
			"id=importfromorigin-{$masterRec->id},{$error} order=10,title=Попълване на артикулите от {$btnTitle}", $arr);
		}
    }
    
    
    /**
     * Намира рецептата от заданието
     * 
     * @param core_ObjectReference $origin
     * @return int|NULL - ид на рецепта или NULL ако няма
     */
    private function getBomFromOrigin(core_ObjectReference $origin)
    {
    	if($origin->isInstanceOf('planning_Jobs')){
    		$productId = $origin->fetchField('productId');
    		$bomId = cat_Products::getLastActiveBom($productId, 'production')->id;
    		if(!$bomId){
    			$bomId = cat_Products::getLastActiveBom($productId, 'sales')->id;
    		}
    		
    		return ($bomId) ? $bomId : NULL;
    	}
    	
    	return NULL;
    }
    
    
    /**
     * Екшън зареждащ дефолтните детайли от източника на документа
     */
    function act_Importfromorigin()
    {
    	// Проверка за права
    	$this->requireRightFor('importfromorigin');
    	expect($masterId = Request::get($this->masterKey, 'int'));
    	$this->requireRightFor('importfromorigin', (object)array("{$this->masterKey}" => $masterId));
    	expect($masterRec = store_ReserveStocks::fetch($masterId));
    	$origin = doc_Containers::getDocument($masterRec->originId);
    	
    	// Ако е към задание
    	if($origin->isInstanceOf('planning_Jobs')){
    		$bomId = $this->getBomFromOrigin($origin);
    		expect($bomId);
    		
    		// Показва се форма за въвеждане за какво к-во да се попълнят материалите
    		$form = cls::get('core_Form');
    		$form->title = "Резервиране на материали по рецепта|* " . cat_Boms::getLink($bomId, 0);
    		$form->FLD('quantity', 'double', 'caption=За к-во,mandatory');
    		$form->setDefault('quantity', $origin->fetchField('quantity'));
    		$form->input();
    		
    		if($form->isSubmitted()){
    			
    			// Добавяне на материалите от рецептата и редирект
    			$details = self::getDefaultDetailsFromBom($bomId, $form->rec->quantity);
    			$this->saveDetails($details, $masterId);
    			
    			followRetUrl();
    		}
    		
    		$form->toolbar->addSbBtn('Попълване', 'save', 'ef_icon = img/16/move.png');
    		$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png');
        
        	return $this->renderWrapping($form->renderHtml());
    		
        	// Ако е към продажба директно се наливат артикулите от нея
    	} elseif($origin->isInstanceOf('sales_Sales')) {
    		$details = $this->getDefaultDetailsFromSale($origin->that);
    		$this->saveDetails($details, $masterId);
    	}
    	
    	// Редирект
    	followRetUrl();
    }
    
    
    
    /**
     * Записва дефолтните детайли според източника
     * 
     * @param int $reserveId - ид/запис на документа
     */
    public static function saveDefaultDetails($reserveId)
    {
    	$details = array();
    	$masterRec = store_ReserveStocks::fetchRec($reserveId);
    	
    	$me = cls::get(get_called_class());
    	$origin = doc_Containers::getDocument($masterRec->originId);
    	$bomId = $me->getBomFromOrigin($origin);
    	if(!empty($bomId)){
    		$quantity = $origin->fetchField('quantity');
    		$details = $me->getDefaultDetailsFromBom($bomId, $quantity);
    	} elseif($origin->isInstanceOf('sales_Sales')){
    		$details = $me->getDefaultDetailsFromSale($origin->that);
    	}
    	
    	if(count($details)){
    		$me->saveDetails($details, $reserveId);
    	}
    }
    
    
    /**
     * Записва детайлите към документа за резервиране на складови наличности
     * 
     * @param array $details - детайли за добавяне
     * @param int $reserveId - ид на мастъра
     */
    private function saveDetails($details, $reserveId)
    {
    	store_ReserveStockDetails::delete("#reserveId = {$reserveId}");
    	
    	if(count($details)){
    		array_walk($details, function(&$obj) use ($reserveId){ $obj->reserveId = $reserveId;});
    		$this->saveArray($details);
    		cls::get('store_ReserveStocks')->invoke('AfterUpdateDetail', array($reserveId, $this));
    	}
    }
    
    
    /**
     * Взима дефолтните детайли от продажба
     * 
     * @param int $saleId - ид на продажба
     * @return array $res - артикулите от продажбата
     */
    private function getDefaultDetailsFromSale($saleId)
    {
    	$res = array();
    	$dealInfo = cls::get('sales_Sales')->getAggregateDealInfo($saleId);
    	$products = $dealInfo->dealProducts;
    	if(is_array($products)){
    		foreach ($products as $pRec){
    			
    			// Само складируемите артикули
    			$canStore = cat_Products::fetchField($pRec->productId, 'canStore');
    			if($canStore != 'yes') continue;
    			
    			$res[] = (object)array('productId'      => $pRec->productId,
    					               'packagingId'    => $pRec->packagingId,
    					               'quantity'       => $pRec->quantity,
    					               'quantityInPack' => $pRec->quantityInPack);
    		}
    	}
    
    	return $res;
    }
    
    
    /**
     * Дефолтни артикули от рецепта
     * 
     * @param int $bomId
     * @param double $quantity
     * @return array $res - артикулите от рецептата
     */
    private function getDefaultDetailsFromBom($bomId, $quantity)
    {
    	$res = array();
    	
    	$bomInfo = cat_Boms::getResourceInfo($bomId, $quantity, dt::now());
    	if(count($bomInfo['resources'])){
    		foreach ($bomInfo['resources'] as $pRec){
    			$canStore = cat_Products::fetchField($pRec->productId, 'canStore');
    			if($canStore != 'yes' || $pRec->type != 'input') continue;
    				
    			$res[] = (object)array('productId'      => $pRec->productId,
    					               'packagingId'    => $pRec->packagingId,
    					               'quantity'       => $pRec->propQuantity,
    					               'quantityInPack' => $pRec->quantityInPack);
    		}
    	}
    	
    	return $res;
    }
    
    
    /**
     * Преди подготовка на данните за табличния изглед правим филтриране
     * на записите, които са (или не са) оттеглени и сортираме от нови към стари
     */
    protected static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
    	$data->query->orderBy('#id', 'ASC');
    }
}