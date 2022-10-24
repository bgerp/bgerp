<?php


/**
 * Начален номер на фактурите
 */
defIfNot('SALES_TRANSPORT_PRODUCTS_ID', '');


/**
 * Дефолтна валидност на офертата
 */
defIfNot('SALES_DEFAULT_VALIDITY_OF_QUOTATION', '2592000');


/**
 * Сумиране на статистически данни
 */
defIfNot('SALES_STATISTIC_DATA_FOR_THE_LAST', 6 * core_DateTime::SECONDS_IN_MONTH);


/**
 * Начален номер на фактурите
 */
defIfNot('SALE_INV_MIN_NUMBER1', '1');


/**
 * Групи за делта
 */
defIfNot('SALES_DELTA_CAT_GROUPS', '');


/**
 * Колко време след като не е платена една продажба, да се отбелязва като просрочена
 */
defIfNot('SALES_OVERDUE_CHECK_DELAY', 60 * 60 * 6);


/**
 * Колко дена да се изчаква преди да се затворят миналите еднократни маршрути
 */
defIfNot('SALES_ROUTES_CLOSE_DELAY', 3);


/**
 * Колко време да се изчака след активиране на продажба, да се приключва автоматично
 */
defIfNot('SALE_CLOSE_OLDER_THAN', 60 * 60 * 24 * 3);


/**
 * Срок по подразбиране за плащане на фактурата
 */
defIfNot('SALES_INVOICE_DEFAULT_VALID_FOR', 60 * 60 * 24 * 3);


/**
 * Колко продажби да се приключват автоматично брой
 */
defIfNot('SALE_CLOSE_OLDER_NUM', 50);


/**
 * Кой да е по подразбиране драйвера за фискален принтер
 */
defIfNot('SALE_INV_VAT_DISPLAY', 'no');


/**
 * Системата върана ли е с касови апарати или не
 */
defIfNot('SALE_INV_HAS_FISC_PRINTERS', 'yes');


/**
 * Дефолтен шаблон за продажби на български
 */
defIfNot('SALE_SALE_DEF_TPL_BG', '');


/**
 * Дефолтен шаблон за продажби на английски
 */
defIfNot('SALE_SALE_DEF_TPL_EN', '');


/**
 * Дефолтен шаблон за фактури на български
 */
defIfNot('SALE_INVOICE_DEF_TPL_BG', '');


/**
 * Дефолтен шаблон за фактури на английски
 */
defIfNot('SALE_INVOICE_DEF_TPL_EN', '');


/**
 * Дали да се въвежда курс в продажбата
 */
defIfNot('SALES_USE_RATE_IN_CONTRACTS', 'no');


/**
 * Дали да се въвежда курс в продажбата
 */
defIfNot('SALE_INVOICES_SHOW_DEAL', 'yes');


/**
 * Роли за добавяне на артикул в продажба от бутона 'Артикул'
 */
defIfNot('SALES_ADD_BY_PRODUCT_BTN', '');



/**
 * Роли за добавяне на артикул в продажба от бутона 'Създаване'
 */
defIfNot('SALES_ADD_BY_CREATE_BTN', '');


/**
 * Роли за добавяне на артикул в продажба от бутона 'Списък'
 */
defIfNot('SALES_ADD_BY_LIST_BTN', '');


/**
 * Роли за добавяне на артикул в продажба от бутона 'Импорт'
 */
defIfNot('SALES_ADD_BY_IMPORT_BTN', '');


/**
 * Дължина на името на артикула в продажбата
 */
defIfNot('SALES_PROD_NAME_LENGTH', '20');


/**
 * % Неснижаема делта по дефолт
 */
defIfNot('SALES_DELTA_MIN_PERCENT', '');


/**
 * % от продажната цена за себестойността, когато е 0
 */
defIfNot('SALES_DELTA_MIN_PERCENT_PRIME_COST', '0.2');


/**
 * Да се изчислява ли себестойноста на делтата на ЕН и СР лайв
 */
defIfNot('SALES_LIVE_CALC_SO_DELTAS', 'no');


/**
 * Дефолтно действие при създаване на нова продажба в папка
 */
defIfNot('SALES_NEW_SALE_AUTO_ACTION_BTN', 'form');


/**
 * Дефолтно действие при създаване на нова продажба в папка
 */
defIfNot('SALES_NEW_QUOTATION_AUTO_ACTION_BTN', 'form');


/**
 * Политика за предупреждение за минимални цени
 */
defIfNot('SALES_MIN_PRICE_POLICY', '');


