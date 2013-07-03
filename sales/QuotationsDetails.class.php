<?php



/**
 * Мениджър за "Детайли на офертите" 
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class sales_QuotationsDetails extends core_Detail {
    
    
    /**
     * Заглавие
     */
    var $title = 'Детайли на офертите';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'sales_QuotesDetails';
    
    
    /**
	 * Мастър ключ към дъските
	 */
	var $masterKey = 'quotationId';
    
    
    /**
     * Кой може да променя?
     */
    var $canAdd = 'ceo,sales';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, sales_Wrapper, plg_AlignDecimals';
    
    
    /**
     * Кой може да променя?
     */
    var $canList = 'no_one';
    
	
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, quantity, price, discount, tolerance, term, optional, amount, discAmount';
    
    
  	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('quotationId', 'key(mvc=sales_Quotations)', 'column=none,notNull,silent,hidden,mandatory');
    	$this->FLD('productId', 'int', 'caption=Продукт,notNull,mandatory');
        $this->FLD('policyId', 'class(interface=price_PolicyIntf, select=title)', 'input=hidden,caption=Политика, silent');
    	$this->FLD('quantity', 'double', 'caption=К-во,width=8em;');
    	$this->FLD('price', 'double(decimals=2)', 'caption=Ед. цена, input,width=8em;');
        $this->FLD('discount', 'percent(decimals=2,min=0)', 'caption=Отстъпка,width=8em;');
        $this->FLD('tolerance', 'percent(min=0,max=1,decimals=0)', 'caption=Толеранс,width=8em;');
    	$this->FLD('term', 'time(uom=days)', 'caption=Срок,width=8em;');
    	$this->FLD('vatPercent', 'percent(min=0,max=1,decimals=2)', 'caption=ДДС,input=none');
        $this->FLD('optional', 'enum(no=Не,yes=Да)', 'caption=Опционален,value=no');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    static function on_AfterPrepareListRecs($mvc, $data)
    {
    	$recs = &$data->recs;
    	$rows = &$data->rows;
    	$masterRec = $data->masterData->rec;
    	($masterRec->vat == 'yes') ? $applyVat = TRUE : $applyVat = FALSE;
    	
    	if($recs){
	    	foreach($recs as $id => $rec){
	    		
	    		// Цената с добавено ДДС и конвертирана
	    		if(!$applyVat) {
	    			$rec->vatPercent = 0;
	    		}
	    		
		    	$price = $rec->price + ($rec->price * $rec->vatPercent);
		    	$price = round($price / $masterRec->rate, 2);
	    		$rec->vatPrice = $price;
		    	
		    	// Сумата с добавено ддс и конвертирана
	    		if($rec->quantity){
	    			$rec->amount = $rec->quantity * $price;
			    }
	    		
	    		// Отстъпката с добавено ДДС и конвертирана
		    	if($rec->discount && $rec->quantity){
		    		$disc = round(($rec->amount * $rec->discount), 2);
    				$rec->discAmountVat = $rec->amount - $disc;
		    	}
	    	}
    	}
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
       $form = &$data->form;
       $rec = &$form->rec;
       
       $title = (Request::get('edit')) ? "Редактиране" : "Добавяне";
       $masterLink = sales_Quotations::getLink($form->rec->quotationId);
       $form->title = $title . " " . "на артикул в" . " |*" . $masterLink;
       
       $masterRec = $mvc->Master->fetch($form->rec->quotationId);
       $Policy = cls::get($rec->policyId);
       $productMan = $Policy->getProductMan();
       $products = $Policy->getProducts($masterRec->contragentClassId, $masterRec->contragentId);
       
       // Ако офертата е базирана на спецификация, то тя може да
       // се добавя редактира в нея дори ако е чернова
       if(isset($masterRec->originId)){
	       	$origin = doc_Containers::getDocument($masterRec->originId);
	    	if($origin->className == 'techno_Specifications'){
	    		$products[$origin->that] = $origin->recToVerbal('title')->title;
	    	}
       	} elseif(!count($products)) {
       		return Redirect(array($mvc->Master, 'single', $rec->quotationId), NULL, 'Няма достъпни продукти');
       }
       
       $form->setOptions('productId', $products);
       
       if($form->rec->price && $masterRec->rate){
       	 	$price = round($rec->price / $masterRec->rate, 2);
       	 	($rec->vatPercent) ? $vat = $rec->vatPercent : $vat = $productMan::getVat($rec->productId, $masterRec->date);
       	 	
       		$rec->price = $price + ($price * $vat);
       }
    }
    
    
	/**
     * Извиква се след въвеждането на данните от Request във формата
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
	    	$rec = &$form->rec;
	    	$Policy = cls::get($rec->policyId);
	    	$productMan = $Policy->getProductMan();
	    	if(!$rec->vatPercent){
	    		$rec->vatPercent = $productMan::getVat($rec->productId, $masterRec->date);
	    	}
	    	
	    	$masterRec = $mvc->Master->fetch($rec->quotationId);
	    	
    		if(!$rec->discount){
    			$rec->discount = $price->discount;
	    	}
	    		
	    	if(!$rec->price){
	    		$price = $Policy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, NULL, $rec->quantity, $masterRec->date);
	    		
	    		if(!$price){
	    			$form->setError('price', 'Проблем с изчислението на цената ! Моля задайте ръчно');
	    			$form->setField('price', 'mandatory');
	    		}
	    		
	    		// Конвертираме цената към посочената валута в офертата
	    		$rec->price = $price->price;
	    	} else {
       			$rec->price = $rec->price / (1 + $rec->vatPercent);
	    		$rec->price = round($rec->price * $masterRec->rate, 2);
	    	}
    	}
    }
    
    
	/**
     * Подготовка на бутоните за добавяне на нови редове на фактурата 
     */
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
    	if (!empty($data->toolbar->buttons['btnAdd'])) {
            $pricePolicies = core_Classes::getOptionsByInterface('price_PolicyIntf');
           
            $addUrl = $data->toolbar->buttons['btnAdd']->url;
            foreach ($pricePolicies as $policyId=>$Policy) {
                $Policy = cls::getInterface('price_PolicyIntf', $Policy);
                
                $data->toolbar->addBtn($Policy->getPolicyTitle($data->masterData->rec->contragentClassId, $data->masterData->rec->contragentId), $addUrl + array('policyId' => $policyId,),
                    "id=btnAdd-{$policyId}", 'ef_icon = img/16/shopping.png');
            }
            
            unset($data->toolbar->buttons['btnAdd']);
        }
    }
    
    
    /**
     * Сортираме резултатите по продукти
     */
	static function on_AfterPrepareDetailQuery(core_Detail $mvc, $data)
    {
        // Историята на ценовите групи на продукта - в обратно хронологичен ред.
        $data->query->orderBy("id,productId", 'ASC');
    }
    
    
    /**
     * Подготовка на детайлите
     */
    function prepareDetail_($data)
    {
    	// Подготвяме записите
    	parent::prepareDetail_($data);
    	
    	// Изчисляваме общата сума (ако е възможно)
    	$this->calcTotal($data);
    	
    	// Групираме резултатите по продукти и дали са опционални или не
    	$this->groupResultData($data);
    }
    
    
    /**
     * Групираме резултатите спрямо продукта
     * @var stdClass $data
     */
    private function groupResultData(&$data)
    {
    	$newRows = array();
    	if(!$data->rows) return;
    	foreach($data->rows as $i => $row){
    		$pId = $data->recs[$i]->productId;
    		$optional = $data->recs[$i]->optional;
    		
    		// Сездава се специален индекс на записа productId|optional, така
    		// резултатите са разделени по продукти и дали са опционални или не
    		$pId = $pId . "|{$optional}";
    		if(array_key_exists($pId, $newRows)){
    			
    			// Ако има вече такъв продукт, го махаме от записа
    			unset($row->productId);
    		}
    		
    		$newRows[$pId][] = $row;
    	}
    	
    	// Така имаме масив в който резултатите са групирани 
    	// по продукти, и това дали са опционални или не,
    	$data->rows = $newRows;
    }
    
    
    /**
     * Изчисляване на общата сума на всички задължителни продукти
     * Ако не е известна цялата сума, показваме "???"
     * @var stdClass $data
     */
    private function calcTotal($data)
    {
    	if(!$data->rows) return;
    	$total = $totalDisc = 0;
    	$resArr = array_values($data->recs);
    	foreach($resArr as $i => $rec){
    		if($rec->optional == 'no'){
    			if($i != 0){
    				$prevRec = $resArr[$i-1];
    				if($rec->productId == $prevRec->productId && $rec->policyId == $prevRec->policyId) return;
    			}
    			
    			// Ако няма количество, цената неможе да се изчисли
    			if(!$rec->quantity) return;
    			$total += $rec->amount;
    			if($rec->discAmountVat){
    				$totalDisc += $rec->amount - $rec->discAmountVat; 
    			}
    		}
    	}
    	
    	if($totalDisc != 0){
    		$afterDisc = $total - $totalDisc;
    	}
    	$double = cls::get('type_Double');
    	$double->params['decimals'] = 2;
    	$total = $double->toVerbal($total);
    	$data->total = (object) array('total' => $total, 'totalDisc' => $afterDisc);
    }
    
    
    /**
     * Променяме рендирането на детайлите
     */
    function renderDetail_($data)
    {
    	$tpl = new ET("");
    	
    	// Шаблон за задължителните продукти
    	$dTpl = getTplFromFile('sales/tpl/LayoutQuoteDetails.shtml');
    	
    	// Шаблон за опционалните продукти
    	$oTpl = clone $dTpl;
    	$oCount = $dCount = 1;
    	
    	// Променливи за определяне да се скриват ли някои колони
    	$hasQuantityCol = $hasQuantityColOpt = FALSE;
    	if($data->rows){
	    	foreach($data->rows as $index => $arr){
	    		list(, $optional) = explode("|", $index);
	    		foreach($arr as $key => $row){
	    			if($key == 0){
	    				
	    				// Задаваме rowspan на полето за продукта, взависимост от данните
	    				$row->rowspan = count($arr);
	    			}
	    			
	    			// Взависимост дали е опционален продукта 
	    			// го добавяме към определения шаблон
	    			if($optional == 'no'){
	    				$rowTpl = $dTpl->getBlock('ROW');
	    				$id = &$dCount;
	    				$colQ = &$hasQuantityCol;
	    			} else {
	    				$rowTpl = $oTpl->getBlock('ROW');
	    				
	    				// слага се 'opt' в класа на колоната да се отличава
	    				$rowTpl->replace('-opt', 'OPT');
	    				$oTpl->replace('-opt', 'OPT');
	    				$id = &$oCount;
		    			$colQ = &$hasQuantityColOpt;
	    			} 
	    			
	    			if($colQ !== TRUE && ($row->quantity)){
	    				$colQ = TRUE;
	    			}
	    			
	    			$row->index = $id++;
	    			$rowTpl->placeObject($row);
	    			$rowTpl->removeBlocks();
	    			$rowTpl->append2master();
	    		}
	    	}
    	}
    	
    	if($data->total){
    		if($data->total->totalDisc){
    			$data->total->totalClass = 'oldAmount';
    		}
    		$dTpl->placeObject($data->total);
    	}
    	
    	$tpl->append($this->renderListToolbar($data), 'ListToolbar');
    	$dTpl->removeBlocks();
    	$tpl->append($dTpl, 'MANDATORY');
    	
    	// Ако няма опционални продукти не рендираме таблицата им
    	if($oCount > 1){
    		$tpl->append($oTpl, 'OPTIONAL');
    	}
    	
    	if(!$hasQuantityCol){
    		$tpl->append(".quote-col {display:none;}", 'STYLES');
    	}
    	
    	if(!$hasQuantityColOpt){
    		$tpl->append(".quote-col-opt {display:none;}", 'STYLES');
    	}
    	
    	return $tpl;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$Policy = cls::get($rec->policyId);
        $productMan = $Policy->getProductMan();
        $pInfo = $productMan->getProductInfo($rec->productId);
    	
        $double = cls::get('type_Double');
        $double->params['decimals'] = 2;
    	$row->productId = $productMan->getTitleById($rec->productId, TRUE, TRUE);
    	if($rec->quantity){
    		$uomId = $pInfo->productRec->measureId;
    		$row->uomShort = cat_UoM::getShortName($uomId);
    	}
    	
    	$row->price = $double->toVerbal($rec->vatPrice);
    	if($rec->amount){
    		$row->amount = $double->toVerbal($rec->amount);
    	}
    	
    	if($rec->discount){
    		$row->price = "<span class='oldAmount' style='text-decoration:none'>{$row->price}</span>";
    		$row->discount = "<span class='newAmount'>{$row->discount}</span>";
    	} else {
    		$row->price = "<b>{$row->price}</b>";
    	}
    	
    	$row->discAmount = $double->toVerbal($rec->discAmountVat);
    	if($rec->discAmountVat){
    		$row->amount = "<span class='oldAmount'>{$row->amount}</span>";
    		$row->discAmount = "<span class='newAmount'>{$row->discAmount}</span>";
    	} else {
    		$row->amount = "<b>{$row->amount}</b>";
    	}
    }
    
    
    /**
     * След проверка на ролите
     */
    function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec, $userId)
    {
    	if($action == 'add' && isset($rec)){
    		$quoteState = $mvc->Master->fetchField($rec->quotationId, 'state');
    		if($quoteState != 'draft'){
    			$res = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Екшън за обновяване на данните в оферта от спецификация
     */
    function act_updateData()
    {
    	$this->Master->requireRightFor('edit');
    	$quantities = array();
    	$quantities[] = Request::get('quantity1', 'double');
    	$quantities[] = Request::get('quantity2', 'double');
    	$quantities[] = Request::get('quantity3', 'double');
    	expect($quantities[0] || $quantities[1] || $quantities[2]);
    	expect($quotationId = Request::get('quotationId'));
    	expect($rec = $this->Master->fetch($quotationId));
    	expect($sId = Request::get('specId', 'int'));
    	expect(techno_Specifications::fetch($sId));
    	
    	$this->insertFromSpecification($rec, $sId, $quantities);
    	return Redirect(array($this->Master, 'single', $quotationId));
    }
    
    
    /**
     * Ако ориджина е спецификация вкарват се записи отговарящи
     * на посочените примерни количества в нея
     * @param stdClass $rec - запис на оферта
     * @param int $sId - ид на спецификацията
     * @param array $quantities - количества подадени от заявката
     */
    public function insertFromSpecification($rec, $sId, $quantities = array())
    {
    	$policyId = techno_Specifications::getClassId();
    	$Policy = cls::get($policyId);
    	
    	// Изтриват се предишни записи на спецификацията в офертата
    	$this->delete("#quotationId = $rec->id AND #productId = {$sId} AND #policyId = {$policyId}");
    	
    	foreach($quantities as $q) {
    		if(empty($q)) continue;
    		
    		// Записва се нов детайл за всяко зададено к-во
    		$dRec = new stdClass();
    		$dRec->quotationId = $rec->id;
    		$dRec->productId = $sId;
    		$dRec->quantity = $q;
    		$dRec->policyId = $policyId;
    		$price = $Policy->getPriceInfo($rec->contragentClassId, $rec->contragentId, $dRec->productId, NULL, $q, $rec->date);
    		
    		$dRec->price = $price->price;
    		$dRec->discount = $price->discount;
    		$dRec->vatPercent = $Policy->getVat($dRec->productId, $rec->date);
    		
    		$this->save($dRec);
    	}
    }
    
    
	/**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave($mvc, &$id, $rec)
    {
    	// Нотифицираме продуктовия мениджър че продукта вече е използван
    	$productMan = cls::get($rec->policyId)->getProductMan();
    	$productRec = $productMan::fetch($rec->productId);
    	$productRec->lastUsedOn = dt::now();
    	$productMan::save($productRec);
    }
}