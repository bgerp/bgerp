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
class batch_definitions_DealReff extends batch_definitions_Varchar
{

    /**
     * Старо име на класа
     */
    public $oldClassName = 'batch_definitions_SaleReff';


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
        $batch = $this->getDefaultBatchName($documentClass, $id);

        if(!empty($batch)) return $batch;

        return null;
    }


    /**
     * Разпределя количество към наличните партиди в даден склад към дадена дата
     *
     * @param array  $quantities - масив с наличните партиди и количества
     * @param string $mvc        - клас на обект, към който да се разпределят
     * @param string $id         - ид на обект, към който да се разпределят
     * @param int $storeId       - ид на склад
     *
     * @return array $quantities - масив с филтрираните наличните партиди и количества
     */
    public function filterBatches($quantities, $mvc, $id, $storeId)
    {
        $batchName = $this->getDefaultBatchName($mvc, $id);
        if(!empty($batchName)){

            // Ще се приема че винаги е налична
            return array($batchName => 10000000000);
        }

        return array();
    }


    /**
     * Дефолтната стойност на партидата
     *
     * @param mixed $class
     * @param int $id
     * @return null $reff
     */
    private function getDefaultBatchName($class, $id)
    {
        $reff = null;

        // Намира нишката (ако има такава)
        $Class = cls::get($class);
        if($Class instanceof core_Detail){
            $Master = cls::get($Class->Master);
            $masterId = $Class->fetchField($id, $Class->masterKey);
            $threadId = $Master->fetchField($masterId, 'threadId');
        } else {
            $threadId = $Class->fetchField($id, 'threadId');
        }

        // От нишката търси вашия реф на сделката
        if(isset($threadId)){
            if($firstDoc = doc_Threads::getFirstDocument($threadId)){
                if($firstDoc->isInstanceOf('deals_DealMaster')){
                    $reff = $firstDoc->fetchField('reff');
                } elseif($firstDoc->isInstanceOf('planning_Jobs')){
                    $saleId = $firstDoc->fetchField('saleId');
                    if(isset($saleId)){
                        $reff = sales_Sales::fetchField($saleId, 'reff');
                    }
                }
            }
        }

        return $reff;
    }
}
