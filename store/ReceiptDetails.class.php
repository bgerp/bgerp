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
class store_ReceiptDetails extends core_Detail
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
    public $loadList = 'plg_RowTools, plg_Created, store_Wrapper, plg_RowNumbering, 
                        plg_AlignDecimals, doc_plg_HidePrices';
    
    
    /**
     * Активен таб на менюто
     */
    public $menuPage = 'Логистика:Складове';
    
    
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
     * Кой може да го види?
     */
    public $canView = 'ceo, store';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, store';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId, uomId, packQuantity, price, discount, amount';
    
        
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
	/**
     * Полета свързани с цени
     */
    public $priceFields = 'price, amount, discount, packPrice';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('receiptId', 'key(mvc=store_Receipts)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('classId', 'class(select=title)', 'caption=Мениджър,silent,input=hidden');
        $this->FLD('productId', 'int(cellAttr=left)', 'caption=Продукт,notNull,mandatory');
        $this->FLD('uomId', 'key(mvc=cat_UoM, select=name)', 'caption=Мярка,input=none');
        $this->FLD('packagingId', 'key(mvc=cat_Packagings, select=name, allowEmpty)', 'caption=Мярка/Опак.,input=none');
        $this->FLD('quantity', 'double', 'caption=К-во,input=none');
        $this->FLD('quantityInPack', 'double(decimals=2)', 'input=none,column=none');
        $this->FLD('price', 'double(decimals=2)', 'caption=Цена,input=none');
        $this->FNC('amount', 'double(decimals=2)', 'caption=Сума,input=none');
        $this->FNC('packQuantity', 'double(decimals=2)', 'caption=К-во,input=input,mandatory');
        $this->FNC('packPrice', 'double(decimals=2)', 'caption=Цена,input=none');
        $this->FLD('discount', 'percent', 'caption=Отстъпка,input=none');
    }


    /**
     * Изчисляване на цена за опаковка на реда
     */
    public function on_CalcPackPrice(core_Mvc $mvc, $rec)
    {
        if (empty($rec->price) || empty($rec->quantity) || empty($rec->quantityInPack)) {
            return;
        }
    
        $rec->packPrice = $rec->price * $rec->quantityInPack;
    }
    
    
    /**
     * Изчисляване на количеството на реда в брой опаковки
     */
    public function on_CalcPackQuantity(core_Mvc $mvc, $rec)
    {
        if (empty($rec->price) || empty($rec->quantity) || empty($rec->quantityInPack)) {
            return;
        }
    
        $rec->packQuantity = $rec->quantity / $rec->quantityInPack;
    }
    
    
    /**
     * Изчисляване на сумата на реда
     */
    public function on_CalcAmount(core_Mvc $mvc, $rec)
    {
        if (empty($rec->price) || empty($rec->quantity)) {
            return;
        }
    
        $rec->amount = $rec->price * $rec->quantity;
    }


    /**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave($mvc, &$id, $rec, $fieldsList = NULL)
    {
        // Подсигуряваме наличието на ключ към мастър записа
        if (empty($rec->{$mvc->masterKey})) {
            $rec->{$mvc->masterKey} = $mvc->fetchField($rec->id, $mvc->masterKey);
        }
        
        $mvc->updateMasterSummary($rec->{$mvc->masterKey});
    }

    
    /**
     * Обновява агрегатни стойности в мастър записа
     * 
     * @param int $masterId ключ на мастър модела
     * @param boolean $force FALSE - само запомни ключа на мастъра, но не прави промени по БД
     * 
     *                       TRUE  - направи промените в БД сега! Тези промени ще засегнат:
     *                       
     *                               * Мастър записа с ключ $masterId, ако $masterId не е 
     *                                 празно;
     *                               * Всички мастър записи с ключове, добавени по-рано чрез 
     *                                 извикване на този метод с параметър $force = FALSE.
     *                        
     */
    public function updateMasterSummary($masterId = NULL, $force = FALSE)
    {
        static $updatedMasterIds = array();
         
        if (!$force) {
            if (!empty($masterId)) {
                $updatedMasterIds[$masterId] = $masterId;
            }
            
            return;
        }
        
        $masterIds = empty($masterId) ? $updatedMasterIds : array($masterId);
        
        foreach ($masterIds as $masterId) {
            $amountDelivered = 0;
            
            $query = $this->getQuery();
            $query->where("#{$this->masterKey} = '{$masterId}'");
            
            while ($rec = $query->fetch()) {
                $amountDelivered += $rec->amount;
            }
            
            $this->Master->save(
                (object)array('id' => $masterId, 'amountDelivered' => $amountDelivered)
            );
        }
    }
    
    
    /**
     * Изпълнява се след приключване на работата на скрипта
     */
    public function on_Shutdown($mvc)
    {
        $mvc->updateMasterSummary(NULL /* ALL */, TRUE /* force update now */);
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if(($action == 'edit' || $action == 'delete') && isset($rec)){
        	if($mvc->Master->fetchField($rec->receiptId, 'state') != 'draft'){
        		$requiredRoles = 'no_one';
        	}
        }
    	
    	if($action == 'add6' && isset($rec->receiptId)){
      		$masterRec = $mvc->Master->fetch($rec->receiptId);
		    $origin = $mvc->Master->getOrigin($masterRec);
		    $dealAspect = $origin->getAggregateDealInfo()->agreed;
		    $invProducts = $mvc->Master->getDealInfo($rec->receiptId)->shipped;
    		if(!bgerp_iface_DealAspect::buildProductOptions($dealAspect, $invProducts)){
    			$requiredRoles = 'no_one';
    		}
    	}
    }


    /**
     * След обработка на записите от базата данни
     */
    public function on_AfterPrepareListRows(core_Mvc $mvc, $data)
    {
        $rows = $data->rows;
    	$showVat = $data->masterData->rec->chargeVat == 'yes' || $data->masterData->rec->chargeVat == 'no';
    	
        // Скриваме полето "мярка"
        $data->listFields = array_diff_key($data->listFields, arr::make('uomId', TRUE));
        
        // Определяме кой вижда ценовата информация
        if (!$mvc->Master->haveRightFor('viewprices', $data->masterData->rec)) {
            $data->listFields = array_diff_key($data->listFields, arr::make('price, discount, amount', TRUE));
        }
    
        // Флаг дали има отстъпка
        $haveDiscount = FALSE;
    
        if(count($data->rows)) {
            foreach ($data->rows as $i => &$row) {
                $rec = &$data->recs[$i];
                $ProductManager = cls::get($rec->classId);
                
    			if($showVat){
    				$price = $rec->price * (1 + $ProductManager->getVat($rec->productId, $data->masterData->rec->valior));
    				@$price /= $data->masterData->rec->currencyRate;
    				
    				$row->price = $mvc->fields['amount']->type->toVerbal($price * $rec->quantityInPack);
    				$row->amount = $mvc->fields['amount']->type->toVerbal($price * $rec->quantity);
    			}
                
    			$row->productId = $ProductManager->getTitleById($rec->productId);
                $haveDiscount = $haveDiscount || !empty($rec->discount);
    			
                if (empty($rec->packagingId)) {
                    if ($rec->uomId) {
                        $row->packagingId = $row->uomId;
                    } else {
                        $row->packagingId = '???';
                    }
                } else {
                    $shortUomName = cat_UoM::getShortName($rec->uomId);
                    $row->quantityInPack = $mvc->fields['quantityInPack']->type->toVerbal($rec->quantityInPack);
                    $row->packagingId .= ' <small class="quiet">' . $row->quantityInPack . '  ' . $shortUomName . '</small>';
                }
            }
        }
    
        if(!$haveDiscount) {
            unset($data->listFields['discount']);
        }
    }
        
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = &$data->form;
    	$origin = store_Receipts::getOrigin($data->masterRec, 'bgerp_DealIntf');
        
        $masterRec = $mvc->Master->fetch($form->rec->receiptId);
      	expect($origin = $mvc->Master->getOrigin($masterRec));
      	$dealAspect = $origin->getAggregateDealInfo()->agreed;
      	$invProducts = $mvc->Master->getDealInfo($form->rec->receiptId)->shipped;
        
      	$form->setOptions('productId', bgerp_iface_DealAspect::buildProductOptions($dealAspect, $invProducts, $form->rec->productId, $form->rec->classId, $form->rec->packagingId));
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    { 
        if ($form->isSubmitted() && !$form->gotErrors()) {
            
            // Извличане на информация за продукта - количество в опаковка, единична цена
            $rec = $form->rec;
            
            // Извличаме ид на политиката, кодирано в ид-то на продукта 
            list($rec->classId, $rec->productId, $rec->packagingId) = explode('|', $rec->productId);
			$rec->packagingId = ($rec->packagingId) ? $rec->packagingId : NULL;
            
            /* @var $origin bgerp_DealAggregatorIntf */
            $origin = store_Receipts::getOrigin($rec->receiptId, 'bgerp_DealIntf');
            
            /* @var $dealInfo bgerp_iface_DealResponse */
            $dealInfo = $origin->getAggregateDealInfo();
            
            $aggreedProduct = $dealInfo->agreed->findProduct($rec->productId, $rec->classId, $rec->packagingId);
            
            if (!$aggreedProduct) {
                $form->setError('productId', 'Продуктът не е наличен за експедиция');
                return;
            }
            
            $rec->price = $aggreedProduct->price;
            $rec->uomId = $aggreedProduct->uomId;
            
            if (empty($rec->packagingId)) {
                $rec->quantityInPack = 1;
            } else {
                // Извлича $productInfo, за да определи количеството единици продукт (в осн. мярка) в една опаковка
                $productInfo = cls::get($rec->classId)->getProductInfo($rec->productId, $rec->packagingId);
                $rec->quantityInPack = $productInfo->packagingRec->quantity;
            }
            
            $rec->quantity = $rec->packQuantity * $rec->quantityInPack;
           
            if (empty($rec->discount)) {
                $rec->discount = $aggreedProduct->discount;
            }
        }
    }
    
    
	/**
     * След подготовката на списъчните полета
     */
    static function on_AfterPrepareListFields($mvc, $data)
    {
        $showPrices = Request::get('showPrices', 'int');
    	if(Mode::is('printing') && empty($showPrices)) {
            unset($data->listFields['price'], 
            	  $data->listFields['amount'], 
            	  $data->listFields['discount']);
        }
    }
}