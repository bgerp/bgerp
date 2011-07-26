<?php

/**
 * Прототип на драйвер за IP устройство
 */
class rfid_driver_IpDeviceMysql extends core_BaseClass {
    
    
    /**
     * IP на устройството
     */
    var $dbHost;
    
    
    /**
     * id на устройството
     */
    var $id;
    
    
    /**
     * Потребителско име
     */
    var $dbUser;
    
    
    /**
     * Парола за достъп
     */
    var $dbPass;
    
    
    /**
     * Име на базата данни
     */
    var $dbName;
    
    
    /**
     * Начално установяване на параметрите
     */
    function init( $params = array() )
    {
        $initParams = arr::make($params[1], TRUE);
        
        //   $initParams['ip'] = $params[0];
        
        if(!$initParams['id']) {
            $initParams['id'] = 1;
        }
        
        parent::init($params);
    }
}