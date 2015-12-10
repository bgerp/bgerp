<?php



/**
 * Кеширани последни цени за артикулите
 *
 *
 * @category  bgerp
 * @package   price
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class price_ProductCosts extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Кеширани последни цени на артикулите';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Кеширани последни цени на артикулите";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, price_Wrapper';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id=Пулт, productId,accCost,activeDelivery,lastDelivery,bom';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'id';
    
    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да редактира?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'price,ceo';
	
	
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
    	$this->FLD('accCost', 'double', 'caption=Цени->Счетоводна');
    	$this->FLD('activeDelivery', 'double', 'caption=Цени->Текуща поръчка,tdClass=accCell');
    	$this->FLD('lastDelivery', 'double', 'caption=Цени->Последна доставка,tdClass=accCell');
    	$this->FLD('lastQuote', 'double', 'caption=Цени->Последна оферта,tdClass=accCell');
    	$this->FLD('bom', 'double', 'caption=Цени->Последна рецепта,tdClass=accCell');
    	
    	$this->FLD('activeDeliveryId', 'key(mvc=purchase_Purchases)', 'input=none');
    	$this->FLD('lastDeliveryId', 'key(mvc=purchase_Purchases)', 'input=none');
    	$this->FLD('lastQuoteId', 'key(mvc=sales_Quotations)', 'input=none');
    	$this->FLD('bomId', 'key(mvc=cat_Boms)', 'input=none');
    	
    	// Поставяне на уникални индекси
    	$this->setDbUnique('productId');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
    	
    	foreach (array('lastQuote', 'activeDelivery', 'lastDelivery', 'bomId') as $fld){
    		if(isset($rec->{$fld}) && isset($rec->{$fld})){
    			if($fld == 'bomId'){
    				$activeHandle = cat_Boms::getLink($rec->{"{$fld}Id"}, 0);
    			} elseif($fld == 'lastQuote'){
    				$activeHandle = sales_Quotations::getLink($rec->{"{$fld}Id"}, 0);
    			} else {
    				$activeHandle = purchase_Purchases::getLink($rec->{"{$fld}Id"}, 0);
    				//$activeHandle = purchase_Purchases::getHandle($rec->{"{$fld}Id"});
    				//$url = purchase_Purchases::getSingleUrlArray($rec->{"{$fld}Id"});
    				//$activeHandle = ht::createLink($activeHandle, $url);
    			}
    			
    			$row->{$fld} .= " <small>/ {$activeHandle}</small>";
    		}
    	}
    }
    
    
    /**
     * Рекалкулира себестойностите
     */
    function act_Recalcbomcost()
    {
    	expect(haveRole('debug'));
    	$this->cron_Recalcbomcost();
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(haveRole('debug')){
    		$data->toolbar->addBtn('Преизчисли', array($mvc, 'Recalcbomcost'), NULL, 'ef_icon = img/16/arrow_refresh.png,title=Преизчисляване на себестойностите,target=_blank');
    	}
    }
    
    
    /**
     * Връщаме усреднените цени от счетоводството
     * 
     * @return array $res - намерените цени
     */
    function getAccCosts()
    {
    	$tmpArr = $res = array();
    	$balanceRec = acc_Balances::getLastBalance();
    		
    	// Ако няма баланс няма какво да подготвяме
    	if(empty($balanceRec)) return FALSE;
    	
    	// Филтриране да се показват само записите от зададените сметки
    	$dQuery = acc_BalanceDetails::getQuery();
    	acc_BalanceDetails::filterQuery($dQuery, $balanceRec->id, '321');
    	$positionId = acc_Lists::getPosition('321', 'cat_ProductAccRegIntf');
    	
    	// За всеки запис в баланса
    	while($dRec = $dQuery->fetch()){
    		$itemId = $dRec->{"ent{$positionId}Id"};
    		if(!array_key_exists($itemId, $tmpArr)){
    			$tmpArr[$itemId] = new stdClass();
    			$tmpArr[$itemId]->name = acc_Items::getTitleById($itemId);
    		}
    		
    		// Сумираме сумите и количествата
    		$tmpArr[$itemId]->quantity += abs($dRec->blQuantity);
    		$tmpArr[$itemId]->amount += abs($dRec->blAmount);
    	}
    	
    	// Намираме цената 
    	foreach ($tmpArr as $index => $r){
    		$pId = acc_Items::fetchField($index, 'objectId');
    		$res[$pId] = (!$r->quantity) ? 0 : round($r->amount / $r->quantity, 5);
    	}
    	
    	// Връщаме резултатите
    	return $res;
    }
    
    
    /**
     * Връща всички покупки, в които участват подадените артикули.
     * Покупките са подредени в низходящ ред, така най-първите са последните.
     * 
     * @param array $productKeys - масив с ид-та на артикули
     * @param boolean $withDelivery - дали да има доставено по покупката или не
     * @return array $res - намерените последни доставни цени
     */
    private function getPurchasesWithProducts($productKeys, $withDelivery = FALSE)
    {
    	$pQuery = purchase_PurchasesDetails::getQuery();
    	$pQuery->EXT('state', 'purchase_Purchases', 'externalName=state,externalKey=requestId');
    	$pQuery->EXT('amountDelivered', 'purchase_Purchases', 'externalName=amountDelivered,externalKey=requestId');
    	$pQuery->where("#state = 'active'");
    	
    	if($withDelivery === TRUE){
    		$pQuery->EXT('threadId', 'purchase_Purchases', 'externalName=threadId,externalKey=requestId');
    		$pQuery->EXT('containerId', 'purchase_Purchases', 'externalName=containerId,externalKey=requestId');
    		$pQuery->where("#amountDelivered IS NOT NULL AND #amountDelivered != 0");
    		$pQuery->show('price,productId,threadId,requestId,containerId');
    	} else {
    		$pQuery->where("#amountDelivered IS NULL OR #amountDelivered = 0");
    		$pQuery->show('price,productId,requestId');
    	}
    	
    	$pQuery->in('productId', $productKeys);
    	$pQuery->orderBy('id', 'DESC');
    	
    	// Връщаме намерените резултати
    	return $pQuery->fetchAll();
    }
    
    
    /**
     * Връща последните доставни цени на подадените артикули
     * 
     * @param array $productKeys - масив с ид-та на артикули
     * @return array $res - намерените последни доставни цени
     */
    private function getDeliveryCosts($productKeys)
    {
    	$res = array();
    	$Purchases = cls::get('purchase_Purchases');
    	
    	// Намираме всички покупки с доставка
    	$allPurchases = $this->getPurchasesWithProducts($productKeys, TRUE);
		
    	// Тук ще кешираме доставените артикули във всяка
    	$purchaseProducts = array();
    	
    	// За всяка
    	foreach ($allPurchases as $purRec){
    		
    		// Ако няма цена за артикула, взимаме първата срещната, така винаги на артикула
    		// ще му съответства последната доставна цена, другите записи ще се пропуснат
    		if(!isset($res[$purRec->productId])){
    			
    			// Ако няма кеширана информация за доставеното по сделката кешираме го
    			if(!isset($purchaseProducts[$purRec->requestId])){
    				
    				// Намираме всички записи от журнала по покупката
    				$entries = purchase_transaction_Purchase::getEntries($purRec->requestId);
    				
    				// Към тях търсим всички документи от вида "Корекция на стойности", които са
    				// в нишката на покупката и са по друга сделка. Понеже в тяхната контировка не участва
    				// перото на текущата сделка, и 'purchase_transaction_Purchase::getEntries' не може
    				// да им вземе записите, затова ги добавяме ръчно
    				$aExpensesQuery = acc_AllocatedExpenses::getQuery();
    				$aExpensesQuery->where("#threadId = {$purRec->threadId} AND #state = 'active' AND #correspondingDealOriginId != {$purRec->containerId}");
    				$aExpensesQuery->show('id');
    				
    				// За всеки документ "Корекция на стойности" в нишката
    				while($aRec = $aExpensesQuery->fetch()){
    					
    					// Намираме записите от журнала 
    					$jRec = acc_Journal::fetchByDoc('acc_AllocatedExpenses', $aRec->id);
    					$dQuery = acc_JournalDetails::getQuery();
    					$dQuery->where("#journalId = {$jRec->id}");
    					$expensesEntries = $dQuery->fetchAll();
    					
    					// Добавяме записите на корекцията към записите на сделката
    					// Така ще коригираме себестойностите и с техните данни
    					$entries = $expensesEntries + $entries;
    				}
    				
    				// Намираме и кешираме всичко доставено по сделката с приспаднати корекции на сумите
    				// от документите от вида "Корекция на стойност". В обикновените записи имаше приложени
    				// само корекциите от документа когато той е към същата сделка. Когато е към друга не се вземаха
    				// затова трябваше да се добавят ръчно към записите
    				$purchaseProducts[$purRec->requestId] = purchase_transaction_Purchase::getShippedProducts($entries);
    			}
    			
    			// Намираме какво е експедирано по сделката
    			$shippedProducts = $purchaseProducts[$purRec->requestId];
    			
    			// Взимаме цената на продукта по тази сделка
    			$price = $shippedProducts[$purRec->productId]->price;
    			if(isset($price)){
    				$price = round($price, 5);
    				
    				$res[$purRec->productId] = (object)array('documentId' => $purRec->requestId,
    														 'price'      => $price);
    			}
    		}
    	}
    	
    	// Връщаме намерените последни цени
    	return $res;
    }
    
   
    /**
     * Намира цените от последната активна поръчка
     * 
     * @param array $productKeys - масив с ид-та на артикули
     * @return array $res - намерените цените по последна активна поръчка
     */
    private function getActiveDeliveryCosts($productKeys)
    {
    	$res = array();
    	
    	// Намираме всички покупки по, които няма доставени
    	$allPurchases = $this->getPurchasesWithProducts($productKeys);
    	
    	// За всяка покупка
    	foreach ($allPurchases as $purRec){
    		
    		// Намираме първата срещната цена за артикула, покупките са подредени по-последно
    		// създаване, така сме сигурни че ще се вземе първата срещната цена, която е цената по
    		// последна активна поръчка
    		if(!isset($res[$purRec->productId])){
    			$res[$purRec->productId] = (object)array('documentId' => $purRec->requestId,
    													 'price'      => round($purRec->price, 5));
    			
    		}
    	}
    	
    	// Връщаме намерените цени
    	return $res;
    }
    
    
    /**
     * Намира цените от последната активна оферта
     * 
     * @param array $productKeys - масив с ид-та на артикули
     * @return array $res - намерените цените по последна активна оферта
     */
    private function getLastQuoteCosts($productKeys)
    {
    	$res = array();
    	
    	// Намираме всички активни оферти с подадените артикули
    	$qQuery = sales_QuotationsDetails::getQuery();
    	$qQuery->EXT('state', 'sales_Quotations', 'externalName=state,externalKey=quotationId');
    	$qQuery->where("#state = 'active'");
    	$qQuery->show('price,productId,quotationId');
    	$qQuery->in('productId', $productKeys);
    	$qQuery->orderBy('id', 'DESC');
    	$allQuotes = $qQuery->fetchAll();
    	
    	// За всяка оферта
    	foreach ($allQuotes as $quote){
    		
    		// Намираме първата срещната цена за артикула, офертите са подредени по-последно
    		// създаване, така сме сигурни че ще се вземе първата срещната цена, която е цената по
    		// последна активна оферта
    		if(!isset($res[$quote->productId])){
    			$res[$quote->productId] = (object)array('documentId' => $quote->quotationId, 
    													'price' => round($quote->price, 5));
    		}
    	}
    	
    	// Връщаме намерените цени
    	return $res;
    }
    
    
    /**
     * Намира цените от последната активна рецепта
     * 
     * @param array $productKeys - масив с ид-та на артикули
     * @return array $res - намерените цените по последна рецепта
     */
    private function getLastBomCosts($productKeys)
    {
    	$res = array();
    	$Boms = cls::get('cat_Boms');
    	$cache = array();
    	
    	// За всеки артикул
    	foreach ($productKeys as $productId){
    		
    		// Търсим му рецептата
    		if($bomRec = cat_Products::getLastActiveBom($productId)){
    			if(!isset($cache[$bomRec->id])){
    				
    				// Ако има, намираме и цената
    				$t = ($bomRec->quantityForPrice) ? $bomRec->quantityForPrice : $bomRec->quantity;
    				$cache[$bomRec->id] = cat_Boms::getBomPrice($bomRec, $t, 0, 0, $bomRec->modifiedOn, price_ListRules::PRICE_LIST_COST);
    			}
    			
    			$primeCost = $cache[$bomRec->id];
    			if($primeCost){
    				$res[$productId] = (object)array('documentId' => $bomRec->id, 
    												 'price' => $primeCost);
    			}
    		}
    	}
    	
    	// Връщаме намрените цени
    	return $res;
    }
    
    
    /**
     * Обновяване на себестойностите по разписание
     */
    function cron_Recalcbomcost()
    {
    	core_App::setTimeLimit(360);
    	
    	$date = dt::now();
    	$products = cat_Products::getStandartProducts(TRUE);
    	$productKeys = array_combine(array_keys($products), array_keys($products));
    	
    	$res = array();
    	
    	$res['accCost'] = $this->getAccCosts();
    	$res['activeDelivery'] = $this->getActiveDeliveryCosts($productKeys);
    	$res['lastDelivery'] = $this->getDeliveryCosts($productKeys);
    	$res['lastQuote'] = $this->getLastQuoteCosts($productKeys);
    	$res['bom'] = $this->getLastBomCosts($productKeys);
    	
    	//echo "<li>" . count($products);
    	//echo "<li>accCost: " . core_Debug::$timers['accCost']->workingTime;
    	//echo "<li>activeDelivery: " . core_Debug::$timers['activeDelivery']->workingTime;
    	//echo "<li>lastDelivery: " . core_Debug::$timers['lastDelivery']->workingTime;
    	//echo "<li>lastQuote: " . core_Debug::$timers['lastQuote']->workingTime;
    	//echo "<li>bom: " . core_Debug::$timers['bom']->workingTime;
    	
    	$values = array();
    	foreach ($products as $productId => $productName){
    		$obj = (object)array(
    					'productId'      => $productId,
    					'accCost'        => $res['accCost'][$productId],
    					'bom'            => $res['bom'][$productId],
    		);
    		
    		foreach (array('lastQuote', 'activeDelivery', 'lastDelivery', 'bom') as $fld){
    			if(isset($res[$fld][$productId])){
    				$obj->{$fld} = $res[$fld][$productId]->price;
    				$obj->{"{$fld}Id"} = $res[$fld][$productId]->documentId;
    			} else {
    				$obj->{$fld} = NULL;
    				$obj->{"{$fld}Id"} = NULL;
    			}
    		}
    		
    		$values[$productId] = $obj;
    	}
    	
    	$query = static::getQuery();
    	$oldRecs = $query->fetchAll();
    	
    	$synced = arr::syncArrays($values, $oldRecs, 'productId', 'lastQuote,activeDelivery,lastDelivery,bom,lastQuoteId,activeDeliveryId,lastDeliveryId,bomId');
    	$this->saveArray($synced['insert']);
    	$this->saveArray($synced['update']);
    }
}