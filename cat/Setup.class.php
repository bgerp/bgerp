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
            'cat_Products',
            'cat_products_Params',
            'cat_products_Packagings',
            'cat_products_Files',
    		'cat_products_VatGroups',
            'cat_Params',
            'cat_Packagings',
    		'migrate::updateProducts',
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'cat';
 
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(1.42, 'Артикули', 'Каталог', 'cat_Products', 'default', "cat, ceo"),
        );
    

    /**
     * Дефинирани класове, които имат интерфейси
     */
    var $defClasses = "cat_GeneralProductDriver, cat_GeneralServiceDriver";
    
    
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
    	core_Classes::add('cat_GeneralServiceDriver');
    	
    	$technoDriverId = cat_GeneralProductDriver::getClassId();
    	$technoDriverServiceId = cat_GeneralServiceDriver::getClassId();
    	
    	while($pRec = $cQuery->fetch()){
    		$meta = cat_Products::getMetaData($pRec->groups);
    		$meta = arr::make($meta, TRUE);
    		
    		if(isset($meta['canStore'])){
    			$pRec->innerClass = $technoDriverId;
    		} else {
    			$pRec->innerClass = $technoDriverServiceId;
    		}
    		
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
}
