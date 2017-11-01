<?php


/**
 * Да се показвали рецептата в описанието на артикула
 */
defIfNot('CAT_SHOW_BOM_IN_PRODUCT', 'auto');


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
            'CAT_SHOW_BOM_IN_PRODUCT'               => array("enum(auto=Автоматично,yes=Да,no=Не)", 'caption=Показване на рецептата в описанието на артикула->Показване'),
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
}
