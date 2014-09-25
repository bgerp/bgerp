<?php



/**
 * Детайли на универсалните продукти
 *
 * @category  bgerp
 * @package   techo
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class techno_GeneralProductsDetails extends core_Detail {
    
    
    /**
     * Заглавие
     */
    var $title = 'Компоненти';
    
    
    /**
     * Единично заглавие
     */
    var $singleTitle = 'Компонент';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'techno_Wrapper, plg_RowTools, plg_Sorting, plg_SaveAndNew,
    				 plg_AlignDecimals, plg_RowNumbering, doc_plg_HidePrices';
    
  
    /**
	 * Мастър ключ към универсалния продукти
	 */
	var $masterKey = 'generalProductId';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'RowNumb';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'techno, ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'no_one';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'techno, ceo';
    
    
    /**
     * Кой таб да бъде отворен
     */
    var $currentTab = 'Универсални продукти';
	
    
    /**
     * Полета за списъчния изглед
     */
    var $listFields = 'RowNumb=Пулт, componentId, cQuantity, cMeasureId, price, amount, bTaxes';
    
    
    /**
     * Полета свързани с цени
     */
    var $priceFields = 'price,amount,bTaxes';
    
    
     /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('generalProductId', 'key(mvc=techno_GeneralProducts)', 'caption=Продукт,input=hidden');
    	$this->FLD('componentId', 'varchar(255)', 'caption=Продукт,mandatory');
    	$this->FLD('cQuantity', 'double', 'caption=К-во');
    	$this->FLD('price', 'double(decimals=2)', 'caption=Цена,');
    	$this->FLD('amount', 'double(decimals=2)', 'caption=Сума,input=hidden');
    	$this->FLD('cMeasureId', 'key(mvc=cat_UoM,select=shortName)', 'caption=Мярка,input=none');
    	$this->FLD('bTaxes', 'double(decimals=2)', 'caption=Такса');
    	$this->FLD('vat', 'percent', 'caption=ДДС,input=hidden');
    }
    
    
	/**
     * Преди показване на форма за добавяне/промяна.
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec = &$form->rec;
    	$products = array('-1' => tr('Основа')) + cat_Products::getByProperty('canConvert');
    	
    	if(empty($form->rec->id)){
    		$products = static::getRemainingOptions($rec->generalProductId, $products);
    		expect(count($products));
    		$form->setOptions('componentId', $products);
    	} else {
    		if($rec->componentId == -1){
    			$rec->price = $rec->amount;
    		}
    		$products = array($rec->componentId => $products[$rec->componentId]);
    	}
    	
    	$form->getField('componentId')->type = cls::get("type_Enum", array('options' => $products));
    }
    
    
     /**
     * Подготовка на бутоните на формата за добавяне/редактиране.
     */
    function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
    	if (empty($data->form->rec->id)) {
    		if(!(count(static::getRemainingOptions($data->form->rec->generalProductId)) - 1)){
    			$data->form->toolbar->removeBtn('saveAndNew');
    		}
    	}
    }
    
    
	/**
     * Извиква се след въвеждането на данните от Request във формата
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
    		$rec = &$form->rec;
    		
    		if($rec->componentId != -1){
		        if(!$rec->cQuantity){
			        $form->setError('cQuantity', 'Моля задайте количество');
			    }
		        $rec->cMeasureId = cat_Products::fetchField($rec->componentId, 'measureId');
	        	$rec->vat = cat_Products::getVat($rec->componentId);
	        	if(!$rec->bTaxes){
	        		$rec->bTaxes = cat_products_Params::fetchParamValue($rec->componentId, 'bTax');
	        	}
        	} else {
        		$rec->cQuantity = 1;
        	}
        	
        	if(!$rec->price){
        		$folderId = $mvc->Master->fetchField($rec->generalProductId, 'folderId');
        		$Policy = cls::get('price_ListToCustomers');
		        $contClass = doc_Folders::fetchCoverClassId($folderId);
			    $contId = doc_Folders::fetchCoverId($folderId);
			    
			    $rec->price = $Policy->getPriceInfo($contClass, $contId, $rec->componentId, cat_Products::getClassId(), NULL, $rec->cQuantity, dt::now(), 1, 'no')->price;
			    if(!$rec->price){
			        $form->setError('price', 'Проблем при извличането на цената! Моля задайте ръчно');
			    }
	        }
	        
	        $rec->amount = $rec->cQuantity * $rec->price;
	        if($rec->componentId == -1){
	        	unset($rec->cQuantity, $rec->cPrice);
	        }
    	}
    }
    
    
    /**
     * Помощен метод за показване само на тези компоненти, които
     * не са добавени към спецификацията
     */
    public static function getRemainingOptions($generalProductId, $products = NULL)
    {
    	if(empty($products)){
    		$products = array('-1' => tr('Основа')) + cat_Products::getByProperty('canConvert');
    	}
    	
    	$query = static::getQuery();
    	$query->where("#generalProductId = {$generalProductId}");
    	$query->show('componentId');
    	while($rec = $query->fetch()){
    		if(isset($products[$rec->componentId])){
    			unset($products[$rec->componentId]);
    		}
    	}
    	
    	return $products;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->componentId = ($rec->componentId != -1) ? cat_Products::getTitleById($rec->componentId) : tr('Основа');
    	if(!Mode::is('text', 'xhtml') && !Mode::is('printing') && $rec->componentId != '-1'){
    		$row->componentId = ht::createLinkRef($row->componentId, array('cat_Products', 'single', $rec->componentId), NULL, 'title=Към компонента');
    	}
    }
    
    
    /**
     * Rendirane na detajlite
     */
    function renderDetail_($data)
    {
    	$cTpl = getTplFromFile('techno/tpl/GeneralProductsDetails.shtml');
    	$tpl = $cTpl->getBlock('LONG');
    	if($data->masterData->rec->state == 'draft' || isset($data->rows)){
    		$tpl->replace(tr('Компоненти'), 'TITLE');
    	}
    	
    	if($data->rows){
    		foreach ($data->rows as $row){
    			$cloneTpl = clone $tpl->getBlock('COMPONENT');
    			$cloneTpl->placeObject($row);
    			$cloneTpl->removeBlocks();
    			$cloneTpl->append2master();
    		}
    		
    		if($data->total && !isset($data->noTotal)){
	    		$tpl->placeObject($data->total);
	    	}
    	}
    	
    	if($data->toolbar){
    		$tpl->replace($this->renderListToolbar($data), 'ListToolbar');
    	}
    	
    	return $tpl;
    }
    
    
    /**
     * След подготовка на детайлите
     */
    static function on_AfterPrepareDetail(core_Mvc $mvc, $res, &$data)
    {	
        if(isset($data->noTotal)) return;
    	$price = $mvc->getTotalPrice($data->masterData->rec->id);
        if(!$price->price) return;
        	
        $Double = cls::get('type_Double');
	    $Double->params['decimals'] = 2;
	    	
	    $data->total = (object)array('totalAmount' => $Double->toVerbal($price->price), 'totalTaxes' => ($price->taxes) ? $Double->toVerbal($price->taxes) : NULL);
    	$cCode = acc_Periods::getBaseCurrencyCode($data->masterData->rec->modifiedOn);
	    $data->total->currencyId = $cCode;
    	if($price->taxes){
	    	$data->total->taxCurrencyId = $cCode;
	    }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(isset($data->toolbar->buttons['btnAdd']) && count($mvc->getRemainingOptions($data->masterData->rec->id))){
    		$data->toolbar->buttons['btnAdd']->title = tr("Нов компонент");
    	} else {
    		$data->toolbar->removeBtn('btnAdd');
    	}
    }
    
    
    /**
     * Подготвя данните за краткия изглед
     * @param int $generalProductId - ид на продукта
     * @return array() - всички детайли
     */
    public function prepareDetails($generalProductId)
    {
    	$query = $this->getQuery();
    	$query->where("#generalProductId = {$generalProductId}");
    	$query->where("#componentId != -1");
    	while($rec = $query->fetch()){
    		$recs[$rec->id] = $rec;
    		$rows[$rec->id] = $this->recToVerbal($rec);
    	}
    	
    	return (object)array('recs' => $recs, 'rows' => $rows);
    }
    
    
    /**
     * Връща вербалното представяне на даденото изделие (HTML, може с картинка)
     * @param array $array - записи
     * @return core_ET - шаблон
     */
	public function renderShortView($data)
    {
    	$tpl = getTplFromFile('techno/tpl/GeneralProductsDetails.shtml')->getBlock('SHORT');
    	
    	if(count($data->rows)){
    		foreach ($data->rows as $row){
    			$block = clone $tpl->getBlock('COMPONENT');
    			$block->placeObject($row);
    			$block->removeBlocks();
    			$block->append2master();
    		}
    	}
    	
    	return $tpl;
    }
    
    
    /**
     * Изчислява точната цена на продукта
     * @param int $generalProductId - ид на продукта
     */
    public function getTotalPrice($generalProductId)
    {
    	$total = $taxes = 0;
    	$query = $this->getQuery();
    	$query->where("#generalProductId = {$generalProductId}");
    	while($rec = $query->fetch()){
    		$total += $rec->amount;
        	$taxes += $rec->bTaxes;
    	}
    	
    	return (object)array('price' => $total, 'tax' => $taxes);
    }
}