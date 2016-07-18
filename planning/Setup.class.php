<?php

/**
 *  Tемата по-подразбиране за пос терминала
 */
defIfNot('PLANNING_TASK_SERIAL_COUNTER', 1000);


/**
 * Производствено планиране - инсталиране / деинсталиране
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'cat=0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'planning_Jobs';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Производствено планиране";
    
    
    /**
     * Описание на конфигурационните константи за този модул
     */
    var $configDescription = array(
    		'PLANNING_TASK_SERIAL_COUNTER'   => array ('int', 'caption=Задачи->Стартов сериен номер'),
    		);
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
    		'planning_Jobs',
    		'planning_ConsumptionNotes',
    		'planning_ConsumptionNoteDetails',
    		'planning_DirectProductionNote',
    		'planning_DirectProductNoteDetails',
    		'planning_ReturnNotes',
    		'planning_ReturnNoteDetails',
    		'planning_ObjectResources',
    		'planning_Tasks',
    		'planning_AssetResources',
    		'planning_drivers_ProductionTaskDetails',
    		'planning_drivers_ProductionTaskProducts',
    		'planning_drivers_ProductionTaskParameters',
    		'planning_TaskActions',
    		'planning_TaskSerials',
    		'migrate::updateTasks',
    		'migrate::updateNotes',
    		'migrate::updateStoreIds',
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = array(
    		array('taskWorker'),
    		array('taskPlanning', 'taskWorker'),
    		array('planning', 'taskPlanning'),
    		array('job'),
    );

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.21, 'Производство', 'Планиране', 'planning_Jobs', 'default', "planning, ceo, job"),
        );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    var $defClasses = "planning_reports_PlanningImpl,planning_reports_PurchaseImpl,planning_drivers_ProductionTask, planning_reports_MaterialsImpl";
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
    
    
    /**
     * Миграция на старите задачи
     */
    function updateTasks()
    {
    	core_Classes::add('planning_Tasks');
    	$PlanningTasks = planning_Tasks::getClassId();
    	 
    	if(!tasks_Tasks::count()) return;
    	
    	$tQuery = tasks_Tasks::getQuery();
    	$tQuery->where('#classId IS NULL || #classId = 0');
    	while($tRec = $tQuery->fetch()){
    		if(cls::get('tasks_Tasks')->getDriver($tRec->id)){
    			$tRec->classId = $PlanningTasks;
    			tasks_Tasks::save($tRec);
    		}
    	}
    	
    	if($cRec = core_Classes::fetch("#name = 'tasks_Tasks'")){
    		$cRec->state = 'closed';
    		core_Classes::save($cRec);
    	}
    }
    
    
    /**
     * Миграция на старите задачи
     */
    function updateNotes()
    {
    	if(!planning_DirectProductionNote::count()) return;
    	
    	$query = planning_DirectProductionNote::getQuery();
    	$query->where('#inputStoreId IS NULL');
    	
    	while($rec = $query->fetch()){
    		$rec->inputStoreId = $rec->storeId;
    		cls::get('planning_DirectProductionNote')->save_($rec);
    	}
    }
    
    
    /**
     * Миграция на протоколите за производство
     */
    function updateStoreIds()
    {
    	core_App::setTimeLimit(300);
    	$Details = cls::get('planning_DirectProductNoteDetails');
    	$Details->setupMvc();
    	
    	$query = planning_DirectProductNoteDetails::getQuery();
    	$query->EXT('inputStoreId', 'planning_DirectProductionNote', 'externalName=inputStoreId,externalKey=noteId');
    	$query->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
    	$query->where("#inputStoreId IS NOT NULL");
    	$query->where("#storeId IS NULL");
    	$query->where("#canStore = 'yes'");
    	
    	while($dRec = $query->fetch()){
    		$dRec->storeId = $dRec->inputStoreId;
    		try{
    			$Details->save_($dRec, 'storeId');
    		} catch(core_exception_Expect $e){
    			reportException($e);
    		}
    	}
    }
    
    
    /**
     * Ъпдейт на информацията за параметрите за влагане
     */
    function updateTaskInfo()
    {
    	core_App::setTimeLimit(300);
    	$Tp = cls::get('planning_drivers_ProductionTaskParameters');
    	$Tp->setupMvc();
    	
    	try{
    		$classId = planning_drivers_ProductionTask::getClassId();
    		$tQuery = planning_Tasks::getQuery();
    		$tQuery->where("#driverClass = {$classId}");
    		while($tRec = $tQuery->fetch()){
    			planning_drivers_ProductionTaskParameters::saveProductParams($tRec->id, $tRec->productId);
    		}
    	} catch(core_exception_Expect $e){
    		reportException($e);
    	}
    }
}
