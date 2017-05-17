<?php



/**
 * Клас 'sales_SalesDetails'
 *
 * Детайли на мениджър на документи за продажба на продукти (@see sales_Sales)
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_SalesDetails extends deals_DealDetail
{
    
    
    /**
     * Заглавие
     * 
     * @var string
     */
    public $title = 'Детайли на продажби';


    /**
     * Заглавие в единствено число
     *
     * @var string
     */
    public $singleTitle = 'Артикул';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'saleId';
    
    
    /**
     * Плъгини за зареждане
     * 
     * var string|array
     */
    public $loadList = 'plg_RowTools2, plg_Created, sales_Wrapper, plg_RowNumbering, plg_SaveAndNew, plg_PrevAndNext,
                        plg_AlignDecimals2, plg_Sorting, deals_plg_ImportDealDetailProduct, doc_plg_HidePrices, LastPricePolicy=sales_SalesLastPricePolicy,cat_plg_CreateProductFromDocument,doc_plg_HideMeasureAndQuantityColumns';
    
    
    /**
     * Активен таб на менюто
     * 
     * @var string
     */
    public $menuPage = 'Търговия:Продажби';
    
    
    /**
     * Кой има право да променя?
     * 
     * @var string|array
     */
    public $canEdit = 'sales,ceo,partner';
    
    
    /**
     * Кой има право да променя?
     *
     * @var string|array
     */
    public $canImportlisted = 'user';
    
    
    /**
     * Кой има право да добавя?
     * 
     * @var string|array
     */
    public $canAdd = 'user';
    
    
    /**
     * Кой може да го изтрие?
     * 
     * @var string|array
     */
    public $canDelete = 'sales,ceo,partner';
    
    
    /**
     * Кой може да го импортира артикули?
     *
     * @var string|array
     */
    public $canImport = 'user';
    
    
    /**
     * Кой може да го импортира артикули?
     *
     * @var string|array
     */
    public $canCreateproduct = 'user';
    
    
    
    /**
     * Кои полета да се извличат при изтриване
     */
    public $fetchFieldsBeforeDelete = 'saleId';
    
    
    /**
     * Брой записи на страница
     * 
     * @var integer
     */
    public $listItemsPerPage;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId, packQuantity, packPrice, discount, amount, quantityInPack';
    

    /**
     * Полета свързани с цени
     */
    public $priceFields = 'price,amount,discount,packPrice';
    
    
    /**
     * Какви мета данни да изискват продуктите, които да се показват
     */
    public $metaProducts = 'canSell';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('saleId', 'key(mvc=sales_Sales)', 'column=none,notNull,silent,hidden,mandatory');
        
        parent::getDealDetailFields($this);
		$this->setField('packPrice', 'silent');
    }
    
    
    /**
     * Проверка и валидиране на формата
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
    	$rec = &$form->rec;
    	$masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey});
    	if(isset($rec->productId)){
    		$pInfo = cat_Products::getProductInfo($rec->productId);
    		$masterStore = $masterRec->shipmentStoreId;
    		
    		if(isset($masterStore) && isset($pInfo->meta['canStore'])){
    			$storeInfo = deals_Helper::checkProductQuantityInStore($rec->productId, $rec->packagingId, $rec->packQuantity, $masterStore);
    			$form->info = $storeInfo->formInfo;
    		}
    	}
    	
    	parent::inputDocForm($mvc, $form);
    	
    	// След събмит
    	if($form->isSubmitted()){
    		
    		// Подготовка на сумата на транспорта, ако има
    		tcost_Calcs::prepareFee($rec, $form, $masterRec);
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
    	$rows = &$data->rows;
    	
    	if(!count($data->recs)) return;
    	$masterRec = $data->masterData->rec;
    	
    	foreach ($rows as $id => $row){
    		$rec = $data->recs[$id];
    		$pInfo = cat_Products::getProductInfo($rec->productId);
    			
    		if($storeId = $masterRec->shipmentStoreId){
    			if(isset($pInfo->meta['canStore']) && $masterRec->state == 'draft'){
    				$warning = deals_Helper::getQuantityHint($rec->productId, $storeId, $rec->quantity);
    				if(strlen($warning)){
    					$row->packQuantity = ht::createHint($row->packQuantity, $warning, 'warning', FALSE);
    				}
    			}
    		}
    		
    		if($rec->price < cat_Products::getSelfValue($rec->productId, NULL, $rec->quantity)){
    			if(!core_Users::haveRole('partner')){
    				$row->packPrice = ht::createHint($row->packPrice, 'Цената е под себестойността', 'warning', FALSE);
    			}
    		}
    		
    		// Ако е имало проблем при изчисляването на скрития транспорт, показва се хинт
    		$fee = tcost_Calcs::get($mvc->Master, $rec->saleId, $rec->id)->fee;
    		$vat = cat_Products::getVat($rec->productId, $masterRec->valior);
    		$row->amount = tcost_Calcs::getAmountHint($row->amount, $fee, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
    	}
    }
    
    
    /**
     * Приготвя информация за нестандартните артикули и техните задания
     * 
     * @param stdClass $rec
     * @param stdClass $masterRec
     * @return void|stdClass
     */
    public static function prepareJobInfo($rec, $masterRec)
    {
    	$res = array();
    	$jQuery = planning_Jobs::getQuery();
    	$jQuery->where("#productId = {$rec->productId}");
    	$jQuery->where("#saleId IS NULL OR #saleId = {$masterRec->id}");
    	$jQuery->XPR('order', 'int', "(CASE #state WHEN 'draft' THEN 1 WHEN 'active' THEN 2 WHEN 'stopped' THEN 3 WHEN 'wakeup' THEN 4 WHEN 'closed' THEN 5 ELSE 3 END)");
		$jQuery->orderBy('order', 'ASC');
    	
    	while($jRec = $jQuery->fetch()){
    		$row = (object)array('quantity' => 0, 'quantityFromTasks' => 0, 'quantityProduced' => 0);
    		$row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
    		$row->ROW_ATTR['class'] = "state-{$jRec->state}";
    		
    		$Double = cls::get('type_Double', (object)array('params' => array('smartRound' => TRUE)));
    		$row->quantity = $Double->toVerbal($jRec->quantity);
    		$row->quantityFromTasks = $Double->toVerbal(planning_TaskActions::getQuantityForJob($jRec->id, 'product'));
    		$row->quantityProduced = $Double->toVerbal($jRec->quantityProduced);
    		$row->dueDate = cls::get('type_Date')->toVerbal($jRec->dueDate);
    		$row->jobId = planning_Jobs::getLink($jRec->id, 0);
    		
    		$res[] = $row;
    	}
    	
    	return $res;
    }
    
    
    /**
     * Изпълнява се преди клониране
     */
    protected static function on_BeforeSaveClonedDetail($mvc, &$rec, $oldRec)
    {
    	// Преди клониране клонира се и сумата на цената на транспорта
    	$cRec = tcost_Calcs::get($mvc->Master, $oldRec->saleId, $oldRec->id);
    	if(isset($cRec)){
    		$rec->fee = $cRec->fee;
    		$rec->deliveryTimeFromFee = $cRec->deliveryTime;
    		$rec->syncFee = TRUE;
    	}
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	// Синхронизиране на сумата на транспорта
    	if($rec->syncFee === TRUE){
    		tcost_Calcs::sync($mvc->Master, $rec->{$mvc->masterKey}, $rec->id, $rec->fee, $rec->deliveryTimeFromFee);
    	}
    }
    
    
    /**
     * След изтриване на запис
     */
    public static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {
    	// Инвалидиране на изчисления транспорт, ако има
    	foreach ($query->getDeletedRecs() as $id => $rec) {
    		tcost_Calcs::sync($mvc->Master, $rec->saleId, $rec->id, NULL);
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'add') && isset($rec)){
    		if($requiredRoles != 'no_one'){
    			$roles = sales_Setup::get('ADD_BY_PRODUCT_BTN');
    			if(!haveRole($roles, $userId)){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    	
    	if($action == 'importlisted'){
    		$roles = sales_Setup::get('ADD_BY_LIST_BTN');
    		if(!haveRole($roles, $userId)){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
}
