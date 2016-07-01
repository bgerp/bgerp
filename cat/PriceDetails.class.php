<?php



/**
 * Помощен детайл подготвящ и обединяващ заедно детайлите на артикулите свързани
 * с ценовата информация на артикулите
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_PriceDetails extends core_Manager
{
    
	
    /**
     * Кои мениджъри ще се зареждат
     */
    public $loadList = 'VatGroups=cat_products_VatGroups';
    
    
    /**
     * Кой има достъп до списъчния изглед
     */
    public $canList = 'no_one';
    
    
    /**
     * Кой може да пише
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Кой може да чете
     */
    public $canSeeprices = 'ceo,priceDealer';
    
    
    /**
     * Подготвя ценовата информация за артикула
     */
	public function preparePrices($data)
    {
    	if(!haveRole($this->canSeeprices)){
    		$data->hide = TRUE;
    		return;
    	}
    	
    	$data->TabCaption = 'Цени';
    	$data->Tab = 'top';
    	$data->Order = 5;
    	 
    	$groupsData = clone $data;
    	$listsData = clone $data;
    	$vatData = clone $data;
    	
    	$this->preparePriceInfo($listsData);
    	$this->VatGroups->prepareVatGroups($vatData);
    	
    	$data->groupsData = $groupsData;
    	$data->listsData = $listsData;
    	$data->vatData = $vatData;
    }
    
    
    /**
     * Рендира ценовата информация за артикула
     */
    public function renderPrices($data)
    {
    	if($data->hide === TRUE) return;
    	
    	$tpl = getTplFromFile('cat/tpl/PriceDetails.shtml');
    	$tpl->append($this->renderPriceInfo($data->listsData), 'PriceList');
    	$tpl->append($this->VatGroups->renderVatGroups($data->vatData), 'VatGroups');
    	
    	return $tpl;
    }
    
    
    /**
     * Подготвя подробната ценова информация
     */
    private function preparePriceInfo($data)
    {
    	$hideIcons = FALSE;
    	if(Mode::is('printing') || Mode::is('text', 'xhtml') || Mode::is('pdf')){
    		$hideIcons = TRUE;
    	}
    	
    	// Може да се добавя нова себестойност, ако продукта е в група и може да се променя
    	$primeCostListId = price_ListRules::PRICE_LIST_COST;
    	if(price_ListRules::haveRightFor('add', (object)array('productId' => $data->masterId))){
    		$data->addPriceUrl = array('price_ListRules', 'add', 'type' => 'value', 'listId' => $primeCostListId, 'productId' => $data->masterId, 'priority' => 1, 'ret_url' => TRUE);
    	}
    	
    	$now = dt::now();
    	
    	$priceCostRows = $primeCostRows = $primeCostRecs = $priceCostRecs = array();
    	
    	$rec = price_ProductCosts::fetch("#productId = {$data->masterId}");
    	if(!$rec){
    		$rec = new stdClass();
    	}
    	
    	$primeCost = price_ListRules::getPrice(price_ListRules::PRICE_LIST_COST, $data->masterId, NULL, $now, $validFrom);
    	if(isset($primeCost)){
    		$primeCostDate = $validFrom;
    	}
        
    	$catalogCost = price_ListRules::getPrice(price_ListRules::PRICE_LIST_CATALOG, $data->masterId, NULL, $now, $validFrom);
    	if($catalogCost == 0 && !isset($rec->primeCost)){
    		$catalogCost = NULL;
    	}
    	if(isset($catalogCost)){
    		$catalogCostDate = $validFrom;
    	}
    	
    	$lQuery = price_ListRules::getQuery();
    	$lQuery->where("#listId = {$primeCostListId} AND #type = 'value' AND #productId = {$data->masterId} AND #validFrom > '{$now}'");
    	$lQuery->orderBy('validFrom', 'ASC');
    	$lQuery->limit(1);
    	if($lRec = $lQuery->fetch()){
    		$futurePrimeCost = price_ListRules::normalizePrice($lRec, $vat, $now);
    		$futurePrimeCostDate = $lRec->validFrom;
    	}
    	
    	$Double = cls::get('type_Double');
    	$DateTime = cls::get('type_DateTime');
    	
    	if(haveRole('priceDealer,ceo')){
    		if(isset($futurePrimeCost)){
    			$primeCostRecs[] = (object)array('price' => $futurePrimeCost);
    			$primeCostRows[] = (object)array('type' => tr('|Мениджърска|* (|Бъдеща|*)'), 
    											 'modifiedOn' => $DateTime->toVerbal($futurePrimeCostDate), 
    											 'price' => $Double->toVerbal($futurePrimeCost), 'ROW_ATTR' => array('class' => 'state-draft'));
    		}
    		
    		if(price_ListRules::haveRightFor('add', (object)array('productId' => $data->masterId))){
    			$btns = '';
    			$uRec = price_Updates::fetch("#type = 'product' AND #objectId = {$data->masterId}");
    			$newCost = NULL;
    			if(isset($uRec->costValue)){
    				$newCost = $uRec->costValue;
    			}
    			if($newCost != $rec->primeCost){
    				$data->addPriceUrl['price'] = $newCost;
    			}
    			
    			if($hideIcons === FALSE){
    				$btns .= " " . ht::createLink('', $data->addPriceUrl, FALSE, 'ef_icon=img/16/add.png,title=Добавяне на нова мениджърска себестойност');
    			}
    			
    			if(isset($uRec)){
    				if(price_Updates::haveRightFor('saveprimecost', $uRec)){
    					if($hideIcons === FALSE){
    						$btns .= " " . ht::createLink('', array('price_Updates', 'saveprimecost', $uRec->id, 'ret_url' => TRUE), FALSE, 'title=Обновяване на себестойноста според зададеното правило,ef_icon=img/16/arrow_refresh.png');
    					}
    				}
    			}
    			
    			if(price_Lists::haveRightFor('single', $primeCostListId) && isset($primeCost)){
    				if($hideIcons === FALSE){
    					$btns .= " " . ht::createLink('', array('price_Lists', 'single', $primeCostListId, 'product' => $data->masterId), FALSE, 'ef_icon=img/16/clock_history.png,title=Хронология на себестойноста на артикула');
    				}
    			}
    		}
    		
    		if($btns || isset($primeCost)){
    			$primeCostRecs[] = (object)array('price' => $primeCost);
    			
    			$primeCostRows[] = (object)array('type' => $btns . tr('Мениджърска'),
    					'modifiedOn' => $DateTime->toVerbal($primeCostDate),
    					'price'      => $Double->toVerbal($primeCost),
    					'ROW_ATTR'   => array('class' => 'state-active'));
    		}
    	}
    	
    	if(haveRole('price,ceo')){
    		$pData = clone $data;
    		price_Updates::prepareUpdateData($pData);
    		$data->updateData = $pData;
    		
    		$cQuery = price_ProductCosts::getQuery();
    		$cQuery->where("#productId = {$data->masterId}");
    		while($cRec = $cQuery->fetch()){
    			$cRow = price_ProductCosts::recToVerbal($cRec);
    			$primeCostRecs[] = (object)array('price' => $cRec->price);
    			$primeCostRows[] = $cRow;
    		}
    	}
    	
    	if(isset($catalogCost)){
    		$priceCostRecs[] = (object)array('price' => $catalogCost);
    		$priceCostRows[] = (object)array('type' => tr('Каталог'), 'modifiedOn' => $DateTime->toVerbal($catalogCostDate), 'price' => $Double->toVerbal($catalogCost), 'ROW_ATTR' => array('class' => 'state-active'));
    	}
    	
    	$data->primeCostRecs = $primeCostRecs;
    	$data->priceCostRecs = $priceCostRecs;
    	
    	$data->primeCostRows = $primeCostRows;
    	$data->priceCostRows = $priceCostRows;
    }
    
    
    /**
     * Рендира подготвената ценова информация
     * 
     * @param stdClass $data
     * @return core_ET
     */
    private function renderPriceInfo($data)
    {
    	$tpl = getTplFromFile('cat/tpl/PrimeCostValues.shtml');
    	$fieldSet = cls::get('core_FieldSet');
    	$fieldSet->FLD('price', 'double(minDecimals=2)');
    	$baseCurrencyCode = acc_Periods::getBaseCurrencyCode();
    	
    	// Рендираме информацията за себестойностите
    	$table = cls::get('core_TableView', array('mvc' => $fieldSet));
    	$fields = "type=Себестойност,document=Документ,modifiedOn=Модифициране||Modified,price=Стойност|* <small>({$baseCurrencyCode})</small> |без ДДС|*";
    	$fields = core_TableView::filterEmptyColumns($data->primeCostRows, $fields, 'document');
    	
    	plg_AlignDecimals2::alignDecimals($fieldSet, $data->primeCostRecs, $data->primeCostRows);
    	$primeCostTpl = $table->get($data->primeCostRows, $fields);
    	$tpl->append($primeCostTpl, 'primeCosts');
    	
    	// Рендираме информацията за обновяване
    	if(count($data->updateData->rows)){
    		$updateTpl = price_Updates::renderUpdateData($data->updateData);
    		$tpl->append($updateTpl, 'updateInfo');
    	}
    	
    	// Бутон за задаване на правило за обновяване
    	$type = ($data->masterMvc instanceof cat_Products) ? 'product' : 'category';
    	if(price_Updates::haveRightFor('add', (object)array('type' => $type, 'objectId' => $data->masterId))){
    		$tpl->append(ht::createBtn('Правило за обновяване', array('price_Updates', 'add', 'type' => $type, 'objectId' => $data->masterId, 'ret_url' => TRUE), FALSE, FALSE, 'title=Създаване на ново правило за обновяване,ef_icon=img/16/arrow_refresh.png'), 'updateInfo');
    	}
    	
    	// Ако има ценова информация, рендираме я
    	if(count($data->priceCostRows)){
    		plg_AlignDecimals2::alignDecimals($fieldSet, $data->priceCostRecs, $data->priceCostRows);
    		
    		$table = cls::get('core_TableView', array('mvc' => $fieldSet));
    		
    		$fields = "type=Цена,document=Документ,modifiedOn=Модифициране||Modified,price=Стойност|* <small>({$baseCurrencyCode})</small> |без ДДС|*";
    		$fields = core_TableView::filterEmptyColumns($data->priceCostRows, $fields, 'document');
    		$priceCost = $table->get($data->priceCostRows, $fields);
    		$tpl->append($priceCost, 'priceCosts');
    	}
    	
    	return $tpl;
    }
}