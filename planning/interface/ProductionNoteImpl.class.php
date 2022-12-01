<?php


/**
 * Помощен клас-имплементация на интерфейса label_SequenceIntf за класа planning_DirectProductionNotes
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see label_SequenceIntf
 *
 */
class planning_interface_ProductionNoteImpl
{
    /**
     * Инстанция на класа
     */
    public $class;
    
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
     * Връща наименованието на етикета
     *
     * @param int $id
     * @param string $series
     * @return string
     */
    public function getLabelName($id, $series = 'label')
    {
        $labelName = $this->class->getTitleById($id);
        
        return $labelName;
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
        $placeholders['JOB'] = (object) array('type' => 'text');
        $placeholders['CODE'] = (object) array('type' => 'text');
        $placeholders['NAME'] = (object) array('type' => 'text');
        $placeholders['PREVIEW'] = (object) array('type' => 'picture');
        $placeholders['MEASURE_ID'] = (object) array('type' => 'text');
        $placeholders['QUANTITY'] = (object) array('type' => 'text');
        $placeholders['ORDER'] = (object) array('type' => 'text');
        $placeholders['BATCH'] = (object) array('type' => 'text');
        $placeholders['OTHER'] = (object) array('type' => 'text');
        $placeholders['SERIAL'] = (object) array('type' => 'text', 'hidden' => true);
        $placeholders['MATERIAL'] = (object) array('type' => 'text');
        $placeholders['SIZE_UNIT'] = (object) array('type' => 'text');
        $placeholders['SIZE'] = (object) array('type' => 'text');
        $placeholders['VALIOR'] = (object) array('type' => 'text');
        $placeholders['NET_WEIGHT'] = (object) array('type' => 'text');
        $placeholders['GROSS_WEIGHT'] = (object) array('type' => 'text');
        $placeholders['EXPIRY_TIME'] = (object) array('type' => 'text');
        $placeholders['EXPIRY_DATE'] = (object) array('type' => 'text');
        $placeholders['QR_CODE'] = (object) array('type' => 'text', 'hidden' => true);
        $placeholders['QR_CODE_90'] = (object) array('type' => 'text', 'hidden' => true);

        if (isset($objId)) {
            
            // Проверка има ли продуктови параметри, които не могат да се редактират от формата
            $productClassId = cat_Products::getClassId();
            $rec = $this->class->fetch($objId);

            $notEditableParamNames = cat_products_Params::getNotEditableLabelParamNames($productClassId, $rec->productId);
            $labelData = $this->getLabelData($objId, 1, true, null, $series);

            if (isset($labelData[0])) {
                foreach ($labelData[0] as $key => $val) {
                    if (!array_key_exists($key, $placeholders)) {
                        $placeholders[$key] = (object) array('type' => 'text');
                    }
                    $placeholders[$key]->example = $val;
                    
                    if(array_key_exists($key, $notEditableParamNames)){
                        $placeholders[$key]->hidden = true;
                    }
                }

                $batches = $this->getBatchesOptions($rec);
                if(countR($batches) > 1){
                    $placeholders['BATCH']->suggestions = array('' => '') + $batches;
                }
            }
        }
       
        return $placeholders;
    }


