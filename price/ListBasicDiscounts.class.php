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
    public $canEdit = 'debug';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'debug';


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

    public function getAutoDiscountsByGroups($basicDiscountListRec, $Master, $masterRec, $Detail, $detailsAll)
    {
        $res = array();
        $Detail = cls::get($Detail);
        $Master = cls::get($Master);

        $query = $this->getQuery();
        $query->where("#listId = {$basicDiscountListRec->id}");
        $query->orderBy('id', 'ASC');
        $dRecs = $query->fetchAll();
        $res['discountRecs'] = $dRecs;

        if(!countR($dRecs)) return $res;

        $groupIds = arr::extractValuesFromArray($dRecs, 'groupId');
        $salesByNow = array();
        if($basicDiscountListRec->discountClassPeriod != 'default'){
            $salesByNow = $this->getSalesByNowForContragent($masterRec->contragentClassId, $masterRec->contragentId, $groupIds, $basicDiscountListRec);
        }
        $res['SALES_BY_NOW'] = $salesByNow;
        $detailsByGroups = array();
        foreach ($groupIds as $groupId){
            foreach ($detailsAll as $detailRec){
                if(keylist::isIn($groupId, $detailRec->groups)){
                    if($Detail instanceof sales_SalesDetails){
                        $amount = isset($detailRec->discount) ? ($detailRec->amount * (1 - $detailRec->discount)) : $detailRec->amount;
                        if($basicDiscountListRec->vat == 'yes'){
                            $vat = cat_Products::getVat($detailRec->productId, $masterRec->valior);
                            $amount *= (1 + $vat);
                        }
                        $detailsByGroups[$groupId] += $amount;
                    }
                }
            }
        }
        $res['CURRENT_SALE'] = $detailsByGroups;
        $finalSums = $salesByNow;
        array_walk($detailsByGroups, function($val, $key) use (&$finalSums) {$finalSums[$key] += $val;});
        $res['TOTAL_SALES'] = $finalSums;

        foreach ($groupIds as $groupId){
            $res['groups'][$groupId] = array('SUM' => $finalSums[$groupId], 'FITS_IN' => null, 'percent' => null);

            if(empty($finalSums[$groupId])) continue;

            $foundDiscountRec = null;
            $filteredRecs = array_filter($dRecs, function($a) use ($groupId) {return $a->groupId == $groupId;});
            foreach ($filteredRecs as $fRec){
                $valToCheck = round($finalSums[$groupId], 2);
                $convertedAmount = currency_CurrencyRates::convertAmount($valToCheck, null, null, $fRec->currencyId);
                if($convertedAmount >= $fRec->amountFrom && (($convertedAmount <= $fRec->amountTo) || !isset($fRec->amountTo))){
                    $foundDiscountRec = $fRec;
                    break;
                }
            }

            // Изчисляване на очаквания среден процент
            if($foundDiscountRec){
                $res['groups'][$groupId]['FITS_IN'] = $foundDiscountRec;
                $valToCheck = round($finalSums[$groupId], 2);

                $totalWithoutDiscountInListCurrency = currency_CurrencyRates::convertAmount($valToCheck, null, null, $basicDiscountListRec->currencyId);

                $totalOld = $totalWithoutDiscountInListCurrency;
                $calcDiscountInListCurrency = 0;
                $totalWithoutDiscountInListCurrency -= $foundDiscountRec->amountFrom;

                if(isset($foundDiscountRec->discountPercent)){
                    $calcDiscountInListCurrency = $totalWithoutDiscountInListCurrency * $foundDiscountRec->discountPercent;
                }

                if(isset($foundDiscountRec->discountAmount)){
                    $calcDiscountInListCurrency += $foundDiscountRec->discountAmount;
                }

                $calcedPercent =  round(($calcDiscountInListCurrency / $totalOld), 4);

                $res['groups'][$groupId]['percent'] = $calcedPercent;
            }
        }

        return $res;
    }


    private function getSalesByNowForContragent($contragentClassId, $contragentId, $groupIds, $listRec)
    {
        $groupKeylist = keylist::fromArray($groupIds);
        $posReportClassId = pos_Reports::getClassId();
        $dQuery = sales_PrimeCostByDocument::getQuery();
        $dQuery->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');
        $dQuery->where("#isPublic = 'yes' AND #state IN ('active', 'closed') AND #sellCost IS NOT NULL AND #contragentClassId = {$contragentClassId} AND #contragentId = {$contragentId} AND #detailClassId != {$posReportClassId}");
        $dQuery->likeKeylist('groups', $groupKeylist);
        $dQuery->show('groups,quantity,sellCost,valior,productId');
        if($listRec->discountClassPeriod == 'monthly'){
            $firstDay = date('Y-m-01');
            $lastDay = dt::getLastDayOfMonth(dt::today());
            $dQuery->where("#valior > '{$firstDay}' AND #valior <= '{$lastDay}'");
        } else {
            $today = dt::today();
            $dQuery->where("#valior = '{$today}'");
        }
        $saleRecs = $dQuery->fetchAll();

        $sumByGroups = array();
        foreach ($groupIds as $groupId){
            foreach ($saleRecs as $sRec1){
                if(keylist::isIn($groupId, $sRec1->groups)){
                    $amount =  $sRec1->sellCost * $sRec1->quantity;
                    if($listRec->vat == 'yes'){
                        $vat = cat_Products::getVat($sRec1->productId, $sRec1->valior);
                        $amount *= (1 + $vat);
                    }
                    $sumByGroups[$groupId] += $amount;
                }
            }
        }

        $pQuery = pos_ReceiptDetails::getQuery();
        $pQuery->EXT('state', 'pos_Receipts', "externalName=state,externalKey=receiptId");
        $pQuery->EXT('waitingOn', 'pos_Receipts', "externalName=waitingOn,externalKey=receiptId");
        $pQuery->EXT('contragentClass', 'pos_Receipts', "externalName=contragentClass,externalKey=receiptId");
        $pQuery->EXT('transferredIn', 'pos_Receipts', "externalName=transferredIn,externalKey=receiptId");
        $pQuery->EXT('contragentObjectId', 'pos_Receipts', "externalName=contragentObjectId,externalKey=receiptId");
        $pQuery->where("#action = 'sale|code' AND (#state = 'waiting' OR (#state = 'closed' AND #transferredIn IS NULL))");
        $pQuery->where("#contragentClass = {$contragentClassId} AND #contragentObjectId = {$contragentId}");
        $pQuery->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');
        $pQuery->likeKeylist('groups', $groupKeylist);
        if($listRec->discountClassPeriod == 'monthly'){
            $firstDay = date('Y-m-01');
            $lastDay = dt::getLastDayOfMonth(dt::today());
            $pQuery->where("#waitingOn > '{$firstDay}' AND #waitingOn <= '{$lastDay}'");
        } else {
            $today = dt::today();
            $pQuery->where("#waitingOn = '{$today}'");
        }

        $receiptRecs = $pQuery->fetchAll();
        foreach ($groupIds as $groupId1){
            foreach ($receiptRecs as $receiptRec){
                if(keylist::isIn($groupId1, $receiptRec->groups)){
                    $amount = isset($receiptRec->discountPercent) ? ($receiptRec->amount * (1 - $receiptRec->discountPercent)) : $receiptRec->amount;
                    if($listRec->vat == 'yes'){
                        $amount *= (1 + $receiptRec->param);
                    }
                    $sumByGroups[$groupId1] += $amount;
                }
            }
        }

        return $sumByGroups;
    }
}