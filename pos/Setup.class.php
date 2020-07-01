<?php


/**
 *  Параметри на продукти, които да се показват при търсене
 */
defIfNot('POS_RESULT_PRODUCT_PARAMS', '');


/**
 *  Колко цифри от края на бележката да се показват в номера и
 */
defIfNot('POS_SHOW_RECEIPT_DIGITS', 4);


/**
 *  Колко свързани артикула да се показват до избрания
 */
defIfNot('POS_TERMINAL_MAX_SEARCH_PRODUCT_RELATIONS', 4);


/**
 * Колко артикула от последните продажби да се показват
 */
defIfNot('POS_TERMINAL_MAX_SEARCH_PRODUCT_LAST_SALE', 10);


/**
 *  Колко отчета да приключват автоматично на опит
 */
defIfNot('POS_CLOSE_REPORTS_PER_TRY', 30);


/**
 *  Автоматично приключване на отчети по стари от
 */
defIfNot('POS_CLOSE_REPORTS_OLDER_THAN', 60 * 60 * 24 * 2);


/**
 *  Показване на бутона за цената в терминала
 */
defIfNot('POS_TERMINAL_PRICE_CHANGE', 'yes');


/**
 *  Продаване на неналични артикули през ПОС-а
 */
defIfNot('POS_ALLOW_SALE_OF_PRODUCTS_NOT_IN_STOCK', 'yes');


/**
 *  Под каква ширина да се смята за тесен режим
 */
defIfNot('POS_MIN_WIDE_WIDTH', '1200');


/**
 *  Максимален брой търсения на контрагенти в терминала
 */
defIfNot('POS_TERMINAL_MAX_SEARCH_CONTRAGENTS', '20');


/**
 *  Максимален брой търсения на артикули в терминала
 */
defIfNot('POS_TERMINAL_MAX_SEARCH_PRODUCTS', '30');


/**
 *  Максимален брой търсения на артикули в терминала
 */
defIfNot('POS_TERMINAL_MAX_SEARCH_RECEIPTS', '20');


/**
 *  Звук за добавяне в терминала
 */
defIfNot('POS_TERMINAL_ADD_SOUND', 'click');


/**
 *  Звук за редакция в терминала
 */
defIfNot('POS_TERMINAL_EDIT_SOUND', 'click');


/**
 *  Звук за изтриване в терминала
 */
defIfNot('POS_TERMINAL_DELETE_SOUND', 'delete1');


/**
 *  След колко секунди да сработва търсенето в резултатите на терминала
 */
defIfNot('POS_TERMINAL_SEARCH_SECONDS', 2000);


/**
 *  Време на изпълнение на периодичния процес за обновяване на Top 100
 */
defIfNot('POS_TOP_100_PERIOD', 86400);


