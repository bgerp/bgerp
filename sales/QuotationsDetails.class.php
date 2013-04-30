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
    public $listFields = 'productId, quantity, price, discount, tolerance, term, optional, amount, discAmount, tools=Пулт';
    
    
  	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('quotationId', 'key(mvc=sales_Quotations)', 'column=none,notNull,silent,hidden,mandatory');
    	$this->FLD('productId', 'key(mvc=cat_Products, select=name, allowEmpty)', 'caption=Продукт,notNull,mandatory');
        $this->FLD('policyId', 'class(interface=price_PolicyIntf, select=title)', 'input=hidden,caption=Политика, silent');
    	$this->FLD('quantity', 'double(decimals=4)', 'caption=К-во,width=8em;');
    	$this->FLD('price', 'double(decimals=2)', 'caption=Ед. цена, input,width=8em;');
        $this->FLD('discount', 'percent(decimals=0)', 'caption=Отстъпка,width=8em;');
        $this->FLD('tolerance', 'percent(min=0,max=1,decimals=0)', 'caption=Толеранс,width=8em;');
    	$this->FLD('term', 'int', 'caption=Срок,unit=дни,width=8em;');
        $this->FLD('optional', 'enum(no=Не,yes=Да)', 'caption=Опционален,value=no');
    	$this->FNC('amount', 'varchar', 'caption=Сума,input=none');
    	$this->FNC('discAmount', 'varchar', 'caption=Сума,input=none');
    }
    
    
	/**
     * Изчислява на сумата
     */
    static function on_CalcAmount($mvc, $rec)
    {
        if($rec->quantity){
        	$rec->amount = round($rec->quantity * $rec->price, 2);
        } else {
        	$rec->amount = "???";
        }
    }
    
    
    /**
     * Изчислява на сумата с приложена отстъпка
     */
    static function on_CalcDiscAmount($mvc, $rec)
    {
    	if($rec->discount && $rec->quantity){
    		$disc = round(($rec->amount * $rec->discount), 2);
    		$rec->discAmount = $rec->amount - $disc;
    	}
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
       $form = $data->form;
       (Request::get('edit')) ? $title = tr("Редактиране") : $title = tr("Добавяне");
       $form->title = $title . " " . tr("|на запис в Оферта|* №{$form->rec->quotationId}");
    
       $masterRec = $mvc->Master->fetch($form->rec->quotationId);
       $Policy = cls::get($form->rec->policyId);
       $products = $Policy->getProducts($masterRec->contragentClassId, $masterRec->contragentId);
       $form->setOptions('productId', $products);
    }
    
    
	/**
     * Извиква се след въвеждането на данните от Request във формата
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
	    	$rec = &$form->rec;
	    	$Policy = cls::get($rec->policyId);
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
    	$prevProduct = NULL;
    	foreach($data->recs as $i => $rec){
    		if($rec->optional == 'no'){
    			if(!$prevProduct){
    				$prevProduct = $rec->productId;
    			} else {
    				if($rec->productId == $prevProduct) return;
    			}
    			
    			// Ако няма количество, цената неможе да се изчисли
    			if(!$rec->quantity) return;
    			$total += $rec->amount;
    			if($rec->discAmount){
    				$totalDisc += $rec->discAmount; 
    			}
    		}
    	}
    	
    	if($totalDisc == 0){
    		$totalDisc = NULL;
    	}
    	
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
    	$tpl->append($dTpl, 'DETAILS');
    	
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
    	if(!$rec->quantity){
    		$row->quantity = '???';
    	} else {
    		$row->quantity = floatval($row->quantity);
    	}
    	
    	// Временно докато се изесним какво се прави с productManCls
    	$uomId = cat_Products::fetchField($rec->productId, 'measureId');
    	$uomTitle = cat_UoM::recToVerbal($uomId, 'shortName')->shortName;
    	$row->quantity = "<b>{$row->quantity}</b> {$uomTitle}";
    	
    	if($rec->discount && $rec->discount < 0){
    		$row->discount = abs($rec->discount);
    		$row->discount = "+ {$row->discount}%";
    	} elseif($rec->discount && $rec->discount > 0){
    		$row->discount = "- {$row->discount}";
    	}
    	
    	$double = cls::get('type_Double');
    	$double->params['decimals'] = 2;
    	$masterRec = $mvc->Master->fetch($rec->quotationId);
    	$row->price = $double->toVerbal(currency_CurrencyRates::convertAmount($rec->price, $masterRec->date, NULL, $masterRec->paymentCurrencyId));
    	
    	if($rec->amount != '???'){
    		$row->amount = $double->toVerbal(currency_CurrencyRates::convertAmount($rec->amount, $masterRec->date, NULL, $masterRec->paymentCurrencyId));
    	}
    	
    	if($rec->discAmount){
    		$row->discAmount = $double->toVerbal(currency_CurrencyRates::convertAmount($rec->discAmount, $masterRec->date, NULL, $masterRec->paymentCurrencyId));
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
}