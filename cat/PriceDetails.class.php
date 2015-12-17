<?php



/**
 * Помощен детайл подготвящ и обединяващ заедно детайлите на артикулите свързани
 * с ценовата информация на артикулите
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_PriceDetails extends core_Manager
{
    
    /**
     * Кои мениджъри ще се зареждат
     */
    public $loadList = 'PriceList=price_ListRules,VatGroups=cat_products_VatGroups,PriceGroup=price_GroupOfProducts';
    
    
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
    	
    	$this->PriceGroup->preparePriceGroup($groupsData);
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
    	$tpl->append($this->PriceGroup->renderPriceGroup($data->groupsData), 'PriceGroup');
    	$tpl->append($this->renderPriceInfo($data->listsData), 'PriceList');
    	$tpl->append($this->VatGroups->renderVatGroups($data->vatData), 'VatGroups');
    	
    	return $tpl;
    }
    
    
    /**
     * Подготвя подробната ценова информация
     */
    private function preparePriceInfo($data)
    {
    	$now = dt::now();
    	$primeCostListId = price_ListRules::PRICE_LIST_COST;
    	$rec = price_ProductCosts::fetch("#productId = {$data->masterId}");
    	if(!$rec){
    		$rec = new stdClass();
    	}
    	
    	$vat = cat_Products::getVat($data->masterId);
    	
    	$lQuery = price_ListRules::getQuery();
    	$lQuery->where("#listId = {$primeCostListId} AND #productId = {$data->masterId} AND #validFrom <= '{$now}' AND (#validUntil IS NULL OR #validUntil > '{$now}')");
    	$lQuery->orderBy("#validFrom,#id", "DESC");
        $lQuery->limit(1);
        
        if($pRec = $lQuery->fetch()){
        	$rec->primeCost = price_ListRules::normalizePrice($pRec, $vat, $now);
        	
	        if(isset($rec->primeCost)){
	    		$rec->primeCostDate = $pRec->validFrom;
	    	}
        }
    	
    	$catalogCost = price_ListRules::getPrice(price_ListRules::PRICE_LIST_CATALOG, $data->masterId);
    	if($catalogCost == 0 && !isset($rec->primeCost)){
    		$catalogCost = NULL;
    	}
    	$rec->catalogCost = $catalogCost;
    	
    	if(isset($rec->catalogCost)){
    		$rec->catalogCostDate = $now;
    	}
    	
    	$lQuery = price_ListRules::getQuery();
    	$lQuery->where("#listId = {$primeCostListId} AND #type = 'value' AND #productId = {$data->masterId} AND #validFrom > '{$now}'");
    	$lQuery->orderBy('validFrom', 'ASC');
    	$lQuery->limit(1);
    	if($lRec = $lQuery->fetch()){
    		$rec->futurePrimeCost = price_ListRules::normalizePrice($lRec, $vat, $now);
    		$rec->futurePrimeCostDate = $lRec->validFrom;
    	}
    	
    	$row = price_ProductCosts::recToVerbal($rec);
    	
    	$priceCostRows = $primeCostRows = array();
    	
    	if(haveRole('priceDealer,ceo')){
    		if(isset($rec->futurePrimeCost)){
    			$primeCostRows[] = (object)array('name' => tr('|Мениджърска|* (|Бъдеща|*)'), 'date' => $row->futurePrimeCostDate, 'price' => $row->futurePrimeCost, 'ROW_ATTR' => array('class' => 'state-draft'));
    		}
    		if(isset($rec->primeCost)){
    			$primeCostRows[] = (object)array('name' => tr('Мениджърска'), 'date' => $row->primeCostDate, 'price' => $row->primeCost, 'ROW_ATTR' => array('class' => 'state-active'));
    		}
    	}
    	
    	if(haveRole('price,ceo')){
    		$pData = clone $data;
    		price_Updates::prepareUpdateData($pData);
    		$data->updateData = $pData;
    		
    		if(isset($rec->accCost)){
    			$primeCostRows[] = (object)array('name' => tr('Складова'), 'date' => $row->accCostDate, 'price' => $row->accCost, 'ROW_ATTR' => array('class' => 'state-active'));
    		}
    		if(isset($rec->activeDelivery)){
    			$primeCostRows[] = (object)array('name' => tr('Текуща поръчка'), 'documentId' => $row->activeDeliveryId, 'date' => $row->activeDeliveryDate, 'price' => $row->activeDelivery, 'ROW_ATTR' => array('class' => 'state-active'));
    		}
    		if(isset($rec->lastDelivery)){
    			$primeCostRows[] = (object)array('name' => tr('Последна доставка'), 'documentId' => $row->lastDeliveryId, 'date' => $row->lastDeliveryDate, 'price' => $row->lastDelivery, 'ROW_ATTR' => array('class' => 'state-active'));
    		}
    		if(isset($rec->bom)){
    			$primeCostRows[] = (object)array('name' => tr('Последна рецепта'), 'documentId' => $row->bomId, 'date' => $row->bomIdDate, 'price' => $row->bom, 'ROW_ATTR' => array('class' => 'state-active'));
    		}
    		
    		if(isset($rec->lastQuote)){
    			$priceCostRows[] = (object)array('name' => tr('Последна оферта'), 'documentId' => $row->lastQuoteId, 'date' => $row->lastQuoteDate, 'price' => $row->lastQuote, 'ROW_ATTR' => array('class' => 'state-active'));
    		}
    	}
    	
    	if(isset($rec->catalogCost)){
    		$priceCostRows[] = (object)array('name' => tr('Каталог'), 'date' => $row->catalogCostDate, 'price' => $row->catalogCost, 'ROW_ATTR' => array('class' => 'state-active'));
    	}
    	
    	$data->primeCostRows = $primeCostRows;
    	$data->priceCostRows = $priceCostRows;
    	
    	// Може да се добавя нова себестойност, ако продукта е в група и може да се променя
    	if(price_ListRules::haveRightFor('add', (object)array('productId' => $data->masterId))){
    		$data->addPriceUrl = array('price_ListRules', 'add', 'type' => 'value',
    								  'listId' => $primeCostListId, 'productId' => $data->masterId, 'ret_url' => TRUE);
    	}
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
    	$fieldSet->FLD('price', 'double');
    	$baseCurrencyCode = acc_Periods::getBaseCurrencyCode();
    	
    	// Рендираме информацията за себестойностите
    	$table = cls::get('core_TableView', array('mvc' => $fieldSet));
    	$table->setFieldsToHideIfEmptyColumn('documentId');
    	$primeCostTpl = $table->get($data->primeCostRows, "name=Себестойност,documentId=Документ,date=Дата,price=Стойност|* <small>({$baseCurrencyCode})</small> |без ДДС|*");
    	$tpl->append($primeCostTpl, 'primeCosts');
    	
    	// Рендираме информацията за обновяване
    	if(count($data->updateData->rows)){
    		$updateTpl = price_Updates::renderUpdateData($data->updateData);
    		$tpl->append($updateTpl, 'updateInfo');
    	}
    	
    	// Бутон за задаване на правило за обновяване
    	$type = ($data->masterMvc instanceof cat_Products) ? 'product' : 'category';
    	if(price_Updates::haveRightFor('add', (object)array('type' => $type, 'objectId' => $data->masterId))){
    		$tpl->append(ht::createBtn('Обновяване', array('price_Updates', 'add', 'type' => $type, 'objectId' => $data->masterId, 'ret_url' => TRUE), FALSE, FALSE, 'ef_icon=img/16/arrow_refresh.png,title=Създаване на ново правило за обновяване'), 'updateInfo');
    	}
    	
    	if($data->addPriceUrl  && !Mode::is('text', 'xhtml') && !Mode::is('printing')){
			$tpl->append(ht::createLink("<img src=" . sbf('img/16/add.png') . " style='vertical-align: middle; margin-left:5px;'>", $data->addPriceUrl, FALSE, 'title=Добавяне на нова себестойност'), 'addBtn');
		}
    	
    	// Ако има ценова информация, рендираме я
    	if(count($data->priceCostRows)){
    		$table = cls::get('core_TableView', array('mvc' => $fieldSet));
    		$table->setFieldsToHideIfEmptyColumn('documentId');
    		$priceCost = $table->get($data->priceCostRows, "name=Цена,documentId=Документ,date=Дата,price=Стойност|* <small>({$baseCurrencyCode})</small> |без ДДС|*");
    		$tpl->append($priceCost, 'priceCosts');
    	}
    	
    	return $tpl;
    }
}