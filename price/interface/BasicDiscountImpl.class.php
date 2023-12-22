<?php


/**
 * Клас за Отстъпки от общата сума
 *
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Ivelin Dimov <ivelin_pdimov@@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Отстъпки от общата сума
 */
class price_interface_BasicDiscountImpl extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Отстъпки от общата сума';


    /**
     * Интерфейс за ценова политика
     */
    public $interfaces = 'price_SaleAutoDiscountIntf';


    /**
     * Работен кеш
     */
    protected $calcedPercent = null;


    /**
     * Колко е базовата отстъпка
     *
     * @param mixed $Master
     * @param stdClass $masterRec
     * @return null|double
     */
    private function getBasicDiscount($Master, $masterRec)
    {
        if(isset($this->calcedPercent)) return $this->calcedPercent;

        // Коя е ЦП на продажбата
        $Master = cls::get($Master);
        if($Master instanceof sales_Sales){
            $listId = $masterRec->priceListId ?? price_ListToCustomers::getListForCustomer($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->valior);
        } else {
            $listId = pos_Receipts::isForDefaultContragent($masterRec) ? pos_Points::getSettings($masterRec->pointId)->policyId : price_ListToCustomers::getListForCustomer($masterRec->contragentClass, $masterRec->contragentObjectId);
        }

        $listPaths = $basicArr = array();
        $parent = $listId;

        // Кои политики наследява тази политика
        while ($parent && ($pRec = price_Lists::fetch("#id = {$parent}", "id,parent"))) {
            $listPaths[] = $pRec->id;
            $parent = $pRec->parent;
        }

        // Кеш на твърдите отстъпки за посочените политики
        $classId = $this->getClassId();
        $basicDiscountQuery = price_ListBasicDiscounts::getQuery();
        $basicDiscountQuery->EXT('currencyId', 'price_Lists', 'externalName=currency,externalKey=listId');
        $basicDiscountQuery->EXT('vat', 'price_Lists', 'externalName=vat,externalKey=listId');
        $basicDiscountQuery->EXT('discountClass', 'price_Lists', 'externalName=discountClass,externalKey=listId');
        $basicDiscountQuery->in('listId', $listPaths);
        $basicDiscountQuery->where("#discountClass = {$classId}");

        while($basicRec = $basicDiscountQuery->fetch()){
            $basicArr[$basicRec->listId][$basicRec->id] = $basicRec;
        }
        if(!countR($basicArr)) return;

        // Извличат се първите намерени отстъпки в опашката
        $foundRecs = array();
        foreach ($listPaths as $lId){
            if(array_key_exists($lId, $basicArr)){
                $foundRecs = $basicArr[$lId];
                break;
            }
        }

        // Колко е сумата на продажбата без ддс и приложена ТО
        $totalAmountWithoutVatAndDiscount = $totalAmountWithVatAndWithoutDiscount = 0;
        if($Master instanceof sales_Sales){
            $dQuery = sales_SalesDetails::getQuery();
            $dQuery->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');
            $dQuery->where("#saleId = {$masterRec->id} AND #isPublic = 'yes'");

            while($dRec = $dQuery->fetch()){
                $amount = isset($dRec->discount) ? ($dRec->amount * (1 - $dRec->discount)) : $dRec->amount;
                $totalAmountWithoutVatAndDiscount += $amount;
                $vat = cat_Products::getVat($dRec->productId, $masterRec->valior);
                $totalAmountWithVatAndWithoutDiscount += $amount * (1 + $vat);
            }
        } else {
            $dQuery = pos_ReceiptDetails::getQuery();
            $dQuery->where("#receiptId = {$masterRec->id} AND #action LIKE '%sale%'");
            while($dRec = $dQuery->fetch()) {
                $amount = isset($dRec->discountPercent) ? ($dRec->amount * (1 - $dRec->discountPercent)) : $dRec->amount;
                $totalAmountWithoutVatAndDiscount += $amount;
                $totalAmountWithVatAndWithoutDiscount += $amount * (1 + $dRec->param);
            }
        }

        $totalAmountWithoutVatAndDiscount = round($totalAmountWithoutVatAndDiscount, 2);
        $totalAmountWithVatAndWithoutDiscount = round($totalAmountWithVatAndWithoutDiscount, 2);

        // Оставям само първия запис в този диапазон
        $foundDiscountRec = null;
        foreach ($foundRecs as $fRec){
            $valToCheck = ($fRec->vat == 'yes') ? $totalAmountWithVatAndWithoutDiscount : $totalAmountWithoutVatAndDiscount;
            $convertedAmount = currency_CurrencyRates::convertAmount($valToCheck, null, null, $fRec->currencyId);
            if($convertedAmount >= $fRec->amountFrom && (($convertedAmount <= $fRec->amountTo) || !isset($fRec->amountTo))){
                $foundDiscountRec = $fRec;
                break;
            }
        }

        // Изчисляване на очаквания среден процент
        if($foundDiscountRec){
            $valToCheck = ($foundDiscountRec->vat == 'yes') ? $totalAmountWithVatAndWithoutDiscount : $totalAmountWithoutVatAndDiscount;

            $totalWithoutDiscountInListCurrency = currency_CurrencyRates::convertAmount($valToCheck, null, null, $foundDiscountRec->currencyId);
            $totalOld = $totalWithoutDiscountInListCurrency;
            $calcDiscountInListCurrency = 0;
            $totalWithoutDiscountInListCurrency -= $foundDiscountRec->amountFrom;
            if(isset($foundDiscountRec->discountPercent)){
                $calcDiscountInListCurrency = $totalWithoutDiscountInListCurrency * $foundDiscountRec->discountPercent;
            }

            if(isset($foundDiscountRec->discountAmount)){
                $calcDiscountInListCurrency += $foundDiscountRec->discountAmount;
            }
            $this->calcedPercent =  round(($calcDiscountInListCurrency / $totalOld), 4);

            return $this->calcedPercent;
        }
    }


    /**
     * Изчислява автоматичната отстъпка на ред от детайл
     *
     * @param string|core_Detail $Detail - клас на детайла
     * @param int|stdclass $dRec         - ид или запис на детайла
     * @param string|core_Master $Master - клас на мастъра
     * @param int|stdclass $masterRec    - ид или запис на мастъра
     *
     * @return double|null
     */
    public function calcAutoSaleDiscount($Detail, $dRec, $Master, $masterRec)
    {
        $percent = $this->getBasicDiscount($Master, $masterRec);
        $isPublic = cat_Products::fetchField($dRec->productId, 'isPublic');
        if($isPublic == 'yes') return $percent;

        return null;
    }
}