<?php

/**
 * Мениджър за сензори
 */
class sens_Sensors extends core_Manager
{
    /**
     *  Необходими мениджъри
     */
    var $loadList = 'plg_Created, plg_RowTools, plg_State,
                     Params=sens_Params, sens_Wrapper';
    
    
    /**
     *  Титла
     */
    var $title = 'Сензори';
    
    
    /**
     * Права за писане
     */
    var $canWrite = 'sens, admin';
    
    
    /**
     *  Права за запис
     */
    var $canRead = 'sens, admin';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('title', 'varchar(255)', 'caption=Заглавие, mandatory');
        $this->FLD('params', 'text', 'caption=Инициализация');
        $this->FLD('checkPeriod', 'int', 'caption=период (m)');
        $this->FLD('monitored', 'keylist(mvc=sens_Params,select=param)', 'caption=Параметри');
        $this->FLD('location', 'key(mvc=common_Locations,select=title)', 'caption=Локация');
        $this->FLD('driver', 'class(interface=sens_DriverIntf)', 'caption=Драйвер,mandatory');
        $this->FLD('state', 'enum(active=Активен, closed=Спрян)', 'caption=Статус');
    }
    
    
   /**
     * Преди извличане на записите от БД
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {

    }
   
   
   /**
     * Преди извличане на записите от БД
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    function on_AfterPrepareEditForm($mvc, $rec, $data)
    {
		if (isset($rec->form->rec->id)) { //bp($rec);
		}
    }
    
    
    
    /**
     * Показваме актуални данни за всеки от параметрите
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {   

        /**
         * @todo: Да се махне долния пасаж, когато се направи де-иснталиране
         */
        if(!cls::getClassName($rec->driver, FALSE)) {
            return;
        }

        $driver = cls::getInterface('sens_DriverIntf', $rec->driver, $rec->params);
       
        $sensorData = array();
        
        if ($rec->state == 'active') {
            $sensorData = $driver->getData();
        }
        
        // Проверка на получените данни
        expect(is_array($sensorData));
        
        $monitoredParams = type_Keylist::toArray($rec->monitored);
        
        $newMonitoredData = "";
        
        // По $k (равно на полето 'unit' от 'sens/Params') намираме $paramId и ако $paramId
        // е сред елементите на масива $monitoredParams, записваме данните  
        foreach ($sensorData as $k => $v) {
            $paramId = $mvc->Params->fetchField("#unit='{$k}'", 'id');
            $param = $mvc->Params->fetchField("#unit='{$k}'", 'param');
            $details = $mvc->Params->fetchField("#unit='{$k}'", 'details');
            
            if (in_array($paramId, $monitoredParams)) {
                $newMonitoredData .= $param . " " . $v . " " . $details . "<br/>";
            }
        }
        
        // Отрязваме последния '<br/>'
        if (substr($newMonitoredData, strlen($newMonitoredData) - 6, 5) == '<br/>') {
            $newMonitoredData = substr($newMonitoredData, 0, strlen($newMonitoredData) - 6);
        }
        
        $row->monitored = $newMonitoredData;
    }
}