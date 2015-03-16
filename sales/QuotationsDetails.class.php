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
    public $loadList = 'plg_RowTools, sales_Wrapper, plg_AlignDecimals, doc_plg_HidePrices, plg_SaveAndNew';
    
    
    /**
     * Кой може да променя?
     */
    public $canList = 'no_one';
    
	
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, quantity, price, discount, tolerance, term, optional, amount, discAmount';
    
    
    /**
     * Кой таб да бъде отворен
     */
    public $currentTab = 'Оферти';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'price,discount,amount,discAmount';
    
    
    /**
     * Помощен масив (@see deals_Helper)
     */
    protected static $map = array('priceFld'      => 'price', 
    							  'quantityFld'   => 'quantity', 
    							  'valior'        => 'date', 
    							  'discAmountFld' => 'discAmountVat');
  	
  	
  	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('quotationId', 'key(mvc=sales_Quotations)', 'column=none,notNull,silent,hidden,mandatory');
    	$this->FLD('productId', 'int', 'caption=Продукт,notNull,mandatory');
        $this->FLD('classId', 'class(interface=cat_ProductAccRegIntf, select=title)', 'input=hidden,caption=Политика,silent,oldFieldName=productManId');
    	$this->FLD('quantity', 'double(Min=0)', 'caption=К-во');
    	$this->FLD('price', 'double(minDecimals=2,maxDecimals=4)', 'caption=Ед. цена, input');
        $this->FLD('discount', 'percent(maxDecimals=2)', 'caption=Отстъпка');
        $this->FLD('tolerance', 'percent(min=0,max=1,decimals=0)', 'caption=Толеранс');
    	$this->FLD('term', 'time(uom=days,suggestions=1 ден|5 дни|7 дни|10 дни|15 дни|20 дни|30 дни)', 'caption=Срок');
    	$this->FLD('vatPercent', 'percent(min=0,max=1,decimals=2)', 'caption=ДДС,input=none');
        $this->FLD('optional', 'enum(no=Не,yes=Да)', 'caption=Опционален,maxRadio=2,columns=2,input=hidden,silent');
        $this->FLD('showMode', 'enum(auto=Автоматично,detailed=Разширено,short=Кратко)', 'caption=Показване,notNull,default=auto');
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
    	
    	if(count($recs)){
	    	foreach ($recs as $id => $rec){
	    		if($rec->optional == 'no'){
	    			$notOptional[$id] = $rec;
	    		}  else {
	    			$optional[$id] = $rec;
	    		}
	    	}
    	}
    	
    	// Подготовка за показване на задължителнтие продукти
    	deals_Helper::fillRecs($mvc, $notOptional, $masterRec, static::$map);
    	
    	if(empty($data->noTotal)){
    		
    		// Запомня се стойноста и ддс-то само на опционалните продукти
    		$data->summary = deals_Helper::prepareSummary($mvc->_total, $masterRec->date, $masterRec->currencyRate, $masterRec->currencyId, $masterRec->chargeVat);
    	}
    	
    	// Подготовка за показване на опционалните продукти
    	deals_Helper::fillRecs($mvc, $optional, $masterRec, static::$map);
    	$recs = $notOptional + $optional;
    	
    	// Изчисляване на цената с отстъпка
    	foreach($recs as $id => $rec){
            if($rec->optional == 'no'){
    			$other = $mvc->checkUnique($recs, $rec->productId, $rec->classId, $rec->id);
            	if($other) unset($data->summary);
    		}
    	}
    }
    
    
    /**
     * Проверява дали има вариация на продукт
     */
    private function checkUnique($recs, $productId, $classId, $id, $isOptional = 'no')
    {
    	$other = array_values(array_filter($recs, function ($val) use ($productId, $classId, $id, $isOptional) {
           				if($val->optional == $isOptional && $val->productId == $productId && $val->classId == $classId && $val->id != $id){
            				return $val;
            			}}));
            			
        return count($other);
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
        $rec = &$form->rec;
        $masterRec = $data->masterRec;
        $productMan = cls::get($rec->classId);
        
        if($rec->productId){
        	
        	// При редакция единствения възможен продукт е редактируемия
	   		$productName = $productMan->getTitleById($rec->productId);
	   		$products = array();
	   		$products[$rec->productId] = $productName;
	    } else {
	    	
	    	// Кои са продаваемите продукти
	    	$products = $productMan->getProducts($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->date, 'canSell');
	    	
	    	// Подсигуряваме се че ориджина винаги може да се добави
	    	if($masterRec->originId){
	    		$origin = doc_Containers::getDocument($masterRec->originId);
	    		$products[$origin->that] = $origin->getTitleById(FALSE);
	    	}
	    	
	    	$products = array('' => '') + $products;
	    }
	   
        $form->setDefault('optional', 'no');
	    $form->setOptions('productId', $products);
       
	    $form->fields['price']->unit = "|*" . $masterRec->currencyId . ", " .(($masterRec->chargeVat == 'yes') ? '|с ДДС|*' : '|без ДДС|*');
	   
	    if($form->rec->price && $masterRec->currencyRate){
       	 	if($masterRec->chargeVat == 'yes'){
       	 		($rec->vatPercent) ? $vat = $rec->vatPercent : $vat = $productMan::getVat($rec->productId, $masterRec->date);
       	 		 $rec->price = $rec->price * (1 + $vat);
       	 	}
       	 	
       		$rec->price = $rec->price / $masterRec->currencyRate;
        }
        
        $form->setDefault('discount', $mvc->fetchField("#quotationId = {$masterRec->id} AND #discount IS NOT NULL", 'discount'));
    
    	/*if(isset($rec->productId)){
    		$params = $productMan->getParams($rec->productId);
    	}*/
    }
    
    
	/**
     * Извиква се след въвеждането на данните от Request във формата
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
	    	$rec = &$form->rec;
	    	
	    	if($sameProduct = $mvc->fetch("#quotationId = {$rec->quotationId} AND #classId = {$rec->classId} AND #productId = {$rec->productId}")){
	    		if($rec->optional == 'yes' && $sameProduct->optional == 'no' && $rec->id != $sameProduct->id){
	    			$form->setError('optional', "Не може да добавите продукта като опционален, защото фигурира вече като задължителен!");
	    			
	    			return;
	    		} elseif($rec->optional == 'no' && $sameProduct->optional == 'yes' && $rec->id != $sameProduct->id){
	    			$form->setError('optional', "Не може да добавите продукта като задължителен, защото фигурира вече като опционален!");
	    			
	    			return;
	    		}
	    	}
	    	
    		if($rec->optional == 'no' && !$rec->quantity){
	    		$form->setError('quantity', 'Задължителния продукт не може да е без количество!');
	    		
	    		return;
    		}
	    	
    		if($sameProduct = $mvc->fetch("#quotationId = {$rec->quotationId} AND #classId = {$rec->classId} AND #productId = {$rec->productId}  AND #quantity='{$rec->quantity}'")){
    			if($sameProduct->id != $rec->id){
    				$form->setError('quantity', 'Избрания продукт вече фигурира с това количество');
    			}
    		}
    		
	    	$ProductMan = cls::get($rec->classId);
	    	if(!$rec->vatPercent){ 
	    		$rec->vatPercent = $ProductMan::getVat($rec->productId, $masterRec->date);
	    	}
	    	
	    	$masterRec = $mvc->Master->fetch($rec->quotationId);
	    	
    		if(!$rec->discount){
    			$rec->discount = $price->discount;
	    	}
	    	
	    	if(!$rec->price){
	    		$Policy = $ProductMan->getPolicy();
	    		$price = $Policy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->classId, NULL, $rec->quantity, $masterRec->date, $masterRec->currencyRate, $masterRec->chargeVat);
	    		
	    		if(!$price->price){
	    			$form->setError('price', 'Проблем с изчислението на цената ! Моля задайте ръчно');
	    		}
	    		
	    		$rec->price = $price->price;
	    		
	    		if($price->discount){
	    			$rec->discount = abs($price->discount);
	    		}
	    	}
	    	
	    	// Обръщаме в основна валута без ддс
	    	$vat = cls::get($rec->classId)->getVat($rec->productId);
	    	$rec->price = deals_Helper::getPurePrice($rec->price, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
	    }
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
    		$productMan = cls::get('cat_Products');
    		$products = $productMan->getProducts($data->masterData->rec->contragentClassId, $data->masterData->rec->contragentId, $data->masterData->rec->date, 'canSell');
    		if(!count($products)){
    			$error = "error=Няма продаваеми артикули";
    		}
    	
    		$data->addNotOptionalBtn = ht::createBtn('Артикул',  array($this, 'add', 'quotationId' => $data->masterId, 'classId' => $productMan->getClassId(), 'optional' => 'no'), FALSE, FALSE, "{$error},ef_icon = img/16/shopping.png, title=Добавяне на артикул към офертата");
    		$data->addOptionalBtn = ht::createBtn('Артикул',  array($this, 'add', 'quotationId' => $data->masterId, 'classId' => $productMan->getClassId(), 'optional' => 'yes'),  FALSE, FALSE, "{$error},ef_icon = img/16/shopping.png, title=Добавяне на артикул към офертата");
    	}
    	
    	if(!$data->rows) return;
    	foreach($data->rows as $i => $row){
    		$pId = $data->recs[$i]->productId;
    		$polId = $data->recs[$i]->classId;
    		$optional = $data->recs[$i]->optional;
    		($optional == 'no') ? $zebra = &$dZebra : $zebra = &$oZebra;
    		
    		// Сездава се специален индекс на записа productId|optional, така
    		// резултатите са разделени по продукти и дали са опционални или не
    		$pId = $pId . "|{$optional}|" . $polId;
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
    	
    	// Шаблон за задължителните продукти
    	$dTpl = getTplFromFile('sales/tpl/LayoutQuoteDetails.shtml');
    	
    	// Шаблон за опционалните продукти
    	$oTpl = clone $dTpl;
    	$oTpl->removeBlock("totalPlace");
    	$oCount = $dCount = 1;
    	
    	// Променливи за определяне да се скриват ли някои колони
    	$hasQuantityColOpt = FALSE;
    	if($data->rows){
	    	foreach($data->rows as $index => $arr){
	    		list($pId, $optional, $polId) = explode("|", $index);
	    		foreach($arr as $key => $row){
	    			
	    			// Взависимост дали е опционален продукта го добавяме към определения шаблон
	    			if($optional == 'no'){
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
    	} else {
    		$dTpl->replace('<tr><td colspan="5">' . tr("Няма записи") . '</td></tr>', 'ROWS');
    	}

    	if($summary = $data->summary){
    		$dTpl->placeObject($summary, 'SUMMARY');
    		$dTpl->replace($summary->sayWords, 'sayWords');
    	} else {
    		$dTpl->removeBlock("totalPlace");
    	}
    	
    	$vatRow = ($masterRec->chargeVat == 'yes') ? tr(', |с ДДС|*') : tr(', |без ДДС|*');
    	$misc = $masterRec->currencyId . $vatRow;
    	
    	// Ако сме чернова или има поне един задължителен артикул, рендираме таблицата му
    	if($masterRec->state == 'draft' || $dCount > 1){
    		$tpl->append($this->renderListToolbar($data), 'ListToolbar');
    		$dTpl->append(tr('Оферирани'), 'TITLE');
    		$dTpl->append($misc, "MISC");
    		if(isset($data->addNotOptionalBtn)){
    			$dTpl->append($data->addNotOptionalBtn, 'ADD_BTN');
    		}
    		
    		$dTpl->removeBlocks();
    		$tpl->append($dTpl, 'MANDATORY');
    	}
    	
    	// Ако сме чернова и има поне един опционален артикул, рендираме таблицата за артикули
    	if($masterRec->state == 'draft' || $oCount > 1){
    		$oTpl->append(tr('Опционални'), 'TITLE');
    		$oTpl->append($misc, "MISC");
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
    	 
    	foreach ($rows as $id => &$row){
    		$rec = $recs[$id];
    		
    		$row->productId = cat_Products::getAutoProductDesc($rec->productId, $data->masterData->rec->modifiedOn, $rec->showMode);
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$ProductMan = cls::get($rec->classId);
        $pInfo = $ProductMan->getProductInfo($rec->productId);
    	
        $double = cls::get('type_Double');
        $double->params['decimals'] = 2;
    	
    	if($rec->quantity){
    		$uomId = $pInfo->productRec->measureId;
    		$row->uomShort = cat_UoM::getShortName($uomId);
    	}
    	
    	if($rec->amount){
    		$row->amount = $double->toVerbal($rec->amount);
    	}
    	
    	if($rec->discount){
    		$Percent = cls::get('type_Percent');
		    $parts = explode(".", $rec->discount * 100);
		    $percent->params['decimals'] = count($parts[1]);
		    $row->discount = $Percent->toVerbal($rec->discount);
    	}
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
    public function insertFromSpecification($rec, $origin, $dRows = array())
    {
    	$ProductMan = $origin->getInstance();
    	$productRec = $origin->rec();
    	
    	// Изтриват се предишни записи на спецификацията в офертата
    	$this->delete("#quotationId = {$rec->id} AND #productId = {$productRec->id} AND #classId = {$ProductMan->getClassId()}");
    	
    	foreach ($dRows as $row) {
    		if(empty($row)) continue;
    		
    		// Извличане на к-то и цената от формата
    		$row = type_ComplexType::getParts($row);
    		
    		// Записва се нов детайл за всяко зададено к-во
    		$dRec = new stdClass();
    		$dRec->quotationId = $rec->id;
    		$dRec->productId = $productRec->id;
    		$dRec->quantity = $row['left'];
    		$dRec->classId = $ProductMan->getClassId();
    		$dRec->vatPercent = $ProductMan->getVat($dRec->productId, $rec->date);
    		
    		// Ако полето от формата има дясна част, това е цената
    		if($row['right']){
    			
    			// Въведената цена се обръща в основна валута без ддс
    			$dRec->price = $row['right'];
    			$dRec->price = static::getBasePrice($dRec->price, $rec->currencyRate, $dRec->vatPercent, $rec->chargeVat);
    		} else {
    			
    			// Ако няма извлича се цената от спецификацията
    			$price = $ProductMan->getPriceInfo($rec->contragentClassId, $rec->contragentId, $dRec->productId, $dRec->classId, NULL, $dRec->quantity, $rec->date)->price;
    			$dRec->price = deals_Helper::getPurePrice($price, $dRec->vatPercent, $rec->currencyRate, $rec->chargeVat);
    		}
    		
    		$dRec->optional = 'no';
    		$dRec->discount = $price->discount;
    		
    		$this->save($dRec);
    	}
    }
    
    
	/**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave($mvc, &$id, $rec)
    {
    	// Нотифицираме продуктовия мениджър че продукта вече е използван
    	$ProductMan = cls::get($rec->classId);
    	$productRec = $ProductMan->fetch($rec->productId);
    	$productRec->lastUsedOn = dt::now();
    	$ProductMan->save_($productRec);
    }
    
    
   /**
    * Помощна ф-я обръщаща въведената цена в основна валута без ддс
    */
    private function getBasePrice($price, $currencyRate, $vatPercent, $chargeVat)
    {
    	if($chargeVat == 'yes'){
			$price = $price / (1 + $vatPercent);
    	}
    	
    	return $price * $currencyRate;
    }
}
