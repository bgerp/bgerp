<?php 

/**
 * 
 *
 * @category  bgerp
 * @package   ztm
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class ztm_Index extends core_Mvc
{
    
    /**
     * Екшън за регистриране на устройство
     */
    public function act_Register()
    {
        
        return Request::forward(array('Ctr' => 'ztm_Devices', 'Act' => 'Register'));
    }
    
    
    /**
     * Създава пряк път до публичните статии
     */
    public function act_Sync()
    {
        return Request::forward(array('Ctr' => 'ztm_RegisterValues', 'Act' => 'Sync'));
    }
}
