<?php


/**
 * Интерфейс за конвертиране в pdf
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doc_ConvertToPdfIntf
{
    /**
     * Конвертира html към pdf файл
     *
     * @param string $html       - HTML стинга, който ще се конвертира
     * @param string $fileName   - Името на изходния pdf файл
     * @param string $bucketName - Името на кофата, където ще се записват данните
     * @param array  $jsArr      - Масив с JS и JQUERY_CODE
     *
     * @return string $fh - Файлов манипулатор на новосъздадения pdf файл
     */
    public function convert($html, $fileName, $bucketName, $jsArr = array())
    {
        return $this->class->convert($html, $fileName, $bucketName, $jsArr);
    }
    
    
    /**
     * Проверява дали програмата е инсталирана и работи
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->class->isEnabled();
    }
}
