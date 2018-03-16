<?php


/**
 * Клас 'docoffice_ConverterIntf' - Интерфейс за конвертиране на office документи
 *
 * @category  vendors
 * @package   docoffice
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за конвертиране на office документи
 */
class docoffice_ConverterIntf
{

    
    /**
     * Конвертира office документи
     * 
     * @param fileHandler $fileHnd - Манупулатора на файла, който ще се конвертира
     * @param string $toExt - Разширението, в което ще се конвертира
     * @param array $params - Други параметри
     * 				$params['callBack'] - Класа и функцията, която ще се извикат след приключване на конвертирането
     * 				$params['fileInfoId'] - id към bgerp_FileInfo
     * 				$params['asynch'] - Дали скрипта да се стартира асинхронно или не
     * 
     * @return string|NULL
     */
    function convertDoc($fileHnd, $toExt, &$params=array())
    {
        
        return $this->class->convertDoc($fileHnd, $toExt, $params);
    }
}