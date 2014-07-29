<?php
/**
 * Клас 'store_ShipmentOrderDetails'
 *
 * Детайли на мениджър на експедиционни нареждания (@see store_ShipmentOrders)
 *
 * @category  bgerp
 * @package   store
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_ShipmentOrderDetails extends core_Detail
{
    /**
     * Заглавие
     * 
     * @var string
     */
    public $title = 'Детайли на ЕН';


    /**
     * Заглавие в единствено число
     *
     * @var string
     */
    public $singleTitle = 'Продукт';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'shipmentId';
    
    
    /**
     * Плъгини за зареждане
     * 
     * var string|array
     */
    public $loadList = 'plg_RowTools, plg_Created, store_Wrapper, plg_RowNumbering, plg_SaveAndNew, 
                        plg_AlignDecimals2 , doc_plg_TplManagerDetail, store_plg_DocumentDetail';
    
    
    /**
     * Активен таб на менюто
     * 
     * @var string
     */
    public $menuPage = 'Логистика:Складове';
    
    
    /**
     * Кой има право да чете?
     * 
     * @var string|array
     */
    public $canRead = 'ceo, store';
    
    
    /**
     * Кой има право да променя?
     * 
     * @var string|array
     */
    public $canEdit = 'ceo, store';
    
    
    /**
     * Кой има право да добавя?
     * 
     * @var string|array
     */
    public $canAdd = 'ceo, store';
    
    
    /**
     * Кой може да го види?
     * 
     * @var string|array
     */
    public $canView = 'ceo, store';
    
    
    /**
     * Кой може да го изтрие?
     * 
     * @var string|array
     */
    public $canDelete = 'ceo, store';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'info, productId, packagingId, uomId, packQuantity, packPrice, discount, amount, weight, volume';
    
        
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
	/**
     * Полета свързани с цени
     */
    public $priceFields = 'price,amount,discount,packPrice';
    
    
    /**
     * Полета за скриване/показване от шаблоните
     */
    public $toggleFields = 'packagingId=Опаковка,packQuantity=Количество,packPrice=Цена,discount=Отстъпка,amount=Сума,weight=Обем,volume=Тегло,info=Инфо';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('shipmentId', 'key(mvc=store_ShipmentOrders)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('classId', 'class(select=title)', 'caption=Мениджър,silent,input=hidden');
        $this->FLD('productId', 'int(cellAttr=left)', 'caption=Продукт,notNull,mandatory', 'tdClass=large-field');
        $this->FLD('uomId', 'key(mvc=cat_UoM, select=shortName)', 'caption=Мярка,input=none');
        $this->FLD('packagingId', 'key(mvc=cat_Packagings, select=name, allowEmpty)', 'caption=Мярка/Опак.', 'tdClass=small-field');
        $this->FLD('weight', 'cat_type_Weight', 'input=hidden,caption=Тегло');
        $this->FLD('volume', 'cat_type_Volume', 'input=hidden,caption=Обем');
        
        // Количество в основна мярка
        $this->FLD('quantity', 'double', 'caption=К-во,input=none');
        
        // Количество (в осн. мярка) в опаковката, зададена от 'packagingId'; Ако 'packagingId'
        // няма стойност, приема се за единица.
        $this->FLD('quantityInPack', 'double(smartRound)', 'input=none,column=none');
        
        // Цена за единица продукт в основна мярка
        $this->FLD('price', 'double(decimals=2)', 'caption=Цена,input=none');
        
        $this->FNC('amount', 'double(minDecimals=2,maxDecimals=2)', 'caption=Сума');
        
        // Брой опаковки (ако има packagingId) или к-во в основна мярка (ако няма packagingId)
        $this->FNC('packQuantity', 'double(Min=0,decimals=2)', 'caption=К-во,input=input,mandatory', 'tdClass=small-field');
        
        // Цена за опаковка (ако има packagingId) или за единица в основна мярка (ако няма packagingId)
        $this->FNC('packPrice', 'double(minDecimals=2)', 'caption=Цена,input');
        
        $this->FLD('discount', 'percent', 'caption=Отстъпка');
        $this->FLD('info', "varchar(50)", 'caption=Колети');
    }


    /**
     * Изчисляване на цена за опаковка на реда
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public function on_CalcPackPrice(core_Mvc $mvc, $rec)
    {
        if (!isset($rec->price) || empty($rec->quantity) || empty($rec->quantityInPack)) {
            return;
        }
    
        $rec->packPrice = $rec->price * $rec->quantityInPack;
    }
    
    
    /**
     * Изчисляване на количеството на реда в брой опаковки
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public function on_CalcPackQuantity(core_Mvc $mvc, $rec)
    {
        if (!isset($rec->price) || empty($rec->quantity) || empty($rec->quantityInPack)) {
            return;
        }
    
        $rec->packQuantity = $rec->quantity / $rec->quantityInPack;
    }
    
    
    /**
     * Изчисляване на сумата на реда
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public function on_CalcAmount(core_Mvc $mvc, $rec)
    {
        if (empty($rec->price) || empty($rec->quantity)) {
            return;
        }
    
        $rec->amount = $rec->price * $rec->quantity;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if(($action == 'edit' || $action == 'delete' || $action == 'add') && isset($rec)){
        	if($mvc->Master->fetchField($rec->shipmentId, 'state') != 'draft'){
        		$requiredRoles = 'no_one';
        	}
        }
    }


	/**
     * След извличане на записите от базата данни
     */
    public static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
    {
        $recs = &$data->recs;
        $orderRec = $data->masterData->rec;
        
        if (empty($recs)) return;
        
        deals_Helper::fillRecs($mvc->Master, $recs, $orderRec);
        
        // Преброява броя на колетите, само ако се показва тази информация
        if(isset($data->listFields['info'])){
        	$orderRec->colletsCount = $mvc->countCollets($recs);
        	$data->masterData->row->colletsCount = cls::get('type_Int')->toVerbal($orderRec->colletsCount);
        }
    }
    
    
    /**
     * Преброява общия брой на колетите
     * @param array $recs - записите от модела
     */
    private function countCollets($recs)
    {
    	$count = 0;
    	foreach ($recs as $rec){
    		
    		// За всяка информация за колети
    		if($rec->info){
    			
    			// Разбиване на записа
    			$info = explode(',', $rec->info);
	    		foreach ($info as &$seq){
	    			
	    			// Ако е посочен интервал от рода 1-5
	    			$seq = explode('-', $seq);
	    			if(count($seq) == 1){
	    				
	    				// Ако няма такова разбиване, се увеличава броя
	    				$count += 1;
	    			} else {
	    				
	    				// Ако е посочен интервал, броя се увеличава с разликата
	    				$count += $seq[1] - $seq[0] +1;
	    			}
	    		}
    		}
    	}
    	
    	// Връщане на броя на колетите
    	return $count;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
    	$rec = &$data->form->rec;
    	$masterRec = $data->masterRec;
    	
    	$ProductManager = ($data->ProductManager) ? $data->ProductManager : cls::get($rec->classId);
    	
    	// Намираме всички продаваеми продукти, и оттях оставяме само складируемите за избор
    	$products = $ProductManager::getByProperty('canSell');
    	$products2 = $ProductManager::getByProperty('canStore');
    	$products = array_intersect_key($products, $products2);
    	
    	expect(count($products));
    	if (empty($rec->id)) {
    		$data->form->addAttr('productId', array('onchange' => "addCmdRefresh(this.form);document.forms['{$data->form->formAttr['id']}'].elements['id'].value ='';document.forms['{$data->form->formAttr['id']}'].elements['packPrice'].value ='';document.forms['{$data->form->formAttr['id']}'].elements['discount'].value ='';this.form.submit();"));
    		$data->form->setOptions('productId', array('' => ' ') + $products);
    	} else {
    		$data->form->setOptions('productId', array($rec->productId => $products[$rec->productId]));
    	}
    }
    
    
    /**
     * След обработка на записите от базата данни
     */
    public function on_AfterPrepareListRows(core_Mvc $mvc, $data)
    {
        $rows = $data->rows;
    	
        // Скриваме полето "мярка"
        $data->listFields = array_diff_key($data->listFields, arr::make('uomId', TRUE));
        
        // Флаг дали има отстъпка
        $haveDiscount = FALSE;
    
        if(count($data->rows)) {
            foreach ($data->rows as $i => &$row) {
            	$rec = &$data->recs[$i];
            	$ProductManager = cls::get($rec->classId);
                
        		$row->productId = $ProductManager->getTitleById($rec->productId);
        		$haveDiscount = $haveDiscount || !empty($rec->discount);
    			
                if (empty($rec->packagingId)) {
                    $row->packagingId = ($rec->uomId) ? $row->uomId : '???';
                } else {
                    $shortUomName = cat_UoM::getShortName($rec->uomId);
                    $row->quantityInPack = $mvc->getFieldType('quantityInPack')->toVerbal($rec->quantityInPack);
                    $row->packagingId .= ' <small class="quiet">' . $row->quantityInPack . ' ' . $shortUomName . '</small>';
                	$row->packagingId = "<span class='nowrap'>{$row->packagingId}</span>";
                }
                
                $row->weight = (!empty($rec->weight)) ? $row->weight : "<span class='quiet'>0</span>";
                $row->volume = (!empty($rec->volume)) ? $row->volume : "<span class='quiet'>0</span>";
            }
        }
    
        if(!$haveDiscount) {
            unset($data->listFields['discount']);
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form &$form)
    { 
        if ($form->isSubmitted() && !$form->gotErrors()) {
            
            // Извличане на информация за продукта - количество в опаковка, единична цена
            $rec = $form->rec;
            
            if($rec->info){
            	if(!preg_match('/^[0-9]+[\ \,\-0-9]*$/', $rec->info, $matches)){
            		$form->setError('info', "Полето може да приема само числа,запетаи и тирета");
            	}
            	
            	$rec->info = preg_replace("/\s+/", "", $rec->info);
            }
        }
    }
    
    
    /**
     * След подготовка на лист тулбара
     */
    public static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if (!empty($data->toolbar->buttons['btnAdd'])) {
    		$productManagers = core_Classes::getOptionsByInterface('cat_ProductAccRegIntf');
    		$masterRec = $data->masterData->rec;
    
    		foreach ($productManagers as $manId => $manName) {
    			$productMan = cls::get($manId);
    			$products = $productMan::getByProperty('canSell');
    			$products2 = $productMan::getByProperty('canStore');
    			$products = array_intersect_key($products, $products2);
    			if(!count($products)){
    				$error = "error=Няма продаваеми {$productMan->title}";
    			}
    
    			$data->toolbar->addBtn($productMan->singleTitle, array($mvc, 'add', $mvc->masterKey => $masterRec->id, 'classId' => $manId, 'ret_url' => TRUE),
    					"id=btnAdd-{$manId},{$error},order=10", 'ef_icon = img/16/shopping.png');
    			unset($error);
    		}
    
    		unset($data->toolbar->buttons['btnAdd']);
    	}
    }
}