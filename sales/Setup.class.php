<?php
/**
 *  Изглед за фактурата
 */
defIfNot('INV_LAYOUT', '');


/**
 * Покупки - инсталиране / деинсталиране
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
    var $startCtr = 'sales_Deals';
    
    
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
			'INV_LAYOUT' => array ('varchar'),
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
        	'sales_SalesLastPricePolicy',
        	'sales_Quotations',
        	'sales_QuotationsDetails',
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
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}
