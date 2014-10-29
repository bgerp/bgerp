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
    
    	$result = (object)array(
    			'reason' => "Приключване на период \"" . $this->class->getVerbal($rec, 'periodId') . "\"",
    			'valior' => $rec->valior,
    			'totalAmount' => 0,
    			'entries' => array()
    	);
    	
    	$this->date = acc_Periods::forceYearAndMonthItems($rec->valior);
    	
    	
    	$total = $incomeFromProducts = 0;
    	$this->periodRec = acc_Periods::fetch($rec->periodId);
    	$this->balanceId =  acc_Balances::fetchField("#periodId = {$this->periodRec->id}");
    	//bp($this->periodRec);
    	$incomeRes = array();
    	
    	/*$entries1 = $this->transferIncome($result->totalAmount, $incomeRes);
    	if(count($entries1)){
    		$result->entries = array_merge($result->entries, $entries1);
    	}
    	
    	$entries2 = $this->transferIncomeToYear($result->totalAmount, $incomeRes);
    	if(count($entries2)){
    		$result->entries = array_merge($result->entries, $entries2);
    	}*/
    	
    	$entries3 = $this->transferVat($result->totalAmount, $rec);
    	if(count($entries3)){
    		$result->entries = array_merge($result->entries, $entries3);
    	}
    	
    	return $result;
    }
    
    
    private function transferVat(&$total, $rec)
    {
    	// Общата сума въведена в документа извлечена от касовия апарат
    	$amountFromFiscPrinter = $rec->amountVatGroup1 + $rec->amountVatGroup2 + $rec->amountVatGroup3 + $rec->amountVatGroup4;
    	
    	$entries = array();
    	
    	// Начисляваме сумата от касовия апарат
    	$entries[] = array('amount' => $amountFromFiscPrinter, 'debit' => array('4535'), 'credit' => array('4532'));
    	$total += $amountFromFiscPrinter;
    	
    	// Намираме текущите салда по '4531'
    	$bQuery = acc_BalanceDetails::getQuery();
    	acc_BalanceDetails::filterQuery($bQuery, $this->balanceId, '4531');
    	$sumRec4531 = new stdClass();
    	
    	while($bRec = $bQuery->fetch()){
    		$sumRec4531->debitAmount += $bRec->debitAmount;
    		$sumRec4531->creditAmount += $bRec->creditAmount;
    		$sumRec4531->blAmount += $bRec->blAmount;
    	}
    	
    	
    	// Текущите салда по '4532'
    	$bQuery = acc_BalanceDetails::getQuery();
    	acc_BalanceDetails::filterQuery($bQuery, $this->balanceId, '4532');
    	
    	$sumRec4532 = new stdClass();
    	while($bRec = $bQuery->fetch()){
    		$sumRec4532->debitAmount += $bRec->debitAmount;
    		$sumRec4532->creditAmount += $bRec->creditAmount;
    		$sumRec4532->blAmount += $bRec->blAmount;
    	}
    	
    	// Местим дебитното салдо на '4531' в '4532'
    	$sumRec4531->debitAmount = ($sumRec4531->debitAmount) ? $sumRec4531->debitAmount : 0;
    	$entries[] = array('amount' => $sumRec4531->debitAmount, 'debit' => array('4532'), 'credit' => array('4531'));
    	$total += $sumRec4531->debitAmount;
    	
    	// Колко ще бъде крайното салдо на '4532' след изпълнението на тези операции
    	$amount4532 = $sumRec4531->debitAmount + $sumRec4532->blAmount - $amountFromFiscPrinter;
    	
    	// Ако по 4532 накрая имаме кредитно или дебитно салдо
    	if($amount4532 <= 0){
    		$am = $sumRec4532->creditAmount + $amountFromFiscPrinter;
    		$entries[] = array('amount' => $am, 'debit' => array('4532'), 'credit' => array('4539'));
    	} else {
    		$am = $sumRec4532->debitAmount;
    		$entries[] = array('amount' => $am, 'debit' => array('4538'), 'credit' => array('4532'));
    	}
    	
    	// @TODO
    	$total += $am;
    	
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
    	
    	if(!count($this->balanceId)) return $entries;
    	
    	$bQuery = acc_BalanceDetails::getQuery();
    	acc_BalanceDetails::filterQuery($bQuery, $this->balanceId, '701,706,703');
    	$bQuery->where("#ent1Id IS NOT NULL || #ent2Id IS NOT NULL || #ent3Id IS NOT NULL");
    	$balanceArr = $bQuery->fetchAll();
    	
    	$accIds = array();
    	
    	foreach (arr::make('701,706,703') as $systemId){
    		$accId = acc_Accounts::getRecBySystemId($systemId)->id;
    		$accIds[$accId] = $systemId;
    	}
    	
    	if(!count($balanceArr)) return $entries;
    	 
    	foreach ($balanceArr as $rec){
    		$arr1 = array('700', $rec->ent1Id, $rec->ent2Id);
    		$arr2 = array($accIds[$rec->accountId], $rec->ent1Id, $rec->ent2Id, $rec->ent3Id, 'quantity' => $rec->blQuantity);
    		
    		if($rec->blAmount > 0){
    			$debitArr = $arr1;
    			$creditArr = $arr2;
    		} else {
    			$debitArr = $arr2;
    			$creditArr = $arr1;
    		}
    
    		$incomeRes[$rec->ent1Id][$rec->ent2Id] += $rec->blAmount;
    		
    		$total += abs($rec->blAmount);
    		$entries[] = array('amount' => abs($rec->blAmount), 'debit' => $debitArr, 'credit' => $creditArr);
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
    			$arr2 = array('123', $this->date->year, $this->date->month);
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
    		
    			$entries[] = array('amount' => abs($sum), 'debit' => $debitArr, 'credit' => $creditArr);
    		}
    	}
    	
    	
	    return $entries;
    }
}