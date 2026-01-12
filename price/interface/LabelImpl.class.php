<?php


/**
 * Помощен клас-имплементация на интерфейса label_SequenceIntf за класа price_reports_PriceList
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see label_SequenceIntf
 *
 */
class price_interface_LabelImpl extends label_ProtoSequencerImpl
{
    /**
     * Връща масив с данните за плейсхолдерите
     *
     * @param int|NULL $objId
     * @param string $series
     *
     * @return array
     *               Ключа е името на плейсхолдера и стойностите са обект:
     *               type -> text/picture - тип на данните на плейсхолдъра
     *               len -> (int) - колко символа макс. са дълги данните в този плейсхолдер
     *               readonly -> (boolean) - данните не могат да се променят от потребителя
     *               hidden -> (boolean) - данните не могат да се променят от потребителя
     *               importance -> (int|double) - тежест/важност на плейсхолдера
     *               example -> (string) - примерна стойност
     */
    public function getLabelPlaceholders($objId = null, $series = 'label')
    {
        $placeholders = array();
        $placeholders['EAN'] = (object) array('type' => 'text', 'hidden' => true);
        $placeholders['EAN_ROTATED'] = (object) array('type' => 'text', 'hidden' => true);
        $placeholders['CODE'] = (object) array('type' => 'text', 'hidden' => true);
        $placeholders['NAME'] = (object) array('type' => 'text', 'hidden' => true);
        $placeholders['CATALOG_CURRENCY'] = (object) array('type' => 'text', 'hidden' => true);
        $placeholders['CATALOG_PRICE'] = (object) array('type' => 'text', 'hidden' => true);
        $placeholders['DATE'] = (object) array('type' => 'text', 'hidden' => true);
        $placeholders['QUANTITY'] = (object) array('type' => 'text', 'hidden' => true);
        $placeholders['MEASURE_ID'] = (object) array('type' => 'text', 'hidden' => true);
        $placeholders['PRICE_CAPTION'] = (object) array('type' => 'text', 'hidden' => true);
        $date = !empty($rec->date) ? $rec->date : dt::now();

        if (isset($objId)) {
            $rec = frame2_Reports::fetch($objId);

            // Показване обединението на множеството от плейсхолдърите на артикулите, които ще им се печата етикет
            $allergenSysId = cat_Params::fetchIdBySysId('allergens');

            $printableRecs = $this->getPrintableRecs($rec, $rec->data->recs);
            $combinedParams = array();
            foreach ($printableRecs as $dRec){
                $params = cat_Products::getParams($dRec->productId);
                $paramsPlaceholders = array_keys(cat_Params::getParamNameArr($params, true));
                if(array_key_exists($allergenSysId, $params)){
                    $paramsPlaceholders[] = 'ALLERGENS_IMG';
                }
                $combinedParams = array_merge($combinedParams, $paramsPlaceholders);
            }
            foreach ($combinedParams as $paramName) {
                $placeholders[$paramName] = (object) array('type' => 'text');
                $placeholders[$paramName]->hidden = true;
            }
        }

        return $placeholders;
    }


