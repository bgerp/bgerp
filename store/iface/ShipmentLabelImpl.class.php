<?php


/**
 * Помощен клас-имплементация на интерфейса label_SequenceIntf за класа store_ShipmentOrders
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see label_SequenceIntf
 *
 */
class store_iface_ShipmentLabelImpl
{
    /**
     * Инстанция на класа
     */
    public $class;


    /**
     * Връща наименованието на етикета
     *
     * @param int $id
     * @param string $series
     * @return string
     */
    public function getLabelName($id, $series = 'label')
    {
        $rec = $this->class->fetchRec($id);
        
        return '#' . $this->class->getHandle($rec);
    }


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

        if($series == 'detail'){
            $placeholders['PRODUCT_NAME'] = (object) array('type' => 'text', 'hidden' => TRUE);
            $placeholders['CODE'] = (object) array('type' => 'text', 'hidden' => TRUE);
            $placeholders['SHIPMENT_ID'] = (object) array('type' => 'text');
            $placeholders['PREVIEW'] = (object) array('type' => 'picture', 'hidden' => TRUE);
            $placeholders['REFF'] = (object) array('type' => 'text');
            $placeholders['SALE_ID'] = (object) array('type' => 'text');
            $placeholders['MEASURE_ID'] = (object) array('type' => 'text', 'hidden' => TRUE);
            $placeholders['QUANTITY'] = (object) array('type' => 'text', 'hidden' => TRUE);
            $placeholders['QUANTITY_IN_PACK'] = (object) array('type' => 'text', 'hidden' => TRUE);
            $placeholders['NOTES'] = (object) array('type' => 'text', 'hidden' => TRUE);
            $placeholders['PACKAGING_ID'] = (object) array('type' => 'text', 'hidden' => TRUE);
            $placeholders['BATCH'] = (object) array('type' => 'text', 'hidden' => TRUE);
        } else {
            $placeholders['NOMER'] = (object) array('type' => 'text');
            $placeholders['DESTINATION'] = (object) array('type' => 'text');
            $placeholders['SPEDITOR'] = (object) array('type' => 'text');
            $placeholders['DATE'] = (object) array('type' => 'text');
        }

        if (isset($objId)) {
            $labelData = $this->getLabelData($objId, 1, true, null, $series);
            if (isset($labelData[0])) {
                foreach ($labelData[0] as $key => $val) {
                    if(is_object($placeholders[$key])){
                        $placeholders[$key]->example = $val;
                    }
                }
            }
        }
        
        return $placeholders;
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
        $rec = $this->class->fetchRec($id);

        if($series == 'detail'){
            $recs = $this->getDetailLabelRecs($rec);
            $count = countR($recs);
        } else {
            $count = 0;
            $transUnits = is_array($rec->transUnitsInput) ? $rec->transUnitsInput : (is_array($rec->transUnits) ? $rec->transUnits : array());
            array_walk($transUnits, function ($e) use (&$count) {
                $count += $e;
            });
            $count = max(1, $count);
        }

