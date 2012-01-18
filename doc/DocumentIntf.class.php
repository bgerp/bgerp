<?php


/**
 * Клас 'doc_DocumentIntf' - Интерфейс за мениджърите на документи
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_DocumentIntf
{
    function route($rec)
    {
        $this->class->route($rec);
    }
    
    function getHandle($id)
    {
        return $this->class->getHandle($id);
    }
    
    
    /**
     * Връща данните за получателя
     */
    function getContragentData($id)
    {
        return $this->class->getContragentData($id);
    }
}