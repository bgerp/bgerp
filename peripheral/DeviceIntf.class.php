<?php


/**
 *
 *
 * @category  bgerp
 * @package   peripheral
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class peripheral_DeviceIntf extends embed_DriverIntf
{
    
    /**
     * Проверява дали устройството с тези параметри, може да се използва
     * 
     * @param stdClass $rec
     * @param array $params
     * @return boolean
     */
    public static function checkDevice($rec, $params)
    {
        
        return $this->class->checkDevice($allRecsArr, $params);
    }
}
