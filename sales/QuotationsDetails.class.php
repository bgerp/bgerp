<?php



/**
 * Мениджър за "Детайли на офертите" 
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class sales_QuotationsDetails extends doc_Detail {
    
    
    /**
     * Заглавие
     */
    public $title = 'Детайли на офертите';
    
    
    /**
     * Заглавие в единствено число
     *
     * @var string
     */
    public $singleTitle = 'Артикул';
    
    
    /**
	 * Мастър ключ към дъските
	 */
	public $masterKey = 'quotationId';
    
    
    /**
     * Кой може да променя?
     */
    public $canAdd = 'ceo,sales';
    
    
    /**
     * Кой може да променя?
     */
    public $canDelete = 'ceo,sales';
    
    
    /**
     * При колко линка в тулбара на реда да не се показва дропдауна
     *
     * @param int
     * @see plg_RowTools2
     */
    public $rowToolsMinLinksToShow = 2;
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, sales_Wrapper, doc_plg_HidePrices, plg_SaveAndNew, LastPricePolicy=sales_SalesLastPricePolicy, cat_plg_CreateProductFromDocument';
    
    
    /**
     * Кой може да променя?
     */
    public $canList = 'no_one';
    
	
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId, quantityInPack, packQuantity, packPrice, discount, tolerance, term, optional, amount, discAmount,quantity';
    
    
    /**
     * Кой таб да бъде отворен
     */
    public $currentTab = 'Оферти';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'price,discount,amount';
  	
  	
    /**
     * Какви мета данни да изискват продуктите, които да се показват
     */
    public $metaProducts = 'canSell';
    
    
    /**
     * Кои полета да се извличат при изтриване
     */
    public $fetchFieldsBeforeDelete = 'quotationId';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'price,tolerance,term';
    
    
  	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('quotationId', 'key(mvc=sales_Quotations)', 'column=none,notNull,silent,hidden,mandatory');
    	$this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,notNull,mandatory,silent,removeAndRefreshForm=packPrice|discount|packagingId');
        
        $this->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName)', 'caption=Мярка,mandatory', 'tdClass=small-field nowrap,smartCenter,input=hidden');
        $this->FNC('packQuantity', 'double(Min=0)', 'caption=Количество,input=input,smartCenter');
        $this->FLD('quantityInPack', 'double(smartRound)', 'input=none');
        $this->FNC('packPrice', 'double(minDecimals=2)', 'caption=Цена,input,smartCenter');
        
        $this->FLD('quantity', 'double(Min=0)', 'caption=Количество,input=none');
    	$this->FLD('price', 'double(minDecimals=2,maxDecimals=4)', 'caption=Ед. цена, input=none');
        $this->FLD('discount', 'percent(smartRound,min=0)', 'caption=Отстъпка,smartCenter');
        $this->FLD('tolerance', 'percent(min=0,max=1,decimals=0)', 'caption=Толеранс,input=none');
    	$this->FLD('term', 'time(uom=days,suggestions=1 ден|5 дни|7 дни|10 дни|15 дни|20 дни|30 дни)', 'caption=Срок,input=none');
    	$this->FLD('vatPercent', 'percent(min=0,max=1,decimals=2)', 'caption=ДДС,input=none');
        $this->FLD('optional', 'enum(no=Не,yes=Да)', 'caption=Опционален,maxRadio=2,columns=2,input=hidden,silent,notNull,value=no');
        $this->FLD('showMode', 'enum(auto=По подразбиране,detailed=Разширен,short=Съкратен)', 'caption=Изглед,notNull,default=auto');
        $this->FLD('notes', 'richtext(rows=3)', 'caption=Забележки,formOrder=110001');
    	$this->setField('packPrice', 'silent');
    }
    
    
    /**
     * Изчисляване на цена за опаковка на реда
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public static function on_CalcPackPrice(core_Mvc $mvc, $rec)
    {
    	if (!isset($rec->price) || empty($rec->quantityInPack)) {
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
     * Помощна ф-я за лайв изчисляване на цената
     * 
     * @param stdClass $rec
     * @param stdClass $masterRec
     * @return void;
     */
    public static function calcLivePrice($rec, $masterRec)
    {
    	$policyInfo = cls::get('price_ListToCustomers')->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->packagingId, $rec->quantity, $rec->date, 1, 'no', NULL, FALSE);
    	
    	if(isset($policyInfo->price)){
    		$rec->price = $policyInfo->price;
    		
    		// Добавяне на транспортните разходи, ако има
    		$fee = tcost_Calcs::get('sales_Quotations', $rec->quotationId, $rec->id)->fee;
    		
    		if(isset($fee) && $fee != tcost_CostCalcIntf::CALC_ERROR){
    			$rec->price += $fee / $rec->quantity;
    		}
    		
    		if(!isset($rec->discount)){
    			$rec->discount = $policyInfo->discount;
    		}
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterPrepareListRecs($mvc, $data)
    {
    	$recs = &$data->recs;
    	$rows = &$data->rows;
    	$masterRec = $data->masterData->rec;
    	$notOptional = $optional = array();
    	$total = new stdClass();
    	$total->discAmount = 0;
    	$data->notOptionalHaveOneQuantity = TRUE;
    	$data->optionalHaveOneQuantity = TRUE;
    	$pcsUom = cat_UoM::fetchBySinonim('pcs')->id;
    	
    	if(count($recs)){
	    	foreach ($recs as $id => $rec){
	    		if(!isset($rec->price)){
	    			self::calcLivePrice($rec, $masterRec);
	    			if(isset($rec->price)){
	    				$rec->packPrice = $rec->price * $rec->quantityInPack;
	    				$rec->amount = $rec->packPrice * $rec->packQuantity;
	    				 
	    				$rec->livePrice = TRUE;
	    			} else {
	    				$data->noTotal = TRUE;
	    			}
	    		}
	    		
	    		if($rec->optional == 'no'){
	    			if($rec->packQuantity != 1 || $rec->packagingId != $pcsUom) {
	    				$data->notOptionalHaveOneQuantity = FALSE;
	    			}
	    			
	    			$notOptional[$id] = $rec;
	    		}  else {
	    			if($rec->packQuantity != 1 || $rec->packagingId != $pcsUom) {
	    				$data->optionalHaveOneQuantity = FALSE;
	    			}
	    			
	    			$optional[$id] = $rec;
	    		}
	    	}
    	}
    	
    	$data->countNotOptional = count($notOptional);
    	$data->countOptional = count($optional);
    	
    	// Подготовка за показване на задължителнтие продукти
    	deals_Helper::fillRecs($mvc, $notOptional, $masterRec);
    	
    	$notDefinedAmount = FAlSE;
    	$onlyNotOptionalRec = NULL;
    	
    	if($data->countNotOptional == 1 && $data->notOptionalHaveOneQuantity){
    		unset($data->noTotal);
    		list($firstKey) = array_keys($notOptional);
    		$onlyNotOptionalRec = $notOptional[$firstKey];
    		if(!isset($onlyNotOptionalRec->price)){
    			$notDefinedAmount = TRUE;
    		}
    	}
    	
    	if(empty($data->noTotal) && count($notOptional)){
    		
    		// Запомня се стойноста и ддс-то само на опционалните продукти
    		$data->summary = deals_Helper::prepareSummary($mvc->_total, $masterRec->date, $masterRec->currencyRate, $masterRec->currencyId, $masterRec->chargeVat, FALSE, $masterRec->tplLang);
    		
    		if(isset($data->summary->vat009) && !isset($data->summary->vat0) && !isset($data->summary->vat02)){
    			$data->summary->onlyVat = $data->summary->vat009;
    			unset($data->summary->vat009);
    		} elseif(isset($data->summary->vat0) && !isset($data->summary->vat009) && !isset($data->summary->vat02)){
    			$data->summary->onlyVat = $data->summary->vat0;
    			unset($data->summary->vat0);
    		} elseif(isset($data->summary->vat02) && !isset($data->summary->vat009) && !isset($data->summary->vat0)){
    			$data->summary->onlyVat = $data->summary->vat02;
    			unset($data->summary->vat02);
    		}
    		
    		// Обработваме сумарните данни
    		if($data->masterData->rec->chargeVat != 'separate'){
    			$data->summary->chargeVat = $data->masterData->row->chargeVat;
    		}
    		
    		if(!$data->summary->discountValue){
    			$data->summary->discountValue = '-';
    			$data->summary->discountTitle = '-';
    		} else {
    			$data->summary->discountTitle = 'Отстъпка';
    			$data->summary->discountValue = "- {$data->summary->discountValue}";
    		}
    		
    		if(!$data->summary->neto){
    			$data->summary->neto = '-';
    			$data->summary->netTitle = '-';
    		} else {
    			$data->summary->netTitle = 'Нето';
    		}
    		
    		if($notDefinedAmount === TRUE){
    			$data->summary->value = '???';
    			$data->summary->total = "<span class='quiet'>???</span>";
    		}
    		
    		// Ако има само 1 артикул и той е в 1 бройка и няма опционални и цената му е динамично изчислена
    		if(is_object($onlyNotOptionalRec)){
    			if($onlyNotOptionalRec->livePrice === TRUE){
    				$rowAmount = cls::get('type_Double', array('params' => array('decimals' => 2)))->toVerbal($onlyNotOptionalRec->amount);
    				$data->summary->value = "<span style='color:blue'>{$rowAmount}</span>";
    				$data->summary->value = ht::createHint($data->summary->value, 'Сумата е динамично изчислена. Ще бъде записана при активиране', 'notice', FALSE, 'width=14px,height=14px');
    				
    				$data->summary->total = "<span style='color:blue'>{$rowAmount}</span>";
    				$data->summary->total = ht::createHint($data->summary->total, 'Сумата е динамично изчислена. Ще бъде записана при активиране', 'notice', FALSE, 'width=14px,height=14px');
    			}
    		}
    	}
    	
    	// Подготовка за показване на опционалните продукти
    	deals_Helper::fillRecs($mvc, $optional, $masterRec);
    	$recs = $notOptional + $optional;
    	
    	// Изчисляване на цената с отстъпка
    	foreach($recs as $id => $rec){
            if($rec->optional == 'no'){
    			$other = $mvc->checkUnique($recs, $rec->productId, $rec->id);
            	if($other) unset($data->summary);
    		}
    	}
    }
    
    
    /**
     * Проверява дали има вариация на продукт
     */
    private function checkUnique($recs, $productId, $id, $isOptional = 'no')
    {
    	$other = array_values(array_filter($recs, function ($val) use ($productId, $id, $isOptional) {
           				if($val->optional == $isOptional && $val->productId == $productId && $val->id != $id){
            				return $val;
            			}}));
            			
        return count($other);
    }
    
    
    /**
     * Преди подготвяне на едит формата
     */
    protected static function on_BeforePrepareEditForm($mvc, &$res, $data)
    {
    	if($optional = Request::get('optional')){
    		$prepend = ($optional == 'no') ? 'задължителен' : 'опционален';
    		$mvc->singleTitle = "|{$prepend}|* |{$mvc->singleTitle}|*";
    	}
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
        $rec = &$form->rec;
        $masterRec = $data->masterRec;
        
        $products = cat_Products::getProducts($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->valior, $mvc->metaProducts);
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
        	
        	if(Request::get('Act') != 'CreateProduct'){
        		$vat = cat_Products::getVat($rec->productId, $masterRec->valior);
        	} else {
        		$vat =  acc_Periods::fetchByDate($masterRec->valior)->vatRate;
        	}
        	
        	$rec->packPrice = deals_Helper::getDisplayPrice($rec->packPrice, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
        }
        
	    $form->fields['packPrice']->unit = "|*" . $masterRec->currencyId . ", " .(($masterRec->chargeVat == 'yes') ? '|с ДДС|*' : '|без ДДС|*');
	   
	    if($form->rec->price && $masterRec->currencyRate){
       	 	if($masterRec->chargeVat == 'yes'){
       	 		($rec->vatPercent) ? $vat = $rec->vatPercent : $vat = cat_Products::getVat($rec->productId, $masterRec->date);
       	 		 $rec->price = $rec->price * (1 + $vat);
       	 	}
       	 	
       		$rec->price = $rec->price / $masterRec->currencyRate;
        }
        
        if(empty($rec->id)){
        	$form->setDefault('discount', $mvc->fetchField("#quotationId = {$masterRec->id} AND #discount IS NOT NULL", 'discount'));
        }
    
    	if(isset($rec->productId)){
    		if(cat_Products::getTolerance($rec->productId, 1)){
    			$form->setField('tolerance', 'input');
    		}
    		
    		if(cat_Products::getDeliveryTime($rec->productId, 1)){
    			$form->setField('term', 'input');
    		}
    	}
    }
    
    
	/**
     * Извиква се след въвеждането на данните от Request във формата
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = &$form->rec;
    	$masterRec  = $mvc->Master->fetch($rec->{$mvc->masterKey});
    	$priceAtDate = (isset($masterRec->date)) ? $masterRec->date : dt::today();
    	
    	if($rec->productId){
    		$productInfo = cat_Products::getProductInfo($rec->productId);
    	
    		$vat = cat_Products::getVat($rec->productId, $masterRec->valior);
    		$rec->vatPercent = $vat;
    		$packs = cat_Products::getPacks($rec->productId);
    		$form->setOptions('packagingId', $packs);
    		$form->setDefault('packagingId', key($packs));
    		
    		if(isset($mvc->LastPricePolicy)){
    			$policyInfoLast = $mvc->LastPricePolicy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->packagingId, $rec->packQuantity, $priceAtDate, $masterRec->currencyRate, $masterRec->chargeVat);
    			if($policyInfoLast->price != 0){
    				$form->setSuggestions('packPrice', array('' => '', "{$policyInfoLast->price}" => $policyInfoLast->price));
    			}
    		}
    		
    		// Ако артикула не е складируем, скриваме полето за мярка
    		$productInfo = cat_Products::getProductInfo($rec->productId);
    		if(!isset($productInfo->meta['canStore'])){
    			$measureShort = cat_UoM::getShortName($rec->packagingId);
    			$form->setField('packQuantity', "unit={$measureShort}");
    		} else {
    			$form->setField('packagingId', 'input');
    		}
    	}
    	
    	if($form->isSubmitted()){
    		if(!isset($form->rec->packQuantity)){
    			$defQuantity = cat_UoM::fetchField($form->rec->packagingId, 'defQuantity');
    			if(!empty($defQuantity)){
    				$rec->packQuantity = $defQuantity;
    			} else {
    				$form->setError('packQuantity', 'Не е въведено количество');
    			}
    		}
    		
    		// Ако артикула няма опаковка к-то в опаковка е 1, ако има и вече не е свързана към него е това каквото е било досега, ако още я има опаковката обновяваме к-то в опаковка
    		$rec->quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
    		$rec->quantity = $rec->packQuantity * $rec->quantityInPack;
    		
    		// Проверка дали к-то е под МКП
    		deals_Helper::isQuantityBellowMoq($form, $rec->productId, $rec->quantity, $rec->quantityInPack);
    		
    		if(!$form->gotErrors()){
    		    if(Request::get('Act') != 'CreateProduct'){
    			   if($sameProduct = $mvc->fetch("#quotationId = {$rec->quotationId} AND #productId = {$rec->productId}")){
    				   if($rec->optional == 'no' && $sameProduct->optional == 'yes' && $rec->id != $sameProduct->id){
    					   $form->setError('productId', "Не може да добавите продукта като задължителен, защото фигурира вече като опционален!");
    					   return;
    				   }
    			    }
    			    
    				if($sameProduct = $mvc->fetch("#quotationId = {$rec->quotationId} AND #productId = {$rec->productId}  AND #quantity='{$rec->quantity}'")){
    					if($sameProduct->id != $rec->id){
    						$form->setError('packQuantity', 'Избраният продукт вече фигурира с това количество');
    						return;
    					}
    				}
    			}
    		}
    		
    		$noPrice = FALSE;
    		if (!isset($rec->packPrice)) {
    			$rec->price = NULL;
    		} else {
    			
    			if(!$form->gotErrors()){
    				$price = $rec->packPrice / $rec->quantityInPack;
    				$rec->packPrice =  deals_Helper::getPurePrice($rec->packPrice, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
    			}
    		}
    		
    		// Проверка на цената
    		if(!deals_Helper::isPriceAllowed($price, $rec->quantity, FALSE, $msg)){
    			$form->setError('packPrice,packQuantity', $msg);
    		}
    	
    		if(!$form->gotErrors()){
    			if(isset($price)){
    				$price = deals_Helper::getPurePrice($price, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
    				$rec->price  = $price;
    			}
    		}
    	
    		// При редакция, ако е променена опаковката слагаме преудпреждение
    		if($rec->id){
    			$oldRec = $mvc->fetch($rec->id);
    			if($oldRec && $rec->packagingId != $oldRec->packagingId && round($rec->packPrice, 4) == round($oldRec->packPrice, 4)){
    				$form->setWarning('packPrice,packagingId', "Опаковката е променена без да е променена цената.|*<br />| Сигурни ли сте, че зададената цена отговаря на  новата опаковка!");
    			}
    		}
    		
    		if(!$form->gotErrors()){
    		    if(isset($masterRec->deliveryPlaceId)){
    		        $masterRec->deliveryPlaceId  = crm_Locations::fetchField("#title = '{$masterRec->deliveryPlaceId}'", 'id');
    		    }
    		  
    		    if($rec->productId){
    		    	tcost_Calcs::prepareFee($rec, $form, $masterRec, array('masterMvc' => 'sales_Quotations', 'deliveryLocationId' => 'deliveryPlaceId'));
    		    }
    		}
	    }
    }
    
    
    /**
     * Опитваме се да намерим цена за записа, ако има два предишни записа със цени
     */
    private static function tryToCalcPrice($rec)
    {
    	// Имали за този запис поне два други записа със различни количества
    	$checkQuery = self::getQuery();
    	$checkQuery->where("#quotationId = {$rec->quotationId} AND #productId = {$rec->productId}");
    	$checkQuery->show('quantity,price');
    	$checkQuery->orderBy("id", 'DESC');
    	$checkQuery->limit(2);
    	
    	// Ако да изчисляваме третата цена по формула
    	// (Q1 / Q3) * (P1 - (P1*Q1 - P2*Q2) / (Q1 - Q2)) + (P1*Q1 - P2*Q2) / (Q1 - Q2)
    	if($checkQuery->count() == 2){
    		$fRec = $checkQuery->fetch();
    		$sRec = $checkQuery->fetch();
    		
    		$newPrice = ($fRec->quantity / $rec->quantity) * 
    			($fRec->price - ($fRec->price * $fRec->quantity - $sRec->price * $sRec->quantity) / 
    			($fRec->quantity - $sRec->quantity)) + ($fRec->price * $fRec->quantity - $sRec->price * $sRec->quantity) /
    			($fRec->quantity - $sRec->quantity);
    		
    		return $newPrice;
    	}
    	
    	return NULL;
    }
    
    
	/**
     * Подготовка на бутоните за добавяне на нови редове на фактурата 
     */
    protected static function on_AfterPrepareListToolbar($mvc, $data)
    {
    	unset($data->toolbar->buttons['btnAdd']);
    	unset($data->toolbar->buttons['btnNewProduct']);
    }
    
    
    /**
     * След подготовка на детайлите, изчислява се общата цена
     * и данните се групират
     */
    protected static function on_AfterPrepareDetail($mvc, $res, $data)
    {
	    // Групираме резултатите по продукти и дали са опционални или не
    	$mvc->groupResultData($data);
    }
    
    
    /**
     * Групираме резултатите спрямо продукта
     * @var stdClass $data
     */
    private function groupResultData(&$data)
    {
    	$newRows = array();
    	$dZebra = $oZebra = 'zebra0';
    	
    	// Подготвяме бутоните за добавяне на нов артикул
		if($this->haveRightFor('add', (object)array('quotationId' => $data->masterId))){
    		$products = cat_Products::getProducts($data->masterData->rec->contragentClassId, $data->masterData->rec->contragentId, $data->masterData->rec->date, 'canSell');
    		if(!count($products)){
    			$error = "error=Няма продаваеми артикули,";
    		}
    	
    		$data->addNotOptionalBtn = ht::createBtn('Добавяне',  array($this, 'add', 'quotationId' => $data->masterId, 'optional' => 'no', 'ret_url' => TRUE), FALSE, FALSE, "{$error} ef_icon = img/16/shopping.png, title=Добавяне на артикул към офертата");
    		$data->addOptionalBtn = ht::createBtn('Опционален артикул',  array($this, 'add', 'quotationId' => $data->masterId, 'optional' => 'yes', 'ret_url' => TRUE),  FALSE, FALSE, "{$error} ef_icon = img/16/shopping.png, title=Добавяне на опционален артикул към офертата");
    		
    		if($this->haveRightFor('createProduct', (object)array('quotationId' => $data->masterId))){
    			$data->addNewProductBtn = ht::createBtn('Създаване',  array($this, 'CreateProduct', 'quotationId' => $data->masterId, 'ret_url' => TRUE),  FALSE, FALSE, "id=btnNewProduct,title=Създаване на нов нестандартен артикул,ef_icon = img/16/shopping.png,order=12");
    		}
		}
		
    	// Ако няма записи не правим нищо
    	if(!$data->rows) return;
    	
    	// Заределяме рековете и роуовете на опционални и неопционални
    	$optionalRows = $notOptionalRows = $optionalRecs = $notOptionalRecs = array();
    	foreach($data->recs as $ind => $r){
    		
    		if($r->optional == 'no'){
    			$notOptionalRecs[$ind] = $r;
    			$notOptionalRows[$ind] = $data->rows[$ind];
    		} else {
    			$optionalRecs[$ind] = $r;
    			$optionalRows[$ind] = $data->rows[$ind];
    		}
    	}
    	
    	// Подравняваме ги спрямо едни други
    	plg_AlignDecimals2::alignDecimals($this, $optionalRecs, $optionalRows);
    	plg_AlignDecimals2::alignDecimals($this, $notOptionalRecs, $notOptionalRows);
    	
    	// Подменяме записите за показване с подравнените
    	$data->rows = $notOptionalRows + $optionalRows;
    	$masterRec = $data->masterData->rec;
    	
    	// Групираме записите за по-лесно показване
    	foreach($data->rows as $i => $row){
    		$rec = $data->recs[$i];
    		if($rec->livePrice === TRUE){
    			$row->packPrice = "<span style='color:blue'>{$row->packPrice}</span>";
    			$row->packPrice = ht::createHint($row->packPrice, 'Цената е динамично изчислена. Ще бъде записана при активиране', 'notice', FALSE, 'width=14px,height=14px');
    		}
    		
    		if(!isset($data->recs[$i]->price)){
    			$row->packPrice = '???';
    			$row->amount = '???';
    		}
    		
    		$pId = $data->recs[$i]->productId;
    		$optional = $data->recs[$i]->optional;
    		($optional == 'no') ? $zebra = &$dZebra : $zebra = &$oZebra;
    		
    		// Сездава се специален индекс на записа productId|optional, така
    		// резултатите са разделени по продукти и дали са опционални или не
    		$pId = $pId . "|{$optional}";
    		if(array_key_exists($pId, $newRows)){
    			
    			// Ако има вече такъв продукт, го махаме от записа
    			unset($row->productId);
    			
    			// Слагаме клас на клетките около rospan-а за улеснение на JS
    			$row->rowspanId = $newRows[$pId][0]->rowspanId;
    			$row->TR_CLASS = $newRows[$pId][0]->TR_CLASS;
    		} else {
    			// Слагаме уникален индекс на клетката с продукта
    			$prot = md5($pId.$data->masterData->rec->id);
	    		$row->rowspanId = $row->rowspanpId = "product-row{$prot}";
	    		$zebra = $row->TR_CLASS = ($zebra == 'zebra0') ? 'zebra1' :'zebra0';
    		}
    		
    		$newRows[$pId][] = $row;
    		$newRows[$pId][0]->rowspan = count($newRows[$pId]);
    	}
    	
    	// Така имаме масив в който резултатите са групирани 
    	// по продукти, и това дали са опционални или не,
    	$data->rows = $newRows;
    }
    
    
    /**
     * Променяме рендирането на детайлите
     */
    function renderDetail_($data)
    {
    	$tpl = new ET("");
    	$masterRec = $data->masterData->rec;
    	
    	// Ако всички продукти са с еднаква отстъпка и може да се изчисли обобщената информация, няма да показваме отстъпката
    	$unsetDiscount = FALSE;
    	if($data->summary && count($data->discounts) == 1){
    		if(key($data->discounts)){
    			$unsetDiscount = TRUE;
    		}
    	}
    	
    	// Шаблон за задължителните продукти
    	$shortest = FALSE;
    	$templateFile = ($data->countNotOptional && $data->notOptionalHaveOneQuantity) ? 'sales/tpl/LayoutQuoteDetailsShort.shtml' : 'sales/tpl/LayoutQuoteDetails.shtml';
    	if($data->countNotOptional == 1 && $data->notOptionalHaveOneQuantity){
    		$templateFile = 'sales/tpl/LayoutQuoteDetailsShortest.shtml';
    		$shortest = TRUE;
    	}
    	
    	$dTpl = getTplFromFile($templateFile);
    	if($data->countNotOptional){
    		$dTpl->replace(1, 'DATA_COL_ATTR');
    		$dTpl->replace(2, 'DATA_COL_ATTR_AMOUNT');
    	}
    	
    	if($shortest === TRUE){
    		if($masterRec->state != 'draft'){
    			$dTpl->replace('display:none;', 'none');
    		}
    	}
    	
    	// Шаблон за опционалните продукти
    	$optionalTemplateFile = ($data->countOptional && $data->optionalHaveOneQuantity) ? 'sales/tpl/LayoutQuoteDetailsShort.shtml' : 'sales/tpl/LayoutQuoteDetails.shtml';
    	
    	$oTpl = getTplFromFile($optionalTemplateFile);
    	if($data->countOptional){
    		$oTpl->replace(3, 'DATA_COL_ATTR');
    		$oTpl->replace(4, 'DATA_COL_ATTR_AMOUNT');
    	}
    	
    	$oTpl->removeBlock("totalPlace");
    	
    	$oCount = $dCount = 1;
    	
    	// Променливи за определяне да се скриват ли някои колони
    	$hasQuantityColOpt = FALSE;
    	if($data->rows){
	    	foreach($data->rows as $index => $arr){
	    		list($pId, $optional) = explode("|", $index);
	    		foreach($arr as $key => $row){
	    			core_RowToolbar::createIfNotExists($row->_rowTools);
	    			$row->tools = $row->_rowTools->renderHtml($this->rowToolsMinLinksToShow);
	    			
	    			// Взависимост дали е опционален продукта го добавяме към определения шаблон
	    			if($optional == 'no'){
	    				
	    				// Ако искаме да не показваме отстъпката, махаме я
	    				if($unsetDiscount === TRUE){
	    					unset($row->discount);
	    				}
	    				
	    				$rowTpl = $dTpl->getBlock('ROW');
	    				$id = &$dCount;
	    			} else {
	    				$rowTpl = $oTpl->getBlock('ROW');
	    				
	    				// Слага се 'opt' в класа на колоната да се отличава
	    				$rowTpl->replace("-opt{$masterRec->id}", 'OPT');
	    				if($row->productId){
	    					$rowTpl->replace('-opt-product', 'OPTP');
	    				}
	    				$oTpl->replace("-opt{$masterRec->id}", 'OPT');
	    				$id = &$oCount;
		    			if($hasQuantityColOpt !== TRUE && ($row->quantity)){
		    				$hasQuantityColOpt = TRUE;
		    			}
	    			}
	    			
	    			$row->index = $id++;
	    			$rowTpl->placeObject($row);
	    			$rowTpl->removeBlocks();
	    			$rowTpl->append2master();
	    		}
	    	}
    	}

    	if($dCount  <= 1){
    		$dTpl->replace('<tr><td colspan="6">' . tr("Няма записи") . '</td></tr>', 'ROWS');
    	}
    	
    	if($oCount <= 1){
    		$oTpl->replace('<tr><td colspan="6">' . tr("Няма записи") . '</td></tr>', 'ROWS');
    	}
    	
    	if($summary = $data->summary){
    		if($summary->discountTitle != '-'){
    			$summary->discountTitle = tr($summary->discountTitle);
    		}
    		
    		if($summary->netTitle != '-'){
    			$summary->netTitle = tr($summary->netTitle);
    		}
    		
    		if($masterRec->chargeVat != 'separate'){
    			$summary->vatAmount = tr($summary->vatAmount);
    		}
    		
    		$dTpl->placeObject($summary, 'SUMMARY');
    		$dTpl->replace($summary->sayWords, 'sayWords');
    		
    		// Ако всички артикули имат валидна отстъпка показваме я в обобщената информация
    		if(isset($summary) && count($data->discounts) == 1){
    			if(key($data->discounts)){
    				$dTpl->replace($data->discounts[key($data->discounts)], 'discountPercent');
    			}
    		}
    	} else {
    		$dTpl->removeBlock("totalPlace");
    	}
    	
    	$vatRow = ($masterRec->chargeVat == 'yes') ? tr(', |с ДДС|*') : tr(', |без ДДС|*');
    	$miscMandatory = $masterRec->currencyId . $vatRow;
    	$miscOptional = $masterRec->currencyId . $vatRow;
    	if(count($data->discounts) && $data->hasDiscounts === TRUE){
    		$miscMandatory .= ', ' . tr('без извадени отстъпки');
    	}
    	
    	if(count($data->discountsOptional) && $data->hasDiscounts === TRUE){
    		$miscOptional .= ', ' . tr('без извадени отстъпки');
    	}
    	
    	// Ако сме чернова или има поне един задължителен артикул, рендираме таблицата му
    	if($masterRec->state == 'draft' || $dCount > 1){
    		$tpl->append($this->renderListToolbar($data), 'ListToolbar');
    		$dTpl->append(tr('Оферирани'), 'TITLE');
    		
    		if($shortest !== TRUE){
    			$dTpl->append($miscMandatory, "MISC");
    		}
    		
    		if(isset($data->addNotOptionalBtn)){
    			$dTpl->append($data->addNotOptionalBtn, 'ADD_BTN');
    		}
    		
    		if(isset($data->addNewProductBtn)){
    			$dTpl->append($data->addNewProductBtn, 'ADD_BTN');
    		}
    		
    		$dTpl->removeBlocks();
    		$tpl->append($dTpl, 'MANDATORY');
    	}
    	
    	// Ако сме чернова и има поне един опционален артикул, рендираме таблицата за артикули
    	if($masterRec->state == 'draft' || $oCount > 1){
    		$oTpl->append(tr('Опционални'), 'TITLE');
    		$oTpl->append($miscOptional, "MISC");
    		if(isset($data->addOptionalBtn)){
    			$oTpl->append($data->addOptionalBtn, 'ADD_BTN');
    		}
    		$oTpl->removePlaces();
    		$oTpl->removeBlocks();
    		$tpl->append($oTpl, 'OPTIONAL');
    	}
    	
    	if(!$hasQuantityColOpt){
    		$tpl->append(".quote-col-opt{$masterRec->id} {display:none;} .product-id-opt-product {width:65%;}", 'STYLES');
    	}
    	
    	// Закачане на JS
        $tpl->push('sales/js/ResizeQuoteTable.js', 'JS');
        jquery_Jquery::run($tpl, "resizeQuoteTable();");
		jquery_Jquery::runAfterAjax($tpl, "resizeQuoteTable");
        
    	return $tpl;
    }
    
    
    /**
     * Преди подготовка на полетата за показване в списъчния изглед
     */
    protected static function on_AfterPrepareListRows($mvc, $data)
    {
    	if(!count($data->recs)) return;
    	 
    	$recs = &$data->recs;
    	$rows = &$data->rows;
    	$data->discountsOptional = $data->discounts = array();
    	$data->hasDiscounts = FALSE;
    	$masterRec = $data->masterData->rec;
    	
    	core_Lg::push($masterRec->tplLang);
    	$date = ($masterRec->state == 'draft') ? NULL : $masterRec->modifiedOn;
    	
    	foreach ($rows as $id => &$row){
    		$rec = $recs[$id];
    		if($rec->discount){
    			$data->hasDiscounts = TRUE;
    		}
    		
    		if($rec->optional == 'no'){
    			$data->discounts[$rec->discount] = $row->discount;
    		} else {
    			$data->discountsOptional[$rec->discount] = $row->discount;
    		}
    		
    		$row->productId = cat_Products::getAutoProductDesc($rec->productId, $date, $rec->showMode, 'public', $masterRec->tplLang);
    		if($rec->notes){
    			deals_Helper::addNotesToProductRow($row->productId, $rec->notes);
    		}
    		
    		// Ако е имало проблем при изчисляването на скрития транспорт, показва се хинт
    		$fee = tcost_Calcs::get($mvc->Master, $rec->quotationId, $rec->id)->fee;
    		$vat = cat_Products::getVat($rec->productId, $masterRec->date);
    		$row->amount = tcost_Calcs::getAmountHint($row->amount, $fee, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
    	}
    	
    	core_Lg::pop();
    }
    
    
    /**
     * Конвертира един запис в разбираем за човека вид
     * Входният параметър $rec е оригиналният запис от модела
     * резултата е вербалният еквивалент, получен до тук
     */
    public static function recToVerbal_($rec, &$fields = array())
    {
    	$row = parent::recToVerbal_($rec, $fields);
    	
    	$Double = cls::get('type_Double');
    	$Double->params['decimals'] = 2;
    	
    	// Показваме подробната информация за опаковката при нужда
    	deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
    	 
    	if($rec->amount){
    		$row->amount = $Double->toVerbal($rec->amount);
    	}
    	
    	$row->tolerance = deals_Helper::getToleranceRow($rec->tolerance, $rec->productId, $rec->quantity);
    	
    	if(empty($rec->term)){
    		if($term = cat_Products::getDeliveryTime($rec->productId, $rec->quantity)){
    			$row->term = core_Type::getByName('time')->toVerbal($term);
    			$row->term = ht::createHint($row->term, 'Срокът на доставка е изчислен автоматично на база количеството и параметрите на артикула');
    		}
    	}
    	
    	return $row;
    }
    
    
    /**
     * След проверка на ролите
     */
    protected static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'add' || $action == 'delete') && isset($rec)){
    		$quoteState = $mvc->Master->fetchField($rec->quotationId, 'state');
    		if($quoteState != 'draft'){
    			$requiredRoles = 'no_one';
    		}
    	}
    	
    	if($action == 'createproduct' && isset($rec->cloneId)){
    		$cloneRec = $mvc->fetch($rec->cloneId);
    		if($cloneRec->optional != 'no'){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    

    /**
     * Връща последната цена за посочения продукт направена оферта към контрагента
     *
     * @return object $rec->price  - цена
     * 				  $rec->discount - отстъпка
     */
    public static function getPriceInfo($customerClass, $customerId, $date, $productId, $packagingId = NULL, $quantity = 1)
    {
    	$today = dt::today();
    	
    	$query = sales_QuotationsDetails::getQuery();
    	$query->EXT('contragentClassId', 'sales_Quotations', 'externalName=contragentClassId,externalKey=quotationId');
    	$query->EXT('contragentId', 'sales_Quotations', 'externalName=contragentId,externalKey=quotationId');
    	$query->EXT('state', 'sales_Quotations', 'externalName=state,externalKey=quotationId');
    	$query->EXT('date', 'sales_Quotations', 'externalName=date,externalKey=quotationId');
    	$query->EXT('validFor', 'sales_Quotations', 'externalName=validFor,externalKey=quotationId');
    	$query->XPR('expireOn', 'datetime', 'CAST(DATE_ADD(#date, INTERVAL #validFor SECOND) AS DATE)');
    	
    	// Филтрираме офертите за да намерим на каква цена последно сме оферирали артикула за посоченото количество
    	$query->where("#productId = {$productId} AND #quantity = {$quantity}");
    	$query->where("#contragentClassId = {$customerClass} AND #contragentId = {$customerId}");
    	$query->where("#state = 'active'");
    	$query->where("(#expireOn IS NULL AND #date >= '{$date}') OR (#expireOn IS NOT NULL AND #expireOn >= '{$date}')");
    	$query->orderBy("date,quotationId", 'DESC');
    	
    	$res = (object)array('price' => NULL);
    	if($rec = $query->fetch()){
    		$res->price = $rec->price;
    		$fee = tcost_Calcs::get('sales_Quotations', $rec->quotationId, $rec->id);
    		if($fee){
    			$res->price -= round($fee->fee / $rec->quantity, 4);
    		}
    		
    		if($rec->discount){
    			$res->discount = $rec->discount;
    		}
    	}
    	 
    	return $res;
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	// Синхронизиране на сумата на транспорта
    	if($rec->syncFee === TRUE){
    		tcost_Calcs::sync($mvc->Master, $rec->quotationId, $rec->id, $rec->fee);
    	}
    }
    
    
    /**
     * След изтриване на запис
     */
    public static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {
    	// Инвалидиране на изчисления транспорт, ако има
    	foreach ($query->getDeletedRecs() as $id => $rec) {
    		tcost_Calcs::sync($mvc->Master, $rec->quotationId, $rec->id, NULL);
    	}
    }
    
    
    /**
     * Изпълнява се преди клониране
     */
    protected static function on_BeforeSaveClonedDetail($mvc, &$rec, $oldRec)
    {
    	// Преди клониране клонира се и сумата на цената на транспорта
    	$fee = tcost_Calcs::get($mvc->Master, $oldRec->quotationId, $oldRec->id)->fee;
    	if(isset($fee)){
    		$rec->fee = $fee;
    		$rec->syncFee = TRUE;
    	}
    }
}
