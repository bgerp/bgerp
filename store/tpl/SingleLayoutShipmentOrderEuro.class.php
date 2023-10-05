<?php


/**
 * Помощен модел за лесна работа с баланс, в който участват само определени пера и сметки
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Обработвач на шаблона за ЕН с цени в евро
 */
class store_tpl_SingleLayoutShipmentOrderEuro extends doc_TplScript
{
    /**
     * Към шаблоните на кой документ да може да се избира
     */
    public $addToClassTemplate = 'store_ShipmentOrders';


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
        $euroRate = round(currency_CurrencyRates::getRate($data->rec->valior, 'EUR', null), 4);
        currency_CurrencyRates::checkRateAndRedirect($euroRate);
        
        $Double = cls::get('type_Double');
        $data->row->euroRate = $Double->toVerbal($euroRate);
        
        $Double->params['decimals'] = 2;
        $data->row->amountEuro = $Double->toVerbal($data->rec->amountDelivered / $euroRate);
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
        if (!countR($data->rows)) {
            
            return;
        }
        
        if ($data->masterData->rec->currencyId == 'EUR') {
            
            return;
        }

        $before = 'packQuantity';
        if(isset($data->listFields['packPrice'])){
            $before = 'packPrice';
            $data->listFields['packPrice'] = 'Ед. цена';
        }
        arr::placeInAssocArray($data->listFields, 'priceEuro=Ед. цена в EUR', $before);

        $euroRate = round(currency_CurrencyRates::getRate($data->masterData->rec->date, 'EUR', null), 4);
        currency_CurrencyRates::checkRateAndRedirect($euroRate);

        foreach ($data->rows as $id => $row) {
            $rec = $data->recs[$id];
            $priceEuro = ($rec->packPrice * $data->masterData->rec->currencyRate) / $euroRate;
            
            $Double = core_Type::getByName('double(decimals=2)');
            $row->priceEuro = "<span style='float:right'>" . $Double->toVerbal($priceEuro) . '</span>';
        }
    }
}
