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
    	$primeCostListRound = price_Lists::fetchField($primeCostListId, 'roundingPrecision');
    	$catalogRound = price_Lists::fetchField(price_ListRules::PRICE_LIST_CATALOG, 'roundingPrecision');
    	setIfNot($primeCostListRound, 4);
    	setIfNot($catalogRound, 4);
    	
    	if(price_ListRules::haveRightFor('add', (object)array('productId' => $data->masterId))){
    		$data->addPriceUrl = array('price_ListRules', 'add', 'type' => 'value', 'listId' => $primeCostListId, 'productId' => $data->masterId, 'priority' => 1, 'ret_url' => TRUE);
    	}
    	
    	$now = dt::now();
    	
    	$primeCostRows = array();
    	
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
    	
    	$Double = cls::get('type_Double', array('params' => array('decimals' => $primeCostListRound)));
    	$DateTime = cls::get('type_DateTime');
    	
    	// Бутон за задаване на правило за обновяване
    	$data->afterRow = NULL;
    	
    	$uRec = price_Updates::fetch("#type = 'product' AND #objectId = {$data->masterId}");
    	if(is_object($uRec)){
    		$uRow = price_Updates::recToVerbal($uRec);
    		
    		$arr = array('manual' => tr('Ръчно'), 'nextDay' => tr('Дневно'), 'nextWeek' => tr('Седмично'), 'nextMonth' => tr('Месечно'), 'now' => tr('Ежечасово'));
    		$tpl = new core_ET(tr("|*[#tools#]<b>[#updateMode#]</b> |обновяване на себестойността, последователно по|* [#type#] |с надценка|* <b>[#costAdd#]</b>"));
    		
    		$type = '';
    		foreach (array($uRow->costSource1, $uRow->costSource2, $uRow->costSource3) as $cost){
    			if(isset($cost)){
    				$type .= "<b>" . $cost . "</b>, ";
    			}
    		}
    		
    		$tpl->append($arr[$uRec->updateMode], 'updateMode');
    		$tpl->append($type, 'type');
    		$tpl->append($uRow->costAdd, 'costAdd');
    		$tpl->append($uRow->tools, 'tools');
    		$data->afterRow = $tpl;
    	}
    	
    	if(haveRole('priceDealer,ceo')){
    		if(price_ListRules::haveRightFor('add', (object)array('productId' => $data->masterId))){
    			$btns = '';
    			$newCost = NULL;
    			if(isset($uRec->costValue)){
    				$newCost = $uRec->costValue;
    			}
    			if($newCost != $rec->primeCost){
    				$data->addPriceUrl['price'] = $newCost;
    			}
    			
    			if($hideIcons === FALSE){
    				$btns .= "<div>" . ht::createLink('Нова себест-ст', $data->addPriceUrl, FALSE, 'ef_icon=img/16/add.png,title=Добавяне на нова мениджърска себестойност') . "</div>";
    			}
    			
    			if(isset($uRec)){
    				if(price_Updates::haveRightFor('saveprimecost', $uRec)){
    					if($hideIcons === FALSE){
    						$btns .= "<div>" . ht::createLink('Обновяване', array('price_Updates', 'saveprimecost', $uRec->id, 'ret_url' => TRUE), FALSE, 'title=Обновяване на себестойноста според зададеното правило,ef_icon=img/16/arrow_refresh.png'). "</div>";
    					}
    				}
    			}
    			
    			if(price_Lists::haveRightFor('single', $primeCostListId) && isset($primeCost)){
    				if($hideIcons === FALSE){
    					$btns .= "<div>" . ht::createLink('Хронология', array('price_Lists', 'single', $primeCostListId, 'product' => $data->masterId), FALSE, 'ef_icon=img/16/clock_history.png,title=Хронология на себестойноста на артикула'). "</div>";
    				}
    			}
    		}
    		
    		if($btns || isset($primeCost)){
    			
    			$primeCostRows[] = (object)array('type' => tr('|Мениджърска себестойност|*'),
    					'modifiedOn' => $DateTime->toVerbal($primeCostDate),
    					'price'      => "<b>" . $Double->toVerbal($primeCost) . "</b>",
    					'buttons'    => $btns,
    					'ROW_ATTR'   => array('class' => 'state-active'));
    		}
    		
    		if(isset($futurePrimeCost)){
    			$primeCostRows[] = (object)array('type' => tr('|Бъдеща|* |себестойност|*'),
    					'modifiedOn' => $DateTime->toVerbal($futurePrimeCostDate),
    					'price' => "<b>" . $Double->toVerbal($futurePrimeCost) . "</b>", 
    					'ROW_ATTR' => array('class' => 'state-draft'));
    		}
    	}
    	
    	if(haveRole('price,ceo')){
    		
    		$cQuery = price_ProductCosts::getQuery();
    		$cQuery->where("#productId = {$data->masterId}");
    		while($cRec = $cQuery->fetch()){
    			$cRow = price_ProductCosts::recToVerbal($cRec);
    			$cRow->price = "<b>{$cRow->price}</b>";
    			if(isset($cRow->document)){
    				$cRow->buttons = $cRow->document;
    			}
    			$primeCostRows[] = $cRow;
    		}
    	}
    	
    	if(isset($catalogCost)){
    		$Double->params['decimals'] = $catalogRound;
    		$primeCostRows[] = (object)array('type' => tr('Каталог'), 
    									     'modifiedOn' => $DateTime->toVerbal($catalogCostDate), 
    										 'price' => "<b>" . $Double->toVerbal($catalogCost) . "</b>", 
    										 'ROW_ATTR' => array('class' => 'state-active'));
    	}
    	
    	$data->primeCostRows = $primeCostRows;
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
    	$fields = arr::make("price=Стойност|* <small>({$baseCurrencyCode}) |без ДДС|*</small>,type=Вид,modifiedOn=В сила от||Valid from,buttons=Действия / Документ");
    	$primeCostTpl = $table->get($data->primeCostRows, $fields);
    	$colspan = count($fields);
    	
    	$colspan = count($fields);
    	if(isset($data->afterRow)){
    		$afterRowTpl = new core_ET("<tr><td colspan={$colspan}>[#1#][#button#]</td></tr>");
    		$afterRowTpl->append($data->afterRow, '1');
    	} else {
    		$afterRowTpl = new core_ET("<tr><td colspan={$colspan}>[#1#][#button#]</td></tr>");
    		$afterRowTpl->append(tr('Няма зададено правило за обновяване'), '1');
    	
    		if(price_Updates::haveRightFor('add', (object)array('type' => 'product', 'objectId' => $data->masterId))){
    			$afterRowTpl->append(ht::createLink('Задаване', array('price_Updates', 'add', 'type' => 'product', 'objectId' => $data->masterId, 'ret_url' => TRUE), FALSE, 'title=Създаване на ново правило за обновяване,ef_icon=img/16/arrow_refresh.png'), 'button');
    		}
    	}
    	$primeCostTpl->append($afterRowTpl, 'ROW_AFTER');
    	
    	$tpl->append($primeCostTpl, 'primeCosts');
    	
    	return $tpl;
    }
}