<?php

/**
 * Драйвер за единична гравимитрична система на TSM (Modbus TCP/IP)
 */
class sens_driver_TSM extends sens_driver_IpDevice
{

	// Параметри които чете или записва драйвера 
	var $params = array(
						'KGH' => array('unit'=>'KGH', 'param'=>'Килограми за час', 'details'=>'Kgh'),
						'EO' => array('unit'=>'EO', 'param'=>'Килограми', 'details'=>'Kg'),
						'ERC' => array('unit'=>'ERC', 'param'=>'Рецепта', 'details'=>'%', 'onChange'=>TRUE)
					);

    // Колко аларми/контроли да има?
    var $alarmCnt = 1;
    
    // IP адрес на сензора
    var $ip = '';
    
    // Порт
    var $port = '';
    
    // Unit
    var $unit = '';

	/**
	 * 
	 * Брой последни стойности на базата на които се изчислява средна стойност
	 * @var integer
	 */
	var $avgCnt = 60;
    
					

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
     * Връща масив със стойностите на температурата и влажността
     */
    function getData(& $indications)
    {
        $driver = new modbus_Driver( (array) $rec);
        
        $driver->ip = $this->settings->ip;
        $driver->port = $this->settings->port;
        $driver->unit = $this->settings->unit;
        
        // Прочитаме произведеното с компонент 1
        $driver->type = 'double';
        
        $c1 = $driver->read(400446, 2);
        
        $c2 = $driver->read(400468, 2);
        
        $c3 = $driver->read(400490, 2);
        $c4 = $driver->read(400512, 2);
        $c5 = $driver->read(400534, 2);
        $c6 = $driver->read(400556, 2);
        
        $output = ($c1[400446] + $c2[400468] + $c3[400490] + $c4[400512] + $c5[400534] + $c6[400556]) / 100;
        
        if (!$output) return FALSE;
        
        $currMin = (int)time()/60;
        
        // Минутите от 0-60 са индекси на масива за изчисление на средната стойност
        $ndx = $currMin % $this->avgCnt;

        $indications['avgOutputArr'][$ndx] = $output;
                
        $indications['KGH'] = round((max($indications['avgOutputArr']) - min($indications['avgOutputArr']))*$this->avgCnt/count($indications['avgOutputArr']),2);
        
        $driver = new modbus_Driver( (array) $rec);
        
        $driver->ip = $this->settings->ip;
        $driver->port = $this->settings->port;
        $driver->unit = $this->settings->unit;
        
        $driver->type = 'words';
        
        $p1 = $driver->read(400439, 1);
        $p1 = $p1[400439];
        
        $p2 = $driver->read(400461, 1);
        $p2 = $p2[400461];
        
        $p3 = $driver->read(400483, 1);
        $p3 = $p3[400483];
        
        $p4 = $driver->read(400505, 1);
        $p4 = $p4[400505];
        
        $p5 = $driver->read(400527, 1);
        $p5 = $p5[400527];
        
        $p6 = $driver->read(400549, 1);
        $p6 = $p6[400549];
        
        if($p1) {
            $recpt .= "[1] => " . $p1/100 . "%";
        }
        
        if($p2) {
            $recpt .= ($recpt?", ":"") . "[2] => " . $p2/100 . "%";
        }
        
        if($p3) {
            $recpt .= ($recpt?", ":"") . "[3] => " . $p3/100 . "%";
        }
        
        if($p4) {
            $recpt .= ($recpt?", ":"") . "[4] => " . $p4/100 . "%";
        }
        
        if($p5) {
            $recpt .= ($recpt?", ":"") . "[5] => " . $p5/100 . "%";
        }
        
        if($p6) {
            $recpt .= ($recpt?", ":"") . "[6] => " . $p6/100 . "%";
        }

        $indications = array_merge((array)$indications,array('EO' => $output, 'ERC' => $recpt));
        
        return TRUE;
    }
}