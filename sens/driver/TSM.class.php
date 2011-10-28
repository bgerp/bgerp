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
        $form->FNC('unit','int(5)','caption=Unit,hint=Unit, input, mandatory, value=1');
        $form->FNC('param', 'enum(EO=Килограми)', 'caption=Параметри за следене->Параметър,hint=Параметър за следене,input');
        $form->FNC('cond', 'enum(higher=по голямо, lower=по малко, equal=равно)', 'caption=Параметри за следене->Условие,hint=Условие на действие,input');
        $form->FNC('value', 'double(7)', 'caption=Параметри за следене->Стойност за сравняване,hint=Стойност за сравняване,input');
        $form->FNC('dataLogPeriod', 'int(4)', 'caption=Параметри за следене->Период на Логване,hint=На колко мин се пише в лога - 0 не се пише,input');
        $form->FNC('alarm', 'varchar', 'caption=Параметри за следене->Съобщение,hint=Текстово съобщение за лог-а,input');
        $form->FNC('severity', 'enum(normal=Информация, warning=Предупреждение, alert=Аларма)', 'caption=Параметри за следене->Ниво на важност,hint=Ниво на важност,input');
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

		permanent_Data::write($this->getSettingsKey(), $settings);
    }
    
}