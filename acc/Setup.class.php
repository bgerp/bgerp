<?php



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
 * Толеранс за допустимо разминаване на суми
 */
defIfNot('ACC_MONEY_TOLERANCE', '0.05');


/**
 * Колко реда да се показват в детайлния баланс
 */
defIfNot('ACC_DETAILED_BALANCE_ROWS', 500);


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
        'migrate::removeYearInterfAndItem',
        'migrate::updateItemsNum1',
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
        'ACC_MONEY_TOLERANCE' => array("double(decimals=2)", 'caption=Толеранс за допустимо разминаване на суми в основна валута->Сума'),
        'ACC_DETAILED_BALANCE_ROWS' => array("int", 'caption=Редове в страница от детайлния баланс->Брой редове,unit=бр.'),
    );
    
    
    /**
     * Роли за достъп до модула
     */
    var $roles = array(
        'acc',
        array('accMaster', 'acc')
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
        array(2.1, 'Счетоводство', 'Книги', 'acc_Balances', 'default', "acc, ceo"),
        array(2.3, 'Счетоводство', 'Настройки', 'acc_Periods', 'default', "acc, ceo"),
    );
    
    
    /**
     * Описание на системните действия
     */
    var $systemActions = array(
    		'Реконтиране' => array ('acc_Journal', 'reconto', 'ret_url' => TRUE)
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
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    var $defClasses = "acc_ReportDetails, acc_BalanceReportImpl, acc_BalanceHistory, acc_HistoryReportImpl, acc_CorespondingReportImpl";
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
    
    
    /**
     * Обновява номерата на перата
     */
    function updateItemsNum1()
    {
        $Items = cls::get('acc_Items');
        $itemsQuery = $Items->getQuery();
        
        do{
            try {
                $iRec = $itemsQuery->fetch();
                
                if($iRec === NULL) break;
            
            	if(cls::load($iRec->classId, TRUE)){
	                $Register = cls::get($iRec->classId);
	                
	                if($iRec->objectId) {
	                    $regRec = $Register->getItemRec($iRec->objectId);
	                    
	                    if($regRec->num != $iRec->num){
	                        $iRec->num = $regRec->num;
	                        $Items->save_($iRec, 'num');
	                    }
	                }
	            }
            } catch (core_exception_Expect $e) {
            	$Items->log($e->getMessage());
            	continue;
            }
            
        } while(TRUE);
    }
    
    
    /**
     * Миграция, която премахва данните останали от мениджъра за годините
     */
    function removeYearInterfAndItem()
    {
        // Изтриваме интерфейса на годините от таблицата с итнерфейсите
        if($oldIntRec = core_Interfaces::fetch("#name = 'acc_YearsAccRegIntf'")){
            core_Interfaces::delete($oldIntRec->id);
        }
        
        if($oldIntRec = core_Interfaces::fetch("#name = 'acc_YearsRegIntf'")){
            core_Interfaces::delete($oldIntRec->id);
        }
        
        // Изтриваме и перата за години със стария меджър 'години'
        if($oldYearManId = core_Classes::getId('acc_Years')){
            if(acc_Items::fetch("#classId = '{$oldYearManId}'")){
                acc_Items::delete("#classId = '{$oldYearManId}'");
            }
        }
    }
}
