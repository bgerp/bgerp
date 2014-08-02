<?php


/**
 * Толеранс за автоматичното затваряне на продажба за доставеното - платеното
 */
defIfNot('SALE_CLOSE_TOLERANCE', '0.01');


/**
 * Начален номер на фактурите
 */
defIfNot('SALE_INV_MIN_NUMBER', '0');


/**
 * Краен номер на фактурите
 */
defIfNot('SALE_INV_MAX_NUMBER', '10000000');


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
 * Колко време да се изчака след активиране на продажба, преди да се провери дали е просрочена
 */
defIfNot('SALE_CLOSE_OLDER_THAN', 60 * 60 * 24 * 3);


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
    var $info = "Продажби на продукти и стоки";
    
    
    /**
	 * Описание на конфигурационните константи
	 */
	var $configDescription = array(
			'SALE_OVERDUE_CHECK_DELAY' => array("time", "caption=Толеранс за просрочване на продажбата->Време"),
			'SALE_CLOSE_TOLERANCE'     => array("double(decimals=2)", 'caption=Толеранс за приключване на продажбата->Сума'),
			'SALE_MAX_FUTURE_PRICE'    => array("time(uom=months,suggestions=1 месец|2 месеца|3 месеца)", 'caption=Допустим ценови период за продажбата->В бъдещето'),
			'SALE_MAX_PAST_PRICE'      => array("time(uom=months,suggestions=1 месец|2 месеца|3 месеца)", 'caption=Допустим ценови период за продажбата->В миналото'),
			'SALE_CLOSE_OLDER_THAN'    => array("time(uom=days,suggestions=1 ден|2 дена|3 дена)", 'caption=Изчакване преди автоматично приключване на продажбата->Дни'),
			'SALE_INV_MIN_NUMBER'      => array('int', 'caption=Номер на фактура->Долна граница'),
			'SALE_INV_MAX_NUMBER'      => array('int', 'caption=Номер на фактура->Горна граница'),
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
    		'sales_SaleRequests',
    		'sales_SaleRequestDetails',
    		'sales_ClosedDeals',
    		'sales_Services',
    		'sales_ServicesDetails',
    		'sales_Invoices',
            'sales_InvoiceDetails',
    		'sales_Proformas',
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
     * Път до js файла
     */
    var $commonJS = 'sales/js/ResizeQuoteTable.js';
    
    
    /**
     * Път до css файла
     */
    var $commonCSS = 'sales/tpl/invoiceStyles.css, sales/tpl/styles.css';
    
    
	/**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
        
        // Добавяме политиката "По последна продажна цена"
        core_Classes::add('sales_SalesLastPricePolicy');
        
        // Добавяне на роля за старши продавач
        $html .= core_Roles::addRole('salesMaster', 'sales') ? "<li style='color:green'>Добавена е роля <b>salesMaster</b></li>" : '';
        
        // Добавяне на роля за старши касиер
        $html .= core_Roles::addRole('invoicer') ? "<li style='color:green'>Добавена е роля <b>accMaster</b></li>" : '';
        
        // acc наследява invoicer
        core_Roles::addRole('acc', 'invoicer');
        
        // sales наследява invoicer
        core_Roles::addRole('sales', 'invoicer');
        
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
}