    /**
     * Връща масив с всички данни за етикетите
     *
     * @param int  $id
     * @param int  $cnt
     * @param bool $onlyPreview
     * @param stdClass $lRec
     * @param string $series
     *
     * @return array - масив от масив с ключ плейсхолдера и стойността
     */
    public function getLabelData($id, $cnt, $onlyPreview = false, $lRec = null, $series = 'label')
    {
        $resArr = array();
        $rec = frame2_Reports::fetchRec($id);
        $recs = $rec->data->recs;
        $Double = core_Type::getByName("double(decimals=2)");

        $currentCount = 0;
        Mode::push('text', 'plain');
        $priceCaption = ($rec->vat == 'yes') ? tr('цена с ДДС') : tr('цена без ДДС');
        $allergenPramId = cat_Params::fetchIdBySysId('allergens');

        $date = !empty($rec->date) ? $rec->date : dt::now();
        $showDualPrice = $date <= '2026-06-30' && in_array($rec->currencyId, array('BGN', 'EUR'));

        if(is_array($recs)){
            // От редовете, ще останат САМО тези, които ще могат да се печатат на етикети
            $printableRecs = $this->getPrintableRecs($rec, $recs);

            $date = dt::mysql2verbal(dt::today(), 'd.m.Y');
            foreach ($printableRecs as $pRec){

                $ean = '';
                if($onlyPreview === true){
                    $ean = '0000000000000';
                }

                $name = cat_Products::getVerbal($pRec->productId, 'name');
                $name = str::limitLen($name, 70);
                $code = cat_Products::getVerbal($pRec->productId, 'code');
                $code = !empty($code) ? $code : "Art{$pRec->productId}";

                // Ревербализиране на алергена
                Mode::push('printLabel', true);
                Mode::push('text', 'plain');
                $params = cat_Products::getParams($pRec->productId, null, true);
                Mode::pop('text');
                Mode::pop('printLabel');
                $params = cat_Params::getParamNameArr($params, true);

                if($rec->showMeasureId == 'yes' && !empty($pRec->price)){
                    $measureName = cat_UoM::getShortName($pRec->measureId);

                    $packagingRec = cat_products_Packagings::getPack($pRec->productId, $pRec->measureId);

                    $res = array('EAN' => $ean, 'EAN_ROTATED' => $ean, 'NAME' => $name, 'CATALOG_CURRENCY' => $rec->currencyId, "CODE" => $code, 'DATE' => $date, 'MEASURE_ID' => $measureName, 'PRICE_CAPTION' => $priceCaption);

                    $catalogPrice = currency_Currencies::decorate($Double->toVerbal($pRec->price), $rec->currencyId, true);
                    $res['CATALOG_PRICE'] = $catalogPrice;

                    if($showDualPrice){
                        if($rec->currencyId == 'BGN'){
                            $priceInBg = $catalogPrice;
                            $priceInEuro = currency_CurrencyRates::convertAmount($pRec->price, $date, 'BGN', 'EUR');
                            $priceInEuro = currency_Currencies::decorate($Double->toVerbal($priceInEuro), 'EUR', true);

                            $res['CATALOG_PRICE'] = "{$priceInEuro} / {$priceInBg}";
                        } else {
                            $priceInEuro = $catalogPrice;
                            $priceInBg = currency_CurrencyRates::convertAmount($pRec->price, $date, 'EUR', 'BGN');
                            $priceInBg = currency_Currencies::decorate($Double->toVerbal($priceInBg), 'BGN', true);

                            $res['CATALOG_PRICE'] = "{$priceInEuro} / {$priceInBg}";
                        }
                    }

                    if($rec->packType == 'base' && is_object($packagingRec)){
                        $measureId = cat_Products::fetchField($pRec->productId, 'measureId');
                        $quantity = cat_UoM::round($measureId, $packagingRec->quantity);
                        $measureName = cat_UoM::getShortName($measureId);
                        $res['QUANTITY'] = "{$quantity} {$measureName}";
                    }

                    if (countR($params)) {
                        $res = array_merge($res, $params);
                    }

                    $resArr[] = $res;
                    $currentCount++;
                    if($currentCount == $cnt) break;
                }

                foreach ($pRec->packs as $packRec){
                    $ean = !empty($packRec->eanCode) ? $packRec->eanCode : null;
                    $packName = cat_UoM::getShortName($packRec->packagingId);
                    $measureId = $pRec->measureId;
                    $quantity = cat_UoM::round($measureId, $packRec->quantity);
                    $measureName = cat_UoM::getShortName($measureId);

                    $catalogPrice = currency_Currencies::decorate($Double->toVerbal($packRec->price), $rec->currencyId, true);
                    $res = array('EAN' => $ean, 'EAN_ROTATED' => $ean, 'NAME' => $name, 'CATALOG_CURRENCY' => $rec->currencyId, "CODE" => $code, 'DATE' => $date, 'MEASURE_ID' => $packName, 'QUANTITY' => "{$quantity} {$measureName}", 'PRICE_CAPTION' => $priceCaption);

                    if($showDualPrice){
                        if($rec->currencyId == 'BGN'){
                            $priceInBg = $catalogPrice;
                            $priceInEuro = currency_CurrencyRates::convertAmount($packRec->price, $date, 'BGN', 'EUR');
                            $priceInEuro = currency_Currencies::decorate($Double->toVerbal($priceInEuro), 'EUR', true);

                            $res['CATALOG_PRICE'] = "{$priceInEuro} / {$priceInBg}";
                        } else {
                            $priceInEuro = $catalogPrice;
                            $priceInBg = currency_CurrencyRates::convertAmount($packRec->price, $date, 'EUR', 'BGN');
                            $priceInBg = currency_Currencies::decorate($Double->toVerbal($priceInBg), 'BGN', true);

                            $res['CATALOG_PRICE'] = "{$priceInEuro} / {$priceInBg}";
                        }
                    }

                    if (countR($params)) {
                        $res = array_merge($res, $params);
                    }
                    $resArr[] = $res;
                    $currentCount++;
                    if($currentCount == $cnt) break;
                }
            }
        }
        
        Mode::pop('text', 'plain');

        return $resArr;
    }


