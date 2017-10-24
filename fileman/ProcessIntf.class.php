<?php


/**
 * Клас 'fileman_ProcessIntf' - Интерфейс за обработка на файлове
 *
 * @category  bgerp
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_ProcessIntf
{
    
    
    /**
     * Пуска обработка на файла
     * 
     * @param stdObject $dRec
     * @param datetime $endOn
     * 
     * @return boolean
     */
    function processFile($dRec, $endOn)
    {
        
        return $this->class->processFile($dRec, $endOn);
    }
}
