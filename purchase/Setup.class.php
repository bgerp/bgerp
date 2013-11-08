<?php



/**
 * Покупки - инсталиране / деинсталиране
 *
 *
 * @category  bgerp
 * @package   purchase
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
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
    var $startCtr = 'purchase_Offers';
    
    
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
            'purchase_Requests',
            'purchase_RequestDetails',
            'purchase_Debt',
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'purchase';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.3, 'Логистика', 'Доставки', 'purchase_Offers', 'default', "purchase, ceo"),
        );


	/**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
        
        // Добавяме политиката "По последна покупна цена"
        core_Classes::add('purchase_RequestLastPricePolicy');
        
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
