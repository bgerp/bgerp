<?php

/**
 * Мениджър за логовете на сензорите
 */
class sens_IndicationsLog extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, sens_Wrapper, plg_Sorting,
                      plg_RefreshRows';
    
    // plg_Chart
    
    /**
     *  Заглавие
     */
    var $title = 'Записи от сензорите';
    
    
    /**
     *  На колко време ще се ъпдейтва листа
     */
    var $refreshRowsTime = 15000;
    
    
    /**
     * Права за запис
     */
    var $canWrite = 'sens, admin';
    
    
    /**
     *  Права за четене
     */
    var $canRead = 'sens, admin';
    
    
    /**
     *  Брой записи на страница
     */
    var $listItemsPerPage = 100;
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('sensorId', 'key(mvc=sens_Sensors, select=title, allowEmpty)', 'caption=Сензор');
        $this->FLD('paramId', 'key(mvc=sens_Params, select=param)', 'caption=Параметър');
        $this->FLD('value', 'double(decimals=2)', 'caption=Показания');
        $this->FLD('measure', 'varchar(8)', 'caption=Мярка');
        $this->FLD('time', 'datetime', 'caption=Време,chart=ax');
    }
    
    
    /**
     * 
     * Добавя запис в логовете
     */
    function add($sensorId, $param, $value, $measure)
    {
    	$rec = new stdClass();
    	$rec->sensorId = $sensorId;
    	$rec->paramId = sens_Params::getIdByUnit($param);
    	$rec->value = $value;
    	$rec->measure = $measure;
    	$rec->time = dt::verbal2mysql();
    	
    	sens_IndicationsLog::save($rec);
    }
    
    /**
     *  @todo Чака за документация...
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {	
    	return TRUE;
    	
/*        $data->listFilter->FNC('groupBy', 'enum(all=Без осредняване,howr=По часове,day=По дни,dayMax=Макс. дневни,dayMin=Мин. дневни, week=По седмици)', 'caption=Осредняване,input');
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
*/
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
    	return TRUE;
    	
/*        // Добавяме най в дясно детайлите на параметъра
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
*/
    }
    
    
    /**
     * Изпълнява се след сетъп на модела
     */
    function on_AfterSetupMVC($mvc, $res)
    {
		       
    }
}