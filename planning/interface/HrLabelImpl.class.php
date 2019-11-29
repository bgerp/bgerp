<?php


/**
 * Помощен клас-имплементация на интерфейса label_SequenceIntf за класа planning_Hr
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
class planning_interface_HrLabelImpl
{
    /**
     * Инстанция на класа
     */
    public $class;
    
    
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
        $placeholders['QR_CODE'] = (object) array('type' => 'text', 'hidden' => true);
        $placeholders['HR_CODE'] = (object) array('type' => 'text');
        $placeholders['NAME'] = (object) array('type' => 'text');
        
        if (isset($objId)) {
            $labelData = $this->getLabelData($objId, 1, true);
            if (isset($labelData[0])) {
                foreach ($labelData[0] as $key => $val) {
                    if (!array_key_exists($key, $placeholders)) {
                        $placeholders[$key] = (object) array('type' => 'text');
                    }
                    $placeholders[$key]->example = $val;
                }
            }
        }
        
        return $placeholders;
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
        $rec = $this->class->fetchRec($id);
        $name = crm_Persons::getVerbal($rec->personId, 'name');
        $singleUrl = toUrl(array('crm_Persons', 'single', $rec->personId), 'absolute');
        
        $arr = array();
        for ($i = 1; $i <= $cnt; $i++) {
            $res = array('QR_CODE' => $singleUrl, 'HR_CODE' => $rec->code, 'NAME' => $name);
            $arr[] = $res;
        }
       
        return $arr;
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
        $rec = $this->class->fetchRec($id);
        $productName = crm_Persons::getVerbal($rec->personId, 'name');
        $labelName = "QR на \"{$productName}\"";
        
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
}