<?php

/**
 * Прототип на драйвер за IP устройство
 */
class sens_driver_IpDevice extends core_BaseClass
{
    /**
     * Интерфeйси, поддържани от всички наследници
     */
    var $interfaces = 'sens_DriverIntf,permanent_SettingsIntf';
    
    /**
     * id на устройството
     */
    var $id;
    
    /** Параметри които чете или записва драйвера - предефинирани в драйверите наследници
     *	Например:
	 *	array(
	 *			'T' => array('unit'=>'T', 'param'=>'Температура', 'details'=>'C'),
	 *			'Hr' => array('unit'=>'Hr', 'param'=>'Влажност', 'details'=>'%'),
	 *			'Dst' => array('unit'=>'Dst', 'param'=>'Запрашеност', 'details'=>'%'),
	 *			'Chm' => array('unit'=>'Chm', 'param'=>'Хим. замърсяване', 'details'=>'%')
	 *		);
     */
	var $params = array();

	/**
	 * 
	 * Брой последни стойности на базата на които се изчислява средна стойност
	 * @var integer
	 */
	var $avgCnt = 10;
	
	
    /**
     * Брой аларми - хубаво е да се предефинира в драйверите наследници
     */
    var $alarmCnt = 3;
    
    /**
     * Описания на изходите ако има такива
     * Пример: array(
     * 					'out1' => array('digital' => array(0,1)),
     * 					'out2' => array('digital' => array(0,1)),
     * 					'out3' => array('analog' => array(0,10))
     * 				);
     */
    var $outs = array();

    /**
     * Показанията на сензора - попълва се от getData, записва се в permanent_Data(getIndicationsKey());
     * Примерна структура:
	 *	array(
	 *			'T' => 5,
	 *			'Hr' => 45,
	 *			'Dst' => 40,
	 *			'Chm' => 20,
	 *			'out1' => 1,
	 *			'out2' => 0,
	 *			'out3' => 4.8
	 *		);

     */
    var $indications = array();
    
    /**
     * Начално установяване на параметрите
     */
    function init( $params = array() )
    {
        if(is_string($params) && strpos($params, '}')) {
            $params = arr::make(json_decode($params));
        } else {
            $params = arr::make($params, TRUE);
        }
        
        parent::init($params);
    }
    
    /**
     * 
     * Връща текущите настройки на обекта
     */
    function getSettings()
    {
    	return $this->settings;
    }
    
    /**
     * 
     * Задава вътрешните сетинги на обекта
     */
    function setSettings($data)
    {
    	if (!$data) return FALSE;
		$this->settings = $data;
    }
    
	/**
	 * 
	 * Връща уникален за обекта ключ под който
	 * ще се запишат сетингите в permanent_Data
	 */
	function getSettingsKey()
	{
		return core_String::convertToFixedKey(cls::getClassName($this) . "_" . $this->id . "Settings");
	}					

	/**
	 * 
	 * Връща уникален за обекта ключ под който
	 * ще се запишат показанията в permanent_Data
	 */
	function getIndicationsKey()
	{
		return core_String::convertToFixedKey(cls::getClassName($this) . "_" . $this->id . "Indications");
	}

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
     * Подготвя формата за настройки на сензора
     * и алармите в зависимост от параметрите му
     */
    function prepareSettingsForm($form)
    {
    	if (isset($this->ip)) {
        	$form->FNC('ip', new type_Varchar(array( 'size' => 16, 'regexp' => '^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}(/[0-9]{1,2}){0,1}$')),
        				'caption=IP,hint=Въведете IP адреса на устройството, input, mandatory');
    	}
    	
    	if (isset($this->port)) {
        	$form->FNC('port','int(5)','caption=Port,hint=Порт, input, mandatory');
    	}
    	
    	if (isset($this->unit)) {
        	$form->FNC('unit','int(5)','caption=Unit,hint=Unit, input, mandatory,value=1');
    	}
    	
    	if (isset($this->user)) {
        	$form->FNC('user','varchar(10)','caption=User,hint=Потребител, input, mandatory');
    	}
    	
    	if (isset($this->password)) {
        	$form->FNC('password','varchar(10)','caption=Password,hint=Парола, input, mandatory');
    	}
    	
        
        $paramArr[''] = 'избери';

        foreach($this->params as $p => $pArr) {
        	if ($pArr['onChange']) $onChange = ' или при промяна';
            $form->FLD('logPeriod_' . $p, 
                       'int(4)', 
                       'caption=Параметри - периоди на следене->' . $pArr['param'] . 
                       ',hint=На колко минути да се записва стойността на параметъра,unit=мин.' . $onChange . ',input');

            $paramArr[$p] = $pArr['param'];
        }
         
        for($i = 1; $i <= $this->alarmCnt; $i++) {
            $form->FLD("alarm_{$i}_message", 'varchar', "caption=Аларма {$i}->Съобщение,hint=Съобщение за лог-а,input,width=400px;");
            $form->FLD("alarm_{$i}_severity", 'enum(normal=Информация, warning=Предупреждение, alert=Аларма)', "caption=Аларма {$i}->Приоритетност,hint=Ниво на важност,input");
            $enumType = cls::get('type_Enum', array('options' => $paramArr));
            $form->FLD("alarm_{$i}_param", $enumType, "caption=Аларма {$i}->Параметър,hint=Параметър за алармиране,input");
            $form->FLD("alarm_{$i}_cond", "enum(nothing=нищо, higher=по-голямо, lower=по-малко)", "caption=Аларма {$i}->Условие,hint=Условие на действие,input");
            $form->FLD("alarm_{$i}_value", "double(4)", "caption=Аларма {$i}->Стойност за сравняване,hint=Стойност за сравняване,input");
            
            // Ако имаме изходи /релета/ за управление
            if (!empty($this->outs)) {
            	foreach ($this->outs as $out => $values) {
            		foreach ($values as $type => $set) {
            			switch ($type) {
            				case 'digital':
            					$set = array_merge(array('nothing'=>'нищо'),(array)$set);
            					$enumType = cls::get('type_Enum', array('options' => $set));
            					$form->FLD("{$out}_{$i}",$enumType,"caption=Стойност на цифров изход,hint=Как да се сетне изхода,input");
            					break;
            				case 'analog':
            					$form->FLD("{$out}_{$i}",new type_Float(array( 'size' => 6, 'min='.$set[0],'max='.$set[1])),"caption=Стойност на аналогов изход,hint=Как да се сетне изхода,input");
            					break;
            			}
            		}
            	}
            }
        }
    }