        return $count;
    }


    /**
     * Връща детайлни записи
     */
    private function getDetailLabelRecs($rec)
    {
        $dQuery = store_ShipmentOrderDetails::getQuery();
        $dQuery->where("#shipmentId = {$rec->id}");
        $dQuery->orderBy('id', 'ASC');
        $dRecs = $dQuery->fetchAll();

        $batches = array();
        if(core_Packs::isInstalled('batch')){
            $bQuery = batch_BatchesInDocuments::getQuery();
            $bQuery->where("#containerId = {$rec->containerId}");
            while($bRec = $bQuery->fetch()){
                $batches["{$bRec->detailClassId}|{$bRec->detailRecId}"][$bRec->id] = $bRec;
            }
        }

        $recs = array();
        $detailClassId = store_ShipmentOrderDetails::getClassId();
        foreach ($dRecs as $dRec){
            if(array_key_exists("{$detailClassId}|{$dRec->id}", $batches)){
                $rest = $dRec->quantity;
                foreach ($batches["{$detailClassId}|{$dRec->id}"] as $b){
                    $clone = clone $dRec;
                    $clone->batch = $b->batch;
                    $clone->quantity = $b->quantity;
                    $clone->packQuantity = ($clone->quantity / $b->quantityInPack);
                    $clone->packPrice = $b->quantityInPack * $dRec->price;
                    $clone->amount = $clone->quantity * $clone->price;

                    $rest -= $b->quantity;
                    $recs[] = $clone;
                }

                if($rest > 0){
                    $clone = clone $dRec;
                    $clone->quantity = $rest;
                    $clone->packQuantity = ($clone->quantity / $clone->quantityInPack);
                    $clone->packPrice = $clone->quantityInPack * $clone->price;
                    $clone->amount = $clone->quantity * $clone->price;
                    $recs[] = $clone;
                }
            } else {
                $recs[] = $dRec;
            }
        }

        $recs = array_values($recs);
        array_unshift($recs, null);
        unset($recs[0]);

        return $recs;
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
        static $resArr = array();
        
        $key = $id . '|' . $cnt . '|' . $onlyPreview . '|' . core_Lg::getCurrent() . "|" . $series;
        
        if (isset($resArr[$key])) {
            
            return $resArr[$key];
        }
        
        $rec = $this->class->fetchRec($id);

        if($series == 'detail'){

            $handler = "#" . store_ShipmentOrders::getHandle($rec->id);
            $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
            $docReff = $firstDoc->fetchField('reff');
            $saleHandler = $firstDoc->getHandle();

            $recs = $this->getDetailLabelRecs($rec);
            $arr = array();
            for ($i = 1; $i <= $cnt; $i++) {
                $dRec = $recs[$i];
                if(!is_object($dRec))  continue;

                $code = cat_Products::fetchField($dRec->productId, 'code');
                $code = !empty($code) ? $code : "Art{$dRec->productId}";
                $name = trim(cat_Products::getVerbal($dRec->productId, 'name'));
                $measureId = cat_Products::fetchField($dRec->productId, 'measureId');
                $quantityInPack = cat_UoM::round($measureId, $dRec->quantityInPack);
                $measureName = cat_UoM::getShortName($measureId);

                Mode::push('text', 'plain');
                $quantity = core_Type::getByName('double(smartRound)')->toVerbal($dRec->packQuantity);
                $quantityInPack = core_Type::getByName('double(smartRound)')->toVerbal($quantityInPack);
                Mode::pop('text');

                $res = array('CODE' => $code,
                             'PRODUCT_NAME' => $name,
                             'SHIPMENT_ID' => $handler,
                             'SALE_ID' => "#" . $saleHandler,
                             'MEASURE_ID' => $measureName,
                             'PACKAGING_ID' => cat_UoM::getSmartName($dRec->packagingId),
                             'QUANTITY' => $quantity,
                             'QUANTITY_IN_PACK' => $quantityInPack,
                );

                if(!empty($dRec->batch)){
                    if($BatchDef = batch_Defs::getBatchDef($dRec->productId)){
                        Mode::push('text', 'plain');
                        $res['BATCH'] = $BatchDef->toVerbal($dRec->batch);
                        Mode::pop('text');
                    }
                } else {
                    $res['BATCH'] = '';
                }

                if(!empty($dRec->notes)){
                    Mode::push('text', 'plain');
                    $res['NOTES'] = core_Type::getByName('richtext')->toVerbal($dRec->notes);
                    Mode::pop('text');
                }

                if(!empty($docReff)){
                    $res['REFF'] = $docReff;
                    $res['SALE_ID'] .= "/{$docReff}";
                }

                $Driver = cat_Products::getDriver($dRec->productId);
                $additionalFields = $Driver->getAdditionalLabelData($dRec->productId, $this->class);
                if(countR($additionalFields)){
                    $res += $additionalFields;
                }

                $arr[] = $res;
            }
        } else {
            $logisticData = $this->class->getLogisticData($rec);
            $countryName = is_numeric($logisticData['toCountry']) ? drdata_Countries::getTitleById($logisticData['toCountry']) : $logisticData['toCountry'];
            $logisticData['toPlace'] = transliterate($logisticData['toPlace']);
            $destination = trim("{$logisticData['toPCode']} {$logisticData['toPlace']}, {$countryName}");
            $date = dt::mysql2verbal(dt::today(), 'd/m/y');

            $arr = array();
            for ($i = 1; $i <= $cnt; $i++) {
                $res = array('NOMER' => $rec->id, 'DESTINATION' => $destination, 'DATE' => $date);
                if (isset($rec->lineId)) {
                    $res['SPEDITOR'] = trans_Lines::getTitleById($rec->lineId);
                }

                $arr[] = $res;
            }
        }
        
        $resArr[$key] = $arr;
        
        return $resArr[$key];
    }
    
    
    /**
     * Връща дефолтен шаблон за печат на бърз етикет
     *
     * @param int  $id
     * @param stdClass|null  $driverRec
     *
     * @return int
     */
    public function getDefaultFastLabel($id, $driverRec = null)
    {
        return null;
    }
    
    
    /**
     * Връща попълнен дефолтен шаблон с дефолтни данни.
     * Трябва `getDefaultFastLabel` да върне резултат за да се покажат данните
     *
     * @param int  $id
     * @param int $templateId
     *
     * @return core_ET|null
     */
    public function getDefaultLabelWithData($id, $templateId)
    {
        return null;
    }


    /**
     * Кой е дефолтния шаблон за печат към обекта
     *
     * @param $id
     * @param string $series
     * @return int|null
     */
    public function getDefaultLabelTemplateId($id, $series = 'label')
    {
        return null;
    }
}
