<?php

/**
 * Начален номер на фактурите
 */
defIfNot('HR_EC_MIN', '1');


/**
 * Краен номер на фактурите
*/
defIfNot('HR_EC_MAX', '10000');



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
    
    // Обновяване на индикаторите на всеки час
    const INDICATORS_UPDATE_PERIOD = 60;

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
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
    
    		'HR_EC_MIN'        => array('int(min=0)', 'caption=Диапазон за номериране на трудовите договори->Долна граница'),
    		'HR_EC_MAX'        => array('int(min=0)', 'caption=Диапазон за номериране на трудовите договори->Горна граница'),
    
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
   var $managers = array(
   		    'hr_Departments',
            'hr_CustomSchedules',
            'hr_WorkingCycles',
            'hr_WorkingCycleDetails',
            'hr_Shifts',
            'hr_ShiftDetails',
			'hr_Positions',
            'hr_ContractTypes',
            'hr_EmployeeContracts',
            'hr_Indicators',
            'hr_Payroll',
            'hr_Leaves',
            'hr_Sickdays',
            'hr_Trips',
            'hr_Bonuses',
            'hr_Deductions',
   			'migrate::forceDepartmentFolders'
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'hr';
    
    
    /**
     * Настройки за Cron
     */
    var $cronSettings = array(
        array(
            'systemId' => "Update indicators",
            'description' => "Обновяване на индикаторите за заплатите",
            'controller' => "hr_Indicators",
            'action' => "update",
            'period' => self::INDICATORS_UPDATE_PERIOD,
            'offset' => 7,
            'timeLimit' => 200
        ));

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(2.31, 'Счетоводство', 'Персонал', 'hr_Indicators', 'default', "ceo, hr, admin"),
        );

    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    var $defClasses = "hr_reports_LeaveDaysPersons";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {  
    	$html = parent::install(); 
    	 
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('humanResources', 'Прикачени файлове в човешки ресурси', NULL, '1GB', 'user', 'powerUser');
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
    
    
    /**
     * Форсиране на папките на департаментите
     */
    function forceDepartmentFolders()
    {
    	$Departments = cls::get('hr_Departments');
    	$Departments->setupMvc();
    	
    	$query = hr_Departments::getQuery();
    	while($dRec = $query->fetch()){
    		hr_Departments::forceCoverAndFolder($dRec->id);
    	}
    }


    /**
     * Миграция "длъжности"
     */
    function setPositionName()
    {
        $mvc = cls::get('hr_Positions');
     	if($mvc->fetch('1=1')) {
            if($mvc->db->tableExists('hr_Professions') && 
               cls::load('hr_Professions', TRUE) && 
               $mvc->db->isFieldExists($mvc->dbTableName, 'profession_id') &&
               $mvc->db->isFieldExists($mvc->dbTableName, 'department_id')) {
    	        
                $query = hr_Positions::getQuery();
                $query->FLD('professionId', 'int');
                $query->FLD('departmentId', 'int');
                
                while($rec = $query->fetch()) { 
                    $profRec = hr_Professions::fetch($rec->professionId);
                    $depRec  = hr_Departments::fetch($rec->departmentId);
                    
                    if(!$rec->name) {
                        $rec->name = $profRec->name . '/' . $depRec->name;

                        if($mvc->fetch("#name = '{$rec->name}'")) {
                            $rec->name .= ' ' . $rec->id;
                        }

                        $rec->nkpd = $profRec->nkpd;

                        $mvc->save($rec);
                    }
                }

            }
        }

    }
  
}