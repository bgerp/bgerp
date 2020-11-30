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
 * @copyright 2006 - 2016 Experta OOD
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
        'migrate::updateProductCodes2',
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
        'migrate::updateIntName',
        'migrate::updateBrState',
        'migrate::updateEmptyEanCode'
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
     * Миграция на имената на артикулите
     */
    public function updateIntName()
    {
        $Products = cls::get('cat_Products');
        $Products->setupMvc();
        
        if (!cat_Products::count()) {
            
            return;
        }
        
        core_App::setTimeLimit(700);
        $toSave = array();
        $query = cat_Products::getQuery();
        $query->where("LOCATE('||', #name)");
        $query->show('name,nameEn');
        while ($rec = $query->fetch()) {
            $exploded = explode('||', $rec->name);
            if (countR($exploded) == 2) {
                $rec->name = $exploded[0];
                $rec->nameEn = $exploded[1];
                $toSave[$rec->id] = $rec;
            }
        }
        
        if (countR($toSave)) {
            $Products->saveArray($toSave, 'id,name,nameEn');
        }
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
     * Обновяване на предишното състояние на грешно създадените артикули
     */
    public function updateBrState()
    {
        $Products = cls::get('cat_Products');
        $Products->setupMvc();
        
        $toSave = array();
        $pQuery = $Products->getQuery();
        $pQuery->where("#state = 'closed' AND #brState = 'closed'");
        $pQuery->show('brState');
        while($pRec = $pQuery->fetch()){
            $pRec->brState = 'active';
            $toSave[] = $pRec;
        }
        
        if(countR($toSave)){
            $Products->saveArray($toSave, 'id,brState');
        }
    }
    
    
    /**
     * Миграция на празните баркодове
     */
    public function updateEmptyEanCode()
    {
        if(!cat_products_Packagings::count()) return;
        
        $Packs = cls::get('cat_products_Packagings');
        $eanCol = str::phpToMysqlName('eanCode');
        $query = "UPDATE {$Packs->dbTableName} SET {$eanCol} = '' WHERE {$eanCol} IS NULL";
        $Packs->db->query($query);
    }
    
    
    /**
     * Миграция на кодовете
     */
    public function updateProductCodes2()
    {
        $Products = cls::get('cat_Products');
        
        if ($Products->db->tableExists('cat_products')){
            $Products->setupMvc();
            $updateRecs = $saveRecs = array();
            
            $query = cat_Products::getQuery();
            $query->XPR('normCode', 'varchar', 'LOWER(#code)');
            $query->XPR('count', 'varchar', 'COUNT(#id)');
            $query->show('normCode');
            $query->where("#count > 1 AND #code IS NOT NULL");
            $query->groupBy("normCode");
            $query->orderBy("id", 'DESC');
            
            $duplicatedCodes = arr::extractValuesFromArray($query->fetchAll(), 'normCode');
            
            $query = $Products->getQuery();
            $query->XPR('normCode', 'varchar', 'LOWER(#code)');
            $query->where("#code IS NOT NULL");
            $query->show('code,normCode');
            $query->orderBy('id', 'DESC');
            while($rec = $query->fetch()){
                $updateRecs[$rec->id] = $rec;
            }
            
            if(!countR($updateRecs)) return;
            
            $count = countR($updateRecs);
            core_App::setTimeLimit($count * 0.2, false, 100);
            
            foreach ($updateRecs as &$uRec){
                if(!array_key_exists($uRec->normCode, $duplicatedCodes)) continue;
                
                // Проверява се има ли записи със същото уникално поле
                $foundRec = array_filter($updateRecs, function ($a) use ($uRec) {return $a->normCode == $uRec->normCode && $a->id != $uRec->id;});
                
                if(countR($foundRec)){
                    
                    // Отново се проверява дали е уникално
                    $loop = true;
                    while($loop){
                       
                        // Ако има то се инкрементира
                        $uRec->code = str::addIncrementSuffix($uRec->code, '_');
                        $uRec->normCode = mb_strtolower($uRec->code);
                        $foundRec = array_filter($updateRecs, function ($a) use ($uRec) {return $a->normCode == $uRec->normCode && $a->id != $uRec->id;});
                       
                        if(!countR($foundRec)){
                            $loop = false;
                        }
                    }
                    
                    $saveRecs[$uRec->id] = $uRec;
                }
            }
            
            if(!countR($saveRecs)) return;
            
            $Products->saveArray($saveRecs, "id, code");
        }
    }
}
