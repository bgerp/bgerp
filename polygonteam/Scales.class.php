<?php


/**
 *
 *
 * @category  bgerp
 * @package   polygonteam
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class polygonteam_Scales extends core_Mvc
{
    public $interfaces = 'peripheral_DeviceIntf, wscales_intf_Scales';
    
    public $title = 'Везна на ПолигонТийм';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('user', 'varchar', 'caption=Потребителско име,mandatory');
        $fieldset->FLD('pass', 'password', 'caption=Парола,mandatory');
        $fieldset->FLD('hostName', 'varchar', 'caption=Хост,mandatory');
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
        return true;
    }
    
    
    /**
     * 
     * 
     * @param stdClass
     * 
     * @see wscales_intf_Scales
     */
    public function getJs($params)
    {
        $jsTpl = getTplFromFile('/polygonteam/js/jsTpl.txt');
        
        $jsTpl->placeObject($params);
        
        return $jsTpl;
    }
}
