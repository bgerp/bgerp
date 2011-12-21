<?php
/**
 * Прототип на драйвер за IP устройство
 *
 * @category   bgERP 2.0
 * @package    sens
 * @title:     Сензори
 * @author     Димитър Минеков <mitko@extrapack.com>
 * @copyright  2006-2011 Experta Ltd.
 * @license    GPL 2
 * @since      v 0.1
 */

class sens_driver_IpDevice extends core_BaseClass
{
    /**
     * Интерфeйси, поддържани от всички наследници
     */
    var $interfaces = 'sens_DriverIntf, permanent_SettingsIntf';
    
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
     * Показанията на сензора - попълва се от updateState, записва се в permanent_Data(getStateKey());
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
	 *
     */
    var $stateArr = array();
    
    /**
     * Начално установяване на параметрите
     */
    function init( $params = array() )
    {
        $params = arr::make($params, TRUE);
        parent::init($params);
        permanent_Settings::setObject($this);
    	//$this->settings = $this->getSettings();
    }
    
    
    /**
     * 
     * Зарежда в $this->stateArr всички досегашни данни, които драйвера пази
     * от permanent_Data
     */
    function loadState()
    {
    	if (empty($this->stateArr)) {
    		$this->stateArr = permanent_Data::read($this->getStateKey());
    	}
    	
    	return (array) $this->stateArr;
    }
    
    /**
     * 
     * Запазва всички актуални данни в permanent_Data
     */
    function saveState()
    {
		if (!permanent_Data::write($this->getStateKey(),$this->stateArr)) {
			sens_Sensors::log("Неуспешно записване на " . cls::getClassName($this));
		}
    }
    
	/**
     * Прочита текущото състояние на драйвера/устройството
     * Реализира се в наследниците
     * в зависимост от начина на четенето на входовете и изходите
     */
    function updateState()
    {
    	
    }
    