/**
 * Модул "Точки на продажба" - инсталиране/деинсталиране
 *
 *
 * @category  bgerp
 * @package   pos
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pos_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'pos_Points';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Управление на точки за продажба в магазин';
    
    
    /**
     * Необходими пакети
     */
    public $depends = 'cat=0.1,peripheral=0.1';
    
    
    /**
     * Описание на конфигурационните константи за този модул
     */
    public $configDescription = array(
        'POS_RESULT_PRODUCT_PARAMS' => array('keylist(mvc=cat_Params,select=name)', 'caption=Параметри за показване търсене на продукт->Параметри,columns=2'),
        'POS_SHOW_RECEIPT_DIGITS' => array('double', 'caption=Цифри показващи се цифри от кода на бележката->Брой'),
        'POS_CLOSE_REPORTS_PER_TRY' => array('int(min=0)', 'caption=По колко отчета да се приключват автоматично на опит->Брой,columns=2'),
        'POS_CLOSE_REPORTS_OLDER_THAN' => array('time(uom=days,suggestions=1 ден|2 дена|3 дена)', 'caption=Автоматично приключване на отчети по стари от->Дни'),
        'POS_TERMINAL_PRICE_CHANGE' => array('enum(yes=Разрешено,no=Забранено)', 'caption=Операции в POS терминала->Промяна на цена'),
        'POS_TERMINAL_MAX_SEARCH_CONTRAGENTS' => array('int(min=0)', 'caption=Операции в POS терминала->Брой на намерени контрагенти'),
        'POS_TERMINAL_MAX_SEARCH_PRODUCTS' => array('int(min=0)', 'caption=Операции в POS терминала->Брой на намерени артикули'),
        'POS_TERMINAL_MAX_SEARCH_RECEIPTS' => array('int(min=0)', 'caption=Операции в POS терминала->Брой на намерени бележки'),
        'POS_TERMINAL_MAX_SEARCH_PRODUCT_RELATIONS' => array('int(min=0)', 'caption=Операции в POS терминала->Брой намерени свързани артикули'),
        'POS_TERMINAL_MAX_SEARCH_PRODUCT_LAST_SALE' => array('int(min=0)', 'caption=Операции в POS терминала->Брой намерени последни продажби'),
        'POS_TERMINAL_SEARCH_SECONDS' => array('int(min=500)', 'caption=Операции в POS терминала->Търсене след,unit=милисекунди'),
        'POS_ALLOW_SALE_OF_PRODUCTS_NOT_IN_STOCK' => array('enum(yes=Включено,no=Изключено)', 'caption=Продажба на неналични артикули->Избор'),
        'POS_MIN_WIDE_WIDTH' => array('int', 'caption=Под каква ширина да се смята за тесен режим->Под,unit=px'),
        'POS_TERMINAL_ADD_SOUND' => array('enum(click=Клик (1),mouseclick=Клик (2),tap=Клик (3),terminal=Скенер (1),terminal2=Скенер (2))', 'caption=Звуци в терминала->Добавяне'),
        'POS_TERMINAL_EDIT_SOUND' => array('enum(click=Клик (1),mouseclick=Клик (2),tap=Клик (3),terminal=Скенер (1),terminal2=Скенер (2))', 'caption=Звуци в терминала->Редактиране'),
        'POS_TERMINAL_DELETE_SOUND' => array('enum(crash=Изтриване (1),delete1=Изтриване (2),filedelete=Изтриване (3))', 'caption=Звуци в терминала->Изтриване'),
        'POS_TOP_100_PERIOD' => array('time', 'caption=Време за изпълнение на периодичните процеси->Top 100'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'pos_Points',
        'pos_Receipts',
        'pos_ReceiptDetails',
        'pos_Reports',
        'pos_Stocks',
        'migrate::migrateCronSettings',
        'migrate::updateStoreIdInReceipts',
        'migrate::updateBrState',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = array(
        array('pos'),
        array('posMaster', 'pos'),
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.1, 'Търговия', 'POS', 'pos_Points', 'default', 'pos, ceo'),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('pos_ProductsImages', 'Снимки', 'jpg,jpeg,image/jpeg,gif,png', '6MB', 'user', 'every_one');
        
        // Залагаме в cron
        $rec = new stdClass();
        $rec->systemId = 'Update Pos Top 100';
        $rec->description = 'Обновява най-продаваните артикули';
        $rec->controller = 'pos_Setup';
        $rec->action = 'UpdatePosTop100';
        $rec->period = static::get('TOP_100_PERIOD') / 60;
        $rec->offset = 120;
        $rec->timeLimit = 200;
        
        $html .= core_Cron::addOnce($rec);
        
        return $html;
    }
    
    
    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'Close reports',
            'description' => 'Затваряне на ПОС отчети',
            'controller' => 'pos_Reports',
            'action' => 'CloseReports',
            'period' => 1440,
            'offset' => 1380,
            'timeLimit' => 100,
        ),
        array(
            'systemId' => 'Update Pos statistic',
            'description' => 'Обновява статистическите данни в POS-а',
            'controller' => 'pos_Setup',
            'action' => 'UpdateStatistic',
            'period' => 1440,
            'offset' => 1320,
            'timeLimit' => 100,
        ),
    );
    
    
    /**
     * Класове за зареждане
     */
    public $defClasses = 'pos_Terminal';
    
    
    /**
     * Обновяване на предишното състояние на грешно създадените артикули
     */
    public function updateBrState()
    {
        $Reports = cls::get('pos_Reports');
        $Reports->setupMvc();
        
        $toSave = array();
        $pQuery = $Reports->getQuery();
        $pQuery->where("#state = 'closed' AND #brState != 'active'");
        $pQuery->show('brState');
        while($pRec = $pQuery->fetch()){
            $pRec->brState = 'active';
            $toSave[] = $pRec;
        }
        
        if(countR($toSave)){
            $Reports->saveArray($toSave, 'id,brState');
        }
    }
    
    
    /**
     * Миграция на крон процеса
     */
    public function migrateCronSettings()
    {
        if ($cronRec = core_Cron::getRecForSystemId('Close reports')) {
            if ($cronRec->offset != 1380) {
                $cronRec->offset = 1380;
                core_Cron::save($cronRec, 'offset');
            }
        }
    }
    
    
    /**
     * Добавя склада към реда
     */
    public function updateStoreIdInReceipts()
    {
        cls::get('pos_Points')->setupMvc();
        $Details = cls::get('pos_ReceiptDetails');
        $Details->setupMvc();
        cls::get('pos_Receipts')->setupMvc();
        
        if(!pos_ReceiptDetails::count()) return;
        
        $toSave = array();
        $query = pos_ReceiptDetails::getQuery();
        $query->EXT('pointId', 'pos_Receipts', 'externalName=pointId,externalKey=receiptId');
        $query->where("#storeId IS NULL AND #action = 'sale|code'");
        $query->show('id,pointId,storeId');
        while($rec = $query->fetch()){
            $rec->storeId = pos_Points::fetchField($rec->pointId, 'storeId');
            $toSave[] = $rec;
        }
        
        if(countR($toSave)){
            $Details->saveArray($toSave, 'storeId,id');
        }
    }
    
    
    /**
     * Обновява статистическите данни в POS-а
     */
    public function cron_UpdateStatistic()
    {
        pos_ReceiptDetails::getMostUsedTexts(24, true);
    }
    
    
    /**
     * Обновява статистическите данни в POS-а
     */
    public function cron_UpdatePosTop100()
    {
        // Кои са POS групите
        $topGroupId = cat_Groups::fetch("#sysId = 'topPos100'")->id;
        $topGroupFatherId = cat_Groups::fetch("#sysId = 'posProducts'")->id;
        
        // За всяка бележка, намират се най-продаваните 100 артикула
        $receiptQuery = pos_ReceiptDetails::getQuery();
        $receiptQuery->EXT('state', 'pos_Receipts', 'externalName=state,externalKey=receiptId');
        $receiptQuery->EXT('groupsInput', 'cat_Products', 'externalName=groupsInput,externalKey=productId');
        $receiptQuery->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');
        $receiptQuery->XPR('count', 'int', 'count(#id)');
        $receiptQuery->where("#state != 'draft' && #state != 'rejected'");
        $receiptQuery->show('productId,groups,groupsInput');
        $receiptQuery->groupBy('productId');
        $receiptQuery->orderBy("count", 'DESC');
        $receiptQuery->limit(100);
        
        // Те ще се добавят в групата за Топ 100 най-продавани
        $products = $existingProducts = $topKeys = array();
        while($receiptRec = $receiptQuery->fetch()){
            $topKeys[$receiptRec->productId] = $receiptRec->productId;
            if(keylist::isIn($topGroupId, $receiptRec->group)) continue;
            
            $receiptRec->groupsInput = keylist::addKey($receiptRec->groupsInput, $topGroupId);
            $receiptRec->groups = keylist::addKey($receiptRec->groups, $topGroupId);
            $receiptRec->groups = keylist::addKey($receiptRec->groups, $topGroupFatherId);
            $products[$receiptRec->productId] = (object)array('id' => $receiptRec->productId, 'groups' => $receiptRec->groups, 'groupsInput' => $receiptRec->groupsInput);
        }
        
        // Ако има артикули, които са в тази група, но вече не влизат в Топ 100 те се махат
        $pQuery = cat_Products::getQuery();
        $pQuery->where("#groups LIKE '%|{$topGroupId}|%'");
        $pQuery->show('id,groups,groupsInput');
        if(countR($products)){
            $pQuery->notIn("id", array_keys($topKeys));
        }
        
        while($pRec = $pQuery->fetch()){
            $pRec->groupsInput = keylist::removeKey($pRec->groupsInput, $topGroupId);
            $pRec->groupsInput = !empty($pRec->groupsInput) ? $pRec->groupsInput : null;
            $pRec->groups = keylist::removeKey($pRec->groups, $topGroupId);
            $pRec->groups = keylist::removeKey($pRec->groups, $topGroupFatherId);
            $pRec->groups = !empty($pRec->groups) ? $pRec->groups : null;
            $existingProducts[$pRec->id] = $pRec;
        }
        
        // Записване на най-продаваните артикули
        $Products = cls::get('cat_Products');
        if(countR($products)){
            $Products->saveArray($products, 'id,groups,groupsInput');
        }
        
        if(countR($existingProducts)){
            $Products->saveArray($existingProducts, 'id,groups,groupsInput');
        }
    }
}
