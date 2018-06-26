<?php


/**
 * Колко секунди да се кешира съдържанието за не PowerUsers
 */
defIfNot('ESHOP_BROWSER_CACHE_EXPIRES', 3600);


/**
 * Минимален брой групи, необходими за да се покаже страничната навигация
 */
defIfNot('ESHOP_MIN_GROUPS_FOR_NAVIGATION', 4);


/**
 * Име на кошницата във външната част
 */
defIfNot('ESHOP_CART_EXTERNAL_NAME', 'Количка');


/**
 * Текст в магазина ако артикулът не е наличен
 */
defIfNot('ESHOP_NOT_IN_STOCK_TEXT', 'Няма наличност');


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
class eshop_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версията на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'eshop_Groups';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Уеб каталог с продукти и услуги за сайта";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'eshop_Groups',
            'eshop_Products',
    		'eshop_Settings',
    		'eshop_ProductDetails',
    		'eshop_Carts',
    		'eshop_CartDetails',
            'migrate::migrateProductOrdering',
            'migrate::migrateGroupOrdering',
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'eshop';
 
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.55, 'Сайт', 'Е-маг', 'eshop_Groups', 'default', "ceo, eshop"),
        );
    
    
    /**
	 * Описание на конфигурационните константи
	 */
	var $configDescription = array(
         'ESHOP_BROWSER_CACHE_EXPIRES' => array ('time', 'caption=Кеширане в браузъра->Време'),
         'ESHOP_MIN_GROUPS_FOR_NAVIGATION' => array ('int', 'caption=Минимален брой групи за навигация->Брой'),
	     'ESHOP_CART_EXTERNAL_NAME' => array ('varchar', 'caption=Стрингове във външната част->Кошница'),
		 'ESHOP_NOT_IN_STOCK_TEXT' => array ('varchar', 'caption=Стрингове във външната част->Липса на наличност'),
	);
	
	
	/**
	 * Настройки за Cron
	 */
	public $cronSettings = array(
			array(
					'systemId' => "Delete Carts",
					'description' => "Изтриване на старите колички",
					'controller' => "eshop_Carts",
					'action' => "DeleteDraftCarts",
					'period' => 1440,
					'offset' => 60,
					'timeLimit' => 100
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
        $html .= $Bucket->createBucket('eshopImages', 'Илюстрации в емаг', 'jpg,jpeg,png,bmp,gif,image/*', '10MB', 'user', 'every_one');
        
        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->installPlugin('Разширяване на външната част за онлайн магазина', 'eshop_plg_External', 'cms_page_External', 'private');
        $html .= $Plugins->installPlugin('Разширяване на потребителите свързана с външната част', 'eshop_plg_Users', 'core_Users', 'private');
        
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
     * Миграция за подредбата на продуктите
     */
    public static function migrateProductOrdering()
    {
        $mvc = cls::get('eshop_Products');

        $query = $mvc->getQuery();
        $query->FLD('order', 'int', 'caption=Подредба');

        $query->orderBy("#order,#code");
         
        $i = array();
        $cnt = 0;
        while($rec = $query->fetch()) {
            if(!isset($i[$rec->groupId])) {
                $i[$rec->groupId] = 1;
            }
            $rec->saoOrder = $i[$rec->groupId]++;
            $rec->saoLevel = 1;
            $mvc->save_($rec, 'saoOrder, saoLevel');
            $cnt++;
        }
 
        return "<li>Мигрирана подредбата на eshop продукти: " . $cnt;    
    }

    /**
     * Миграция за подредбата на продуктите
     */
    public static function migrateGroupOrdering()
    {
        $mvc = cls::get('eshop_Groups');

        $query = $mvc->getQuery();
        
        $i = array();
        $cnt = 0;
        while($rec = $query->fetch()) {
            if(!isset($i[$rec->menuId])) {
                $i[$rec->menuId] = 1;
            }
            $rec->saoOrder = $i[$rec->menuId]++;
            $rec->saoLevel = 1;
            $mvc->save_($rec, 'saoOrder, saoLevel');
            $cnt++;
        }
 
        return "<li>Мигрирана подредбата на eshop групи: " . $cnt;
    }

}
