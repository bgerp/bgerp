<?php



/**
 * Прототип на драйвер за IP устройство
 *
 *
 * @category  bgerp
 * @package   sens
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Сензори
 */
class sens_driver_IpDevice extends core_BaseClass
{
    
    
    /**
     * Интерфeйси, поддържани от всички наследници
     */
    public $interfaces = 'sens_DriverIntf, permanent_SettingsIntf';
    
    
    /**
     * id на устройството
     */
    public $id;
    
    
    /**
     * /** Параметри които чете или записва драйвера - предефинирани в драйверите наследници
     * Например:
     * array(
     * 'T' => array('unit'=>'T', 'param'=>'Температура', 'details'=>'C'),
     * 'Hr' => array('unit'=>'Hr', 'param'=>'Влажност', 'details'=>'%'),
     * 'Dst' => array('unit'=>'Dst', 'param'=>'Запрашеност', 'details'=>'%'),
     * 'Chm' => array('unit'=>'Chm', 'param'=>'Хим. замърсяване', 'details'=>'%')
     * );
     */
    public $params = array();
    
    
    /**
     * Брой последни стойности на базата на които се изчислява средна стойност
     * @var integer
     */
    public $avgCnt = 10;
    
    
    /**
     * Брой аларми - хубаво е да се предефинира в драйверите наследници
     */
    public $alarmCnt = 3;
    
    
    /**
     * Описания на изходите ако има такива
     * Пример: array(
     * 'out1' => array('digital' => array(0,1)),
     * 'out2' => array('digital' => array(0,1)),
     * 'out3' => array('analog' => array(0,10))
     * );
     */
    public $outs = array();
    
    
    /**
     * Показанията на сензора - попълва се от updateState, записва се в permanent_Data(getStateKey());
     * Примерна структура:
     * array(
     * 'T' => 5,
     * 'Hr' => 45,
     * 'Dst' => 40,
     * 'Chm' => 20,
     * 'out1' => 1,
     * 'out2' => 0,
     * 'out3' => 4.8
     * );
     */
    public $stateArr = array();
    
    
    /**
     * Начално установяване на параметрите
     */
    public function init($params = array())
    {
        $params = arr::make($params, true);
        parent::init($params);
        permanent_Settings::setObject($this);
    }
    
    
    /**
     * Зарежда в $this->stateArr всички досегашни данни, които драйвера пази
     * от permanent_Data
     */
    public function loadState()
    {
        if (empty($this->stateArr)) {
            $this->stateArr = permanent_Data::read($this->getStateKey());
        }
        
        return (array) $this->stateArr;
    }
    
    
    /**
     * Запазва всички актуални данни в permanent_Data
     */
    public function saveState()
    {
        if (!permanent_Data::write($this->getStateKey(), $this->stateArr)) {
            $this->logWarning('Неуспешно записване на драйвер');
        }
    }
    
    
    /**
     * Прочита текущото състояние на драйвера/устройството
     * Реализира се в наследниците
     * в зависимост от начина на четенето на входовете и изходите
     */
    public function updateState()
    {
    }
    
    
    /**
     * Връща настройките на обекта от permanent_Data
     */
    public function getSettings()
    {
        if (!$this->settings && $this->id) {
            permanent_Settings::setObject($this);
        }

        return $this->settings;
    }
    
    
    /**
     * Задава вътрешните сетинги на обекта
     */
    public function setSettings($data)
    {
        if (!$data) {
            
            return false;
        }
        $this->settings = $data;
    }
    
    
    /**
     * Връща уникален за обекта ключ под който
     * ще се запишат сетингите в permanent_Data
     */
    public function getSettingsKey()
    {
        return core_String::convertToFixedKey(cls::getClassName($this) . '_' . $this->id . 'Settings');
    }
    
    
    /**
     * Връща уникален за обекта ключ под който
     * ще се запишат показанията в permanent_Data
     */
    public function getStateKey()
    {
        return core_String::convertToFixedKey(cls::getClassName($this) . '_' . $this->id . 'State');
    }
    
    
    /**
     * Записва в мениджъра на параметрите - параметрите на драйвера
     * Ако има вече такъв unit не прави нищо
     */
    public function setParams()
    {
        $Params = cls::get('sens_Params');
        
        foreach ($this->params as $param) {
            $rec = (object) $param;
            $rec->id = $Params->fetchField("#unit = '{$param[unit]}'", 'id');
            
            $Params->save($rec);
        }
    }

