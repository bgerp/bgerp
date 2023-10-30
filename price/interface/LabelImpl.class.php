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

        if (isset($objId)) {

            // Показване обединението на множеството от плейсхолдърите на артикулите, които ще им се печата етикет
            $rec = frame2_Reports::fetch($objId);
            $printableRecs = $this->getPrintableRecs($rec, $rec->data->recs);
            $combinedParams = array();
            foreach ($printableRecs as $dRec){
                $params = cat_Products::getParams($dRec->productId);
                $params = array_keys(cat_Params::getParamNameArr($params, true));
                $combinedParams = array_merge($combinedParams, $params);
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
        $round = isset($rec->round) ? $rec->round : price_reports_PriceList::DEFAULT_ROUND;
        $Double = core_Type::getByName("double(decimals={$round})");

        $currentCount = 0;
        Mode::push('text', 'plain');
        $priceCaption = ($rec->vat == 'yes') ? tr('цена с ДДС') : tr('цена без ДДС');
        $allergenPramId = cat_Params::fetchIdBySysId('allergens');

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
                $measureId = cat_UoM::getShortName($pRec->measureId);

                // Ревербализиране на алергена
                Mode::push('printLabel', true);
                Mode::push('text', 'plain');
                $params = cat_Products::getParams($pRec->productId, null, true);
                Mode::pop('text');
                Mode::pop('printLabel');
                $params = cat_Params::getParamNameArr($params, true);

                if($rec->showMeasureId == 'yes' && !empty($pRec->price)){
                    $res = array('EAN' => $ean, 'EAN_ROTATED' => $ean, 'NAME' => $name, 'CATALOG_CURRENCY' => $rec->currencyId, 'CATALOG_PRICE' => $Double->toVerbal($pRec->price), "CODE" => $code, 'DATE' => $date, 'MEASURE_ID' => $measureId, 'PRICE_CAPTION' => $priceCaption);
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
                    $res = array('EAN' => $ean, 'EAN_ROTATED' => $ean, 'NAME' => $name, 'CATALOG_CURRENCY' => $rec->currencyId, 'CATALOG_PRICE' =>  $Double->toVerbal($packRec->price), "CODE" => $code, 'DATE' => $date, 'MEASURE_ID' => $packName, 'QUANTITY' => "({$packRec->quantity} {$measureId})", 'PRICE_CAPTION' => $priceCaption);
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