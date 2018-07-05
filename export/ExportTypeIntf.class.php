<?php



/**
 * Интерфейс за типове за експортиране на документи
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
class export_ExportTypeIntf
{
    
    /**
     * Клас имплементиращ мениджъра
     */
    public $class;
    
    
    /**
     * Инпортиране на csv-файл в даден мениджър
     *
     * @param integer $clsId
     * @param integer $objId
     *
     * @return boolean
     */
    public function canUseExport($clsId, $objId)
    {
        return $this->class->canUseExport($clsId, $objId);
    }
    
    
    /**
     * Инпортиране на csv-файл в даден мениджър
     *
     * @param integer $clsId
     * @param integer $objId
     *
     * @return string
     */
    public function getExportTitle($clsId, $objId)
    {
        return $this->class->getExportTitle($clsId, $objId);
    }
    
    
    /**
     * Инпортиране на csv-файл в даден мениджър
     *
     * @param core_Form        $form
     * @param integer          $clsId
     * @param integer|stdClass $objId
     *
     * @return NULL|string
     */
    public function makeExport($form, $clsId, $objId)
    {
        return $this->class->makeExport($form, $clsId, $objId);
    }
    
    
    /**
     * Връща линк за експортиране във външната част
     *
     * @param integer $clsId
     * @param integer $objId
     * @param string  $mid
     *
     *
     * @return core_ET|NULL
     */
    public function getExternalExportLink($clsId, $objId, $mid)
    {
        return $this->class->getExternalExportLink($clsId, $objId, $mid);
    }
}
