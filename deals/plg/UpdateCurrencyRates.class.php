<?php


/**
 * Плъгин преизчисляващ курса на документи на заявка/чернова
 * запазвайки оригиналната цена във валута
 *
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class deals_plg_UpdateCurrencyRates extends core_Plugin
{
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(&$mvc)
    {
        setIfNot($mvc->priceInCurrencyFieldName, 'priceInCurrency');
        $mvc->FLD('priceInCurrency', 'double', 'caption=Цена във валута,input=none');
    }


    /*
     * Преизчисляване на валутния курс на старите чернови документи и заявки
     */
    function cron_RecalcCurrencyRate()
    {
        $classArr = array('sales_SalesDetails', 'purchase_PurchasesDetails');
        foreach ($classArr as $class){
            $Detail = cls::get($class);
            if(!$Detail->hasPlugin('deals_plg_UpdateCurrencyRates')) continue;

            // Взимат се детайлите на черновите/заявките, които са без вальор във валута
            $dQuery = $Detail->getQuery();
            $dQuery->EXT('currencyId', $Detail->Master->className, "externalName=currencyId,externalKey={$Detail->masterKey}");
            $dQuery->EXT($Detail->Master->rateFldName, $Detail->Master->className, "externalName={$Detail->Master->rateFldName},externalKey={$Detail->masterKey}");
            $dQuery->EXT($Detail->Master->valiorFld, $Detail->Master->className, "externalName={$Detail->Master->valiorFld},externalKey={$Detail->masterKey}");
            $dQuery->EXT('state', $Detail->Master->className, "externalName=state,externalKey={$Detail->masterKey}");
            $dQuery->in("state", array('draft', 'pending'));
            $dQuery->show("{$Detail->masterKey},currencyId,{$Detail->Master->rateFldName},price,{$Detail->priceInCurrencyFieldName},productId");
            $dQuery->where("#{$Detail->Master->valiorFld} IS NULL AND #currencyId != 'BGN'");

            $res = array();
            $today = dt::today();
            while($dRec = $dQuery->fetch()){
                // Ако новия курс е различен от записания
                $newRate = currency_CurrencyRates::getRate($today, $dRec->currencyId, null);
                if(round($dRec->{$Detail->Master->rateFldName}, 6) == round($newRate, 6)) continue;

                // Извличат се данните от мастъра
                if(!array_key_exists($dRec->{$Detail->masterKey}, $res)){
                    $res[$dRec->{$Detail->masterKey}] = (object) array('_oldRate' => $dRec->{$Detail->Master->rateFldName},
                        'id' => $dRec->{$Detail->masterKey},
                        "{$Detail->Master->rateFldName}" => $newRate,
                        'details' => array(),
                        'currencyId' => $dRec->currencyId);
                }

                // Изчислява се колко е валутата по стария курс и ще се запише по новия така че да остане същата
                $priceInCurrency = $dRec->priceInCurrency ?? ($dRec->price / $dRec->{$Detail->Master->rateFldName});
                $newPrice = $priceInCurrency * $res[$dRec->{$Detail->masterKey}]->{$Detail->Master->rateFldName};
                $res[$dRec->{$Detail->masterKey}]->details[$dRec->id] = (object)array('id' => $dRec->id,
                                                                                      'price' => $newPrice,
                                                                                      'oldPrice' => $dRec->price,
                                                                                      "{$Detail->priceInCurrencyFieldName}" => $priceInCurrency);
            }

            // Разделяне на записите за обновяване на мастър и на детайла
            $detailsToSave = $saveMasters = array();
            foreach ($res as $id => $data){
                $saveMasters[$id] = (object)array('id' => $id, "{$Detail->Master->rateFldName}" => $data->{$Detail->Master->rateFldName});
                foreach ($data->details as $det){
                    $detailsToSave[$det->id] = (object)array('id' => $det->id, 'price' => $det->price, "{$Detail->priceInCurrencyFieldName}" => $det->{$Detail->priceInCurrencyFieldName});
                }
                $Detail->Master->logWrite('Авт. преизч. на курса на чернови/заявки без вальор', $id);
            }

            // Запис на новите валутни курсове
            if(countR($saveMasters)){
                $Detail->Master->saveArray($saveMasters, "id,{$Detail->Master->rateFldName}");
            }

            // Запис детайлите
            if(countR($detailsToSave)){
                $Detail->saveArray($detailsToSave, "id,price,{$Detail->priceInCurrencyFieldName}");
            }
        }
    }
}