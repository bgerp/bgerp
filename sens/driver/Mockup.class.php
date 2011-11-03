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
        $form->FNC('param', 'enum(T=Температура,Hr=Влажност,Dst=Запрашеност,Chm=Хим. замърсяване)', 'caption=Параметри за следене->Параметър,hint=Параметър за следене,input');
        $form->FNC('cond', 'enum(higher=по голямо, lower=по малко, equal=равно)', 'caption=Параметри за следене->Условие,hint=Условие на действие,input');
        $form->FNC('value', 'double(4)', 'caption=Параметри за следене->Стойност за сравняване,hint=Стойност за сравняване,input');
        $form->FNC('dataLogPeriod', 'int(4)', 'caption=Параметри за следене->Период на Логване,hint=На колко мин се пише в лога - 0 не се пише,input');
        $form->FNC('alarm', 'varchar', 'caption=Параметри за следене->Съобщение,hint=Текстово съобщение за лог-а,input');
        $form->FNC('severity', 'enum(normal=Информация, warning=Предупреждение, alert=Аларма)', 'caption=Параметри за следене->Ниво на важност,hint=Ниво на важност,input');
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
        
        return $data;
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
        $settings['values'] = $this->getData();
		$settings['lastMsg'] = $this->settings['lastMsg'];
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

		if (!permanent_Data::write($this->getSettingsKey(), $settings)) {
			sens_Sensors::log("Неуспешно записване на Ментака!!!");
		}
    }
    
}