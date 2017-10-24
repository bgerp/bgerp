<?php


/**
 * Толеранс за допустимо разминаване в салдото->Сума
 */
defIfNot('DEALS_BALANCE_TOLERANCE', '0.01');


/**
 * class deals_Setup
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
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
     * Екшън - входна точка в пакета.
     */
    var $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'drdata=0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Помощни класове за бизнес документите";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'deals_OpenDeals',
    		'migrate::updateOpenDeals2',
    		'migrate::updatedClosedDealsValior',
        );

    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
    		'DEALS_BALANCE_TOLERANCE' => array("percent(min=0)", 'caption=Процент за допустимо разминаване в салдото според сумата->Процент'),
    );
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    var $defClasses = "deals_reports_ArrearsImpl";
    
    
     /**
     * Роли за достъп до модула
     */
    var $roles = 'dealJoin';
    
    
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
     * Ъпдейт на вече отворените сделки
     */
    function updateOpenDeals2()
    {
    	if(!deals_OpenDeals::count()){
    		return;
    	}
    	
    	core_App::setTimeLimit(800);
    	 
    	$query = deals_OpenDeals::getQuery();
    	$query->where("#state = 'active'");
    	$query->where("#amountDelivered IS NULL");
    	
    	while($rec = $query->fetch()){
    		if(cls::load($rec->docClass, TRUE)){
    			$Class = cls::get($rec->docClass);
    			
    			try{
    				$dRec = $Class->fetch($rec->docId);
    				deals_OpenDeals::saveRec($dRec, $Class);
    			} catch(core_exception_Expect $e){
    			    reportException($e);
    			}
    		}
    	}
    }
    
    
    /**
     * Миграция на полето вальор на приключващия документ
     */
    function updatedClosedDealsValior()
    {
    	core_App::setTimeLimit(800);
    	foreach (array('sales_ClosedDeals', 'purchase_ClosedDeals', 'findeals_ClosedDeals') as $Doc){
    		if(cls::load($Doc, TRUE)){
    			$Doc = cls::get($Doc);
    			$Doc->setupMvc();
    			
    			$query = $Doc->getQuery();
    			$query->where("#classId = '{$Doc->getClassId()}'");
    			$query->where("#valior IS NULL");
    			
    			while($rec = $query->fetch()){
    				try{
    					$jRec = acc_Journal::fetchByDoc($Doc, $rec->id);
    					if($jRec){
    						$valior = $jRec->valior;
    					} else {
    						if($rec->createdBy == core_Users::SYSTEM_USER){
    							$biggestValiorInThread = $Doc->getBiggestValiorInThread($rec);
    								
    							$period = acc_Periods::fetchByDate($biggestValiorInThread);
    								
    							if($period->state != 'closed'){
    								$valior = $biggestValiorInThread;
    							} elseif($period->state == 'closed') {
    					
    								$createdOn = dt::verbal2mysql($rec->createdOn, FALSE);
    								$period = acc_Periods::fetchByDate($createdOn);
    								if($period->state != 'closed'){
    									$valior = $Doc->getBiggestValiorInThread($rec);
    								} elseif($period->state == 'closed') {
    									$valior = $rec->createdOn;
    								}
    							}
    						} else {
    							$valior = $rec->createdOn;
    						}
    					}
    					
    					$rec->valior = dt::verbal2mysql($valior, FALSE);
    					$Doc->save_($rec, 'valior');
    				} catch(core_exception_Expect $e){
    					reportException($e);
    				}
    			}
    		}
    	}
    }
}
