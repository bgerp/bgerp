<?php


/**
 * Прототип на драйвер за IP устройство
 *
 *
 * @category  bgerp
 * @package   cams
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cams_driver_IpDevice extends core_BaseClass {
    
    
    
    /**
     * IP на устройството
     */
    var $ip;
    
    
    
    /**
     * id на устройството
     */
    var $id;
    
    
    
    /**
     * Потребителско име
     */
    var $user;
    
    
    
    /**
     * Парола за достъп
     */
    var $pass;
    
    
    
    /**
     * Начално установяване на параметрите
     */
    function init( $params = array() )
    {
        if(strpos($params, '}') ) {
            $params = arr::make(json_decode($params));
        } else {
            $params = arr::make($params, TRUE);
        }
        
        parent::init($params);
    }
    
    
    
    /**
     * Връща базовото URL към устройството
     */
    function getDeviceUrl($protocol, $portName = NULL)
    {
        if($this->user) {
            $url = "{$this->user}:{$this->password}@{$this->ip}";
        } else {
            $url = "{$this->ip}";
        }
        
        if(!isset($portName)) {
            $portName = $protocol . "Port";
        }
        
        if($this->{$portName}) {
            $url .= ":" . $this->{$portName};
        }
        
        return $protocol . "://" . $url;
    }
}