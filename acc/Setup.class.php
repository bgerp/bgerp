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
defIfNot('ACC_VAT_REASON_OUTSIDE_EU', 'чл.28 от ЗДДС – износ извън ЕС');


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
defIfNot('ACC_BALANCE_REPAIR_QUANITITY_BELLOW', '0,00999');


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
        'migrate::updateFeatures'
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'ACC_MONEY_TOLERANCE' => array(
            'double(decimals=2)',
            'caption=Толеранс за допустимо разминаване на суми в основна валута->Сума'
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
            'caption=Основание за неначисляване на ДДС за контрагент->Извън ЕС'
        ),
        'ACC_VAT_REASON_IN_EU' => array(
            'varchar',
            'caption=Основание за неначисляване на ДДС за контрагент->От ЕС'
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
        'ACC_BALANCE_REPAIR_QUANITITY_BELLOW' => array(
            'double',
            'caption=Корекция на грешки от закръгляне->Количество под'
        ),
        'ACC_BALANCE_REPAIR_AMOUNT_BELLOW' => array(
            'double',
            'caption=Корекция на грешки от закръгляне->Сума под'
        ),
        'ACC_ALTERNATE_WINDOW' => array(
            'time(suggestions=3 месец|4 месеца|5 месеца|6 месеца|7 месеца|8 месеца|9 месеца|10 месеца|11 месеца|12 месеца)',
            'caption=Колко назад могат да бъдат променяни счетоводни документи->Време,placeholder=Винаги'
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
            'acc, invoiceAllGlobal, storeAllGlobal, bankAllGlobal, cashAllGlobal, saleAllGlobal, purchaseAllGlobal, planningAllGlobal'
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
                        acc_reports_UnactiveContableDocs,acc_reports_NegativeQuantities,acc_reports_InvoicesByContragent, acc_drivers_TotalRepPortal';
    
    
    
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
     * Миграция на свойствата
     */
    function updateFeatures()
    {
        $FeatureTitles = cls::get('acc_FeatureTitles');
        $FeatureTitles->setupMvc();
        
        $Features = cls::get('acc_Features');
        $Features->setupMvc();
        
        if(!acc_Features::count()) return;
        core_App::setTimeLimit(700);
        
        $titleToSave = array();
        $tQuery = acc_FeatureTitles::getQuery();
        $tQuery->where("LOCATE('||', #title)");
        $tQuery->show('title');
        while($tRec = $tQuery->fetch()){
            $exploded = explode('||', $tRec->title);
            if(countR($exploded) == 2){
                $tRec->title = $exploded[0];
                $titleToSave[$tRec->id] = $tRec;
            }
        }
        
        $valuesToSave = array();
        $fQuery = acc_Features::getQuery();
        $fQuery->where("LOCATE('||', #value)");
        $fQuery->show('value');
        
        while($fRec = $fQuery->fetch()){
            $exploded = explode('||', $fRec->value);
            if(countR($exploded) == 2){
                $fRec->value = $exploded[0];
                $valuesToSave[$fRec->id] = $fRec;
            }
        }
        
        if(countR($titleToSave)){
            $FeatureTitles->saveArray($titleToSave, 'id,title');
        }
        
        if(countR($valuesToSave)){
            $Features->saveArray($valuesToSave, 'id,value');
        }
    }
}
