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
    		'planning_Centers',
    		'planning_Hr',
    		'planning_FoldersWithResources',
    		'migrate::deleteTaskCronUpdate',
    		'migrate::deleteAssets',
    		'migrate::deleteNorms',
    		'migrate::transferCenters',
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
    		array('jobMaster', 'job'),
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
    
    
    /**
     * Изчистване на нормите
     */
    public function deleteNorms()
    {
    	$Norms = cls::get('planning_AssetResourcesNorms');
    	$Norms->setupMvc();
    	$Norms->truncate();
    }
    
    
    public function transferCenters()
    {
    	$Deparments = cls::get('hr_Departments');
    	$Deparments->setupMvc();
    	$Unsorted = cls::get('doc_UnsortedFolders');
    	
    	core_Classes::add('planning_Centers');
    	$Centers = cls::get('planning_Centers');
    	$Centers->setupMvc();
    	$Centers->loadSetupData();
    	$centerClassId = planning_Centers::getClassId();
    	$unsortedClassId = $Unsorted->getClassId();
    	
    	if(!$Deparments->count()) return;
    	
    	$Lists = cls::get('acc_Lists');
    	$Lists->setupMvc();
    	$Lists->loadSetupData();
    	
    	$Cust = cls::get('hr_CustomSchedules');
    	$Cust->setupMvc();
    	 
    	$Econtr = cls::get('hr_EmployeeContracts');
    	$Econtr->setupMvc();
    	 
    	$Jobs = cls::get('planning_Jobs');
    	$Jobs->setupMvc();
    	 
    	$Cons = cls::get('planning_ConsumptionNotes');
    	$Cons->setupMvc();
    	 
    	$Ret = cls::get('planning_ReturnNotes');
    	$Ret->setupMvc();
    	 
    	$Assets = cls::get('planning_AssetResources');
    	$Assets->setupMvc();
    	 
    	$Hr = cls::get('planning_Hr');
    	$Hr->setupMvc();
    	
    	$now = dt::now();
    	
    	$toTransfer = $toUnsorted = array();
    	$dQuery = hr_Departments::getQuery();
    	$dQuery->FLD('folderId', 'key(mvc=doc_Folders)');
    	$dQuery->FLD('type', 'enum(section,branch,office,affiliate,division,direction,department,plant,workshop,store,shop,unit,brigade,shift,organization)');
    	$dQuery->FLD('nkid', 'key(mvc=bglocal_NKID, select=title,allowEmpty=true)');
    	$dQuery->FLD('employmentTotal', 'int');
    	$dQuery->FLD('employmentOccupied', 'int');
    	$dQuery->FLD('startingOn', 'datetime');
    	$dQuery->FLD('schedule', 'key(mvc=hr_WorkingCycles, select=name, allowEmpty=true)');
    	$dQuery->FLD('inCharge' , 'user(role=powerUser, rolesForAll=executive)');
    	$dQuery->FLD('access', 'enum(team=Екипен,private=Личен,public=Общ,secret=Секретен)');
    	$dQuery->FLD('shared' , 'userList');
    	$dQuery->where("#folderId IS NOT NULL");
    	
    	while($dRec = $dQuery->fetch()){
    		if($dRec->type == 'workshop' || acc_Items::isItemInList('hr_Departments', $dRec->id, 'departments') || hr_EmployeeContracts::fetchField("#departmentId = {$dRec->id}") || planning_ConsumptionNotes::fetchField("#departmentId = {$dRec->id}") || planning_ReturnNotes::fetchField("#departmentId = {$dRec->id}")){
    			
    			$obj = (object)arr::getSubArray((object)$dRec, 'name,type,nkid,employmentTotal,schedule,folderId,startingOn,createdBy,inCharge,access,shared,state');
    			$obj->departmentId = $dRec->parentId;
    			if($cRec = $Centers->fetch("#name = '{$dRec->name}'")){
    				$obj->type = 'workshop';
    				$obj->id = $cRec->id;
    			}
    			
    			$toTransfer[$dRec->id] = $obj;
    		} elseif($dRec->folderId) {
    			$threadsCount = doc_Folders::fetchField($dRec->folderId, 'allThreadsCnt');
    			if($threadsCount){
    				$obj = (object)arr::getSubArray((object)$dRec, 'name,folderId,createdBy,inCharge,access,shared,state');
    				$obj->description = 'Мигриран от департамент';
    				$toUnsorted[$dRec->id] = $obj;
    			} else {
    				doc_Folders::delete($dRec->folderId);
    			}
    		}
    	}
    	
    	if(!count($toTransfer) && !count($toUnsorted)) return;
    	$map = array();
    	$deleted = array();
    	
    	foreach ($toTransfer as $objectId => $obj)
    	{
    		if(empty($obj->id)){
    			while($Centers->fetchField("#name = '{$obj->name}'")){
    				$obj->name .= " (1)";
    				if(!$Centers->fetchField("#name = '{$obj->name}'")){
    					break;
    				}
    			}
    		}
    		
    		$obj->createdBy = empty($obj->createdBy) ? core_Users::SYSTEM_USER : $obj->createdBy;
    		$obj->createdOn = $now;
    		core_Users::sudo($obj->createdBy);
    		
    		$id = $Centers->save_($obj);
    		core_Users::exitSudo($obj->createdBy);
    		
    		if($id){
    			$map[$objectId] = $id;
    			
    			if($itemRec = acc_Items::fetchItem('hr_Departments', $objectId)){
    				$itemRec->classId = $centerClassId;
    				$itemRec->objectId = $id;
    				acc_Items::save($itemRec);
    				
    				$register = core_Cls::getInterface('planning_ActivityCenterIntf', $centerClassId);
    				acc_Items::syncItemRec($itemRec, $register, $id);
    				acc_Items::save($itemRec);
    			}
    			
    			if(isset($obj->folderId)){
    				$folderRec = doc_Folders::fetch($obj->folderId);
    				
    				if($folderRec->coverClass != $centerClassId){
    					$folderRec->coverClass = $centerClassId;
    					$folderRec->coverId = $id;
    					$folderRec->title = $obj->name;
    					doc_Folders::save($folderRec, NULL, 'REPLACE');
    				}
    			}
    			
    			if(isset($obj->departmentId) || $obj->name == 'Неопределен'){
    				$deleted[$objectId] = hr_Departments::fetch($objectId);
    				hr_Departments::delete($objectId);
    			}
    		}
    	}
    	
    	// Оправяне и на изтритите департаменти
    	foreach ($deleted as $dId => $delRec){
    		$q = $Centers->getQuery();
    		$q->where("#departmentId = {$dId}");
    		while($c1 = $q->fetch()){
    			$c1->departmentId = $delRec->parentId;
    			$Centers->save($c1);
    		}
    	}
    	
    	foreach ($toUnsorted as $objId => $uRec){
    		if(empty($uRec->id)){
    			while($Unsorted->fetchField("#name = '{$uRec->name}'")){
    				$uRec->name .= " (1)";
    				if(!$Unsorted->fetchField("#name = '{$uRec->name}'")){
    					break;
    				}
    			}
    		}
    		
    		core_Users::sudo($uRec->createdBy);
    		if($cId = $Unsorted->fetchField("#name = '{$uRec->name}'")){
    			$uRec->id = $cId;
    		}
    		$uRec->createdOn = $now;
    		$uId = $Unsorted->save_($uRec);
    		core_Users::exitSudo($uRec->createdBy);
    		
    		if($uId){
    			$folderRec = doc_Folders::fetch($uRec->folderId);
    			if($folderRec->coverClass != $unsortedClassId){
    				$folderRec->coverClass = $unsortedClassId;
    				$folderRec->coverId = $uId;
    				$folderRec->title = $uRec->name;
    				doc_Folders::save($folderRec, NULL, 'REPLACE');
    			}
    		}
    	}
    	
    	$jQuery = $Jobs->getQuery();
    	$jQuery->where("department IS NOT NULL");
    	$jQuery->show('department');
    	while($jRec = $jQuery->fetch()){
    		$jRec->department = $map[$jRec->department];
    		$Jobs->save_($jRec, 'department');
    	}
    	
    	$aQuery = $Assets->getQuery();
    	$aQuery->where("#departments IS NOT NULL");
    	$aQuery->show('departments');
    	while($aRec = $aQuery->fetch()){
    		$aRec->departments = NULL;
    		$Assets->save_($aRec);
    	}
    	 
    	$cQuery = $Cons->getQuery();
    	$cQuery->where("#departmentId IS NOT NULL");
    	$cQuery->show('departmentId');
    	while($cRec = $cQuery->fetch()){
    		$cRec->departmentId = $map[$cRec->departmentId];
    		$Cons->save_($cRec, 'departmentId');
    	}
    	 
    	$rQuery = $Ret->getQuery();
    	$rQuery->where("#departmentId IS NOT NULL");
    	$rQuery->show('departmentId');
    	while($rRec = $rQuery->fetch()){
    		$rRec->departmentId = $map[$rRec->departmentId];
    		$Ret->save_($rRec, 'departmentId');
    	}
    	
    	$hQuery = $Hr->getQuery();
    	$hQuery->where("#departments IS NOT NULL");
    	$hQuery->show('departments');
    	while($hRec = $hQuery->fetch()){
    		$d = keylist::toArray($hRec->departments);
    		$intersect = arr::make(array_intersect_key($map, $d), TRUE);
    		
    		$new = array();
    		if(is_array($intersect)){
	    		foreach ($intersect as $v){
    				$v = planning_Centers::fetchField($v, 'folderId');
    				$new[$v] = $v;
	    		}
    		}
    		
    		$hRec->departments = keylist::fromArray($new);
    		$hRec->departments = empty($hRec->departments) ? NULL : $hRec->departments;
    		$Hr->save_($hRec);
    	}
    	
    	$eQuery = $Econtr->getQuery();
    	$eQuery->where("#departmentId IS NOT NULL");
    	$eQuery->show('departmentId');
    	while($eRec = $eQuery->fetch()){
    		$eRec->departmentId = $map[$eRec->departmentId];
    		$eRec->departmentId = (empty($eRec->departmentId)) ? NULL : $eRec->departmentId;
    		$Econtr->save_($eRec);
    	}
    	
    	$cuQuery = $Cust->getQuery();
    	$cuQuery->where("#departmenId IS NOT NULL");
    	$cuQuery->show('departmenId');
    	while($cuRec = $cuQuery->fetch()){
    		$cuRec->departmenId = $map[$cuRec->departmenId];
    		$cuRec->departmenId = (empty($cuRec->departmenId)) ? NULL : $cuRec->departmenId;
    		$Cust->save_($cuRec);
    	}
    	
    	$this->updateCenterExt();
    }
    
    
    /**
     * Ъпдейт на центровете
     */
    function updateCenterExt()
    {
    	$Assets = cls::get('planning_AssetResources');
    	$Assets->setupMvc();
    	
    	$aQuery = $Assets->getQuery();
    	$aQuery->where("#departments IS NULL || #departments = ''");
    	while($aRec = $aQuery->fetch()){
    		$Assets->save($aRec, 'departments');
    	}
    	
    	$Hr = cls::get('planning_Hr');
    	$Hr->setupMvc();
    	
    	$hQuery = $Hr->getQuery();
    	$hQuery->where("#departments IS NULL");
    	while($hRec = $hQuery->fetch()){
    		$Hr->save($hRec, 'departments');
    	}
    	
    	$query = $Hr->getQuery();
    	$query->where("#code IS NULL");
    	while($rec = $query->fetch()){
    		$rec->code = planning_Hr::getDefaultCode($rec->personId);
    		$Hr->save($rec);
    	}
    }
}
