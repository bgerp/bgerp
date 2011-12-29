<?php
/**
 * Клас 'doc_Tasks' - Документ - задача
 */
class doc_Tasks extends core_Master
{   
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf';	
	
    var $loadList = 'plg_Created, plg_RowTools, doc_Wrapper, doc_DocumentPlg, plg_Printing';

    var $title    = "Задачи";

    var $listFields = 'id, title, timeStart=Начало, responsables';
    

    /**
     * Поле в което да се показва иконата за единичен изглед
     */
    var $rowToolsSingleField = 'title';

    /**
     * Права
     */
    var $canRead = 'admin,doc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin,doc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin,doc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canView = 'admin,doc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin,doc';    
    

    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/sheduled-task-icon.png';

    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'doc/tpl/SingleLayoutTasks.html';    

    
    /**
     * Абривиатура
     */
    var $abbr = "TSK";

     
    function description()
    {    	
    	$this->FLD('title',        'varchar(128)', 'caption=Заглавие,mandatory,width=100%');
        $this->FLD('priority',     'enum(low=нисък,
                                         normal=нормален,
                                         high=висок,
                                         critical=критичен)', 'caption=Приоритет,mandatory,maxRadio=4,columns=4');  
    	$this->FLD('details',      'richtext',    'caption=Описание,mandatory');
    	$this->FLD('responsables', 'keylist(mvc=core_Users,select=names)', 'caption=Отговорници,mandatory');
                                         

    	$this->FLD('timeStart',    'datetime',    'caption=Времена->Начало,mandatory');
    	$this->FLD('timeDuration', 'varchar(64)', 'caption=Времена->Продължителност');
    	$this->FLD('timeEnd',      'datetime',    'caption=Времена->Край');
    	$this->FLD('repeat',       'enum(none=няма,
    	                                 everyDay=всеки ден,
    	                                 everyTwoDays=на всеки 2 дена,
    	                                 everyThreeDays=на всеки 3 дена,
    	                                 everyWeek=всяка седмица,
    	                                 everyMonthy=всеки месец,
    	                                 everyThreeMonths=на всеки 3 месеца,
    	                                 everySixMonths=на всяко полугодие,
    	                                 everyYear=всяка година)', 'caption=Времена->Повторение,mandatory');
        $this->FLD('notification', 'enum(NULL=няма,
                                         0=на момента,
                                         -5=5 мин. предварително,
                                         -10=10 мин. предварително,
                                         -30=30 мин. предварително,
                                         -60=1 час предварително,
                                         -120=2 час предварително,
                                         -480=8 часа предварително,
                                         -1440=1 ден предварително,
                                         -2880=2 дни предварително,
                                         -4320=3 дни предварително,
                                         -10080=7 дни предварително)', 'caption=Времена->Напомняне,mandatory');

    }


	/**
     * Интерфейсен метод на doc_DocumentIntf
     */
	function getDocumentRow($id)
	{
		$rec = $this->fetch($id);
		
 
        //Заглавие
        $row->title = $this->getVerbal($rec, 'title');
		
        //Създателя
		$row->author =  $this->getVerbal($rec, 'createdBy');
		
		//Състояние
        $row->state  = $rec->state;
		
        //id на създателя
        $row->authorId = $rec->createdBy;
        
		return $row;
	}
	
	
    /**
     * При нов запис state е draft 
     *
     * @param core_Mvc $mvc
     * @param int $id
     * @param stdClass $rec
     */
    function on_BeforeSave($mvc,&$id,$rec)
    {
        if (!isset($rec->id)) {
            $rec->state = 'draft';
        }
    }

    
    /**
     * Сменя state в doc_Tasks 30 мин. след като е създадена задачата
     */
    function act_SetTasksActive()
    {
    	$queryTasks = doc_Tasks::getQuery();
    	$where = "#state = 'draft'";
    	
        while($recTasks = $queryTasks->fetch($where)) {
            $createdOn = dt::mysql2timestamp($recTasks->createdOn);
            $now = time();
            $delayMins = ($now - $createdOn) / 60;

            if ($delayMins > 30) {
                $recTasks->state = 'active';
                doc_Tasks::save($recTasks);    
            }
        }
    }    

}