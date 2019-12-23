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
class peripheral_DeviceDriver extends core_Mvc
{
    
    
    /**
     * 
     */
    public $interfaces = 'peripheral_DeviceIntf';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
    }
    
    
    /**
     * Може ли вградения обект да се избере
     *
     * @param NULL|int $userId
     *
     * @return bool
     */
    public function canSelectDriver($userId = null)
    {
        return false;
    }
    
    
    /**
     * 
     * @param stdClass $rec
     * @param array $params
     * 
     * @return boolean
     * 
     * @see peripheral_DeviceIntf
     */
    public static function checkDevice_($rec, $params)
    {
        
        return true;
    }
}
