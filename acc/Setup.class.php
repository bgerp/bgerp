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
defIfNot('ACC_MONEY_TOLERANCE', '0.01');


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
    		'acc_OpenDeals',
    		'acc_Features',
    		'migrate::removeYearInterfAndItem',
        );
    

    /**
	 * Описание на конфигурационните константи
	 */
	var $configDescription = array(
		'ACC_MONEY_TOLERANCE' => array("double(decimals=2)", 'caption=Толеранс за допустимо разминаване на суми в основна валута->Сума'),
		'ACC_DETAILED_BALANCE_ROWS' => array("int", 'caption=Баланс->Редове в детайлния баланс,unit=бр.'),
	);
    
    
    /**
     * Роли за достъп до модула
     */
    var $roles = 'acc';

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(2.1, 'Счетоводство', 'Книги', 'acc_Balances', 'default', "acc, ceo"),
            array(2.3, 'Счетоводство', 'Настройки', 'acc_Periods', 'default', "acc, ceo"),
        );
	
    /**
     * Път до js файла
     */
//    var $commonJS = 'acc/js/balance.js';
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        // Добавяне на класа за репорти
    	$html .= core_Classes::add('acc_ReportDetails');
    	
        //Данни за работата на cron
        $rec = new stdClass();
        $rec->systemId = 'RecalcBalances';
        $rec->description = 'Преизчисляване на баланси';
        $rec->controller = 'acc_Balances';
        $rec->action = 'Recalc';
        $rec->period = 1;
        $rec->offset = 0;
        $rec->delay = 0;
        $rec->timeLimit = 55;
        
        $Cron = cls::get('core_Cron');
        
        if ($Cron->addOnce($rec)) {
            $html .= "<li><span class=\"green\">Задаване по крон да преизчислява баланси</span></li>";
        } else {
            $html .= "<li>Отпреди Cron е бил нагласен да преизчислява баланси</li>";
        }
		
        // Добавяне на роля за старши касиер
        $html .= core_Roles::addRole('accMaster', 'acc') ? "<li style='color:green'>Добавена е роля <b>accMaster</b></li>" : '';
        
    	$html .= parent::install();

        return $html;
    }
    
    
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
    	if($oldYearManId = core_Classes::fetchIdByName('acc_Years')){
    		if(acc_Items::fetch("#classId = '{$oldYearManId}'")){
    			acc_Items::delete("#classId = '{$oldYearManId}'");
    		}
    	}
    }
}
