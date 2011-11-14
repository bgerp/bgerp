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
        $form->FNC('user','varchar(10)','caption=Потребител,hint=Потребител, input, mandatory,value=admin');
        $form->FNC('password','varchar(10)','caption=Парола,hint=Парола, input, mandatory, value=admin');

        $form->FLD('logPeriod_T', 'int(4)', 'caption=Период на следене на параметрите->Температура,hint=На колко минути да се записва температурата,input');
        $form->FLD('logPeriod_Hr', 'int(4)', 'caption=Период на следене на параметрите->Влажност,hint=На колко минути да се записва влажността,input');
        
        $form->FLD('alarm_1_message', 'varchar', 'caption=Аларма 1->Съобщение,hint=Съобщение за лог-а,input');
        $form->FLD('alarm_1_param', 'enum(T=Температура, Hr=Влажност)', 'caption=Аларма 1->Параметър,hint=Параметър за алармиране,input');
        $form->FLD('alarm_1_cond', 'enum(nothing=нищо, higher=по голямо, lower=по малко)', 'caption=Аларма 1->Условие,hint=Условие на действие,input');
        $form->FLD('alarm_1_value', 'double(4)', 'caption=Аларма 1->Стойност за сравняване,hint=Стойност за сравняване,input');
        $form->FLD('alarm_1_severity', 'enum(normal=Информация, warning=Предупреждение, alert=Аларма)', 'caption=Аларма 1->Ниво на важност,hint=Ниво на важност,input');
        $form->FLD('alarm_1_action', 'enum(none=нищо, openOut1=Отваряме реле 1, openOut2=Отваряме реле 2, closeOut1=Затваряме реле 1, closeOut2=Затваряме реле 2)', 'caption=Аларма 1->Действие,hint=Какво се прави,input');
        
        $form->FLD('alarm_2_message', 'varchar', 'caption=Аларма 2->Съобщение,hint=Съобщение за лог-а,input');
        $form->FLD('alarm_2_param', 'enum(T=Температура, Hr=Влажност)', 'caption=Аларма 2->Параметър,hint=Параметър за алармиране,input');
        $form->FLD('alarm_2_cond', 'enum(nothing=нищо, higher=по голямо, lower=по малко)', 'caption=Аларма 2->Условие,hint=Условие на действие,input');
        $form->FLD('alarm_2_value', 'double(4)', 'caption=Аларма 2->Стойност за сравняване,hint=Стойност за сравняване,input');
        $form->FLD('alarm_2_severity', 'enum(normal=Информация, warning=Предупреждение, alert=Аларма)', 'caption=Аларма 2->Ниво на важност,hint=Ниво на важност,input');
        $form->FLD('alarm_2_action', 'enum(none=нищо, openOut1=Отваряме реле 1, openOut2=Отваряме реле 2, closeOut1=Затваряме реле 1, closeOut2=Затваряме реле 2)', 'caption=Аларма 2->Действие,hint=Какво се прави,input');
        
        
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
		$indications = permanent_Data::read($this->getIndicationsKey()); 
		$indications['values'] = $this->getData();
		
		if (!$indications['values']) {
			sens_Sensors::Log("Проблем с четенето от драйвер $this->title - id = $this->id");
			exit(1);
		}
		
		// Обикаляме всички параметри на драйвера и всичко с префикс logPeriod от настройките
		// и ако му е времето го записваме в indicationsLog-а
		$settingsArr = (array) $this->settings['fromForm'];		
    	        
		foreach ($this->params as $param => $arr) {
			if ($settingsArr["logPeriod_{$param}"] > 0) {
				// Имаме зададен период на следене на параметъра
				// Ако периода съвпада с текущата минута - го записваме в IndicationsLog-a
				$currentMinute = round(time() / 60);
				if ($currentMinute % $settingsArr["logPeriod_{$param}"] == 0) {
					// Заглавие на параметъра
					//$param;

					// Стойност в момента на параметъра
					//$indications['values']->param;
					
					sens_IndicationsLog::add(	$this->id,
												$param,
												$indications['values']["$param"]
											);
				}
			}
		}
		
		// Ред е да задействаме аларми ако има.
		// Започваме цикъл тип - 'не се знае къде му е края' по идентификаторите на формата
		$i = 0;
		$relayAct = "";
		do {
			$i++;
			$cond = FALSE;
			switch ($settingsArr["alarm_{$i}_cond"]) {
				
				case "lower":
					$cond = $indications['values'][$settingsArr["alarm_{$i}_param"]] < $settingsArr["alarm_{$i}_value"];
				break;
				
				case "higher":
					$cond = $indications['values'][$settingsArr["alarm_{$i}_param"]] > $settingsArr["alarm_{$i}_value"];
				break;
				
				default:
					// Щом минаваме оттук означава, 
					// че няма здадена аларма в тази група идентификатори
					// => Излизаме от цикъла;
				break 2;
			}

			if ($cond && $indications["lastMsg_{$i}"] != $settingsArr["alarm_{$i}_message"].$settingsArr["alarm_{$i}_severity"]) {
				// Имаме задействана аларма и тя се изпълнява за 1-ви път
				// => записваме в sens_MsgLog
				sens_MsgLog::add($this->id, $settingsArr["alarm_{$i}_message"],$settingsArr["alarm_{$i}_severity"]);
				
				//  и изпълняваме действие ако е зададено
				switch ($settingsArr["alarm_{$i}_action"]) {
					case 'openOut1':
						// Отваряме реле 1
						$relayAct = "r1=1";
						break;
					case 'openOut2':
						// Отваряме реле 2
						$relayAct = "r2=1";
						break;
					case 'closeOut1':
						// Затваряме реле 1
						$relayAct = "r1=0";
						break;
					case 'closeOut2':
						// Затваряме реле 2
						$relayAct = "r2=0";
						break;
				}
				// сетваме флага 	
				$indications["lastMsg_{$i}"] = $settingsArr["alarm_{$i}_message"].$settingsArr["alarm_{$i}_severity"];
			}
			
			// Ако нямаме сработила аларма 
			if (!$cond) {
				// изтриваме флага
				unset($indications["lastMsg_{$i}"]);
				// Правим обратното действие ако има зададено	
				switch ($settingsArr["alarm_{$i}_action"]) {
					case 'openOut1':
						// Затваряме реле 1
						$relayAct = "r1=0";
						break;
					case 'openOut2':
						// Затваряме реле 2
						$relayAct = "r2=0";
						break;
					case 'closeOut1':
						// Отваряме реле 1
						$relayAct = "r1=1";
						break;
					case 'closeOut2':
						// Отваряме реле 2
						$relayAct = "r2=1";
						break;
				}
			}
			// Ако има зададени действия върху устройството тук ще ги изпълним
			if (!empty($relayAct)) {
				$url = "http://{$this->settings[fromForm]->user}:{$this->settings[fromForm]->password}@{$this->settings[fromForm]->ip}:{$this->settings[fromForm]->port}/set?{$relayAct}";
				if (function_exists(curl_init)) {
					$ch = curl_init("$url");
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
					curl_exec($ch);
					curl_close($ch);		
				} else {
					$this->log("Warning: Инсталирайте cUrl за PHP.");
					exec ("curl \"$url\"");
				}
			}
			
		} while (TRUE);
		
		// Може да има настъпила промяна в сензора затова взимаме данните му отново
		$indications['values'] = $this->getData();
		
		if (!permanent_Data::write($this->getIndicationsKey(),$indications)) {
			sens_Sensors::log("Неуспешно записване на TCW121!!!");
			
			exit(1);
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
