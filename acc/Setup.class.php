<?php


/**
 * Задължителен параметър за експорт на ф-ра
 */
defIfNot('ACC_INVOICE_MANDATORY_EXPORT_PARAM', '');


/**
 * Колко дена преди края на месеца да се направи следващия бъдещ период чакащ
 */
defIfNot('ACC_DAYS_BEFORE_MAKE_PERIOD_PENDING', '86400');


/**
 * Стойност по подразбиране на актуалния ДДС (между 0 и 1)
 * Използва се по време на инициализацията на системата, при създаването на първия период
 */
defIfNot('ACC_DEFAULT_VAT_RATE', 0.20);


/**
 * Стойност по подразбиране на актуалния ДДС (между 0 и 1)
 * Използва се по време на инициализацията на системата, при създаването на първия период
 */
defIfNot('BASE_CURRENCY_CODE', 'BGN');


/**
 * Кои документи могат да са разходни пера
 */
defIfNot('ACC_COST_OBJECT_DOCUMENTS', '');


/**
 * Толеранс за допустимо разминаване на суми
 */
defIfNot('ACC_MONEY_TOLERANCE', '0.05');


/**
 * Колко реда да се показват в детайлния баланс
 */
defIfNot('ACC_DETAILED_BALANCE_ROWS', 500);


/**
 * Основание за неначисляване на ДДС за контрагент контрагент от държава в ЕС (без България)
 */
defIfNot('ACC_VAT_REASON_IN_EU', 'чл.53 от ЗДДС – ВОД');


/**
 * Основание за неначисляване на ДДС за контрагент извън ЕС
 */
defIfNot('ACC_VAT_REASON_OUTSIDE_EU', 'чл.28 от ЗДДС – износ/внос извън ЕС');


/**
 * Основание за неначисляване на ДДС ако "Моята фирма" не е регистрирана по ДДС
 */
defIfNot('ACC_VAT_REASON_MY_COMPANY_NO_VAT', 'чл.113, ал. 9 от ЗДДС');


/**
 * Роли за всички при филтриране
 */
defIfNot('ACC_SUMMARY_ROLES_FOR_ALL', 'ceo,admin');


/**
 * Роли за екипите при филтриране
 */
defIfNot('ACC_SUMMARY_ROLES_FOR_TEAMS', 'ceo,admin,manager');


/**
 * Ден от месеца за изчисляване на Счетоводна дата на входяща фактура
 */
defIfNot('ACC_DATE_FOR_INVOICE_DATE', '10');


/**
 * Какво количество автоматично да се попълва в корекцията от закръгляния
 */
defIfNot('ACC_BALANCE_REPAIR_QUANTITY_BELLOW', '0,00999');


/**
 * Каква сума автоматично да се попълва в корекцията от закръгляния
 */
defIfNot('ACC_BALANCE_REPAIR_AMOUNT_BELLOW', '0,00999');


/**
 * Кои сметки автоматично да се попълвавт в корекцията от закръгляния
 */
defIfNot('ACC_BALANCE_REPAIR_ACCOUNTS', '');


/**
 * Да се използват ли дефолтите за корекцията от стойност
 */
defIfNot('ACC_BALANCE_REPAIR_NO_DEFAULTS', 'no');


/**
 * Колко назад във времето ще се инвалидират балансите
 */
defIfNot('ACC_ALTERNATE_WINDOW', '');


/**
 * Захранване на стратегия с отрицателни крайни салда
 */
defIfNot('ACC_FEED_STRATEGY_WITH_NEGATIVE_QUANTITY', 'yes');


/**
 * class acc_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани със счетоводството
 *
 *
 * @category bgerp
 * @package acc
 *
 * @author Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license GPL 3
 *
 * @since v 0.1
 */
