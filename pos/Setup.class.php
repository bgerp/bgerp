<?php

/**
 *  Tемата по-подразбиране за пос терминала
 */
defIfNot('POS_PRODUCTS_DEFAULT_THEME', 'pos_DefaultTheme');


/**
 *  Параметри на продукти, които да се показват при търсене
 */
defIfNot('POS_RESULT_PRODUCT_PARAMS', '');


/**
 *  Колко цифри от края на бележката да се показват в номера и
 */
defIfNot('POS_SHOW_RECEIPT_DIGITS', 4);


/**
 *  Колко отчета да приключват автоматично на опит
 */
defIfNot('POS_CLOSE_REPORTS_PER_TRY', 30);


/**
 *  Автоматично приключване на отчети по стари от
 */
defIfNot('POS_CLOSE_REPORTS_OLDER_THAN', 60 * 60 * 24 * 2);


/**
 *  Показване на бутона за отстъпка в терминала
 */
defIfNot('POS_SHOW_DISCOUNT_BTN', 'yes');


/**
 *  Продаване на неналични артикули през ПОС-а
 */
defIfNot('POS_ALLOW_SALE_OF_PRODUCTS_NOT_IN_STOCK', 'yes');


/**
 * Модул "Точки на продажба" - инсталиране/деинсталиране
 *
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
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
            'POS_PRODUCTS_DEFAULT_THEME' => array('class(interface=pos_ThemeIntf,select=title)', 'caption=Tемата по-подразбиране за пос терминала->Тема'),
            'POS_RESULT_PRODUCT_PARAMS' => array('keylist(mvc=cat_Params,select=name)', 'caption=Параметри за показване търсене на продукт->Параметри,columns=2'),
            'POS_SHOW_RECEIPT_DIGITS' => array('double', 'caption=Цифри показващи се цифри от кода на бележката->Брой'),
            'POS_CLOSE_REPORTS_PER_TRY' => array('int', 'caption=По колко отчета да се приключват автоматично на опит->Брой,columns=2'),
            'POS_CLOSE_REPORTS_OLDER_THAN' => array('time(uom=days,suggestions=1 ден|2 дена|3 дена)', 'caption=Автоматично приключване на отчети по стари от->Дни'),
            'POS_SHOW_DISCOUNT_BTN' => array('enum(yes=Показване,no=Скриване)', 'caption=Показване на бутони в терминала->Отстъпка'),
            'POS_ALLOW_SALE_OF_PRODUCTS_NOT_IN_STOCK' => array('enum(yes=Включено,no=Изключено)', 'caption=Продажба на неналични артикули->Избор'),
    );
    

    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
            'pos_Points',
            'pos_Receipts',
            'pos_ReceiptDetails',
            'pos_Favourites',
            'pos_FavouritesCategories',
            'pos_Reports',
            'pos_Stocks',
            'pos_Cards',
            'migrate::updateReceipts',
        );
    

    /**
     * Роли за достъп до модула
     */
    public $roles = 'pos';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
            array(3.1, 'Търговия', 'POS', 'pos_Points', 'default', 'pos, ceo'),
        );
    
    
    /**
     * Път до js файла
     */
//    var $commonJS = 'pos/js/scripts.js';
    
    
    /**
     * Път до css файла
     */
//    var $commonCSS = 'pos/tpl/css/styles.css';
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
                                
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('pos_ProductsImages', 'Снимки', 'jpg,jpeg,image/jpeg,gif,png', '6MB', 'user', 'every_one');
         
        // Добавяме класа връщащ темата в core_Classes
        $html .= core_Classes::add('pos_DefaultTheme');
        
        // Добавяне на роля за старши пос
        $html .= core_Roles::addOnce('posMaster', 'pos');
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
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
                'offset' => 60,
                'timeLimit' => 100,
            ),
            array(
                    'systemId' => 'Update Pos Buttons Group',
                    'description' => 'Обновяване на групите на категориите на бързите бутони',
                    'controller' => 'pos_Favourites',
                    'action' => 'UpdateButtonsGroup',
                    'period' => 10,
                    'offset' => 0,
                    'timeLimit' => 100,
            ),
    );
    
    
    /**
     * Миграция
     */
    public static function updateReceipts()
    {
        pos_ReceiptDetails::delete("#action IS NULL OR (#action NOT LIKE 'sale|%' AND #action NOT LIKE 'payment|%' AND #action NOT LIKE 'discount|%') ");
    }
}
