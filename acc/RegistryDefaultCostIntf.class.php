<?php


/**
 * Интерфейс за регистри на пера, които задължително трябва да имат цена при контировката
 *
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за регистри на пера, които задължително трябва да имат цена при контировката
 */
class acc_RegistryDefaultCostIntf
{
    /**
     * Инстанция на мениджъра имащ интерфейса
     */
    public $class;
    
    
    /**
     * Връща дефолтната цена отговаряща на количеството
     *
     * @param mixed $id - ид/запис на обекта
     */
    public function getDefaultCost($id)
    {
        $this->class->getDefaultCost($id);
    }
}
