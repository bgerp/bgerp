<?php
/**
 * Клас 'sales_SalesDetails'
 *
 * Детайли на мениджър на документи за продажба на продукти (@see sales_Sales)
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
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
    public $loadList = 'plg_RowTools, plg_Created, sales_Wrapper, plg_RowNumbering, plg_SaveAndNew,
                        plg_AlignDecimals2, doc_plg_HidePrices, LastPricePolicy=sales_SalesLastPricePolicy';
    
    
    /**
     * Активен таб на менюто
     * 
     * @var string
     */
    public $menuPage = 'Търговия:Продажби';
    
    
    /**
     * Кой има право да чете?
     * 
     * @var string|array
     */
    public $canRead = 'ceo, sales';
    
    
    /**
     * Кой има право да променя?
     * 
     * @var string|array
     */
    public $canEdit = 'ceo, sales';
    
    
    /**
     * Кой има право да добавя?
     * 
     * @var string|array
     */
    public $canAdd = 'ceo, sales';
    
    
    /**
     * Кой може да го види?
     * 
     * @var string|array
     */
    public $canView = 'ceo, sales';
    
    
    /**
     * Кой може да го изтрие?
     * 
     * @var string|array
     */
    public $canDelete = 'ceo, sales';
    
    
    /**
     * Брой записи на страница
     * 
     * @var integer
     */
    public $listItemsPerPage;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId, uomId, packQuantity, packPrice, discount, amount';
    
        
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';


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
    }
    
    
    /**
     * Проверка и валидиране на формата
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
    	$rec = &$form->rec;
    	$masterStore = $mvc->Master->fetch($rec->{$mvc->masterKey})->shipmentStoreId;
    	
    	if(isset($rec->productId)){
    		if(isset($masterStore)){
    			$storeInfo = deals_Helper::getProductQuantityInStoreInfo($rec->productId, $rec->classId, $masterStore);
    			$form->info = $storeInfo->formInfo;
    		}
    	}
    	
    	if ($form->isSubmitted()){
    		$pInfo = cls::get($rec->classId)->getProductInfo($rec->productId, $rec->packagingId);
    		$quantityInPack = ($pInfo->packagingRec) ? $pInfo->packagingRec->quantity : 1;
    		
    		if(isset($storeInfo)){
    			if($rec->packQuantity > ($storeInfo->quantity / $quantityInPack)){
    				$form->setWarning('packQuantity', 'Въведеното количество е по-голямо от наличното в склада');
    			}
    		}
    		
    		if(isset($rec->packPrice)){
    			if($rec->packPrice < cls::get($rec->classId)->getSelfValue($rec->productId) * $quantityInPack){
    				$form->setWarning('packPrice', 'Цената е под себестойност');
    			}
    		}
    	}
    	
    	parent::inputDocForm($mvc, $form);
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
    	$rows = &$data->rows;
    	 
    	if(!count($data->recs)) return;
    	 
    	if($storeId = $data->masterData->rec->shipmentStoreId){
    		foreach ($rows as $id => $row){
    			$rec = $data->recs[$id];
    			$quantityInStore = store_Products::fetchField("#productId = {$rec->productId} AND #classId = {$rec->classId} AND #storeId = {$storeId}", 'quantity');
    			$diff = ($data->masterData->rec->state == 'active') ? $quantityInStore : $quantityInStore - $rec->quantity;
    			
    			if($diff < 0){
    				$row->packQuantity = "<span class='row-negative' title = '" . tr('Количеството в скалда е отрицателно') . "'>{$row->packQuantity}</span>";
    			}
    			
    			if($rec->price < cls::get($rec->classId)->getSelfValue($rec->productId)){
    				$row->packPrice = "<span class='row-negative' title = '" . tr('Цената е под себестойност') . "'>{$row->packPrice}</span>";
    			}
    		}
    	}
    }
}
