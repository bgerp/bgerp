<?php


/**
 * Производствено планиране - инсталиране / деинсталиране
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
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
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
    		'planning_Jobs',
    		'planning_Stages',
    		'planning_ConsumptionNotes',
    		'planning_ConsumptionNoteDetails',
    		'planning_ProductionNotes',
    		'planning_ProductionNoteDetails',
    		'planning_DirectProductionNote',
    		'planning_DirectProductNoteDetails',
    		'planning_ObjectResources',
    		'planning_Tasks',
    		'planning_HumanResources',
    		'planning_AssetResources',
    		'planning_drivers_ProductionTaskDetails',
    		'migrate::updateTasks',
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'planning';

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.21, 'Производство', 'Планиране', 'planning_Jobs', 'default', "planning, ceo"),
        );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    var $defClasses = "planning_reports_PlanningImpl,planning_reports_PurchaseImpl,planning_drivers_ProductionTask";
    
    
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
    	
    	$cRec = core_Classes::fetch("#name = 'tasks_Tasks'");
    	$cRec->state = 'closed';
    	core_Classes::save($cRec);
    }
}
