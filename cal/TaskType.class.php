<?php


/**
 * 
 * 
 * @category  bgerp
 * @package   cal
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cal_TaskType extends core_Mvc
{
    
    
    /**
     * 
     */
    public $interfaces = 'cal_TaskTypeIntf';
    
    
    /**
     * 
     */
    public $title = 'Задача';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        
    }
    
    
    /**
     * Може ли вградения обект да се избере
     * 
     * @param NULL|integer $userId
     * 
     * @return boolean
     */
    public function canSelectDriver($userId = NULL)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }
        if ($userId >0) return TRUE;
        
        return FALSE;
    }
    
    
    /**
     * Връща подсказките за добавяне на прогрес
     * 
     * @param  stdClass $tRec
     * 
     * @return array
     */
    public function getProgressSuggestions($tRec)
    {
        $progressArr = array();
        for($i = 0; $i <= 100; $i += 10) {
            $p = $i . ' %';
            $progressArr[$p] = $p;
        }
        
        return $progressArr;
    }
    
    
    /**
     * Подготвя формата за добавя на сигнал от външната част
     *
     * @param core_Form $form
     */
    public function prepareFieldForIssue($form)
    {
        
    }
    
    
    /**
     * Подготвя documentRow за функцията
     * 
     * @param stdClass $rec
     * @param stdClass $row
     */
    public function prepareDocumentRow($rec, $row)
    {
        
    }
    
    
    /**
     * Подготвя getContrangentData за функцията
     *
     * @param stdClass $rec
     * @param stdClass $contrData
     */
    public function prepareContragentData($rec, $contrData)
    {
        
    }
}
