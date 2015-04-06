<?php



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
    var $info = "Каталог на стандартни продукти";
    
    
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
    		'migrate::updateProducts',
    		'migrate::updateProducts3',
    		'migrate::migrateMetas',
    		'migrate::migrateGroups',
    		'migrate::migrateProformas',
    		'migrate::makeProductsDocuments2',
    		'migrate::removeOldParams1',
    		'migrate::updateDocs',
    		'migrate::fixStates',
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
     * Дефинирани класове, които имат интерфейси
     */
    var $defClasses = "cat_GeneralProductDriver, cat_BaseImporter";
    
    
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
     * Миграция за продуктовите драйвъри
     */
    function updateProducts()
    {
    	$cQuery = cat_Products::getQuery();
    	
    	core_Classes::add('cat_GeneralProductDriver');
    	
    	$technoDriverId = cat_GeneralProductDriver::getClassId();
    	
    	while($pRec = $cQuery->fetch()){
    		$meta = cat_Products::getMetaData($pRec->groups);
    		$meta = arr::make($meta, TRUE);
    		
    		$pRec->innerClass = $technoDriverId;
    		
    		$clone = clone $pRec;
    		unset($clone->innerForm, $clone->innerState);
    		
    		$pRec->innerForm = $clone;
    		$pRec->innerState = $clone;
    		
    		cat_Products::save($pRec, 'innerClass,innerForm,innerState');
    	}
    	
    	$pQuery = cat_products_Params::getQuery();
    	$cId = cat_Products::getClassId();
    	while($pRec = $pQuery->fetch()){
    		$pRec->classId = $cId;
    		cat_products_Params::save($pRec);
    	}
    }
    
    
    /**
     * Миграция за продуктовите драйвъри
     */
    function updateProducts3()
    {
    	$pQuery = cat_Products::getQuery();
    	while($rec = $pQuery->fetch()){
    		if(cls::load($rec->innerClass, TRUE)){
    			try{
    				$rec->innerForm->photo = $rec->photo;
    				$rec->innerState->photo = $rec->photo;
    				 
    				cat_Products::save($rec);
    			} catch(core_exception_Expect $e){
    				
    			}
    		}
    	}
    }
    
    
    /**
     * Миграция от старите мета данни към новите
     */
    public function migrateMetas()
    {
    	if(!cat_Products::count()) return;
    	
    	set_time_limit(600);
    	
    	$Products = cls::get('cat_Products');
    	$query = $Products->getQuery();
    	$query->where("#groups IS NOT NULL");
    	
    	$Set = cls::get('type_Set');
    	
    	while($rec = $query->fetch()){
    		$meta = cat_Products::getMetaData($rec->groups);
    		$metaArr = type_Set::toArray($meta);
    		
    		if(isset($metaArr['materials'])){
    			unset($metaArr['materials']);
    			$metaArr['canStore'] = 'canStore';
    			$metaArr['canConvert'] = 'canConvert';
    		}
    		
    		foreach ($metaArr as $metaName){
    			$rec->$metaName = 'yes';
    		}
    		
    		$rec->meta = $Set->fromVerbal($metaArr);
    		
    		$Products->save_($rec);
    	}
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
     * Прави всички артикули документи в папката на първата им група,
     * ако нямат отиват в папката на 'Услуги'
     */
    public function makeProductsDocuments2()
    {
    	set_time_limit(900);
    	
    	core_Classes::add('cat_Categories');
    	$Products = cls::get('cat_Products');
    	$query = cat_Products::getQuery();
    	$query->where("#threadId IS NULL");
    	
    	while($rec = $query->fetch()){
    		$first = NULL;
    		try {
    			if(isset($rec->groups)){
    				$groups = keylist::toArray($rec->groups);
    				foreach ($groups as $index => $gr){
    					if($sysId = cat_Groups::fetchField($gr, 'sysId')){
    						$first = cat_Categories::fetchField("#sysId = '{$sysId}'", 'id');
    						break;
    					}
    				}
    				
    				if(empty($first)){
    					if($rec->createdBy == -1){
    						$first = cat_Categories::fetchField("#sysId = 'externalServices'", 'id');
    					} else {
    						$first = cat_Categories::fetchField("#sysId = 'goods'", 'id');
    					}
    				}
    			} else {
    				$first = cat_Categories::fetchField("#sysId = 'services'", 'id');
    			}
    			
    			
    			if(core_Classes::getId('cat_GeneralServiceDriver') == $rec->innerClass){
    				$rec->innerClass = cat_GeneralProductDriver::getClassId();
    			}
    			
    			$rec->folderId = cat_Categories::forceCoverAndFolder($first);
    			$Products->route($rec);
    			$Products->save($rec);
    		} catch(core_exception_Expect $e){
    			$Products->log("Проблем при прехвърлянето на артикул: {$rec->name}: {$e->getMessage()}");
    		}
    	}
    	
    	if(core_Packs::fetch("#name = 'techno2'")){
    		$allProducts = array();
    		$manId = $Products->getClassId();
    		$specId = techno2_SpecificationDoc::getClassId();
    		$tQuery = techno2_SpecificationDoc::getQuery();
    		$tQuery->where("#state != 'rejected'");
    		
    		core_Users::cancelSystemUser();
    		while($tRec = $tQuery->fetch()){
    			core_Users::sudo($tRec->createdBy);
    			
    			try{
    				$pId = techno2_SpecificationDoc::createProduct($tRec);
    				$allProducts[$tRec->id] = $pId;
    				
    				$paramQ = cat_products_Params::getQuery();
    				$paramQ->where("#classId = {$specId} AND #productId = {$tRec->id}");
    				
    				while($pRec = $paramQ->fetch()){
    					$pRec->classId = $manId;
    					$pRec->productId = $pId;
    					
    					cat_products_Params::save($pRec, 'productId,classId');
    				}
    			} catch(core_exception_Expect $e){
    				$Products->log("Проблем при прехвърляне на спецификация {$tRec->id}: {$e->getMessage()}");
    			}
    			
    			core_Users::exitSudo();
    		}
    		core_Users::forceSystemUser();
    	}

    	$docsArr = array('sales_SalesDetails', 'sales_InvoiceDetails', 'store_ShipmentOrderDetails', 'store_ReceiptDetails', 'sales_ServicesDetails', 'purchase_InvoiceDetails', 'purchase_PurchasesDetails', 'purchase_ServicesDetails', 'sales_QuotationsDetails');
    	if(count($allProducts)){
    		foreach ($allProducts as $sId => $pId){
    				
    			try{
    				if($itemRec = acc_Items::fetchItem('techno2_SpecificationDoc', $sId)){
    					$itemRec->classId = $manId;
    					$itemRec->objectId = $pId;
    					acc_Items::save($itemRec);
    				}
    				
    				foreach ($docsArr as $manName){
    					$dQuery = $manName::getQuery();
    					$dQuery->where("#classId = {$specId} AND #productId = {$sId}");
    					while($dRec = $dQuery->fetch()){
    						$dRec->classId = $manId;
    						$dRec->productId = $pId;
    						$manName::save($dRec);
    					}
    				}
    			} catch(core_exception_Expect $e){
    				$Products->log("Проблем при Заместване на спецификация {$tRec->id}: {$e->getMessage()}");
    			}
    			
    		}
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
     * Поправя грешните състояния
     */
    public function fixStates()
    {
    	$Products = cls::get('cat_Products');
    	
    	$query = $Products->getQuery();
    	$query->where("#state IS NULL || (#brState IS NULL AND #state = 'rejected')");
    	$query->show('id,name,state,brState');
    	while($rec = $query->fetch()){
    		if(is_null($rec->state)){
    			$rec->state = 'active';
    		}
    		
    		if($rec->state == 'rejected' && is_null($rec->brState)){
    			$rec->brState = 'active';
    		}
    		
    		$Products->save_($rec, 'state,brState');
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
}
