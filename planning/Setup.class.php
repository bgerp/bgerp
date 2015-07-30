<?php


/**
 * Начален номер на фактурите
 */
defIfNot('PLANNING_TASK_DETAIL_CODE_MIN', '0');


/**
 * Начален номер на фактурите
 */
defIfNot('PLANNING_TASK_DETAIL_CODE_MAX', '200000000');


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
            'planning_Tasks',
    		'planning_TaskDetails',
    		'planning_Stages',
    		'planning_ConsumptionNotes',
    		'planning_ConsumptionNoteDetails',
    		'planning_ProductionNotes',
    		'planning_ProductionNoteDetails',
    		'planning_DirectProductionNote',
    		'planning_DirectProductNoteDetails',
    		'planning_ObjectResources',
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
    var $defClasses = "planning_PlanningReportImpl,planning_PurchaseReportImpl,planning_drivers_ProductionTask";
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}
