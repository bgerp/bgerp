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
    		'migrate::removeOldRoles',
    		'migrate::updateDocuments',
    		'migrate::updateReports',
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = array(
    		array('pettyCashReport'),
    		array('findeals', 'pettyCashReport'),
    		array('findealsMaster', 'findeals'),
    );

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(2.3, 'Финанси', 'Сделки', 'findeals_Deals', 'default', "findealsMaster, ceo"),
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
     * Миграция преименуване на старите роли
     */
    public function removeOldRoles()
    {
    	if($rec = core_Roles::fetch("#role = 'deals'")){
    		$rec->role = 'findeals';
    	
    		core_Roles::delete("role = 'findeals'");
    		core_Roles::save($rec);
    	}
    	 
    	if($rec = core_Roles::fetch("#role = 'dealsMaster'")){
    		$rec->role = 'findealsMaster';
    		 
    		core_Roles::delete("role = 'findealsMaster'");
    		core_Roles::save($rec);
    	}
    }
    

    /**
     * Миграция на документите
     */
    public function updateDocuments()
    {
    	core_App::setTimeLimit(300);
    	 
    	$array = array('findeals_DebitDocuments', 'findeals_CreditDocuments');
    	 
    	foreach ($array as $doc){
    		$Doc = cls::get($doc);
    		$Doc->setupMvc();
    
    		$query = $Doc->getQuery();
    		$query->where('#amountDeal IS NULL');
    		while($rec = $query->fetch()){
    			try{
    				$dealRec = findeals_Deals::fetch($rec->dealId);
    				$rec->amountDeal = ($rec->amount * $rec->rate) / $dealRec->currencyRate;
    				$rec->currencyId = currency_Currencies::getIdByCode($dealRec->currencyId);
    				
    				$Doc->save_($rec, 'amountDeal,currencyId');
    			} catch(core_exception_Expect $e){
    				reportException($e);
    			}
    		}
    	}
    }
    
    
    /**
     * Ъпдейт на авансовите отчети
     */
    function updateReports()
    {
    	$Reports = cls::get('findeals_AdvanceReports');
    	$Reports->setupMvc();
    	
    	$query = $Reports->getQuery();
    	$query->where("#contragentClassId IS NULL");
    	$query->where("#contragentId IS NULL");
    	
    	while($rec = $query->fetch()){
    		try{
    			$Cover = doc_Folders::getCover($rec->folderId);
    			$rec->contragentClassId = $Cover->getClassId();
    			$rec->contragentId = $Cover->that;
    			$Reports->save_($rec, 'contragentClassId,contragentId');
    		} catch(core_exception_Expect $e){
    			reportException($e);
    		}
    	}
    }
}