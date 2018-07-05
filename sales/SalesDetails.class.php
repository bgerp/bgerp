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
                        plg_AlignDecimals2, plg_Sorting, deals_plg_ImportDealDetailProduct, doc_plg_HidePrices, LastPricePolicy=sales_SalesLastPricePolicy,cat_plg_CreateProductFromDocument,doc_plg_HideMeasureAndQuantityColumns,cat_plg_ShowCodes';
    
    
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
    public $listFields = 'productId, packagingId, packQuantity, packPrice, discount, amount';
    

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
    		sales_TransportValues::prepareFee($rec, $form, $masterRec);
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
    			if(isset($pInfo->meta['canStore']) && in_array($masterRec->state, array('draft', 'pending'))){
    				$warning = deals_Helper::getQuantityHint($rec->productId, $storeId, $rec->quantity);
    				if(strlen($warning)){
    					$row->packQuantity = ht::createHint($row->packQuantity, $warning, 'warning', FALSE, NULL, 'class=doc-negative-quantiy');
    				}
    			}
    		}
    		
    		if($rec->price < cat_Products::getSelfValue($rec->productId, NULL, $rec->quantity)){
    			if(!core_Users::haveRole('partner') && isset($row->packPrice)){
    				$row->packPrice = ht::createHint($row->packPrice, 'Цената е под себестойността', 'warning', FALSE);
    			}
    		}
    		
    		// Ако е имало проблем при изчисляването на скрития транспорт, показва се хинт
    		$fee = sales_TransportValues::get($mvc->Master, $rec->saleId, $rec->id)->fee;
    		$vat = cat_Products::getVat($rec->productId, $masterRec->valior);
    		$row->amount = sales_TransportValues::getAmountHint($row->amount, $fee, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
    	}
    }
    
    
    /**
     * Изпълнява се преди клониране
     */
    protected static function on_BeforeSaveClonedDetail($mvc, &$rec, $oldRec)
    {
    	// Преди клониране клонира се и сумата на цената на транспорта
    	$cRec = sales_TransportValues::get($mvc->Master, $oldRec->saleId, $oldRec->id);
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
    		sales_TransportValues::sync($mvc->Master, $rec->{$mvc->masterKey}, $rec->id, $rec->fee, $rec->deliveryTimeFromFee);
    	}
    }
    
    
    /**
     * След изтриване на запис
     */
    public static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {
    	// Инвалидиране на изчисления транспорт, ако има
    	foreach ($query->getDeletedRecs() as $id => $rec) {
    		sales_TransportValues::sync($mvc->Master, $rec->saleId, $rec->id, NULL);
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
