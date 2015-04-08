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
}
