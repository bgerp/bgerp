<?php
/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа acc_ClosePeriods
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class acc_transaction_ClosePeriod
{
	
	
    /**
     * 
     * @var acc_ClosePeriod
     */
    public $class;
    
    
    /**
     * Работен кеш за запомняне на направения, оборот докато не е влязал в счетоводството
     */
    private  $blAmount = 0;
    
    
    /**
     * Извлечен краткия баланс
     */
    private $shortBalance;
    
    
    /**
     * Дата
     */
    private $date;


    /**
     * @param int $id
     * @return stdClass
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public function getTransaction($id)
    {
    	set_time_limit(600);
    	
    	// Извличане на мастър-записа
    	expect($rec = $this->class->fetchRec($id));
    	$this->periodRec = acc_Periods::fetch($rec->periodId);
    	
    	$result = (object)array(
    			'reason' => "Приключване на период \"" . $this->class->getVerbal($rec, 'periodId') . "\"",
    			'valior' => $this->periodRec->end, // Вальора е последния ден от периода
    			'totalAmount' => 0,
    			'entries' => array()
    	);
    	
    	$this->date = acc_Periods::forceYearItem($rec->valior);
    	
    	$total = $incomeFromProducts = 0;
    	$this->balanceId =  acc_Balances::fetchField("#periodId = {$this->periodRec->id}");
    	$incomeRes = array();
    	
    	$entries1 = $this->transferIncome($result->totalAmount, $incomeRes);
    	if(count($entries1)){
    		$result->entries = array_merge($result->entries, $entries1);
    	}
    	
    	$entries2 = $this->transferIncomeToYear($result->totalAmount, $incomeRes);
    	if(count($entries2)){
    		$result->entries = array_merge($result->entries, $entries2);
    	}
    	
    	$entries3 = $this->transferVat($result->totalAmount, $rec);
    	if(count($entries3)){
    		$result->entries = array_merge($result->entries, $entries3);
    	}
    	
    	$entries4 = $this->transferCurrencyDiffs($result->totalAmount, $rec);
    	if(count($entries4)){
    		$result->entries = array_merge($result->entries, $entries4);
    	}
    	
    	return $result;
    }
    
    
    /**
     * Прехвърляне на част от начисленото по сметка 4535 - "ДДС по касови бележки" ДДС по сметка 4532 - "Начислен ДДС за продажбите"
     * 
     * 		Dt: 4535 - ДДС по касови бележки
     *  	Ct: 4532 - Начислен ДДС за продажбите
     *  
     * Приспадане на платеното ("Начислен ДДС за покупките") от полученото (Начислен ДДС за продажбите) ДДС
     *  
     *  	Dt: 4532 - Начислен ДДС за продажбите
     *  	Ct: 4531 - Начислен ДДС за покупките
     *  
     * Ако с/ка 4532 има кредитно (или нулево) салдо - това означава, че имаме ДДС за внасяне
     *  
     *  	Dt: 4532 - Начислен ДДС за продажбите
     *  	Ct: 4539 - ДДС за внасяне
     *  
     * Ако с/ка 4532 има дебитно салдо - това означава, че имаме ДДС за възстановяване
     * 
     * 		Dt: 4538 - ДДС за възстановяване
     * 		Ct: 4532 - Начислен ДДС за продажбите
     * 
     * Приспадане на евентуално налично салдо по с/ка 4538 - "ДДС за възстановяване" от евентуално начисленото в настоящия период "ДДС за внасяне" по с/ка 4539
     * 
     * 		Dt: 4539 - ДДС за внасяне
     * 		Ct: 4538 - ДДС за възстановяване
     * 
     * Прехвърляне на останалото кредитно салдо в с/ка 4535 - "ДДС по касови бележки" като извънреден приход -
     * 
     * 		Dt: 4535 - ДДС по касови бележки
     * 		Ct: 123 - Печалби и загуби от текущата година
     */
    private function transferVat(&$total, $rec)
    {
    	// Общата сума въведена в документа извлечена от касовия апарат
    	$amountFromFiscPrinter = $rec->amountVatGroup1 + $rec->amountVatGroup2 + $rec->amountVatGroup3 + $rec->amountVatGroup4;
    	
    	// Колко е сумата по фактури без касови бележки
    	$diffAmount = $amountFromFiscPrinter - $rec->amountFromInvoices;
    	
    	$entries = array();
    	
    	$entries[] = array('amount' => $diffAmount, 'debit' => array('4535'), 'credit' => array('4532'), 'reason' => 'Начисляваме сумата от касовия апарат');
    	// ДДС по продажби без фактура
    	$total += $diffAmount;
    	
    	$bQuery = acc_BalanceDetails::getQuery();
    	acc_BalanceDetails::filterQuery($bQuery, $this->balanceId, '4535');
    	$amount4535 = $bQuery->fetch()->blAmount;
    	
    	$amount4535 += $diffAmount;
    	
    	$entries[] = array('amount' => abs($amount4535), 'debit' => array('4535'), 'credit' => array('123', $this->date->year), 'reason' => 'ДДС - остатъци');
    	$total += abs($amount4535);
    	
    	$bQuery = acc_BalanceDetails::getQuery();
    	acc_BalanceDetails::filterQuery($bQuery, $this->balanceId, '4531');
    	
    	$bQuery->where("#ent1Id IS NULL && #ent2Id IS NULL && #ent3Id IS NULL");
    	$amount4531 = $bQuery->fetch()->blAmount;
    	
    	$bQuery = acc_BalanceDetails::getQuery();
    	acc_BalanceDetails::filterQuery($bQuery, $this->balanceId, '4532');
    	$bQuery->where("#ent1Id IS NULL && #ent2Id IS NULL && #ent3Id IS NULL");
    	$amount4532 = $bQuery->fetch()->blAmount;
    	
    	// Местим дебитното салдо на '4531' в '4532'
    	$amount4531 = ($amount4531) ? $amount4531 : 0;
    	
    	// Приспадане на ДДС по покупки
    	$entries[] = array('amount' => abs($amount4531), 'debit' => array('4532'), 'credit' => array('4531'), 'reason' => 'Приспадане на ДДС по покупки');
    	$total += abs($amount4531);
    	
    	$amount = $amount4532 - $diffAmount + abs($amount4531);
    	
    	// Ако по 4532 накрая имаме кредитно или дебитно салдо
    	if($amount <= 0){
    		
    		// ДДС за внасяне
    		$entries[] = array('amount' => abs($amount), 'debit' => array('4532'), 'credit' => array('4539'), 'reason' => 'ДДС за внасяне');
    	} else {
    		
    		// ДДС за възстановяване
    		$entries[] = array('amount' => abs($amount), 'debit' => array('4538'), 'credit' => array('4532'), 'reason' => 'ДДС за възстановяване');
    	}
    	 
    	$total += abs($amount);
    	 
    	if($amount <= 0){
    		$bQuery = acc_BalanceDetails::getQuery();
    		acc_BalanceDetails::filterQuery($bQuery, $this->balanceId, '4538');
    		$bQuery->where("#ent1Id IS NULL && #ent2Id IS NULL && #ent3Id IS NULL");
    		$amount4538 = $bQuery->fetch()->blAmount;
    		
    		if($amount4538){
    			$rAmount = ($amount4538 > abs($amount)) ? abs($amount) : $amount4538;
    			
    			// Приспаднато ДДС за възстановяване
    			$entries[] = array('amount' => $rAmount, 'debit' => array('4539'), 'credit' => array('4538'), 'reason' => 'Приспаднато ДДС за възстановяване');
    			$total += $rAmount;
    		}
    	}
    	
    	return $entries;
    }
    
    
    /**
     * @param int $id
     * @return stdClass
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public function finalizeTransaction($id)
    {
    	$rec = $this->class->fetchRec($id);
    	$rec->state = 'active';
    
    	return $this->class->save($rec, 'state');
    }
    
    
    /**
     * Отчитане на финансовия резултат от сделката по сметка 123 - Печалби и загуби от текущата година
     * Обобщаване на резултата от "Продажба"-та на ниво Сделка в с/ка 700 - Приходи от продажби (по сделки)
     *
     * Записа за конкретния артикул по сметка 701 (сметка от гр. 70) има Дебитно (Dt) салдо - т.е.
     *
     * 		Отнасяме резултата за артикула като разход по сметка 700 - Приходи от продажби (по сделки)
     *
     * 			Dt: 700 - Приходи от продажби (по сделки)
     * 				Ct: 701 - Приходи от продажби на Стоки и Продукти
     * 				Ct: 706 - Приходи от продажба на суровини/материали
     * 				Ct: 703 - Приходи от продажби на Услуги
     *
     * Записа за конкретния артикул по сметка 701 (сметка от гр. 70) има Кредитно (Ct) или нулево "0" салдо
     *
     * 		Отнасяме резултата за артикула като приход по сметка 700 - Приходи от продажби (по сделки)
     *
     * 				Dt: 701 - Приходи от продажби на Стоки и Продукти
     * 				Dt: 706 - Приходи от продажба на суровини/материали
     * 				Dt: 703 - Приходи от продажби на Услуги
     * 			Ct: 700 - Приходи от продажби (по сделки)
     */
    protected function transferIncome(&$total, &$incomeRes)
    {
    	$entries = array();
    	// Приходи от продажби по артикули
    	if(!count($this->balanceId)) return $entries;
    	
    	$bQuery = acc_BalanceDetails::getQuery();
    	acc_BalanceDetails::filterQuery($bQuery, $this->balanceId, '701,706,703,700');
    	$bQuery->where("#ent1Id IS NOT NULL || #ent2Id IS NOT NULL || #ent3Id IS NOT NULL");
    	$balanceArr = $bQuery->fetchAll();
    	
    	$accIds = array();
    	$dealPosition = array();
    	
    	foreach (arr::make('701,706,703,700') as $systemId){
    		$accId = acc_Accounts::getRecBySystemId($systemId)->id;
    		$accIds[$accId] = $systemId;
    		$dealPosition[$accId] = acc_Lists::getPosition($systemId, 'deals_DealsAccRegIntf');
    		$dealPosition[$accId] = "ent{$dealPosition[$accId]}Id";
    	}
    	
    	if(!count($balanceArr)) return $entries;
    	 
    	foreach ($balanceArr as $rec){
    		if($accIds[$rec->accountId] != '700'){
    			$arr1 = array('700', $rec->ent1Id, $rec->ent2Id);
    			$arr2 = array($accIds[$rec->accountId], $rec->ent1Id, $rec->ent2Id, $rec->ent3Id, 'quantity' => $rec->blQuantity);
    			
    			// Ако перото на продажбата не е затворено, пропускаме го !
    			if(acc_Items::fetchField($rec->{$dealPosition[$rec->accountId]}, 'state') == 'active') continue;
    			
    			// Пропускаме нулевите салда
    			if(round($rec->blAmount, 2) == 0) continue;
    			
    			if($rec->blAmount > 0){
    				$debitArr = $arr1;
    				$creditArr = $arr2;
    			} else {
    				$debitArr = $arr2;
    				$creditArr = $arr1;
    			}
    			
    			$incomeRes[$rec->ent1Id][$rec->ent2Id] += $rec->blAmount;
    			$total += abs($rec->blAmount);
    			
    			switch($rec->accountId){
    				case '706':
    					$reason = 'Приходи от продажба (суровини/материали)';
    					break;
    				case '703':
    					$reason = 'Приходи от продажби (Услуги)';
    					break;
    				default:
    					$reason = 'Приходи от продажби (Стоки и Продукти)';
    					break;
    			}
    			
    			$entries[] = array('amount' => abs($rec->blAmount), 'debit' => $debitArr, 'credit' => $creditArr, 'reason' => $reason);
    		} else {
    			
    			// Ако имаме крайно салдо по 700, само го добавяме към натрупването
    			$incomeRes[$rec->ent1Id][$rec->ent2Id] += $rec->blAmount;
    		}
    	}
    	
    	return $entries;
    }
    
    
    /**
     * Отчитане на финансовия резултат от сделката по сметка 123 - Печалби и загуби от текущата година
     *
     * Сметка 700 има Дебитно (Dt) салдо
     *
     * 		Отнасяме резултата от сделката като загуба по сметка 123, със сумата на дебитното салдо на с/ка 700 по сделката
     *
     *			Dt: 123 - Печалби и загуби от текущата година
     *			Ct: 700 - Приходи от продажби (по сделки)  (вече на ниво "Сделка")
     *
     * Сметка 700 има Кредитно (Ct) или нулево "0" салдо
     *
     * 		Отнасяме резултата от сделката като печалба по сметка 123, със сумата на кредитното салдо на с/ка 700 по сделката
     *
     * 			Dt: 700 - Приходи от продажби (по сделки)  (вече на ниво "Сделка")
     * 			Ct: 123 - Печалби и загуби от текущата година
     */
    protected function transferIncomeToYear(&$total, $incomeRes)
    {
    	$entries = array();
		
		foreach ($incomeRes as $ctrItem => $arr){
    		foreach ($arr as $dealItem => $sum){
    			$arr1 = array('700', $ctrItem, $dealItem);
    			$arr2 = array('123', $this->date->year);
    			$total += abs($sum);
    				 
    			// Дебитно салдо
    			if($sum > 0){
    				$debitArr = $arr2;
    				$creditArr = $arr1;
    			} else {
    						
    				// Кредитно салдо
    				$debitArr = $arr1;
    				$creditArr = $arr2;
    			}
    			
    			$entries[] = array('amount' => abs($sum), 'debit' => $debitArr, 'credit' => $creditArr, 'reason' => 'Приходи от продажби (по сделки)');
    		}
    	}
    	
    	
	    return $entries;
    }
    
    
    /**
     * Отчитане на резултата по сметка 481 - "Разчети по курсови разлики" и отнасянето му по сметките за финансови разходи / приходи
     * 
     * Ако с/ка 481 има дебитно салдо, отчитаме го като разход
     * 		
     * 		Dt: 624 - Разходи по валутни операции
     * 		Ct: 481 - Разчети по курсови разлики		          (Валута)
     * 
     * 		с дебитното салдо по с/ка 481, отнасяме го като намаление на печалбата
     * 
     * 		Dt: 123 - Печалби и загуби от текущата година 	      (Година, Месец)
     * 		Ct: 624 - Разходи по валутни операции
     * 
     * Ако с/ка 481 има кредитно салдо, отчитаме го като приход
     * 
     * 		Dt: 481 - Разчети по курсови разлики			      (Валута)
     * 		Ct: 724 - Приходи от валутни операции
     * 
     * 		с дебитното салдо по с/ка 481, отнасяме го като увеличение на печалбата
     * 
     * 		Dt: 724 - Приходи от валутни операции
     * 		123 - Печалби и загуби от текущата година		      (Година, Месец)
     */
    protected function transferCurrencyDiffs(&$total, $rec)
    {
    	$bQuery = acc_BalanceDetails::getQuery();
    	acc_BalanceDetails::filterQuery($bQuery, $this->balanceId, '481');
    	$bQuery->where("#ent1Id IS NOT NULL || #ent2Id IS NOT NULL || #ent3Id IS NOT NULL");
    	
    	$entries = array();
    	
    	while ($dRec = $bQuery->fetch()){
    		if(round($dRec->blAmount, 2) == 0) return;
    		
    		if($dRec->blAmount > 0){
    			$entries[] = array('amount' => abs($dRec->blAmount), 'debit' => array('624'), 'credit' => array('481', $dRec->ent1Id, 'quantity' => $dRec->blQuantity, 'reason' => 'Курсови разлики'));
    			$entries[] = array('amount' => abs($dRec->blAmount), 'debit' => array('123', $this->date->year), 'credit' => array('624'), 'reason' => 'Курсови разлики');
    		} else {
    			$entries[] = array('amount' => abs($dRec->blAmount), 'debit' => array('481', $dRec->ent1Id, 'quantity' => $dRec->blQuantity), 'credit' => array('724'), 'reason' => 'Курсови разлики');
    			$entries[] = array('amount' => abs($dRec->blAmount), 'debit' => array('724'), 'credit' => array('123', $this->date->year), 'reason' => 'Курсови разлики');
    		}
    		
    		$total += 2 * abs($dRec->blAmount);
    	}
    	
    	return $entries;
    }
}