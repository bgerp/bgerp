<?php


/**
 *
 *
 * @category  bgerp
 * @package   fileman
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class fileman_import_Base64 extends core_Mvc
{
    /**
     * Интерфейсни методи
     */
    public $interfaces = 'fileman_ConvertDataIntf';
    
    
    /**
     * Заглавие на модела
     */
    public $title = '';
    
    
    /**
     * Обработките на данните, за файла
     *
     * @param string $data
     *
     * @see fileman_ConvertDataIntf
     *
     * @return string
     */
    public function convertData($data)
    {
        return base64_decode($data);
    }
}
