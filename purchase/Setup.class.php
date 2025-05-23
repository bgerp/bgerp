<?php


/**
 * Покупки до колко дни назад без да са модифицирани да се затварят автоматично
 */
defIfNot('PURCHASE_CLOSE_OLDER_THAN', 60 * 60 * 24 * 3);


/**
 * Колко покупки да се приключват автоматично брой
 */
defIfNot('PURCHASE_CLOSE_OLDER_NUM', 15);


/**
 * Колко време да се изчака след активиране на покупка, преди да се провери дали е просрочена
 */
defIfNot('PURCHASE_OVERDUE_CHECK_DELAY', 60 * 60 * 6);


/**
 * Колко време да се изчака след активиране на покупка, преди да се провери дали е просрочена
 */
defIfNot('PURCHASE_OVERDUE_CHECK_DELAY', 60 * 60 * 6);


/**
 * Дали да се въвежда курс в покупката
 */
defIfNot('PURCHASE_USE_RATE_IN_CONTRACTS', 'no');


/**
 * Срок по подразбиране за плащане на фактурата
 */
defIfNot('PURCHASE_INVOICE_DEFAULT_VALID_FOR', 60 * 60 * 24 * 3);


/**
 * Роли за добавяне на артикул в продажба от бутона 'Артикул'
 */
defIfNot('PURCHASE_ADD_BY_PRODUCT_BTN', '');


/**
 * Роли за добавяне на артикул в продажба от бутона 'Списък'
 */
defIfNot('PURCHASE_ADD_BY_LIST_BTN', '');


/**
 * Дефолтно действие при създаване на нова покупка в папка
 */
defIfNot('PURCHASE_NEW_PURCHASE_AUTO_ACTION_BTN', 'form');


/**
 * Нотификацията за нефактурирани авансови сделки
 */
defIfNot('PURCHASE_NOTIFICATION_FOR_FORGOTTEN_INVOICED_PAYMENT_DAYS', '432000');


/**
 * Дефолтно действие при създаване на нова продажба в папка
 */
defIfNot('PURCHASE_NEW_QUOTATION_AUTO_ACTION_BTN', 'form');


/**
 * Показване на ваш реф в нишката на покупката
 */
defIfNot('PURCHASE_SHOW_REFF_IN_PURCHASE_THREAD', 'no');


/**
 * Дали да се изчислява дефолтен закупчик в покупката
 */
defIfNot('PURCHASE_SET_DEFAULT_DEALER_ID', 'yes');


/**
 * Дни след "Ден от месеца за изчисляване на Счетоводна дата на входяща фактура" за приключване на валутни сделки
 */
defIfNot('PURCHASE_CURRENCY_CLOSE_AFTER_ACC_DATE', '5');


/**
 * Показване на кода на артикула в покупките в отделна колонка
 */
defIfNot('PURCHASE_SHOW_CODE_IN_SEPARATE_COLUMN', 'no');


/**
 * Над каква сума за покупка на резервни части да се пуска протокол за предаване на ремонт
 */
defIfNot('PURCHASE_ASSET_TRANSFER_REPLACEMENTS_FROM_INVOICE_ABOVE', '1000');


