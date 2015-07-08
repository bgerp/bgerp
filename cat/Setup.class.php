<?php


/**
 * Колко от последно вложените ресурси да се показват в мастъра на рецептите
 */
defIfNot('CAT_BOM_REMEMBERED_RESOURCES', 20);


/**
 * Дефолт свойства на нови артикули в папките на клиенти
 */
defIfNot('CAT_DEFAULT_META_IN_CONTRAGENT_FOLDER', 'canSell,canManifacture,canStore');


/**
 * Дефолт свойства на нови артикули в папките на доставчици
 */
defIfNot('CAT_DEFAULT_META_IN_SUPPLIER_FOLDER', 'canBuy,canConvert,canStore');


/**
 * class cat_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с продуктите
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версията на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'cat_Products';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Каталог на стандартните артикули";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'cat_UoM',
            'cat_Groups',
    		'cat_Categories',
            'cat_Products',
            'cat_products_Params',
            'cat_products_Packagings',
    		'cat_products_VatGroups',
            'cat_Params',
            'cat_Packagings',
    		'cat_Boms',
    		'cat_BomDetails',
    		'cat_ProductTplCache',
    		'migrate::migrateGroups',
    		'migrate::migrateProformas',
    		'migrate::removeOldParams1',
    		'migrate::updateDocs',
    		'migrate::truncatCache',
            'migrate::fixProductsSearchKeywords'
        );


    /**
     * Роли за достъп до модула
     */
    var $roles = 'cat,sales,purchase';
 
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(1.42, 'Артикули', 'Каталог', 'cat_Products', 'default', "cat,ceo,sales,purchase"),
        );


    /**
     * Дефиниции на класове с интерфейси
     */
    var $classes = 'cat_SalesArticleReport';


    /**
     * Дефинирани класове, които имат интерфейси
     */
    var $defClasses = "cat_GeneralProductDriver, cat_BaseImporter,cat_SalesArticleReport";


    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
    		'CAT_BOM_REMEMBERED_RESOURCES' => array("int", 'caption=Колко от последно изпозлваните ресурси да се показват в рецептите->Брой'),
    		'CAT_DEFAULT_META_IN_CONTRAGENT_FOLDER' => array("set(canSell=Продаваем,canBuy=Купуваем,canStore=Складируем,canConvert=Вложим,fixedAsset=Дълготраен актив,canManifacture=Производим)", 'caption=Свойства по подразбиране в папка->На клиент,columns=2'),
    		'CAT_DEFAULT_META_IN_SUPPLIER_FOLDER' => array("set(canSell=Продаваем,canBuy=Купуваем,canStore=Складируем,canConvert=Вложим,fixedAsset=Дълготраен актив,canManifacture=Производим)", 'caption=Свойства по подразбиране в папка->На доставчик,columns=2'),
    );

    
    /**
     * Настройки за Cron
     */
    var $cronSettings = array(
    		array(
    				'systemId' => "Close Old Private Products",
    				'description' => "Затваряне на частните артикули, по които няма движения",
    				'controller' => "cat_Products",
    				'action' => "closePrivateProducts",
    				'period' => 21600,
    				'offset' => 60,
    				'timeLimit' => 200
    		),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $html = parent::install();
        
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('productsImages', 'Илюстрация на продукта', 'jpg,jpeg,png,bmp,gif,image/*', '3MB', 'user', 'every_one');
        
        return $html;
    }
    
           
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
     * Миграция на мета данните на групите
     */
    public function migrateGroups()
    {
    	$Set = cls::get('type_Set');
    	
    	$query = cat_Groups::getQuery();
    	while($rec = $query->fetch()){
    		$meta = type_Set::toArray($rec->meta);
    		if(isset($meta['materials'])){
    			$meta['canStore'] = 'canStore';
    			$meta['canConvert'] = 'canConvert';
    			unset($meta['materials']);
    		}
    		
    		$rec->meta = $Set->fromVerbal($meta);
    		cat_Groups::save($rec, 'meta');
    	}
    }
    
    
    /**
     * Изтрива стари параметри
     */
    public function removeOldParams1()
    {
    	foreach (array('vat', 'vatGroup') as $sysId){
    		if($vRec = cat_Params::fetch("#sysId = '{$sysId}'")){
    			cat_products_Params::delete("#paramId = '{$vRec->id}'");
    			cat_Params::delete($vRec->id);
    		}
    	}
    }
    
    
    /**
     * Временна миграция
     */
    public function migrateProformas()
    {
    	if(core_Packs::fetch("#name = 'sales'")){
    		if(sales_ProformaDetails::count()){
    			$query = sales_ProformaDetails::getQuery();
    			$productId = cat_Products::getClassId();
    			while($rec = $query->fetch()){
    				if($rec->classId != $productId){
    					$rec->classId = $productId;
    					sales_ProformaDetails::save_($rec);
    				}
    			}
    		}
    	}
    }
    
    
    /**
     * Ъпдейтване на старите задания и рецепти
     */
    public function updateDocs()
    {
    	$bomQuery = cat_Boms::getQuery();
    	$bomQuery->where("#productId IS NULL");
    	while($bRec = $bomQuery->fetch()){
    		$origin = doc_Containers::getDocument($bRec->originId);
    		$bRec->productId = $origin->that;
    		cat_Boms::save($bRec, 'productId');
    	}
    	
    	if(core_Packs::fetch("#name = 'planning'")){
    		$jQuery = planning_Jobs::getQuery();
    		$jQuery->where("#productId IS NULL");
    		while($jRec = $jQuery->fetch()){
    			$origin = doc_Containers::getDocument($jRec->originId);
    			$jRec->productId = $origin->that;
    			planning_Jobs::save($jRec, 'productId');
    		}
    	}
    }
    
    
    /**
     * Изтриваме кеша
     */
    public function truncatCache()
    {
    	cat_ProductTplCache::truncate();
    }
    
    
    /**
     * Оправя ключовите думи на артикулите
     */
    public static function fixProductsSearchKeywords()
    {
    	$query = cat_Products::getQuery();
    	
    	while($rec = $query->fetch()) {
    		if(cls::load($rec->innerClass, TRUE)){
    			try {
    				cat_Products::save($rec, 'searchKeywords');
    			} catch (core_exception_Expect $e) {
    				continue;
    			}
    		}
    	}
    }
    
    
    public function replaceResources()
    {
    	if(!cat_Products::count()) return;
    	
    	$pClassId = cat_Products::getClassId();
    	$rClass = planning_Resources::getClassId();
    	
    	$oQuery = planning_ObjectResources::getQuery();
    	$oQuery->groupBy('resourceId');
    	$oQuery->where("#classId = {$pClassId}");
    	$map = array();
    	while($oRec = $oQuery->fetch()){
    		$map[$oRec->resourceId] = $oRec->objectId;
    	}
    	
    	if(!count($map)) return;
    	
    	$bomQuery = cat_BomDetails::getQuery();
    	while ($bomRec = $bomQuery->fetch()){
    		$bomRec->resourceId = $map[$bomRec->resourceId];
    		//cat_BomDetails::save($bomRec, 'resourceId');
    		
    		
    		
    	}
    	
    	
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
    	bp($tQuery->fetchAll());
    	
    	//bp($jQuery->fetchAll());
    	
    	bp($replaceIds,$bQuery->fetchAll());
    	
    	bp($itemMap,acc_items::fetch(5687), acc_Items::fetch(138));
    }
}
