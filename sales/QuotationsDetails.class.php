<?php



/**
 * Мениджър за "Детайли на офертите" 
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
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
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'sales_QuotesDetails';
    
    
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
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, sales_Wrapper, plg_AlignDecimals, doc_plg_HidePrices, plg_SaveAndNew, LastPricePolicy=sales_SalesLastPricePolicy';
    
    
    /**
     * Кой може да променя?
     */
    public $canList = 'no_one';
    
	
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId, quantityInPack, packQuantity, packPrice, discount, tolerance, term, optional, amount, discAmount';
    
    
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
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('quotationId', 'key(mvc=sales_Quotations)', 'column=none,notNull,silent,hidden,mandatory');
    	$this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Продукт,notNull,mandatory,silent,removeAndRefreshForm=packPrice|discount|packagingId');
        
        $this->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName)', 'caption=Мярка,mandatory', 'tdClass=small-field');
        $this->FNC('packQuantity', 'double(Min=0)', 'caption=К-во,input=input');
        $this->FLD('quantityInPack', 'double(smartRound)', 'input=none');
        $this->FNC('packPrice', 'double(minDecimals=2)', 'caption=Цена,input');
        
        $this->FLD('quantity', 'double(Min=0)', 'caption=К-во,input=none');
    	$this->FLD('price', 'double(minDecimals=2,maxDecimals=4)', 'caption=Ед. цена, input=none');
        $this->FLD('discount', 'percent(maxDecimals=2)', 'caption=Отстъпка');
        $this->FLD('tolerance', 'percent(min=0,max=1,decimals=0)', 'caption=Толеранс,input=none');
    	$this->FLD('term', 'time(uom=days,suggestions=1 ден|5 дни|7 дни|10 дни|15 дни|20 дни|30 дни)', 'caption=Срок,input=none');
    	$this->FLD('vatPercent', 'percent(min=0,max=1,decimals=2)', 'caption=ДДС,input=none');
        $this->FLD('optional', 'enum(no=Не,yes=Да)', 'caption=Опционален,maxRadio=2,columns=2,input=hidden,silent');
        $this->FLD('showMode', 'enum(auto=По подразбиране,detailed=Разширен,short=Съкратен)', 'caption=Изглед,notNull,default=auto');
        $this->FLD('notes', 'richtext(rows=3)', 'caption=Забележки,formOrder=110001');
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
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterPrepareListRecs($mvc, $data)
    {
    	$recs = &$data->recs;
    	$rows = &$data->rows;
    	$masterRec = $data->masterData->rec;
    	$notOptional = $optional = array();
    	$total = new stdClass();
    	$total->discAmount = 0;
    	$data->notOptionalHaveOneQuantity = TRUE;
    	$data->optionalHaveOneQuantity = TRUE;
    	
    	if(count($recs)){
	    	foreach ($recs as $id => $rec){
	    		if($rec->optional == 'no'){
	    			if($rec->packQuantity != 1) {
	    				$data->notOptionalHaveOneQuantity = FALSE;
	    			}
	    			
	    			$notOptional[$id] = $rec;
	    		}  else {
	    			if($rec->packQuantity != 1) {
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
    	
    	if(empty($data->noTotal) && count($notOptional)){
    		
    		// Запомня се стойноста и ддс-то само на опционалните продукти
    		$data->summary = deals_Helper::prepareSummary($mvc->_total, $masterRec->date, $masterRec->currencyRate, $masterRec->currencyId, $masterRec->chargeVat, FALSE, $masterRec->tplLang);
    		
    		// Обработваме сумарните данни
    		if(!$data->summary->vatAmount){
    			$data->summary->vatAmount = $data->masterData->row->chargeVat;
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
    public static function on_BeforePrepareEditForm($mvc, &$res, $data)
    {
    	if($optional = Request::get('optional')){
    		$prepend = ($optional == 'no') ? 'задължителен' : 'опционален';
    		$mvc->singleTitle = "|{$prepend}|* |{$mvc->singleTitle}|*";
    	}
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
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
        	$vat = cat_Products::getVat($rec->productId, $masterRec->valior);
        	$rec->packPrice = deals_Helper::getDisplayPrice($rec->packPrice, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
        }
        
	    $form->fields['price']->unit = "|*" . $masterRec->currencyId . ", " .(($masterRec->chargeVat == 'yes') ? '|с ДДС|*' : '|без ДДС|*');
	   
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
    		$tolerance = cat_Products::getParamValue($rec->productId, 'tolerance');
    		if(!empty($tolerance)){
    			$form->setField('tolerance', 'input');
    			$form->setDefault('tolerance', $tolerance);
    		}
    		
    		$term = cat_Products::getParamValue($rec->productId, 'term');
    		if(!empty($term)){
    			$form->setField('term', 'input');
    			$form->setDefault('term', $term);
    		}
    	}
    }
    
    
	/**
     * Извиква се след въвеждането на данните от Request във формата
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = &$form->rec;
    	$masterRec  = $mvc->Master->fetch($rec->{$mvc->masterKey});
    	
    	if($rec->productId){
    		$productInfo = cat_Products::getProductInfo($rec->productId);
    	
    		$vat = cat_Products::getVat($rec->productId, $masterRec->valior);
    		$packs = cat_Products::getPacks($rec->productId);
    		$form->setOptions('packagingId', $packs);
    		 
    		if(isset($mvc->LastPricePolicy)){
    			$policyInfoLast = $mvc->LastPricePolicy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->packagingId, $rec->packQuantity, $priceAtDate, $masterRec->currencyRate, $masterRec->chargeVat);
    			if($policyInfoLast->price != 0){
    				$form->setSuggestions('packPrice', array('' => '', "{$policyInfoLast->price}" => $policyInfoLast->price));
    			}
    		}
    	} else {
    		$form->setReadOnly('packagingId');
    	}
    	
    	if($form->isSubmitted()){
    		if(empty($form->rec->packQuantity)){
    			$form->rec->packQuantity = 1;
    		}
    		
    		// Ако артикула няма опаковка к-то в опаковка е 1, ако има и вече не е свързана към него е това каквото е било досега, ако още я има опаковката обновяваме к-то в опаковка
    		$rec->quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
    		$rec->quantity = $rec->packQuantity * $rec->quantityInPack;
    		
    		if (!isset($rec->packPrice)) {
    			$Policy = (isset($mvc->Policy)) ? $mvc->Policy : cls::get('price_ListToCustomers');
    			$policyInfo = $Policy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->packagingId, $rec->packQuantity, $priceAtDate, $masterRec->currencyRate, $masterRec->chargeVat);
    			
    			if(empty($policyInfo->price)){
    				$policyInfo->price = $mvc->tryToCalcPrice($rec);
    			}
    			
    			if (empty($policyInfo->price)) {
    				
    				$form->setError('packPrice', 'Продукта няма цена в избраната ценова политика');
    			} else {
    	
    				// Ако се обновява запис се взима цената от него, ако не от политиката
    				$price = $policyInfo->price;
    				$rec->packPrice = $policyInfo->price * $rec->quantityInPack;
    				if($policyInfo->discount && empty($rec->discount)){
    					$rec->discount = $policyInfo->discount;
    				}
    			}
    				 
    			$price = $policyInfo->price;
    		} else {
    			$price = $rec->packPrice / $rec->quantityInPack;
    			$rec->packPrice =  deals_Helper::getPurePrice($rec->packPrice, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
    		}
    	
    		$price = deals_Helper::getPurePrice($price, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
    		$rec->price  = $price;
    	
    		// При редакция, ако е променена опаковката слагаме преудпреждение
    		if($rec->id){
    			$oldRec = $mvc->fetch($rec->id);
    			if($oldRec && $rec->packagingId != $oldRec->packagingId && round($rec->packPrice, 4) == round($oldRec->packPrice, 4)){
    				$form->setWarning('packPrice,packagingId', "Опаковката е променена без да е променена цената.|*<br />| Сигурни ли сте, че зададената цена отговаря на  новата опаковка!");
    			}
    		}
	    	
    		if(!$form->gotErrors()){
    			if($sameProduct = $mvc->fetch("#quotationId = {$rec->quotationId} AND #productId = {$rec->productId}")){
    				if($rec->optional == 'yes' && $sameProduct->optional == 'no' && $rec->id != $sameProduct->id){
    					$form->setError('productId', "Не може да добавите продукта като опционален, защото фигурира вече като задължителен!");
    			    } elseif($rec->optional == 'no' && $sameProduct->optional == 'yes' && $rec->id != $sameProduct->id){
    					$form->setError('productId', "Не може да добавите продукта като задължителен, защото фигурира вече като опционален!");
    			    }
    			}
    			
    			if($sameProduct = $mvc->fetch("#quotationId = {$rec->quotationId} AND #productId = {$rec->productId}  AND #quantity='{$rec->quantity}'")){
    				if($sameProduct->id != $rec->id){
    					$form->setError('packQuantity', 'Избрания продукт вече фигурира с това количество');
    				}
    			}
    			
    			// Ако във формата са открити грешки, занулаваме вече изчислените полета, да не се показват
    			if($form->gotErrors()){
    				unset($rec->packPrice, $rec->packQuantity, $rec->quantity, $rec->price, $rec->discount);
    			}
    			
    			$rec->vatPercent = $vat;
    		}
	    }
    }
    
    
    /**
     * Опитваме се да намерим цена за записа, ако има два предишни записа със цени
     */
    private function tryToCalcPrice($rec)
    {
    	// Имали за този запис поне два други записа със различни количества
    	$checkQuery = $this->getQuery();
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
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
    	unset($data->toolbar->buttons['btnAdd']);
    }
    
    
    /**
     * След подготовка на детайлите, изчислява се общата цена
     * и данните се групират
     */
    public static function on_AfterPrepareDetail($mvc, $res, $data)
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
    	
    		$data->addNotOptionalBtn = ht::createBtn('Артикул',  array($this, 'add', 'quotationId' => $data->masterId, 'optional' => 'no', 'ret_url' => TRUE), FALSE, FALSE, "{$error} ef_icon = img/16/shopping.png, title=Добавяне на офериран артикул към офертата");
    		$data->addOptionalBtn = ht::createBtn('Опционален артикул',  array($this, 'add', 'quotationId' => $data->masterId, 'optional' => 'yes', 'ret_url' => TRUE),  FALSE, FALSE, "{$error} ef_icon = img/16/shopping.png, title=Добавяне на опционален артикул към офертата");
    	}
    	
    	if(!$data->rows) return;
    	foreach($data->rows as $i => $row){
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
    	$templateFile = ($data->countNotOptional && $data->notOptionalHaveOneQuantity) ? 'sales/tpl/LayoutQuoteDetailsShort.shtml' : 'sales/tpl/LayoutQuoteDetails.shtml';
    	$dTpl = getTplFromFile($templateFile);
    	 
    	// Шаблон за опционалните продукти
    	$optionalTemplateFile = ($data->countOptional && $data->optionalHaveOneQuantity) ? 'sales/tpl/LayoutQuoteDetailsShort.shtml' : 'sales/tpl/LayoutQuoteDetails.shtml';
    	$oTpl = getTplFromFile($optionalTemplateFile);
    	$oTpl->removeBlock("totalPlace");
    	$oCount = $dCount = 1;
    	
    	// Променливи за определяне да се скриват ли някои колони
    	$hasQuantityColOpt = FALSE;
    	if($data->rows){
	    	foreach($data->rows as $index => $arr){
	    		list($pId, $optional) = explode("|", $index);
	    		foreach($arr as $key => $row){
	    			
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
    	if(count($data->discounts)){
    		$miscMandatory .= ', ' . tr('без извадени отстъпки');
    	}
    	
    	if(count($data->discountsOptional)){
    		$miscOptional .= ', ' . tr('без извадени отстъпки');
    	}
    	
    	// Ако сме чернова или има поне един задължителен артикул, рендираме таблицата му
    	if($masterRec->state == 'draft' || $dCount > 1){
    		$tpl->append($this->renderListToolbar($data), 'ListToolbar');
    		$dTpl->append(tr('Оферирани'), 'TITLE');
    		$dTpl->append($miscMandatory, "MISC");
    		if(isset($data->addNotOptionalBtn)){
    			$dTpl->append($data->addNotOptionalBtn, 'ADD_BTN');
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
    		$tpl->append($oTpl, 'OPTIONAL');
    	}
    	
    	if(!$hasQuantityColOpt){
    		$tpl->append(".quote-col-opt{$masterRec->id} {display:none;} .product-id-opt-product {width:65%;}", 'STYLES');
    	}
    	
    	// Закачане на JS
        $tpl->push('sales/js/ResizeQuoteTable.js', 'JS');
        jquery_Jquery::run($tpl, "resizeQuoteTable();");
        
    	return $tpl;
    }
    
    
    /**
     * Преди подготовка на полетата за показване в списъчния изглед
     */
    public static function on_AfterPrepareListRows($mvc, $data)
    {
    	if(!count($data->recs)) return;
    	 
    	$recs = &$data->recs;
    	$rows = &$data->rows;
    	$data->discountsOptional = $data->discounts = array();
    	
    	core_Lg::push($data->masterData->rec->tplLang);
    	
    	foreach ($rows as $id => &$row){
    		$rec = $recs[$id];
    		
    		if($rec->optional == 'no'){
    			$data->discounts[$rec->discount] = $row->discount;
    		} else {
    			$data->discountsOptional[$rec->discount] = $row->discount;
    		}

    		$row->productId = cat_Products::getAutoProductDesc($rec->productId, $data->masterData->rec->modifiedOn, $rec->showMode);
    		if($rec->notes){
    			deals_Helper::addNotesToProductRow($row->productId, $rec->notes);
    		}
    	}
    	
    	core_Lg::pop();
    }
    
    
    /**
     * Конвертира един запис в разбираем за човека вид
     * Входният параметър $rec е оригиналният запис от модела
     * резултата е вербалният еквивалент, получен до тук
     */
    public static function recToVerbal_($rec, &$fields = '*')
    {
    	$row = parent::recToVerbal_($rec, $fields);
    	 
    	$Double = cls::get('type_Double');
    	$Double->params['decimals'] = 2;
    	
    	// Показваме подробната информация за опаковката при нужда
    	deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
    	 
    	if($rec->amount){
    		$row->amount = $Double->toVerbal($rec->amount);
    	}
    	 
    	if($rec->discount){
    		$Percent = cls::get('type_Percent');
    		$parts = explode(".", $rec->discount * 100);
    		$Percent->params['decimals'] = count($parts[1]);
    		$row->discount = $Percent->toVerbal($rec->discount);
    	}
    	
    	return $row;
    }
    
    
    /**
     * След проверка на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec, $userId)
    {
    	if(($action == 'add' || $action == 'delete') && isset($rec)){
    		$quoteState = $mvc->Master->fetchField($rec->quotationId, 'state');
    		if($quoteState != 'draft'){
    			$res = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Ако ориджина е спецификация, вкарват се записи отговарящи
     * на посочените примерни количества в нея
     * @param stdClass $rec - запис на оферта
     * @param core_ObjectReference $origin - ид на спецификацията
     * @param array $dRows - количества И цени подадени във вида "к-во|цена"
     */
    public static function insertFromSpecification($rec, $origin, $dRows = array())
    {
    	$productRec = $origin->rec();
    	
    	// Изтриват се предишни записи на спецификацията в офертата
    	static::delete("#quotationId = {$rec->id} AND #productId = {$productRec->id}");
    	
    	foreach ($dRows as $row) {
    		if(empty($row)) continue;
    		
    		// Извличане на к-то и цената от формата
    		$row = type_ComplexType::getParts($row);
    		
    		// Записва се нов детайл за всяко зададено к-во
    		$dRec = new stdClass();
    		$dRec->quotationId = $rec->id;
    		$dRec->productId = $productRec->id;
    		$dRec->quantityInPack = 1;
    		$dRec->quantity = $row['left'];
    		$dRec->vatPercent = cat_Products::getVat($dRec->productId, $rec->date);
    		$dRec->packagingId = cat_Products::getProductInfo($dRec->productId)->productRec->measureId;
    		
    		if($tolerance = cat_Products::getParamValue($dRec->productId, 'tolerance')){
    			$dRec->tolerance = $tolerance;
    		}
    		
    		if($term = cat_Products::getParamValue($dRec->productId, 'term')){
    			$dRec->term = $term;
    		}
    		
    		// Ако полето от формата има дясна част, това е цената
    		if($row['right']){
    			
    			// Въведената цена се обръща в основна валута без ддс
    			$dRec->price = $row['right'];
    			$dRec->price = static::getBasePrice($dRec->price, $rec->currencyRate, $dRec->vatPercent, $rec->chargeVat);
    		} else {
    			
    			// Ако няма извлича се цената от спецификацията
    			$price = cat_Products::getPriceInfo($rec->contragentClassId, $rec->contragentId, $dRec->productId, cat_Products::getClassId(), NULL, $dRec->quantity, $rec->date)->price;
    			$dRec->price = deals_Helper::getPurePrice($price, $dRec->vatPercent, $rec->currencyRate, $rec->chargeVat);
    		}
    		
    		$dRec->optional = 'no';
    		$dRec->discount = $price->discount;
    		
    		static::save($dRec);
    	}
    }
    
    
   /**
    * Помощна ф-я обръщаща въведената цена в основна валута без ддс
    */
    private static function getBasePrice($price, $currencyRate, $vatPercent, $chargeVat)
    {
    	if($chargeVat == 'yes'){
			$price = $price / (1 + $vatPercent);
    	}
    	
    	return $price * $currencyRate;
    }
}
