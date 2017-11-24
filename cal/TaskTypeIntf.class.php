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
class cal_TaskTypeIntf extends embed_DriverIntf
{
    
    
    /**
     * Връща подсказките за добавяне на прогрес
     * 
     * @param stdClass $tRec
     * 
     * @return array
     */
    public function getProgressSuggestions($tRec)
    {
        
        return $this->class->getProgressSuggestions($tRec);
    }
    
    
    /**
     * Подготвя формата за добавя на сигнал от външната част
     * 
     * @param core_Form $form
     */
    public function prepareFieldForIssue($form)
    {
        
        return $this->class->prepareFieldForIssue($tRec);
    }
    
    
    /**
     * Подготвя documentRow за функцията
     * 
     * @param stdClass $rec
     * @param stdClass $row
     */
    public function prepareDocumentRow($rec, $row)
    {
        
        return $this->class->prepareDocumentRow($rec, $row);
    }
    
    
    /**
     * Подготвя getContrangentData за функцията
     *
     * @param stdClass $rec
     * @param stdClass $contrData
     */
    public function prepareContragentData($rec, $contrData)
    {
        return $this->class->prepareContragentData($rec, $contrData);
    }
}
