<?php


/**
 * Ценови политики Общи отстъпки към
 *
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Общи отстъпки
 */
class price_ListBasicDiscounts extends core_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Общи отстъпки на ценови политики';


    /**
     * Заглавие
     */
    public $singleTitle = 'Обща отстъпка на ценова политика';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, price_Wrapper, plg_Modified, plg_Created, plg_SaveAndNew, plg_AlignDecimals2';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'listId,groupId,amountFrom,amountTo,discountPercent,discountAmount,currencyId=Валута,modifiedOn,modifiedBy';


    /**
     * Кой може да го промени?
     */
    public $canEdit = 'price,sales,ceo';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'price,sales,ceo';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';


    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'listId';


    /**
     * Работен кеш
     */
    protected $cacheDiscounts = array();


    /**
     * Работен кеш
     */
    protected $cacheExSales = array();


    /**
     * Работен кеш
     */
    public $invalidateListsOnShutdown = array();


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('listId', 'key(mvc=price_Lists,select=title)', 'caption=Ценоразпис,input=hidden,silent');
        $this->FLD('groupId', 'key(mvc=cat_Groups,select=name,allowEmpty)', 'caption=Отстъпка->Група,mandatory');
        $this->FLD('amountFrom', 'double(min=0,minDecimals=2)', 'caption=Сума->От');
        $this->FLD('amountTo', 'double(Min=0,minDecimals=2)', 'caption=Сума->До');
        $this->FLD('discountPercent', 'percent', 'caption=Отстъпка->Процент');
        $this->FLD('discountAmount', 'double(minDecimals=2)', 'caption=Отстъпка->Твърда');
    }


    /**
     * Извиква се след подготовката на формата
     */
    protected static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        $listRec = price_Lists::fetch($rec->listId);

        $vatUnit = ($listRec->vat == 'yes') ? tr('с ДДС') : tr('без ДДС');
        $form->setField('amountFrom', array('unit' => "|*{$listRec->currency}, {$vatUnit}"));
        $form->setField('amountTo', array('unit' => "|*{$listRec->currency},  {$vatUnit}"));
        $form->setField('discountAmount', array('unit' => "|*{$listRec->currency},  {$vatUnit}"));
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;

        if ($form->isSubmitted()) {
            if (empty($rec->discountPercent) && empty($rec->discountAmount)) {
                $form->setError('discountPercent,discountAmount', 'Трябва поне едно от полетата да е попълнено');
            }

            $from = $rec->amountFrom ?? 0;
            $to = $rec->amountTo ?? 999999999999;
            if($from >= $to){
                $form->setError('amountFrom,amountTo', 'Сума от трябва да е по-малка от сума до|*');
            }

            if(!$form->gotErrors()){
                $query = static::getQuery();
                $query->XPR('amountToCalc', 'int', "COALESCE(#amountTo, 999999999999)");
                $query->where("#id != '{$rec->id}' AND #listId = {$rec->listId} AND #groupId = {$rec->groupId}");
                $query->where("'{$from}' < #amountToCalc && '{$to}' > #amountFrom");

                if($query->count()){
                    $form->setError('amountFrom,amountTo', 'Посоченият интервал се засича с вече зададен за групата|*!');
                }
            }

            if(!$form->gotErrors()){
                if(empty($rec->amountFrom)){
                    $rec->amountFrom = 0;
                }
            }
        }
    }


    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $data->listTableMvc->FLD('currencyId', 'varchar', 'smartCenter');
        foreach ($data->rows as $id => $row){
            $rec = $data->recs[$id];
            $listRec = price_Lists::fetch($rec->listId);
            $row->listId = price_Lists::getHyperlink($rec->listId, true);
            $row->currencyId = $listRec->currency;
            if(empty($rec->amountTo)){
                $row->amountTo = "<i style='color:blue'>" . tr('Без лимит') . "</i class>";
            }
        }
    }


    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    protected static function on_AfterPrepareListFields($mvc, $data)
    {
        if (isset($data->masterMvc)) {
            unset($data->listFields['listId']);
        }
        $data->query->orderBy('listId', 'ASC');
    }


    /**
     * Подготовка на Детайлите
     */
    public function prepareDetail_($data)
    {
        if($data->masterId == price_ListRules::PRICE_LIST_COST){
            $data->hide = true;
            return;
        }

        $res = parent::prepareDetail_($data);
        $count = countR($data->recs);
        $data->TabCaption = "Общи отстъпки|* ({$count})";
        $data->Tab = 'top';

        return $res;
    }


    /**
     * Рендиране на детайла
     *
     * @param stdClass $data
     * @return core_ET $tpl
     */
    public function renderDetail_($data)
    {
        if($data->hide) return new core_ET("");

        // Ако не се иска да се показва детайла - да се скрива
        $vatUnit = ($data->masterData->rec->vat == 'yes') ? tr('с ДДС') : tr('без ДДС');
        $data->listFields['amountFrom'] = "Сума|* <small>($vatUnit)</small>->От";
        $data->listFields['amountTo'] = "Сума|* <small>($vatUnit)</small>->До";
        $data->listFields['discountAmount'] = "Отстъпка->Твърда|* <small>($vatUnit)</small>";

        return parent::renderDetail_($data);
    }


    /**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $user = null)
    {
        if(isset($rec->listId)){
            if($rec->listId == price_ListRules::PRICE_LIST_COST){
                $requiredRoles = 'no';
            }
        }
    }


    /**
     * Изчислява автоматичните отстъпки на детайла на документа
     *
     * @param stdClass $basicDiscountListRec
     * @param mixed $Master
     * @param stdClass $masterRec
     * @param mixed $Detail
     * @param array $detailsAll
     * @return array $res - масив с данните за отстъпки + дебъг информация
     */
    public static function getAutoDiscountsByGroups($basicDiscountListRec, $Master, $masterRec, $Detail, $detailsAll)
    {
        $res = array();
        $Detail = cls::get($Detail);
        $Master = cls::get($Master);

        // Към посочения лист с отстъпки се взимат зададените му прагове
        $query = static::getQuery();
        $query->where("#listId = {$basicDiscountListRec->id}");
        $query->orderBy('id', 'ASC');
        $dRecs = $query->fetchAll();
        $res['discountRecs'] = $dRecs;
        if(!countR($dRecs)) return $res;

        $salesByNow = array();
        $groupIds = arr::extractValuesFromArray($dRecs, 'groupId');

        // Ако периода за продажба е различен от "текущата продажба" смятат се сумите от предишните продажби за контрагента
        if($basicDiscountListRec->discountClassPeriod != 'default' && !($Master instanceof eshop_Carts)){
            $contragentClassId = $masterRec->contragentClassId;
            $contragentId = $masterRec->contragentId;
            if($Master instanceof pos_Receipts){
                $contragentClassId = $masterRec->contragentClass;
                $contragentId = $masterRec->contragentObjectId;
            }

            // Взимане на предишните продажби от кеша, ако няма се изчисляват на моментаПро
            $cacheKey = "{$contragentClassId}|{$contragentId}|{$basicDiscountListRec->id}|" . implode('|', $groupIds);
            $salesByNow = core_Cache::get($Master->className, $cacheKey);
            if($Master->className == 'pos_Receipts'){
                core_Permanent::set("autoDiscCache|{$Master->className}|{$masterRec->id}", $cacheKey, 48 * 60);
            }
            if(!is_array($salesByNow)){
                $salesByNow = static::getSalesByNowForContragent($contragentClassId, $contragentId, $groupIds, $basicDiscountListRec);
                core_Cache::set($Master->className, $cacheKey, $salesByNow, 1);
            }
        }
        $res['SALES_BY_NOW'] = $salesByNow;

        // За всяка група от праговете
        $detailsByGroups = array();
        if($Master instanceof eshop_Carts){
            $settings = cms_Domains::getSettings($masterRec->domainId);
            $vatExceptionId = $settings->vatExceptionId;
        } else {
            $vatExceptionId = cond_VatExceptions::getFromThreadId($masterRec->threadId);
        }

        foreach ($groupIds as $groupId){

            // Добавят се и данните за раздадените отстъпки от текущата продажба
            foreach ($detailsAll as $detailRec){
                if(keylist::isIn($groupId, $detailRec->groups)){
                    $detailsByGroups[$groupId]['autoDiscount'] += 0;
                    if($Detail instanceof sales_SalesDetails){
                        $amount = isset($detailRec->discount) ? ($detailRec->amount * (1 - $detailRec->discount)) : $detailRec->amount;
                        if($basicDiscountListRec->vat == 'yes'){
                            $vat = cat_Products::getVat($detailRec->productId, $masterRec->valior, $vatExceptionId);
                            $amount *= (1 + $vat);
                        }
                        $detailsByGroups[$groupId]['amount'] += $amount;
                    } else {
                        $amount = isset($detailRec->discountPercent) ? ($detailRec->amount * (1 - $detailRec->discountPercent)) : $detailRec->amount;
                        if($basicDiscountListRec->vat == 'yes'){
                            $amount *= (1 + $detailRec->param);
                        }
                        $detailsByGroups[$groupId]['amount'] += $amount;
                    }
                }
            }
        }

        // Обединяват се данните от предишни и от текуща продажба
        $res['CURRENT_SALE'] = $detailsByGroups;
        $finalSums = $salesByNow;
        array_walk($detailsByGroups, function($valArr, $key) use (&$finalSums) {
            $finalSums[$key]['amount'] += $valArr['amount'];
            $finalSums[$key]['autoDiscount'] += $valArr['autoDiscount'];

        });
        $res['TOTAL_SALES'] = $finalSums;

        // За всяка група от праговете
        foreach ($groupIds as $groupId){

            // Гледа се сумата на продажбите от нея
            $res['groups'][$groupId] = array('SUM' => $finalSums[$groupId]['amount'], 'DISC_BY_NOW' => $finalSums[$groupId]['autoDiscount'], 'FITS_IN' => null, 'percent' => null);
            if(empty($finalSums[$groupId]['amount'])) continue;
            if(empty($res['CURRENT_SALE'][$groupId]['amount'])) continue;

            // Ако има се търси попада ли в някой от посочените прагове
            $foundDiscountRec = null;
            $filteredRecs = array_filter($dRecs, function($a) use ($groupId) {return $a->groupId == $groupId;});
            foreach ($filteredRecs as $fRec){
                $valToCheck = round($finalSums[$groupId]['amount'], 2);
                $convertedAmount = currency_CurrencyRates::convertAmount($valToCheck, null, null, $fRec->currencyId);
                if($convertedAmount >= $fRec->amountFrom && (($convertedAmount <= $fRec->amountTo) || !isset($fRec->amountTo))){
                    $foundDiscountRec = $fRec;
                    break;
                }
            }

            // Изчисляване на очаквания среден процент
            if($foundDiscountRec){

                // Ако попада в някой праг
                $res['groups'][$groupId]['FITS_IN'] = $foundDiscountRec;
                $valToCheck = round($finalSums[$groupId]['amount'], 2);

                // Смята се процента на автоматична отстъпка
                $totalWithoutDiscountInListCurrency = currency_CurrencyRates::convertAmount($valToCheck, null, null, $basicDiscountListRec->currencyId);
                $calcDiscountInListCurrency = 0;
                $totalWithoutDiscountInListCurrency -= $foundDiscountRec->amountFrom;

                // Изчислява се сумата за отстпка
                if(isset($foundDiscountRec->discountPercent)){
                    $calcDiscountInListCurrency = $totalWithoutDiscountInListCurrency * $foundDiscountRec->discountPercent;
                }
                if(isset($foundDiscountRec->discountAmount)){
                    $calcDiscountInListCurrency += $foundDiscountRec->discountAmount;
                }

                // Приспадат се вече раздадените авт. отстъпки
                $calcDiscountInListCurrency -= $finalSums[$groupId]['autoDiscount'];
                if($calcDiscountInListCurrency <= 0) continue;

                // Изчислява се процента спрямо сумата на групата от текущата продажба
                $totalOld = currency_CurrencyRates::convertAmount($res['CURRENT_SALE'][$groupId]['amount'], null, null, $basicDiscountListRec->currencyId);
                $calcedPercent =  round(($calcDiscountInListCurrency / $totalOld), 4);
                $calcedPercent = min($calcedPercent, 1);

                $res['groups'][$groupId]['percent'] = $calcedPercent;
            }
        }

        return $res;
    }


    /**
     * Намира предишните продажби(обикновени и POS) на контрагента по групи
     *
     * @param int $contragentClassId
     * @param int $contragentId
     * @param array $groupIds
     * @param stdClass $listRec
     * @return array $sumByGroups
     */
    private static function getSalesByNowForContragent($contragentClassId, $contragentId, $groupIds, $listRec)
    {
        // От модела за делтите се извличат предишните продажби за контрагента от посочения интервал
        $groupKeylist = keylist::fromArray($groupIds);
        $posReportClassId = pos_Reports::getClassId();
        $dQuery = sales_PrimeCostByDocument::getQuery();
        $dQuery->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');
        $dQuery->where("#isPublic = 'yes' AND #state IN ('active', 'closed') AND #sellCost IS NOT NULL AND #contragentClassId = {$contragentClassId} AND #contragentId = {$contragentId} AND #detailClassId != {$posReportClassId}");
        plg_ExpandInput::applyExtendedInputSearch('cat_Products', $dQuery, $groupKeylist, 'productId');

        $dQuery->show('groups,quantity,sellCost,valior,productId,sellCostWithOriginalDiscount,autoDiscountAmount,threadId,activatedOn');
        if($listRec->discountClassPeriod == 'monthly'){
            $firstDay = date('Y-m-01');
            $lastDay = dt::getLastDayOfMonth(dt::today());
            $dQuery->where("#valior >= '{$firstDay}' AND #valior <= '{$lastDay}'");
        } elseif($listRec->discountClassPeriod == 'hourly'){
            $now = dt::now();
            $before1Hour = dt::addSecs(-3600, $now);
            $dQuery->where("#activatedOn >= '{$before1Hour}' AND #activatedOn <= '{$now}'");
        } else{
            $today = dt::today();
            $dQuery->where("#valior = '{$today}'");
        }
        $saleRecs = $dQuery->fetchAll();

        $threadExceptionCache = array();
        $threadIds = arr::extractValuesFromArray($saleRecs, 'threadId');
        foreach ($threadIds as $threadId){
            $threadExceptionCache[$threadId] = cond_VatExceptions::getFromThreadId($threadId);
        }

        // Сумира се сумата без оригинална отстъпка и сумата на автоматичните отстъпки от тях
        $sumByGroups = array();
        foreach ($groupIds as $groupId){
            foreach ($saleRecs as $sRec1){
                if(keylist::isIn($groupId, $sRec1->groups)){
                    $amount =  $sRec1->sellCostWithOriginalDiscount * $sRec1->quantity;
                    $autoDiscountAmount = isset($sRec1->autoDiscountAmount) ? ($sRec1->autoDiscountAmount * $sRec1->quantity): 0;
                    if($listRec->vat == 'yes'){
                        $vat = cat_Products::getVat($sRec1->productId, $sRec1->valior, $threadExceptionCache[$sRec1->threadId]);
                        $amount *= (1 + $vat);
                        $autoDiscountAmount *= (1 + $vat);
                    }
                    $sumByGroups[$groupId]['amount'] += $amount;
                    $sumByGroups[$groupId]['autoDiscount'] += $autoDiscountAmount;
                }
            }
        }

        // Към масива се добавят и чакащите/приключени (но непрехвърлени) ПОС бележки
        $pQuery = pos_ReceiptDetails::getQuery();
        $pQuery->EXT('state', 'pos_Receipts', "externalName=state,externalKey=receiptId");
        $pQuery->EXT('waitingOn', 'pos_Receipts', "externalName=waitingOn,externalKey=receiptId");
        $pQuery->EXT('contragentClass', 'pos_Receipts', "externalName=contragentClass,externalKey=receiptId");
        $pQuery->EXT('transferredIn', 'pos_Receipts', "externalName=transferredIn,externalKey=receiptId");
        $pQuery->EXT('contragentObjectId', 'pos_Receipts', "externalName=contragentObjectId,externalKey=receiptId");
        $pQuery->where("#action = 'sale|code' AND (#state = 'waiting' OR (#state = 'closed' AND #transferredIn IS NULL))");
        $pQuery->where("#contragentClass = {$contragentClassId} AND #contragentObjectId = {$contragentId}");
        $pQuery->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');
        plg_ExpandInput::applyExtendedInputSearch('cat_Products', $pQuery, $groupKeylist, 'productId');
        $pQuery->useIndex('state', 'pos_Receipts');

        if($listRec->discountClassPeriod == 'monthly'){
            $firstDay = date('Y-m-01');
            $lastDay = dt::getLastDayOfMonth(dt::today());
            $pQuery->where("#waitingOn >= '{$firstDay}' AND #waitingOn <= '{$lastDay}'");
        } elseif($listRec->discountClassPeriod == 'hourly'){
            $now = dt::now();
            $before1Hour = dt::addSecs(-3600, $now);
            $pQuery->where("#waitingOn >= '{$before1Hour}' AND #waitingOn <= '{$now}'");
        }  else {
            $today = dt::today();
            $pQuery->where("#waitingOn >= '{$today} 00:00:00' AND #waitingOn <= '{$today} 23:59:59'");
        }

        $receiptRecs = $pQuery->fetchAll();

        foreach ($groupIds as $groupId1){
            foreach ($receiptRecs as $receiptRec){
                if(keylist::isIn($groupId1, $receiptRec->groups)){
                    $amount = isset($receiptRec->inputDiscount) ? ($receiptRec->amount * (1 - $receiptRec->inputDiscount)) : $receiptRec->amount;
                    $autoDiscountAmount = isset($receiptRec->autoDiscount) ? $amount * $receiptRec->autoDiscount : 0;
                    if($listRec->vat == 'yes'){
                        $amount *= (1 + $receiptRec->param);
                        $autoDiscountAmount *= (1 + $receiptRec->param);
                    }

                    $sumByGroups[$groupId1]['amount'] += $amount;
                    $sumByGroups[$groupId1]['autoDiscount'] += $autoDiscountAmount;
                }
            }
        }

        return $sumByGroups;
    }


    /**
     * Изпълнява се преди запис
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
        if(isset($rec->id)){
            $exRec = $mvc->fetch($rec->id, '*', false);
            $checkExFields = md5("{$exRec->groupId}|{$exRec->amountFrom}|{$exRec->amountTo}|{$exRec->discountPercent}|{$exRec->discountAmount}");
            $checkCurrentFields = md5("{$rec->groupId}|{$rec->amountFrom}|{$rec->amountTo}|{$rec->discountPercent}|{$rec->discountAmount}");
            if($checkExFields != $checkCurrentFields){
                $mvc->invalidateListsOnShutdown[$rec->listId] = $rec->listId;
            }
        } else {
            $mvc->invalidateListsOnShutdown[$rec->listId] = $rec->listId;
        }
    }


    /**
     * Изпълнява се на шътдаун
     */
    public static function on_Shutdown($mvc)
    {
        // Ако има списъци за инвалидиране на кешираните цени да се инвалидират
        if (is_array($mvc->invalidateListsOnShutdown)) {
            foreach ($mvc->invalidateListsOnShutdown as $listId) {
                price_Cache::callback_InvalidatePriceList($listId);
            }
        }
    }
}