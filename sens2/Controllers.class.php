<?php



/**
 * Мениджър на входно-изходни контролери
 *
 *
 * @category  bgerp
 * @package   sens2
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sens2_Controllers extends core_Master
{
    
    
    /**
     * Необходими плъгини
     */
    var $loadList = 'plg_Created, plg_Rejected, plg_RowTools2, plg_State2, plg_Rejected, sens2_Wrapper';
                      
    
    /**
     * Заглавие
     */
    var $title = 'Контролери';
    
    
    /**
     * Полето "Наименование" да е хипервръзка към единичния изглед
     */
    var $rowToolsSingleField = 'name';


    /**
     * Заглавие в единичния изглед
     */
    var $singleTitle = 'Контролер';


    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/network-ethernet-icon.png';


    /**
     * Права за писане
     */
    var $canWrite = 'ceo,sens,admin';
    
    
    /**
     * Права за запис
     */
    var $canRead = 'ceo, sens, admin';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'debug';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,admin,sens';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,admin,sens';
    

    var $canUpdate = 'ceo,admin,sens';

    /**
     * Масиви за кеширане пер хит на инсталираните портове
     */
    static $inputs, $outputs;
    

    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'identifier(64,utf8)', 'caption=Наименование, mandatory,notConfig');
        $this->FLD('driver', 'class(interface=sens2_DriverIntf, allowEmpty, select=title)', 'caption=Драйвер,silent,mandatory,notConfig,placeholder=Тип на контролера');
        $this->FLD('config', 'blob(serialize, compress)', 'caption=Конфигурация,input=none,single=none,column=none');
        $this->FLD('state', 'enum(active=Активен, closed=Спрян)', 'caption=Състояние,input=none');
        $this->FLD('persistentState', 'blob(serialize)', 'caption=Персистентно състояние,input=none,single=none,column=none');

        $this->setDbUnique('name');
    }


    /**
     * Връща инстанция на драйвера за посочения контролер
     */
    public static function getDriver($controllerId)
    {
        static $drivers = array();

        if(!isset($drivers[$controllerId])) {
            $rec = self::fetch($controllerId);
            $drivers[$controllerId] = cls::get($rec->driver);
        }

        return $drivers[$controllerId];
    }
    

    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareEditform($mvc, &$data)
    {
        $form = $data->form;
        $rec =  $form->rec;
        
        $exFields = $form->selectFields();

        if($rec->driver) {
            self::prepareConfigForm($form, $rec->driver);
        }
 
        if($rec->id) {
            $form->setReadOnly('driver');
            $config = (array) self::fetch($rec->id)->config;
            if(is_array($config)) {  
                foreach($config as $key => $value) {
                    $rec->{$key} = $value;
                }
            }
        } else {
            $fldList = '';
            $newFields = $form->selectFields();
            foreach($newFields as $name => $fld) {
                if(!$exFields[$name]) {
                    $fldList .= ($fldList ? '|' : '') . $name;
                }
            }
            if($fldList) {
                $form->setField('driver',  "removeAndRefreshForm={$fldList}");
            } else {
                $form->setField('driver',  "refreshForm");
            }
        }

    }


    /**
     * Връща обекта драйвер за посочения контролер
     */
    static function getActivePorts($controllerId, $type = 'all')
    {   
        static $ap = array();

        if(!$ap[$controllerId . '_' . $type]) {
            $ap[$controllerId . '_' . $type] = array();
            $rec = self::fetch($controllerId);
            $drv = self::getDriver($controllerId);
            
            $ports = array();

            if($type != 'outputs') {
                $ports = $drv->getInputPorts();
            }
            
            if($type != 'inputs') {
                $ports += $drv->getOutputPorts();
            }
 
            $config = $rec->config;
            foreach($ports as $port => $params) {
                $partName = $port . '_name';
                if($config->{$partName}) {
                    $caption = $port . " (". $config->{$partName} . ")";
                    $title = '$' . $rec->name . '->' . $config->{$partName};
                } else {
                    $caption = new stdClass();
                    $caption->title = $port . " (". $params->caption . ")";
                    $caption->attr = array('style' => 'color:#999;');
                    $title = '$' . $rec->name . '->' . $port;
                }
                $partUom = $port . '_uom';
                $ap[$controllerId . '_' . $type][$port] = (object) array('caption' => $caption, 'uom' => $config->{$partUom}, 'title' => $title);
            }
        }
  
        return  $ap[$controllerId . '_' . $type];
    }



    /**
     * Подготвя конфигурационната форма на посочения драйвер
     */
    static function prepareConfigForm($form, $driver)
    {
        $drv = cls::get($driver);
        $drv->prepareConfigForm($form);

        $ports = $drv->getInputPorts();

        if(!$ports) {
            $ports = array();
        }

        expect(is_array($ports));

        foreach($ports as $port => $params) {
            
            $prefix = $port . ($params->caption ? " ({$params->caption})" : "");

            $form->FLD($port . '_name', 'identifier(32,utf8)', "caption={$prefix}->Наименование");
            $form->FLD($port . '_uom', 'varchar(16)', "caption={$prefix}->Единица");
            $form->FLD($port . '_scale', 'varchar(255,valid=sens2_Controllers::isValidExpr)', "caption={$prefix}->Скалиране,hint=Въведете функция на X с която да се скалира стойността на входа. Например: `X*50` или `X/2`");
            $form->FLD($port . '_update', 'time(suggestions=1 min|2 min|5 min|10 min|30 min,uom=minutes)', "caption={$prefix}->Четене през");
            $form->FLD($port . '_log', 'time(suggestions=1 min|2 min|5 min|10 min|30 min,uom=minutes)', "caption={$prefix}->Логване през");
            if(trim($params->uom)) {
                $form->setSuggestions($port . '_uom', arr::combine(array('' => ''), arr::make($params->uom, TRUE)));
            }
        }

        $ports = $drv->getOutputPorts();

        if(!$ports) {
            $ports = array();
        }
        
        foreach($ports as $port => $params) {

            $prefix = $port . ($params->caption ? " ({$params->caption})" : "");

            $form->FLD($port . '_name', 'identifier(32,utf8)', "caption={$prefix}->Наименование");
            $form->FLD($port . '_uom', 'varchar(16)', "caption={$prefix}->Единица");
            if(trim($params->uom)) {
                $form->setSuggestions($port . '_uom', arr::combine(array('' => ''), arr::make($params->uom, TRUE)));
            }
        }
    }
    
    
    /**
     * Валидира дали е правилен израза
     */
    static function isValidExpr($value, &$res)
    {   
        if(!trim($value)) return;

        $value = strtolower(str_replace(' ', '', $value));
        $value = preg_replace("/(^|[^a-z0-9])x([^a-z0-9]|$)/", '$1X$2', $value);
 
        if(strpos($value, 'X') === FALSE) {
            $res['error'] = "В израза трябва да се съдържа променливата `X`";
        } elseif(preg_match('/ХХ/', $value)) {
            $res['error'] = "Между променливите трябва да има аритметични операции";
        } elseif(!str::prepareMathExpr(str_replace('X', '1', $value))) {
            $res['error'] = "Невалиден израз за скалиране";
        } else {
            $res['value'] = str_replace(array('+', '-', '*', '/'), array(' + ', ' - ', ' * ', ' / '), $value);
        }
    }


    /**
     * Изпълнява се след въвеждането на данните от заявката във формата
     */
    function on_AfterInputEditForm($mvc, $form)
    {
        if($form->isSubmitted() && $form->rec->driver) {
            $drv = cls::get($form->rec->driver);
            $drv->checkConfigform($form);
            if(!$form->gotErrors()) {
                $configFields = array_keys($form->selectFields("(#input == 'input' || #input == '') && !#notConfig"));
                $form->rec->config = new stdClass();
                foreach($configFields as $field) {
                    $form->rec->config->{$field} = $form->rec->{$field};
                }
            }
         }
    }


    function on_AfterPrepareSingleToolbar($mvc, $res, $data)
    {
        if($mvc->haveRightFor('update', $data->rec)) {
            $data->toolbar->addBtn('Обноваване', array($mvc, 'updateInputs', $data->rec->id));
        }
    }


    /**
     * Обновява стойностите на посочения контролер
     */
    function act_UpdateInputs()
    {
        $this->requireRightFor('update');

        expect($id = Request::get('id', 'int'));

        expect($rec = self::fetch($id));
 
        $drv = self::getDriver($id);
    
        $ports = $drv->getInputPorts();
 
        foreach($ports as $name => $def) {
            $part = "{$name}_update";
            if($rec->config->{$part} > 0) {
                $force[$name] = $name;
            }
        }

        $res = $this->updateInputs($id, $force, FALSE);

        return new Redirect(array($this, 'Single', $id), "|Обновени са|* <b>{$res}</b> |входа на контролера");
    }


    /**
     * Обновява стойностите на входовете за посочения контролер
     * Новите стойности се записват в sens2_Ports, а тези, за които е дошло време се логват
     * 
     * @param $id       int    id на контролер
     * @param $force    array  Масив с портове, които задължително трябва да бъдат обновени
     * @param $sav      bool   Дали да се запишат стойностите в dataLog
     */
    function updateInputs($id, $force = array(), $save = TRUE)
    { 
        expect($rec = self::fetch($id));

        $config = (array) $rec->config;

        $drv = self::getDriver($id);

        $ports = $drv->getInputPorts();

        $nowMinutes = round(time()/60);
        
        $inputs = $force;

        $updatedCnt = 0;
        
        if(is_array($ports)) {
            foreach($ports as $port => $params) {
                
                $updateMinutes = abs(round($config[$port . '_update'] / 60));
                if($updateMinutes && ($nowMinutes % $updateMinutes) == 0) {
                    $inputs[$port] = $port;
                }

                $logMinutes = abs(round($config[$port . '_log'] / 60));
                if($logMinutes && ($nowMinutes % $logMinutes) == 0) {
                    $inputs[$port] = $port;
                    $log[$port] = $port;
                }
            }
        }


        if(is_array($inputs) && count($inputs)) {

            // Прочитаме състоянието на входовете от драйвера
            if($rec->persistentState) {
                $hash = md5(serialize($rec->persistentState));
            }
            $values = $drv->readInputs($inputs, $rec->config, $rec->persistentState);
            if($rec->persistentState && $hash != md5(serialize($rec->persistentState))) {
                self::save($rec, 'persistentState');
            }

            // Текущото време
            $time = dt::now();

            foreach($inputs as $port) {
                
                if(is_array($values)) {
                    $value = $values[$port];
                } else {
                    // Ако не получим масив със стойности, приемаме, че сме получили грешка
                    // и размножаваме грешката за всички входове на контролера
                    $value = $values;
                }
                
                if(($expr = $config[$port . '_scale']) && is_numeric($value)) {  
                    $expr = str_replace('X', $value, $expr);
                    $value = str::calcMathExpr($expr);
                }
                   
                // Обновяваме индикатора за стойността на текущия контролерен порт
                $indicatorId = sens2_Indicators::setValue($rec->id, $port, $value, $time);
                
                if($indicatorId) {
                    $updatedCnt++;
                }
                // Ако е необходимо, записваме стойноста на входа в дата-лог-а
                if($log[$port] && $indicatorId && $save) {
                    sens2_DataLogs::addValue($indicatorId, $value, $time);
                }
            }
        }

        return $updatedCnt;
    }


    /**
     * Задава стойност на физически изход. Те се записва и в модела.
     */
    public static function setOutput($output, $value)
    {
        list($ctrName, $name) = explode('->', ltrim($output, '$'));
        // Вземаме записа на контролера
        $rec = self::fetch(array("#name = '[#1#]'", $ctrName));
        
        if($rec) {
            // Вземаме драйвера
            $drv = self::getDriver($rec->id);
            
            // Вземаме му всички изходни портове
            $ports = $drv->getOutputPorts();

            foreach($ports as $p => $pObj) {
                $part = $p . '_name';
                if($p == $name || $rec->config->{$part} == $name) {
                    $portName = $p;
                    break;
                }
            }

            if($portName) {
                $sets = array($portName => $value);
                
                if($rec->persistentState) {
                    $hash = md5(serialize($rec->persistentState));
                }
                $res = $drv->writeOutputs($sets, $rec->config, $rec->persistentState);
                if($rec->persistentState && $hash != md5(serialize($rec->persistentState))) {
                    self::save($rec, 'persistentState');
                }
            }
        }
     
        if(!$res[$portName]) {
            $value = "Грешка при запис";
        }
        
        // Записване стойността в индикаторите
        if($rec->id && $portName) {
            sens2_Indicators::setValue($rec->id, $portName, $value, dt::verbal2mysql());
        }

        return $res;
    }

    
    
    /**
     * Показваме актуални данни за всеки от параметрите
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
    }
    

    /**
     * Стартира се на всяка минута от cron-a
     * Извиква по http sens_Sensors->act_Process
     * за всеки 1 драйвер като предава id и key - ключ,
     * базиран на id на драйвера и сол
     */
    function cron_Update()
    {
        $query = self::getQuery();
        $query->where("#state = 'active'"); 
        $cnt = $query->count();
        
        if (!$cnt) return ;
        
        $sleepNanoSec = round(min(0.5, 25/$cnt) * 1000000000);
 

        while ($rec = $query->fetch("#state = 'active'")) {
            
            if($mustSleep) {
                time_nanosleep(0, $sleepNanoSec);
            }

            $url = toUrl(array($this, 'Update', str::addHash($rec->id)), 'absolute');
            
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Connection: close')); 
            $data = curl_exec($curl);
            curl_close($curl);

           // $data = file_get_contents($url);

            $res .= "<li>" . $data . "</li>";

            $mustSleep = TRUE;
        }
        
        if(Request::get('forced')) {
            echo $res;
        }
    }


    /**
	 * За да не могат да се изтриват активните контролери
	 */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{  
   		if($action == 'delete') {
	    	if($rec->state != 'closed'){
	    		$res = 'no_one';
	    	}
   		}
   		
	}
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy('#createdOn', 'DESC');
    }
    
    
    /**
     * Приема id и key - базиран на драйвера и сол
     * Затваря връзката с извикващия преждевременно.
     * Инициализира обект $driver
     * и извиква $driver->process().
     */
    function act_Update()
    { 
        $id = str::checkHash(Request::get('id', 'varchar'));
        
        if(!$id && haveRole('debug')) {
            $id = Request::get('device', 'int');
        }
        
        if(!$id) {
            echo "Controllers::Update - miss id on " . dt::now();
            die;
        }
        
        echo "Controllers::Update for device with id={$id} started on " . dt::now();
        
        if(!haveRole('debug')) {
            core_App::flushAndClose();
        }

        // Освобождава манипулатора на сесията. Ако трябва да се правят
        // записи в сесията, то те трябва да се направят преди shutdown()
        if (session_id()) session_write_close();


        if($id) {
            // Извършваме обновяването "на сянка""
            $this->updateInputs($id);
        }

        die;
    }
}
