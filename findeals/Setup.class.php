<?php

/**
 * class findeals_Setup
 *
 * Инсталиране/Деинсталиране на
 * финансови сделки
 *
 *
 * @category  bgerp
 * @package   findeals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class findeals_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'findeals_Deals';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'drdata=0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Финансови операции";
    
	
	/**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'findeals_Deals',
    		'findeals_AdvanceDeals',
    		'findeals_DebitDocuments',
    		'findeals_CreditDocuments',
    		'findeals_ClosedDeals',
    		'findeals_AdvanceReports',
    		'findeals_AdvanceReportDetails',
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = array(
    		array('pettyCashReport'),
    		array('findeals', 'pettyCashReport,seePrice'),
    		array('findealsMaster', 'findeals'),
    );

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(2.3, 'Финанси', 'Сделки', 'findeals_Deals', 'default', "findeals, ceo, acc"),
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
}