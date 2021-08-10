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
        'purchase_Offers',
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
        'migrate::updateInvoiceJournalDate',
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
        'PURCHASE_CLOSE_OLDER_THAN' => array('time(uom=days,suggestions=1 ден|2 дена|3 дена)', 'caption=Изчакване преди автоматично приключване на покупката->Дни'),
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
        'PURCHASE_NOTIFICATION_FOR_FORGOTTEN_INVOICED_PAYMENT_DAYS' => array('time', 'caption=Нотификацията за нефактурирани авансови сделки->Време'),
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = array(
        array('purchase', 'invoicer,seePrice'),
        array('purchaseMaster', 'purchase'),
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'purchase_PurchaseLastPricePolicy,purchase_reports_PurchasedItems';


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
     * Мигриране на сч. дата на активираните ф-ри ако е празна
     */
    public function updateInvoiceJournalDate()
    {
        $Invoices = cls::get('purchase_Invoices');
        if (!$Invoices->count()) return;

        $stateColName = str::phpToMysqlName('state');
        $dateFieldName = str::phpToMysqlName('date');
        $journalDateFieldName = str::phpToMysqlName('journalDate');

        $query = "UPDATE {$Invoices->dbTableName} SET {$Invoices->dbTableName}.{$journalDateFieldName} = {$Invoices->dbTableName}.{$dateFieldName} WHERE {$Invoices->dbTableName}.{$journalDateFieldName} IS NULL AND ({$Invoices->dbTableName}.{$stateColName} = 'active' OR {$Invoices->dbTableName}.{$stateColName} = 'stopped')";
        $Invoices->db->query($query);
    }


    /**
     * Миграция на старите оферти към новите
     */
    function migrateOldQuotes()
    {
        $db = new core_Db();
        if (!$db->tableExists('purchase_offers')) return;

        $OldQuote = cls::get('purchase_Offers');
        $oldQuoteCount = $OldQuote->count();
        if(!$oldQuoteCount) return;

        $Quotations = cls::get('purchase_Quotations');
        $query = $OldQuote->getQuery();
        $query->where("#state != ''");

        core_App::setTimeLimit($oldQuoteCount * 0.6, false, 300);
        while($rec = $query->fetch()){
            $Cover = doc_Folders::getCover($rec->folderId);
            if($Cover->haveInterface('crm_ContragentAccRegIntf')){

                $others = "";
                if(!empty($rec->product)){
                    $others .= "Продукт: {$rec->product}" . "\n";
                }

                if(!empty($rec->sum)){
                    $others .= "Цена: {$rec->sum}" . "\n";
                }

                if(!empty($rec->offer)){
                    $others .= "Детайли: {$rec->offer}" . "\n";
                }

                if(!empty($rec->documentId)){
                    $others .= "Документ: [file={$rec->documentId}][/file]";
                }

                $fields = array();
                $date = !empty($rec->date) ? $rec->date : null;
                if(!empty($others)){
                    $fields['others'] = $others;
                }

                $cancelLater = false;
                if(!core_Users::isSystemUser()){
                    core_Users::forceSystemUser();
                    $cancelLater = true;
                }

                $quoteId = purchase_Quotations::createNewDraft($Cover->getClassId(), $Cover->that, $date, $fields);
                purchase_Quotations::logWrite('Автоматично прехвърляне на стара оферта');
                if($cancelLater){
                    core_Users::cancelSystemUser();
                }

                $quoteRec = purchase_Quotations::fetch($quoteId);
                doc_Linked::add($quoteRec->containerId, $rec->containerId, 'doc', 'doc', 'Прехвърляне на стара оферта');

                if($rec->state == 'active'){
                    $quoteRec->state = 'active';
                    $Quotations->save($quoteRec, 'state');
                    $Quotations->invoke('AfterActivation', array($quoteRec));
                }

                doc_Threads::doUpdateThread($quoteRec->threadId);
            }
        }
    }
}
