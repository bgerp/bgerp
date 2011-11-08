<?php

/**
 * Прототип на драйвер за IP устройство
 */
class sens_driver_IpDevice extends core_BaseClass
{
    /**
     * Интерфeйси, поддържани от всички наследници
     */
    var $interfaces = 'sens_DriverIntf,permanent_SettingsIntf';
    
    /**
     * id на устройството
     */
    var $id;
    
    /**
     * Начално установяване на параметрите
     */
    function init( $params = array() )
    {
        if(is_string($params) && strpos($params, '}')) {
            $params = arr::make(json_decode($params));
        } else {
            $params = arr::make($params, TRUE);
        }
        
        parent::init($params);
    }
    
    /**
     * 
     * Връща текущите настройки на обекта
     */
    function getSettings()
    {
    	
    }
    
    /**
     * 
     * Задава вътрешните сетинги на обекта
     */
    function setSettings($data)
    {
    	if (!$data) return FALSE;
		$this->settings = $data;
    }
}