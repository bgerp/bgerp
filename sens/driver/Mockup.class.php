<?php

/**
 * Прототип на драйвер за IP сензор
 */
class sens_driver_Mockup extends sens_driver_IpDevice
{
	// Параметри които чете или записва драйвера 
	var $params = array(
						'T' => array('unit'=>'T', 'param'=>'Температура', 'details'=>'C'),
						'Hr' => array('unit'=>'Hr', 'param'=>'Влажност', 'details'=>'%'),
						'Dst' => array('unit'=>'Dst', 'param'=>'Запрашеност', 'details'=>'%'),
						'Chm' => array('unit'=>'Chm', 'param'=>'Хим. замърсяване', 'details'=>'%')
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
        $form->FLD('logPeriod_T', 'int(4)', 'caption=Параметри - период на следене->Температура,hint=На колко минути да се записва температурата,input');
        $form->FLD('logPeriod_Hr', 'int(4)', 'caption=Параметри - период на следене->Влажност,hint=На колко минути да се записва влажността,input');
        $form->FLD('logPeriod_Dst', 'int(4)', 'caption=Параметри - период на следене->Запрашеност,hint=На колко минути да се записва запрашеността,input');
        $form->FLD('logPeriod_Chm', 'int(4)', 'caption=Параметри - период на следене->Хим. замърсители,hint=На колко минути да се записват Хим. замърсители,input');
        
        $form->FLD('alarm_1_message', 'varchar', 'caption=Аларма 1->Съобщение,hint=Съобщение за лог-а,input');
        $form->FLD('alarm_1_param', 'enum(T=Температура, Hr=Влажност, Dst=Запрашеност, Chm=Хим. замърсители)', 'caption=Аларма 1->Параметър,hint=Параметър за алармиране,input');
        $form->FLD('alarm_1_cond', 'enum(nothing=нищо, higher=по голямо, lower=по малко)', 'caption=Аларма 1->Условие,hint=Условие на действие,input');
        $form->FLD('alarm_1_value', 'double(4)', 'caption=Аларма 1->Стойност за сравняване,hint=Стойност за сравняване,input');
        $form->FLD('alarm_1_severity', 'enum(normal=Информация, warning=Предупреждение, alert=Аларма)', 'caption=Аларма 1->Ниво на важност,hint=Ниво на важност,input');
        
        $form->FLD('alarm_2_message', 'varchar', 'caption=Аларма 2->Съобщение,hint=Съобщение за лог-а,input');
        $form->FLD('alarm_2_param', 'enum(T=Температура, Hr=Влажност, Dst=Запрашеност, Chm=Хим. замърсители)', 'caption=Аларма 2->Параметър,hint=Параметър за алармиране,input');
        $form->FLD('alarm_2_cond', 'enum(nothing=нищо, higher=по голямо, lower=по малко)', 'caption=Аларма 2->Условие,hint=Условие на действие,input');
        $form->FLD('alarm_2_value', 'double(4)', 'caption=Аларма 2->Стойност за сравняване,hint=Стойност за сравняване,input');
        $form->FLD('alarm_2_severity', 'enum(normal=Информация, warning=Предупреждение, alert=Аларма)', 'caption=Аларма 2->Ниво на важност,hint=Ниво на важност,input');
        
        $form->FLD('alarm_3_message', 'varchar', 'caption=Аларма 3->Съобщение,hint=Съобщение за лог-а,input');
        $form->FLD('alarm_3_param', 'enum(T=Температура, Hr=Влажност, Dst=Запрашеност, Chm=Хим. замърсители)', 'caption=Аларма 3->Параметър,hint=Параметър за алармиране,input');
        $form->FLD('alarm_3_cond', 'enum(nothing=нищо, higher=по голямо, lower=по малко)', 'caption=Аларма 3->Условие,hint=Условие на действие,input');
        $form->FLD('alarm_3_value', 'double(4)', 'caption=Аларма 3->Стойност за сравняване,hint=Стойност за сравняване,input');
        $form->FLD('alarm_3_severity', 'enum(normal=Информация, warning=Предупреждение, alert=Аларма)', 'caption=Аларма 3->Ниво на важност,hint=Ниво на важност,input');
        
        $form->FLD('alarm_4_message', 'varchar', 'caption=Аларма 4->Съобщение,hint=Съобщение за лог-а,input');
        $form->FLD('alarm_4_param', 'enum(T=Температура, Hr=Влажност, Dst=Запрашеност, Chm=Хим. замърсители)', 'caption=Аларма 4->Параметър,hint=Параметър за алармиране,input');
        $form->FLD('alarm_4_cond', 'enum(nothing=нищо, higher=по голямо, lower=по малко)', 'caption=Аларма 4->Условие,hint=Условие на действие,input');
        $form->FLD('alarm_4_value', 'double(4)', 'caption=Аларма 4->Стойност за сравняване,hint=Стойност за сравняване,input');
        $form->FLD('alarm_4_severity', 'enum(normal=Информация, warning=Предупреждение, alert=Аларма)', 'caption=Аларма 4->Ниво на важност,hint=Ниво на важност,input');
    }
	
	/**
     * Връща масив с всички данните от сензора
     *
     * @return array $sensorData
     */
    function getData()
    {
        // Дани за всички параметри, които поддържа сензора
        $data = array(	'T' => rand(-60,60),
        				'Hr' => rand(0,100),
        				'Dst' => rand(0,100),
        				'Chm' => rand(0,100)
        		);
        sleep(7);
        
        return $data;
    }
    
    /**
     * 
     * При всяко извикване взима данните за сензора чрез getData
     * и ги записва под ключа си в permanentData $driver->indications[values]
     * Взима сетингите от $driver->settings[fromForm]
     * и се свързва с устройството според тях
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
			sens_Sensors::log("Неуспешно записване на Ментака!!!");
		}
    }
}