    /**
     * 
     * Връща настройките на обекта от permanent_Data
     */
    function getSettings()
    {
        if(!$this->settings && $this->id) {
            permanent_Settings::setObject($this);
        }

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
	function getStateKey()
	{
		return core_String::convertToFixedKey(cls::getClassName($this) . "_" . $this->id . "State");
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
     * Добавя във формата за настройки на сензора
     * стандартните параметри и условията
     */
    function getSettingsForm($form)
    {
        
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
     * При всяко извикване взима данните за сензора чрез loadState
     * и ги записва под ключа си в permanentData
     * Взима сетингите от $driver->settings
     * и се свързва с устройството според тях
     */
    function process()
    {
    	// Запазваме старото състояние за сравняване при необходимост с новите данни
    	$stateArrOld = $this->loadState(); 
    	
    	if (!$this->updateState()) {
			if (!$stateArrOld['readError']) {
				sens_MsgLog::add($this->id, "Не се чете!", 3);
				$this->stateArr['readError'] = TRUE;
				$this->saveState();
			}
			exit(1);
		}
    	
		$this->stateArr['readError'] = FALSE;
		
		// Обикаляме всички параметри на драйвера и всичко с префикс logPeriod от настройките
		// и ако му е времето го записваме в indicationsLog-а
		$settingsArr = (array) $this->getSettings();
		
		foreach ($this->params as $param => $arr) {
			// Дали параметъра е зададен да се логва при промяна?
			if ($arr['onChange']) {
				// Дали има промяна? Ако - ДА записваме в лог-а
				if ($this->stateArr["$param"] != $stateArrOld["$param"]) {
					sens_MsgLog::add($this->id,	$param . " - " . $this->stateArr["$param"], 1);
				}
			}
			
			if ($settingsArr["logPeriod_{$param}"] > 0) {
				// Имаме зададен период на следене на параметъра
				// Ако периода съвпада с текущата минута - го записваме в IndicationsLog-a
				$currentMinute = round(time() / 60);
				if ($currentMinute % $settingsArr["logPeriod_{$param}"] == 0) {
					
					sens_IndicationsLog::add(	$this->id,
												$param,
												$this->stateArr["$param"]
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
					$cond = $this->stateArr[$settingsArr["alarm_{$i}_param"]] < $settingsArr["alarm_{$i}_value"];
				break;
				
				case "higher":
					$cond = $this->stateArr[$settingsArr["alarm_{$i}_param"]] > $settingsArr["alarm_{$i}_value"];
				break;
				default:
					// Прескачаме недефинираните аларми
					continue 2;
			}
			
			// Ако имаме задействано условие
			if ($cond) {
				// и то се изпълнява за 1-ви път
				if ($stateArrOld["lastMsg_{$i}"] != $settingsArr["alarm_{$i}_message"].$settingsArr["alarm_{$i}_severity"]) {
					// => ако има съобщение - записваме в sens_MsgLog
					if (!empty($settingsArr["alarm_{$i}_message"])) {// bp($stateArrOld["lastMsg_{$i}"]);
						sens_MsgLog::add($this->id, $settingsArr["alarm_{$i}_message"],$settingsArr["alarm_{$i}_severity"]);
						
						$lastMsgArr["lastMsg_{$i}"] = $settingsArr["alarm_{$i}_message"].$settingsArr["alarm_{$i}_severity"];
						
					}
				}
				// Тук ще зададем състоянието на изходите /ако има такива/ 
				// в зависимост от изпълненото условие /задействаната аларма/
				if (is_array($this->outs)) {
					foreach ($this->outs as $out => $type) {
						// Прескачаме изходите със стойност 'nothing' а останалите ги санитизираме 
						if ($settingsArr["{$out}_{$i}"] != 'nothing') {
							switch ($type) {
								case 'digital':
									$settingsArr["{$out}_{$i}"] = empty($settingsArr["{$out}_{$i}"])?0:1;
								break;
								case 'analog':
									$settingsArr["{$out}_{$i}"] = (int) $settingsArr["{$out}_{$i}"];
								break;
							}
							$newOuts[$out] = $settingsArr["{$out}_{$i}"];
						}
					}
				}				
			} else {
				unset($this->stateArr["lastMsg_{$i}"]);
			}
		}
		
		if (is_array($newOuts)) {
			$this->setOuts($newOuts); 
			$this->updateState();
		}
		
		// Добавяме последните аларми към състоянието ако е имало такива
		$this->stateArr = array_merge((array)$this->stateArr, (array)$lastMsgArr);
		
		$this->saveState();
	}
	
	/**
	 * Връща URL - входна точка за настройка на данните за този обект.
	 * Ключа в URL-то да бъде декориран с кодировка така,
	 * че да е валиден само за текущата сесия на потребителя.
	 * @param object $object
	 */
	function getUrl()
	{
		return array('sens_Sensors', 'Settings', $this->id);
	}
	
	/**
	 * 
	 * Връща линк с подходяща картинка към входната точка за настройка на данните за този обект
	 * @param object $object
	 */
	function getLink()
	{
		return ht::createLink("<img width=16 height=16 src=" . sbf('img/16/testing.png') . ">",
								array('sens_Sensors', 'Settings', $this->id)
							);
	}
	
    /**
     * Връща заглавието на драйвера
     */
    function getTitle()
    {   
        $settings = $this->getSettings();

        return $settings->title ? $settings->title : cls::getClassName($this);
    }
    
    /**
     * Връща HTML блок, показващ вербално състоянието на сензора
     */
    function renderHtml()
    {

    	$this->loadState();
    	
        foreach ($this->params as $param => $properties) {
        	
        	// Празните параметри не ги показваме
        	if (empty($this->stateArr["{$param}"]) && !is_numeric($this->stateArr["{$param}"])) continue;
        	
        	// Стринговите се обработват различно
        	 if (!is_numeric($this->stateArr["{$param}"])) {
        	 	$html .= "{$param} = ". $this->stateArr["{$param}"] ." {$properties['details']}<br>";
        	 	continue;
        	 }
        	
        	$html .= "{$param} = ". round($this->stateArr["{$param}"],2) ." {$properties['details']}<br>";	
        }
    	
        return $html;
    }
}