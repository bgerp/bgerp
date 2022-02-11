<?php


/**
 * Помощен клас-имплементация на интерфейса label_SequenceIntf за класа planning_Tasks
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
class planning_interface_TaskLabel
{
    /**
     * Инстанция на класа
     */
    public $class;


    /**
     * Връща наименованието на етикета
     *
     * @param int $id
     *
     * @return string
     */
    public function getLabelName($id)
    {
        $rec = $this->class->fetchRec($id);
        $labelName = planning_Tasks::getTitleById($rec->id);

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
        $placeholders['DATE'] = (object) array('type' => 'text');
        $placeholders['PREVIEW'] = (object) array('type' => 'picture');
        $placeholders['MEASURE_ID'] = (object) array('type' => 'text');
        $placeholders['QUANTITY'] = (object) array('type' => 'text');
        $placeholders['ORDER'] = (object) array('type' => 'text');
        $placeholders['OTHER'] = (object) array('type' => 'text');
        $placeholders['SERIAL'] = (object) array('type' => 'text', 'hidden' => true);
        $placeholders['MATERIAL'] = (object) array('type' => 'text');
        $placeholders['SIZE_UNIT'] = (object) array('type' => 'text');
        $placeholders['SIZE'] = (object) array('type' => 'text');
        $placeholders['EAN'] = (object) array('type' => 'text');

        if (isset($objId)) {
            // Проверка има ли продуктови параметри, които не могат да се редактират от формата
            $taskClassId = planning_Tasks::getClassId();
            $rec = $this->class->fetch($objId);
            $notEditableParamNames = cat_products_Params::getNotEditableLabelParamNames($taskClassId, $rec->id);

            $labelData = $this->getLabelData($objId, 1, true);
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

        expect($rec = planning_Tasks::fetchRec($id));

        // Кое е последното задание към артикула
        $jRec = doc_Containers::getDocument($rec->originId)->fetch();
        $jobCode = mb_strtoupper(planning_Jobs::getHandle($jRec->id));
        if ($lg != 'bg' && isset($jRec->saleId)) {
            $lData = cls::get('sales_Sales')->getLogisticData($jRec->saleId);
            $countryCode = drdata_Countries::fetchField(array("#commonName = '[#1#]'", $lData['toCountry']), 'letterCode2');
            $countryCode .= ' ' . date('m/y');
        }

        $code = (!empty($pRec->code)) ? $pRec->code : "Art{$rec->productId}";
        $name = trim(cat_Products::getVerbal($rec->productId, 'name'));
        $date = date('m/y');

        $ean = null;
        $quantity = $rec->labelQuantityInPack;
        if(empty($quantity) && isset($rec->labelPackagingId)){
            $packRec = cat_products_Packagings::getPack($rec->productId, $rec->labelPackagingId);
            $quantity = is_object($packRec) ? $packRec->quantity : 1;
            $ean = is_object($packRec) ? $rec->eanCode : null;
        }

        $measureId = $rec->measureId;
        $quantity = cat_UoM::round($measureId, $quantity);
        $measureId = cat_UoM::getShortName($measureId);

        Mode::push('text', 'plain');
        $quantity = core_Type::getByName('double(smartRound)')->toVerbal($quantity);
        Mode::pop('text');

        // Продуктови параметри, като тези от операцията са с приоритет
        $params = $this->getTaskParamData($rec->id, $rec->productId);
        $Driver = cat_Products::getDriver($rec->productId);
        $additionalFields = (is_object($Driver)) ? $Driver->getAdditionalLabelData($rec->productId, $this->class) : array();

        if($onlyPreview === false){
            core_App::setTimeLimit(round($cnt / 8, 2), false, 100);
        }

        $batch = null;
        if(core_Packs::isInstalled('batch')){
            if($BatchDef = batch_Defs::getBatchDef($rec->productId)){
                if($BatchDef instanceof batch_definitions_Job){
                    $origin = doc_Containers::getDocument($rec->originId);
                    $batch = $BatchDef->getDefaultBatchName($origin->that);
                }
            }
        }

        $arr = array();
        for ($i = 1; $i <= $cnt; $i++) {
            $res = array('CODE' => $code, 'NAME' => $name, 'DATE' => $date, 'MEASURE_ID' => $measureId, 'QUANTITY' => $quantity, 'JOB' => $jobCode);
            if(!empty($ean)){
                $res['EAN'] = $ean;
            }

            if(!empty($batch)){
                $res['BATCH'] = $batch;
            }

            if (countR($params)) {
                $res = array_merge($res, $params);
            }

            // Допълване на параметрите с тези от драйвера, само за тези за които вече няма дефолтна стойност
            foreach ($additionalFields as $addFieldName => $addFieldValue){
                if(!array_key_exists($addFieldName, $res)){
                    $res[$addFieldName] = $addFieldValue;
                }
            }

            if (isset($countryCode) && empty($res['OTHER'])) {
                $res['OTHER'] = $countryCode;
            }

            $arr[] = $res;
        }

        $resArr[$key] = $arr;

        return $resArr[$key];
    }


    /**
     * Връща масив с плейсхолдъри за продуктови параметри
     *
     * @param $taskId
     * @param $productId
     * @return array
     */
    protected function getTaskParamData($taskId, $productId)
    {
        // Кои продуктови параметри са предефинирани в операцията
        $paramRecs = array();
        $taskClassId = planning_Tasks::getClassId();
        $taskParamQuery = cat_products_Params::getQuery();
        $taskParamQuery->where("#productId = {$taskId} AND #classId = {$taskClassId}");
        while($pRec = $taskParamQuery->fetch()){
            $paramRecs[$pRec->paramId] = $pRec->paramValue;
        }

        // От останалите продуктови параметри, се извличат тези, които вече не са предефинирани
        $productParams = cat_Products::getParams($productId, null, false);
        foreach ($productParams as $paramId => $paramValue){
            if(!array_key_exists($paramId, $paramRecs)){
                $paramRecs[$paramId] = $paramValue;
            }
        }

        // Вербализират се
        $res = array();
        foreach ($paramRecs as $pId => $pVal){
            $ParamType = cat_Params::getTypeInstance($pId, $taskClassId, $productId, $pVal);
            if($ParamType instanceof fileman_FileType){
                $paramValue = $pVal;
            } else {
                $paramValue = $ParamType->toVerbal(trim($pVal));
            }
            $res[$pId] = $paramValue;
        }

        $res = cat_Params::getParamNameArr($res, true);

        return $res;
    }


    /**
     * Броя на етикетите, които могат да се отпечатат
     *
     * @param int    $id
     * @param string $allowSkip
     *
     * @return int
     * @see label_SequenceIntf
     */
    public function getLabelEstimatedCnt($id)
    {
        $rec = $this->class->fetchRec($id);
        if(!empty($rec->labelQuantityInPack)){
            $count = $rec->plannedQuantity / $rec->labelQuantityInPack;

            return ceil($count);
        }
    }


    /**
     * Кой е дефолтния шаблон за печат към обекта
     *
     * @param $id
     * @return int|null
     */
    public function getDefaultLabelTemplateId($id)
    {
        $rec = $this->class->fetchRec($id);
        if(isset($rec->labelTemplate)){
            return $rec->labelTemplate;
        }

        return null;
    }
}