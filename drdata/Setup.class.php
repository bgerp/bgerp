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
     * Описание на модула
     */
    var $info = "Готови данни и типове от различни области";


    /**
     *  Инсталиране на пакета
     */
    function install()
    {

        $managers = array(
            'drdata_Countries',
            'drdata_IpToCountry',
            'drdata_DialCodes',
            'drdata_Vats',
            'drdata_Holidays',
        );
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        

        
        return $html;
    }
    
    
    /**
     *  Де-инсталиране на пакета
     */
    function deinstall()
    {
        return "Пакета drdata е разкачен";
    }
}