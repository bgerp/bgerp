<?php
/**
 * Драйвер за електромер SATEC
 *
 * @category   bgERP 2.0
 * @package    sens_driver
 * @title:     Драйвери на сензори
 * @author     Димитър Минеков <mitko@extrapack.com>
 * @copyright  2006-2011 Experta Ltd.
 * @license    GPL 2
 * @since      v 0.1
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
     * 
     * Подготвя формата за настройки на сензора
     * и алармите в зависимост от параметрите му
     */
    function prepareSettingsForm($form)
    {

   		$form->FNC('ip', new type_Ip(),	'caption=IP,hint=Въведете IP адреса на устройството, input, mandatory');
       	$form->FNC('port','int(5)','caption=Port,hint=Порт, input, mandatory,value=80');
		$form->FNC('unit','int(5)','caption=Unit,hint=Unit, input, mandatory,value=1');
    	
       	// Добавя и стандартните параметри
    	$this->getSettingsForm($form);
    }	
	
	
    /**
     * Връща масив със стойностите на изразходваната активна мощност
     */
    function updateState()
    {
        $driver = new modbus_Driver( (array) $rec); 
        
        $driver->ip   = $this->settings->ip;
        $driver->port = $this->settings->port;
        $driver->unit = $this->settings->unit;
        
        // Прочитаме изчерпаната до сега мощност
        $driver->type = 'double';
        
        $kwh = $driver->read(405072, 2);
        $state['kWh'] = $kwh['405072'];
        
        if (!$kwh) return FALSE;
        
        $this->stateArr = $state; 
        
        return TRUE;
	}
}