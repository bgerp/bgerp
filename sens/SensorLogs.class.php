<?php

/**
 * Мениджър за логовете на сензорите
 */
class sens_SensorLogs extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, sens_Wrapper, plg_Sorting,
                     Sensors=sens_Sensors, Params=sens_Params, Limits=sens_Limits,
                     plg_Chart, plg_RefreshRows';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Записи от сензорите';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $refreshRowsTime = 15000;
    
    
    /**
     * Права
     */
    var $canWrite = 'sens, admin';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canRead = 'sens, admin';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listItemsPerPage = 500;
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('sensorId', 'key(mvc=sens_Sensors, select=title, allowEmpty)', 'caption=Сензор,chart=series');
        $this->FLD('paramId', 'key(mvc=sens_Params, select=param, allowEmpty)', 'caption=Параметър,chart=diff');
        $this->XPR('valueAvg', 'double(decimals=2)', 'ROUND(AVG(#value), 2)', 'caption=Стойност,chart=ay');
        $this->FLD('value', 'double(decimals=2)', 'column=none');
        $this->FLD('statusText', 'varchar(255)', 'caption=Съобщение');
        $this->FLD('statusAlert', 'int', 'caption=Alert');
        $this->FLD('time', 'datetime', 'caption=Време,chart=ax');
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->FNC('groupBy', 'enum(all=Без осредняване,howr=По часове,day=По дни,dayMax=Макс. дневни,dayMin=Мин. дневни, week=По седмици)', 'caption=Осредняване,input');
        $data->listFilter->showFields = 'sensorId,paramId,groupBy';
        
        $data->listFilter->toolbar->addSbBtn('Филтър');
        
        $data->listFilter->view = 'horizontal';
        
        $url = getCurrentUrl();
        
        unset($url['sensorId'], $url['paramId'], $url['Cmd'], $url['groupBy']);
        
        $data->listFilter->setHidden($url);
        
        $rec = $data->listFilter->input();
        
        $data->query->groupBy('sensorId,paramId');
        
        if($rec->groupBy == 'all' || !$rec->groupBy) {
            $data->query->XPR('timeGroup', 'date', '#time');
        } elseif($rec->groupBy == 'day') {
            $data->query->XPR('timeGroup', 'date', 'DATE(#time)');
        } elseif($rec->groupBy == 'dayMax') {
            $data->query->XPR('timeGroup', 'date', 'DATE(#time)');
            $data->query->setField('valueAvg', array('expression' => 'ROUND(MAX(#value), 2)' ) );
        } elseif($rec->groupBy == 'dayMin') {
            $data->query->XPR('timeGroup', 'date', 'DATE(#time)');
            $data->query->setField('valueAvg', array('expression' => 'ROUND(MIN(#value), 2)' ) );
        } elseif($rec->groupBy == 'howr') {
            $data->query->XPR('timeGroup', 'date', "CONCAT(DATE(#time), ' ', HOUR(#time), ':00')");
        } elseif($rec->groupBy == 'week') {
            $data->query->XPR('timeGroup', 'date', "CONCAT(YEAR(#time), ' (', WEEK(#time, 3), ')')");
        }
        
        $data->query->groupBy('timeGroup');
        
        if($rec) {
            if($rec->sensorId) {
                $data->query->where("#sensorId = {$rec->sensorId}");
            }
            
            if($rec->paramId) {
                $data->query->where("#paramId = {$rec->paramId}");
                $data->listFields['value'] = $mvc->Params->fetchField($rec->paramId, 'param');
            }
        }
    }
    
    
    /**
     * Сортиране DESC - последния запис да е най-отгоре
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        $data->query->orderBy('#time', 'DESC');
        $data->query->orderBy('#id', 'DESC');
    }
    
    
    /**
     * Добавяме % или C в зависимост дали показваме влажност/температура и оцветяваме
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        // Добавяме най в дясно детайлите на параметъра
        $paramRec = $this->Params->fetch($rec->paramId);
        $row->value = "<div style='width: 20px; float: right;'>{$paramRec->details}</div>
                       <div style='float: right;'>{$row->value}</div>";
        
        // Дефинираме различни цветове за различните statusAlert стойности
        $alertColors = array('no' => '#ffffff',
            'low' => '#f8f8ff',
            'moderate' => '#fff0f0',
            'high' => '#ffdddd');
        
        // Променяме $row->statusAlert от int да стане no/low/moderate/high
        switch ($row->statusAlert) {
            case 0:
                $row->statusAlert = "no";
                break;
            case 1:
                $row->statusAlert = "low";
                break;
            case 2:
                $row->statusAlert = "moderate";
                break;
            case 3:
                $row->statusAlert = "high";
                break;
        }
        
        // Променяме цвета на реда в зависимост от стойността на $row->statusAlert
        $rowStyle = " style=\"background-color: ". $alertColors[$row->statusAlert] . ";\"";
        $row->ROW_ATTR .= new ET($rowStyle);
        
        // Ако $row->statusAlert e "no" го правим да е празен
        if ($row->statusAlert == "no") {
            $row->statusAlert = "";
        }
        
        $row->time = dt::mysql2verbal($rec->timeGroup);
    }
    
    
    /**
     * Запис на данни в логовете
     */
    function act_RecSensorsData()
    {
        $this->cron_RecSensorsData();
        
        return $this->act_List();
    }
    
    
    /**
     * Метод за запис на данните от сензорите по cron
     */
    function cron_RecSensorsData()
    {
        // BEGIN Зареждаме цялата таблица за лимитите в $sensorslimits
        $Limits = cls::get('sens_Limits');
        $queryLimits = $Limits->getQuery();
        $queryLimits->where("1=1");
        
        while ($limitsRec = $queryLimits->fetch($where)) {
            $sensorsLimits[$limitsRec->sensorId][$limitsRec->paramId][] = $limitsRec;
        }
        // END Зареждаме цялата таблица за лимитите в $sensorslimits
        
        // Заявка за всички сензори, които са активни от 'sens_Sensors'
        $Sensors = cls::get('sens_Sensors');
        $querySensors = $Sensors->getQuery();
        $querySensors->where("#state='active'");
        $querySensors->show("id, params, monitored, checkPeriod, driver");
        
        // BEGIN Цикъл за всеки активен сензор
        while ($sensorRec = $querySensors->fetch($where)) {
            // Unix timestamp конвертиран в минути
            $currentMins = round((time()/60));
            
            // Резултата от текущите минути по модул от периода на сензора
            $minsDiv = $currentMins % $sensorRec->checkPeriod;
            
            // BEGIN Ако текущите минути по модул от периода на сензора са нула
            if ($minsDiv == 0) {
                // Кои пареметри от сензора ни интересуват
                $monitoredParams = type_Keylist::toArray($sensorRec->monitored);
                
                // Вземаме текущите показания на сензора (всички параметри)
                $driver = cls::get($sensorRec->driver, $sensorRec->params);
                $sensorData = $driver->getData();
                
                // Проверка на получените данни
                expect($sensorData && is_array($sensorData));
                
                // BEGIN Цикъл за всеки един от желаните параметри (мениджър 'sens_Sensors', поле 'monitored')
                foreach ($monitoredParams as $paramId) {
                    // Вземаме за параметъра 'unit' и 'details' от 'sens_Params'
                    $paramUnit = $this->Params->fetchField($paramId, 'unit');
                    $paramDetails = $this->Params->fetchField($paramId, 'details');
                    
                    // Подготвяме записа
                    $rec = new stdClass();
                    $rec->sensorId = $sensorRec->id;
                    $rec->paramId = $paramId;
                    $rec->paramDetails = $paramDetails;
                    $rec->value = $sensorData[$paramUnit];
                    $rec->statusText = "";
                    $rec->statusAlert = "0";
                    $rec->time = dt::verbal2mysql();
                    
                    // Вземаме всички лимити за конкретния параметър на дадения сензор в променливата $sensorParamLimits
                    $sensorParamLimits = $sensorsLimits[$rec->sensorId][$rec->paramId];
                    
                    // Ако за параметъра на сензора има дефинирани лимити
                    if(count($sensorParamLimits)) {
                        // BEGIN Цикъл за всеки лимит на конкретния параметър за дадения сензор
                        foreach ($sensorParamLimits as $sensorParamLimit) {
                            // Ако лимита е за min
                            if ($sensorParamLimit->type == 'min') {
                                if ($rec->value < $sensorParamLimit->value) {
                                    $rec->statusText = $sensorParamLimit->statusText;
                                    $rec->statusAlert = $sensorParamLimit->statusAlert;
                                }
                            }
                            
                            // Ако лимита е за max
                            if ($sensorParamLimit->type == 'max') {
                                if ($rec->value > $sensorParamLimit->value) {
                                    $rec->statusText = $sensorParamLimit->statusText;
                                    $rec->statusAlert = $sensorParamLimit->statusAlert;
                                }
                            }
                        }
                        // END Цикъл за всеки лимит на конкретния параметър за дадения сензор
                    }
                    // END Ако за параметъра на сензора има дефинирани лимити
                    
                    // Записваме в логовете
                    $this->save($rec);
                }
                // END Цикъл за всеки един от желаните параметри (мениджър 'sens_Sensors', поле 'monitored')
            }
            // END Ако текущите минути по модул от периода на сензора са нула
        }
        // END Цикъл за всеки сензор
    }
    
    
    /**
     * Изпълнява се след сетъп на модела
     */
    function on_AfterSetupMVC($mvc, $res)
    {
        
        // Наглася Cron да стартира записването на сензорите
        $Cron = cls::get('core_Cron');
        
        $rec->systemId = "recored_sensors";
        $rec->description = "Запива от сензорите";
        $rec->controller = "sens_SensorLogs";
        $rec->action = "RecSensorsData";
        $rec->period = 1;
        $rec->offset = 0;
        
        if($Cron->addOnce($rec)) {
            $res .= "<li style='color:green;'>Cron записва сензорите.";
        } else {
            $res .= "<li>Cron отпреди е бил нагласен да записва сензорите.";
        }
    }
}