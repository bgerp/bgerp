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
 * Неизползваните от колко време частни артикули да се затварят
 */
defIfNot('CAT_CLOSE_UNUSED_PRIVATE_PRODUCTS_OLDER_THEN', 7776000);


/**
 * Неизползваните от колко време стандартни артикули да се затварят
 */
defIfNot('CAT_CLOSE_UNUSED_PUBLIC_PRODUCTS_OLDER_THEN', 31104000);

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
 * Начален брояч на баркодовете
 */
defIfNot('CAT_PACKAGING_AUTO_BARCODE_BEGIN', '');


/**
 * Краен брояч на баркодовете
 */
defIfNot('CAT_PACKAGING_AUTO_BARCODE_END', '');


/**
 * Резерва при печат на етикети
 */
defIfNot('CAT_LABEL_RESERVE_COUNT', '0');


/**
 * Дефолтни папки в които да се затварят автоматично нестандартните артикули
 */
defIfNot('CAT_CLOSE_UNUSED_PUBLIC_PRODUCTS_FOLDERS', '');


/**
 * Дали дефолтно рецептите да са пълни или не
 */
defIfNot('CAT_DEFAULT_BOM_IS_COMPLETE', 'no');



/**
 * class cat_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с продуктите
 *
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cat_Setup extends core_ProtoSetup
{
    /**
     * Версията на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'cat_Products';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Каталог на стандартните артикули';
    
    
    /**
     * Необходими пакети
     */
    public $depends = 'cond=0.1';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'cat_UoM',
        'cat_Groups',
        'cat_Categories',
        'cat_Products',
        'cat_products_Params',
        'cat_products_Packagings',
        'cat_products_VatGroups',
        'cat_products_SharedInFolders',
        'cat_Serials',
        'cat_Params',
        'cat_Boms',
        'cat_BomDetails',
        'cat_ProductTplCache',
        'cat_Listings',
        'cat_ListingDetails',
        'cat_PackParams',
        'migrate::updateBoms'
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = array(
        array('listArt'),
        array('sales', 'listArt'),
        array('purchase'),
        array('packEdit'),
        array('catEdit', 'packEdit'),
        array('cat', 'catEdit'),
        array('catImpEx'),
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(1.42, 'Артикули', 'Каталог', 'cat_Products', 'default', 'powerUser'),
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'cat_GeneralProductDriver,cat_ImportedProductDriver';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'CAT_BOM_REMEMBERED_RESOURCES' => array('int', 'caption=Колко от последно изпозлваните ресурси да се показват в рецептите->Брой'),
        'CAT_DEFAULT_META_IN_CONTRAGENT_FOLDER' => array('set(canSell=Продаваем,canBuy=Купуваем,canStore=Складируем,canConvert=Вложим,fixedAsset=Дълготраен актив,canManifacture=Производим)', 'caption=Свойства по подразбиране в папка->На клиент,columns=2'),
        'CAT_DEFAULT_META_IN_SUPPLIER_FOLDER' => array('set(canSell=Продаваем,canBuy=Купуваем,canStore=Складируем,canConvert=Вложим,fixedAsset=Дълготраен актив,canManifacture=Производим)', 'caption=Свойства по подразбиране в папка->На доставчик,columns=2'),
        'CAT_DEFAULT_MEASURE_ID' => array('key(mvc=cat_UoM,select=name,allowEmpty)', 'optionsFunc=cat_UoM::getUomOptions,caption=Основна мярка на артикулите->Мярка'),
        'CAT_BOM_MAX_COMPONENTS_LEVEL' => array('int(min=0)', 'caption=Вложени рецепти - нива с показване на компонентите->Макс. брой'),
        'CAT_WAC_PRICE_PERIOD_LIMIT' => array('int(min=1)', array('caption' => 'До колко периода назад да се търси складова себестойност, ако няма->Брой')),
        'CAT_DEFAULT_PRICELIST' => array('key(mvc=price_Lists,select=title,allowEmpty)', 'caption=Ценова политика по подразбиране->Избор,mandatory'),
        'CAT_AUTO_LIST_PRODUCT_COUNT' => array('int(min=1)', 'caption=Списъци от последно продавани артикули->Брой,customizeBy=label'),
        'CAT_AUTO_LIST_ALLOWED_GROUPS' => array('keylist(mvc=cat_Groups,select=name)', 'caption=Списъци от последно продавани артикули->Групи'),
        'CAT_SHOW_BOM_IN_PRODUCT' => array('enum(auto=Автоматично,product=В артикула,job=В заданието,yes=Навсякъде,no=Никъде)', 'caption=Показване на рецептата в описанието на артикула->Показване'),
        'CAT_PACKAGING_AUTO_BARCODE_BEGIN' => array('gs1_TypeEan', 'caption=Автоматични баркодове на опаковките->Начало'),
        'CAT_PACKAGING_AUTO_BARCODE_END' => array('gs1_TypeEan', 'caption=Автоматични баркодове на опаковките->Край'),
        'CAT_LABEL_RESERVE_COUNT' => array('percent(min=0,max=1)', 'caption=Печат на етикети на опаковки->Резерва'),
        'CAT_CLOSE_UNUSED_PRIVATE_PRODUCTS_OLDER_THEN' => array('time', 'caption=Затваряне на стари нестандартни артикули->Неизползвани от'),
        'CAT_CLOSE_UNUSED_PUBLIC_PRODUCTS_OLDER_THEN' => array('time', 'caption=Затваряне на неизползвани стандартни артикули->Създадени преди'),
        'CAT_CLOSE_UNUSED_PUBLIC_PRODUCTS_FOLDERS' => array('keylist(mvc=doc_Folders,select=title)', 'caption=Затваряне на неизползвани стандартни артикули->Само в папките'),
        'CAT_DEFAULT_BOM_IS_COMPLETE' => array('enum(yes=Пълни,no=Непълни)', 'caption=Дали рецептите по подразбиране са завършени->Избор'),
    );
    
    
    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'Close Old Private Products',
            'description' => 'Затваряне на неизползваните нестандартни артикули',
            'controller' => 'cat_Products',
            'action' => 'closePrivateProducts',
            'period' => 21600,
            'offset' => 60,
            'timeLimit' => 900
        ),
        
        array(
            'systemId' => 'Update Auto Sales List',
            'description' => 'Обновяване на листовете с продажби',
            'controller' => 'cat_Listings',
            'action' => 'UpdateAutoLists',
            'period' => 1440,
            'offset' => 60,
            'timeLimit' => 200
        ),
        
        array(
            'systemId' => 'Update Groups Cnt',
            'description' => 'Обновяване броячите на групите',
            'controller' => 'cat_Products',
            'action' => 'UpdateGroupsCnt',
            'period' => 1440,
            'offset' => 1327,
            'timeLimit' => 20
        ),
    
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('productsImages', 'Илюстрация на продукта', 'jpg,jpeg,png,bmp,gif,image/*', '3MB', 'user', 'every_one');
        
        return $html;
    }
    
    
    /**
     * Менижиране на формата формата за настройките
     *
     * @param core_Form $configForm
     * @return void
     */
    public function manageConfigDescriptionForm(&$configForm)
    {
        $suggestions = doc_Folders::getOptionsByCoverInterface('cat_ProductFolderCoverIntf');
        $configForm->setSuggestions('CAT_CLOSE_UNUSED_PUBLIC_PRODUCTS_FOLDERS', $suggestions);
    }


    /**
     * Обновява рецептите
     */
    public function updateBoms()
    {
        $Bom = cls::get('cat_Boms');
        if(!$Bom->count()) return;

        // Обновява полето за завършеност на рецептата
        $isCompleteColName = str::phpToMysqlName('isComplete');
        $query = "UPDATE {$Bom->dbTableName} SET {$isCompleteColName} = 'auto'";
        $Bom->db->query($query);
    }
}