    /**
     * Кои редове от справката да се печатат етикети
     *
     * @param stdClass $rec
     * @param array $recs
     * @return array $res
     */
    private function getPrintableRecs($rec, $recs)
    {
        // Ако е инсталиран пакета `uiext` или не се искат конкретни тагове за отпечатване
        $res = array();
        if(!core_Packs::isInstalled('uiext') || $rec->showUiextLabels != 'yes') return $recs;

        $printLabelId = uiext_Labels::fetchField("#systemId='printLabel'", 'id');
        $hashFields = $this->class->getUiextLabelHashFields($rec);
        foreach($recs as $dRec){

            // Остават само редовете, в чиито тагове е посочено че са за принтиране
            $hash = uiext_Labels::getHash($dRec, $hashFields);
            $selRec = uiext_ObjectLabels::fetchByDoc(frame2_Reports::getClassId(), $rec->id, $hash);
            if($selRec){
                if(keylist::isIn($printLabelId, $selRec->labels)){
                    $res[] = $dRec;
                }
            }
        }

        return $res;
    }


    /**
     * Броя на етикетите, които могат да се отпечатат
     *
     * @param int $id
     * @param string $series
     *
     * @return int
     */
    public function getLabelEstimatedCnt($id, $series = 'label')
    {
        $count = 0;
        $rec = frame2_Reports::fetchRec($id);
        if(!is_array($rec->data->recs)) return $count;

        $recs = $this->getPrintableRecs($rec, $rec->data->recs);

        foreach ($recs as $dRec){
            if($rec->showMeasureId == 'yes' && !empty($dRec->price)){
                $count++;
            }
            $count += countR($dRec->packs);
        }
        
        return $count;
    }


    /**
     * Връща наименованието на етикета
     *
     * @param int $id
     * @param string $series
     * @return string
     */
    public function getLabelName($id, $series = 'label')
    {
        $rec = frame2_Reports::fetchRec($id);
        
        return $rec->title;
    }


    /**
     * Кога е отпечатан етикет от източника
     *
     * @param int $id
     * @return void
     */
    public function onLabelIsPrinted($id)
    {
        $rec = frame2_Reports::fetchRec($id);
        frame2_Reports::logWrite('Печат на етикет', $rec->id);
        $classId = frame2_Reports::getClassId();

        if(!core_Packs::isInstalled('uiext')) return;
        if($rec->showUiextLabels != 'yes') return;

        // Ако има отбелязани редове с таг "Печат" да се занулят след печата
        $printLabelId = uiext_Labels::fetchField("#systemId='printLabel'", 'id');
        $recLabelQuery = uiext_ObjectLabels::getQuery();
        $recLabelQuery->where("#classId={$classId} AND #objectId={$rec->id}");
        $recLabelQuery->where("LOCATE('|{$printLabelId}|', #labels)");
        while($recLabel = $recLabelQuery->fetch()){
            $recLabel->labels = keylist::removeKey($recLabel->labels, $printLabelId);
            if(empty($recLabel->labels)){
                uiext_ObjectLabels::delete($recLabel->id);
            }
        }
    }
}