/**
 * Нотификацията за нефактурирани авансови сделки
 */
defIfNot('SALES_NOTIFICATION_FOR_FORGOTTEN_INVOICED_PAYMENT_DAYS', '432000');


/**
 * Място за фактура
 */
defIfNot('SALES_DEFAULT_LOCATION_FOR_INVOICE', '');


/**
 * Показване на ваш реф в нишката на продажбата
 */
defIfNot('SALES_SHOW_REFF_IN_SALE_THREAD', 'no');


/**
 * Дали да се изчислява дефолтен търговец в продажбата
 */
defIfNot('SALES_SET_DEFAULT_DEALER_ID', 'yes');


/**
 * Дали да се изчислява дефолтен търговец в продажбата
 */
defIfNot('SALES_SHOW_PRICE_IN_PRODUCT_SELECTION', 'no');


/**
 * Дни след "Ден от месеца за изчисляване на Счетоводна дата на входяща фактура" за приключване на валутни сделки
 */
defIfNot('SALES_CURRENCY_CLOSE_AFTER_ACC_DATE', '5');

/**
 * Дефолтен текст на циркулярен имейл за просрочени фактури
 */
defIfNot('SALES_DEFAULT_BLAST_BODY_BG',  "Здравейте,\nС настоящото бихме желали да Ви обърнем внимание, че към [#date#] година имате просрочия по фактури:\n[#docs#]\nна обща стойност [#sum#] [#currency#]\nМолим във възможно най-кратки срокове да погасите цитираното задължение.\nВ случай, че вече сте направили плащане по посочените документи, молим да ни извините и да игнорирате настоящото писмо.\n\nТова е автоматично генерирано съобщение, което не изисква отговор. За допълнителни въпроси и информация моля, свържете се с наш представител.\n\nС уважение");

/**
 * Дефолтен subject на циркулярен имейл за просрочени фактури
 */
