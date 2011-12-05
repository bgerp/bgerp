<?php

/**
 * Драйвер за електромер SATEC
 */
class sens_driver_SATEC extends sens_driver_IpDevice
{
	// Параметри които чете или записва драйвера 
	var $params = array(
						'kWh' => array('unit'=>'kWh', 'param'=>'Енергия', 'details'=>'kWh'),
						'kWhTotal' => array('unit'=>'kWhTotal', 'param'=>'Енергия общо', 'details'=>'kWh')
	);

    // Колко аларми/контроли да има?
    var $alarmCnt = 3;
    
    // IP адрес на сензора
    var $ip = '';
    
    // Порт
    var $port = '';
    
    // Unit
    var $unit = '';
    
	/**
	 * 
	 * Извлича данните от формата със заредени от Request данни,
	 * като може да им направи специализирана проверка коректност.
	 * Ако след извикването на този метод $form->getErrors() връща TRUE,
	 * то означава че данните не са коректни.
	 * От формата данните попадат в тази част от вътрешното състояние на обекта,
	 * която определя неговите settings
	 * 
	 * @param object $form
	 */
	function setSettingsFromForm($form)
	{

	}
	
	
    /**
     * Връща масив със стойностите на изразходваната активна мощност
     */
    function getData(&$indications)
    {
        $driver = new modbus_Driver( (array) $rec); 
        
        $driver->ip   = $this->settings->ip;
        $driver->port = $this->settings->port;
        $driver->unit = $this->settings->unit;
        
        // Прочитаме изчерпаната до сега мощност
        $driver->type = 'double';
        
        $kwh = $driver->read(405072, 2); //bp($kwh);
        $output = $kwh['405072'];
        if (!$output) return FALSE;
        
		$indications['kW'] = $output; 
		
		return $indications;
    }
}