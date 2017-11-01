<?php


/**
 * Колко дена преди края на месеца да се направи следващия бъдещ период чакащ
 */
defIfNot('ACC_DAYS_BEFORE_MAKE_PERIOD_PENDING', '');



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
 * Класове, които ще разширяват правата за контиране на документ
 */
defIfNot('ACC_CLASSES_FOR_VIEW_ACCESS', '');


/**
 * Класове по-подразбиране, които ще пълнята ACC_CLASSES_FOR_VIEW_ACCESS, ако няма стойност
 */
defIfNot('ACC_CLASSES_FOR_VIEW_ACCESS_NAME', 'bank_ExchangeDocument, bank_IncomeDocuments, bank_InternalMoneyTransfer, cash_InternalMoneyTransfer, purchase_Services, planning_DirectProductionNote, planning_ConsumptionNotes, planning_ReturnNotes, cash_Pko, cash_Rko, store_ShipmentOrders, sales_Services, store_ConsignmentProtocols, store_Receipts, store_Transfers');


/**
 * Ден от месеца за изчисляване на Счетоводна дата на входяща фактура
 */
defIfNot('ACC_DATE_FOR_INVOICE_DATE', '10');


/**
 * class acc_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани със счетоводството
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'currency=0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'acc_Lists';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Двустранно счетоводство: Настройки, Журнали";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
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
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
        'ACC_MONEY_TOLERANCE'                 => array("double(decimals=2)", 'caption=Толеранс за допустимо разминаване на суми в основна валута->Сума'),
        'ACC_DETAILED_BALANCE_ROWS'           => array("int", 'caption=Редове в страница от детайлния баланс->Брой редове,unit=бр.'),
    	'ACC_DAYS_BEFORE_MAKE_PERIOD_PENDING' => array("time(suggestions= 1 ден|2 дена|7 Дена)", 'caption=Колко дни преди края на месеца да се направи следващия бъдещ период чакащ->Дни'),
    	'ACC_VAT_REASON_OUTSIDE_EU'           => array('varchar', 'caption=Основание за неначисляване на ДДС за контрагент->Извън ЕС'),
    	'ACC_VAT_REASON_IN_EU'                => array('varchar', 'caption=Основание за неначисляване на ДДС за контрагент->От ЕС'),
    	'ACC_COST_OBJECT_DOCUMENTS'           => array('keylist(mvc=core_Classes,select=name)', "caption=Кои документи могат да бъдат разходни обекти->Документи,optionsFunc=acc_Setup::getDocumentOptions"),
        'ACC_SUMMARY_ROLES_FOR_TEAMS'         => array('varchar', 'caption=Роли за екипите при филтриране->Роли'),
        'ACC_SUMMARY_ROLES_FOR_ALL'           => array('varchar', 'caption=Роли за всички при филтриране->Роли'),
        'ACC_CLASSES_FOR_VIEW_ACCESS'         => array('keylist(mvc=core_Classes, select=title)', 'caption=Класове|*&#44; |*които ще разширяват правата за контиране на документи->Класове, optionsFunc=acc_Setup::getAccessClassOptions', array('data-role' => 'list')),
		'ACC_DATE_FOR_INVOICE_DATE'			  => array('int(min=1,max=31)', 'caption=Ден от месеца за изчисляване на Счетоводна дата на входяща фактура->Ден'),
    );
    
    
    /**
     * Роли за достъп до модула
     */
    var $roles = array(
    	array('seePrice'),
    	array('invoicer'),
    	array('accJournal'),
    	array('acc', 'accJournal,invoicer,seePrice'),
        array('accMaster', 'acc'),
        array('accLimits'),
        array('allGlobal'),
        array('invoiceAll'),
        array('invoiceAllGlobal', 'invoiceAll, allGlobal'),
        array('storeAll'),
        array('storeaAllGlobal', 'storeAll, allGlobal'),
        array('bankAll'),
        array('bankAllGlobal', 'bankAll, allGlobal'),
        array('cashAll'),
        array('cashAllGlobal', 'cashAll, allGlobal'),
        array('saleAll'),
        array('saleAllGlobal', 'saleAll, allGlobal'),
        array('purchaseAll'),
        array('purchaseAllGlobal', 'purchaseAll, allGlobal'),
        array('planningAll'),
        array('planningAllGlobal', 'planningAll, allGlobal'),
        array('rep_acc'),

    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
        array(2.1, 'Счетоводство', 'Книги', 'acc_Balances', 'default', "acc, ceo"),
        array(2.3, 'Счетоводство', 'Настройки', 'acc_Periods', 'default', "acc, ceo, admin"),
    );
    
    
    /**
     * Описание на системните действия
     */
    var $systemActions = array(
        array('title' => 'Реконтиране', 'url' => array('acc_Journal', 'reconto', 'ret_url' => TRUE), 'params' => array('title' => 'Реконтиране на документите', 'ef_icon' => 'img/16/arrow_refresh.png'))
    );
    
    
    /**
     * Настройки за Cron
     */
    var $cronSettings = array(
        array(
            'systemId' => "Delete Items",
            'description' => "Изтриване на неизползвани затворени пера",
            'controller' => "acc_Items",
            'action' => "DeleteUnusedItems",
            'period' => 1440,
        	'offset' => 60,
            'timeLimit' => 100
        ),
        array(
            'systemId' => "Create Periods",
            'description' => "Създаване на нови счетоводни периоди",
            'controller' => "acc_Periods",
            'action' => "createFuturePeriods",
            'period' => 1440,
            'offset' => 60,
        ),
        array(
            'systemId' => 'RecalcBalances',
            'description' => 'Преизчисляване на баланси',
            'controller' => 'acc_Balances',
            'action' => 'Recalc',
            'period' => 1,
            'timeLimit' => 55,
        ),
    	array(
    		'systemId' => "SyncAccFeatures",
    		'description' => "Синхронизиране на счетоводните свойства",
    		'controller' => "acc_Features",
    		'action' => "SyncFeatures",
    		'period' => 1440,
    		'offset' => 60,
    		'timeLimit' => 600,
    	),
    	array(
    		'systemId' => "CheckAccLimits",
    		'description' => "Проверка на счетоводните лимити",
    		'controller' => "acc_Limits",
    		'action' => "CheckAccLimits",
    		'period' => 480,
    		'offset' => 1,
    		'timeLimit' => 60,
    	),
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    var $defClasses = "acc_ReportDetails, acc_reports_BalanceImpl, acc_BalanceHistory, acc_reports_HistoryImpl, acc_reports_PeriodHistoryImpl,
    					acc_reports_CorespondingImpl,acc_reports_SaleArticles,acc_reports_SaleContractors,acc_reports_OweProviders,
    					acc_reports_ProfitArticles,acc_reports_ProfitContractors,acc_reports_MovementContractors,acc_reports_TakingCustomers,
    					acc_reports_ManufacturedProducts,acc_reports_PurchasedProducts,acc_reports_BalancePeriodImpl, acc_reports_ProfitSales,
                        acc_reports_MovementsBetweenAccounts,acc_reports_ProductGroupRep,acc_reports_MovementArtRep,acc_reports_TotalRep";
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
    
    
    /**
     * Зареждане на данни
     */
    function loadSetupData($itr = '')
    {
    	$res = parent::loadSetupData($itr);
    	$docs = core_Packs::getConfigValue('acc', 'ACC_COST_OBJECT_DOCUMENTS');
    	
    	// Ако потребителя не е избрал документи, които могат да са разходни пера
    	if(strlen($docs) === 0){
    		$this->getCostObjectDocuments();
    		$res .= "<li style='color:green'>Добавени са дефолт документи за разходни пера</li>";
    	}
    	
    	$viewAccess = self::get('CLASSES_FOR_VIEW_ACCESS');
    	
    	// Ако не е сетната стойност, задаваме класовете от константата
    	if (!strlen($viewAccess)) {
    	    $viewAccessNameArr = type_Set::toArray(self::get('CLASSES_FOR_VIEW_ACCESS_NAME'));
    	    if (!empty($viewAccessNameArr)) {
    	        
    	        $clsIdArr = array();
    	        
    	        foreach ($viewAccessNameArr as $clsName) {
    	            
    	            $clsName = trim($clsName);
    	            if (!$clsName) continue;
    	            
    	            if (!cls::load($clsName, TRUE)) continue;
    	            
    	            $clsId = core_Classes::getId($clsName);
    	            
    	            $clsIdArr[$clsId] = $clsId;
    	        }
    	        
    	        $clsIds = type_Keylist::fromArray($clsIdArr);
    	        
    	        core_Packs::setConfig('acc', array('ACC_CLASSES_FOR_VIEW_ACCESS' => $clsIds));
    	    }
    	}
    	
    	return $res;
    }
    
    
    /**
     * 
     * @param core_Type $type
     * @param array $otherParams
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
    function getCostObjectDocuments()
    {
    	$docArr = array();
    	foreach (array('cal_Tasks', 'sales_Sales', 'purchase_Purchases', 'accda_Da', 'findeals_Deals', 'findeals_AdvanceDeals', 'planning_DirectProductionNote', 'store_Transfers') as $doc){
    		if(core_Classes::add($doc)){
    			$id = $doc::getClassId();
    			$docArr[$id] = $id;
    		}
    	}
    	 
    	// Записват се ид-та на дефолт сметките за синхронизация
    	core_Packs::setConfig('acc', array('ACC_COST_OBJECT_DOCUMENTS' => keylist::fromArray($docArr)));
    }
    
    
   
    
    /**
     * Помощна функция връщаща всички класове, които са документи
     */
    public static function getDocumentOptions()
    {
    	$options = core_Classes::getOptionsByInterface('doc_DocumentIntf', 'title');
    	
    	return $options;
    }
}
