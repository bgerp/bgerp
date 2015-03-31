<?php


/**
 * Клас 'deals_DealDetail'
 *
 * Клас за наследяване от детайли на бизнес документи(@see deals_DealDetail)
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class deals_DealDetail extends doc_Detail
{
 	
 	
 	/**
     * Изчисляване на сумата на реда
     * 
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public static function on_CalcAmount(core_Mvc $mvc, $rec)
    {
        if (empty($rec->price) || empty($rec->quantity)) {
            return;
        }
        
        $rec->amount = $rec->price * $rec->quantity;
    }
    
    
    /**
     * Изчисляване на цена за опаковка на реда
     * 
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public static function on_CalcPackPrice(core_Mvc $mvc, $rec)
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
    public static function on_CalcPackQuantity(core_Mvc $mvc, $rec)
    {
        if (empty($rec->quantity) || empty($rec->quantityInPack)) {
            return;
        }
        
        $rec->packQuantity = $rec->quantity / $rec->quantityInPack;
    }
    
    
    /**
     * След описанието на полетата
     */
    public static function getDealDetailFields(&$mvc)
    {
    	$mvc->FLD('classId', 'class(interface=cat_ProductAccRegIntf, select=title)', 'caption=Мениджър,silent,input=hidden');
    	$mvc->FLD('productId', 'int', 'caption=Продукт,notNull,mandatory', 'tdClass=large-field leftCol wrap,removeAndRefreshForm=packPrice|discount|uomId|packagingId');
    	$mvc->FLD('uomId', 'key(mvc=cat_UoM, select=shortName)', 'caption=Мярка,input=none');
    	$mvc->FLD('packagingId', 'key(mvc=cat_Packagings, select=name, allowEmpty)', 'caption=Мярка', 'tdClass=small-field');
    	
    	// Количество в основна мярка
    	$mvc->FLD('quantity', 'double', 'caption=Количество,input=none');
    	
    	$mvc->FLD('quantityDelivered', 'double', 'caption=К-во->Доставено,input=none'); // Експедирано количество (в основна мярка)
    	
    	// Количество (в осн. мярка) в опаковката, зададена от 'packagingId'; Ако 'packagingId'
    	// няма стойност, приема се за единица.
    	$mvc->FLD('quantityInPack', 'double', 'input=none');
    	
    	// Цена за единица продукт в основна мярка
    	$mvc->FLD('price', 'double', 'caption=Цена,input=none');
    	
    	// Брой опаковки (ако има packagingId) или к-во в основна мярка (ако няма packagingId)
    	$mvc->FNC('packQuantity', 'double(Min=0)', 'caption=К-во,input=input,mandatory');
    	$mvc->FNC('amount', 'double(minDecimals=2,maxDecimals=2)', 'caption=Сума');
    	
    	// Цена за опаковка (ако има packagingId) или за единица в основна мярка (ако няма packagingId)
    	$mvc->FNC('packPrice', 'double(minDecimals=2)', 'caption=Цена,input');
    	$mvc->FLD('discount', 'percent(min=-1,max=1)', 'caption=Отстъпка');
    	$mvc->FLD('showMode', 'enum(auto=Автоматично,detailed=Разширено,short=Кратко)', 'caption=Показване,notNull,default=auto');
    	$mvc->FLD('notes', 'richtext(rows=3)', 'caption=Забележки');
    }
    
    
    /**
     * След описанието
     */
    public static function on_AfterDescription(&$mvc)
    {
    	// Скриване на полетата за създаване
    	$mvc->setField('createdOn', 'column=none');
    	$mvc->setField('createdBy', 'column=none');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if(($action == 'delete' || $action == 'add' || $action == 'edit') && isset($rec)){
        	$state = $mvc->Master->fetchField($rec->{$mvc->masterKey}, 'state');
        	if($state != 'draft'){
        		$requiredRoles = 'no_one';
        	}
        }
    }
    
    
    /**
     * След извличане на записите от базата данни
     */
    public static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
    {
        if (empty($data->recs)) return;
    	$recs = &$data->recs;
        
        deals_Helper::fillRecs($mvc->Master, $recs, $data->masterData->rec);
    }
    
    
    /**
     * Преди подготвяне на едит формата
     */
    public static function on_BeforePrepareEditForm($mvc, &$res, $data)
    {
    	if($classId = Request::get('classId', 'class(interface=cat_ProductAccRegIntf)')){  
    		$data->ProductManager = cls::get($classId);
    		
    		$mvc->getField('productId')->type = cls::get('type_Key', array('params' => array('mvc' => $data->ProductManager->className, 'select' => 'name')));
    	}
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $rec       = &$data->form->rec;
        $masterRec = $data->masterRec;
       	$ProductManager = ($data->ProductManager) ? $data->ProductManager : cls::get($rec->classId);
       	
       	$data->form->fields['packPrice']->unit = "|*" . $masterRec->currencyId . ", ";
        $data->form->fields['packPrice']->unit .= ($masterRec->chargeVat == 'yes') ? "|с ДДС|*" : "|без ДДС|*";
       
        $products = $ProductManager->getProducts($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->valior, $mvc->metaProducts);
        expect(count($products));
        
        $data->form->setSuggestions('discount', array('' => '') + arr::make('5 %,10 %,15 %,20 %,25 %,30 %', TRUE));
        
        if (empty($rec->id)) {
        	$data->form->setOptions('productId', array('' => ' ') + $products);
        	
        } else {
            // Нямаме зададена ценова политика. В този случай задъжително трябва да имаме
            // напълно определен продукт (клас и ид), който да не може да се променя във формата
            // и полето цена да стане задължително
            $data->form->setOptions('productId', array($rec->productId => $products[$rec->productId]));
        }
        
        if (!empty($rec->packPrice)) {
        	$vat = cls::get($rec->classId)->getVat($rec->productId, $masterRec->valior);
        	$rec->packPrice = deals_Helper::getDisplayPrice($rec->packPrice, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    protected static function inputDocForm(core_Mvc $mvc, core_Form $form)
    {
    	$rec = &$form->rec;
    	$masterRec  = $mvc->Master->fetch($rec->{$mvc->masterKey});
    	$priceAtDate = ($masterRec->pricesAtDate) ? $masterRec->pricesAtDate : $masterRec->valior;
    	$update = FALSE;
    	
    	expect($ProductMan = cls::get($rec->classId));
    	if($rec->productId){
    		$productRef = new core_ObjectReference($ProductMan, $rec->productId);
    		expect($productInfo = $productRef->getProductInfo());
    		
    		$vat = cls::get($rec->classId)->getVat($rec->productId, $masterRec->valior);
    		$packs = $ProductMan->getPacks($rec->productId);
    		if(isset($rec->packagingId) && !isset($packs[$rec->packagingId])){
    			$packs[$rec->packagingId] = cat_Packagings::getTitleById($rec->packagingId, FALSE);
    		}
    		
    		if(count($packs)){
    			$form->setOptions('packagingId', $packs);
    		} else {
    			$form->setReadOnly('packagingId');
    		}
    		
    		$uomName = cat_UoM::getTitleById($productInfo->productRec->measureId);
    		$form->setField('packagingId', "placeholder={$uomName}");
    	
    		// Само при рефреш слагаме основната опаковка за дефолт
    		if($form->cmd == 'refresh'){
    			$baseInfo = $ProductMan->getBasePackInfo($rec->productId);
    			 
    			if($baseInfo->classId == 'cat_Packagings'){
    				$form->rec->packagingId = $baseInfo->id;
    			}
    		}
    	
    		if(isset($mvc->LastPricePolicy)){
    			$policyInfoLast = $mvc->LastPricePolicy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->classId, $rec->packagingId, $rec->packQuantity, $priceAtDate, $masterRec->currencyRate, $masterRec->chargeVat);
    			if($policyInfoLast->price != 0){
    				$form->setSuggestions('packPrice', array('' => '', "{$policyInfoLast->price}" => $policyInfoLast->price));
    			}
    		}
    	}
    	 
    	if ($form->isSubmitted() && !$form->gotErrors()) {
    	
    		// Извличане на информация за продукта - количество в опаковка, единична цена
    		$rec = &$form->rec;
    	
    		if($rec->packQuantity == 0){
    			$form->setError('packQuantity', 'Количеството не може да е|* "0"');
    		}
    	
    		if(empty($rec->id)){
    			$where = "#{$mvc->masterKey} = {$rec->{$mvc->masterKey}} AND #classId = {$rec->classId} AND #productId = {$rec->productId} AND #packagingId";
    			$where .= ($rec->packagingId) ? "={$rec->packagingId}" : " IS NULL";
    			if($pRec = $mvc->fetch($where)){
    				$form->setWarning("productId", "Има вече такъв продукт. Искате ли да го обновите?");
    				$rec->id = $pRec->id;
    				$update = TRUE;
    			}
    			}
    	
    			$rec->quantityInPack = (empty($rec->packagingId)) ? 1 : $productInfo->packagings[$rec->packagingId]->quantity;
    			$rec->quantity = $rec->packQuantity * $rec->quantityInPack;
    	
    			if (!isset($rec->packPrice)) {
    				$Policy = (isset($mvc->Policy)) ? $mvc->Policy : cls::get($rec->classId)->getPolicy();
    				$policyInfo = $Policy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->classId, $rec->packagingId, $rec->packQuantity, $priceAtDate, $masterRec->currencyRate, $masterRec->chargeVat);
    				 
    				 
    				if (empty($policyInfo->price) && empty($pRec)) {
    					$form->setError('packPrice', 'Продукта няма цена в избраната ценова политика');
    				} else {
    					// Ако се обновява вече съществуващ запис
    					if($pRec){
    						$pRec->packPrice = deals_Helper::getDisplayPrice($pRec->packPrice, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
    					}
    	
    					// Ако се обновява запис се взима цената от него, ако не от политиката
    					$price = ($pRec->price) ? $pRec->price : $policyInfo->price;
    					$rec->packPrice = ($pRec->packPrice) ? $pRec->packPrice : $policyInfo->price * $rec->quantityInPack;
    					if($policyInfo->discount && empty($rec->discount)){
    						$rec->discount = $policyInfo->discount;
    					}
    				}
    				 
    				$price = $policyInfo->price;
    			} else {
    				$price = $rec->packPrice / $rec->quantityInPack;
    				 
    				// Обръщаме цената в основна валута, само ако не се ъпдейтва или се ъпдейтва и е чекнат игнора
    				if(!$update || ($update && Request::get('Ignore'))){
    					$rec->packPrice =  deals_Helper::getPurePrice($rec->packPrice, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
    				}
    			}
    	
    			$price = deals_Helper::getPurePrice($price, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
    			$rec->price  = $price;
    	
    			// При редакция, ако е променена опаковката слагаме преудпреждение
    			if($rec->id){
    				$oldRec = $mvc->fetch($rec->id);
    				if($oldRec && $rec->packagingId != $oldRec->packagingId && round($rec->packPrice, 4) == round($oldRec->packPrice, 4)){
    					$form->setWarning('packPrice,packagingId', 'Опаковката е променена без да е променена цената.|*<br />| Сигурнили сте че зададената цена отговаря на  новата опаковка!');
    				}
    			}
    		}
    }
    
    
    /**
     * Преди запис
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
    	if(empty($rec->uomId)){
    		$productInfo = cls::get($rec->classId)->getProductInfo($rec->productId);
    		 
    		// Записваме основната мярка на продукта
    		$rec->uomId = $productInfo->productRec->measureId;
    	}
    }
    
    
    /**
     * Преди подготовка на полетата за показване в списъчния изглед
     */
    public static function on_AfterPrepareListRows($mvc, $data)
    {
    	if(!count($data->recs)) return;
    	
    	$recs = &$data->recs;
    	$rows = &$data->rows;
    	
    	foreach ($rows as $id => &$row){
    		$rec = $recs[$id];
    		
    		$row->productId = cat_Products::getAutoProductDesc($rec->productId, $data->masterData->rec->modifiedOn, $rec->showMode);
    		if($rec->notes){
    			deals_Helper::addNotesToProductRow($row->productId, $rec->notes);
    		}
    	}
    }
    
    
    /**
     * След подготовка на лист тулбара
     */
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
    	if (!empty($data->toolbar->buttons['btnAdd'])) {
    		$masterRec = $data->masterData->rec;
    		
    		$productMan = cls::get('cat_Products');
    		if(!count($productMan->getProducts($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->valior, $mvc->metaProducts, NULL, 1))){
                $error = "error=Няма продаваеми артикули, ";
            }
            
            $data->toolbar->addBtn('Артикул', array($mvc, 'add', "{$mvc->masterKey}" => $masterRec->id, 'classId' => $productMan->getClassId(), 'ret_url' => TRUE),
            "id=btnAdd-{$manId},{$error} order=10,title=Добавяне на артикул", 'ef_icon = img/16/shopping.png');
            
            unset($data->toolbar->buttons['btnAdd']);
        }
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    public static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
    	$recs = &$data->recs;
    	$rows = &$data->rows;
    	
    	if(!count($recs)) return;
    	
    	// Скриване на полето "мярка" 
        $data->listFields = array_diff_key($data->listFields, arr::make('uomId,quantityInPack', TRUE));
        
        // Флаг дали има отстъпка
        $haveDiscount = FALSE;
        $haveQuantityDelivered = FALSE;
        
        if(count($data->rows)) {
            foreach ($data->rows as $i => &$row) {
                $rec = $data->recs[$i];
                
                $haveDiscount = $haveDiscount || !empty($rec->discount);
                $haveQuantityDelivered = $haveQuantityDelivered || !empty($rec->quantityDelivered);
              
                if (empty($rec->packagingId)) {
                	$row->packagingId = ($rec->uomId) ? $row->uomId : $row->packagingId;
                } else {
                   if(cat_Packagings::fetchField($rec->packagingId, 'showContents') == 'yes'){
                   		$shortUomName = cat_UoM::getShortName($rec->uomId);
                   		$row->packagingId .= ' <small class="quiet">' . $row->quantityInPack . ' ' . $shortUomName . '</small>';
                   		$row->packagingId = "<span class='nowrap'>{$row->packagingId}</span>";
                   }
                }
            }
        }
		
        if(!$haveDiscount) {
            unset($data->listFields['discount']);
        }
    }
    
    
    /**
	 * Инпортиране на артикул генериран от ред на csv файл 
	 * @param int $masterId - ид на мастъра на детайла
	 * @param array $row - Обект представляващ артикула за импортиране
	 * 					->code - код/баркод на артикула
	 * 					->quantity - К-во на опаковката или в основна мярка
	 * 					->price - цената във валутата на мастъра, ако няма се изчислява директно
	 * @return  mixed - резултата от експорта
	 */
    function import($masterId, $row)
    {
    	$Master = $this->Master;
    	
    	$pRec = cat_Products::getByCode($row->code);
    	
    	$price = NULL;
    	
    	// Ако има цена я обръщаме в основна валута без ддс, спрямо мастъра на детайла
    	if($row->price){
    		$masterRec = $Master->fetch($masterId);
    		$price = deals_Helper::getPurePrice($row->price, cat_Products::getVat($pRec->productId), $masterRec->currencyRate, $masterRec->chargeVat);
    	}
    	
    	return $Master::addRow($masterId, 'cat_Products', $pRec->productId, $row->quantity, $price, $pRec->packagingId);
    }
    
    
    /**
     * Изпълнява се преди запис на клониран детайл
     */
    public static function on_BeforeSaveClonedDetail($mvc, &$rec)
    {
    	unset($rec->quantityDelivered);
    }
}
