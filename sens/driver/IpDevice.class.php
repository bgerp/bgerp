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
        if(is_string($params) && strpos($params, '}')) {
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