<?php


/**
 * Мениджър на отчети за залежали артикули
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Склад » Залежали артикули
 */
class store_reports_ArticlesDepended extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, cat';


    /**
     * Показателите на справката, в реда на обработката
     */
    protected static $statCaptions = array(
        'storeProducts' => 'Записи от наличностите',
        'products' => 'Различни артикули',
        'quantities' => 'С прочетено количество',
        'withoutPrimeCost' => 'Без себестойност (пропуснати)',
        'belowMinCost' => 'Под мин. стойност (пропуснати)',
        'withPrimeCost' => 'Остават след себестойност',
        'soonDelivered' => 'Със скорошна доставка (пропуснати)',
        'journal' => 'Групи от журнала',
        'items' => 'Прочетени пера',
        'aboveReversibility' => 'Над обръщаемостта (пропуснати)',
        'rows' => 'Редове в справката',
        'memory' => 'Пикова памет (MB)',
        'total' => 'Общо',
    );


    /**
     * Кои полета от листовия изглед да може да се сортират
     *
     * @var int
     */
    protected $sortableListFields;

    /**
     * Кои полета от таблицата в справката да се сумират в обобщаващия ред
     *
     * @var int
     */
    protected $summaryListFields;


    /**
     * Как да се казва обобщаващия ред. За да се покаже трябва да е зададено $summaryListFields
     *
     * @var int
     */
    protected $summaryRowCaption = 'ОБЩО';


    /**
     * Коя комбинация от полета от $data->recs да се следи, ако има промяна в последната версия
     *
     * @var string
     */
    protected $newFieldsToCheck;


    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField;


    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields;


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,placeholderType=all,after=title,single=none');
        $fieldset->FLD('period', 'time(suggestions=1 месец|3 месеца|6 месеца|1 година)', 'caption=Период, after=storeId,mandatory,single=none,removeAndRefreshForm');
        $fieldset->FLD('minCost', 'double', 'caption=Мин. наличност, after=period,single=none,unit=' . acc_Periods::getBaseCurrencyCode());
        $fieldset->FLD('reversibility', 'percent(suggestions=1%|5% |10%|20%)', 'caption=Обращаемост, after=minCost,mandatory,single=none');


        if (BGERP_GIT_BRANCH == 'dev') {
            $fieldset->FLD('groups', 'keylist(mvc=cat_Groups,select=name, parentId=parentId)', 'caption=Артикули->Група артикули,placeholder = Всички,after=reversibility,single=none');
        } else {
            $fieldset->FLD('groups', 'treelist(mvc=cat_Groups,select=name, parentId=parentId)', 'caption=Артикули->Група артикули,placeholder = Всички,after=reversibility,single=none');
        }
        //Подредба на резултатите
        $fieldset->FLD('orderBy', 'enum(name=Артикул, reversibility=Обращаемост,storeAmount=Стойност,storeQuantity=Количество,code=Код)', 'caption=Подреждане по,after=reversibility');

        $fieldset->FLD('soonPeriod', 'time(suggestions=1 месец|3 месеца|6 месеца|1 година)', 'caption=Последни доставки/производства->Период, after=orderBy,mandatory,single=none');
        $fieldset->FLD('soonQuantity', 'percent(suggestions=10%|20% |30%|50%)', 'caption=Последни доставки/производства->Количество,unit=от наличното, after=soonPeriod,single=none');

        $fieldset->FNC('from', 'date', 'caption=Период->От,after=title,single=none,input = hidden');
        $fieldset->FNC('to', 'date', 'caption=Период->До,after=from,single=none,input = hidden');
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager $Embedder
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
    {
        if ($form->isSubmitted()) {
            if (($form->rec->minCost ?? 0) < 0) {
                $form->setError('minCost', 'Наличността трябва да е положително число.');
            }

            if (($form->rec->period ?? 0) < ($form->rec->soonPeriod ?? 0)) {
                $form->setError('period,soonPeriod', 'Краткият период не може да бъде по-голям от общия период на справката');
            }
        }
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager $Embedder
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;

        $Time = cls::get('type_Time');

        $form->setDefault('orderBy', 'name');
        $form->setDefault('period', '6 месеца');
        $form->setDefault('minCost', 1000);
        $form->setDefault('reversibility', 0.1);

        $form->input('period');

        $periodSec = ($Time->fromVerbal($rec->period));

        $shortPeriod = $Time->toVerbal($periodSec / 2);

        $form->setDefault('soonQuantity', 0.5);
        $form->setDefault('soonPeriod', "{$shortPeriod}");
    }


    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec
     * @param stdClass $data
     *
     * @return array
     */
    protected function prepareRecs($rec, &$data = null)
    {
        $recs = array();
        $startedOn = microtime(true);

        /** @var core_Query $pQuery */
        $pQuery = store_Products::getQuery();

        $pQuery->where("#state != 'rejected'");
        $pQuery->where('#quantity > 0');
        if (!empty($rec->storeId)) {
            $pQuery->where("#storeId = {$rec->storeId}");
        }

        $pQuery->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');
        $pQuery->show('productId,storeId,quantity,code');

        //Филтър по група артикули
        if (isset($rec->groups)) {
            plg_ExpandInput::applyExtendedInputSearch('cat_Products', $pQuery, $rec->groups, 'productId');
        }

        $timer = microtime(true);
        $storeCount = $pQuery->selectOnReplica();
        // Лимитът се увеличава преди извличането и обработката на редовете.
        $timeLimit = $storeCount * 0.2;
        if ($timeLimit >= 30) {
            core_App::setTimeLimit($timeLimit);
        }
        $storeProductRecs = $productIds = array();
        while ($pRec = $pQuery->fetch()) {
            $storeProductRecs[] = $pRec;
            $productId = $pRec->productId ?? null;
            $productIds[$productId] = $productId;
        }
        self::addReportStat($data, 'storeProducts', microtime(true) - $timer, countR($storeProductRecs));
        self::addReportStat($data, 'products', 0, countR($productIds));

        // Артикулите и наличностите им се четат наведнъж, вместо за всеки ред поотделно
        $timer = microtime(true);
        $products = $this->preloadProducts($productIds);
        $quantities = $this->getProductQuantities($productIds, $rec->storeId ?? null);
        self::addReportStat($data, 'quantities', microtime(true) - $timer, countR($quantities));

        $prodArr = $notSelfPrice = $belowMinCost = array();
        $primeCosts = $missingPrices = $publicPrices = array();
        $minCost = $rec->minCost ?? 0;

        // Изключва преизчисляването на параметрите - иначе драйверът ги преизчислява и записва
        Mode::push('doNotCalculate', true);
        $timer = microtime(true);

        try {
            foreach ($storeProductRecs as $pRec) {
                $productId = $pRec->productId ?? null;
                $quantity = $pRec->quantity ?? 0;
                $product = $products[$productId] ?? null;
                // За стандартните артикули тази политика има предимство пред драйвера.
                if (($product->isPublic ?? null) == 'yes' && !array_key_exists($productId, $publicPrices)) {
                    $publicPrices[$productId] = price_ListRules::getPrice(price_ListRules::PRICE_LIST_COST, $productId);
                }
                // Драйверът може да върне различна цена за различно количество.
                $priceKey = $productId . '|' . serialize($quantity);
                if (!array_key_exists($priceKey, $primeCosts)) {
                    $primeCosts[$priceKey] = $publicPrices[$productId] ?? cat_Products::getPrimeCost($product ?? $productId, null, $quantity, null);
                }
                $pRec->reportPrimeCost = $primeCosts[$priceKey];
                if (empty($pRec->reportPrimeCost)) {
                    $missingPrices[$productId] = $productId;
                }
            }
            $fallbackPrices = $this->getFallbackPrices($missingPrices, $rec->storeId ?? null);

            foreach ($storeProductRecs as $pRec) {
                $productId = $pRec->productId ?? null;
                $selfPrice = ($pRec->reportPrimeCost ?? null) ?: ($fallbackPrices[$productId] ?? null);

                if (!$selfPrice) {
                    $notSelfPrice[$productId] = $productId;
                    continue;
                }
                $pQuantity = $quantities[$productId] ?? 0;
                $amount = $pQuantity * $selfPrice;
                $code = !empty($pRec->code) ? $pRec->code : 'Art' . $productId;

                if ($amount <= $minCost) {
                    $belowMinCost[$productId] = $productId;
                }

                if ($amount > $minCost) {

                    //Налични артикули на склад
                    $prodArr[$productId] = (object)array(

                        'productId' => $productId,                //Id на артикула
                        'selfPrice' => $selfPrice,                      //себестойност на артикула
                        'pQuantity' => $pQuantity,                      //Складова наличност: количество
                        'amount' => $amount,                            //Складова наличност: стойност
                        'code' => $code,                                //код на артикула

                    );
                }
            }
        } finally {
            Mode::pop('doNotCalculate');
        }

        $notSelfPrice = array_values($notSelfPrice);
        unset($storeProductRecs, $primeCosts, $fallbackPrices, $publicPrices);

        self::addReportStat($data, 'withoutPrimeCost', 0, countR($notSelfPrice), $notSelfPrice);
        self::addReportStat($data, 'belowMinCost', 0, countR($belowMinCost), $belowMinCost);
        self::addReportStat($data, 'withPrimeCost', microtime(true) - $timer, countR($prodArr));

        //Изключване на артикули, които имат скорошна доставка или производство
        $timer = microtime(true);
        $beforeSoon = countR($prodArr);
        $prodArr = self::removeSoonDeliveredProds($rec, $prodArr);
        self::addReportStat($data, 'soonDelivered', microtime(true) - $timer, $beforeSoon - countR($prodArr));

        $rec->from = $startDate = dt::addSecs(-($rec->period ?? 0), dt::now());
        $rec->to = dt::today();
        $journalProdArr = array();
        if (countR($prodArr)) {
            // Кредитните количества от записи със сметка 321 за периода.

            $docTypeIdArr = array();
            foreach (array('sales_Sales', 'store_ShipmentOrders', 'planning_DirectProductionNote', 'planning_ConsumptionNotes') as $val) {
                $docTypeIdArr[] = (core_Classes::getId($val));
            }

            $accountRec = acc_Accounts::getRecBySystemId('321');
            $acc321 = $accountRec->id ?? null;

            /** @var core_Query $query */
            $query = acc_JournalDetails::getQuery();
            acc_JournalDetails::filterQuery($query, $startDate, dt::now(), null, null, null, null, null, null, $documents = $docTypeIdArr);

            // Четат се само оборотите на останалите кандидати.
            $query->EXT('reportProductId', 'acc_Items', 'externalName=objectId,externalKey=creditItem2');
            $query->in('reportProductId', array_keys($prodArr));
            if (!empty($rec->storeId)) {
                $query->EXT('reportStoreId', 'acc_Items', 'externalName=objectId,externalKey=creditItem1');
                $query->where(array('#reportStoreId = [#1#]', $rec->storeId));
            }

            // Двата клона не се застъпват, за да не се броят по два пъти записите с 321 от двете страни
            $query->setUnion("#debitAccId = {$acc321}");
            $query->setUnion("#creditAccId = {$acc321} AND (#debitAccId IS NULL OR #debitAccId != {$acc321})");
            $query->useUnionAll = true;
            // Редовете без перо на артикул се пропускат и в РНР, затова не се и вадят
            $query->where('#creditItem2 IS NOT NULL');

            // Сумирането е в базата - иначе всички редове на журнала минават един по един през РНР
            $query->XPR('creditQuantitySum', 'double', 'SUM(#creditQuantity)');
            $query->groupBy('creditItem1,creditItem2');
            $query->show('creditItem1,creditItem2,creditQuantitySum');
            $timer = microtime(true);
            $query->selectOnReplica();

            // Групирането е поотделно във всеки клон на обединението, затова сумите се сливат тук
            $quantityByItems = $itemIds = array();
            while ($jRec = $query->fetch()) {
                if (empty($jRec->creditItem2)) continue;

                $storeItemId = $jRec->creditItem1 ?? null;
                $productItemId = $jRec->creditItem2 ?? null;
                $key = $storeItemId . '|' . $productItemId;
                $quantityByItems[$key] = ($quantityByItems[$key] ?? 0) + ($jRec->creditQuantitySum ?? 0);

                $itemIds[$productItemId] = $productItemId;
                if ($storeItemId) {
                    $itemIds[$storeItemId] = $storeItemId;
                }
            }

            self::addReportStat($data, 'journal', microtime(true) - $timer, countR($quantityByItems));

            // Перата се четат наведнъж, вместо по две на всеки запис
            $timer = microtime(true);
            $items = $this->getAccItems($itemIds);
            self::addReportStat($data, 'items', microtime(true) - $timer, countR($items));

            foreach ($quantityByItems as $key => $creditQuantity) {
                list($storeItemId, $productItemId) = explode('|', $key);

                $productItem = $items[$productItemId] ?? null;
                $storeItem = $items[$storeItemId] ?? null;
                if (!$productItem || !$storeItem) {
                    continue;
                }
                $productId = $productItem->objectId ?? null;
                $storeId = $storeItem->objectId ?? null;

                //Филтър по склад
                if (!empty($rec->storeId) && ($storeId != $rec->storeId)) {
                    continue;
                }

                // Кредитните количества се обединяват по артикул.
                $journalProdArr[$productId] = ($journalProdArr[$productId] ?? 0) + $creditQuantity;
            }

        } else {
            self::addReportStat($data, 'journal', 0, 0);
            self::addReportStat($data, 'items', 0, 0);
        }

        $aboveReversibility = array();
        foreach ($prodArr as $prod) {

            $id = $prod->productId;

            $totalCreditQuantity = $journalProdArr[$prod->productId] ?? 0;
            $reversibility = $prod->pQuantity ? $totalCreditQuantity / $prod->pQuantity : 0;

            if ($reversibility > ($rec->reversibility ?? 0)) {
                $aboveReversibility[$id] = $id;
                continue;
            }

            $storeQuantity = $prod->pQuantity;
            $storeAmount = $prod->amount;
            // Запис в масива
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object)array(

                    'productId' => $prod->productId,                            //Id на артикула
                    'code' => $prod->code,                                      //код на артикула
                    'name' => cat_Products::getTitleById($prod->productId),     //Име на артикула
                    'storeQuantity' => $storeQuantity,                          //Складова наличност: количество
                    'storeAmount' => $storeAmount,                              //Складова наличност: стойност
                    'totalCreditQuantity' => $totalCreditQuantity,              //Кредит обороти
                    'reversibility' => $reversibility                           //Обръщаемост

                );
            }
        }

        //Подредба на резултатите
        if (!is_null($recs)) {
            $orderBy = $rec->orderBy ?? 'name';
            $typeOrder = ($orderBy == 'name' || $orderBy == 'code') ? 'stri' : 'native';

            $order = in_array($orderBy, array('reversibility', 'name', 'code')) ? 'ASC' : 'DESC';

            arr::sortObjects($recs, $orderBy, $order, $typeOrder);
        }

        self::addReportStat($data, 'aboveReversibility', 0, countR($aboveReversibility), $aboveReversibility);
        self::addReportStat($data, 'rows', 0, countR($recs));
        self::addReportStat($data, 'memory', 0, round(memory_get_peak_usage(true) / 1048576));
        self::addReportStat($data, 'total', microtime(true) - $startedOn, countR($recs));
        $statsMsg = $this->getReportStatsMsg($data, ', ');
        if (!empty($statsMsg)) {
            $this->logWhilePreparing($statsMsg);
        }

        $recs['self'] = (object)array('info' => true, 'array' => $notSelfPrice);

        return $recs;
    }


    /**
     * Заместващите цени се четат наведнъж само за артикулите без себестойност.
     */
    private function getFallbackPrices($productIds, $storeId = null)
    {
        $prices = array_fill_keys($productIds, null);
        if (!countR($productIds)) return $prices;

        /** @var core_Query $query */
        $query = acc_Items::getQuery();
        $query->where(array('#classId = [#1#]', cat_Products::getClassId()));
        $query->in('objectId', $productIds);
        $query->show('id,objectId');
        $query->selectOnReplica();
        $itemMap = array();
        while ($item = $query->fetch()) {
            $itemMap[$item->id ?? null] = $item->objectId ?? null;
        }

        $storeItems = null;
        if (!empty($storeId)) {
            $storeItem = acc_Items::fetchItem('store_Stores', $storeId);
            $storeItems = !empty($storeItem->id) ? array($storeItem->id) : array();
        }
        if (countR($itemMap) && (empty($storeId) || countR($storeItems))) {
            // Същото осредняване и закръгляне като getWacAmountInStore(1, ...).
            // getPricesToDate() приема MySQL низ, въпреки PHPDoc типа datetime.
            /** @noinspection PhpParamsInspection */
            $stockPrices = acc_ProductPricePerPeriods::getPricesToDate(dt::getLastDayOfMonth(dt::today()), array_keys($itemMap), $storeItems);
            $sums = $counts = array();
            foreach ($stockPrices as $stockPrice) {
                $productId = $itemMap[$stockPrice->productItemId ?? null] ?? null;
                if (!$productId) continue;
                $sums[$productId] = ($sums[$productId] ?? 0) + ($stockPrice->price ?? 0);
                $counts[$productId] = ($counts[$productId] ?? 0) + 1;
            }
            foreach ($sums as $productId => $sum) {
                $price = round($sum / $counts[$productId], 4);
                if ($price > 0) $prices[$productId] = $price;
            }
        }

        $missing = array_keys(array_filter($prices, function ($price) { return $price === null; }));
        if (!countR($missing)) return $prices;

        /** @var core_Query $query */
        $query = purchase_PurchasesDetails::getQuery();
        $query->EXT('purchaseState', 'purchase_Purchases', 'externalName=state,externalKey=requestId');
        $query->EXT('valior', 'purchase_Purchases', 'externalName=valior,externalKey=requestId');
        $query->in('productId', $missing);
        $query->in('purchaseState', array('active', 'closed'));
        $query->orderBy('valior,requestId,id', 'DESC');
        $query->show('productId,price,discount,valior');
        $query->selectOnReplica();
        $seen = array();
        while ($purchaseRec = $query->fetch()) {
            $productId = $purchaseRec->productId ?? null;
            if (isset($seen[$productId])) continue;
            // Нулева цена в последната поръчка не се заменя с цена от по-стара.
            $seen[$productId] = true;
            $price = ($purchaseRec->price ?? 0) * (1 - ($purchaseRec->discount ?? 0));
            $price = deals_Helper::getSmartBaseCurrency($price, $purchaseRec->valior ?? dt::today());
            if ($price > 0) $prices[$productId] = $price;
            if (countR($seen) == countR($missing)) break;
        }

        return $prices;
    }


    /**
     * Артикулите наведнъж, за да не се четат един по един
     *
     * @param array $productIds
     *
     * @return array - ид на артикул => запис
     */
    private function preloadProducts($productIds)
    {
        $res = array();
        if (!countR($productIds)) return $res;

        // Нарочно от основната база - записите се подават на getPrimeCost() вместо ид-та,
        // затова трябва да съдържат същото, което би върнал fetchRec() за всеки артикул
        $Products = cls::get('cat_Products');
        /** @var core_Query $pQuery */
        $pQuery = cat_Products::getQuery();
        $pQuery->in('id', $productIds);

        while ($pRec = $pQuery->fetch()) {
            $res[$pRec->id] = $pRec;

            // Ключът е същият, който ползва core_Query::fetchAndCache()
            $Products->_cachedRecords[$pRec->id . '|*'] = $pRec;
        }

        return $res;
    }


    /**
     * Наличните количества по артикули, както ги връща store_Products::getQuantities()
     *
     * @param array $productIds
     * @param int|null $storeId
     *
     * @return array - ид на артикул => количество
     */
    private function getProductQuantities($productIds, $storeId)
    {
        $res = array();
        if (!countR($productIds)) return $res;

        /** @var core_Query $query */
        $query = store_Products::getQuery();
        $query->in('productId', $productIds);
        if (!empty($storeId)) {
            $query->in('storeId', array($storeId));
        }
        $query->XPR('quantityTotal', 'double', 'SUM(#quantity)');
        $query->groupBy('productId');
        $query->show('productId,quantityTotal');
        $query->selectOnReplica();

        while ($qRec = $query->fetch()) {
            $res[$qRec->productId] = $qRec->quantityTotal ?? 0;
        }

        return $res;
    }


    /**
     * Перата по ид
     *
     * @param array $itemIds
     *
     * @return array - ид на перо => запис
     */
    private function getAccItems($itemIds)
    {
        $res = array();
        if (!countR($itemIds)) return $res;

        /** @var core_Query $query */
        $query = acc_Items::getQuery();
        $query->in('id', $itemIds);
        $query->show('id,objectId,classId');
        $query->selectOnReplica();

        while ($iRec = $query->fetch()) {
            $res[$iRec->id] = $iRec;
        }

        return $res;
    }


    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec - записа
     * @param bool $export - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');

        $fld->FLD('code', 'varchar', 'caption=Код,tdClass=centered');
        $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
        $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Наличност->Мярка,tdClass=centered');
        $fld->FLD('storeQuantity', 'double(smartRound,decimals=2)', 'smartCenter,caption=Наличност->Количество');
        $fld->FLD('storeAmount', 'double(smartRound,decimals=2)', 'smartCenter,caption=Наличност->Стойност');
        $fld->FLD('totalCreditQuantity', 'double(smartRound,decimals=2)', 'caption=Обороти');

        $fld->FLD('reversibility', 'percent', 'caption=Обращаемост');

        return $fld;
    }


    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec
     *                       - записа
     * @param stdClass $dRec
     *                       - чистия запис
     *
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;

        $row = new stdClass();

        if (!empty($dRec->info)) {
            $row->productId = '<b>' . 'Артикули без себестойност:' . '</b></br></br>';
            $i=0;
            foreach ($dRec->array as $val) {
                $i++;
                $row->productId = ($row->productId ?? '') . $i.'>>'.cat_Products::getVerbal($val, 'name') . '</br>';
            }

            return $row;
        }

        if (isset($dRec->code)) {
            $row->code = $dRec->code;
        }
        if (isset($dRec->productId)) {
            $row->productId = cat_Products::getVerbal($dRec->productId, 'name');
        }

        $measureId = cat_Products::fetchField($dRec->productId, 'measureId');
        $row->measure = cat_UoM::fetchField($measureId, 'shortName');


        if (isset($dRec->storeId)) {
            $row->storeId = store_Stores::getLinkToSingle_($dRec->storeId, 'name');
        }

        if (isset($dRec->storeQuantity)) {
            $row->storeQuantity = ht::styleNumber($Double->toVerbal($dRec->storeQuantity), $dRec->storeQuantity);
        }

        if (isset($dRec->storeAmount)) {
            $row->storeAmount = ht::styleNumber($Double->toVerbal($dRec->storeAmount), $dRec->storeAmount);
        }

        if (isset($dRec->totalCreditQuantity)) {
            $row->totalCreditQuantity = ht::styleNumber($Double->toVerbal($dRec->totalCreditQuantity), $dRec->totalCreditQuantity);
        }

        if (isset($dRec->reversibility)) {
            $row->reversibility = ht::styleNumber(core_Type::getByName('percent(decimals=2)')->toVerbal($dRec->reversibility), $dRec->reversibility);
        }

        return $row;
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager $Embedder
     * @param core_ET $tpl
     * @param stdClass $data
     */
    protected static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager $Embedder
     * @param core_ET $tpl
     * @param stdClass $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        $Date = cls::get('type_Date');
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        $currency = acc_Periods::getBaseCurrencyCode();

        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <!--ET_BEGIN from--><div>|От|*: [#from#]</div><!--ET_END from-->
                                        <!--ET_BEGIN to--><div>|До|*: [#to#]</div><!--ET_END to-->
                                        <!--ET_BEGIN storeId--><div>|Склад|*: [#storeId#]</div><!--ET_END storeId-->
                                        <!--ET_BEGIN groups--><div>|Групи продукти|*: [#groups#]</div><!--ET_END groups-->
                                        <!--ET_BEGIN minCost--><div>|Мин. наличност|*: [#minCost#] {$currency}</div><!--ET_END minCost-->
                                        <!--ET_BEGIN reversibility--><div>|Мин. обращаемост|*: [#reversibility#]</div><!--ET_END reversibility-->
                                    </div>
                                </fieldset><!--ET_END BLOCK-->"));
        if (isset($data->rec->from)) {
            $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->from) . '</b>', 'from');
        }

        if (isset($data->rec->to)) {
            $fieldTpl->append('<b>' . $Date->toVerbal($data->rec->to) . '</b>', 'to');
        }

        if ((isset($data->rec->storeId))) {
            $fieldTpl->append('<b>' . store_Stores::getTitleById($data->rec->storeId) . '</b>', 'storeId');
        }

        if ((isset($data->rec->minCost))) {
            $fieldTpl->append('<b>' . core_Type::getByName('double(smartRound,decimals=2)')->toVerbal($data->rec->minCost) . '</b>', 'minCost');
        }

        if ((isset($data->rec->reversibility))) {
            $fieldTpl->append('<b>' . core_Type::getByName('percent(smartRound,decimals=2)')->toVerbal($data->rec->reversibility) . '</b>', 'reversibility');
        }

        $marker = 0;
        if (isset($data->rec->groups)) {
            $groupVerb = '';
            foreach (type_Keylist::toArray($data->rec->groups) as $group) {
                $marker++;

                $groupVerb .= (cat_Groups::getTitleById($group));

                if ((countR((type_Keylist::toArray($data->rec->groups))) - $marker) != 0) {
                    $groupVerb .= ', ';
                }
            }

            $fieldTpl->append('<b>' . $groupVerb . '</b>', 'groups');
        } else {
            $fieldTpl->append('<b>' . 'Всички' . '</b>', 'groups');
        }

        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }


    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver
     * @param stdClass $res
     * @param stdClass $rec
     * @param stdClass $dRec
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {
    }


    /**
     * Кои артикули са произвеждани или доставени през периода soonPeriod в количество повече от soonQuantity
     *
     *
     * @param $prodArr - артикули на склад
     *
     * @return array
     */
    private static function removeSoonDeliveredProds($rec, $prodArr)
    {
        if (!countR($prodArr)) {
            return $prodArr;
        }

        //Проверка за доставени количества през периода soonPeriod
        /** @var core_Query $query */
        $query = purchase_PurchasesData::getQuery();

        $from = dt::addSecs(-($rec->soonPeriod ?? 0), dt::now());
        $query->where(array("#valior>= '[#1#]' AND #valior <= '[#2#]'", $from, dt::now()));
        $query->in('isFromInventory', array('no', 'false'));

        //Артикули, които имат наличности над минималната $extractProdArr
        $extractProdArr = array_keys($prodArr);
        $query->in('productId', $extractProdArr);

        $detClassesId = array();
        foreach (array('purchase_PurchasesDetails', 'store_ReceiptDetails', 'acc_ArticleDetails') as $val) {
            $detClassesId[] = core_Classes::getId($val);
        }

        $query->in('detailClassId', $detClassesId);
        $query->show('productId,quantity');
        $query->selectOnReplica();

        $deliveredProdInPeriod = array();
        while ($prod = $query->fetch()) {

            //Артикули които имат доставка през част от периода на стойност заложената част от скл. наличност
            if (!isset($prodArr[$prod->productId])) {
                continue;
            }
            $deliveredProdInPeriod[$prod->productId] = ($deliveredProdInPeriod[$prod->productId] ?? 0) + $prod->quantity * $prodArr[$prod->productId]->selfPrice;

        }

        foreach ($deliveredProdInPeriod as $key => $val) {

            if ($val > $prodArr[$key]->amount * ($rec->soonQuantity ?? 0)) {
                unset($prodArr[$key]);
            }
        }

        if (!countR($prodArr)) {
            return $prodArr;
        }
        $extractProdArr = array_keys($prodArr);

        //Произведени артикули
        /** @var core_Query $planningQuery */
        $planningQuery = planning_DirectProductionNote::getQuery();

        $planningQuery->where("#state = 'active'");

        $planningQuery->where(array("#valior>= '[#1#]' AND #valior <= '[#2#]'", $from, dt::now()));

        $planningQuery->in('productId', $extractProdArr);
        $planningQuery->show('productId,quantity');
        $planningQuery->selectOnReplica();

        $planningProdsInPeriod = array();
        while ($planningProd = $planningQuery->fetch()) {
            if (!isset($prodArr[$planningProd->productId])) {
                continue;
            }
            $planningProdsInPeriod[$planningProd->productId] = ($planningProdsInPeriod[$planningProd->productId] ?? 0) + $planningProd->quantity * $prodArr[$planningProd->productId]->selfPrice;
        }

        foreach ($planningProdsInPeriod as $key => $val) {

            if ($val > $prodArr[$key]->amount * ($rec->soonQuantity ?? 0)) {

                unset($prodArr[$key]);

            }
        }

        return $prodArr;
    }
}
