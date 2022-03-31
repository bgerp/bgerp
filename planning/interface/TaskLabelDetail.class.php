<?php


/**
 * Помощен клас-имплементация на интерфейса label_SequenceIntf за класа cat_products_Packagings
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2018 Experta OOD
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
        $defaultRec = label_Templates::fetchField("#classId={$this->class->getClassId()} AND #peripheralDriverClassId = {$driverRec->driverClass}");
       
        return $defaultRec;
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
        $template = label_Templates::fetch($templateId);
        $templateTpl = new core_ET($template->template);
        
        // Взимат се данните за бърз етикет
        $labelData = $this->getLabelData($id, 1, false);
        $content = $labelData[0];
        $templateTpl->placeObject($content);
        
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
        $rowInfo = planning_ProductionTaskProducts::getInfo($rec->taskId, $rec->productId, $rec->type);
        $productName = trim(cat_Products::getVerbal($rec->productId, 'name'));

        core_Lg::push('en');
        $quantity = $rec->quantity . " " . cat_UoM::getShortName($rowInfo->measureId);
        $weight = (!empty($rec->weight)) ? core_Type::getByName('cat_type_Weight')->toVerbal($rec->weight) : null;
        core_Lg::pop('en');

        $date = dt::mysql2verbal($rec->createdOn, 'd.m.Y');
        $Origin = doc_Containers::getDocument(planning_Tasks::fetchField($rec->taskId, 'originId'));

        $batch = null;
        if($BatchDef = batch_Defs::getBatchDef($rec->productId)){
            if($BatchDef instanceof batch_definitions_Job){
                $batch = $BatchDef->getDefaultBatchName($Origin->that);
            }
        }

        $arr = array();
        for ($i = 1; $i <= $cnt; $i++) {
            $res = array('PRODUCT_NAME' => $productName, 'QUANTITY' => $quantity, 'DATE' => $date, 'WEIGHT' => $weight, 'SERIAL' => $rec->serial, 'SERIAL_STRING' => $rec->serial, 'JOB' => "#" . $Origin->getHandle());
            $res['BATCH'] = $batch;

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
