<?php


/**
 * Базов драйвер за вид партида 'Ваш реф на сделка'
 *
 *
 * @category  bgerp
 * @package   batch
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title Ваш реф на сделка
 */
class batch_definitions_SaleReff extends batch_definitions_Varchar
{
    /**
     * Връща автоматичния партиден номер според класа
     *
     * @param mixed         $documentClass - класа за който ще връщаме партидата
     * @param int           $id            - ид на документа за който ще връщаме партидата
     * @param int           $storeId       - склад
     * @param datetime|NULL $date          - дата
     *
     * @return mixed $value        - автоматичния партиден номер, ако може да се генерира
     */
    public function getAutoValue($documentClass, $id, $storeId, $date = null)
    {
        $batch = null;

        $Class = cls::get($documentClass);
        if($threadId = $Class->fetchField($id, 'threadId')){

            if($firstDoc = doc_Threads::getFirstDocument($threadId)){
                if($firstDoc->isInstanceOf('deals_DealMaster')){
                    $batch = $firstDoc->fetchField('reff');
                } elseif($firstDoc->isInstanceOf('planning_Jobs')){
                    $saleId = $firstDoc->fetchField('saleId');
                    if(isset($saleId)){
                        $batch = sales_Sales::fetchField($saleId, 'reff');
                    }
                }
            }
        }

        if(!empty($batch)) return $batch;

        return null;
    }
}
