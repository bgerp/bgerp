<?php


/**
 * Начален номер на фактурите
 */
defIfNot('PRICE_SIGNIFICANT_DIGITS', '5');


/**
 * Краен номер на фактурите
*/
defIfNot('PRICE_MIN_DECIMALS', '2');


/**
 * Инсталиране на модул 'price'
 *
 * Ценови политики на фирмата
 *
 * @category  bgerp
 * @package   price
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class price_Setup extends core_ProtoSetup
{
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'price_Lists';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Ценови политики, ценоразписи, разходни норми";
    
    
    /**
     * Настройки за Cron
     */
    var $cronSettings = array(
    		array(
    			'systemId'    => "Update primecosts",
    			'description' => "Обновяване на себестойностите",
    			'controller'  => "price_Updates",
    			'action'      => "Updateprimecosts",
    			'period'      => 60,
    			'timeLimit'   => 360,
    		),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'price_Lists',
            'price_ListToCustomers',
            'price_ListRules',
            'migrate::priceHistoryTruncate',
            'price_History',
        	'price_ListDocs',
    		'price_ProductCosts',
    		'price_Updates',
    		'migrate::routeLists',
    		'migrate::truncateProductCosts',
    		'migrate::transferGroups',
    		'migrate::updateListStates'
        );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = array(array('priceDealer'),
    				   array('price', 'priceDealer'),
    				   array('priceMaster', 'price'),
    );
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(1.44, 'Артикули', 'Ценообразуване', 'price_Lists', 'default', "price,sales, ceo"),
        );
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
    		'PRICE_SIGNIFICANT_DIGITS' => array("int(min=0)", "caption=Закръгляне в ценовите политики (без себестойност)->Значещи цифри"),
    		'PRICE_MIN_DECIMALS'       => array("int(min=0)", 'caption=Закръгляне в ценовите политики (без себестойност)->Мин. знаци'),
    	);
    	
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
     * Миграция, която изтрива таблицата price_History
     * за да може да се постави уникален индекс
     */
    function priceHistoryTruncate()
    {
        $history = cls::get('price_History');
        if($history->db->tableExists($history->dbTableName)) {
            $history->truncate();
        }
    }
    
    
    /**
     * Миграция за изтриване на кешираните цени
     */
    function truncateProductCosts()
    {
    	price_ProductCosts::truncate();
    }
    
    
    /**
     * Мигрира ценовите групи към групите
     */
    function transferGroups()
    {
    	$CatGroups = cls::get('cat_Groups');
    	$CatGroups->setupMvc();
    	
    	$PriceGroups = cls::get('price_Groups');
    	$PriceGroups->setupMvc();
    	
    	$Lists = cls::get('price_Lists');
    	$Lists->setupMvc();
    	
    	$Rules = cls::get('price_ListRules');
    	$Rules->setupMvc();
    	
    	cls::get('price_ListToCustomers')->setupMvc();
    	
    	if (!$PriceGroups->db->tableExists($PriceGroups->dbTableName)) return;
    	core_App::setTimeLimit(300);
    	
    	$Products = cls::get('cat_Products');
    	
    	// Кой ще е бащата на новите групи
    	$parentId = cat_Groups::fetchField("#sysId = 'priceGroup'", 'id');
    	
    	$res = array();
    	
    	// Извличаме всички ценови групи
    	$gQuery = price_Groups::getQuery();
    	while($rec = $gQuery->fetch()){
    		try{
    			// Ако не е прехвърляна
    			$id = cat_Groups::fetchField(array("#parentId = {$parentId} AND #name = '[#1#]'", $rec->title));
    			
    			// Прехвърляме я
    			if(!$id){
    				$recToSave = (object)array('name' => $rec->title, 'parentId' => $parentId);
    				$id = $CatGroups->save_($recToSave, NULL, 'REPLACE');
    			}
    			
    			// За всеки случай записваме в модела новото ид
    			if($id){
    				$rec->groupId = $id;
    				$PriceGroups->save_($rec, 'groupId');
    			}
    			
    			// Запомняме прехвърлените ид-та
    			$res[$rec->id] = $rec->groupId;
    		} catch(core_exception_Expect $e){
    			reportException($e);
    		}
    	}
    	
    	$date = dt::now();
    	
    	// Ако има прехвърлени ид-та
    	if(count($res)){
    		try{
    			// Извличаме всички артикули
    			$pQuery = cat_Products::getQuery();
    			$pQuery->show('groups');
    			while($pRec = $pQuery->fetch()){
    				// Намираме коя е последната им ценова група
    				$group = price_GroupOfProducts::getGroup($pRec->id, $date);
    				
    				// Ако имат такава
    				if($group){
    					
    					// Намираме кое ново ид и съответства
    					$key = $res[$group];
    					
    					// Ако има такова
    					if(isset($key)){
    						
    						// И ид-то му не присъства в групите на артикула
    						if(!keylist::isIn($key, $pRec->groups)){
    							
    							// Добавяме го
    							$newGroups = keylist::addKey($pRec->groups, $key);
    							$pRec->groups = $newGroups;
    							$Products->save_($pRec, 'groups');
    						}
    					}
    				}
    			}
    		} catch(core_exception_Expect $e){
    			reportException($e);
    		}
    	}
    	
    	try{
    		$costId = price_ListRules::PRICE_LIST_COST;
    		
    		$lQuery = price_Lists::getQuery();
    		$lQuery->where("#defaultSurcharge IS NULL");
    		$lQuery->where("id != '{$costId}'");
    		$lQuery->show('defaultSurcharge');
    		while($lRec = $lQuery->fetch()){
    			$lRec->defaultSurcharge = ($lRec->id == price_ListRules::PRICE_LIST_CATALOG) ? NULL : 0;
    			$Lists->save_($lRec, 'defaultSurcharge');
    		}
    	} catch(core_exception_Expect $e){
    		reportException($e);
    	}
    	
    	try{
    		$rQuery = price_ListRules::getQuery();
    		$rQuery->where("#groupId IS NOT NULL");
    		while($r = $rQuery->fetch()){
    			if(isset($res[$r->groupId])){
    				$r->groupId = $res[$r->groupId];
    				$Rules->save_($r, 'groupId');
    			}
    		}
    	} catch(core_exception_Expect $e){
    		reportException($e);
    	}
    	
    	try{
    		$rQuery2 = price_ListRules::getQuery();
    		$rQuery2->where("#priority IS NULL");
    		 
    		$saveArray = array();
    		 
    		while ($r = $rQuery2->fetch()){
    			$res = (object)array('id' => $r->id);
    			if($r->type == 'value' || $r->type == 'discount'){
    				$res->priority = 1;
    			} else {
    				$res->priority = 3;
    			}
    		
    			$saveArray[] = $res;
    		}
    		 
    		if(count($saveArray)){
    			$Rules = cls::get('price_ListRules');
    			$Rules->saveArray($saveArray, 'id,priority');
    		}
    	} catch(core_exception_Expect $e){
    		reportException($e);
    	}
    }
    
    
    /**
     * Обновяване на състоянията
     */
    function updateListStates()
    {
    	try{
    		cls::get('price_ListToCustomers')->setupMvc();
    		cls::get('price_Lists')->setupMvc();
    		price_ListToCustomers::updateStates();
    	} catch(core_exception_Expect $e){
    		reportException($e);
    	}
    }
    
    
    /**
     * Мигриране на себестойностите
     */
    function routeLists()
    {
    	$CatGroups = cls::get('cat_Groups');
    	$CatGroups->setupMvc();
    	$Lists = cls::get('price_Lists');
    	$Lists->setupMvc();
    	$Folders = cls::get('doc_Folders');
    	$Containers = cls::get('doc_Containers');
    	$Threads = cls::get('doc_Threads');
    	$Rules = cls::get('price_ListRules');
    	$Rules->setupMvc();
    	cls::get('price_ListToCustomers')->setupMvc();
    	
    	try{
    		$query = $Lists->getQuery();
    		$query->where("#folderId IS NULL");
    		while($rec = $query->fetch()){
    		
    		    $sudoUser = core_Users::sudo($rec->createdBy);
    		
    			$folderId = (isset($rec->cClass) && isset($rec->cId)) ? cls::get($rec->cClass)->forceCoverAndFolder($rec->cId) : NULL;
    			$rec->folderId = $folderId;
    			$rec->state = ($rec->state == 'rejected') ? 'rejected' : 'active';
    			$Lists->route($rec);
    		
    			$Lists->save($rec);
    		
    		    core_Users::exitSudo($sudoUser);
    		}
    	} catch(core_exception_Expect $e){
            core_Users::exitSudo($sudoUser);
    		reportException($e);
    		expect(FALSE);
    	}
    }
}
