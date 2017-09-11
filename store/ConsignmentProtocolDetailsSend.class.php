<?php


/**
 * Клас 'store_ConsignmentProtocolDetailsSend'
 *
 * Детайли на мениджър на детайлите на протоколите за отговорни пазене-предадени
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_ConsignmentProtocolDetailsSend extends store_InternalDocumentDetail
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Детайли на протоколите за отговорни пазене-предадени';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'артикул за предаване';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'protocolId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, store_Wrapper, plg_RowNumbering, plg_SaveAndNew, 
                        plg_AlignDecimals2, LastPricePolicy=sales_SalesLastPricePolicy, plg_PrevAndNext,store_plg_TransportDataDetail';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, store';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, store';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, store';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, store';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId=Дадено, packagingId, packQuantity, weight=Тегло,volume=Обем,packPrice, amount';

    
	/**
     * Полета свързани с цени
     */
    public $priceFields = 'price, amount, discount, packPrice';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('protocolId', 'key(mvc=store_ConsignmentProtocols)', 'column=none,notNull,silent,hidden,mandatory');
    	parent::setFields($this);
    	$this->setDbUnique('protocolId,productId,packagingId');
    }
    
    
    /**
     * Достъпните продукти
     */
    protected function getProducts($masterRec)
    {
    	// Намираме всички продаваеми продукти, и оттях оставяме само складируемите за избор
    	$products = cat_Products::getProducts($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->date, 'canSell,canStore');
    	 
    	return $products;
    }
    
    
    /**
     * След инпутване на формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form &$form)
    {
    	$rec = &$form->rec;
    	
    	if(isset($rec->productId)){
    		$masterStore = $mvc->Master->fetch($rec->{$mvc->masterKey})->storeId;
    		$storeInfo = deals_Helper::checkProductQuantityInStore($rec->productId, $rec->packagingId, $rec->packQuantity, $masterStore);
    		$form->info = $storeInfo->formInfo;
    	}
    }

    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
    	$rows = &$data->rows;
    	if(!count($data->recs)) return;
    	
    	$storeId = $data->masterData->rec->storeId;
    	foreach ($data->rows as $id => $row){
    		$rec = $data->recs[$id];
    		
    		$warning = deals_Helper::getQuantityHint($rec->productId, $storeId, $rec->quantity);
    		if(strlen($warning) && $data->masterData->rec->state == 'draft'){
    			$row->packQuantity = ht::createHint($row->packQuantity, $warning, 'warning', FALSE);
    		}
    	}
    }
}