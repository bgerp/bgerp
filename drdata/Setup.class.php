<?php

/**
 *  class drdata_Setup
 *
 *  Инсталиране/Деинсталиране на
 *  доктор за адресни данни
 *
 *
 */
class drdata_Setup extends core_Manager {
    
    
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startCtr = 'drdata_Countries';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startAct = 'default';
    
    
    /**
     *  Инсталиране на пакета
     */
    function install()
    {
        // Установяваме страните;
        $Countries = cls::get('drdata_Countries');
        $html .= $Countries->setupMVC();
        
        // Установяваме IP-TO-COUNTRY таблицата;
        $IpToCountry = cls::get('drdata_IpToCountry');
        $html .= $IpToCountry->setupMVC();
        
        // Установяваме DialCodes таблицата;
        $DialCodes = cls::get('drdata_DialCodes');
        $html .= $DialCodes->setupMVC();
        
        return $html;
    }
    
    
    /**
     *  Де-инсталиране на пакета
     */
    function deinstall()
    {
        return "";
    }
}