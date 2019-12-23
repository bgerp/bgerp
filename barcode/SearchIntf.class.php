<?php


/**
 * Клас 'barcode_SearchIntf' - Интерфейс за търсене по баркод
 *
 * @category  bgerp
 * @package   barcode
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за търсене по баркод
 */
class barcode_SearchIntf
{
    /**
     * Търси по подадения баркод
     *
     * @param string $str
     *
     * @return array
     * ->title - заглавие на резултата
     * ->url - линк за хипервръзка
     * ->comment - html допълнителна информация
     * ->priority - приоритет
     */
    public function searchByCode($str)
    {
        return $this->class->searchByCode($str);
    }
}
