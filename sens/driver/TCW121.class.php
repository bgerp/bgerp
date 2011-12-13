<?php

/**
 * Драйвер за IP сензор Teracom TCW-121 - следи състоянието на първите цифров и аналогов вход
 */
class sens_driver_TCW121 extends sens_driver_IpDevice
{

	// Параметри които чете или записва драйвера 
	var $params = array(
						'T' => array('unit'=>'T', 'param'=>'Температура', 'details'=>'C'),
						'Hr' => array('unit'=>'Hr', 'param'=>'Влажност', 'details'=>'%'),
						'In1' => array('unit'=>'In1', 'param'=>'Състояние вход 1', 'details'=>'(ON,OFF)'),
						'In2' => array('unit'=>'In2', 'param'=>'Състояние вход 2', 'details'=>'(ON,OFF)')
					);

    /**
     * Описания на изходите 
     */
    var $outs = array(
      					'out1' => array('digital' => array('0','1')),
      					'out2' => array('digital' => array('0','1'))
      				);

    // Колко аларми/контроли да има?
    var $alarmCnt = 3;
    
    // IP адрес на сензора
    var $ip = '';
    
    // Порт
    var $port = '';
    
    // Потребител
    var $user = '';
    
    // Парола
    var $password = '';
    
    var $test = TRUE;


    function getTest()
    {
        $url = "http://{$this->settings->ip}:{$this->settings->port}/m.xml";

        $context = stream_context_create(array('http' => array('timeout' => 4)));

        $xml = @file_get_contents($url, FALSE, $context); 
        $this->XMLToArrayFlat(simplexml_load_string($xml), $result);

        bp($result);

        return $xml;
    }


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
     * Връща масив със моментните стойности на параметрите на сензора
     * или FALSE ако не може да прочете стойностите
     */
    function getData(& $indications)
    {
		$url = "http://{$this->settings->ip}:{$this->settings->port}/m.xml";

        $context = stream_context_create(array('http' => array('timeout' => 4)));

        $xml = @file_get_contents($url, FALSE, $context); 

        if (empty($xml) || !$xml) return FALSE;
        
        $result = array();
        
        $this->XMLToArrayFlat(simplexml_load_string($xml), $result);
        
        $res = array(
            'T' => $result['/Entry[5]/Value[1]'],
            'Hr' => $result['/Entry[7]/Value[1]'],
            'In1' => $result['/Entry[1]/Value[1]'],
            'V' => $result['/Entry[3]/Value[1]'],
            'In2' => $result['/Entry[2]/Value[1]'],
            'V' => $result['/Entry[4]/Value[1]'],
            'out1' => $result['/Entry[9]/Value[1]'],
            'out2' => $result['/Entry[10]/Value[1]']
        ); 
        // Всички стойности ON и OFF ги обръщаме в респективно 1 и 0
        foreach ($res as $key => $value) {
        	$value = trim(strtoupper($value));
        	switch ($value) {
        		case 'ON':
        			$res[$key] = 1;
        		break;
        		case 'OFF':
        			$res[$key] = 0;
        		break;
        	};
        }

		$indications = array_merge((array)$indications,$res);
		         
        return $indications;
    }
    
	/**
     * Сетва изходите на драйвера по зададен масив
     *
     * @return bool
     */
    function setOuts($alarmNo,$cond,$settingsArr)
    {
    	static $url = array();
    	
    	if ($cond) {
	    	foreach ($this->outs as $out => $dummy) {
	    		if ($settingsArr["{$out}_{$alarmNo}"] != 'nothing') {
	    			// Санитизизараме изхода	
	    			$outs[$out] = empty($settingsArr["{$out}_{$alarmNo}"])?0:1;
	    		} else {
	    			// $outs[$out] = empty($settingsArr["{$out}_{$alarmNo}"])?1:0;
	    		}
	    	}
		    if ($outs['out1'] == 0) $url[1] = "http://{$this->settings->user}:{$this->settings->password}@{$this->settings->ip}:{$this->settings->port}/set?r1=0";
	    	if ($outs['out1'] == 1) $url[1] = "http://{$this->settings->user}:{$this->settings->password}@{$this->settings->ip}:{$this->settings->port}/set?r1=1";
	    	if ($outs['out2'] == 0) $url[2] = "http://{$this->settings->user}:{$this->settings->password}@{$this->settings->ip}:{$this->settings->port}/set?r2=0";
	    	if ($outs['out2'] == 1) $url[2] = "http://{$this->settings->user}:{$this->settings->password}@{$this->settings->ip}:{$this->settings->port}/set?r2=1";
    	}
		
    	
    	// Сетваме изходите според масива $outs
		if (!function_exists('curl_init')) {
			sens_MsgLog::add($this->id, "Инсталирайе Curl за PHP!", 3);
			exit(1);
		}
		
		// Ако сме на последната аларма - прилагаме резултата
		if (($alarmNo == $this->alarmCnt) && is_array($url)) {
			foreach ($url as $cmd) {
				$ch = curl_init("$cmd");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
				curl_exec($ch);
				curl_close($ch);		
			}
		}
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function XMLToArrayFlat($xml, &$return, $path='', $root=FALSE)
    {
        $children = array();
        
        if ($xml instanceof SimpleXMLElement) {
            $children = $xml->children();
            
            if ($root){ // we're at root
                $path .= '/'.$xml->getName();
            }
        }
        
        if ( count($children) == 0 ){
            $return[$path] = (string)$xml;
            
            return;
        }
        
        $seen = array();
        
        foreach ($children as $child => $value) {
            $childname = ($child instanceof SimpleXMLElement)?$child->getName():$child;
            
            if ( !isset($seen[$childname])){
                $seen[$childname] = 0;
            }
            $seen[$childname]++;
            $this->XMLToArrayFlat($value, $return, $path.'/'.$child.'['.$seen[$childname].']');
        }
    }
    
}