    /**
     * Добавя във формата за настройки на сензора
     * стандартните параметри и условията
     */
    public function getSettingsForm($form)
    {
        $paramArr[''] = 'избери';
        
        foreach ($this->params as $p => $pArr) {
            if ($pArr['onChange']) {
                $onChange = ' или при промяна';
            }
            if (strpos($p, 'InA') === false) {
                $form->FLD(
                    'logPeriod_' . $p,
                    'int(4)',
                    'caption=Параметри - периоди на следене->' . $pArr['param'] .
                    ',hint=На колко минути да се записва стойността на параметъра,unit=мин.' . $onChange . ',input'
                );
            } else {
                // Входа е аналогов
                $form->FLD(
                    'logPeriod_' . $p,
                    'int(4)',
                    'caption=Параметри - периоди на следене - ' . $pArr['param'] .'->' . 'Период на следене' .
                    ',hint=На колко минути да се записва стойността на параметъра,unit=мин.' . $onChange . ',input'
                );
                
                // Подготвяме масива за setOptions
                $queryParams = sens_Params::getQuery();
                $queryParams->show('unit, param, details');
                while ($res = $queryParams->fetch()) {
                    $arrRes["{$res->unit}"] = $res->param . ' ' . $res->details;
                }

                $arrRes = array('empty' => '') + $arrRes;
                $form->FLD(
                    'name_' . $p,
                    'enum',
                    'caption=Параметри - периоди на следене - ' . $pArr['param'] .'->Наименование,hint=Наименование' . $onChange . ',input'
                );

                $form->setOptions("name_{$p}", $arrRes);
                
                $form->FLD(
                
                    'angular_' . $p,
                    'double(decimals=2)',
                    'caption=Параметри - периоди на следене - ' . $pArr['param'] .'->Ъглов коефициент,hint=Коефициент на линейното уравнение,input'
                
                );
                $form->FLD(
                    'linear_' . $p,
                    'double(decimals=2)',
                    'caption=Параметри - периоди на следене - ' . $pArr['param'] .'->Линеен коефициент,hint=отстояние на линейната функция,input'
                );
            }
            $paramArr[$p] = $pArr['param'];
        }
        
        for ($i = 1; $i <= $this->alarmCnt; $i++) {
            $form->FLD("alarm_{$i}_message", 'varchar', "caption=Аларма {$i}->Съобщение,hint=Съобщение за лог-а,input,width=400px;");
            $form->FLD("alarm_{$i}_severity", 'enum(normal=Информация, warning=Предупреждение, alert=Аларма)', "caption=Аларма {$i}->Приоритетност,hint=Ниво на важност,input");
            $enumType = cls::get('type_Enum', array('options' => $paramArr));
            $form->FLD("alarm_{$i}_param", $enumType, "caption=Аларма {$i}->Параметър,hint=Параметър за алармиране,input");
            $form->FLD("alarm_{$i}_cond", 'enum(nothing=нищо, higher=по-голямо, lower=по-малко)', "caption=Аларма {$i}->Условие,hint=Условие на действие,input");
            $form->FLD("alarm_{$i}_value", 'double(4)', "caption=Аларма {$i}->Стойност за сравняване,hint=Стойност за сравняване,input");
            
            // Ако имаме изходи /релета/ за управление
            if (!empty($this->outs)) {
                foreach ($this->outs as $out => $values) {
                    foreach ($values as $type => $set) {
                        switch ($type) {
                            case 'digital':
                                $set = array_merge(array('nothing' => 'нищо'), (array) $set);
                                $enumType = cls::get('type_Enum', array('options' => $set));
                                $form->FLD("{$out}_{$i}", $enumType, 'caption=Стойност на цифров изход,hint=Как да се сетне изхода,input');
                                break;
                            case 'analog':
                                $form->FLD("{$out}_{$i}", new type_Float(array('size' => 6, 'min=' . $set[0], 'max=' . $set[1])), 'caption=Стойност на аналогов изход,hint=Как да се сетне изхода,input');
                                break;
                        }
                    }
                }
            }
        }
    }
    
    
    /**
     * При всяко извикване взима данните за сензора чрез loadState
     * и ги записва под ключа си в permanentData
     * Взима сетингите от $driver->settings
     * и се свързва с устройството според тях
     */
    public function process()
    {
        // Запазваме старото състояние за сравняване при необходимост с новите данни
        $stateArrOld = $this->loadState();
        
        if (!$this->updateState()) {
            if (!$stateArrOld['readError']) {
                sens_MsgLog::add($this->id, 'Не се чете!', 3);
                $this->stateArr['readError'] = true;
                $this->saveState();
            }
            exit(1);
        }
        
        $this->stateArr['readError'] = false;
        
        // Обикаляме всички параметри на драйвера и всичко с префикс logPeriod от настройките
        // и ако му е времето го записваме в indicationsLog-а
        $settingsArr = (array) $this->getSettings();

        foreach ($this->params as $param => $arr) {
            // Ако в сетингите е зададено, че параметъра е изчисляем:
            // Създаваме logPeriod
            if (!empty($settingsArr["name_{$param}"]) && $settingsArr["name_{$param}"] != 'empty') {
                $settingsArr["logPeriod_{$settingsArr["name_{$param}"]}"] = $settingsArr["logPeriod_{$param}"];
                $param = $settingsArr["name_{$param}"];
            }

            // Дали параметъра е зададен да се логва при промяна?
            if ($arr['onChange']) {
                // Дали има промяна? Ако - ДА записваме в лог-а
                if ($this->stateArr["${param}"] != $stateArrOld["${param}"]) {
                    sens_MsgLog::add($this->id, $param . ' - ' . $this->stateArr["${param}"], 1);
                }
            }
            
            if ($settingsArr["logPeriod_{$param}"] > 0) {
                // Имаме зададен период на следене на параметъра
                // Ако периода съвпада с текущата минута - го записваме в IndicationsLog-a
                $currentMinute = round(time() / 60);
                
                if ($currentMinute % $settingsArr["logPeriod_{$param}"] == 0) {
                    sens_IndicationsLog::add(
                    
                        $this->id,
                        $param,
                        $this->stateArr["${param}"]
                    );
                }
            }
        }
        
        // Ред е да задействаме аларми ако има.
        // Започваме цикъл всички идентификатори на формата
        for ($i = 1; $i <= $this->alarmCnt; $i++) {
            $cond = false;
            
            switch ($settingsArr["alarm_{$i}_cond"]) {
                
                case 'lower':
                    $cond = $this->stateArr[$settingsArr["alarm_{$i}_param"]] < $settingsArr["alarm_{$i}_value"];
                    break;
                
                case 'higher':
                    $cond = $this->stateArr[$settingsArr["alarm_{$i}_param"]] > $settingsArr["alarm_{$i}_value"];
                    break;
                default:
                // Прескачаме недефинираните аларми
                continue 2;
            }
            
            // Ако имаме задействано условие
            if ($cond) {
                // и то се изпълнява за 1-ви път
                if ($stateArrOld["lastMsg_{$i}"] != $settingsArr["alarm_{$i}_message"] . $settingsArr["alarm_{$i}_severity"]) {
                    // => ако има съобщение - записваме в sens_MsgLog
                    if (!empty($settingsArr["alarm_{$i}_message"])) {
                        sens_MsgLog::add($this->id, $settingsArr["alarm_{$i}_message"], $settingsArr["alarm_{$i}_severity"]);
                    }
                }
                
                // При задействано условие запазваме съобщението в състоянието на сензора за следваща употреба
                $lastMsgArr["lastMsg_{$i}"] = $settingsArr["alarm_{$i}_message"] . $settingsArr["alarm_{$i}_severity"];
                
                // Тук ще зададем състоянието на изходите /ако има такива/
                // в зависимост от изпълненото условие /задействаната аларма/
                if (is_array($this->outs)) {
                    foreach ($this->outs as $out => $type) {
                        // Прескачаме изходите със стойност 'nothing' а останалите ги санитизираме
                        if ($settingsArr["{$out}_{$i}"] != 'nothing') {
                            switch ($type) {
                                case 'digital':
                                    $settingsArr["{$out}_{$i}"] = empty($settingsArr["{$out}_{$i}"]) ? 0 : 1;
                                    break;
                                case 'analog':
                                    $settingsArr["{$out}_{$i}"] = (int) $settingsArr["{$out}_{$i}"];
                                    break;
                            }
                            $newOuts[$out] = $settingsArr["{$out}_{$i}"];
                        }
                    }
                }
            }   // if ($cond)
        }

        if (is_array($newOuts)) {
            $this->setOuts($newOuts);
            $this->updateState();
        }
        // Добавяме последните аларми към състоянието ако е имало такива
        $this->stateArr = array_merge((array) $this->stateArr, (array) $lastMsgArr);
        
        $this->saveState();
    }
    
    
    /**
     * Връща URL - входна точка за настройка на данните за този обект.
     * Ключа в URL-то да бъде декориран с кодировка така,
     * че да е валиден само за текущата сесия на потребителя.
     * @param object $object
     */
    public function getUrl()
    {
        return array('sens_Sensors', 'Settings', $this->id);
    }
    
    
    /**
     * Връща линк с подходяща картинка към входната точка за настройка на данните за този обект
     * @param object $object
     */
    public function getLink()
    {
        return ht::createLink(
            '<img width=16 height=16 src=' . sbf('img/16/testing.png') . '>',
            array('sens_Sensors', 'Settings', $this->id)
        );
    }
    
    
    /**
     * Връща заглавието на драйвера
     */
    public function getTitle()
    {
        $settings = $this->getSettings();
        
        return $settings->title ? $settings->title : cls::getClassName($this);
    }
    
    
    /**
     * Връща HTML блок, показващ вербално състоянието на сензора
     */
    public function renderHtml()
    {
        $this->loadState();
        $settings = (array) $this->settings;

        $html = '<table colspan=0 rowspan=0>';
        foreach ($this->params as $param => $properties) {
            
            // Празните параметри не ги показваме
            if (empty($this->stateArr["{$param}"]) || $param == 'empty') {
                continue;
            }
            
            // Ако параметъра е аналогов и има функция за изчислението му показваме само изчисления параметър
            if (strpos($param, 'InA') !== false && !empty($settings["name_{$param}"]) && $settings["name_{$param}"] != 'empty') {
                $query = sens_params::getQuery();
                $query->where('#unit="' . $settings["name_{$param}"] . '"');
                $countable = $query->fetch();
                
                $html .= "<tr><td>{$settings["name_{$param}"]}</td><td>= " . round($this->stateArr["{$settings["name_{$param}"]}"], 2) . " {$countable->details}</td></tr>";
                continue;
            }
            
            // Стринговете се кастват към float, и ако са !=0 се показват
            $valParam = floatval($this->stateArr["{$param}"]);
            if (empty($valParam)) {
                continue;
            }
            
            $html .= "<tr><td>{$param}</td><td>= " . round($valParam, 2) . " {$properties['details']}</td></tr>";
        }
        $html .= '</table>';
        
        return $html;
    }
}
