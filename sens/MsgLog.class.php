<?php

/**
 * Мениджър за съобщенията на сензорите
 */
class sens_MsgLog extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, plg_Sorting,sens_Wrapper,
                      plg_RefreshRows';
    
    /**
     *  Заглавие
     */
    var $title = 'Съобщения от сензорите';
    
    
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
        $this->FLD('message', 'varchar(255)', 'caption=Съобщение');
        $this->FLD('priority', 'enum(normal=Информация,warning=Предупреждение,alert=Аларма)', 'caption=Важност');
        $this->FLD('time', 'datetime', 'caption=Време');
    }
    
    
    /**
     * 
     * Добавя запис в логовете
     */
    function add($sensorId, $message, $priority)
    {
    	$rec = new stdClass();
    	$rec->sensorId = $sensorId;
    	$rec->message = $message;
    	$rec->priority = $priority;
    	$rec->time = dt::verbal2mysql();
    	
    	sens_MsgLog::save($rec);
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
     * Оцветяваме записите в зависимост от приоритета събитие
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $msgColors = array(	'normal' => '#ffffff',
            				'warning' => '#fff0f0',
            				'alert' => '#ffdddd'
        			);
       // Променяме цвета на реда в зависимост от стойността на $row->statusAlert
        $row->ROW_ATTR['style'] .= "background-color: ". $msgColors[$rec->priority] . ";";
        			
 //   	bp($row);
//    	return TRUE;
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {	
    	return TRUE;
    }
}