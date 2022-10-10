<?php


/**
 * Помощен клас-имплементация на интерфейса label_SequenceIntf за детайла на производствените операции
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
class planning_interface_TaskLabelDetail extends planning_interface_TaskLabel
{
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
        $labelName = planning_Tasks::getTitleById($rec->taskId);

        return $labelName;
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
        $query = label_Templates::getQuery();
        $query->where("#classId={$this->class->getClassId()} AND #peripheralDriverClassId = {$driverRec->driverClass} AND #state != 'closed'");
        $query->orderBy('id', 'DESC');
        $query->show('id');

        return $query->fetch()->id;
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
        $templateTpl = label_Templates::addCssToTemplate($templateId);

        // Взимат се данните за бърз етикет
        $allLabelData = $this->getLabelData($id, 1, false);

        $placeArr = label_Templates::getPlaceholders($templateTpl);

        foreach ($allLabelData as $allKey => $labelData) {
            foreach ($labelData as $lKey => $lVal) {
                $place = $placeArr[$lKey];
                $newVal = label_TemplateFormats::getVerbalTemplate($templateId, $place, $lVal);
                $allLabelData[$allKey][$lKey] = strlen($newVal) ? $newVal : $allLabelData[$allKey][$lKey];
            }
        }

        $templateTpl = new ET($templateTpl);
        $templateTpl->placeObject($allLabelData[0]);
        
        return $templateTpl->getContent();
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

        expect($rec = planning_ProductionTaskDetails::fetchRec($id));
        $taskRec = planning_Tasks::fetch($rec->taskId);
        $Origin = doc_Containers::getDocument($taskRec->originId);
        $jRec = $Origin->fetch();

        $jobProductName = trim(cat_Products::getVerbal($jRec->productId, 'name'));
        $jobProductCode = cat_Products::getVerbal($jRec->productId, 'code');

        $productName = trim(cat_Products::getVerbal($rec->productId, 'name'));
        $productCode = cat_Products::getVerbal($rec->productId, 'code');

        $stepProductName = trim(cat_Products::getVerbal($taskRec->productId, 'name'));
        $stepProductCode = cat_Products::getVerbal($taskRec->productId, 'code');

        $productId = ($rec->isFinal == 'yes') ? $jRec->productId : $rec->productId;
        $rowInfo = planning_ProductionTaskProducts::getInfo($rec->taskId, $productId, $rec->type);

        $quantity = $rec->quantity . " " . cat_UoM::getShortName($rowInfo->measureId);
        $weight = (!empty($rec->weight)) ? core_Type::getByName('cat_type_Weight')->toVerbal($rec->weight) : null;
        $nettWeight = (!empty($rec->netWeight)) ? core_Type::getByName('cat_type_Weight')->toVerbal($rec->netWeight) : null;

        $batch = null;
        $date = dt::mysql2verbal($rec->createdOn, 'd.m.Y');
        if($BatchDef = batch_Defs::getBatchDef($productId)){
            if(!empty($rec->batch)){
                $batch = $rec->batch;
            }
        }

        $singleUrl = toUrl(array('planning_Tasks', 'single', $rec->taskId), 'absolute');
        $saleId = $clientName = null;
        if(isset($jRec->saleId)){
            $saleId = "#" . sales_Sales::getHandle($jRec->saleId);
            $saleRec = sales_Sales::fetch($jRec->saleId, 'reff, contragentClassId,contragentId');
            $reff = !empty($saleRec->reff) ? $saleRec->reff : null;
            $clientName = cls::get($saleRec->contragentClassId)->getVerbal($saleRec->contragentId, 'name');
        }
        $operatorName = core_Users::getVerbal($rec->createdBy, 'names');
        $notes = !empty($rec->notes) ? core_Type::getByName('richtext')->toHtml($rec->notes) : null;
        $params = self::getTaskParamData($rec->taskId, $rec->productId);

        $arr = array();
        for ($i = 1; $i <= $cnt; $i++) {
            $res = array('OPERATOR' => $operatorName, 'STEP_PRODUCT_NAME' => $stepProductName, 'STEP_PRODUCT_CODE' => $stepProductCode,'JOB_PRODUCT_NAME' => $jobProductName, 'JOB_PRODUCT_CODE' => $jobProductCode, 'QR_CODE' => $singleUrl, 'PRODUCT_NAME' => $productName, 'CODE' => $productCode, 'QUANTITY' => $quantity, 'DATE' => $date, 'WEIGHT' => $weight, 'SERIAL' => $rec->serial, 'SERIAL_STRING' => $rec->serial, 'JOB' => "#" . $Origin->getHandle(), 'NETT_WEIGHT' => $nettWeight, 'NOTES' => $notes);
            if(!empty($batch)){
                $res['BATCH'] = $BatchDef->toVerbal($batch);
            }

            if(!empty($reff)){
                $res['REFF'] = $reff;
            }

            if(!empty($saleId)){
                $res['SALE_ID'] = $saleId;
                $res['CLIENT_NAME'] = $clientName;
            }

            if (countR($params)) {
                $res = array_merge($res, $params);
            }

            $arr[] = $res;
        }
        $resArr[$key] = $arr;

        return $resArr[$key];
    }


    /**
     * Кой е дефолтния шаблон за печат към обекта
     *
     * @param $id
     * @return int|null
     */
    public function getDefaultLabelTemplateId($id)
    {
        return null;
    }
}
