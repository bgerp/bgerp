<?php



/**
 * Клас 'store_ReceiptDetails'
 *
 * Детайли на мениджър на детайлите на складовите разписки (@see store_ReceiptDetails)
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_ReceiptDetails extends deals_DeliveryDocumentDetail
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Детайли на складовите разписки';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Продукт';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'receiptId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, store_Wrapper, plg_SaveAndNew, plg_RowNumbering,store_plg_RequestDetail,Policy=purchase_PurchaseLastPricePolicy, 
                        plg_AlignDecimals2, plg_Sorting, doc_plg_HidePrices, ReverseLastPricePolicy=sales_SalesLastPricePolicy, 
                        Policy=purchase_PurchaseLastPricePolicy, plg_PrevAndNext,deals_plg_ImportDealDetailProduct,cat_plg_ShowCodes,store_plg_TransportDataDetail';
    
    
    /**
     * Активен таб на менюто
     */
    public $menuPage = 'Логистика:Складове';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, store, purchase, sales';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, store, purchase, sales';
    
    
    /**
     * Кой има право да импортира?
     */
    public $canImport = 'ceo, store, purchase, sales';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, store, purchase, sales';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId, packQuantity, packPrice, discount, amount, weight=Тегло, volume=Обем';
    
        
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
	/**
     * Полета свързани с цени
     */
    public $priceFields = 'price, amount, discount, packPrice';
    
    
    /**
     * Полета за скриване/показване от шаблоните
     */
    public $toggleFields = 'packagingId=Опаковка,packQuantity=Количество,packPrice=Цена,discount=Отстъпка,amount=Сума,weight=Обем,volume=Тегло,info=Инфо';
    
    
    /**
     * Какво движение на партида поражда документа в склада
     *
     * @param out|in|stay - тип движение (излиза, влиза, стои)
     */
    public $batchMovementDocument = 'in';
    
    
    /**
     * Да се показва ли кода като в отделна колона
     */
    public $showCodeColumn = TRUE;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('receiptId', 'key(mvc=store_Receipts)', 'column=none,notNull,silent,hidden,mandatory');
        parent::setDocumentFields($this);
        $this->setFieldTypeParams('packQuantity', "Min=0");
    }

    
    /**
     * Достъпните продукти
     */
    protected function getProducts($masterRec)
    {
    	$property = ($masterRec->isReverse == 'yes') ? 'canSell' : 'canBuy';
    	$property .= ',canStore';
    	
    	// Намираме всички продаваеми продукти, и оттях оставяме само складируемите за избор
    	$products = cat_Products::getProducts($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->date, $property);
    	
    	return $products;
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form &$form)
    {
    	parent::inputDocForm($mvc, $form);
    }
    
    
    /**
     * След обработка на записите от базата данни
     */
    public static function on_AfterPrepareListRows(core_Mvc $mvc, $data)
    {
    	$date = ($data->masterData->rec->state == 'draft') ? NULL : $data->masterData->rec->modifiedOn;
    	if(count($data->rows)) {
    		foreach ($data->rows as $i => &$row) {
    			$rec = &$data->recs[$i];
    
    			$row->productId = cat_Products::getAutoProductDesc($rec->productId, $date, 'short', 'public', $data->masterData->rec->tplLang, 1, FALSE);
    			deals_Helper::addNotesToProductRow($row->productId, $rec->notes);
    		}
    	}
    }
    
    
    /**
     * Метод по пдоразбиране на getRowInfo за извличане на информацията от реда
     */
    public static function on_AfterGetRowInfo($mvc, &$res, $rec)
    {
    	$rec = $mvc->fetchRec($rec);
    	$masterRec = store_Receipts::fetch($rec->receiptId, 'isReverse,storeId');
    	if($masterRec->isReverse == 'yes'){
    		$res->operation['in'] = $masterRec->storeId;
    		unset($res->operation['out']);
    	}
    }
    
    
    /**
     * Импортиране на артикул генериран от ред на csv файл
     *
     * @param int $masterId - ид на мастъра на детайла
     * @param array $row - Обект представляващ артикула за импортиране
     * 					->code - код/баркод на артикула
     * 					->quantity - К-во на опаковката или в основна мярка
     * 					->price - цената във валутата на мастъра, ако няма се изчислява директно
     * 					->pack - Опаковката
     * @return  mixed - резултата от експорта
     */
    function import($masterId, $row)
    {
    	$pRec = cat_Products::getByCode($row->code);
    	$rec = new stdClass();
    	$rec->receiptId = $masterId;
    	$rec->productId = $pRec->productId;
    	$rec->packagingId = (isset($pRec->packagingId)) ? $pRec->packagingId : $row->pack;
    	$rec->isEdited = TRUE;
    	
    	$pack = cat_products_Packagings::getPack($rec->productId, $rec->packagingId);
    	$rec->quantityInPack = ($pack) ? $pack->quantity : 1;
    	$rec->quantity = $row->quantity * $rec->quantityInPack;
    	
    	// Ако има цена я обръщаме в основна валута без ддс, спрямо мастъра на детайла
    	$masterRec = store_Receipts::fetch($masterId);
    	if($row->price){
    		$rec->price = deals_Helper::getPurePrice($row->price, cat_Products::getVat($rec->productId), $masterRec->currencyRate, $masterRec->chargeVat);
    		$rec->price /= $rec->quantityInPack;
    	} else {
    		$policyInfo = cls::get('purchase_PurchaseLastPricePolicy')->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->packagingId, $rec->quantity, $masterRec->valior, $masterRec->currencyRate, $masterRec->chargeVat);
    		$rec->price = $policyInfo->price;
    	}
    	
    	if(!empty($row->batch)){
    		$rec->batch = $row->batch;
    	}
    	
    	return $this->save($rec);
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'import' && isset($rec)){
    		$isReverse = $mvc->Master->fetchField($rec->receiptId, 'isReverse');
    		if($isReverse == 'yes'){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
}