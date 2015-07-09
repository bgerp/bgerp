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
    		'planning_Stages',
    		'planning_ConsumptionNotes',
    		'planning_ConsumptionNoteDetails',
    		'planning_ProductionNotes',
    		'planning_ProductionNoteDetails',
    		'planning_DirectProductionNote',
    		'planning_DirectProductNoteDetails',
    		'planning_ObjectResources',
    		'migrate::replaceResources'
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
    var $defClasses = "planning_PlanningReportImpl,planning_PurchaseReportImpl";
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
    
    
    public function replaceResources()
    {
    	if(!cat_Products::count()) return;
    	 
    	cls::get('planning_ObjectResources')->setupMVC();
    	
    	if(!cls::load('planning_Resources', TRUE)) return;
    	
    	$pClassId = cat_Products::getClassId();
    	$rClass = planning_Resources::getClassId();
    	 
    	$oQuery = planning_ObjectResources::getQuery();
    	$oQuery->groupBy('resourceId');
    	$oQuery->where("#classId = {$pClassId}");
    	$map = array();
    	while($oRec = $oQuery->fetch()){
    		if($oRec->resourceId){
    			$map[$oRec->resourceId] = $oRec->objectId;
    			
    			$oRec->measureId = $resource->measureId;
    			$oRec->selfValue = $resource->selfValue;
    			planning_ObjectResources::save($oRec, 'measureId,selfValue');
    		}
    	}
    	 
    	if(!count($map)) return;
    	 
    	$bomQuery = cat_BomDetails::getQuery();
    	while ($bomRec = $bomQuery->fetch()){
    		//bp($bomRec, planning_resources::getTitleByid($bomRec->resourceId), $map[$bomRec->resourceId], cat_Products::getTitleByid($map[$bomRec->resourceId]));
    		$bomRec->resourceId = $map[$bomRec->resourceId];
    		//bp($bomRec);
    		cat_BomDetails::save($bomRec, 'resourceId');
    
    
    
    	}
    	//return;
    	 
    	$itemsQuery = acc_Items::getQuery();
    	$itemsQuery->where("#classId = {$rClass}");
    	$itemMap = array();
    	 
    	while($iRec = $itemsQuery->fetch()){
    		if(isset($map[$iRec->objectId])){
    			if($productItem = acc_Items::fetchItem($pClassId, $map[$iRec->objectId])){
    				$itemMap[$iRec->id] = $productItem->id;
    			}
    		} else {
    			$sysId = planning_Resources::fetchField($iRec->objectId, 'systemId');
    			unset($pId);
    			switch($sysId){
    				case 'commonLabor':
    					$pId = cat_Products::fetchField("#code = 'labor'", 'id');
    					break;
    				case 'commonMaterial':
    					$pId = cat_Products::fetchField("#code = 'materials'", 'id');
    					break;
    				case 'commonService':
    					$pId = cat_Products::fetchField("#code = 'services'", 'id');
    					break;
    				case 'commonEquipment':
    					$pId = cat_Products::fetchField("#code = 'fixedAssets'", 'id');
    					break;
    			}
    			$itemMap[$iRec->id] = acc_Items::fetchItem($pClassId, $pId)->id;
    		}
    
    		if($iRec->state != 'closed'){
    			$iRec->state = 'closed';
    			acc_Items::save($iRec);
    		}
    	}
    	 
    	$replaceIds = array_keys($itemMap);
    	 
    	$bQuery = acc_BalanceDetails::getQuery();
    	$bQuery->in('ent1Id', $replaceIds);
    	$bQuery->in('ent2Id', $replaceIds, FALSE, TRUE);
    	$bQuery->in('ent3Id', $replaceIds, FALSE, TRUE);
    	 
    	while($bRec = $bQuery->fetch()){
    		foreach (array('ent1Id', 'ent2Id', 'ent3Id') as $fld){
    			if(isset($itemMap[$bRec->$fld])){
    				$bRec->$fld = $itemMap[$bRec->$fld];
    			}
    		}
    
    		try{
    			acc_BalanceDetails::save($bRec);
    		} catch(core_exception_Expect $e){
    			 
    		}
    	}
    	 
    	$jQuery = acc_JournalDetails::getQuery();
    	 
    	$jQuery->in('debitItem1', $replaceIds);
    	$jQuery->in('debitItem2', $replaceIds, FALSE, TRUE);
    	$jQuery->in('debitItem3', $replaceIds, FALSE, TRUE);
    	$jQuery->in('creditItem1', $replaceIds, FALSE, TRUE);
    	$jQuery->in('creditItem2', $replaceIds, FALSE, TRUE);
    	$jQuery->in('creditItem3', $replaceIds, FALSE, TRUE);
    	 
    	while($jRec = $jQuery->fetch()){
    		foreach (array('debitItem1', 'debitItem2', 'debitItem3', 'creditItem1', 'creditItem2', 'creditItem3') as $fld){
    			if(isset($itemMap[$jRec->$fld])){
    				$jRec->$fld = $itemMap[$jRec->$fld];
    			}
    		}
    
    		try{
    			acc_JournalDetails::save($jRec);
    		} catch(core_exception_Expect $e){
    			 
    		}
    	}
    	 
    	$mQuery = acc_ArticleDetails::getQuery();
    	$mQuery->in('debitEnt1', $replaceIds);
    	$mQuery->in('debitEnt2', $replaceIds, FALSE, TRUE);
    	$mQuery->in('debitEnt3', $replaceIds, FALSE, TRUE);
    	$mQuery->in('creditEnt1', $replaceIds, FALSE, TRUE);
    	$mQuery->in('creditEnt2', $replaceIds, FALSE, TRUE);
    	$mQuery->in('creditEnt3', $replaceIds, FALSE, TRUE);
    	 
    	while($mRec = $mQuery->fetch()){
    		foreach (array('debitEnt1', 'debitEnt2', 'debitEnt3', 'creditEnt1', 'creditEnt2', 'creditEnt3') as $fld){
    			if(isset($itemMap[$mRec->$fld])){
    				$mRec->$fld = $itemMap[$mRec->$fld];
    			}
    		}
    
    		try{
    			acc_ArticleDetails::save($mRec);
    		} catch(core_exception_Expect $e){
    			 
    		}
    	}
    	 
    	$tQuery = acc_BalanceTransfers::getQuery();
    	$tQuery->in('fromEnt1Id', $replaceIds);
    	$tQuery->in('fromEnt2Id', $replaceIds, FALSE, TRUE);
    	$tQuery->in('fromEnt3Id', $replaceIds, FALSE, TRUE);
    	$tQuery->in('toEnt1Id', $replaceIds, FALSE, TRUE);
    	$tQuery->in('toEnt2Id', $replaceIds, FALSE, TRUE);
    	$tQuery->in('toEnt3Id', $replaceIds, FALSE, TRUE);
    	 
    	while($tRec = $tQuery->fetch()){
    		foreach (array('fromEnt1Id', 'fromEnt2Id', 'fromEnt3Id', 'toEnt1Id', 'toEnt3Id', 'toEnt3Id') as $fld){
    			if(isset($itemMap[$tRec->$fld])){
    				$tRec->$fld = $itemMap[$tRec->$fld];
    			}
    		}
    		 
    		try{
    			acc_BalanceTransfers::save($tRec);
    		} catch(core_exception_Expect $e){
    			 
    		}
    	}
    	//bp($tQuery->fetchAll());
    	 
    	//bp($jQuery->fetchAll());
    	 
    	//bp($replaceIds,$bQuery->fetchAll());
    	 
    	//bp($itemMap,acc_items::fetch(5687), acc_Items::fetch(138));
    }
    
    
    function replaceResources11()
    {
    	$pClassId = cat_Products::getClassId();
    	$oQuery = planning_ObjectResources::getQuery();
    	$oQuery->groupBy('resourceId');
    	$oQuery->where("#classId = {$pClassId}");
    	$oQuery->where("#resourceId IS NOT NULL");
    	
    	while($oRec = $oQuery->fetch()){
    		if($oRec->resourceId){
    			
    			$resource = planning_resources::fetch($oRec->resourceId);
    			$oRec->measureId = $resource->measureId;
    			$oRec->selfValue = $resource->selfValue;
    			planning_ObjectResources::save($oRec, 'measureId,selfValue');
    		}
    	}
    }
}
