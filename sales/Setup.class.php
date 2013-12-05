<?php


/**
 * Шаблон на хедъра на фактурата
 */
defIfNot('INV_LAYOUT', 'Normal');


/**
 * Шаблон на хедъра на офертата
 */
defIfNot('QUOTE_LAYOUT', 'Letter');


/**
 * Максимален срок за бъдещи цени с които да работи офертата
 */
defIfNot('SALE_MAX_FUTURE_PRICE', type_Time::SECONDS_IN_MONTH);


/**
 * Максимален срок за минали цени с които да работи офертата
 */
defIfNot('SALE_MAX_PAST_PRICE', type_Time::SECONDS_IN_MONTH * 2);


/**
 * Колко време след като не е платена една продажба, да се отбелязва като пресрочена
 */
defIfNot('SALE_OVERDUE_CHECK_DELAY', 60 * 60 * 6);


/**
 * Продажби до колко дни назад без да са модифицирани да се затварят автоматично
 */
defIfNot('SALE_CLOSE_OLD_SALES', 60 * 60 * 24 * 3);


/**
 * Начален номер на фактурите
 */
defIfNot('INV_MIN_NUMBER', '0');


/**
 * Краен номер на фактурите
 */
defIfNot('INV_MAX_NUMBER', '10000000');


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
			'INV_LAYOUT'               => array("enum(Normal=Основен изглед,Letter=Изглед за писмо)", 'caption=Изглед за фактурата->Шаблон'),
			'QUOTE_LAYOUT'             => array("enum(Normal=Основен изглед,Letter=Изглед за писмо)", 'caption=Изглед за оферта->Шаблон'),
			'SALE_OVERDUE_CHECK_DELAY' => array("time", "caption=Продажби->Толеранс за пресрочване"),
			'SALE_MAX_FUTURE_PRICE'    => array("time(uom=months,suggestions=1 месец|2 месеца|3 месеца)", 'caption=Продажби->Ценови период в бъдещето'),
			'SALE_MAX_PAST_PRICE'      => array("time(uom=months,suggestions=1 месец|2 месеца|3 месеца)", 'caption=Продажби->Ценови период в миналото'),
			'SALE_CLOSE_OLD_SALES'     => array("time(uom=days,suggestions=1 ден|2 дена|3 дена)", 'caption=Продажби->Затваряне на по-стари от'),
			'INV_MIN_NUMBER'           => array('int', 'caption=Номер на фактура->Долна граница'),
			'INV_MAX_NUMBER'           => array('int', 'caption=Номер на фактура->Горна граница'),
	);
	
	
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'sales_Invoices',
            'sales_InvoiceDetails',
            'sales_Sales',
            'sales_SalesDetails',
        	'sales_Routes',
        	'sales_Quotations',
        	'sales_QuotationsDetails',
    		'sales_SaleRequests',
    		'sales_SaleRequestDetails',
    		'sales_ClosedDealsDebit',
    		'sales_ClosedDealsCredit',
    		'sales_Services',
    		'sales_ServicesDetails',
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
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
        
        // Добавяме политиката "По последна продажна цена"
        core_Classes::add('sales_SalesLastPricePolicy');
        
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
