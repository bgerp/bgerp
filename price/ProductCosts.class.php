<?php


/**
 * Кеширани последни цени за артикулите
 *
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
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
    public $singleTitle = 'Кеширани последни цени на артикулите';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, price_Wrapper';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id=Пулт, productId, type, price, quantity, document=Документ, modifiedOn';
    
    
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
    public $canList = 'debug';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
        $this->FLD('type', 'enum(accCost=Складова,
    							 lastDelivery=Последна доставка (+разходи),
    							 activeDelivery=Текуща поръчка,
    							 lastQuote=Последна оферта,
    							 bom=Последна рецепта,average=Средна доставна за наличното)', 'caption=Тип');
        $this->FLD('price', 'double', 'caption=Ед. цена');
        $this->FLD('quantity', 'double', 'caption=К-во');
        $this->FLD('documentClassId', 'class(interface=doc_DocumentIntf)', 'caption=Документ->Клас');
        $this->FLD('documentId', 'int', 'caption=Документ->Ид');
        $this->FLD('modifiedOn', 'datetime(format=smartTime)', 'caption=Създадено на');
        
        $this->setDbUnique('productId,type');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->productId = cat_Products::getHyperlink($rec->productId, true);
        $row->price = price_Lists::roundPrice(price_ListRules::PRICE_LIST_COST, $rec->price, true);
        
        if (cls::load($rec->documentClassId, true) && isset($rec->documentId)) {
            $Document = cls::get($rec->documentClassId);
            $row->document = $Document->getLink($rec->documentId, 0);
        }
        
        $row->ROW_ATTR = array('class' => 'state-active');
    }
    
    
    /**
     * Рекалкулира себестойностите
     */
    public function act_CachePrices()
    {
        expect(haveRole('debug'));
        $this->cron_CachePrices();
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if (haveRole('debug')) {
            $data->toolbar->addBtn('Преизчисли', array($mvc, 'CachePrices'), null, 'ef_icon = img/16/arrow_refresh.png,title=Преизчисляване на себестойностите,target=_blank');
        }
    }
    
    
    /**
     * Връщаме усреднените цени от счетоводството
     *
     * @return array $res - намерените цени
     */
    private function getAccCosts()
    {
        $tmpArr = $res = array();
        $balanceRec = acc_Balances::getLastBalance();
        
        // Ако няма баланс няма какво да подготвяме
        if (empty($balanceRec)) {
            
            return false;
        }
        
        // Филтриране да се показват само записите от зададените сметки
        $dQuery = acc_BalanceDetails::getQuery();
        acc_BalanceDetails::filterQuery($dQuery, $balanceRec->id, '321');
        $positionId = acc_Lists::getPosition('321', 'cat_ProductAccRegIntf');
        
        // За всеки запис в баланса
        while ($dRec = $dQuery->fetch()) {
            $itemId = $dRec->{"ent{$positionId}Id"};
            if (!array_key_exists($itemId, $tmpArr)) {
                $tmpArr[$itemId] = new stdClass();
            }
            
            // Сумираме сумите и количествата
            if ($dRec->blQuantity >= 0) {
                $tmpArr[$itemId]->quantity += $dRec->blQuantity;
                $tmpArr[$itemId]->amount += $dRec->blAmount;
            }
        }
        
        // Намираме цената
        foreach ($tmpArr as $index => $r) {
            $pId = acc_Items::fetchField($index, 'objectId');
            $amount = (!$r->quantity) ? 0 : round($r->amount / $r->quantity, 5);
            $r->quantity = (!$r->quantity) ? 0 : $r->quantity;
            $res[$pId] = (object)array('price' => $amount, 'quantity' => $r->quantity);
        }
       
        // Връщаме резултатите
        return $res;
    }
    
    
    /**
     * Връща всички покупки, в които участват подадените артикули.
     * Покупките са подредени в низходящ ред, така най-първите са последните.
     *
     * @param array $productKeys  - масив с ид-та на артикули
     * @param bool  $withDelivery - дали да има доставено по покупката или не
     * @param bool  $onlyActive   - дали да търси само по активните покупки
     *
     * @return array $res           - намерените последни доставни цени
     */
    private function getPurchasesWithProducts($productKeys, $withDelivery = false, $onlyActive = false)
    {
        $pQuery = purchase_PurchasesDetails::getQuery();
        $pQuery->EXT('state', 'purchase_Purchases', 'externalName=state,externalKey=requestId');
        $pQuery->EXT('valior', 'purchase_Purchases', 'externalName=valior,externalKey=requestId');
        $pQuery->EXT('modifiedOn', 'purchase_Purchases', 'externalName=modifiedOn,externalKey=requestId');
        $pQuery->EXT('amountDelivered', 'purchase_Purchases', 'externalName=amountDelivered,externalKey=requestId');
        
        // Всички активни
        if ($onlyActive === true) {
            $pQuery->where("#state = 'active'");
        } else {
            $pQuery->where("#state = 'active' OR #state = 'closed'");
        }
        
        // и тези които са затворени и са последно модифицирани до два часа
        $from = dt::addSecs(-2 * 60 * 60, dt::now());
        $pQuery->orWhere("#state = 'closed' AND #modifiedOn >= '{$from}'");
        
        if ($withDelivery === true) {
            $pQuery->EXT('threadId', 'purchase_Purchases', 'externalName=threadId,externalKey=requestId');
            $pQuery->EXT('containerId', 'purchase_Purchases', 'externalName=containerId,externalKey=requestId');
            $pQuery->where('#amountDelivered IS NOT NULL AND #amountDelivered != 0');
            $pQuery->show('price,productId,threadId,requestId,containerId,quantity');
        } else {
            $pQuery->where('#amountDelivered IS NULL OR #amountDelivered = 0');
            $pQuery->show('price,productId,requestId,quantity');
        }
        
        $pQuery->in('productId', $productKeys);
        $pQuery->orderBy('valior,id', 'DESC');
        
        // Връщаме намерените резултати
        return $pQuery->fetchAll();
    }
    
    
    /**
     * Връща последните доставни цени на подадените артикули
     *
     * @param array $productKeys - масив с ид-та на артикули
     *
     * @return array $res - намерените последни доставни цени
     */
    private function getDeliveryCosts($productKeys)
    {
        $res = array();
        
        // Намираме всички покупки с доставка
        $allPurchases = $this->getPurchasesWithProducts($productKeys, true, false);
        
        // Тук ще кешираме доставените артикули във всяка
        $purchaseProducts = array();
        
        // За всяка
        foreach ($allPurchases as $purRec) {
            
            // Ако няма цена за артикула, взимаме първата срещната, така винаги на артикула
            // ще му съответства последната доставна цена, другите записи ще се пропуснат
            if (!isset($res[$purRec->productId])) {
                
                // Ако няма кеширана информация за доставеното по сделката кешираме го
                if (!isset($purchaseProducts[$purRec->requestId])) {
                    
                    // Намираме всички записи от журнала по покупката
                    $entries = purchase_transaction_Purchase::getEntries($purRec->requestId);
                    
                    // Към тях търсим всички документи от вида "Корекция на стойности", които са
                    // в нишката на покупката и са по друга сделка. Понеже в тяхната контировка не участва
                    // перото на текущата сделка, и 'purchase_transaction_Purchase::getEntries' не може
                    // да им вземе записите, затова ги добавяме ръчно
                    $aExpensesQuery = acc_ValueCorrections::getQuery();
                    $aExpensesQuery->where("#threadId = {$purRec->threadId} AND #state = 'active' AND #correspondingDealOriginId != {$purRec->containerId}");
                    $aExpensesQuery->show('id');
                    
                    // За всеки документ "Корекция на стойности" в нишката
                    while ($aRec = $aExpensesQuery->fetch()) {
                        
                        // Намираме записите от журнала
                        $jRec = acc_Journal::fetchByDoc('acc_ValueCorrections', $aRec->id);
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
                    $purchaseProducts[$purRec->requestId] = purchase_transaction_Purchase::getShippedProducts($entries, $purRec->requestId, '321,302,601,602,60010,60020,60201', false, false);
                    
                    // Добавяне и на разпределените разходи, ако има
                    foreach ($purchaseProducts[$purRec->requestId] as $o1) {
                        $itemId = acc_Items::fetchItem('cat_Products', $o1->productId)->id;
                        $amount = acc_Balances::getBlAmounts($entries, '321', 'debit', '60201', array(null, $itemId, null))->amount;
                        $val = (empty($o1->quantity)) ? 0 : ($amount / $o1->quantity);
                        $o1->price += $val;
                    }
                }
                
                // Намираме какво е експедирано по сделката
                $shippedProducts = $purchaseProducts[$purRec->requestId];
                
                // Взимаме цената на продукта по тази сделка
                $price = $shippedProducts[$purRec->productId]->price;
                if (isset($price)) {
                    $price = round($price, 5);
                    
                    $res[$purRec->productId] = (object) array('documentId' => $purRec->requestId,
                        'price' => $price);
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
     *
     * @return array $res - намерените цените по последна активна поръчка
     */
    private function getActiveDeliveryCosts($productKeys)
    {
        $res = array();
        
        // Намираме всички покупки по, които няма доставени
        $allPurchases = $this->getPurchasesWithProducts($productKeys, false, true);
        
        // За всяка покупка
        foreach ($allPurchases as $purRec) {
            
            // Намираме първата срещната цена за артикула, покупките са подредени по-последно
            // създаване, така сме сигурни че ще се вземе първата срещната цена, която е цената по
            // последна активна поръчка
            if (!isset($res[$purRec->productId])) {
                $res[$purRec->productId] = (object) array('documentId' => $purRec->requestId, 
                                                          'quantity' => $purRec->quantity, 
                                                          'price' => round($purRec->price, 5));
            };
        }
        
        // Връщаме намерените цени
        return $res;
    }
    
    
    /**
     * Намира цените от последната активна оферта
     *
     * @param array $productKeys - масив с ид-та на артикули
     *
     * @return array $res - намерените цените по последна активна оферта
     *
     * @todo да се реализира когато станат готови входящите оферти
     */
    private function getLastQuoteCosts($productKeys)
    {
        $res = array();
        
        // Намираме всички активни оферти с подадените артикули
        /*$qQuery = sales_QuotationsDetails::getQuery();
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
    	}*/
        
        // Връщаме намерените цени
        return $res;
    }
    
    
    /**
     * Намира цените от последната активна рецепта
     *
     * @param array $productKeys - масив с ид-та на артикули
     *
     * @return array $res - намерените цените по последна рецепта
     */
    private function getLastBomCosts($productKeys)
    {
        $res = array();
        $cache = array();
        $now = dt::now();
        
        // За всеки артикул
        foreach ($productKeys as $productId) {
            
            // Търсим му рецептата
            if ($bomRec = cat_Products::getLastActiveBom($productId)) {
                if (!isset($cache[$bomRec->id])) {
                    
                    // Ако има, намираме и цената
                    $t = ($bomRec->quantityForPrice) ? $bomRec->quantityForPrice : $bomRec->quantity;
                    $cache[$bomRec->id] = cat_Boms::getBomPrice($bomRec, $t, 0, 0, $now, price_ListRules::PRICE_LIST_COST);
                }
                
                $primeCost = $cache[$bomRec->id];
                if ($primeCost) {
                    $res[$productId] = (object) array('documentId' => $bomRec->id,
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
    public function cron_CachePrices()
    {
        core_App::setTimeLimit(360);
        
        // Намираме всички публични,активни,складируеми и купуваеми или производими артикули
        $products = array();
        $pQuery = cat_Products::getQuery();
        $pQuery->where("#isPublic = 'yes'");
        $pQuery->where("#state = 'active'");
        $pQuery->where("#canStore = 'yes'");
        $pQuery->where("#canBuy = 'yes' OR #canManifacture = 'yes'");
        $pQuery->show('id');
        
        // За всеки от тях
        while ($pRec = $pQuery->fetch()) {
            $products[$pRec->id] = $pRec->id;
        }
        
        $productKeys = array_combine($products, $products);
        
        // Тук ще събираме себестойностите
        $res = array();
        
        // Намираме счетоводните им себестойности
        $res['accCost'] = $this->getAccCosts();
        
        // Намираме цените по текуща поръчка
        $res['activeDelivery'] = $this->getActiveDeliveryCosts($productKeys);
        
        // Намираме цените по последна доставка
        $res['lastDelivery'] = $this->getDeliveryCosts($productKeys);
        
        // Намираме цените по последна оферта
        $res['lastQuote'] = $this->getLastQuoteCosts($productKeys);
        
        // Намираме цените по последна рецепта
        $res['bom'] = $this->getLastBomCosts($productKeys);
        
        // Намиране на средните цени
        $res['average'] = $this->getAveragePrices($productKeys, $res['accCost']);
        
        // Тук ще събираме готовите записи
        $nRes = array();
        $today = dt::now();
        
        // Нормализираме записите
        foreach ($products as $productId => $productName) {
            $bObject = (object) array('productId' => $productId, 'modifiedOn' => $today);
            
            if (isset($res['accCost'][$productId])) {
                $obj = clone $bObject;
                $obj->type = 'accCost';
                $obj->price = $res['accCost'][$productId]->price;
                $obj->quantity = $res['accCost'][$productId]->quantity;
                $nRes[] = $obj;
            }
            
            if (isset($res['lastQuote'][$productId])) {
                $obj = clone $bObject;
                $obj->type = 'lastQuote';
                $obj->price = $res['lastQuote'][$productId]->price;
                $obj->documentClassId = purchase_Offers::getClassId();
                $obj->documentId = $res['lastQuote'][$productId]->documentId;
                $nRes[] = $obj;
            }
            
            if (isset($res['activeDelivery'][$productId])) {
                $obj = clone $bObject;
                $obj->type = 'activeDelivery';
                $obj->price = $res['activeDelivery'][$productId]->price;
                $obj->quantity = $res['activeDelivery'][$productId]->quantity;
                $obj->documentClassId = purchase_Purchases::getClassId();
                $obj->documentId = $res['activeDelivery'][$productId]->documentId;
                $nRes[] = $obj;
            }
            
            if (isset($res['lastDelivery'][$productId])) {
                $obj = clone $bObject;
                $obj->type = 'lastDelivery';
                $obj->price = $res['lastDelivery'][$productId]->price;
                $obj->quantity = $res['lastDelivery'][$productId]->quantity;
                $obj->documentClassId = purchase_Purchases::getClassId();
                $obj->documentId = $res['lastDelivery'][$productId]->documentId;
                $nRes[] = $obj;
            }
            
            if (isset($res['average'][$productId])) {
                $obj = clone $bObject;
                $obj->type = 'average';
                $obj->price = $res['average'][$productId]->price;
                $obj->quantity = $res['average'][$productId]->quantity;
                $nRes[] = $obj;
            }
            
            if (isset($res['bom'][$productId])) {
                $obj = clone $bObject;
                $obj->type = 'bom';
                $obj->price = $res['bom'][$productId]->price;
                $obj->quantity = 1;
                $obj->documentClassId = cat_Boms::getClassId();
                $obj->documentId = $res['bom'][$productId]->documentId;
                $nRes[] = $obj;
            }
        }
        
        // Намираме старите записи
        $query = static::getQuery();
        $oldRecs = $query->fetchAll();
        
        // Синхронизираме новите със старите
        $synced = arr::syncArrays($nRes, $oldRecs, 'productId,type', 'price,documentClassId,documentId,quantity');
        
        // Създаваме записите, които трябва
        $this->saveArray($synced['insert']);
        
        // Обновяваме записите със промени
        $this->saveArray($synced['update']);
        
        if (countR($synced['delete'])) {
            $query = self::getQuery();
            $query->in('id', $synced['delete']);
            $query->show('type');
            $arr = $query->fetchAll();
           
            foreach ($synced['delete'] as $id) {
                if($arr[$id]->type == 'average') continue;
                $this->delete($id);
            }
        }
    }
    
    
    /**
     * Намира себестойността на артикула по вида
     *
     * @param int    $productId - ид на артикула
     * @param string $priceType - вида на цената
     *
     * @return float $price     - намерената себестойност
     */
    public static function getPrice($productId, $priceType)
    {
        expect($productId);
        expect(in_array($priceType, array('accCost', 'lastDelivery', 'activeDelivery', 'lastQuote', 'bom', 'average')));
        $price = static::fetchField("#productId = {$productId} AND #type = '{$priceType}'", 'price');
        
        return $price;
    }
    
    
    /**
     * Средната доставна цена
     * 
     * @param array $productKeys
     * @param array $accCosts
     * @return stdClass $res
     */
    public function getAveragePrices($productKeys, $accCosts)
    {
        // Кои пера са участвали в дебитирането на склада в последните 3 месеца
        $beforeDate = dt::addMonths(-3);
        $jQuery = acc_JournalDetails::getQuery();
        $jQuery->EXT('valior', 'acc_Journal', 'externalKey=journalId');
        $storeAccId = acc_Accounts::getRecBySystemId('321')->id;
        $jQuery->where("#debitAccId = {$storeAccId} AND #valior >= '{$beforeDate}'");
        $jQuery->show('debitItem2');
        $jQuery->groupBy('debitItem2');
        $itemsWithMovement = arr::extractValuesFromArray($jQuery->fetchAll(), 'debitItem2');
        
        // Кои ид-та на артикули, съответстват на тези пера
        $iQuery = acc_Items::getQuery();
        $iQuery->where("#state = 'active' AND #classId=" . cat_Products::getClassId());
        $iQuery->in("id", $itemsWithMovement);
        $iQuery->in("objectId", $productKeys);
        $iQuery->show('id,objectId');
        $productArr = arr::extractValuesFromArray($iQuery->fetchAll(), 'objectId');
        
        // Извличане на данните за покупки за тези артикули
        $valiorFrom = dt::verbal2mysql(dt::addMonths(-12), false);
        
        $res = array();
        $purQuery = purchase_PurchasesData::getQuery();
        $purQuery->in('productId', $productArr);
        $purQuery->where("#state != 'rejected' AND #valior >= '{$valiorFrom}'");
        $purQuery->show('quantity,price,productId,expenses');
        $purQuery->orderBy('valior,id', "DESC");
        $all = $purQuery->fetchAll();
        
        $groupedArr = array();
        foreach($all as $purRec) {
            $groupedArr[$purRec->productId][] = $purRec;
        }
        
        // Нормализираме записите
        foreach ($productKeys as $productId) {
            
            // Всички покупки на търсения артикул
            $accObject = $accCosts[$productId];
            $foundIn = array_key_exists($productId, $groupedArr) ? $groupedArr[$productId] : array();
            
            $useFirstPurchase = true;
            $averageAmount = 0;
            
            // Ако има положителна наличност
            if(!empty($accObject->quantity)){
                if($accObject->quantity > 0){
                    $useFirstPurchase = false;
                    $availableQuantity = $accObject->quantity;
                    $sum = $quantityByNow = 0;
                    
                    // За всяка покупка от последната към първата
                    foreach ($foundIn as $delData){
                        $delData->quantity = round($delData->quantity, 6);
                        $expensesPerPcs = (!empty($delData->quantity)) ? ($delData->expenses / $delData->quantity) : 0;
                        
                        $quantityByNow += $delData->quantity;
                        if($delData->quantity <= $availableQuantity){
                            $quantity = $delData->quantity;
                        } else {
                            $quantity = $availableQuantity;
                        }
                        
                        $availableQuantity -= $quantity;
                        $sum += $quantity * ($delData->price + $expensesPerPcs);
                        
                        if($availableQuantity <= 0) break;
                    }
                    
                    // Изчисляване на колко е средната
                    if($quantityByNow){
                        $delimeter = min($quantityByNow, $accObject->quantity);
                    } else {
                        $delimeter = $accObject->quantity;
                    }
                    
                    $averageAmount = @round($sum / $delimeter, 4);
                    $averageAmount = core_Math::roundNumber($averageAmount);
                }
            }
            
            // Ако има НЕ положителна наличност, но има покупки, взима се цената от първата + разходите към нея
            if($useFirstPurchase === true && !empty($foundIn)){
                $foundDelRec =  $foundIn[key($foundIn)];
                $foundDelRec->quantity = round($foundDelRec->quantity, 6);
                $expensesPerPcs = (!empty($foundDelRec->quantity)) ? ($foundDelRec->expenses / $foundDelRec->quantity) : 0;
                
                $foundIn = $foundIn[key($foundIn)];
                $averageAmount = $foundIn->price + $expensesPerPcs;
            }
            
            // Искаме средната цена да е Не нулева
            $res[$productId] = (object) array('price' => $averageAmount, 'quantity' => $accObject->quantity);
        }
        
        return $res;
    }
}
