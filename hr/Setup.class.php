<?php



/**
 * class dma_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с DMA
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hr_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'hr_EmployeeContracts';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Човешки ресурси";
    

    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = 'hr_WorkingCycles, hr_WorkingCycleDetails, hr_Shifts, hr_Departments, hr_Positions, hr_ContractTypes, hr_EmployeeContracts';
    
    
    /**
     * Роли за достъп до модула
     */
    var $roles = 'hr';


    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
         array(2.31, 'Персонал', 'HR', 'hr_EmployeeContracts', 'default', "hr, admin"),
        );

}