    /**
     * 
     * При всяко извикване взима данните за сензора чрез getData
     * и ги записва под ключа си в permanentData $driver->indications
     * Взима сетингите от $driver->settings
     * и се свързва с устройството според тях
     */
    function process()
    {
		$indications = permanent_Data::read($this->getIndicationsKey());
        
        if(!is_array($indications)) $indications = array();

		//sens_Sensors::log("Procesirane " . cls::getClassName($this));

		// Запазваме старото състояние за сравняване при необходимост с новите данни
		$indicationsOld = $indications;
		
		// 	Някой от данните зависят от предходното състояние на сензора
		if (!$this->getData($indications)) {
			if (!$indications['readError']) {
				sens_MsgLog::add($this->id, "Не се чете!", 3);
				$indications['readError'] = TRUE;
				permanent_Data::write($this->getIndicationsKey(),$indications);
			}
			exit(1);
		}
    	
		$indications['readError'] = FALSE;
		
		// Обикаляме всички параметри на драйвера и всичко с префикс logPeriod от настройките
		// и ако му е времето го записваме в indicationsLog-а
		$settingsArr = (array) $this->settings;		
		
		foreach ($this->params as $param => $arr) {
			// Дали параметъра е зададен да се логва при промяна?
			if ($arr['onChange']) {
				// Дали има промяна? Ако - ДА записваме в лог-а
				if ($indications["$param"] != $indicationsOld["$param"]) {
					sens_MsgLog::add($this->id,	$param . " - " . $indications["$param"], 1);
				}
			}
			
			if ($settingsArr["logPeriod_{$param}"] > 0) {
				// Имаме зададен период на следене на параметъра
				// Ако периода съвпада с текущата минута - го записваме в IndicationsLog-a
				$currentMinute = round(time() / 60);
				if ($currentMinute % $settingsArr["logPeriod_{$param}"] == 0) {
					
					sens_IndicationsLog::add(	$this->id,
												$param,
												$indications["$param"]
											);
				}
			}
		}
		
		// Ред е да задействаме аларми ако има.
		// Започваме цикъл всички идентификатори на формата
        for($i = 1; $i <= $this->alarmCnt; $i++) {
			$cond = FALSE;
			switch ($settingsArr["alarm_{$i}_cond"]) {
				
				case "lower":
					$cond = $indications[$settingsArr["alarm_{$i}_param"]] < $settingsArr["alarm_{$i}_value"];
				break;
				
				case "higher":
					$cond = $indications[$settingsArr["alarm_{$i}_param"]] > $settingsArr["alarm_{$i}_value"];
				break;
				default:
					// Прескачаме недефинираните аларми - ако е последната извикваме ф-та с цел сетване на изходите
					if (($i == $this->alarmCnt) && method_exists($this, setOuts)) {
						$this->setOuts($i,$cond,$settingsArr);
					}
					continue 2;
			}

			if ($cond && $indications["lastMsg_{$i}"] != $settingsArr["alarm_{$i}_message"].$settingsArr["alarm_{$i}_severity"]) {
				// Имаме задействана аларма и тя се изпълнява за 1-ви път - записваме в sens_MsgLog
				sens_MsgLog::add($this->id, $settingsArr["alarm_{$i}_message"],$settingsArr["alarm_{$i}_severity"]);
				
				$indications["lastMsg_{$i}"] = $settingsArr["alarm_{$i}_message"].$settingsArr["alarm_{$i}_severity"];
			}
			
			if (!$cond) unset($indications["lastMsg_{$i}"]);

			// Ако имаме дефинирани изходи извикваме функцията за тяхното сетване
			if (method_exists($this, setOuts)) {
				$this->setOuts($i,$cond,$settingsArr);
			}
		} 
		
		$this->getData($indications);
		
		if (!permanent_Data::write($this->getIndicationsKey(),$indications)) {
			sens_Sensors::log("Неуспешно записване на " . cls::getClassName($this));
		}
	}
}