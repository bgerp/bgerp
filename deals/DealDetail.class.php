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
	 * Кои полета от листовия изглед да се скриват ако няма записи в тях
	 */
	public $hideListFieldsIfEmpty = 'discount,reff';
 	
 	
	/**
	 * Полета, които при клониране да не са попълнени
	 *
	 * @see plg_Clone
	 */
	public $fieldsNotToClone = 'tolerance,term';
	
	
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
    	$mvc->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,notNull,mandatory', 'tdClass=productCell leftCol wrap,silent,removeAndRefreshForm=packPrice|discount|packagingId|tolerance|batch');
    	$mvc->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Мярка', 'smartCenter,tdClass=small-field nowrap,silent,removeAndRefreshForm=packPrice|discount,mandatory,input=hidden');
    	
    	// Количество в основна мярка
    	$mvc->FLD('quantity', 'double', 'caption=Количество,input=none');
    	
    	// Количество (в осн. мярка) в опаковката, зададена от 'packagingId'; Ако 'packagingId'
    	// няма стойност, приема се за единица.
    	$mvc->FLD('quantityInPack', 'double', 'input=none');
    	
    	// Цена за единица продукт в основна мярка
    	$mvc->FLD('price', 'double', 'caption=Цена,input=none');
    	
    	// Брой опаковки (ако има packagingId) или к-во в основна мярка (ако няма packagingId)
    	$mvc->FNC('packQuantity', 'double(Min=0)', 'caption=Количество,input,smartCenter');
    	$mvc->FNC('amount', 'double(minDecimals=2,maxDecimals=2)', 'caption=Сума');
    	
    	// Цена за опаковка (ако има packagingId) или за единица в основна мярка (ако няма packagingId)
    	$mvc->FNC('packPrice', 'double(minDecimals=2)', 'caption=Цена,input,smartCenter');
    	$mvc->FLD('discount', 'percent(min=0,max=1)', 'caption=Отстъпка,smartCenter');
    	
    	$mvc->FLD('tolerance', 'percent(min=0,max=1,decimals=0)', 'caption=Толеранс,input=none');
    	$mvc->FLD('term', 'time(uom=days,suggestions=1 ден|5 дни|7 дни|10 дни|15 дни|20 дни|30 дни)', 'caption=Срок,after=tolerance,before=showMode,input=none');
    	
    	$mvc->FLD('showMode', 'enum(auto=По подразбиране,detailed=Разширен,short=Съкратен)', 'caption=Допълнително->Изглед,notNull,default=auto');
    	$mvc->FLD('notes', 'richtext(rows=3)', 'caption=Допълнително->Забележки');
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
        if(($action == 'delete' || $action == 'add' || $action == 'edit' || $action == 'import' || $action == 'createproduct' || $action == 'importlisted') && isset($rec)){
        	$state = $mvc->Master->fetchField($rec->{$mvc->masterKey}, 'state');
        	if($state != 'draft'){
        		$requiredRoles = 'no_one';
        	}
        }
        
        if($action == 'importlisted' && isset($rec)){
        	if($requiredRoles != 'no_one'){
        		if(isset($rec)){
        			$masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey}, 'contragentClassId,contragentId');
        			
        			if ($masterRec->contragentClassId && $masterRec->contragentId) {
        				$param = ($mvc->Master instanceof sales_Sales) ? 'salesList' : 'purchaseList';
        				$param = cond_Parameters::getParameter($masterRec->contragentClassId, $masterRec->contragentId, $param);
        				if(!isset($param)){
        					$requiredRoles = 'no_one';
        				}
        			} else {
        				$requiredRoles = 'no_one';
        			}
        		}
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
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
    	$rec = &$form->rec;
        $masterRec = $data->masterRec;
       	
       	$form->fields['packPrice']->unit = "|*" . $masterRec->currencyId . ", ";
        $form->fields['packPrice']->unit .= ($masterRec->chargeVat == 'yes') ? "|с ДДС|*" : "|без ДДС|*";
       
        $form->setSuggestions('discount', array('' => '') + arr::make('5 %,10 %,15 %,20 %,25 %,30 %', TRUE));
        
        if (empty($rec->id)) {
        	$products = array();
        	
        	// Ако потребителя е партньор
        	if(haveRole('partner')){
        		
        		// И има листвани артикули за контрагента
        		$listSysId = ($mvc instanceof sales_SalesDetails) ? 'salesList' : 'purchaseList';
        		$listId = cond_Parameters::getParameter($masterRec->contragentClassId, $masterRec->contragentId, $listSysId);
        		
        		// Взимат се само артикулите от тях
        		if(isset($listId)){
        			$allProducts = cat_Listings::getAll($listId);
        			foreach ($allProducts as $o){
        				$pRec = cat_Products::fetch($o->productId, 'name,isPublic,code,createdOn');
        				$products[$o->productId] = cat_Products::getRecTitle($pRec, FALSE);
        			}
        		}
        	}
        	 
        	if(!count($products)){
        		$products = cat_Products::getProducts($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->valior, $mvc->metaProducts);
        	}
        	expect(count($products));
        	
        	$form->setOptions('productId', array('' => ' ') + $products);
        } else {
            // Нямаме зададена ценова политика. В този случай задъжително трябва да имаме
            // напълно определен продукт (клас и ид), който да не може да се променя във формата
            // и полето цена да стане задължително
            $form->setOptions('productId', array($rec->productId => cat_Products::getTitleById($rec->productId, FALSE)));
        }
        
        if (!empty($rec->packPrice)) {
        	if(Request::get('Act') != 'CreateProduct'){
        		$vat = cat_Products::getVat($rec->productId, $masterRec->valior);
        	} else {
        		$vat =  acc_Periods::fetchByDate($masterRec->valior)->vatRate;
        	}
        	
        	$rec->packPrice = deals_Helper::getDisplayPrice($rec->packPrice, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
        }
        
        // Показване на толеранс аи срока на доставка, ако има
        if(isset($rec->productId) && !core_Users::haveRole('partner')){
        	if(cat_Products::getTolerance($rec->productId, 1)){
        		$form->setField('tolerance', 'input');
        	}
        	
        	if(cat_Products::getDeliveryTime($rec->productId, 1)){
        		$form->setField('term', 'input');
        	}
        }
        
        if(core_Users::haveRole('partner')){
        	$form->setField('packPrice', 'input=none');
        	$form->setField('tolerance', 'input=none');
        	$form->setField('discount', 'input=none');
        	
        	$mvc->currentTab = 'Нишка';
        	plg_ProtoWrapper::changeWrapper($mvc, 'cms_ExternalWrapper');
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
    	
    	if($rec->productId){
    		$productInfo = cat_Products::getProductInfo($rec->productId);
    		
    		$vat = cat_Products::getVat($rec->productId, $masterRec->valior);
    		$packs = cat_Products::getPacks($rec->productId);
    		$form->setOptions('packagingId', $packs);
    		$form->setDefault('packagingId', key($packs));
    		
    		if(isset($mvc->LastPricePolicy)){
    			$policyInfoLast = $mvc->LastPricePolicy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->packagingId, $rec->packQuantity, $masterRec->valior, $masterRec->currencyRate, $masterRec->chargeVat);
    			if($policyInfoLast->price != 0){
    				$form->setSuggestions('packPrice', array('' => '', "{$policyInfoLast->price}" => $policyInfoLast->price));
    			}
    		}
    		
    		// Ако артикула не е складируем, скриваме полето за мярка
    		if(!isset($productInfo->meta['canStore'])){
    			$measureShort = cat_UoM::getShortName($form->rec->packagingId);
    			$form->setField('packQuantity', "unit={$measureShort}");
    		} else {
    			$form->setField('packagingId', 'input');
    		}
    	}
    	 
    	if ($form->isSubmitted() && !$form->gotErrors()) {
    	
    		// Извличане на информация за продукта - количество в опаковка, единична цена
    		if(!isset($rec->packQuantity)){
    			$defQuantity = cat_UoM::fetchField($rec->packagingId, 'defQuantity');
    			if(!empty($defQuantity)){
    				$rec->packQuantity = $defQuantity;
    			} else {
    				$form->setError('packQuantity', 'Не е въведено количество');
    			}
    		}
    		
    		// Проверка на к-то
    		if(!deals_Helper::checkQuantity($rec->packagingId, $rec->packQuantity, $warning)){
    			$form->setError('packQuantity', $warning);
    		}
    		
    		// Ако артикула няма опаковка к-то в опаковка е 1, ако има и вече не е свързана към него е това каквото е било досега, ако още я има опаковката обновяваме к-то в опаковка
    		$rec->quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
    		$rec->quantity = $rec->packQuantity * $rec->quantityInPack;
    		
    		// Проверка дали к-то е под МКП
    		deals_Helper::isQuantityBellowMoq($form, $rec->productId, $rec->quantity, $rec->quantityInPack);
    		
    		if (!isset($rec->packPrice)) {
    			$Policy = (isset($mvc->Policy)) ? $mvc->Policy : cls::get('price_ListToCustomers');
    			
    			$listId = ($masterRec->priceListId) ? $masterRec->priceListId : NULL;
    			$policyInfo = $Policy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->packagingId, $rec->quantity, $masterRec->valior, $masterRec->currencyRate, $masterRec->chargeVat, $listId);
    			
    			if (empty($policyInfo->price) && empty($pRec)) {
    				$form->setError('packPrice', 'Продуктът няма цена в избраната ценова политика');
    			} else {
    				 
    				// Ако се обновява запис се взима цената от него, ако не от политиката
    				$price = $policyInfo->price;
    				if($policyInfo->discount && !isset($rec->discount)){
    					$rec->discount = $policyInfo->discount;
    				}
    				$rec->autoPrice = TRUE;
    			}
    		} else {
    			$price = $rec->packPrice / $rec->quantityInPack;
    			
    			if(!$form->gotErrors() || ($form->gotErrors() && Request::get('Ignore'))){
    				$rec->packPrice =  deals_Helper::getPurePrice($rec->packPrice, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
    			}
    		}
    		 
    		// Проверка на цената
    		if(!deals_Helper::isPriceAllowed($price, $rec->quantity, $rec->autoPrice, $msg)){
    			$form->setError('packPrice,packQuantity', $msg);
    		}
    		
    		$price = deals_Helper::getPurePrice($price, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
    		$rec->price  = $price;
    		
    		if(Request::get('Act') != 'CreateProduct'){
    			// Ако има такъв запис, сетваме грешка
    			$exRec = deals_Helper::fetchExistingDetail($mvc, $rec->{$mvc->masterKey}, $rec->id, $rec->productId, $rec->packagingId, $rec->price, $rec->discount, $rec->tolerance, $rec->term, $rec->batch, NULL, $rec->notes);
    			if($exRec){
    				$form->setError('productId,packagingId,packPrice,discount,tolerance,term,notes', 'Вече съществува запис със същите данни');
    				unset($rec->packPrice, $rec->price, $rec->quantity, $rec->quantityInPack);
    			}
    		}
    	
    		// При редакция, ако е променена опаковката слагаме преудпреждение
    		if($rec->id){
    			$oldRec = $mvc->fetch($rec->id);
    			if($oldRec && $rec->packagingId != $oldRec->packagingId && round($rec->packPrice, 4) == round($oldRec->packPrice, 4)){
    				$form->setWarning('packPrice,packagingId', "Опаковката е променена без да е променена цената.|*<br />| Сигурнили сте, че зададената цена отговаря на  новата опаковка!");
    			}
    		}
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
    	$masterRec = $data->masterData->rec;
    	
    	core_Lg::push($masterRec->tplLang);
    	$date = ($masterRec->state == 'draft') ? NULL : $masterRec->modifiedOn;
    	$listSysId = ($mvc instanceof sales_SalesDetails) ? 'salesList' : 'purchaseList';
    	$listId = cond_Parameters::getParameter($masterRec->contragentClassId, $masterRec->contragentId, $listSysId);
    	
    	foreach ($rows as $id => &$row){
    		$rec = $recs[$id];
    		core_RowToolbar::createIfNotExists($row->_rowTools);
    		cat_Products::addButtonsToDocToolbar($rec->productId, $row->_rowTools, $mvc->Master->getClassId(), $masterRec->id);
    		
    		// Показване на вашия реф, ако има
    		if(isset($listId)){
    			$row->reff = cat_Listings::getReffByProductId($listId, $rec->productId, $rec->packagingId);
    		}
    		
    		$row->productId = cat_Products::getAutoProductDesc($rec->productId, $date, $rec->showMode, 'public', $masterRec->tplLang);
    		
    		if($rec->notes){
    			deals_Helper::addNotesToProductRow($row->productId, $rec->notes);
    		}
    	}
    	
    	core_Lg::pop();
    }
    
    
    /**
     * След подготовка на лист тулбара
     */
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
    	if (!empty($data->toolbar->buttons['btnAdd'])) {
    		$masterRec = $data->masterData->rec;
    		
    		if(!count(cat_Products::getProducts($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->valior, $mvc->metaProducts, NULL, 1))){
                $error = "error=Няма продаваеми артикули, ";
            }
            
            $data->toolbar->addBtn('Артикул', array($mvc, 'add', "{$mvc->masterKey}" => $masterRec->id, 'ret_url' => TRUE),
            "id=btnAdd-{$masterRec->id},{$error} order=10,title=Добавяне на артикул", 'ef_icon = img/16/shopping.png');
            
            unset($data->toolbar->buttons['btnAdd']);
        }
        
        if($mvc->haveRightFor('importlisted', (object)array("{$mvc->masterKey}" => $data->masterId))){
        	$data->toolbar->addBtn('Списък', array($mvc, 'importlisted', "{$mvc->masterKey}" => $data->masterId, 'ret_url' => TRUE), "id=btnAddImp-{$data->masterId},order=14,title=Добавяне на артикули от списък", 'ef_icon = img/16/shopping.png');
        }
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    public static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
    	$recs = &$data->recs;
    	$rows = &$data->rows;
    	
    	// Скриване на полето "мярка"
    	$data->listFields = array_diff_key($data->listFields, arr::make('quantityInPack', TRUE));
    	
    	if(!count($recs)) return;
    	arr::placeInAssocArray($data->listFields, array('reff' => 'Ваш номер'), 'productId');
    	$data->listTableMvc->FNC('reff', 'varchar', 'smartCenter');
    	
        // Флаг дали има отстъпка
        $haveDiscount = FALSE;
        
        if(count($data->rows)) {
            foreach ($data->rows as $i => &$row) {
                $rec = $data->recs[$i];
                
                $toleranceRow = deals_Helper::getToleranceRow($rec->tolerance, $rec->productId, $rec->quantity);
              	if($toleranceRow){
              		$row->packQuantity .= "<small style='font-size:0.8em;display:block;' class='quiet'>±{$toleranceRow}</small>";
              	}
                
              	// Показваме подробната информация за опаковката при нужда
              	deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
            }
        }
    }
    
    
    /**
	 * Инпортиране на артикул генериран от ред на csv файл
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
    	$Master = $this->Master;
    	
    	$pRec = cat_Products::getByCode($row->code);
    	$pRec->packagingId = (isset($pRec->packagingId)) ? $pRec->packagingId : $row->pack;
    	$price = NULL;
    	
    	// Ако има цена я обръщаме в основна валута без ддс, спрямо мастъра на детайла
    	if($row->price){
    		$masterRec = $Master->fetch($masterId);
    		$price = deals_Helper::getPurePrice($row->price, cat_Products::getVat($pRec->productId), $masterRec->currencyRate, $masterRec->chargeVat);
    	}
    	
    	return $Master::addRow($masterId, $pRec->productId, $row->quantity, $price, $pRec->packagingId);
    }
    
    
    /**
     * Импорт на списък от артикули
     */
    function act_Importlisted()
    {
    	// Проверка на права
    	$this->requireRightFor('importlisted');
    	expect($saleId = Request::get($this->masterKey, 'int'));
    	expect($saleRec = $this->Master->fetch($saleId));
    	$this->requireRightFor('importlisted', (object)array("{$this->masterKey}" => $saleId));
    	
    	// Инстанциране на формата за добавяне
    	$form = cls::get('core_Form');
    	$form->title = 'Импорт на списък към|* ' . $this->Master->getHyperlink($saleId, TRUE);
    	$form->method = 'POST';
    	
    	// Намират се всички листвани артикули
    	$param = ($this->Master instanceof sales_Sales) ? 'salesList' : 'purchaseList';
    	expect($listId = cond_Parameters::getParameter($saleRec->contragentClassId, $saleRec->contragentId, $param));
    	$form->info = tr("|Списък за листване|*:") . cat_Listings::getLink($listId, 0);
    	
    	$listed = cat_Listings::getAll($listId, $saleRec->shipmentStoreId, 50);
    	$form->info .= tr('|* ( |Показване на първите|* <b>50</b> |артикула|* )');
    	
    	// И всички редове от продажбата
    	$query = $this->getQuery();
    	$query->where("#{$this->masterKey} = {$saleId}");
    	$recs = $query->fetchAll();
    	expect(count($listed));
    	
    	// Подготовка на полетата на формата
    	$this->prepareImportListForm($form, $listed, $recs, $saleRec);
    	$form->input();
    	
    	// Ако формата е събмитната
    	if($form->isSubmitted()){
    		$rec = $form->rec;
    		$Policy = ($this->Master instanceof sales_Sales) ? 'price_ListToCustomers' : 'purchase_PurchaseLastPricePolicy';
    		$Policy = cls::get($Policy);
    		
    		// Подготовка на записите
    		$error = $error2 = $error3 = $toSave = $toUpdate = $multiError = array();
    		foreach ($listed as $lId => $lRec){
    			$packQuantity = $rec->{"quantity{$lId}"};
    			$quantityInPack = $rec->{"quantityInPack{$lId}"};
    			$recId = $rec->{"rec{$lId}"};
    			$quantity = $packQuantity * $quantityInPack;
    			$productId = $rec->{"productId{$lId}"};
    			$packagingId = $rec->{"packagingId{$lId}"};
    			$packPrice = $discount = NULL;
    			
    			// Ако няма к-во пропускане на реда
    			if(empty($packQuantity)) continue;
    			 
    			if(!isset($rec->id)){
    				$listId = ($saleRec->priceListId) ? $saleRec->priceListId : NULL;
    				
    				$policyInfo = $Policy->getPriceInfo($saleRec->contragentClassId, $saleRec->contragentId, $productId, $packagingId, $quantity, $saleRec->valior, $saleRec->currencyRate, $saleRec->chargeVat, $listId);
    				if(!isset($policyInfo->price)){
    					$error[$lId] = "quantity{$lId}";
    				} else {
    					$vat = cat_Products::getVat($productId, $saleRec->valior);
    					$price = deals_Helper::getPurePrice($policyInfo->price, $vat, $saleRec->currencyRate, $saleRec->chargeVat);
    					$packPrice = $price * $quantityInPack;
    					$discount = $policyInfo->discount;
    				}
    			}
    			 
    			if(!deals_Helper::checkQuantity($packagingId, $packQuantity, $warning)){
    				$error3[$warning][] = "quantity{$lId}";
    			}
    			 
    			if(isset($lRec->moq) && $packQuantity < $lRec->moq){
    				$error2[$lId] = "quantity{$lId}";
    			}
    			 
    			if(isset($lRec->multiplicity)){
    				if(core_Math::fmod($packQuantity, $lRec->multiplicity) != 0){
    					$multiError[$lId] = "quantity{$lId}";
    				}
    			}
    			
    			// Ако няма грешка със записа
    			if(!array_key_exists($lId, $error)){
    				$obj = (object)array('quantity'           => $packQuantity * $quantityInPack,
    						             'quantityInPack'     => $quantityInPack,
    						             'price'              => $packPrice / $quantityInPack,
    						             'discount'           => $discount,
    						             'productId'          => $productId,
    						             'packagingId'        => $packagingId,
    						             'id'                 => $recId,
    						             "{$this->masterKey}" => $saleRec->id,
    				);
    
    				// Определяне дали ще се добавя или обновява
    				if(isset($obj->id)){
    					$toUpdate[] = $obj;
    				} else {
    					$toSave[] = $obj;
    				}
    			}
    		}
    
    		if(count($error2)){
    			$warningRoles = ($mvc instanceof sales_SalesDetails) ? 'salesMaster,ceo' : 'purchaseMaster,ceo';
    			if(haveRole($warningRoles)){
    				$form->setWarning(implode(',', $error2), "Количеството е под МКП");
    			} else {
    				$form->setError(implode(',', $error2), "Количеството е под МКП");
    			}
    		}
    
    		// Ако има грешка сетва се ерор
    		if(count($error)){
    			$form->setError(implode(',', $error), 'Артикулът няма цена');
    		}
    
    		if(count($error3)){
    			foreach ($error3 as $msg => $fields){
    				$form->setError(implode(',', $fields), $msg);
    			}
    		}
    
    		if(count($multiError)){
    		if(haveRole('salesMaster,ceo')){
    				$form->setWarning(implode(',', $multiError), "Количеството не е кратно на очакваното");
    			} else {
    				$form->setError(implode(',', $multiError), "Количеството не е кратно на очакваното");
    			}
    		}
    		
    		if(!count($error) && !count($error3) && (!count($error2) || (count($error2) && Request::get('Ignore'))) && (!count($multiError) || (count($multiError) && Request::get('Ignore')))){
    			
    			// Запис на обновените записи
    			if(count($toUpdate)){
    				foreach ($toUpdate as $uRec){
    					$uRec->isEdited = TRUE;
    					$this->save($uRec, 'id,quantity');
    				}
    			}
    			
    			if(count($toSave)){
    				foreach ($toSave as $saveRec){
    					$this->save($saveRec);
    				}
    			}
    			
    			$this->Master->invoke('AfterUpdateDetail', array($saleId, $this));
    			 
    			// Редирект към продажбата
    			followRetUrl(NULL, 'Списъкът е импортиран успешно');
    		}
    	}
    	 
    	// Добавяне на тулбар
    	$form->toolbar->addSbBtn('Импорт', 'save', 'ef_icon = img/16/import.png, title = Импорт');
    	$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
    
    	// Рендиране на опаковката
    	$tpl = $this->renderWrapping($form->renderHtml());
    	 
    	return $tpl;
    }
    
    
    /**
     * Подготовка на полетата към формата за листвани артикули
     *
     * @param core_Form $form
     * @param array $listed
     * @param array $recs
     * @param stdClass $saleRec
     * @return boolean void
     */
    private function prepareImportListForm(&$form, $listed, $recs, $saleRec)
    {
    	// За всеки листван артикул
    	foreach ($listed as $lId => $lRec){
    		$meta = cat_Products::fetchField($lRec->productId, $this->metaProducts);
    		if($meta != 'yes') continue;
    		
    		$title = cat_Products::getTitleById($lRec->productId);
    		$title = str_replace(',', ' ', $title);
    		$caption = "|" . $title . "|*";
    		$caption .= " |" . cat_UoM::getShortName($lRec->packagingId);
    		 
    		$listId = ($saleRec->priceListId) ? $saleRec->priceListId : NULL;
    
    		// Проверка дали вече не просъства в продажбата
    		$res = array_filter($recs, function (&$e) use ($lRec) {
    			if($e->productId == $lRec->productId && $e->packagingId == $lRec->packagingId && !isset($e->batch) && !isset($e->tolerance) && !isset($e->term)){
    				return TRUE;
    			}
    			return FALSE;
    		});
    			 
    		$key = key($res);
    		$exRec = $res[$key];
    
    		// Подготовка на полета за всеки артикул
    		$form->FLD("productId{$lId}", "int", "К-во,input=hidden");
    		$form->FLD("packagingId{$lId}", "int", "К-во,input=hidden");
    		$form->FLD("rec{$lId}", "int", "input=hidden");
    		$form->FLD("quantityInPack{$lId}", "double", "input=hidden");
    		$form->FLD("quantity{$lId}", "double(Min=0)", "caption={$caption}->Количество");
    		$form->setDefault("productId{$lId}", $lRec->productId);
    		$form->setDefault("packagingId{$lId}", $lRec->packagingId);
    		
    		$unit = '';
    		if(isset($lRec->moq)){
    			$moq = cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($lRec->moq);
    			$unit = "<i>|МКП||MOQ|* <b>{$moq}</b></i>";
    		}
    
    		if(isset($lRec->multiplicity)){
    			$multiplicity = cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($lRec->multiplicity);
    			$unit .= (($unit) ? ", " : ' ') . "|кратно на|* <b>{$multiplicity}</b>";
    		}
    		
    		if($unit != ''){
    			$form->setField("quantity{$lId}", array('unit' => "|*{$unit}"));
    		}
    		
    		// Ако иам съшествуващ запис, попълват му се стойностите
    		if(isset($exRec)){
    			$form->setDefault("rec{$lId}", $exRec->id);
    			$form->setDefault("quantity{$lId}", $exRec->packQuantity);
    			$form->setDefault("quantityInPack{$lId}", $exRec->quantityInPack);
    		}
    
    		// Задаване на к-то в опаковката
    		$packRec = cat_products_Packagings::getPack($lRec->productId, $lRec->packagingId);
    		$quantityInPack = is_object($packRec) ? $packRec->quantity : 1;
    		$form->setDefault("quantityInPack{$lId}", $quantityInPack);
    	}
    }
}
