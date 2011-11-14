<?php

/**
 * Драйвер за единична гравимитрична система на TSM (Modbus TCP/IP)
 */
class sens_driver_TSM extends sens_driver_IpDevice
{

	// Параметри които чете или записва драйвера 
	var $params = array(
						'EO' => array('unit'=>'EO', 'param'=>'Килограми', 'details'=>'Kg'),
						'ERC' => array('unit'=>'ERC', 'param'=>'Рецепта', 'details'=>'%')
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
        $form->FNC('port','int(5)','caption=Port,hint=Порт, input, mandatory, value=502');
        $form->FNC('unit','int(5)','caption=Unit,hint=Unit, input, mandatory, value=1');

        $form->FLD('logPeriod_EO', 'int(4)', 'caption=Период на следене на параметрите->Килограми,hint=На колко минути да се записва температурата,input');
        $form->FLD('logPeriod_ERC', 'int(4)', 'caption=Период на следене на параметрите->Рецепта,hint=На колко минути да се записва влажността,input');
        
        $form->FLD('alarm_1_message', 'varchar', 'caption=Аларма 1->Съобщение,hint=Съобщение за лог-а,input');
        $form->FLD('alarm_1_param', 'enum(EO=Килограми, ERC=Рецепта)', 'caption=Аларма 1->Параметър,hint=Параметър за алармиране,input');
        $form->FLD('alarm_1_cond', 'enum(nothing=нищо, higher=по голямо, lower=по малко)', 'caption=Аларма 1->Условие,hint=Условие на действие,input');
        $form->FLD('alarm_1_value', 'double(4)', 'caption=Аларма 1->Стойност за сравняване,hint=Стойност за сравняване,input');
        $form->FLD('alarm_1_severity', 'enum(normal=Информация, warning=Предупреждение, alert=Аларма)', 'caption=Аларма 1->Ниво на важност,hint=Ниво на важност,input');
        $form->FLD('alarm_1_action', 'enum(none=нищо, openOut1=Отваряме реле 1, openOut2=Отваряме реле 2, closeOut1=Затваряме реле 1, closeOut2=Затваряме реле 2)', 'caption=Аларма 1->Действие,hint=Какво се прави,input');
	}
	
	/**
     * Връща масив със стойностите на температурата и влажността
     */
    function getData()
    {
        $driver = new modbus_Driver( (array) $rec);
        
        $driver->ip = $this->settings[fromForm]->ip;
        $driver->port = $this->settings[fromForm]->port;
        $driver->unit = $this->settings[fromForm]->unit;
        
        // Прочитаме произведеното с компонент 1
        $driver->type = 'double';
        
        $c1 = $driver->read(400446, 2);
        
        $c2 = $driver->read(400468, 2);
        
        $c3 = $driver->read(400490, 2);
        $c4 = $driver->read(400512, 2);
        $c5 = $driver->read(400534, 2);
        $c6 = $driver->read(400556, 2);
        
        $output = ($c1[400446] + $c2[400468] + $c3[400490] + $c4[400512] + $c5[400534] + $c6[400556]) / 100;
        
        $driver = new modbus_Driver( (array) $rec);
        
        $driver->ip = $this->settings[fromForm]->ip;
        $driver->port = $this->settings[fromForm]->port;
        $driver->unit = $this->settings[fromForm]->unit;
        
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
        
        return array('EO' => $output, 'ERC' => $recpt);
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
				// Имаме задействана аларма и тя се изпълнява за 1-ви път - записваме в sens_MsgLog
				sens_MsgLog::add($this->id, $settingsArr["alarm_{$i}_message"],$settingsArr["alarm_{$i}_severity"]);
				
				$indications["lastMsg_{$i}"] = $settingsArr["alarm_{$i}_message"].$settingsArr["alarm_{$i}_severity"];
			}
			
			if (!$cond) unset($indications["lastMsg_{$i}"]);
		} while (TRUE);

		if (!permanent_Data::write($this->getIndicationsKey(),$indications)) {
			sens_Sensors::log("Неуспешно записване на TSM-a!!!");
		}
    }
}