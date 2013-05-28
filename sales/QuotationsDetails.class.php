<?php



/**
 * Мениджър за "Оферти за продажба" 
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
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
    var $canAdd = 'admin,sales';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools';
    
    
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
        $this->FLD('discount', 'percent(decimals=0)', 'caption=Отстъпка,width=8em;');
        $this->FLD('tolerance', 'percent(min=0,max=1,decimals=0)', 'caption=Толеранс,width=8em;');
    	$this->FLD('term', 'int', 'caption=Срок,unit=дни,width=8em;');
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
    	$double = cls::get('type_Double');
    	$double->params['decimals'] = 2;
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
       (Request::get('edit')) ? $title = tr("Редактиране") : $title = tr("Добавяне");
       $form->title = $title . " " . tr("|на запис в Оферта|* №{$rec->quotationId}");
       
       $masterRec = $mvc->Master->fetch($form->rec->quotationId);
       $Policy = cls::get($rec->policyId);
       $productMan = $Policy->getProductMan();
       $products = $Policy->getProducts($masterRec->contragentClassId, $masterRec->contragentId);
       
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
	    	
	    	if(!$rec->price){
	    		$price = $Policy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, NULL, 1, $masterRec->date);
	    		
	    		if(!$price){
	    			$form->setError('price', 'Неможе да се изчисли цената за този клиент');
	    		}
	    		
	    		// Конвертираме цената към посочената валута в офертата
	    		$rec->price = $price->price;
	    		
	    		if(!$rec->discount){
	    			$rec->discount = $price->discount;
	    		}
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
                    "id=btnAdd-{$policyId},class=btn-shop");
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
        $data->query->orderBy("productId", 'ASC');
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
    				$totalDisc += $rec->discAmountVat; 
    			}
    		}
    	}
    	
    	$double = cls::get('type_Double');
    	$double->params['decimals'] = 2;
    	(!$totalDisc) ? $totalDisc = NULL : $totalDisc = $double->toVerbal($total-$totalDisc);
    	$total = $double->toVerbal($total);
    	$data->total = (object) array('total' => $total, 'totalDisc' => $totalDisc);
    }
    
    
    /**
     * Променяме рендирането на детайлите
     */
    function renderDetail_($data)
    {
    	$tpl = new ET("");
    	
    	// Шаблон за задължителните продукти
    	$dTpl = new ET(tr("|*" . getFileContent('sales/tpl/LayoutQuoteDetails.shtml')));
    	
    	if(!Mode::is('printing') && $data->masterData->rec->state == 'draft'){
    		
    		// Маха се th-то на полето за редакция, ако се принтира
    		$dTpl->replace(' ', 'toolsTh');
    	}
    	
    	// Шаблон за опционалните продукти
    	$oTpl = clone $dTpl;
    	$oCount = $dCount = 1;
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
	    			} else {
	    				$rowTpl = $oTpl->getBlock('ROW');
	    				$id = &$oCount;
	    			} 
	    			
	    			$row->index = $id++;
	    			$rowTpl->placeObject($row);
	    			$rowTpl->removeBlocks();
	    			$rowTpl->append2master();
	    		}
	    	}
    	}
    	if($data->total){
    		$dTpl->placeObject($data->total);
    	}
    	
    	$tpl->append($this->renderListToolbar($data), 'ListToolbar');
    	$dTpl->removeBlocks();
    	$tpl->append($dTpl, 'MANDATORY');
    	
    	// Ако няма опционални продукти не рендираме таблицата им
    	if($oCount > 1){
    		$tpl->append($oTpl, 'OPTIONAL');
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
    	
        $double = cls::get('type_Double');
    	if(!$rec->quantity){
    		$row->quantity = '???';
    	} else {
    		$quantity = floatval($rec->quantity);
    		$parts = explode('.', $quantity);
    		$double->params['decimals'] = count($parts[1]);
    		$row->quantity = $double->toVerbal($rec->quantity);
    	}
    	
    	$row->productId = $productMan->getTitleById($rec->productId);
    	$uomId = $productMan::fetchField($rec->productId, 'measureId');
    	$uomTitle = cat_UoM::recToVerbal($uomId, 'shortName')->shortName;
    	
    	$row->quantity = "<b>{$row->quantity}</b> {$uomTitle}";
    	
    	if($rec->discount && $rec->discount < 0){
    		$row->discount = abs($rec->discount);
    		$row->discount = "+ {$row->discount}%";
    	} elseif($rec->discount && $rec->discount > 0){
    		$row->discount = "- {$row->discount}";
    	}
    	
    	$double->params['decimals'] = 2;
    	$row->price = $double->toVerbal($rec->vatPrice);
    	if($rec->amount){
    		$row->amount = $double->toVerbal($rec->amount);
    	} else {
    		$row->amount = '???';
    	}
    	
    	$row->discAmount = $double->toVerbal($rec->discAmountVat);
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
     * Ако ориджина е спецификация вкарват се записи отговарящи
     * на посочените примерни коли1ества в нея
     * @param stdClass $rec - запис на оферта
     * @param core_ObjectReference $origin - пораждащия документ
     * (спецификация)
     */
    public function insertFromSpecification($rec, core_ObjectReference $origin)
    {
    	$originRec = $origin->fetch();
    	$quantities = array($originRec->quantity1, $originRec->quantity2, $originRec->quantity3);
    	$policyId = techno_Specifications::getClassId();
    	$Policy = cls::get($policyId);
    	foreach ($quantities as $q){
    		if(empty($q)) continue;
    		$dRec = new stdClass();
    		$dRec->quotationId = $rec->id;
    		$dRec->productId = $origin->that;
    		$dRec->quantity = $q;
    		$dRec->policyId = $policyId;
    		$price = $Policy->getPriceInfo($rec->contragentClassId, $rec->contragentId, $dRec->productId, NULL, $q, $rec->date);
    		$dRec->price = $price->price;
    		$dRec->discount = $price->discount;
    		$dRec->vatPercent = $Policy->getVat($dRec->productId, $rec->date);
    		$this->save($dRec);
    	}
    }
}