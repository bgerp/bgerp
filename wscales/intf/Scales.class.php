<?php


/**
 * Интерфейс за връзка с везни
 *
 * @category  bgerp
 * @package   wscales
 *
 * @author    Yusein Yuseinov
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class wscales_intf_Scales extends peripheral_DeviceIntf
{
    
    /**
     * Инстанция на класа имплементиращ интерфейса
     */
    public $class;
    
    
    /**
     * 
     */
    public function getJs($params)
    {
        
        return $this->class->getJs($params);
    }
}
