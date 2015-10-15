<?php


/**
 * Начален номер на фактурите
 */
defIfNot('SALE_INV_MIN_NUMBER1', '0');


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
 * Максимален срок за бъдещи цени с които да работи офертата
 */
defIfNot('SALE_MAX_FUTURE_PRICE', type_Time::SECONDS_IN_MONTH);


/**
 * Максимален срок за минали цени с които да работи офертата
 */
defIfNot('SALE_MAX_PAST_PRICE', type_Time::SECONDS_IN_MONTH * 2);


/**
 * Колко време след като не е платена една продажба, да се отбелязва като просрочена
 */
defIfNot('SALE_OVERDUE_CHECK_DELAY', 60 * 60 * 6);


/**
 * Колко време да се изчака след активиране на продажба, да се приключва автоматично
 */
defIfNot('SALE_CLOSE_OLDER_THAN', 60 * 60 * 24 * 3);


/**
 * Колко продажби да се приключват автоматично брой
 */
defIfNot('SALE_CLOSE_OLDER_NUM', 15);


/**
 * Кой да е по подразбиране драйвера за фискален принтер
 */
defIfNot('SALE_FISC_PRINTER_DRIVER', '');


/**
 * Основание за неначисляване на ДДС за контрагент контрагент от държава в ЕС (без България)
 */
defIfNot('SALES_VAT_REASON_IN_EU', 'чл.53 от ЗДДС – вътреобщностна доставка на стоки');


/**
 * Основание за неначисляване на ДДС за контрагент извън ЕС
 */
defIfNot('SALES_VAT_REASON_OUTSIDE_EU', 'чл.28 от ЗДДС – износ на стоки извън ЕС');


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
			'SALE_OVERDUE_CHECK_DELAY'    => array("time", "caption=Толеранс за просрочване на продажбата->Време"),
			'SALE_MAX_FUTURE_PRICE'       => array("time(uom=months,suggestions=1 месец|2 месеца|3 месеца)", 'caption=Допустим ценови период за продажбата->В бъдещето'),
			'SALE_MAX_PAST_PRICE'         => array("time(uom=months,suggestions=1 месец|2 месеца|3 месеца)", 'caption=Допустим ценови период за продажбата->В миналото'),
			'SALE_CLOSE_OLDER_THAN'       => array("time(uom=days,suggestions=1 ден|2 дена|3 дена)", 'caption=Изчакване преди автоматично приключване на продажбата->Дни'),
			'SALE_CLOSE_OLDER_NUM'        => array("int", 'caption=По колко продажби да се приключват автоматично на опит->Брой'),
			'SALE_FISC_PRINTER_DRIVER'    => array('class(interface=sales_FiscPrinterIntf,allowEmpty,select=title)', 'caption=Фискален принтер->Драйвър'),
			'SALE_INV_VAT_DISPLAY'        => array('enum(no=Не,yes=Да)', 'caption=Фактури изчисляване на ддс-то като процент от сумата без ддс->Избор'),
			'SALE_INV_MIN_NUMBER1'        => array('int(min=0)', 'caption=Първи диапазон за номериране на фактури->Долна граница'),
			'SALE_INV_MAX_NUMBER1'        => array('int(min=0)', 'caption=Първи диапазон за номериране на фактури->Горна граница'),
			'SALE_INV_MIN_NUMBER2'        => array('int(min=0)', 'caption=Втори диапазон за номериране на фактури->Долна граница'),
			'SALE_INV_MAX_NUMBER2'        => array('int(min=0)', 'caption=Втори диапазон за номериране на фактури->Горна граница'),
			'SALE_INV_HAS_FISC_PRINTERS'  => array('enum(no=Не,yes=Да)', 'caption=Има ли фирмата касови апарати->Избор'),
			
			'SALE_SALE_DEF_TPL_BG'        => array('key(mvc=doc_TplManager,allowEmpty)', 'caption=Продажба основен шаблон->Български,optionsFunc=sales_Sales::getTemplateBgOptions'),
			'SALE_SALE_DEF_TPL_EN'        => array('key(mvc=doc_TplManager,allowEmpty)', 'caption=Продажба основен шаблон->Английски,optionsFunc=sales_Sales::getTemplateEnOptions'),
	
			'SALE_INVOICE_DEF_TPL_BG'     => array('key(mvc=doc_TplManager,allowEmpty)', 'caption=Фактура основен шаблон->Български,optionsFunc=sales_Invoices::getTemplateBgOptions'),
			'SALE_INVOICE_DEF_TPL_EN'     => array('key(mvc=doc_TplManager,allowEmpty)', 'caption=Фактура основен шаблон->Английски,optionsFunc=sales_Invoices::getTemplateEnOptions'),
			
			'SALES_VAT_REASON_OUTSIDE_EU'  => array('varchar', 'caption=Основание за ненаничляване на ДДС за контрагент->Извън ЕС'),
			'SALES_VAT_REASON_IN_EU'       => array('varchar', 'caption=Основание за ненаничляване на ДДС за контрагент->От ЕС'),
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
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'sales';

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.1, 'Търговия', 'Продажби', 'sales_Sales', 'default', "sales, ceo"),
        );

    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    var $defClasses = '';
    
    
	/**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
        
        // Добавяме политиката "По последна продажна цена"
        $html .= core_Classes::add('sales_SalesLastPricePolicy');
        
        // Добавяне на роля за старши продавач
        $html .= core_Roles::addOnce('salesMaster', 'sales');
        
        // Добавяне на роля за старши касиер
        $html .= core_Roles::addOnce('invoicer');
        
        // acc наследява invoicer
        $html .= core_Roles::addOnce('acc', 'invoicer');
        
        // sales наследява invoicer
        $html .= core_Roles::addOnce('sales', 'invoicer');
        
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
     * Зареждане на данни
     */
    function loadSetupData()
    {
    	$res = parent::loadSetupData();
    	
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
    	
    	return $res;
    }
}
