<?php


/**
 *  Колко пъти задачата за производство може да се пуска
 */
defIfNot('PLANNING_TASK_START_COUNTER', 1);


/**
 *  Tемата по-подразбиране за пос терминала
 */
defIfNot('PLANNING_TASK_SERIAL_COUNTER', 1000);


/**
 * Тип на дефолтния брояч за етикета на задачата за производство
 */
defIfNot('PLANNING_TASK_LABEL_COUNTER_SHOWING', 'barcodeAndStr');


/**
 * Клас на дефолтния брояч
*/
defIfNot('PLANNING_TASK_LABEL_COUNTER_BARCODE_TYPE', 'code128');


/**
 * Съотношение на дефолтния броян
*/
defIfNot('PLANNING_TASK_LABEL_RATIO', 4);


/**
 * Широчина на дефолтния
*/
defIfNot('PLANNING_TASK_LABEL_WIDTH', 160);


/**
 * Височина
*/
defIfNot('PLANNING_TASK_LABEL_HEIGHT', 60);


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
    		'PLANNING_TASK_SERIAL_COUNTER'             => array ('int', 'caption=Задачи за производство->Стартов сериен номер'),
    		'PLANNING_TASK_START_COUNTER'              => array('int', 'caption=Задачи за производство->Макс. брой стартирания'),
    		'PLANNING_TASK_LABEL_COUNTER_SHOWING'      => array('enum(barcodeAndStr=Баркод и стринг, string=Стринг, barcode=Баркод)', 'caption=Шаблон за етикети на задачите->Показване'),
    		'PLANNING_TASK_LABEL_COUNTER_BARCODE_TYPE' => array('varchar', 'caption=Шаблон за етикети на задачите->Тип баркод,optionsFunc=barcode_Generator::getAllowedBarcodeTypesArr'),
    		'PLANNING_TASK_LABEL_RATIO'                => array('enum(1=1,2=2,3=3,4=4)', 'caption=Шаблон за етикети на задачите->Съотношение'),
    		'PLANNING_TASK_LABEL_WIDTH'                => array('int(min=1, max=5000)', 'caption=Шаблон за етикети на задачите->Широчина,unit=px'),
    		'PLANNING_TASK_LABEL_HEIGHT'               => array('int(min=1, max=5000)', 'caption=Шаблон за етикети на задачите->Височина,unit=px'),
    		'PLANNING_TASK_LABEL_ROTATION'             => array('enum(yes=Да, no=Не)', 'caption=Шаблон за етикети на задачите->Ротация'),
    		'PLANNING_TASK_LABEL_PREVIEW_WIDTH'        => array('int', 'caption=Превю на артикула в етикета->Широчина,unit=px'),
    		'PLANNING_TASK_LABEL_PREVIEW_HEIGHT'       => array('int', 'caption=Превю на артикула в етикета->Височина,unit=px'),
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
    		'planning_TaskActions',
    		'planning_TaskSerials',
    		'migrate::updateTasks',
    		'migrate::updateNotes',
    		'migrate::updateStoreIds',
    		'migrate::migrateJobs',
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
            array(3.21, 'Производство', 'Планиране', 'planning_Wrapper', 'getStartCtr', "planning, ceo, job, store, taskWorker, taskPlanning"),
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
        $res = bgerp_Menu::remove($this);
        
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
}
