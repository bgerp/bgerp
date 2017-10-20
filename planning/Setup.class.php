<?php


/**
 *  Колко пъти задачата за производство може да се пуска
 */
defIfNot('PLANNING_TASK_START_COUNTER', 1);


/**
 *  Стартов сериен номер при производствените операции
 */
defIfNot('PLANNING_TASK_SERIAL_COUNTER', 1000);


/**
 * Ротация на баркода
*/
defIfNot('PLANNING_TASK_LABEL_ROTATION', 'yes');


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
    		'PLANNING_TASK_START_COUNTER'          => array('int', 'caption=Производствени операции->Макс. брой стартирания'),
    		'PLANNING_TASK_LABEL_PREVIEW_WIDTH'    => array('int', 'caption=Превю на артикула в етикета->Широчина,unit=px'),
    		'PLANNING_TASK_LABEL_PREVIEW_HEIGHT'   => array('int', 'caption=Превю на артикула в етикета->Височина,unit=px'),
    		'PLANNING_CONSUMPTION_USE_AS_RESOURCE' => array('enum(yes=Да,no=Не)', 'caption=Детайлно влагане по подразбиране->Избор'),
    		'PLANNING_PRODUCTION_NOTE_REJECTION'   => array('enum(no=Забранено,yes=Позволено)', 'caption=Оттегляне на стари протоколи за производство ако има нови->Избор'),
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
    		//'planning_Tasks',
    		'planning_AssetResources',
    		//'planning_drivers_ProductionTaskDetails',
    		//'planning_drivers_ProductionTaskProducts',
    		//'planning_TaskActions',
    		//'planning_TaskSerials',
    		'migrate::updateNotes',
    		'migrate::updateStoreIds',
    		'migrate::migrateJobs',
    		'migrate::addPackToNotes',
    		'migrate::addPackToJobs',
    		'migrate::deleteTaskCronUpdate',
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = array(
    		array('production'),
    		array('taskWorker'),
    		array('taskPlanning', 'taskWorker'),
    		array('planning', 'taskPlanning'),
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
    var $defClasses = "planning_reports_PlanningImpl,planning_reports_PurchaseImpl, planning_reports_MaterialsImpl";
    
    
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
     * Миграция на департаментите
     */
    function migrateJobs()
    {
    	$Jobs = cls::get('planning_Jobs');
    	$Jobs->setupMvc();
    	
    	if(!planning_Jobs::count()) return;
    	
    	$emptyId = hr_Departments::fetch("#systemId = 'emptyCenter'")->id;
    	if(!$emptyId) return;
    	
    	$defFolderId = hr_Departments::forceCoverAndFolder($emptyId);
    	
    	$query = $Jobs->getQuery();
    	$query->FLD('departments', 'keylist(mvc=hr_Departments,select=name,makeLinks)');
    	
    	while($rec = $query->fetch()){
    		
    		try{
    			$newFolderId = $defFolderId;
    			
    			if(isset($rec->departments)){
    				$departments = keylist::toArray($rec->departments);
    				if(count($departments)){
    					$departmentId = key($departments);
    					if($departmentId){
    						$rec->department = $departmentId;
    						$newFolderId = hr_Departments::forceCoverAndFolder($departmentId);
    							
    						$Jobs->save_($rec, 'department');
    					}
    				}
    			}
    			
    			doc_Threads::move($rec->threadId, $newFolderId);
    			doc_ThreadUsers::addShared($rec->threadId, $rec->containerId, $rec->createdBy);
    		} catch (core_exception_Expect $e){
    			reportException($e);
    		}
    	}
    }
    
    
    /**
     * Добавя опаковки на протокола за производство
     */
    public function addPackToNotes()
    {
    	$Notes = cls::get('planning_DirectProductionNote');
    	$Notes->setupMvc();
    	
    	if(!$Notes->count()) return;
    	core_App::setTimeLimit(300);
    	
    	$toSave = array();
    	$query = planning_DirectProductionNote::getQuery();
    	$query->where("#packagingId IS NULL");
    	
    	while($rec = $query->fetch()){
    		try{
    			$rec->packagingId = cat_Products::fetchField($rec->productId, 'measureId');
    			$rec->quantityInPack = 1;
    			$toSave[] = $rec;
    		} catch(core_exception_Expect $e){
    			reportException($e);
    		}
    	}
    	
    	if(count($toSave)){
    		$Notes->saveArray($toSave, 'id,packagingId,quantityInPack');
    	}
    }
    
    
    /**
     * Добавя опаковки на протокола за производство
     */
    public function addPackToJobs()
    {
    	$Job = cls::get('planning_Jobs');
    	$Job->setupMvc();
    	 
    	if(!$Job->count()) return;
    	core_App::setTimeLimit(300);
    	 
    	$toSave = array();
    	$query = planning_Jobs::getQuery();
    	$query->where("#packagingId IS NULL");
    	 
    	while($rec = $query->fetch()){
    		try{
    			$rec->packagingId = cat_Products::fetchField($rec->productId, 'measureId');
    			$rec->quantityInPack = 1;
    			$toSave[] = $rec;
    		} catch(core_exception_Expect $e){
    			reportException($e);
    		}
    	}
    	 
    	if(count($toSave)){
    		$Job->saveArray($toSave, 'id,packagingId,quantityInPack');
    	}
    }
    
    
    /**
     * Изтриване на крон метод
     */
    public function deleteTaskCronUpdate()
    {
    	core_Cron::delete("#systemId = 'Update Tasks States'");
    }
}
