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
     * Необходими пакети
     */
    public $depends = 'peripheral=0.1';
    
    
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
}
