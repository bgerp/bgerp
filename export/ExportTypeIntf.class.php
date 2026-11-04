<?php


/**
 * Интерфейс за типове за експортиране на документи
 *
 *
 * @category  bgerp
 * @package   export
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
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
     * Импортиране на csv-файл в даден мениджър
     *
     * @param int $clsId
     * @param int $objId
     *
     * @return bool
     */
    public function canUseExport($clsId, $objId)
    {
        return $this->class->canUseExport($clsId, $objId);
    }
    
    
    /**
     * Импортиране на csv-файл в даден мениджър
     *
     * @param int $clsId
     * @param int $objId
     *
     * @return string
     */
    public function getExportTitle($clsId, $objId)
    {
        return $this->class->getExportTitle($clsId, $objId);
    }
    
    
    /**
     * Изпълнява експорта в общ обхват, който приключва и при грешка или redirect.
     *
     * @param core_Form    $form
     * @param int          $clsId
     * @param int|stdClass $objId
     *
     * @return NULL|string
     */
    public function makeExport($form, $clsId, $objId)
    {
        $scope = new stdClass();
        $result = null;
        $dispatcher = cls::get('export_Export');
        try {
            $dispatcher->invoke('BeginExport', array($scope, $form, $clsId, $objId));
            $result = $this->class->makeExport($form, $clsId, $objId);
        } finally {
            $dispatcher->invoke('EndExport', array($scope, &$result));
        }

        return $result;
    }
    
    
    /**
     * Връща линк за експортиране във външната част
     *
     * @param int    $clsId
     * @param int    $objId
     * @param string $mid
     *
     * @return core_ET|NULL
     */
    public function getExternalExportLink($clsId, $objId, $mid)
    {
        return $this->class->getExternalExportLink($clsId, $objId, $mid);
    }
    
    
    /**
     * Добавя параметри към експорта на формата
     *
     * @param core_Form    $form
     * @param int          $clsId
     * @param int|stdClass $objId
     *
     * @return NULL|string
     */
    public function addParamFields($form, $clsId, $objId)
    {
        return $this->class->addParamFields($form, $clsId, $objId);
    }
    
}
