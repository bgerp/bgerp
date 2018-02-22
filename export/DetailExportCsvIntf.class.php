<?php


/**
 * Интерфейс за експорт към CSV на детайл
 * 
 * 
 * @category  bgerp
 * @package   export
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за типове за експортиране на документи
 */
class export_DetailExportCsvIntf
{
    
    
    /**
     * Клас имплементиращ мениджъра
     */
    public $class;
    
    
    /**
     * 
     * 
     * @return string
     */
    function getExportMasterFieldName()
    {
        
        return $this->class->getExportMasterFieldName();
    }
    
    
    /**
     * 
     * 
     * @return array
     */
    function getExportFieldsNameFromMaster()
    {
        
        return $this->class->getExportFieldsNameFromMaster();
    }
    
    
    /**
     * Инпортиране на csv-файл в даден мениджър
     * 
     * @param core_Master $masterMvc
     * @param stdClass $mRec
     * @param core_FieldSet $csvFields
     * @param integer $activatedBy
     * 
     * @return array
     */
    function getRecsForExportInDetails($masterMvc, $mRec, &$csvFields, $activatedBy)
    {
        
        return $this->class->getRecsForExportInDetails($masterMvc, $mRec, $csvFields, $activatedBy);
    }
}
