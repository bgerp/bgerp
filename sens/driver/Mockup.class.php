<?php

/**
 * Прототип на драйвер за IP сензор
 */
class sens_driver_Mockup extends sens_driver_IpDevice
{
	
	/**
	 * 
	 * Брой последни стойности на базата на които се изчислява средна стойност
	 * @var integer
	 */
	var $avgCnt = 10;
	
	/**
	 *  Параметри които чете или записва драйвера
	 */
	var $params = array(
						'T' => array('unit'=>'T', 'param'=>'Температура', 'details'=>'C'),
						'Hr' => array('unit'=>'Hr', 'param'=>'Влажност', 'details'=>'%'),
						'Dst' => array('unit'=>'Dst', 'param'=>'Запрашеност', 'details'=>'%'),
						'Chm' => array('unit'=>'Chm', 'param'=>'Хим. замърсяване', 'details'=>'%'),
						'avgHr' => array('unit'=>'avgHr', 'param'=>'Средна влажност', 'details'=>'%')
					);

    /**
     * Описания на изходите ако има такива
     */
    var $outs = array(
      					'out1' => array('digital' => array('0','1')),
      					'out2' => array('digital' => array('0','1')),
      					'out3' => array('analog' => array('0','10'))
      				);

    /**
     * Брой аларми
     */
    var $alarmCnt = 4;
      				
					
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
     * Връща масив с всички данните от сензора
     *
     * @return array $sensorData
     */
    function getData(&$indications)
    {
        // Данни за всички параметри, които поддържа сензора
        $indications = array_merge($indications, 
        				array(	//'T' => rand(-60,60),
        						'T' => 50,
        						'Hr' => rand(0,100),
        						'Dst' => rand(0,100),
        						'Chm' => rand(0,100)
        				)
        			);
        
        $ndx = ((int)time()/60) % $this->avgCnt;
        
        $indications['avgHrArr'][$ndx] = $indications['Hr'];
        
        $indications['avgHr'] = array_sum($indications['avgHrArr']) / count($indications['avgHrArr']);
        
        // sleep(2);
        $outs = permanent_Data::read('sens_driver_mockupOuts');
        
        
        $indications = array_merge($indications, (array)$outs);

        return $indications;
    }
    
	/**
     * Сетва изходите на драйвера по зададен масив
     *
     * @return bool
     */
    function setOuts($alarmNo,$cond,$settingsArr)
    {
    	if ($cond) {
	    	foreach ($this->outs as $out => $dummy) {
	    		$outs[$out] = $settingsArr["{$out}_{$alarmNo}"];
	    	}
	    	// Сетваме изходите според масива $outs
	    	
	    	// За Ментак-а ползваме permanent_Data за да предаваме състоянието
	    	permanent_Data::write('sens_driver_mockupOuts',$outs);
    	}
    	
    }
    
}