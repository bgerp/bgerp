<?php


/**
 * Интерфейс за конвертирани на данните на файла след обновяване
 *
 * @category  bgerp
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за типове за експортиране на документи
 */
class fileman_ConvertDataIntf
{
    
    
    /**
     * Клас имплементиращ мениджъра
     */
    public $class;
    
    
    /**
     * Обработките на данните, за файла
     *
     * @param string $data
     *
     * @return string
     */
    public function convertData($data)
    {
        return $this->class->convertData($data);
    }
}
