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
            'cat_products_Files',
    		'cat_products_VatGroups',
            'cat_Params',
            'cat_Packagings',
    		'cat_Boms',
    		'cat_BomStages',
    		'cat_BomStageDetails',
    		'migrate::updateProducts',
    		'migrate::updateProducts3',
    		'migrate::migrateMetas',
    		'migrate::migrateGroups',
    		'migrate::makeProductsDocuments2',
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'cat';
 
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(1.42, 'Артикули', 'Каталог', 'cat_Products', 'default', "powerUser, ceo"),
        );
    

    /**
     * Дефинирани класове, които имат интерфейси
     */
    var $defClasses = "cat_GeneralProductDriver";
    
    
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
    		if($rec->innerClass){
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
    	set_time_limit(600);
    	core_Users::cancelSystemUser();
    	
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
    			
    			if(core_Classes::fetchIdByName('cat_GeneralServiceDriver') == $rec->innerClass){
    				$rec->innerClass = cat_GeneralProductDriver::getClassId();
    			}
    			
    			$rec->folderId = cat_Groups::forceCoverAndFolder($first);
    			$Products->route($rec);
    			$Products->save($rec);
    		} catch(core_exception_Expect $e){
    			$Products->log("Проблем при прехвърлянето на артикул: {$rec->name}: {$e->getMessage()}");
    		}
    	}
    	
    	if(core_Packs::fetch("#name = 'techno2'")){
    		$tQuery = techno2_SpecificationDoc::getQuery();
    		$tQuery->where("#state != 'rejected'");
    		while($tRec = $tQuery->fetch()){
    			core_Users::sudo($tRec->createdBy);
    			
    			try{
    				techno2_SpecificationDoc::createProduct($tRec);
    			} catch(core_exception_Expect $e){
    				$Products->log("Проблем при прехвърляне на спецификация {$tRec->id}: {$e->getMessage()}");
    			}
    			
    			core_Users::exitSudo();
    		}
    	}
    	
    	core_Users::forceSystemUser();
    }
}