    /**
     * Опции за наличните партиди в документа
     *
     * @param $rec
     * @return array
     */
    function getBatchesOptions($rec)
    {
        $batchesArr = array();
        if($BatchDef = batch_Defs::getBatchDef($rec->productId)){

            $bQuery = batch_BatchesInDocuments::getQuery();
            $bQuery->where("#detailClassId = {$this->class->getClassId()} AND #detailRecId = {$rec->id}");
            $bQuery->orderBy('quantity', 'DESC');
            $bQuery->show('batch');

            while($bRec = $bQuery->fetch()){
                $bArr = batch_Defs::getBatchArray($rec->productId, $bRec->batch);
                foreach ($bArr as $k => $v){
                    $bVerbal = strip_tags($BatchDef->toVerbal($k));
                    $batchesArr[$bVerbal] = $bVerbal;
                }
            }
        }

        return $batchesArr;
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
        $lg = core_Lg::getCurrent();
        
        $key = $id . '|' . $cnt . '|' . $onlyPreview . '|' . $lg;
        
        if (isset($resArr[$key])) {
            
            return $resArr[$key];
        }
        
        expect($rec = $this->class->fetchRec($id));
        $pRec = cat_Products::fetch($rec->productId, 'code,measureId');
        $packRec = cat_products_Packagings::getPack($rec->productId, $rec->packagingId);
        $quantity = is_object($packRec) ? $packRec->quantity : 1;
        
        // Каква е мярката и количеството
        $measureId = $pRec->measureId;

        $jobRec = planning_DirectProductionNote::getJobRec($rec);
        $jobHandle = "#" . planning_Jobs::getHandle($jobRec);
        
        $code = (!empty($pRec->code)) ? $pRec->code : "Art{$rec->productId}";
        $name = trim(cat_Products::getVerbal($rec->productId, 'name'));

        // Ако мярката е 'хил. бр' и к-то е под 10 да се каства към бройки
        $thousandPcsId = cat_UoM::fetchBySysId('K pcs')->id;
        if($measureId == $thousandPcsId && $quantity < 10){
            $quantity *= 1000;
            $measureId = cat_UoM::fetchBySysId('pcs')->id;
        }
        
        // Продуктови параметри
        $measureId = cat_UoM::getShortName($measureId);
        Mode::push('dontVerbalizeText', true);
        $params = cat_Products::getParams($rec->productId, null, true);
        Mode::pop();
        $params = cat_Params::getParamNameArr($params, true);
        
        $additionalFields = array();
        $Driver = cat_Products::getDriver($rec->productId);
        if (is_object($Driver)) {
            $additionalFields = $Driver->getAdditionalLabelData($rec->productId, $this->class);
        }
        
        // Дигане на тайм-лимита
        if($onlyPreview === false){
            core_App::setTimeLimit(round($cnt / 8, 2), false, 100);
        }

        // Ако има само една партида, показвасе и тя
        $batchesArr = $this->getBatchesOptions($rec);
        $batch = (countR($batchesArr)) ? $batchesArr[key($batchesArr)] : null;

        $kgId = cat_Uom::fetchBySysId('kg')->id;
        $kgDerivities = cat_UoM::getSameTypeMeasures($kgId);
        unset($kgDerivities['']);

        $mpnWeight = $mpnWeightVerbal = null;
        if(array_key_exists($rec->additionalMeasureId, $kgDerivities)){
            $mpnWeight = cat_UoM::convertValue($rec->additionalMeasureQuantity, $rec->additionalMeasureId, $kgId);
        } elseif(array_key_exists($rec->packagingId, $kgDerivities)){
            $mpnWeight = cat_UoM::convertValue($rec->quantity, $rec->packagingId, $kgId);
        }

        if(!empty($mpnWeight)){
            Mode::push('text', 'plain');
            $mpnWeightVerbal = core_Type::getByName('double(smartRound)')->toVerbal($mpnWeight);
            $mpnWeightVerbal = "{$mpnWeightVerbal} " . tr(cat_UoM::getTitleById($kgId));
            Mode::pop();
        }

        $expiryTime = cat_Products::getParams($rec->productId, 'expiryTime');
        $date = dt::mysql2verbal($rec->valior, 'd.m.Y');
        $singleUrl = toUrl(array($this->class, 'single', $rec->id), 'absolute');
        $arr = array();
        for ($i = 1; $i <= $cnt; $i++) {
            $res = array('CODE' => $code, 'NAME' => $name, 'MEASURE_ID' => $measureId, 'QUANTITY' => $quantity, 'JOB' => $jobHandle, 'VALIOR' => $date, 'QR_CODE' => $singleUrl, 'QR_CODE_90' => $singleUrl);
            if(isset($batch)){
                $res['BATCH'] = $batch;
            }
            
            if(isset($mpnWeightVerbal)){
                $res['WEIGHT_MPN'] = $mpnWeightVerbal;
            }
            
            if(!empty($expiryTime)){
                $res['EXPIRY_TIME'] = core_Type::getByName('time')->toVerbal($expiryTime);
                $expiryDate = dt::addSecs($expiryTime, $rec->valior);
                $expiryDateVerbal = dt::mysql2verbal($expiryDate, 'd.m.Y');
                $res['EXPIRY_DATE'] = $expiryDateVerbal;
            }
            
            if (countR($params)) {
                $res = array_merge($res, $params);
            }
            
            if (is_object($Driver)) {
                if (countR($additionalFields)) {
                    $res = $additionalFields + $res;
                }
            }
            
            $arr[] = $res;
        }
        
        $resArr[$key] = $arr;

        return $resArr[$key];
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
        
        return $rec->packQuantity;
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