defIfNot('SALES_DEFAULT_BLAST_SUBJECT_BG', "Просрочени задължения към [#date#]
");

/**
 * Дефолтен текст на циркулярен имейл за просрочени фактури на английски
 */
defIfNot('SALES_DEFAULT_BLAST_BODY_EN',  "Hello,\nThis letter is to bring to your attention that as of [#date#] no payment has been received for the invoices\nlisted below [#docs#],\ntotal value [#sum#] [#currency#].\n\nWe kindly ask you to promptly settle the outstanding invoices.\n\nIn case you have already made payment, please excuse us and ignore this reminder.\n\nThis letter is automatically generated that does not require a response. For additional questions and information, please contact our representative.\n\nWith respect");

/**
 * Дефолтен subject на циркулярен имейл за просрочени фактури на английски
 */
defIfNot('SALES_DEFAULT_BLAST_SUBJECT_EN', "Dunning Notice");


/**
 * Непродавани от кога артикули да се считат за нови
 */
defIfNot('SALES_DELTA_NEW_PRODUCT_TO', 1);


/**
 * Непродавани от кога артикули да се считат за нови
 */
defIfNot('SALES_DELTA_NEW_PRODUCT_FROM', 12 * dt::SECONDS_IN_MONTH);


/**
 * Продажби - инсталиране / деинсталиране
 *
 *
 * @category bgerp
 * @package sales
 *
 * @author Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license GPL 3
 *
 * @since v 0.1
 */
class sales_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'sales_Sales';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Управление на продажби';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'SALES_OVERDUE_CHECK_DELAY' => array(
            'time',
            'caption=Толеранс за просрочване на продажбата->Време'
        ),
        'SALE_CLOSE_OLDER_THAN' => array(
            'time(uom=days,suggestions=1 ден|2 дена|3 дена)',
            'caption=Изчакване преди автоматично приключване на продажби в BGN / EUR->Дни'
        ),
        'SALES_CURRENCY_CLOSE_AFTER_ACC_DATE' => array(
            'int(Min=0)',
            'caption=Дни след "Ден от месеца за изчисляване на Счетоводна дата на изходяща фактура" за приключване на валутни сделки->Дни'
        ),
        'SALE_CLOSE_OLDER_NUM' => array(
            'int',
            'caption=По колко продажби да се приключват автоматично на опит->Брой'
        ),
        'SALE_INV_VAT_DISPLAY' => array(
            'enum(no=Не,yes=Да)',
            'caption=Без закръгляне на ДДС за всеки ред от фактурите->Избор'
        ),
        'SALE_INV_HAS_FISC_PRINTERS' => array(
            'enum(no=Не,yes=Да)',
            'caption=Има ли фирмата касови апарати->Избор'
        ),
        'SALE_SALE_DEF_TPL_BG' => array(
            'key(mvc=doc_TplManager,allowEmpty)',
            'caption=Продажба основен шаблон->Български,optionsFunc=sales_Sales::getTemplateBgOptions'
        ),
        'SALE_SALE_DEF_TPL_EN' => array(
            'key(mvc=doc_TplManager,allowEmpty)',
            'caption=Продажба основен шаблон->Английски,optionsFunc=sales_Sales::getTemplateEnOptions'
        ),
        'SALE_INVOICE_DEF_TPL_BG' => array(
            'key(mvc=doc_TplManager,allowEmpty)',
            'caption=Фактура основен шаблон->Български,optionsFunc=sales_Invoices::getTemplateBgOptions'
        ),
        'SALE_INVOICE_DEF_TPL_EN' => array(
            'key(mvc=doc_TplManager,allowEmpty)',
            'caption=Фактура основен шаблон->Английски,optionsFunc=sales_Invoices::getTemplateEnOptions'
        ),
        'SALE_INVOICES_SHOW_DEAL' => array(
            'enum(auto=Автоматично,no=Никога,yes=Винаги)',
            'caption=Показване на сделката в описанието на фактурата->Избор'
        ),
        'SALES_USE_RATE_IN_CONTRACTS' => array(
            'enum(no=Не,yes=Да)',
            'caption=Ръчно въвеждане на курс в продажбите->Избор'
        ),
        'SALES_INVOICE_DEFAULT_VALID_FOR' => array(
            'time',
            'caption=Срок за плащане по подразбиране->Срок'
        ),
        'SALES_ADD_BY_PRODUCT_BTN' => array(
            'keylist(mvc=core_Roles,select=role,groupBy=type)',
            'caption=Необходими роли за добавяне на артикули в продажба от->Артикул'
        ),
        'SALES_ADD_BY_CREATE_BTN' => array(
            'keylist(mvc=core_Roles,select=role,groupBy=type)',
            'caption=Необходими роли за добавяне на артикули в продажба от->Създаване'
        ),
        'SALES_ADD_BY_LIST_BTN' => array(
            'keylist(mvc=core_Roles,select=role,groupBy=type)',
            'caption=Необходими роли за добавяне на артикули в продажба от->Списък'
        ),
        'SALES_ADD_BY_IMPORT_BTN' => array(
            'keylist(mvc=core_Roles,select=role,groupBy=type)',
            'caption=Необходими роли за добавяне на артикули в продажба от->Импорт'
        ),
        'SALES_DELTA_CAT_GROUPS' => array(
            'keylist(mvc=cat_Groups,select=name)',
            'caption=Групи продажбени артикули за изчисляване на ТРЗ индикатори->Групи'
        ),
        'SALES_ROUTES_CLOSE_DELAY' => array(
            'int(min=1)',
            'caption=Изчакване преди да се затворят изпълнените търговски маршрути->Дни'
        ),
        'SALES_DEFAULT_VALIDITY_OF_QUOTATION' => array(
            'time',
            'caption=Оферти->Валидност'
        ),
    
        'SALES_PROD_NAME_LENGTH' => array(
            'int(min=0)',
            'caption=Дължина на артикула в името на продажбата->Дължина, customizeBy=powerUser'
        ),
        
        'SALES_LIVE_CALC_SO_DELTAS' => array(
            'enum(no=Договор,yes=ЕН/СР)',
            'caption=Записване на себестойност за изчисляване на делти при контиране на->Избор'
        ),
        
        'SALES_DELTA_MIN_PERCENT' => array(
            'percent',
            'caption=Неснижаема делта->Стойност'
        ),
        
        'SALES_DELTA_MIN_PERCENT_PRIME_COST' => array(
            'percent',
            'caption=Колко % от продажната цена да се приема за делта при липса на себестойност->Стойност'
        ),
        
        'SALES_TRANSPORT_PRODUCTS_ID' => array(
            'keylist(mvc=cat_Products,select=name)',
            'mandatory,caption=Транспорт->Артикули,optionsFunc=sales_Setup::getPossibleTransportProducts'
        ),
        
        'SALES_NEW_SALE_AUTO_ACTION_BTN' => array(
            'enum(none=Договор в "Чернова",form=Създаване на договор,addProduct=Добавяне на артикул,createProduct=Създаване на артикул,importlisted=Списък от предишни продажби)',
            'mandatory,caption=Действие на бързите бутони "Продажба" и "Оферта" в папките->Продажба,customizeBy=ceo|sales|purchase',
        ),
        
        'SALES_NEW_QUOTATION_AUTO_ACTION_BTN' => array(
            'enum(none=Оферта в "Чернова",form=Създаване на оферта,addProduct=Добавяне на артикул,createProduct=Създаване на артикул)',
            'mandatory,caption=Действие на бързите бутони "Продажба" и "Оферта" в папките->Оферта,customizeBy=ceo|sales',
        ),
        'SALES_STATISTIC_DATA_FOR_THE_LAST' => array('time', 'caption=Изчисляване на рейтинги за продажба->Време назад'),
    
        'SALES_MIN_PRICE_POLICY' => array('key(mvc=price_Lists,select=title,allowEmpty)', 'caption=Ценова политика за минимални цени->Избор'),

        'SALES_NOTIFICATION_FOR_FORGOTTEN_INVOICED_PAYMENT_DAYS' => array('time', 'caption=Нотификация за нефактурирано получено плащане ("0" за изключване)->Време'),
        'SALES_DEFAULT_LOCATION_FOR_INVOICE' => array('key(mvc=crm_Locations,select=title,allowEmpty)', 'caption=Настройки на дефолта за фактура и проформа->Локация,customizeBy=ceo|sales|invoicer,optionsFunc=crm_Locations::getOwnLocations'),
        'SALES_SHOW_REFF_IN_SALE_THREAD' => array('enum(no=Скриване,yes=Показване)', 'caption=Показване на "Ваш реф." в документите към продажба->Избор'),
        'SALES_SET_DEFAULT_DEALER_ID' => array('enum(yes=Включено,no=Изключено)', 'caption=Попълване на дефолтен търговец в продажбите->Избор'),
        'SALES_SHOW_PRICE_IN_PRODUCT_SELECTION' => array('enum(no=Изключено,measureId=Основна мярка,basePack=Избраната за основна мярка/опаковка)', 'caption=Показване на продажната цена при избор на артикул в документи->Избор'),

        'SALES_DEFAULT_BLAST_BODY_BG' => array('richtext(rows=5,bucket=Blast)', 'caption=Текст на циркулярен имейл за просрочени плащания->На български, customizeBy=blast'),
        'SALES_DEFAULT_BLAST_SUBJECT_BG' => array('varchar', 'caption=Subject на циркулярен имейл за просрочени плащания->На български, customizeBy=blast'),
        'SALES_DEFAULT_BLAST_BODY_EN' => array('richtext(rows=5,bucket=Blast)', 'caption=Текст на циркулярен имейл за просрочени плащания->На английски, customizeBy=blast'),
        'SALES_DEFAULT_BLAST_SUBJECT_EN' => array('varchar', 'caption=Subject на циркулярен имейл за просрочени плащания->На английски, customizeBy=blast'),

        'SALES_DELTA_NEW_PRODUCT_FROM' => array('time', 'caption=Непродавани артикули от колко време да се считат за нов артикул->От,unit=назад'),
        'SALES_DELTA_NEW_PRODUCT_TO' => array('int(Min=0)', 'caption=Непродавани артикули от колко време да се считат за нов артикул->До,unit=месец(а) назад'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'sales_Sales',
        'sales_SalesDetails',
        'sales_Routes',
        'sales_Quotations',
        'sales_QuotationsDetails',
        'sales_ClosedDeals',
        'sales_Services',
        'sales_ServicesDetails',
        'sales_Invoices',
        'sales_InvoiceDetails',
        'sales_Proformas',
        'sales_ProformaDetails',
        'sales_PrimeCostByDocument',
        'sales_TransportValues',
        'sales_ProductRelations',
        'sales_ProductRatings',
        'sales_LastSaleByContragents',
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(
            3.1,
            'Търговия',
            'Продажби',
            'sales_Sales',
            'default',
            'sales, ceo, acc, saleAll'
        )
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'sales_SalesLastPricePolicy, 
                       sales_reports_ShipmentReadiness,sales_reports_PurBomsRep,sales_reports_OverdueByAdvancePayment,
                       sales_reports_VatOnSalesWithoutInvoices,sales_reports_SoldProductsRep, sales_reports_PriceDeviation,
                       sales_reports_OverdueInvoices,sales_reports_SalesByContragents,sales_interface_FreeRegularDelivery,
                       sales_reports_PriceComparison,sales_tpl_InvoiceHeaderEuro,sales_tpl_InvoiceAccView';
    
    
    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'Close invalid quotations',
            'description' => 'Затваряне на остарелите оферти',
            'controller' => 'sales_Quotations',
            'action' => 'CloseQuotations',
            'period' => 1440,
            'timeLimit' => 360
        ),
        array(
            'systemId' => 'Update Routes Next Visit',
            'description' => 'Изчисляване на посещенията на търговските маршрути',
            'controller' => 'sales_Routes',
            'action' => 'calcNextVisit',
            'offset' => 140,
            'period' => 1440,
            'timeLimit' => 360
        ),
        array(
            'systemId' => 'Calc Near Products',
            'description' => 'Изчисляване на търговска близост между продуктите',
            'controller' => 'sales_ProductRelations',
            'action' => 'CalcNearProducts',
            'offset' => 190,
            'period' => 1440,
            'timeLimit' => 360
        ),
        array(
            'systemId' => 'Gather Sale Statistic',
            'description' => 'Изчисляване на рейтинги на артикулите в продажбите',
            'controller' => 'sales_ProductRatings',
            'action' => 'CalcRating',
            'offset' => 190,
            'period' => 1440,
            'timeLimit' => 500
        )
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = array(
        array(
            'sales',
            'invoicer,seePrice,dec'
        ),
        array(
            'salesMaster',
            'sales'
        )
    );
    
    
    /**
     * Кои артикули могат да се избират като транспорт
     *
     * @return array $suggestions - списък с артикули
     */
    public static function getPossibleTransportProducts()
    {
        $suggestions = array();
        $pQuery = cat_Products::getQuery();
        $pQuery->where("#canStore = 'no'");
        $pQuery->show('name');
        
        while ($pRec = $pQuery->fetch()) {
            $suggestions[$pRec->id] = $pRec->name;
        }
        
        return $suggestions;
    }
    
    
    /**
     * Зареждане на данни
     */
    public function loadSetupData($itr = '')
    {
        $res = parent::loadSetupData($itr);
        
        // Ако няма посочени от потребителя сметки за синхронизация
        $config = core_Packs::getConfig('sales');
        
        // Поставяме първия намерен шаблон на български за дефолтен на продажбата
        if (strlen($config->SALE_SALE_DEF_TPL_BG) === 0) {
            $key = key(sales_Sales::getTemplateBgOptions());
            core_Packs::setConfig('sales', array(
                'SALE_SALE_DEF_TPL_BG' => $key
            ));
        }

        // Поставяме първия намерен шаблон на английски за дефолтен на продажбата
        if (strlen($config->SALE_SALE_DEF_TPL_EN) === 0) {
            $key = key(sales_Sales::getTemplateEnOptions());
            core_Packs::setConfig('sales', array(
                'SALE_SALE_DEF_TPL_EN' => $key
            ));
        }
        
        // Поставяме първия намерен шаблон на български за дефолтен на фактурата
        if (strlen($config->SALE_INVOICE_DEF_TPL_BG) === 0) {
            $key = key(sales_Invoices::getTemplateBgOptions());
            core_Packs::setConfig('sales', array(
                'SALE_INVOICE_DEF_TPL_BG' => $key
            ));
        }
        
        // Поставяме първия намерен шаблон на английски за дефолтен на фактурата
        if (strlen($config->SALE_INVOICE_DEF_TPL_EN) === 0) {
            $key = key(sales_Invoices::getTemplateEnOptions());
            core_Packs::setConfig('sales', array(
                'SALE_INVOICE_DEF_TPL_EN' => $key
            ));
        }
        
        // Добавяне на дефолтни роли за бутоните
        foreach (array(
            'SALES_ADD_BY_PRODUCT_BTN',
            'SALES_ADD_BY_CREATE_BTN',
            'SALES_ADD_BY_LIST_BTN',
            'SALES_ADD_BY_IMPORT_BTN'
        ) as $const) {
            if (strlen($config->{$const}) === 0) {
                $keylist = core_Roles::getRolesAsKeylist('sales,ceo');
                core_Packs::setConfig('sales', array(
                    $const => $keylist
                ));
            }
        }
        
        // Ако няма посочени от потребителя сметки за синхронизация
        if (strlen($config->SALES_TRANSPORT_PRODUCTS_ID) === 0) {
            $transportId = cat_Products::fetchField("#code = 'transport'", 'id');
            if ($transportId) {
                $products = array(
                    $transportId => $transportId
                );
                
                core_Packs::setConfig('sales', array(
                    'SALES_TRANSPORT_PRODUCTS_ID' => keylist::fromArray($products)
                ));
                $res .= "<li style='color:green'>Добавени са дефолтни артикули за транспорт</b></li>";
            }
        }
        
        return $res;
    }
}
