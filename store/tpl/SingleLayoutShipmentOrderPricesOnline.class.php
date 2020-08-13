<?php


/**
 * Клас закачащ се към шаблона за Packaging List за митница
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_tpl_SingleLayoutShipmentOrderPricesOnline extends doc_TplScript
{
    
    
    /**
     * Метод който подава данните на мастъра за обработка на скрипта
     *
     * @param core_Mvc $mvc  - мастър на документа
     * @param stdClass $data - данни
     *
     * @return void
     */
    public function modifyMasterData(core_Mvc $mvc, &$data)
    {
       
    }
    
    
    /**
     * Метод който подава данните на детайла на мастъра, за обработка на скрипта
     *
     * @param core_Mvc $detail - Детайл на документа
     * @param stdClass $data   - данни
     *
     * @return void
     */
    public function modifyDetailData(core_Mvc $detail, &$data)
    {
       
    }
    
    
    /**
     * Преди подготовка на мастър данните
     *
     * @param core_Mvc $detail
     * @param core_ET  $tpl
     * @param stdClass $data
     */
    public function beforePrepareMasterData(core_Mvc $mvc, &$data)
    {
        $data->dontHidePrices = true;
    }
    
    
    /**
     * Преди подготовка на данните на детайла
     *
     * @param core_Mvc $detail
     * @param core_ET  $tpl
     * @param stdClass $data
     */
    public function beforePrepareDetailListRows(core_Mvc $detail, &$data)
    {
        $data->dontHidePrices = true;
    }
}