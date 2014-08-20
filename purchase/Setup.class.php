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
     * Роли за достъп до модула
     */
    var $roles = 'purchase';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.1, 'Логистика', 'Доставки', 'purchase_Purchases', 'default', "purchase, ceo"),
        );


    /**
	 * Описание на конфигурационните константи
	 */
	var $configDescription = array(
			'PURCHASE_OVERDUE_CHECK_DELAY' => array("time", "caption=Толеранс за просрочване на покупката->Време"),
			'PURCHASE_CLOSE_OLDER_THAN'    => array("time(uom=days,suggestions=1 ден|2 дена|3 дена)", 'caption=Изчакване преди автоматично приключване на покупката->Дни'),
			'PURCHASE_CLOSE_OLDER_NUM'     => array("int", 'caption=По колко покупки да се приключват автоматично на опит->Брой'),
	);
	
	
	/**
	 * Път до css файла
	 */
//	var $commonCSS = 'purchase/tpl/invoiceStyles.css';
	
	
	/**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
        
        // Добавяме политиката "По последна покупна цена"
        $html .= core_Classes::add('purchase_PurchaseLastPricePolicy');
        
        // Добавяне на роля за старши куповач
        $html .= core_Roles::addRole('purchaseMaster', 'purchase') ? "<li style='color:green'>Добавена е роля <b>purchaseMaster</b></li>" : '';
        
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
