<?php


/**
 * Помощен клас-имплементация на интерфейса label_SequenceIntf за класа planning_DirectProductionNotes
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2020 Experta OOD
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
     *
     * @return string
     */
    public function getLabelName($id)
    {
        $labelName = strip_tags($this->class->getTitleById($id));
        
        return $labelName;
    }
    
    
    /**
     * Връща масив с данните за плейсхолдерите
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
    public function getLabelPlaceholders($objId = null)
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
        
        if (isset($objId)) {
            
            // Проверка има ли продуктови параметри, които не могат да се редактират от формата
            $productClassId = cat_Products::getClassId();
            $rec = $this->class->fetch($objId);
            $notEdittableParamNames = cat_products_Params::getNotEditableLabelParamNames($productClassId, $rec->productId);
            $labelData = $this->getLabelData($objId, 1, true);
           
            if (isset($labelData[0])) {
                foreach ($labelData[0] as $key => $val) {
                    if (!array_key_exists($key, $placeholders)) {
                        $placeholders[$key] = (object) array('type' => 'text');
                    }
                    $placeholders[$key]->example = $val;
                    
                    if(array_key_exists($key, $notEdittableParamNames)){
                        $placeholders[$key]->hidden = true;
                    }
                }
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
     *
     * @return array - масив от масиви с ключ плейсхолдера и стойността
     */
    public function getLabelData($id, $cnt, $onlyPreview = false)
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
        
        $origin = doc_Containers::getDocument($rec->originId);
        $jobId = $origin->that;
        if($origin->isInstanceOf('planning_Tasks')){
            $taskOrigin = doc_Containers::getDocument($origin->that);
            $jobId = $taskOrigin->that;
        }
        $jobHandle = "#" . $origin->getHandle($jobId);
        
        $code = (!empty($pRec->code)) ? $pRec->code : "Art{$rec->productId}";
        $name = trim(cat_Products::getVerbal($rec->productId, 'name'));
        $quantity = cat_UoM::round($measureId, $quantity);
        
        // Ако мярката е 'хил. бр' и к-то е под 10 да се каства към бройки
        $thousandPcsId = cat_UoM::fetchBySysId('K pcs')->id;
        if($measureId == $thousandPcsId && $quantity < 10){
            $quantity *= 1000;
            $measureId = cat_UoM::fetchBySysId('pcs')->id;
        }
        
        // Продуктови параметри
        $measureId = cat_UoM::getShortName($measureId);
        $params = cat_Products::getParams($rec->productId, null, true);
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
        $batch = null;
        if($BatchDef = batch_Defs::getBatchDef($rec->productId)){
            $bQuery = batch_BatchesInDocuments::getQuery();
            $bQuery->where("#detailClassId = {$this->class->getClassId()} AND #detailRecId = {$rec->id}");
            $bQuery->show('batch');
            if($bQuery->count() == 1){
                $bRec = $bQuery->fetch();
                $batchArr = batch_Defs::getBatchArray($rec->productId, $bRec->batch);
                if(countR($batchArr) == 1){
                    $batch = $BatchDef->toVerbal($batchArr[key($batchArr)]);
                    $batch = strip_tags($batch);
                }
            }
        }
        
        $grossWeight = null;
        $kgId = cat_Uom::fetchBySysId('kg')->id;
        if($netWeight = cat_Products::convertToUom($rec->productId, 'kg')){
            $netWeightVerbal = cat_UoM::round($kgId, $netWeight);
            $netWeightVerbal = core_Type::getByName('double(smartRound)')->toVerbal($netWeightVerbal);
            $netWeightVerbal = "{$netWeightVerbal} " . tr(cat_UoM::getTitleById($kgId));
            
            if(!empty($packRec->tareWeight)){
                $grossWeight = $netWeight + $packRec->tareWeight;
                $grossWeightVerbal = cat_UoM::round($kgId, $grossWeight);
                $grossWeightVerbal = core_Type::getByName('double(smartRound)')->toVerbal($grossWeight);
                $grossWeightVerbal = "{$grossWeightVerbal} " . tr(cat_UoM::getTitleById($kgId));
            }
        }
       
        $expiryTime = cat_Products::getParams($rec->productId, 'expiryTime');
        $date = dt::mysql2verbal($rec->valior, 'd.m.Y');
        $singleUrl = toUrl(array($this->class, 'single', $rec->id), 'absolute');
        $arr = array();
        for ($i = 1; $i <= $cnt; $i++) {
            $res = array('CODE' => $code, 'NAME' => $name, 'MEASURE_ID' => $measureId, 'QUANTITY' => $quantity, 'JOB' => $jobHandle, 'VALIOR' => $date, 'NET_WEIGHT' => $netWeightVerbal, 'QR_CODE' => $singleUrl);
            if(isset($batch)){
                $res['BATCH'] = $batch;
            }
            
            if(isset($grossWeight)){
                $res['GROSS_WEIGHT'] = $grossWeightVerbal;
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
     * @param int    $id
     * @param string $allowSkip
     *
     * @return int
     *
     * @see label_SequenceIntf
     */
    public function getLabelEstimatedCnt($id)
    {
        $rec = $this->class->fetchRec($id);
        
        return $rec->packQuantity;
    }
}