<?php


/**
 * Клас 'docoffice_ConverterIntf' - Интерфейс за 
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
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
     * 				$params['ext'] - Разширението, от което се конвертира /Разширението на файла/
     * 				$params['fileInfoId'] - id към bgerp_FileInfo
     * 				$params['asynch'] - Дали скрипта да се стартира асинхронно или не
     */
    function convertDoc($fileHnd, $toExt, $params=array())
    {
        
        return $this->class-convertDoc($fileHnd, $toExt, $params);
    }
}