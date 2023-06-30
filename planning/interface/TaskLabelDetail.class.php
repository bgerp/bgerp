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
     * @param string $series
     * @return string
     */
    public function getLabelName($id, $series = 'label')
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
        $allLabelData = $this->getLabelData($id, 1, false, null, $series);

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

        expect($rec = planning_ProductionTaskDetails::fetchRec($id));

        // Ако има със същия сериен номер да се третират като един запис
        $dQuery = planning_ProductionTaskDetails::getQuery();
        $dQuery->where("#type = '{$rec->type}' AND #serial = {$rec->serial} AND #id != {$rec->id} AND #state != 'rejected'");
        while($dRec = $dQuery->fetch()){
            $rec->employees = keylist::merge($rec->employees, $dRec->employees);
            $rec->quantity += $dRec->quantity;
            $rec->weight += $dRec->weight;
            $rec->netWeight += $dRec->netWeight;
        }

        $taskRec = planning_Tasks::fetch($rec->taskId);
        $Origin = doc_Containers::getDocument($taskRec->originId);
        $jRec = $Origin->fetch();

        $jobProductName = trim(cat_Products::getVerbal($jRec->productId, 'name'));
        $jobProductCode = cat_Products::getVerbal($jRec->productId, 'code');

        $productName = trim(cat_Products::getVerbal($rec->productId, 'name'));
        $productCode = cat_Products::getVerbal($rec->productId, 'code');

        $stepProductName = trim(cat_Products::getVerbal($taskRec->productId, 'name'));
        $stepProductCode = cat_Products::getVerbal($taskRec->productId, 'code');
        $rowInfo = planning_ProductionTaskProducts::getInfo($rec->taskId, $rec->productId, $rec->type);

        $quantity = $rec->quantity;
        if(planning_ProductionTaskProducts::isProduct4Task($taskRec->id, $rec->productId)){
            $quantity /= $taskRec->quantityInPack;
        }

        $quantity = $quantity . " " . cat_UoM::getShortName($rowInfo->measureId);
        Mode::push('text', 'plain');
        $weight = (!empty($rec->weight)) ? core_Type::getByName('cat_type_Weight(smartRound=no)')->toVerbal($rec->weight) : null;
        $nettWeight = (!empty($rec->netWeight)) ? core_Type::getByName('cat_type_Weight(smartRound=no)')->toVerbal($rec->netWeight) : null;
        Mode::pop('text');

        $batch = null;
        $date = dt::mysql2verbal($rec->createdOn, 'd.m.Y');
        if($BatchDef = batch_Defs::getBatchDef($jRec->productId)){
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

        $notes = !empty($rec->notes) ? core_Type::getByName('richtext')->toHtml($rec->notes) : null;
        $params = self::getTaskParamData($rec->taskId, $jRec->productId);

        $createdBy = core_Users::getVerbal($rec->createdBy, 'names');
        $currentUser = core_Users::getVerbal(core_Users::getCurrent(), 'names');
        $employees = implode(', ', planning_Hr::getPersonsCodesArr(keylist::toArray($rec->employees)));

        $Driver = cat_Products::getDriver($rec->productId);
        $additionalFields = (is_object($Driver)) ? $Driver->getAdditionalLabelData($rec->productId, $this->class) : array();
        $arr = array();
        for ($i = 1; $i <= $cnt; $i++) {
            $res = array('EMPLOYEES' => $employees, 'CURRENT_USER' => $currentUser, 'CREATED_BY' => $createdBy, 'STEP_PRODUCT_NAME' => $stepProductName, 'STEP_PRODUCT_CODE' => $stepProductCode,'JOB_PRODUCT_NAME' => $jobProductName, 'JOB_PRODUCT_CODE' => $jobProductCode, 'QR_CODE' => $singleUrl, 'PRODUCT_NAME' => $productName, 'CODE' => $productCode, 'QUANTITY' => $quantity, 'DATE' => $date, 'WEIGHT' => $weight, 'SERIAL' => $rec->serial, 'SERIAL_STRING' => $rec->serial, 'JOB' => "#" . $Origin->getHandle(), 'NETT_WEIGHT' => $nettWeight, 'NOTES' => $notes);
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

            // Допълване на параметрите с тези от драйвера, само за тези за които вече няма дефолтна стойност
            foreach ($additionalFields as $addFieldName => $addFieldValue){
                if(!array_key_exists($addFieldName, $res)){
                    $res[$addFieldName] = $addFieldValue;
                }
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
     * @param string $series
     * @return int|null
     */
    public function getDefaultLabelTemplateId($id, $series = 'label')
    {
        return null;
    }
}
