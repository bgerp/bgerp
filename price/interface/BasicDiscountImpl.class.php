<?php


/**
 * Клас за автоматични отстъпки от твърдо зададените отстъпки към политика
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
 * @title     Автоматични отстъпки според сумата
 */
class price_interface_BasicDiscountImpl extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Автоматични отстъпки според сумата';


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
     * @param stdClass $masterRec
     * @return null|double
     */
    private function getBasicDiscount($masterRec)
    {
        if(isset($this->calcedPercent)) return $this->calcedPercent;

        // Коя е ЦП на продажбата
        $listId = $masterRec->priceListId ?? price_ListToCustomers::getListForCustomer($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->valior);
        $listPaths = $basicArr = array();
        $parent = $listId;

        // Кои политики наследява тази политика
        while ($parent && ($pRec = price_Lists::fetch("#id = {$parent}", "id,parent"))) {
            $listPaths[] = $pRec->id;
            $parent = $pRec->parent;
        }

        // Кеш на твърдите отстъпки за посочените политики
        $basicDiscountQuery = price_ListBasicDiscounts::getQuery();
        $basicDiscountQuery->EXT('currencyId', 'price_Lists', 'externalName=currency,externalKey=listId');
        $basicDiscountQuery->in('listId', $listPaths);
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
        $totalAmountWithoutVatAndDiscount = 0;
        $dQuery = sales_SalesDetails::getQuery();
        $dQuery->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');
        $dQuery->where("#saleId = {$masterRec->id} AND #isPublic = 'yes'");
        while($dRec = $dQuery->fetch()){
            $totalAmountWithoutVatAndDiscount += $dRec->amount;
        }

        // Оставям само първия запис в този диапазон
        $foundDiscountRec = null;
        foreach ($foundRecs as $fRec){
            $convertedAmount = currency_CurrencyRates::convertAmount($totalAmountWithoutVatAndDiscount, null, null, $fRec->currencyId);
            if($convertedAmount >= $fRec->amountFrom && $convertedAmount <= $fRec->amountTo){
                $foundDiscountRec = $fRec;
                break;
            }
        }

        // Изчисляване на очаквания среден процент
        if($foundDiscountRec){
            $totalWithoutDiscountInListCurrency = currency_CurrencyRates::convertAmount($totalAmountWithoutVatAndDiscount, null, null, $foundDiscountRec->currencyId);
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

            if(haveRole('debug')){
                core_Statuses::newStatus("PNT:{$this->calcedPercent}; DISC:{$calcDiscountInListCurrency}; TOTAL:{$totalOld}", 'warning');
            }

            return $this->calcedPercent;
        }
    }


    /**
     * Изчислява очакваната отстъпка на реда от продажбата
     *
     * @param stdClass $dRec         - ред от детайл на продажба
     * @param stdClass $masterRec    - запис на продажба
     *
     * @return double|null $discount
     */
    public function calcAutoSaleDiscount($dRec, $masterRec)
    {
        $percent = $this->getBasicDiscount($masterRec);
        $isPublic = cat_Products::fetchField($dRec->productId, 'isPublic');
        if($isPublic == 'yes') return $percent;

        return null;
    }
}