/**
 * Покупки - инсталиране / деинсталиране
 *
 *
 * @category  bgerp
 * @package   purchase
 *
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class purchase_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'purchase_Purchases';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Покупки - доставки на стоки, материали и консумативи';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'purchase_Purchases',
        'purchase_PurchasesDetails',
        'purchase_Services',
        'purchase_ServicesDetails',
        'purchase_ClosedDeals',
        'purchase_Invoices',
        'purchase_InvoiceDetails',
        'purchase_Vops',
        'purchase_PurchasesData',
        'purchase_Quotations',
        'purchase_QuotationDetails',
        'purchase_SparePartsProtocols',
        'purchase_SparePartsProtocolDetails',
        'purchase_SparePartsProtocolReturnedDetails',
        'migrate::fixInvoices2824',
        'migrate::forceIsPurchaseOverdue2451',
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.1, 'Логистика', 'Доставки', 'purchase_Purchases', 'default', 'purchase, ceo, acc, purchaseAll'),
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'PURCHASE_OVERDUE_CHECK_DELAY' => array('time', 'caption=Толеранс за просрочване на покупката->Време'),
        'PURCHASE_CLOSE_OLDER_THAN' => array('time(uom=days,suggestions=1 ден|2 дена|3 дена)', 'caption=Изчакване преди автоматично приключване на покупки в BGN / EUR->Дни'),
        'PURCHASE_CURRENCY_CLOSE_AFTER_ACC_DATE' => array(
            'int(Min=0)',
            'caption=Дни след "Ден от месеца за изчисляване на Счетоводна дата на входяща фактура" за приключване на валутни сделки->Дни'
        ),
        'PURCHASE_CLOSE_OLDER_NUM' => array('int', 'caption=По колко покупки да се приключват автоматично на опит->Брой'),
        'PURCHASE_USE_RATE_IN_CONTRACTS' => array('enum(no=Не,yes=Да)', 'caption=Ръчно въвеждане на курс в покупките->Избор'),
        'PURCHASE_INVOICE_DEFAULT_VALID_FOR' => array('time', 'caption=Срок за плащане по подразбиране->Срок'),
        'PURCHASE_ADD_BY_PRODUCT_BTN' => array('keylist(mvc=core_Roles,select=role,groupBy=type)', 'caption=Необходими роли за добавяне на артикули в покупка от->Артикул'),
        'PURCHASE_ADD_BY_LIST_BTN' => array('keylist(mvc=core_Roles,select=role,groupBy=type)', 'caption=Необходими роли за добавяне на артикули в покупка от->Списък'),
        'PURCHASE_NEW_PURCHASE_AUTO_ACTION_BTN' => array(
            'enum(none=Договор в "Чернова",form=Създаване на договор,addProduct=Добавяне на артикул,createProduct=Създаване на артикул,importlisted=Списък от предишни покупки)',
            'mandatory,caption=Действие на бързия бутон "Покупка" и "Оферта от доставчик" в папките->Покупка,customizeBy=ceo|sales|purchase',
         ),
        'PURCHASE_NEW_QUOTATION_AUTO_ACTION_BTN' => array(
            'enum(none=Оферта в "Чернова",form=Създаване на оферта,addProduct=Добавяне на артикул,createProduct=Създаване на артикул)',
            'mandatory,caption=Действие на бързия бутон "Покупка" и "Оферта от доставчик" в папките->Оферта от доставчик,customizeBy=ceo|sales|purchase',
        ),
        'PURCHASE_NOTIFICATION_FOR_FORGOTTEN_INVOICED_PAYMENT_DAYS' => array('time', 'caption=Нотификация за липсваща фактура за направено плащане->Време'),
        'PURCHASE_SHOW_REFF_IN_PURCHASE_THREAD' => array('enum(no=Скриване,yes=Показване)', 'caption=Показване на "Ваш реф." в документите към покупката->Избор'),
        'PURCHASE_SET_DEFAULT_DEALER_ID' => array('enum(yes=Включено,no=Изключено)', 'caption=Попълване на дефолтен закупчик в покупката->Избор'),
        'PURCHASE_SHOW_CODE_IN_SEPARATE_COLUMN' => array('enum(no=Не,yes=Да)', 'caption=Показване на кода на артикула в покупката в отделна колонка->Избор'),
        'PURCHASE_ASSET_TRANSFER_REPLACEMENTS_FROM_INVOICE_ABOVE' => array('double(Min=0)', 'caption=Над каква сума за покупка на резервни части да се пуска протокол за предаване на ремонт->Сума'),
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = array(
        array('purchase', 'invoicerPurchase'),
        array('purchaseMaster', 'purchase'),
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'purchase_PurchaseLastPricePolicy,purchase_reports_PurchasedItems,purchase_tpl_PurchaseWithTotalQuantity';


    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'Close invalid quotations from suppliers',
            'description' => 'Затваряне на остарелите оферти от доставчици',
            'controller' => 'purchase_Quotations',
            'action' => 'CloseQuotations',
            'period' => 1440,
            'timeLimit' => 360
        ),
    );


    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        $config = core_Packs::getConfig('purchase');
        
        // Добавяне на дефолтни роли за бутоните
        foreach (array('PURCHASE_ADD_BY_PRODUCT_BTN', 'PURCHASE_ADD_BY_LIST_BTN') as $const) {
            if (strlen($config->{$const}) === 0) {
                $keylist = core_Roles::getRolesAsKeylist('purchase,ceo');
                core_Packs::setConfig('purchase', array($const => $keylist));
            }
        }

        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('purQuoteFiles', 'Прикачени файлове в офертите от доставчици', null, '104857600', 'user', 'user');

        return $html;
    }


    /**
     * Поправка на входящите фактури без сч. дата
     */
    public function fixInvoices2824()
    {
        $save = array();
        $Invoices = cls::get('purchase_Invoices');
        $query = $Invoices->getQuery();
        $query->where("#state = 'active' AND #journalDate IS NULL");
        while($rec = $query->fetch()){
            $rec->journalDate = $Invoices->getDefaultAccDate($rec->date);
            $save[$rec->id] = $rec;
        }

        if(countR($save)){
            $Invoices->saveArray($save, 'id,journalDate');
        }
    }


    /**
     * Рекалкулиране на просроченото плащане
     */
    public static function forceIsPurchaseOverdue2451()
    {
        $callOn = dt::addSecs(200);
        core_CallOnTime::setOnce('core_Cron', 'forceProcess', 'IsPurchaseOverdue', $callOn);
    }
}
