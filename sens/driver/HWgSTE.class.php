<?php

/**
 * Драйвер за IP сензор HWg-STE - мери температура и влажност
 */
class sens_driver_HWgSTE extends sens_driver_IpDevice
{

	// Параметри които чете или записва драйвера 
	var $params = array(
						'T' => array('unit'=>'T', 'param'=>'Температура', 'details'=>'C'),
						'Hr' => array('unit'=>'Hr', 'param'=>'Влажност', 'details'=>'%')
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
        $form->FNC('param', 'enum(T=Температура,Hr=Влажност,)', 'caption=Параметри за следене->Параметър,hint=Параметър за следене,input');
        $form->FNC('cond', 'enum(higher=по голямо, lower=по малко, equal=равно)', 'caption=Параметри за следене->Условие,hint=Условие на действие,input');
        $form->FNC('value', 'double(4)', 'caption=Параметри за следене->Стойност за сравняване,hint=Стойност за сравняване,input');
        $form->FNC('dataLogPeriod', 'int(4)', 'caption=Параметри за следене->Период на Логване,hint=На колко мин се пише в лога - 0 не се пише,input');
        $form->FNC('alarm', 'varchar', 'caption=Параметри за следене->Съобщение,hint=Текстово съобщение за лог-а,input');
        $form->FNC('severity', 'enum(normal=Информация, warning=Предупреждение, alert=Аларма)', 'caption=Параметри за следене->Ниво на важност,hint=Ниво на важност,input');
    }
    
	
    /**
     * Връща масив със стойностите на температурата и влажността
     */
    function getData()
    {
        $xml = file_get_contents("http://{$this->settings[fromForm]->ip}:{$this->settings[fromForm]->port}/values.xml");
        
        $result = array();
        
        $this->XMLToArrayFlat(simplexml_load_string($xml), $result);
        
        return array('Температура' => $result['/SenSet[1]/Entry[1]/Value[1]'],
            'T' => $result['/SenSet[1]/Entry[1]/Value[1]'],
            'Влажност' => $result['/SenSet[1]/Entry[2]/Value[1]'],
            'Hr' => $result['/SenSet[1]/Entry[2]/Value[1]']
        );
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