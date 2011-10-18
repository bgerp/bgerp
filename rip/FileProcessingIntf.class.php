<?php

 /**
 * Интерфейс за класове, за попълване на клишета
 *
 * @category   bgERP 2.0
 * @package    rip
 * @title:     Попълване на скриптове за обработка на клишета
 * @author     Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright  2006-2011 Experta Ltd.
 * @license    GPL 2
 * @since      v 0.1
 */
class rip_FileProcessingIntf
{
    
    /**
     * Добавя скрипт за конвертиране на файлове
     */
    function processFile($fileId, $id, $combined = FALSE)
    {
        return $this->class->processFile($fileId, $id, $combined);
    }
}