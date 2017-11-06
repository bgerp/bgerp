<?php


/**
 * Дефолтна валидност на офертата
 */
defIfNot('SALES_DEFAULT_VALIDITY_OF_QUOTATION', '2592000');


/**
 * Начален номер на фактурите
 */
defIfNot('SALE_INV_MIN_NUMBER1', '0');


/**
 * 
 */
defIfNot('SALES_DELTA_CAT_GROUPS', '');


/**
 * Краен номер на фактурите
 */
defIfNot('SALE_INV_MAX_NUMBER1', '2000000');


/**
 * Начален номер на фактурите
 */
defIfNot('SALE_INV_MIN_NUMBER2', '2000000');


/**
 * Краен номер на фактурите
*/
defIfNot('SALE_INV_MAX_NUMBER2', '3000000');


/**
 * Колко време след като не е платена една продажба, да се отбелязва като просрочена
 */
defIfNot('SALE_OVERDUE_CHECK_DELAY', 60 * 60 * 6);


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
defIfNot('SALE_FISC_PRINTER_DRIVER', '');


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
 * Продажби - инсталиране / деинсталиране
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'sales_Sales';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Продажби на артикули";
    
    
    /**
	 * Описание на конфигурационните константи
	 */
	var $configDescription = array(
			'SALE_OVERDUE_CHECK_DELAY'             => array("time", "caption=Толеранс за просрочване на продажбата->Време"),
			'SALE_CLOSE_OLDER_THAN'                => array("time(uom=days,suggestions=1 ден|2 дена|3 дена)", 'caption=Изчакване преди автоматично приключване на продажбата->Дни'),
			'SALE_CLOSE_OLDER_NUM'                 => array("int", 'caption=По колко продажби да се приключват автоматично на опит->Брой'),
			'SALE_FISC_PRINTER_DRIVER'             => array('class(interface=sales_FiscPrinterIntf,allowEmpty,select=title)', 'caption=Фискален принтер->Драйвър'),
			'SALE_INV_VAT_DISPLAY'                 => array('enum(no=Не,yes=Да)', 'caption=Фактури изчисляване на ддс-то като процент от сумата без ддс->Избор'),
			'SALE_INV_MIN_NUMBER1'                 => array('int(min=0)', 'caption=Първи диапазон за номериране на фактури->Долна граница'),
			'SALE_INV_MAX_NUMBER1'                 => array('int(min=0)', 'caption=Първи диапазон за номериране на фактури->Горна граница'),
			'SALE_INV_MIN_NUMBER2'                 => array('int(min=0)', 'caption=Втори диапазон за номериране на фактури->Долна граница'),
			'SALE_INV_MAX_NUMBER2'                 => array('int(min=0)', 'caption=Втори диапазон за номериране на фактури->Горна граница'),
			'SALE_INV_HAS_FISC_PRINTERS'           => array('enum(no=Не,yes=Да)', 'caption=Има ли фирмата касови апарати->Избор'),
			'SALE_SALE_DEF_TPL_BG'                 => array('key(mvc=doc_TplManager,allowEmpty)', 'caption=Продажба основен шаблон->Български,optionsFunc=sales_Sales::getTemplateBgOptions'),
			'SALE_SALE_DEF_TPL_EN'                 => array('key(mvc=doc_TplManager,allowEmpty)', 'caption=Продажба основен шаблон->Английски,optionsFunc=sales_Sales::getTemplateEnOptions'),
			'SALE_INVOICE_DEF_TPL_BG'              => array('key(mvc=doc_TplManager,allowEmpty)', 'caption=Фактура основен шаблон->Български,optionsFunc=sales_Invoices::getTemplateBgOptions'),
			'SALE_INVOICE_DEF_TPL_EN'              => array('key(mvc=doc_TplManager,allowEmpty)', 'caption=Фактура основен шаблон->Английски,optionsFunc=sales_Invoices::getTemplateEnOptions'),
			'SALE_INVOICES_SHOW_DEAL'              => array("enum(auto=Автоматично,no=Никога,yes=Винаги)", 'caption=Показване на сделката в описанието на фактурата->Избор'),
			'SALES_USE_RATE_IN_CONTRACTS'          => array("enum(no=Не,yes=Да)", 'caption=Ръчно въвеждане на курс в продажбите->Избор'),
			'SALES_INVOICE_DEFAULT_VALID_FOR'      => array("time", 'caption=Срок за плащане по подразбиране->Срок'),
			'SALES_ADD_BY_PRODUCT_BTN'             => array("keylist(mvc=core_Roles,select=role,groupBy=type)", 'caption=Необходими роли за добавяне на артикули в продажба от->Артикул'),
			'SALES_ADD_BY_CREATE_BTN'              => array("keylist(mvc=core_Roles,select=role,groupBy=type)", 'caption=Необходими роли за добавяне на артикули в продажба от->Създаване'),
			'SALES_ADD_BY_LIST_BTN'                => array("keylist(mvc=core_Roles,select=role,groupBy=type)", 'caption=Необходими роли за добавяне на артикули в продажба от->Списък'),
			'SALES_ADD_BY_IMPORT_BTN'  			   => array("keylist(mvc=core_Roles,select=role,groupBy=type)", 'caption=Необходими роли за добавяне на артикули в продажба от->Импорт'),
			'SALES_DELTA_CAT_GROUPS'               => array('keylist(mvc=cat_Groups,select=name)', 'caption=Групи продажбени артикули за изчисляване на ТРЗ индикатори->Групи'),
			'SALES_ROUTES_CLOSE_DELAY'             => array('int(min=1)', 'caption=Изчакване преди да се затворят изпълнените търговски маршрути->Дни'),
			'SALES_DEFAULT_VALIDITY_OF_QUOTATION'  => array('time', 'caption=Оферти->Валидност'),
	);
	
	
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
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
        );

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.1, 'Търговия', 'Продажби', 'sales_Sales', 'default', "sales, ceo, acc"),
        );

    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    var $defClasses = 'sales_SalesLastPricePolicy,sales_reports_SalesPriceImpl, sales_reports_OweInvoicesImpl, 
                       sales_reports_ShipmentReadiness,sales_reports_PurBomsRep,sales_reports_ZDDSRep,sales_reports_OverdueByAdvancePayment';
    
    
    /**
     * Настройки за Cron
     */
    var $cronSettings = array(
    		array('systemId'    => "Close invalid quotations",
    			  'description' => "Затваряне на остарелите оферти",
    			  'controller'  => "sales_Quotations",
    			  'action'      => "CloseQuotations",
    			  'period'      => 1440,
    			  'timeLimit'   => 360,
    		),
    		array('systemId'    => "Update Routes Next Visit",
    			  'description' => "Изчисляване на посещенията на търговските маршрути",
    			  'controller'  => "sales_Routes",
    			  'action'      => "calcNextVisit",
    			  'offset'      => 140,
    			  'period'      => 1440,
    			  'timeLimit'   => 360,
    		),
    );
    
    
    /**
     * Роли за достъп до модула
     */
    var $roles = array(
    		array('sales', 'invoicer,seePrice,dec'),
    		array('salesMaster', 'sales'),
    );
    
    
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
    	
    	// Ако няма посочени от потребителя сметки за синхронизация
    	$config = core_Packs::getConfig('sales');
    	
    	// Поставяме първия намерен шаблон на български за дефолтен на продажбата
    	if(strlen($config->SALE_SALE_DEF_TPL_BG) === 0){
    		$key = key(sales_Sales::getTemplateBgOptions());
    		core_Packs::setConfig('sales', array('SALE_SALE_DEF_TPL_BG' => $key));
    	}
    	
    	// Поставяме първия намерен шаблон на английски за дефолтен на продажбата
    	if(strlen($config->SALE_SALE_DEF_TPL_EN) === 0){
    		$key = key(sales_Sales::getTemplateEnOptions());
    		core_Packs::setConfig('sales', array('SALE_SALE_DEF_TPL_EN' => $key));
    	}
    	
    	// Поставяме първия намерен шаблон на български за дефолтен на фактурата
    	if(strlen($config->SALE_INVOICE_DEF_TPL_BG) === 0){
    		$key = key(sales_Invoices::getTemplateBgOptions());
    		core_Packs::setConfig('sales', array('SALE_INVOICE_DEF_TPL_BG' => $key));
    	}
    	
    	// Поставяме първия намерен шаблон на английски за дефолтен на фактурата
    	if(strlen($config->SALE_INVOICE_DEF_TPL_EN) === 0){
    		$key = key(sales_Invoices::getTemplateEnOptions());
    		core_Packs::setConfig('sales', array('SALE_INVOICE_DEF_TPL_EN' => $key));
    	}
    	
    	// Добавяне на дефолтни роли за бутоните
    	foreach (array('SALES_ADD_BY_PRODUCT_BTN', 'SALES_ADD_BY_CREATE_BTN', 'SALES_ADD_BY_LIST_BTN', 'SALES_ADD_BY_IMPORT_BTN') as $const){
    		if(strlen($config->{$const}) === 0){
    			$keylist = core_Roles::getRolesAsKeylist('sales,ceo');
    			core_Packs::setConfig('sales', array($const => $keylist));
    		}
    	}
    	
    	return $res;
    }
}
