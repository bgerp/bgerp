<?php
/**
 * Клас 'purchase_PurchasesDetails'
 *
 * Детайли на мениджър на документи за покупка на продукти (@see purchase_Requests)
 *
 * @category  bgerp
 * @package   purchase
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class purchase_PurchasesDetails extends core_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Детайли на покупки';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Артикул';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'requestId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_Created, purchase_Wrapper, plg_RowNumbering, doc_plg_HidePrices, plg_SaveAndNew, plg_AlignDecimals2,Policy=purchase_PurchaseLastPricePolicy';
    
    
    /**
     * Активен таб на менюто
     */
    public $menuPage = 'Логистика:Покупки';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, purchase';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'purchase_RequestDetails';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, purchase';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, purchase';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId, uomId, packQuantityDelivered, packQuantity, packPrice, discount, amount';
    
        
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';


    /**
     * Активен таб
     */
    public $currentTab = 'Покупки';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'price,amount,discount,packPrice';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('requestId', 'key(mvc=purchase_Purchases)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('classId', 'class(interface=cat_ProductAccRegIntf, select=title)', 'caption=Мениджър,silent,input=hidden');
        
        $this->FLD('productId', 'int', 'caption=Продукт,mandatory,silent', 'tdClass=large-field leftCol');
        $this->FLD('uomId', 'key(mvc=cat_UoM, select=shortName)', 'caption=Мярка,input=none');
        $this->FLD('packagingId', 'key(mvc=cat_Packagings, select=name, allowEmpty)', 'caption=Мярка', 'tdClass=small-field');

        // Количество в основна мярка
        $this->FLD('quantity', 'double(Min=0)', 'caption=Количество,input=none', 'tdClass=small-field');
        $this->FLD('quantityDelivered', 'double', 'caption=К-во->Доставено,input=none'); // Сумата на доставената стока
        $this->FNC('packQuantityDelivered', 'double(minDecimals=0)', 'caption=Дост.,input=none'); // Сумата на доставената стока
        
        // Количество (в осн. мярка) в опаковката, зададена от 'packagingId'; Ако 'packagingId'
        // няма стойност, приема се за единица.
        $this->FLD('quantityInPack', 'double(smartRound)', 'input=none');
        
        // Цена за единица продукт в основна мярка
        $this->FLD('price', 'double(decimals=2)', 'caption=Цена,input=none');
        $this->FNC('amount', 'double(minDecimals=2,maxDecimals=2)', 'caption=Сума');
        
        // Брой опаковки (ако има packagingId) или к-во в основна мярка (ако няма packagingId)
        $this->FNC('packQuantity', 'double', 'caption=К-во,input=input,mandatory');
        
        // Цена за опаковка (ако има packagingId) или за единица в основна мярка (ако няма packagingId)
        $this->FNC('packPrice', 'double(minDecimals=2)', 'caption=Цена,input=input');
        
        $this->FLD('discount', 'percent(min=-1,max=1)', 'caption=Отстъпка');
    }
    
    
    /**
     * Изчисляване на цена за опаковка на реда
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
     */
    public function on_CalcPackQuantity(core_Mvc $mvc, $rec)
    {
        if (!isset($rec->price) || empty($rec->quantity) || empty($rec->quantityInPack)) {
            return;
        }
        
        $rec->packQuantity = $rec->quantity / $rec->quantityInPack;
    }
    
    
    /**
     * Изчисляване на доставеното количеството на реда в брой опаковки
     */
    public function on_CalcPackQuantityDelivered(core_Mvc $mvc, $rec)
    {
        if (empty($rec->quantityDelivered) || empty($rec->quantityInPack)) {
            return;
        }
        
        $rec->packQuantityDelivered = $rec->quantityDelivered / $rec->quantityInPack;
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
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        $requiredRoles = $mvc->Master->getRequiredRoles('edit', (object)array('id' => $rec->requestId), $userId);
    }
    
    
    /**
     * След подготовка на записите от базата данни
     */
    public static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
    {
        $recs = &$data->recs;
        $purchaseRec = $data->masterData->rec;
        
        if (empty($recs)) return;
        deals_Helper::fillRecs($mvc->Master, $recs, $purchaseRec);
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $data->listFields = array_diff_key($data->listFields, arr::make('uomId', TRUE));
        
        // Флаг дали има отстъпка
        $haveDiscount = FALSE;
        $haveQuantityDelivered = FALSE;
        
        if(count($data->rows)) {
            foreach ($data->rows as $i => &$row) {
                $rec = $data->recs[$i];
                
                $ProductManager = cls::get($rec->classId);
        		$row->productId = $ProductManager->getTitleById($rec->productId, TRUE, $rec->tplLang);
                
            	if(!Mode::is('printing') && !Mode::is('text', 'xhtml')){
        			$row->productId = ht::createLinkRef($row->productId, array($ProductManager, 'single', $rec->productId));
        		}
        		
                $haveDiscount = $haveDiscount || !empty($rec->discount);
                $haveQuantityDelivered = $haveQuantityDelivered || !empty($rec->quantityDelivered);
                
                if (empty($rec->packagingId)) {
                	$row->packagingId = ($rec->uomId) ? $row->uomId : '???';
                } else {
                    $shortUomName = cat_UoM::getShortName($rec->uomId);
                    $row->packagingId .= ' <small class="quiet">' . $row->quantityInPack . ' ' . $shortUomName . '</small>';
                	$row->packagingId = "<span class='nowrap'>{$row->packagingId}</span>";
                }
            }
        }

        if(!$haveDiscount) {
            unset($data->listFields['discount']);
        }
        
        // Ако няма записи, в режим xhtml или няма поне едно доставено к-во не показваме колонкта за доставено
        if (empty($data->rows) || Mode::is('text', 'xhtml') || !$haveQuantityDelivered) {
        	unset($data->listFields['packQuantityDelivered']);
        }
    }
    
    
	/**
     * Преди подготвяне на едит формата
     */
    static function on_BeforePrepareEditForm($mvc, &$res, $data)
    {
    	if($classId = Request::get('classId', 'class(interface=cat_ProductAccRegIntf)')){
    		$data->ProductManager = cls::get($classId);
    		
    		$mvc->getField('productId')->type = cls::get('type_Key', array('params' => array('mvc' => $data->ProductManager->className, 'maxSuggestions' => 1000000000)));
    	}
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $rec       = &$data->form->rec;
        $masterRec = $data->masterRec;
       	$ProductManager = ($data->ProductManager) ? $data->ProductManager : cls::get($rec->classId);
        
        $data->form->fields['packPrice']->unit = "|*" . $masterRec->currencyId . ", ";
        $data->form->fields['packPrice']->unit .= ($masterRec->chargeVat == 'yes') ? '|с ДДС|*' : '|без ДДС|*';
        
        // @TODO временно
        $products = $ProductManager::getByProperty('canBuy');
        expect(count($products));
        
        if (empty($rec->id)) {
        	$data->form->addAttr('productId', array('onchange' => "addCmdRefresh(this.form);document.forms['{$data->form->formAttr['id']}'].elements['id'].value ='';document.forms['{$data->form->formAttr['id']}'].elements['packPrice'].value ='';document.forms['{$data->form->formAttr['id']}'].elements['discount'].value ='';this.form.submit();"));
        	$data->form->setOptions('productId', array('' => ' ') + $products);
        } else {
        	$data->form->setOptions('productId', array($rec->productId => $products[$rec->productId]));
        }
        
        $data->form->setSuggestions('discount', arr::make('5 %,10 %,15 %,20 %,25 %,30 %', TRUE));
              
        if (!empty($rec->packPrice)) {
        	$vat = cls::get($rec->classId)->getVat($rec->productId, $masterRec->valior);
        	$rec->packPrice = deals_Helper::getPriceToCurrency($rec->packPrice, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    { 
    	$ProductMan = cls::get($form->rec->classId);
    	if($form->rec->productId){
    		$form->setOptions('packagingId', $ProductMan->getPacks($form->rec->productId));
    		unset($form->getFieldType('packagingId')->params['allowEmpty']);
    		
    		// Само при рефреш слагаме основната опаковка за дефолт
    		if($form->cmd == 'refresh'){
	    		$baseInfo = $ProductMan->getBasePackInfo($form->rec->productId);
	    		if($baseInfo->classId == cat_Packagings::getClassId()){
	    			$form->rec->packagingId = $baseInfo->id;
	    		}
    		}
        }
        
    	if ($form->isSubmitted() && !$form->gotErrors()) {
            
        	// Извличане на информация за продукта - количество в опаковка, единична цена
            $rec = &$form->rec;

    		if($rec->packQuantity == 0){
    			$form->setError('packQuantity', 'Количеството не може да е|* "0"');
    		}
    		
            $masterRec  = purchase_Purchases::fetch($rec->{$mvc->masterKey});
            
        	if(empty($rec->id)){
    			$where = "#requestId = {$rec->requestId} AND #classId = {$rec->classId} AND #productId = {$rec->productId}";
    			if($pRec = $mvc->fetch($where)){
    				$form->setWarning("productId", "Има вече такъв продукт. Искате ли да го обновите?");
    				$rec->id = $pRec->id;
    				$update = TRUE;
    			}
            }
            
            $productRef = new core_ObjectReference($ProductMan, $rec->productId);
            expect($productInfo = $productRef->getProductInfo());
           
            if (empty($rec->packagingId)) {
                // Покупка в основна мярка
                $rec->quantityInPack = 1;
            } else {
                // Покупка на опаковки
                if (!$packInfo = $productInfo->packagings[$rec->packagingId]) {
                    $form->setError('packagingId', "Артикулът не поддържа избраната опаковка");
                    return;
                }
                
                $rec->quantityInPack = $packInfo->quantity;
            }
            
            $rec->quantity = $rec->packQuantity * $rec->quantityInPack;
            $vat = cls::get($rec->classId)->getVat($rec->productId, $masterRec->valior);
            
            if (!isset($rec->packPrice)) {
            	// Определяне на цена, количество и отстъпка за опаковка
            	$policyInfo = $mvc->Policy->getPriceInfo(
            			$masterRec->contragentClassId,
            			$masterRec->contragentId,
            			$rec->productId,
            			$rec->classId,
            			$rec->packagingId,
            			$rec->packQuantity,
            			$masterRec->valior
            	);
            	
            	// Ако няма последна покупна цена и не се обновява запис в текущата покупка
                if (!isset($policyInfo->price) && empty($pRec)) {
                    $form->setError('price', 'Продукта няма цена в избраната ценова политика');
                } else {
                	
                	// Ако се обновява вече съществуващ запис
                	if($pRec){
                		$pRec->packPrice = deals_Helper::getPriceToCurrency($pRec->packPrice, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
        			}
                	
                	// Ако се обновява запис се взима цената от него, ако не от политиката
                	$rec->price = ($pRec->price) ? $pRec->price : $policyInfo->price;
                	$rec->packPrice = ($pRec->packPrice) ? $pRec->packPrice : $policyInfo->price * $rec->quantityInPack;
                }
                
            } else {
            	
            	// Обръщаме цената в основна валута, само ако не се ъпдейтва или се ъпдейтва и е чекнат игнора
            	if(!$update || ($update && Request::get('Ignore'))){
            		$rec->packPrice =  deals_Helper::getPriceFromCurrency($rec->packPrice, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
            	}
                
                // Изчисляване цената за единица продукт в осн. мярка
                $rec->price  = $rec->packPrice  / $rec->quantityInPack;
                
            }
            
    		// Записваме основната мярка на продукта
            $rec->uomId = $productInfo->productRec->measureId;
            
            // При редакция, ако е променена опаковката слагаме преудпреждение
            if($rec->id){
            	$oldRec = $mvc->fetch($rec->id);
            	if($oldRec && $rec->packagingId != $oldRec->packagingId && trim($rec->packPrice) == trim($oldRec->packPrice)){
            		$form->setWarning('packPrice,packagingId', 'Опаковката е променена без да е променена цената.|*<br />| Сигурнили сте че зададената цена отговаря на  новата опаковка!');
            	}
            }
        }
    }
    
    
    /**
     * След подготовка на лист тулбара
     */
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
    	if (!empty($data->toolbar->buttons['btnAdd'])) {
    		
    		$productManagers = core_Classes::getOptionsByInterface('cat_ProductAccRegIntf');
    		$masterRec = $data->masterData->rec;
    		foreach ($productManagers as $manId => $manName) {
    			$productMan = cls::get($manId);
    			$canBuy = $productMan::getByProperty('canBuy', 1);
    			
    			if(!count($canBuy)){
    				$error = "error=Няма купуваеми {$productMan->title}";
    			}
    	
    			$data->toolbar->addBtn($productMan->singleTitle, array($mvc, 'add', 'requestId'=> $masterRec->id, 'classId' => $manId, 'ret_url' => TRUE),
    					"id=btnAdd-{$manId},{$error},order=10", 'ef_icon = img/16/shopping.png');
    			unset($error);
    		}
    	
    		unset($data->toolbar->buttons['btnAdd']);
    	}
    }
}