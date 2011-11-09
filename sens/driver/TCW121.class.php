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
						'In2' => array('unit'=>'In2', 'param'=>'Състояние вход 2', 'details'=>'(ON,OFF)'),
						'Out1' => array('unit'=>'Out1', 'param'=>'Състояние изход 1', 'details'=>'(ON,OFF)'),
						'Out2' => array('unit'=>'Out2', 'param'=>'Състояние изход 2', 'details'=>'(ON,OFF)')
					);
	 
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

	/**
	 * 
	 * Връща уникален за обекта ключ под който
	 * ще се запишат данните в permanent_Data
	 */
	function getSettingsKey()
	{
		return core_String::convertToFixedKey(cls::getClassName($this) . "_" . $this->id);
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
     * 
     * Подготвя формата за настройки на сензора
     * По същество тук се описват настройките на параметрите на сензора
     */
    function prepareSettingsForm($form)
    {
        $form->FNC('ip', new type_Varchar(array( 'size' => 16, 'regexp' => '^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}(/[0-9]{1,2}){0,1}$')),
        'caption=IP,hint=Въведете IP адреса на устройството, input, mandatory');
        $form->FNC('port','int(5)','caption=Port,hint=Порт, input, mandatory');
        $form->FNC('param', 'enum(T=Температура,Hr=Влажност,In1=Състояние вход 1,In2=Състояние вход 2,Out1=Състояние изход 1,Out2=Състояние изход 2)', 'caption=Параметри за следене->Параметър,hint=Параметър за следене,input');
        $form->FNC('cond', 'enum(higher=по голямо, lower=по малко, equal=равно)', 'caption=Параметри за следене->Условие,hint=Условие на действие,input');
        $form->FNC('value', 'double(4)', 'caption=Параметри за следене->Стойност за сравняване,hint=Стойност за сравняване,input');
        $form->FNC('action', 'enum(none=нищо, openOut1=Отваряме реле 1, openOut2=Отваряме реле 2, closeOut1=Затваряме реле 1, closeOut2=Затваряме реле 2)', 'caption=Параметри за следене->Действие,hint=Какво се прави,input');
        $form->FNC('dataLogPeriod', 'int(4)', 'caption=Параметри за следене->Период на Логване,hint=На колко мин се пише в лога - 0 не се пише,input');
        $form->FNC('alarm', 'varchar', 'caption=Параметри за следене->Съобщение,hint=Текстово съобщение за лог-а,input');
        $form->FNC('severity', 'enum(normal=Информация, warning=Предупреждение, alert=Аларма)', 'caption=Параметри за следене->Ниво на важност,hint=Ниво на важност,input');
    }
    
	
    /**
     * Връща масив със моментните стойности на параметрите на сензора
     * или FALSE ако не може да прочете стойностите
     */
    function getData()
    {
		$url = "http://{$this->settings[fromForm]->ip}:{$this->settings[fromForm]->port}/m.xml";

        $context = stream_context_create(array('http' => array('timeout' => 4)));

        $xml = @file_get_contents($url, FALSE, $context); 

        if (empty($xml) || !$xml) return FALSE;
        
        $result = array();
        
        $this->XMLToArrayFlat(simplexml_load_string($xml), $result);
        
        $res = array(
        	'Температура' => $result['/Entry[5]/Value[1]'],
            'T' => $result['/Entry[5]/Value[1]'],
            'Влажност' => $result['/Entry[7]/Value[1]'],
            'Hr' => $result['/Entry[7]/Value[1]'],
            'Цифров вход 1' => $result['/Entry[1]/Value[1]'],
            'In1' => $result['/Entry[1]/Value[1]'],
            'Аналогов вход 1' => $result['/Entry[3]/Value[1]'],
            'V' => $result['/Entry[3]/Value[1]'],
  	        'Цифров вход 2' => $result['/Entry[2]/Value[1]'],
            'In2' => $result['/Entry[2]/Value[1]'],
            'Аналогов вход 2' => $result['/Entry[4]/Value[1]'],
            'V' => $result['/Entry[4]/Value[1]'],
            'Изход 1' => $result['/Entry[9]/Value[1]'],
            'Out1' => $result['/Entry[9]/Value[1]'],
            'Изход 2' => $result['/Entry[10]/Value[1]'],
            'Out2' => $result['/Entry[10]/Value[1]']
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

        return $res;
    }
    
    
    /**
     * 
     * При всяко извикване взима данните за сензора чрез getData
     * и ги записва под ключа си в permanentData $driver->settings[values]
     * Взима условията от $driver->settings[fromForm]
     * и извършва действия според тях ако е необходимо
     */
    function process()
    {
    	$settings['fromForm'] = $this->settings['fromForm'];
		$settings['lastMsg'] = $this->settings['lastMsg'];
        
		$settings['values'] = $this->getData();
        
		if (!$settings['values']) {
			sens_Sensors::Log("Проблем с четенето от драйвер $this->title - id = $this->id");
			exit(1);
		}
		
        // Ако имаме зададен период на логване проверяваме дали му е времето и записваме в цифровия лог
		if (!empty($settings['fromForm']->dataLogPeriod)) {
			$currentMinute = round(time() / 60);
			if ($currentMinute % $settings['fromForm']->dataLogPeriod == 0) {
				// Заглавие на параметъра
				//$settings['fromForm']->param;
				
				// Стойност в момента на параметъра
				//$settings['values']["{$settings['fromForm']->param}"];
				
				// Мярка на параметъра
				//$this->params["{$settings['fromForm']->param}"]['details'];
				
				sens_IndicationsLog::add(	$this->id,
											$settings['fromForm']->param,
											$settings['values']["{$settings['fromForm']->param}"],
											$this->params["{$settings['fromForm']->param}"]['details']
										);
			}
		}

		switch ($settings['fromForm']->cond) {
			case 'lower':
				$cond = $settings['values']["{$settings['fromForm']->param}"] < $settings['fromForm']->value;
				break;
			case 'higher':
				$cond = $settings['values']["{$settings['fromForm']->param}"] > $settings['fromForm']->value;
				break;
			case 'equal':
				$cond = $settings['values']["{$settings['fromForm']->param}"] = $settings['fromForm']->value;
				break;
		}
		
		// Записваме съобщение за сензора ако е предходното съобщение не е било същото
		if ($cond && ($settings['lastMsg'] != $settings['fromForm']->alarm . $settings['fromForm']->severity)) {
			sens_MsgLog::add($this->id, $settings['fromForm']->alarm, $settings['fromForm']->severity);
			$settings['lastMsg'] = $settings['fromForm']->alarm . $settings['fromForm']->severity;
		}
		// Ако имаме несработване на алармата нулираме флага за съобщението
		if (!$cond) unset($settings['lastMsg']);
		
		switch ($settings['fromForm']->action) {
			case 'openOut1':
					if ($cond) {
						// Отваряме Out1
						$relayAct = "r1=1";
					} else {
						// Затваряме Out1
						$relayAct = "r1=0";
					}
			break;
			
			case 'openOut2':
					if ($cond) {
						// Отваряме Out2
						$relayAct = "r2=1";
					} else {
						// Затваряме Out2
						$relayAct = "r2=0";
					}
				break;
			
			case 'closeOut1':
					if ($cond) {
						// Затваряме Out1
						$relayAct = "r1=0";
					} else {
						// Отваряме Out1
						$relayAct = "r1=1";
					}
				break;
			
			case 'closeOut2':
					if ($cond) {
						// Затваряме Out2
						$relayAct = "r2=0";
					} else {
						// Отваряме Out2
						$relayAct = "r2=1";
					}
				break;
			
			default:
			break;
		}
		
		$url = "http://admin:admin@{$this->settings[fromForm]->ip}:{$this->settings[fromForm]->port}/set?{$relayAct}";

		if (function_exists(curl_init)) {
			$ch = curl_init("$url");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_exec($ch);
			curl_close($ch);		
		} else {
			$this->log("Warning: Инсталирайте cUrl за PHP.");
			exec ("curl \"$url\"");
		}

		// Може да има настъпила промяна в сензора затова взимаме данните му отново
		$settings['values'] = $this->getData();
		
		if (!permanent_Data::write($this->getSettingsKey(), $settings)) {
			sens_Sensors::log("Неуспешно записване на TCW121!!!"); exit(1);
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
