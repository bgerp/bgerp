<?php

/**
 * class cash_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъра Case
 *
 *
 * @category  bgerp
 * @package   cash
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cash_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'cash_Cases';
    
    
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
    var $info = "Каси, кешови операции и справки";
    
	
	/**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'cash_Cases',
        	'cash_Pko',
        	'cash_Rko',
        	'cash_InternalMoneyTransfer',
        	'cash_ExchangeDocument',
    		'migrate::updateDocumentStates',
    		'migrate::updateDocuments'
    		
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'cash';

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(2.2, 'Финанси', 'Каси', 'cash_Cases', 'default', "cash, ceo"),
        );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    var $defClasses = "cash_reports_CashImpl";
    
    
    /**
     * Път до css файла
     */
//    var $commonCSS = 'cash/tpl/styles.css';
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $html = parent::install();
        
    	if($roleRec = core_Roles::fetch("#role = 'masterCash'")){
    		core_Roles::delete("#role = 'masterCash'");
    	}
    	
        // Добавяне на роля за старши касиер
        $html .= core_Roles::addOnce('cashMaster', 'cash');
    	
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
    
    
    /**
     * Миграция на старите документи
     */
    function updateDocumentStates()
    {
    	$documents = array('cash_Pko', 'cash_Rko', 'cash_InternalMoneyTransfer', 'cash_ExchangeDocument');
    	core_App::setTimeLimit(150);
    	
    	foreach ($documents as $doc){
    		try{
    			$Doc = cls::get($doc);
    			$Doc->setupMvc();
    			
    			$query = $Doc->getQuery();
    			$query->where("#state = 'closed'");
    			$query->show('state');
    			
    			while($rec = $query->fetch()){
    				$rec->state = 'active';
    				$Doc->save_($rec, 'state');
    			}
    		} catch(core_exception_Expect $e){
    			
    		}
    	}
    }
    
    
    /**
     * Миграция на документите
     */
    public function updateDocuments()
    {
    	core_App::setTimeLimit(300);
    	
    	$array = array('cash_Pko', 'cash_Rko');
    	
    	foreach ($array as $doc){
    		$Doc = cls::get($doc);
    		$Doc->setupMvc();
    		
    		$query = $Doc->getQuery();
    		$query->where('#amountDeal IS NULL');
    		while($rec = $query->fetch()){
    			
    			try{
    				$firstDoc = doc_Threads::getFirstDocument($rec->threadId);
    				$firstDocRec = $firstDoc->fetch();
    				$dealCurrencyId = currency_Currencies::getIdByCode($firstDocRec->currencyId);
    				$dealRate = $firstDocRec->currencyRate;
    				 
    				$dealAmount = ($rec->amount * $rec->rate) / $dealRate;
    				 
    				$rec->amountDeal = $dealAmount;
    				$rec->dealCurrencyId = $dealCurrencyId;
    				 
    				$Doc->save_($rec, 'amountDeal,dealCurrencyId');
    			} catch(core_exception_Expect $e){
    				reportException($e);
    			}
    		}
    	}
    }
}