class acc_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Необходими пакети
     */
    public $depends = 'currency=0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'acc_Lists';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Двустранно счетоводство: Настройки, Журнали';
    
    
    /**
     * Дефолтни сметки за добавяне към документа за корекция от грешки
     */
    protected static $accAccount = '321,323,401,411,61101,6911,6912,699,701,703,706,7911,7912';


    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'acc_Lists',
        'acc_Items',
        'acc_Periods',
        'acc_Accounts',
        'acc_Limits',
        'acc_Balances',
        'acc_BalanceDetails',
        'acc_Articles',
        'acc_ArticleDetails',
        'acc_Journal',
        'acc_JournalDetails',
        'acc_Features',
        'acc_VatGroups',
        'acc_ClosePeriods',
        'acc_Operations',
        'acc_BalanceRepairs',
        'acc_BalanceRepairDetails',
        'acc_BalanceTransfers',
        'acc_ValueCorrections',
        'acc_FeatureTitles',
        'acc_CostAllocations',
        'acc_RatesDifferences',
        'migrate::updatePriceRoles2247',
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'ACC_MONEY_TOLERANCE' => array(
            'double(decimals=2)',
            'caption=Автоматично приключване на сделка при салдо (в основна валута) под->Сума'
        ),
        'ACC_DETAILED_BALANCE_ROWS' => array(
            'int',
            'caption=Редове в страница от детайлния баланс->Брой редове,unit=бр.'
        ),
        'ACC_DAYS_BEFORE_MAKE_PERIOD_PENDING' => array(
            'time(suggestions= 1 ден|2 дена|7 Дена)',
            'caption=Колко дни преди края на месеца да се направи следващия бъдещ период чакащ->Дни'
        ),
        'ACC_VAT_REASON_OUTSIDE_EU' => array(
            'varchar',
            'caption=Основание за неначисляване на ДДС ако "Моята фирма" е с ДДС номер->Контрагент (Извън ЕС)'
        ),
        'ACC_VAT_REASON_IN_EU' => array(
            'varchar',
            'caption=Основание за неначисляване на ДДС ако "Моята фирма" е с ДДС номер->Контрагент (ЕС)'
        ),
        'ACC_VAT_REASON_MY_COMPANY_NO_VAT' => array(
            'varchar',
            'caption=Основание за неначисляване на ДДС ако моята фирма е без ДДС номер->Избор'
        ),

        'ACC_COST_OBJECT_DOCUMENTS' => array(
            'keylist(mvc=core_Classes,select=name)',
            'caption=Кои документи могат да бъдат разходни обекти->Документи,optionsFunc=acc_Setup::getDocumentOptions'
        ),
        'ACC_SUMMARY_ROLES_FOR_TEAMS' => array(
            'varchar',
            'caption=Роли за екипите при филтриране->Роли'
        ),
        'ACC_SUMMARY_ROLES_FOR_ALL' => array(
            'varchar',
            'caption=Роли за всички при филтриране->Роли'
        ),
        'ACC_DATE_FOR_INVOICE_DATE' => array(
            'int(min=1,max=31)',
            'caption=Ден от месеца за изчисляване на Счетоводна дата на входяща фактура->Ден'
        ),
        'ACC_INVOICE_MANDATORY_EXPORT_PARAM' => array(
            'key(mvc=cat_Params,select=name,allowEmpty)',
            'caption=Артикул за експорт на данъчна фактура->Параметър'
        ),
        'ACC_BALANCE_REPAIR_NO_DEFAULTS' => array(
            'enum(yes=Да,no=Не)',
            'caption=Корекция на грешки от закръгляне->Празен документ'
        ),
        'ACC_BALANCE_REPAIR_ACCOUNTS' => array(
            'acc_type_accounts',
            'caption=Корекция на грешки от закръгляне->Сметки'
        ),
        'ACC_BALANCE_REPAIR_QUANTITY_BELLOW' => array(
            'double',
            'caption=Корекция на грешки от закръгляне->Количество под'
        ),
        'ACC_BALANCE_REPAIR_AMOUNT_BELLOW' => array(
            'double',
            'caption=Корекция на грешки от закръгляне->Сума под'
        ),
        'ACC_FEED_STRATEGY_WITH_NEGATIVE_QUANTITY' => array(
            'enum(no=Не,yes=Да)',
            'caption=Захранване на стратегия WAC с отрицателни начални салда->Избор'
        ),
        'ACC_ALTERNATE_WINDOW' => array(
            'time(suggestions=3 месеца|6 месеца|9 месеца|12 месеца|24 месеца)',
            'caption=Балансите да НЕ се преизчисляват при промяна на документи по-стари от->Срок,placeholder=Винаги'
        ),
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = array(
        array(
            'seePrice'
        ),
        array(
            'seePriceSale'
        ),
        array(
            'seePricePurchase'
        ),
        array(
            'invoicer'
        ),
        array(
            'accJournal'
        ),
        array(
            'accLimits'
        ),
        array(
            'allGlobal'
        ),
        array(
            'invoiceAll'
        ),
        array(
            'invoiceAllGlobal',
            'invoiceAll'
        ),
        array(
            'storeAll'
        ),
        array(
            'storeAllGlobal',
            'storeAll'
        ),
        array(
            'bankAll'
        ),
        array(
            'bankAllGlobal',
            'bankAll'
        ),
        array(
            'cashAll'
        ),
        array(
            'cashAllGlobal',
            'cashAll'
        ),
        array(
            'saleAll'
        ),
        array(
            'saleAllGlobal',
            'saleAll'
        ),
        array(
            'purchaseAll'
        ),
        array(
            'purchaseAllGlobal',
            'purchaseAll'
        ),
        array(
            'planningAll'
        ),
        array(
            'planningAllGlobal',
            'planningAll'
        ),
        array(
            'acc',
            'accJournal, invoicer, seePrice, invoiceAll, storeAll, bankAll, cashAll, saleAll, purchaseAll, planningAll'
        ),
        array(
            'accMaster',
            'acc, invoiceAllGlobal, storeAllGlobal, bankAllGlobal, cashAllGlobal, saleAllGlobal, purchaseAllGlobal, planningAllGlobal, seePriceSale, seePricePurchase'
        ),
        array(
            'repAll'
        ),
        array(
            'repAllGlobal',
            'repAll'
        )
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(
            2.1,
            'Счетоводство',
            'Книги',
            'acc_Balances',
            'default',
            'acc, ceo'
        ),
        array(
            2.3,
            'Счетоводство',
            'Настройки',
            'acc_Periods',
            'default',
            'acc, ceo, admin'
        )
    );
    
    
    /**
     * Описание на системните действия
     */
    public $systemActions = array(
        array(
            'title' => 'Реконтиране',
            'url' => array(
                'acc_Journal',
                'reconto',
                'ret_url' => true
            ),
            'params' => array(
                'title' => 'Реконтиране на документите',
                'ef_icon' => 'img/16/arrow_refresh.png'
            )
        ),
        array(
            'title' => 'Док. без журнал',
            'url' => array(
                'acc_Journal',
                'fixDocsWithoutJournal',
                'ret_url' => true
            ),
            'params' => array(
                'title' => 'Поправка на контирани документи без журнал',
                'ef_icon' => 'img/16/arrow_refresh.png'
            )
        ),
        array(
            'title' => 'Прикл. сделки с активни пера',
            'url' => array(
                'acc_Journal',
                'findDeals',
                'ret_url' => true
            ),
            'params' => array(
                'title' => 'Има ли неактивни сделки с приключени пера',
                'ef_icon' => 'img/16/arrow_refresh.png'
            )
        )
    );


    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'Delete Items',
            'description' => 'Изтриване на неизползвани затворени пера',
            'controller' => 'acc_Items',
            'action' => 'DeleteUnusedItems',
            'period' => 1440,
            'offset' => 60,
            'timeLimit' => 100
        ),
        array(
            'systemId' => 'Create Periods',
            'description' => 'Създаване на нови счетоводни периоди',
            'controller' => 'acc_Periods',
            'action' => 'createFuturePeriods',
            'period' => 1440,
            'offset' => 1
        ),
        array(
            'systemId' => 'RecalcBalances',
            'description' => 'Преизчисляване на баланси',
            'controller' => 'acc_Balances',
            'action' => 'Recalc',
            'period' => 1,
            'timeLimit' => 255
        ),
        array(
            'systemId' => 'SyncAccFeatures',
            'description' => 'Синхронизиране на счетоводните свойства',
            'controller' => 'acc_Features',
            'action' => 'SyncFeatures',
            'period' => 1440,
            'offset' => 60,
            'timeLimit' => 900
        ),
        array(
            'systemId' => 'CheckAccLimits',
            'description' => 'Проверка на счетоводните лимити',
            'controller' => 'acc_Limits',
            'action' => 'CheckAccLimits',
            'period' => 480,
            'offset' => 1,
            'timeLimit' => 60
        )
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'acc_ReportDetails, acc_reports_BalanceImpl, acc_BalanceHistory, acc_reports_HistoryImpl,
    					acc_reports_CorespondingImpl,
    					acc_reports_BalancePeriodImpl, acc_reports_ProfitSales,
                        acc_reports_MovementArtRep,acc_reports_TotalRep,acc_reports_UnpaidInvoices,
                        acc_reports_UnactiveContableDocs,acc_reports_NegativeQuantities,acc_reports_InvoicesByContragent, acc_drivers_TotalRepPortal,
                        acc_reports_SoldProductsByPrimeCost';


    /**
     * Дали пакета е системен
     */
     public $isSystem = true;


    /**
     * Зареждане на данни
     */
    public function loadSetupData($itr = '')
    {
        $res = parent::loadSetupData($itr);
        $docs = core_Packs::getConfigValue('acc', 'ACC_COST_OBJECT_DOCUMENTS');
        
        // Ако потребителя не е избрал документи, които могат да са разходни пера
        if (strlen($docs) === 0) {
            $this->getCostObjectDocuments();
            $res .= "<li style='color:green'>Добавени са дефолт документи за разходни пера</li>";
        }
        
        // Ако няма посочени от потребителя сметки за синхронизация
        $repairAccountsDefault = core_Packs::getConfigValue('acc', 'ACC_BALANCE_REPAIR_ACCOUNTS');
        if (strlen($repairAccountsDefault) === 0) {
            $accArray = array();
            $accAcounts = arr::make(static::$accAccount, true);
            foreach ($accAcounts as $accSysId) {
                $accId = acc_Accounts::getRecBySystemId($accSysId)->id;
                $accArray[$accId] = $accSysId;
            }
            
            // Записват се ид-та на дефолт сметките за синхронизация
            core_Packs::setConfig('acc', array('ACC_BALANCE_REPAIR_ACCOUNTS' => keylist::fromArray($accArray)));
            $res .= "<li style='color:green'>Дефолт счетодовни сметки за корекция от закръгляне<b>" . implode(',', $accArray) . '</b></li>';
        }
        
        return $res;
    }
    
    
    /**
     *
     * @param core_Type $type
     * @param array     $otherParams
     *
     * @return array
     */
    public static function getAccessClassOptions($type, $otherParams)
    {
        return core_Classes::getOptionsByInterface('acc_TransactionSourceIntf', 'title');
    }
    
    
    /**
     * Кои документи по дефолт да са разходни обекти
     */
    public function getCostObjectDocuments()
    {
        $docArr = array();
        foreach (array(
            'cal_Tasks',
            'sales_Sales',
            'purchase_Purchases',
            'accda_Da',
            'findeals_Deals',
            'findeals_AdvanceDeals',
            'planning_DirectProductionNote',
            'store_Transfers'
        ) as $doc) {
            if (core_Classes::add($doc)) {
                $id = $doc::getClassId();
                $docArr[$id] = $id;
            }
        }
        
        // Записват се ид-та на дефолт сметките за синхронизация
        core_Packs::setConfig('acc', array(
            'ACC_COST_OBJECT_DOCUMENTS' => keylist::fromArray($docArr)
        ));
    }
    
    
    /**
     * Помощна функция връщаща всички класове, които са документи
     */
    public static function getDocumentOptions()
    {
        $options = core_Classes::getOptionsByInterface('doc_DocumentIntf', 'title');
        
        return $options;
    }


    /**
     * Миграция на ролите за виждане на цени
     */
    public function updatePriceRoles2247()
    {
        if(defined('BGERP_DONT_MIGRATE_USERS_WITH_SEE_PRICE')) return;

        $seePriceRoleId = core_Roles::fetchByName('seePrice');
        $seePriceSaleRoleId = core_Roles::fetchByName('seePriceSale');
        $seePricePurchaseRoleId = core_Roles::fetchByName('seePricePurchase');

        $updateUsers = array();
        $uQuery = core_Users::getQuery();
        $uQuery->where("#state != 'rejected' && LOCATE('|{$seePriceRoleId}|', #roles)");

        $addKeylist = keylist::fromArray(array($seePriceSaleRoleId => $seePriceSaleRoleId, $seePricePurchaseRoleId => $seePricePurchaseRoleId));
        while($uRec = $uQuery->fetch()){
            if(!keylist::isIn($seePriceSaleRoleId, $uRec->roles) && !keylist::isIn($seePricePurchaseRoleId, $uRec->roles)){
                $uRec->rolesInput = keylist::merge($uRec->rolesInput, $addKeylist);
                $uRec->roles = keylist::merge($uRec->roles, $addKeylist);
                $updateUsers[$uRec->id] = $uRec;
            }
        }

        if(countR($updateUsers)){
            cls::get('core_Users')->saveArray($updateUsers, 'id,roles,rolesInput');
        }
    }
}
