<?php


/**
 * Покупки до колко дни назад без да са модифицирани да се затварят автоматично
 */
defIfNot('PURCHASE_CLOSE_OLDER_THAN', 60 * 60 * 24 * 3);


/**
 * Колко покупки да се приключват автоматично брой
 */
defIfNot('PURCHASE_CLOSE_OLDER_NUM', 15);


/**
 * Колко време да се изчака след активиране на покупка, преди да се провери дали е просрочена
 */
defIfNot('PURCHASE_OVERDUE_CHECK_DELAY', 60 * 60 * 6);


/**
 * Колко време да се изчака след активиране на покупка, преди да се провери дали е просрочена
 */
defIfNot('PURCHASE_OVERDUE_CHECK_DELAY', 60 * 60 * 6);


/**
 * Дали да се въвежда курс в покупката
 */
defIfNot('PURCHASE_USE_RATE_IN_CONTRACTS', 'no');


/**
 * Срок по подразбиране за плащане на фактурата
 */
defIfNot('PURCHASE_INVOICE_DEFAULT_VALID_FOR', 60 * 60 * 24 * 3);


/**
 * Роли за добавяне на артикул в продажба от бутона 'Артикул'
 */
defIfNot('PURCHASE_ADD_BY_PRODUCT_BTN', '');


/**
 * Роли за добавяне на артикул в продажба от бутона 'Списък'
*/
defIfNot('PURCHASE_ADD_BY_LIST_BTN', '');


/**
 * При приключване на покупки да се затварят и недоставените над определен процент
 */
defIfNot('PURCHASE_CLOSE_UNDELIVERED_OVER', '0.9');


/**
 * Покупки - инсталиране / деинсталиране
 *
 *
 * @category  bgerp
 * @package   purchase
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class purchase_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'purchase_Purchases';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Покупки - доставки на стоки, материали и консумативи";
    
    
   /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'purchase_Offers',
            'purchase_Purchases',
            'purchase_PurchasesDetails',
    		'purchase_Services',
    		'purchase_ServicesDetails',
    		'purchase_ClosedDeals',
    		'purchase_Invoices',
    		'purchase_InvoiceDetails'
        );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.1, 'Логистика', 'Доставки', 'purchase_Purchases', 'default', "purchase, ceo, acc"),
        );


    /**
	 * Описание на конфигурационните константи
	 */
	var $configDescription = array(
		'PURCHASE_OVERDUE_CHECK_DELAY'       => array("time", "caption=Толеранс за просрочване на покупката->Време"),
		'PURCHASE_CLOSE_OLDER_THAN'          => array("time(uom=days,suggestions=1 ден|2 дена|3 дена)", 'caption=Изчакване преди автоматично приключване на покупката->Дни'),
		'PURCHASE_CLOSE_OLDER_NUM'           => array("int", 'caption=По колко покупки да се приключват автоматично на опит->Брой'),
		'PURCHASE_USE_RATE_IN_CONTRACTS'     => array("enum(no=Не,yes=Да)", 'caption=Ръчно въвеждане на курс в покупките->Избор'),
		'PURCHASE_INVOICE_DEFAULT_VALID_FOR' => array("time", 'caption=Срок за плащане по подразбиране->Срок'),
		'PURCHASE_CLOSE_UNDELIVERED_OVER'    => array('percent', 'caption=Приключване на покупки с доставка над определен процент->Процент'),
		'PURCHASE_ADD_BY_PRODUCT_BTN' => array("keylist(mvc=core_Roles,select=role,groupBy=type)", 'caption=Необходими роли за добавяне на артикули в покупка от->Артикул'),
		'PURCHASE_ADD_BY_LIST_BTN'    => array("keylist(mvc=core_Roles,select=role,groupBy=type)", 'caption=Необходими роли за добавяне на артикули в покупка от->Списък'),
	);
	
	
	/**
	 * Роли за достъп до модула
	 */
	var $roles = array(
			array('purchase', 'invoicer,seePrice'),
			array('purchaseMaster', 'purchase'),
	);
	
	
	/**
	 * Дефинирани класове, които имат интерфейси
	 */
	var $defClasses = "purchase_PurchaseLastPricePolicy";
	
	
	/**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
        
        // Добавяне на дефолтни роли за бутоните
        foreach (array('PURCHASE_ADD_BY_PRODUCT_BTN', 'PURCHASE_ADD_BY_LIST_BTN') as $const){
        	if(strlen($config->{$const}) === 0){
        		$keylist = core_Roles::getRolesAsKeylist('purchase,ceo');
        		core_Packs::setConfig('purchase', array($const => $keylist));
        	}
        }
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
}
