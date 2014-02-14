<?php
/**
 * Помощен клас за приключване на сделки и проверка за пресрочено плащане.
 * Изпозлва се от Покупките и продажбите за изпълняване на действия по крон
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 *
 */
class acc_CronDealsHelper
{
	
	
	/**
	 * Инстанция на sales_Sales или purchase_Purchases
	 */
	private $className;
    
	
	
	/**
     * Инициализиране 
     */
    function init($params = array())
    {
    	expect($this->className = $params['className']);
    }
    
    
    /**
     * Проверява дали сделките са с пресрочено плащане
     */
    public function checkPayments($overdueDelay)
    {
    	$Class = cls::get($this->className);
    	
    	$now = dt::now();
    	expect(cls::haveInterface('bgerp_DealAggregatorIntf', $Class));
    	
    	// Проверяват се всички активирани и продажби с чакащо плащане
    	$query = $Class->getQuery();
    	$query->where("#paymentState = 'pending'");
    	$query->where("#state = 'active'");
    	$query->where("ADDDATE(#modifiedOn, INTERVAL {$overdueDelay} SECOND) <= '{$now}'");
    	
    	while($rec = $query->fetch()){
    		if($rec->state == 'closed'){
    			
    			// Ако състоянието е затворено, то приема че сделката е платена
    			$rec->paymentState = 'paid';
    			$Class->save($rec);
    		} else {
    			try{
    				// Намира се метода на плащане от интерфейса
    				$dealInfo = $Class->getAggregateDealInfo($rec->id);
    			} catch(Exception $e){
    				
    				// Ако има проблем при извличането се продължава
    				core_Logs::add($Class, $rec->id, "Проблем при извличането 'bgerp_DealAggregatorIntf': '{$e->getMessage()}'");
    				continue;
    			}
    			
    			$mId = ($dealInfo->agreed->payment->method) ? $dealInfo->agreed->payment->method : $dealInfo->invoiced->payment->method;
    			if($mId){
    				// Намира се датата в реда фактура/експедиция/сделка
    				foreach (array('invoiced', 'shipped', 'agreed') as $asp){
    					if($date = $dealInfo->$asp->valior){
    						break;
    					}
    				}
    				
    				// Извлича се платежния план
    				$plan = cond_PaymentMethods::getPaymentPlan($mId, $rec->amountDeal, $date);
    				
    				// Проверка дали продажбата е пресрочена
    				if(cond_PaymentMethods::isOverdue($plan, $rec->amountDelivered - $rec->amountPaid)){
    				
    					// Ако да, то продажбата се отбелязва като пресрочена
    					$rec->paymentState = 'overdue';
    					
    					try{
    						$Class->save($rec);
    					}catch(Exception $e){
    						
    						// Ако има проблем при обновяването
    						core_Logs::add($Class, $rec->id, "Проблем при проверката дали е пресрочена сделката: '{$e->getMessage()}'");
    					}
    				}
    			}
    		}
    	}
    }
    
    
    /**
     * Приключва остарялите сделки
     */
    public function closeOldDeals($olderThan, $tolerance, $closeDocName)
    {
    	$className = $this->className;
    	
    	expect(cls::haveInterface('bgerp_DealAggregatorIntf', $this->className));
    	$query = $className::getQuery();
    	$ClosedDeals = cls::get($closeDocName);
    	
    	// Текущата дата
    	$now = dt::mysql2timestamp(dt::now());
    	$oldBefore = dt::timestamp2mysql($now - $olderThan);
    	
    	$query->EXT('threadModifiedOn', 'doc_Threads', 'externalName=last,externalKey=threadId');
    	
    	// Закръглената оставаща сума за плащане
    	$query->XPR('toPay', 'double', 'ROUND(#amountDelivered - #amountPaid, 2)');
    	$query->XPR('toInvoice', 'double', 'ROUND(#amountDelivered - #amountInvoiced, 2)');
    	
    	// Само активни продажби
    	$query->where("#state = 'active'");
    	$query->where("#amountDelivered IS NOT NULL AND #amountPaid IS NOT NULL");
    	
    	// На които треда им не е променян от определено време
    	$query->where("#threadModifiedOn <= '{$oldBefore}'");
    	
    	// Доставеното - платеното трябва да е в допустимия толеранс
    	$query->where("#toPay BETWEEN -{$tolerance} AND {$tolerance}");
    	
    	// Ако трябва да се фактурират и са доставеното - фактурираното е в допустими граници
    	$query->where("#makeInvoice = 'yes' AND #toInvoice BETWEEN -{$tolerance} AND {$tolerance}");
    	
    	// Или не трябва да се фактурират
    	$query->orWhere("#makeInvoice = 'no'");
    	
    	// Подреждаме ги в низходящ ред
    	$query->orderBy('id', 'DESC');
    	
    	// Всяка намерената сделка, се приключва като платена
    	while($rec = $query->fetch()){
    		try{
    			// Създаване на приключващ документ-чернова
    			$clId = $ClosedDeals->create($this->className, $rec);
    			
    			// Контиране на документа
    			$ClosedDeals->conto($clId);
	    
    			// Продажбата/покупката се отбелязват като птиключени и платени
    			$rec->state = 'closed';
    			$rec->paymentState = 'paid';
    	
    			// Обновяване състоянието на документа
    			$className::save($rec);
    			
    		} catch(Exception $e){
    			
    			// Ако има проблем при обновяването
    			core_Logs::add($this->className, $rec->id, "Проблем при автоматичното приключване на сделка: '{$e->getMessage()}'");
    		}
    	}
    }
}