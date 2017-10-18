<?php


/**
 * Да се показвали рецептата в описанието на артикула
 */
defIfNot('CAT_SHOW_BOM_IN_PRODUCT', 'yes');


/**
 * Коя да е основната мярка на универсалните артикули
 */
defIfNot('CAT_DEFAULT_MEASURE_ID', '');


/**
 * Показване на компонентите при вложени рецепти, Макс. брой
 */
defIfNot('CAT_BOM_MAX_COMPONENTS_LEVEL', 3);


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
 * При търсене на складова себестойност до колко месеца на зад да се търси
 */
defIfNot('CAT_WAC_PRICE_PERIOD_LIMIT', 3);


/**
 * Ценова политика по подразбиране
 */
defIfNot('CAT_DEFAULT_PRICELIST', price_ListRules::PRICE_LIST_CATALOG);


/**
 * Брой артикули в автоматичните списъци
 */
defIfNot('CAT_AUTO_LIST_PRODUCT_COUNT', 30);



/**
 * Артикулите от кои групи да влизат в последните продажби
 */
defIfNot('CAT_AUTO_LIST_ALLOWED_GROUPS', '');


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
 * @copyright 2006 - 2016 Experta OOD
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
     * Необходими пакети
     */
    var $depends = 'cond=0.1';
    
    
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
    		'cat_products_SharedInFolders',
            'cat_Params',
    		'cat_Boms',
    		'cat_BomDetails',
    		'cat_ProductTplCache',
    		'cat_Listings',
    		'cat_ListingDetails',
    		'cat_PackParams',
    		'migrate::migrateGroups',
    		'migrate::migrateProformas',
    		'migrate::removeOldParams1',
    		'migrate::updateDocs',
    		'migrate::truncatCache',
            'migrate::fixProductsSearchKeywords',
    		'migrate::updateProductsNew',
    		'migrate::deleteCache2',
    		'migrate::addClassIdToParams',
    		'migrate::updateBomType',
    		'migrate::updateParamStates',
    		'migrate::migratePrototypes',
    		'migrate::updateListings1',
    		'migrate::updateLists',
    		'migrate::migrateListings',
    		'migrate::updateCatCache',
        );
    
    
    /**
     * Роли за достъп до модула
     */
    var $roles = array(
            array('listArt'),
    		array('sales', 'listArt'),
    		array('purchase'),
    		array('packEdit'),
    		array('catEdit', 'packEdit'),
    		array('cat', 'catEdit'),
            array('rep_cat'),
            array('catImpEx'),
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(1.42, 'Артикули', 'Каталог', 'cat_Products', 'default', "powerUser"),
        );


    /**
     * Дефинирани класове, които имат интерфейси
     */
    var $defClasses = "cat_GeneralProductDriver, cat_reports_SalesArticle, cat_reports_BomsRep";


    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
    		'CAT_BOM_REMEMBERED_RESOURCES'          => array("int", 'caption=Колко от последно изпозлваните ресурси да се показват в рецептите->Брой'),
    		'CAT_DEFAULT_META_IN_CONTRAGENT_FOLDER' => array("set(canSell=Продаваем,canBuy=Купуваем,canStore=Складируем,canConvert=Вложим,fixedAsset=Дълготраен актив,canManifacture=Производим)", 'caption=Свойства по подразбиране в папка->На клиент,columns=2'),
    		'CAT_DEFAULT_META_IN_SUPPLIER_FOLDER'   => array("set(canSell=Продаваем,canBuy=Купуваем,canStore=Складируем,canConvert=Вложим,fixedAsset=Дълготраен актив,canManifacture=Производим)", 'caption=Свойства по подразбиране в папка->На доставчик,columns=2'),
    		'CAT_DEFAULT_MEASURE_ID'                => array("key(mvc=cat_UoM,select=name,allowEmpty)", 'optionsFunc=cat_UoM::getUomOptions,caption=Основна мярка на артикулите->Мярка'),
    		'CAT_BOM_MAX_COMPONENTS_LEVEL'          => array("int(min=0)", 'caption=Вложени рецепти - нива с показване на компонентите->Макс. брой'),
    		'CAT_WAC_PRICE_PERIOD_LIMIT'            => array("int(min=1)", array('caption' => 'До колко периода назад да се търси складова себестойност, ако няма->Брой')),
            'CAT_DEFAULT_PRICELIST'                 => array("key(mvc=price_Lists,select=title,allowEmpty)", 'caption=Ценова политика по подразбиране->Избор,mandatory'),
            'CAT_AUTO_LIST_PRODUCT_COUNT'           => array("int(min=1)", 'caption=Списъци от последно продавани артикули->Брой'),
            'CAT_AUTO_LIST_ALLOWED_GROUPS'          => array("keylist(mvc=cat_Groups,select=name)", 'caption=Списъци от последно продавани артикули->Групи'),
            'CAT_SHOW_BOM_IN_PRODUCT'               => array("enum(yes=Да,no=Не)", 'caption=Показване на рецептата в описанието на артикула->Показване'),
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
    		
    		array(
    				'systemId' => "Update Auto Sales List",
    				'description' => "Обновяване на листовете с продажби",
    				'controller' => "cat_Listings",
    				'action' => "UpdateAutoLists",
    				'period' => 1440,
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
        $res = bgerp_Menu::remove($this);
        
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
    		$Detail = cls::get('sales_ProformaDetails');
    		
    		if($Detail::count()){
    			$query = $Detail->getQuery();
    			$productId = cat_Products::getClassId();
    			while($rec = $query->fetch()){
    				if($rec->classId != $productId){
    					$rec->classId = $productId;
    					$Detail->save_($rec);
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
    
    
    /**
     * Миграционна функция
     */
    function replaceBoms()
    {
    	$Bom = cls::get('cat_BomDetails');
    	$bomQuery = $Bom->getQuery();
    	
    	while ($bomRec = $bomQuery->fetch()){
    		if($bomRec->resourceId == 1147){
    			$r = cat_products_Packagings::fetch(15);
    	
    			$bomRec->packagingId = $r->packagingId;
    			$bomRec->quantityInPack = $r->quantity;
    	
    			$Bom->save($bomRec, NULL, 'REPLACE');
    		} elseif($bomRec->resourceId == 1151){
    			$r = cat_products_Packagings::fetch(7);
    	
    			$bomRec->packagingId = $r->packagingId;
    			$bomRec->quantityInPack = $r->quantity;
    	
    			$Bom->save($bomRec, NULL, 'REPLACE');
    		} elseif($bomRec->resourceId == 1145){
    			$r = cat_products_Packagings::fetch(11);
    	
    			$bomRec->packagingId = $r->packagingId;
    			$bomRec->quantityInPack = $r->quantity;
    	
    			$Bom->save($bomRec, NULL, 'REPLACE');
    		}
    	}
    	 
    	unset($bomRec);
    	$Dp = cls::get('planning_DirectProductNoteDetails');
    	$dQuery = $Dp->getQuery();
    	
    	while ($bomRec = $dQuery->fetch()){
    		
    		if($bomRec->productId == 1147){
    			$r = cat_products_Packagings::fetch(15);
    		
    			$bomRec->packagingId = $r->packagingId;
    			$bomRec->quantityInPack = $r->quantity;
    	
    			$Dp->save($bomRec, NULL, 'REPLACE');
    		} elseif($bomRec->productId == 1151){
    			$r = cat_products_Packagings::fetch(7);
    			$bomRec->packagingId = $r->packagingId;
    			$bomRec->quantityInPack = $r->quantity;
    	
    			$Dp->save($bomRec, NULL, 'REPLACE');
    		} elseif($bomRec->productId == 1145){
    			$r = cat_products_Packagings::fetch(11);
    			
    			$bomRec->packagingId = $r->packagingId;
    			$bomRec->quantityInPack = $r->quantity;
    			
    			$Dp->save($bomRec, NULL, 'REPLACE');
    		}
    	}
    }
    
    
    /**
     * Миграция на артикулите
     */
    function updateProductsNew()
    {
    	if(!cat_Products::count()) return;
    	
    	core_App::setTimeLimit(700);
    	
    	$Products = cls::get('cat_Products');
    	$query = $Products->getQuery();
    	
		$query->orderBy('id', 'ASC');
    	while($rec = $query->fetch()){
    		try{
    			$Products->save_($rec);
    		} catch(core_exception_Expect $e){
    			
    		}
    	}
    }
    
    
    /**
     * Изчистване на кеша на артикулите
     */
    public function deleteCache2()
    {
    	cat_ProductTplCache::truncate();
    }
    
    
    /**
     * Миграция на параметрите
     */
    public static function addClassIdToParams()
    {
    	$Params = cls::get('cat_products_Params');
    	$Params->setupMvc();
    	$classId = cat_Products::getClassId();
    	
    	try{
    		$query = $Params->getQuery();
    		$query->where("#classId IS NULL");
    		while($rec = $query->fetch()){
    			$rec->classId = $classId;
    			$Params->save_($rec, 'classId');
    		}
    	} catch(core_exception_Expect $e){
    		reportException($e);
    	}
    }
    
    
    /**
     * Ъпдейт на типа на рецептите
     */
    public function updateBomType()
    {
    	$Boms = cls::get('cat_Boms');
    	$Boms->setupMvc();
    	
    	$query = $Boms->getQuery();
    	while($rec = $query->fetch()){
    		try{
    			$firstDocument = doc_Threads::getFirstDocument($rec->threadId);
    			$type = 'sales';
    			if($firstDocument && $firstDocument->isInstanceOf('planning_Jobs')){
    				$type = 'production';
    			}
    			$rec->type = $type;
    			$Boms->save_($rec, 'type');
    		} catch(core_exception_Expect $e){
    			reportException($e);
    		}
    	}
    }
    
    
    /**
     * Миграция на състоянието на параметъра
     */
    public function updateParamStates()
    {
    	$Params = cls::get('cat_Params');
    	$Params->setupMvc();
    	
    	$query = cat_Params::getQuery();
    	$query->where("#state = '' || #state IS NULL");
    	while($rec = $query->fetch()){
    		$rec->state = 'active';
    		$Params->save_($rec, 'state');
    	}
    }
    
    
    /**
     * Миграция на шаблонните артикули
     */
    public function migratePrototypes()
    {
    	try{
    		cls::get('cat_Products')->setupMvc();
    		$folders = array();
    		 
    		// В кои категории може да има прототипни артикули
    		$query = cat_Categories::getQuery();
    		$query->where("#useAsProto = 'yes'");
    		$query->show('folderId');
    		while($cRec = $query->fetch()) {
    			$folders[$cRec->folderId] = $cRec->folderId;
    		}
    		 
    		if(count($folders)){
    			core_App::setTimeLimit(300);
    			
    			foreach ($folders as $folderId){
    				$cQuery = cat_Products::getQuery();
    				$cQuery->where("#state = 'active'");
    				$cQuery->where("#folderId = {$folderId}");
    			
    				while($rec = $cQuery->fetch()){
    					$title = cat_Products::getTitleById($rec->id);
    					doc_Prototypes::add($title, 'cat_Products', $rec->id, $rec->innerClass);
    				}
    			}
    		}
    	} catch(core_exception_Expect $e){
    		reportException($e);
    	}
    }
    
    
    /**
     * Ъпдейт на листингите
     */
    function updateListings1()
    {
    	$Lists = cls::get('cat_Listings');
    	$Lists->setupMvc();
    	core_Classes::add('cat_Listings');
    	
    	$Detail = cls::get('cat_ListingDetails');
    	$Detail->setupMvc();
    	
    	if(!$Detail::count()) return;
    	
    	$new = $toSave = array();
    	$query = $Detail->getQuery();
    	$query->FLD('contragentClassId', 'int');
    	$query->FLD('contragentId', 'int');
    	$query->where("#listId IS NULL");
    	
    	while($rec = $query->fetch()){
    		if(!(isset($rec->contragentClassId) && isset($rec->contragentId))) continue;
    		
    		$folderId = cls::get($rec->contragentClassId)->forceCoverAndFolder($rec->contragentId);
    			 
    		if(!isset($new[$folderId])){
    			$name = cls::get($rec->contragentClassId)->getTitleById($rec->contragentId);
    	
    			if($exRec = cat_Listings::fetch("#title = '{$name}'")){
    				$lId = $exRec->id;
    			} else {
    				$n = (object)array('title' => $name, 'folderId' => $folderId, 'createdBy' => $rec->modifiedBy, 'createdOn' => $rec->modifiedOn);
    				
    				core_Users::sudo($rec->modifiedBy);
    				$listId = cat_Listings::save($n);
    				core_Users::exitSudo();
    				
    				$new[$folderId] = $listId;
    			}
    	
    			$new[$folderId] = $lId;
    		}
    			 
    		$rec->listId = $new[$folderId];
    			 
    		$pRec = cat_Products::fetch($rec->productId, 'canBuy,canSell');
    		$rec->canSell = $pRec->canSell;
    		$rec->canBuy = $pRec->canBuy;
    			 
    		$toSave[] = $rec;
    	}
    	 
    	if(count($toSave)){
    		$Detail->saveArray($toSave);
    	}
    }
    
    
    /**
     * Ъпдейт на валутите на листовете
     */
    function updateLists()
    {
    	$Lists = cls::get('cat_Listings');
    	$Lists->setupMvc();
    	
    	if(!cat_Listings::count()) return;
    	
    	$query = $Lists->getQuery();
    	$query->where('#currencyId IS NULL OR #currencyId = 0');
    	while($rec = $query->fetch()){
    	
    		$Cover = doc_Folders::getCover($rec->folderId);
    		if($Cover->haveInterface('crm_ContragentAccRegIntf')){
    			$rec->currencyId = $Cover->getDefaultCurrencyId();
    			$rec->vat = ($Cover->shouldChargeVat()) ? 'yes' : 'no';
    			
    		} else {
    			$rec->currencyId = 'BGN';
    			$rec->vat = 'yes';
    		}
    		
    		$Lists->save_($rec, 'currencyId,vat');
    	}
    }
    
    
    /**
     * Миграция на листовете
     */
    function migrateListings()
    {
    	core_App::setTimeLimit(200);
    	$Listings = cls::get('cat_ListingDetails');
    	$lQuery = $Listings->getQuery();
    	$lQuery->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');
    	$lQuery->where("#code = #reff");
    	$lQuery->show('id,listId,productId,reff,code');
    	
    	while($lRec = $lQuery->fetch()){
    		try{
    			$lRec->reff = NULL;
    			$Listings->save_($lRec);
    		} catch(core_exception_Expect $e){
    			reportException($e);
    		}
    	}
    }
    
    
    /**
     * Изчистване на кеша
     */
    function updateCatCache()
    {
    	cat_ProductTplCache::delete("#type = 'title'");
    }
}
