<?php

/**
 * class deals_Setup
 *
 * Инсталиране/Деинсталиране на
 * финансови сделки
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class deals_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'deals_Deals';
    
    
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
            'deals_Deals',
    		'deals_AdvanceDeals',
    		'deals_DebitDocuments',
    		'deals_CreditDocuments',
    		'deals_ClosedDeals',
    		'deals_AdvanceReports',
    		'deals_AdvanceReportDetails',
    		'migrate::removeOldInterface',
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'deals';

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(2.3, 'Финанси', 'Сделки', 'deals_Deals', 'default', "dealsMaster, ceo"),
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
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
    	
    	$html .= core_Roles::addOnce('dealsMaster', 'deals');
    	
    	return $html;
    }

    
    /**
     * Миграция за премахване на стария интерфейс
     */
    public function removeOldInterface()
    {
    	if($listRec = acc_Lists::fetchBySystemId('financialDeals')){
    		if(!$listRec->regInterfaceId){
    			$listRec->regInterfaceId = core_Interfaces::fetchField('#name = "deals_DealsAccRegIntf"');
    			acc_Lists::save($listRec);
    		}
    	}
    }
}