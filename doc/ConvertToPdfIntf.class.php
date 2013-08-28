<?php


 /**
 * Интерфейс за конвертиране в pdf
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_ConvertToPdfIntf
{
    
    
    /**
     * Конвертира html към pdf файл
     * 
     * @param string $html - HTML стинга, който ще се конвертира
     * @param string $fileName - Името на изходния pdf файл
     * @param string $bucketName - Името на кофата, където ще се записват данните
     *
     * @return string $fh - Файлов манипулатор на новосъздадения pdf файл
     */
    function convert($html, $fileName, $bucketName)
    {
        
        return $this->class->convert($html, $fileName, $bucketName);
    }
}
