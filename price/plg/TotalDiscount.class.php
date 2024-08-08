<?php


/**
 * Клас 'price_plg_TotalDiscount' - Плъгин за добавяне на общи отстъпки за документите
 *
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class price_plg_TotalDiscount extends core_Plugin
{
    /**
     * Изпълнява се след закачане на детайлите
     */
    public static function on_AfterAttachDetails(core_Mvc $mvc, &$res, $details)
    {
        $mvc->declareInterface('price_TotalDiscountDocumentIntf');
        if ($mvc->details) {
            $details = arr::make($mvc->details);
            $details['price_DiscountsPerDocuments'] = 'price_DiscountsPerDocuments';
            $mvc->details = $details;
        }
    }


    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     * @return bool|null
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        if (price_DiscountsPerDocuments::haveRightFor('add', (object)array('documentClassId' => $mvc->getClassId(), 'documentId' => $data->rec->id))) {
            $data->toolbar->addBtn('Отстъпка', array('price_DiscountsPerDocuments', 'add', 'documentClassId' => $mvc->getClassId(), 'documentId' => $data->rec->id, 'ret_url' => true), 'ef_icon=img/16/discount.png,row=2,title=Задаване на обща отстъпка за документа');
        }
    }


    /**
     * Метод по подразбиране извличащ информацията за сумите от документа
     */
    public static function on_AfterGetTotalDiscountSourceData($mvc, &$res, $rec)
    {
        if(!$res){
            setIfNot($mvc->currencyRateFieldName, 'currencyRate');
            setIfNot($mvc->currencyFieldName, 'currencyId');
            setIfNot($mvc->chargeVatFieldName, 'chargeVat');
            setIfNot($mvc->totalAmountField, 'amountDeal');
            $rec = $mvc->fetchRec($rec);

            $res = (object)array('rate'       => $rec->{$mvc->currencyRateFieldName},
                                 'valior'     => $rec->{$mvc->valiorFld},
                                 'currencyId' => $rec->{$mvc->currencyFieldName},
                                 'chargeVat'  => $rec->{$mvc->chargeVatFieldName},
                                 'amount'     => $rec->{$mvc->totalAmountField},
            );

        }
    }


    /**
     * Метод по подразбиране за рекалкулиране на общите отстъпки за документа
     *
     * @param $mvc
     * @param $res
     * @param $rec
     * @return void
     */
    public static function on_AfterRecalcAutoTotalDiscount($mvc, &$res, $rec)
    {
        if(isset($res)) return;
        $rec = $mvc->fetchRec($rec);
        $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
        $vatExceptionId = cond_VatExceptions::getFromThreadId($rec->threadId);
        $totalDiscount = price_DiscountsPerDocuments::getDiscount4Document($mvc, $rec);
        if(empty($totalDiscount)) return;

        // Взима всички детайли и се опитва да сметне автоматичните отстъпки
        $Detail = cls::get($mvc->mainDetail);
        $dQuery = $Detail->getQuery();
        $dQuery->where("#{$Detail->masterKey} = {$rec->id}");
        $detailsAll = $dQuery->fetchAll();

        $sourceData = $mvc->getTotalDiscountSourceData($rec);
        $totalWithoutDiscount = 0;
        foreach ($detailsAll as $det1){
            $amount = isset($det1->discount) ? ($det1->amount * (1 - $det1->discount)) : $det1->amount;
            if($sourceData->chargeVat == 'yes'){
                $vat = cat_Products::getVat($det1->productId, $rec->valior, $vatExceptionId);
                $amount *= (1 + $vat);
            }
            $totalWithoutDiscount += $amount;
        }

        $calcedPercent =  !empty($totalWithoutDiscount) ? round(($totalDiscount / $totalWithoutDiscount), 8) : 0;
        if($calcedPercent <= 0) return;

        $save = array();
        foreach ($detailsAll as $dRec){
            $dRec->autoDiscount = $calcedPercent;
            $save[] = $dRec;
        }

        if(countR($save)){
            $Detail->saveArray($save, 'id,autoDiscount');
            $res = true;
        }
    }


    /**
     * Изпълнява се преди контиране на документа
     */
    protected static function on_BeforeConto(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        $sourceData = $mvc->getTotalDiscountSourceData($rec);

        if($sourceData->amount < 0){
            if(price_DiscountsPerDocuments::haveDiscount($mvc, $rec->id)){
                core_Statuses::newStatus("Сумата не може да е отрицателна|*!", 'error');

                return false;
            }
        }
    }


    /**
     * След клониране на записа
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec  - клонирания запис
     * @param stdClass $nRec - новия запис
     */
    protected static function on_AfterSaveCloneRec($mvc, $rec, $nRec)
    {
        // Клониране и на ръчните отстъпки
        $dQuery = price_DiscountsPerDocuments::getQuery();
        $dQuery->where("#documentClassId = {$mvc->getClassId()} AND #documentId = {$rec->id}");
        $dQuery->orderBy('id', 'ASC');
        while($dRec = $dQuery->fetch()){
            unset($dRec->id);
            $dRec->documentId = $nRec->id;
            price_DiscountsPerDocuments::save($dRec);
        }
    }


    /**
     * Метод по подразбиране за рекалкулиране на общите отстъпки за документа
     */
    public static function on_AfterCanHaveTotalDiscount($mvc, &$res, $rec)
    {
        if(!isset($res)){
            $res =  true;
        }
    }
}
