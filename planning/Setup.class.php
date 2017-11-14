<?php


/**
 *  Стартов сериен номер при производствените операции
 */
defIfNot('PLANNING_TASK_SERIAL_COUNTER', 1000);


/**
 * Широчина на превюто на артикула в етикета
 */
defIfNot('PLANNING_TASK_LABEL_PREVIEW_WIDTH', 90);


/**
 * Височина на превюто на артикула в етикета
 */
defIfNot('PLANNING_TASK_LABEL_PREVIEW_HEIGHT', 170);


/**
 * Детайлно влагане по подразбиране
 */
defIfNot('PLANNING_CONSUMPTION_USE_AS_RESOURCE', 'yes');


/**
 * Може ли да се оттеглят старите протоколи за производство, ако има нови
 */
defIfNot('PLANNING_PRODUCTION_NOTE_REJECTION', 'no');


/**
 * До колко символа да е серийния номер на произвеодствените операции
 */
defIfNot('PLANNING_SERIAL_STRING_PAD', '10');


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
    var $startCtr = 'planning_Setup';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'getStartCtr';
    
    
    /**
     * Описание на модула
     */
    var $info = "Производствено планиране";
    
    
    /**
     * Описание на конфигурационните константи за този модул
     */
    var $configDescription = array(
    		'PLANNING_TASK_SERIAL_COUNTER'         => array('int', 'caption=Производствени операции->Стартов сериен номер'),
    		'PLANNING_SERIAL_STRING_PAD'           => array('int', 'caption=Производствени операции->Макс. дължина'),
    		'PLANNING_TASK_LABEL_PREVIEW_WIDTH'    => array('int', 'caption=Превю на артикула в етикета->Широчина,unit=px'),
    		'PLANNING_TASK_LABEL_PREVIEW_HEIGHT'   => array('int', 'caption=Превю на артикула в етикета->Височина,unit=px'),
    		'PLANNING_CONSUMPTION_USE_AS_RESOURCE' => array('enum(yes=Да,no=Не)', 'caption=Детайлно влагане по подразбиране->Избор'),
    		'PLANNING_PRODUCTION_NOTE_REJECTION'   => array('enum(no=Забранено,yes=Позволено)', 'caption=Оттегляне на стари протоколи за производство ако има нови->Избор'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
    		'migrate::deleteTasks6',
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
    		'planning_ProductionTaskDetails',
    		'planning_ProductionTaskProducts',
    		'planning_TaskSerials',
    		'planning_AssetGroups',
    		'planning_AssetResourcesNorms',
    		'migrate::deleteTaskCronUpdate',
    		'migrate::deleteAssets'
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = array(
    		array('production'),
    		array('taskWorker'),
    		array('taskPlanning', 'taskWorker'),
    		array('planning', 'taskPlanning'),
    		array('planningMaster', 'planning'),
    		array('job'),
    );

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.21, 'Производство', 'Планиране', 'planning_Wrapper', 'getStartCtr', "planning, ceo, job, store, taskWorker, taskPlanning"),
        );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    var $defClasses = "planning_reports_PlanningImpl,planning_reports_PurchaseImpl, planning_reports_MaterialsImpl,planning_interface_ImportTaskProducts,planning_interface_ImportTaskSerial,planning_interface_ImportFromLastBom";
    
    
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
     * Изтрива старите производствени операции
     */
    public static function deleteTasks6()
    {
    	$Details = cls::get('planning_ProductionTaskDetails');
    	$Details->fillSearchKeywordsOnSetup = FALSE;
    	$Details->setupMvc();
    	$Details->truncate();
    	
    	$Tasks = cls::get('planning_Tasks');
    	$Tasks->fillSearchKeywordsOnSetup = FALSE;
    	$Tasks->setupMvc();
    	if(!$Tasks->count()) return;
    	
    	$Product = cls::get('planning_ProductionTaskProducts');
    	$Product->fillSearchKeywordsOnSetup = FALSE;
    	$Product->setupMvc();
    	$Product->truncate();
    	
    	$Tasks->truncate();
    	$taskClassId = planning_Tasks::getClassId();
    	$query = doc_Containers::getQuery();
    	$query->where("#docClass = {$taskClassId}");
    	$query->delete();
    	
    	$Serials = cls::get('planning_TaskSerials');
    	$Serials->fillSearchKeywordsOnSetup = FALSE;
    	$Serials->setupMvc();
    	$Serials->truncate();
    	
    	$Assets = cls::get('planning_AssetResources');
    	$Assets->fillSearchKeywordsOnSetup = FALSE;
    	$Assets->setupMvc();
    	$Assets->truncate();
    }
    
    
    /**
     * Изтриване на крон метод
     */
    public function deleteTaskCronUpdate()
    {
    	core_Cron::delete("#systemId = 'Update Tasks States'");
    }
    
    
    /**
     * Изтриване на стари задачи от операциите
     */
    public function deleteAssets()
    {
    	$query = planning_Tasks::getQuery();
    	$query->where("#fixedAssets IS NOT NULL");
    	while($tRec = $query->fetch()){
    		$tRec->fixedAssets = NULL;
    		planning_Tasks::save($tRec);
    	}
    	
    	$query = planning_ProductionTaskDetails::getQuery();
    	$query->where("#fixedAsset IS NOT NULL");
    	while($tRec1 = $query->fetch()){
    		$tRec1->fixedAsset = NULL;
    		planning_ProductionTaskDetails::save($tRec1);
    	}
    }
}
