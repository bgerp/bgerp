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
    
	/**
	 * 
	 * Връща уникален за обекта ключ под който
	 * ще се запишат сетингите в permanent_Data
	 */
	function getSettingsKey()
	{
		return core_String::convertToFixedKey(cls::getClassName($this) . "_" . $this->id . "Settings");
	}					

	/**
	 * 
	 * Връща уникален за обекта ключ под който
	 * ще се запишат показанията в permanent_Data
	 */
	function getIndicationsKey()
	{
		return core_String::convertToFixedKey(cls::getClassName($this) . "_" . $this->id . "Indications");
	}

	/**
	 * Записва в мениджъра на параметрите - параметрите на драйвера
	 * Ако има вече такъв unit не прави нищо
	 */
	function setParams()
	{
		
		$Params = cls::get('sens_Params');
		
		foreach ($this->params as $param) {
			$rec = (object) $param;
			$rec->id = $Params->fetchField("#unit = '{$param[unit]}'",'id'); 
			$Params->save($rec);
	 
		}
	}
	
    
}