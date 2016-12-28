<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа acc_ClosePeriods
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class acc_transaction_ClosePeriod extends acc_DocumentTransactionSource
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
     * Ид на баланса
     */
    private $balanceId;
    
    
    /**
     * Сч. период
     */
    private $periodRec;
    
    
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
    	
    	$this->date = acc_Periods::forceYearItem($result->valior);
    	
    	$total = $incomeFromProducts = 0;
    	$this->balanceId =  acc_Balances::fetchField("#periodId = {$this->periodRec->id}");
    	$incomeRes = array();
    	
    	$entries3 = $this->transferVat($result->totalAmount, $rec);
    	if(count($entries3)){
    		$result->entries = array_merge($result->entries, $entries3);
    	}
    	
    	$entries4 = $this->transferCurrencyDiffs($result->totalAmount, $rec);
    	if(count($entries4)){
    		$result->entries = array_merge($result->entries, $entries4);
    	}
    	
    	$entries5 = $this->transferCosts($result->totalAmount, $rec, $incomeRes);
    	if(count($entries5)){
    		$result->entries = array_merge($result->entries, $entries5);
    	}
    	
    	$entries1 = $this->transferIncome($result->totalAmount, $incomeRes);
    	if(count($entries1)){
    		$result->entries = array_merge($result->entries, $entries1);
    	}
    	
    	$entries2 = $this->transferIncomeToYear($result->totalAmount, $incomeRes);
    	if(count($entries2)){
    		$result->entries = array_merge($result->entries, $entries2);
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
    	
    	$entries[] = array('amount' => $diffAmount, 'debit' => array('4535'), 'credit' => array('4532'), 'reason' => 'Начисляване на сумата от касовия апарат');
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
     * 
     * Отнасяме отписаните вземания (извънредния разход) по сделката като разход по дебита на обобщаващата сметка 700, със сумата на дебитното салдо на с/ка 411
     * 
     * 		Dt: 700 - Приходи от продажби (по сделки)
     * 		Ct: 6911 - Отписани вземания по Продажби
     * 
     * Отнасяме извънредния приход по сделката като приход по кредита на обобщаващата сметка 700, със сумата на кредитното салдо на с/ка 411
     * 
     * 		Dt: 7911 - Извънредни приходи по Продажби
     * 		Ct: 700 - Приходи от продажби (по сделки)
     * 
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
    	
    	foreach (arr::make('701,706,703,700,7911,6911') as $systemId){
    		$accId = acc_Accounts::getRecBySystemId($systemId)->id;
    		$accIds[$accId] = $systemId;
    		$dealPosition[$accId] = acc_Lists::getPosition($systemId, 'deals_DealsAccRegIntf');
    		$dealPosition[$accId] = "ent{$dealPosition[$accId]}Id";
    	}
    	 
    	if(count($balanceArr)){
    		foreach ($balanceArr as $rec){
    			if($accIds[$rec->accountId] != '700'){
    				if($rec->blQuantity < 0){
    					
    					// Ако имаме кредитно салдо, правим такова к-во, че да го занулим
    					$quantity = abs($rec->blQuantity);
    				} else {
    					$quantity = $rec->blQuantity;
    				}
    				
    				$arr1 = array('700', $rec->ent1Id, $rec->ent2Id);
    				$arr2 = array($accIds[$rec->accountId], $rec->ent1Id, $rec->ent2Id, $rec->ent3Id, 'quantity' => $quantity);
    				 
    				$dealItemRec = acc_Items::fetch($rec->{$dealPosition[$rec->accountId]});
    				
    				// Пропускаме активните продажби и тези които са затворени в друг период
    				if($dealItemRec->state == 'active' || (strtotime($dealItemRec->closedOn) > strtotime($this->periodRec->end))) continue;
    				
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
    				 
    				switch($accIds[$rec->accountId]){
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
    	}
    	
    	// Прехвърляме извънредните приходи/разходи в сметка 700
    	$bQuery1 = acc_BalanceDetails::getQuery();
    	acc_BalanceDetails::filterQuery($bQuery1, $this->balanceId, '7911,6911');
    	$bQuery1->XPR('roundBlAmount', 'double', 'ROUND(#blAmount, 2)');
    	$bQuery1->where("#roundBlAmount != 0");
    	$bQuery1->where("#ent1Id IS NOT NULL || #ent2Id IS NOT NULL || #ent3Id IS NOT NULL");
    	
    	while($bRec1 = $bQuery1->fetch()){
    		$arr1 = array('700', $bRec1->ent1Id, $bRec1->ent2Id);
    		$arr2 = array($accIds[$bRec1->accountId], $bRec1->ent1Id, $bRec1->ent2Id);
    		
    		$dealItemRec = acc_Items::fetch($bRec1->{$dealPosition[$bRec1->accountId]});
    		if($dealItemRec->state == 'active' || (strtotime($dealItemRec->closedOn) > strtotime($this->periodRec->end))) continue;
    		
    		if($accIds[$bRec1->accountId] == '7911'){
    			$debitArr = $arr2;
    			$creditArr = $arr1;
    			$reason = 'Извънредни приходи - надплатени';
    			$incomeRes[$bRec1->ent1Id][$bRec1->ent2Id] -= abs($bRec1->blAmount);
    		} else {
    			$debitArr = $arr1;
    			$creditArr = $arr2;
    			$reason = 'Извънредни разходи - недоплатени';
    			$incomeRes[$bRec1->ent1Id][$bRec1->ent2Id] += abs($bRec1->blAmount);
    		}
    		
    		$entries[] = array('amount' => abs($bRec1->blAmount), 'debit' => $debitArr, 'credit' => $creditArr, 'reason' => $reason);
    		$total += abs($bRec1->blAmount);
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
     * 
     * Отнасяме натрупаните отписани задължения (извънредния приход) по сделката като печалба по сметка 123
     * 
     * 			Dt: 7912 - Отписани задължения по Покупки
     * 			Ct: 123 - Печалби и загуби от текущата година
     * 
     * Отнасяме натрупаните извънредните разходи по сделката като загуба по сметка 123 - Печалби и загуби от текущата година
     * 
     * 			Dt: 123 - Печалби и загуби от текущата година
     * 			Ct: 6912 - Извънредни разходи по Покупки
     * 
     * Отнасяме общите извънредни разходи (отразени по дебита на с/ка 699) 
     * 			
     * 			Dt: 123 - Печалби и загуби от текущата година
     *          Ct: 699 - Други извънредни разходи
     *          
     * Отнасяме общите извънредни приходи (отразени по кредита на с/ка 799)
     * 
     * 			Dt: 799 - Други извънредни приходи
     *          Ct: 123 - Печалби и загуби от текущата година
     * 
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
    	
    	$arr6912 = $arr7912 = array();
    	
    	// Намираме извънредните разходки/приходи по покупки
    	$bQuery1 = acc_BalanceDetails::getQuery();
    	acc_BalanceDetails::filterQuery($bQuery1, $this->balanceId, '6912,7912');
    	$bQuery1->XPR('roundBlAmount', 'double', 'ROUND(#blAmount, 2)');
    	$bQuery1->where("#roundBlAmount != 0");
    	$bQuery1->where("#ent1Id IS NOT NULL || #ent2Id IS NOT NULL || #ent3Id IS NOT NULL");
    	
    	// Разпределяме ги в два масива според сметката им
    	$id6912 = acc_Accounts::getRecBySystemId('6912')->id;
    	while($dRec1 = $bQuery1->fetch()){
    		$index = "{$dRec1->ent1Id}|{$dRec1->ent2Id}";
    		if($dRec1->accountId == $id6912){
    			$arr6912[$index] = $dRec1;
    		} else {
    			$arr7912[$index] = $dRec1;
    		}
    	}
    	
    	// Ако има записи в масива с извънредни разходи
    	if(count($arr6912)){
    		
    		// За всеки запис
    		foreach ($arr6912 as $index => &$dRec2){
    			
    			// Ако също така, има и извънреден приход за същата сделка
    			if(isset($arr7912[$index])){
    				
    				// Правим рписпадане на изнвънредните приходи/разходи по покупка
    				$min = min(array(abs($dRec2->blAmount), abs($arr7912[$index]->blAmount)));
    				$entries[] = array('amount' => abs($min), 
    								   'debit'  => array('7912', $dRec2->ent1Id, $dRec2->ent2Id),
    								   'credit' => array('6912', $dRec2->ent1Id, $dRec2->ent2Id), 
    						           'reason' => 'Приспадане на извънредни приходи/разходи по покупка');
    				
    				// Приспадаме сумата от оригиналните записи
    				
    				$dRec2->blAmount           -= $min;
    				$arr7912[$index]->blAmount += $min;
    				$total += abs($min);
    			}
    		}
    	}
    	
    	// Отнасяме извънредните разходи по покупки към сметка 123
    	if(count($arr6912)){
    		foreach ($arr6912 as $index1 => $dRec3){
    			
    			if($dRec3->blAmount == 0) continue;
    			
    			$entries[] = array('amount' => abs($dRec3->blAmount), 
    							   'debit'  => array('123', $this->date->year), 
    							   'credit' => array('6912', $dRec3->ent1Id, $dRec3->ent2Id), 
    							   'reason' => 'Извънредни разходи по покупка');
    			
    			$total += abs($dRec3->blAmount);
    		}
    	}
    	
    	// Отнасяме извънредните приходи по покупки към сметка 123
    	if(count($arr7912)){
    		foreach ($arr7912 as $index2 => $dRec4){
    			
    			if($dRec4->blAmount == 0) continue;
    			
    			$entries[] = array('amount' => abs($dRec4->blAmount), 
    							   'debit'  => array('7912', $dRec4->ent1Id, $dRec4->ent2Id), 
    							   'credit' => array('123', $this->date->year), 
    							   'reason' => 'Извънредни приходи по покупка');
    			
    			$total += abs($dRec4->blAmount);
    		}
    	}
    	
    	// Отнасяне на салдото на общите извънредни разходи
    	$bQuery2 = acc_BalanceDetails::getQuery();
    	acc_BalanceDetails::filterQuery($bQuery2, $this->balanceId, '699');
    	$bQuery2->XPR('roundBlAmount', 'double', 'ROUND(#blAmount, 2)');
    	$bl699 = $bQuery2->fetch()->roundBlAmount;
    	
    	if($bl699 > 0){
    		$entries[] = array('amount' => $bl699,
    				           'debit'  => array('123', $this->date->year),
    				           'credit' => array('699'),
    				           'reason' => 'Отнасяне на общи извънредни разходи');
    		 
    		$total += $bl699;
    	} elseif($bl699 < 0) {
    		//@TODO имали такъв случай
    	}
    	
    	// Отнасяне на салдото на общите извънредни приходи
    	$bQuery3 = acc_BalanceDetails::getQuery();
    	acc_BalanceDetails::filterQuery($bQuery3, $this->balanceId, '799');
    	$bQuery3->XPR('roundBlAmount', 'double', 'ROUND(#blAmount, 2)');
    	$bl799 = $bQuery3->fetch()->roundBlAmount;
    	
    	if($bl799 < 0){
    		$entries[] = array('amount' => abs($bl799),
    				'debit'  => array('799'),
    				'credit' => array('123', $this->date->year),
    				'reason' => 'Отнасяне на общи извънредни приходи');
    		 
    		$total += abs($bl799);
    	} elseif($bl799 > 0) {
    		//@TODO имали такъв случай
    	}
    	
	    return $entries;
    }
    
    /**
     * Разходи за материали
     * 
     * 		Dt: 61101. Разходи за Ресурси
     * 		Ct: 601. Разходи за материали
     * 
     * Разходи за материали
     * 
     * 		Dt: 61101. Разходи за Ресурси
     * 		Ct: 60010. Разходи за (складируеми) материали
     * 
     * 
     * Разходи за Разходи за външни услуги
     *  
     * 		Dt: 61102. Други разходи (общо)
     * 		Ct: 602. Разходи за външни услуги
     * 
     * Разходи за Разходи за външни услуги
     *  
     * 		Dt: 61102. Други разходи (общо)
     * 		Ct: 60020. Разходи за (нескладируеми) услуги и консумативи
     * 
     * Разходи за услуги и консумативи (неразпределени), само ако разхода е отнесен към продажба
     *  
     * Ако е с Дебитно салдо
     * 		Dt: 700. Приходи от продажби (по сделки)
     * 		Ct: 60201. Разходи за (нескладируеми) услуги и консумативи
     * 
     * Ако е с Кредитно салдо или 0
     * 		Dt: 700. Приходи от продажби (по сделки)
     * 		Ct: 60201. Разходи за (нескладируеми) услуги и консумативи
     * 
     * Отчитане на отнесени разходи от друга сделка, само ако разхода не е отнесен към продажба
     *  
     * Ако е с Дебитно салдо
     * 		Dt: 61102. Други разходи (общо)
     * 		Ct: 60201. Разходи за (нескладируеми) услуги и консумативи
     * 
     * Ако е с Кредитно салдо или 0
     * 		Dt: 61102. Други разходи (общо)
     * 		Ct: 60201. Разходи за (нескладируеми) услуги и консумативи
     * 
     * Приключваме разхода като намаление на финансовия резултат за периода
     * 
     * 		Dt: 123. Печалби и загуби от текущата година
     * 		Ct: 61101. Разходи по Центрове и Ресурси
     * 
     * Приключваме разхода като намаление на финансовия резултат за периода
     * 
     * 		Dt: 123. Печалби и загуби от текущата година
     * 		Ct: 60020. Разходи за (нескладируеми) услуги и консумативи
     * 
     * Разходи за ДА
     * 
     * 		Dt: 61101. Разходи за Ресурси
     * 		Ct: 603. Разходи за амортизация
     * 
     * Приключваме разхода като намаление на финансовия резултат за периода
     * 
     * 		Dt: 123. Печалби и загуби от текущата година
     * 		Ct: 61101. Разходи за Ресурси
     * 
     * Разходи за труд
     * 
     * 		Dt: 61101. Разходи за Ресурси
     * 		Ct: 604. Разходи за заплати (възнаграждения)
     * 
     * 		Dt: 61101. Разходи за Ресурси
     * 		Ct: 605. Разходи за осигуровки
     * 
     * Приключваме разхода като намаление на финансовия резултат за периода
     * 
     * 		Dt: 123. Печалби и загуби от текущата година
     * 		Ct: 61101. Разходи за Ресурси
     */
    protected function transferCosts(&$total, $rec, &$incomeRes)
    {
    	$bQuery = acc_BalanceDetails::getQuery();
    	$query2 = clone $bQuery;
    	$query3 = clone $bQuery;
    	
    	acc_BalanceDetails::filterQuery($bQuery, $this->balanceId, '601,602,603,60010,60020');
    	$bQuery->where("#ent1Id IS NOT NULL || #ent2Id IS NOT NULL || #ent3Id IS NOT NULL");
    	
    	// Колко е крайното салдо на 61102 преди да започнем да отчитаме разходите
    	acc_BalanceDetails::filterQuery($query2, $this->balanceId, '61102');
    	$query2->where("#ent1Id IS NULL && #ent2Id IS NULL && #ent3Id IS NULL");
    	$amount61102 = $query2->fetch()->blAmount;
    	
    	$entries = array();
    	 
    	// Подготвяме предварително нужните ни данни
    	$baseDepartment = hr_Departments::fetchField("#systemId = 'emptyCenter'", 'id');
    	$resource604 = $resource605 = cat_Products::fetchField("#code = 'labor'", 'id');
    	$resource603 = cat_Products::fetchField("#code = 'fixedAssets'", 'id');
    	$resource602 = cat_Products::fetchField("#code = 'services'", 'id');
    	$resource601 = cat_Products::fetchField("#code = 'commonMaterial'", 'id');
    	$reason601   = 'Разходи за материали (неразпределени)';
    	$reason602   = 'Разходи за външни услуги (неразпределени)';
    	$reason60020 = 'Разходи за външни услуги (неразпределени)';
    	$reason60010 = 'Разходи за материали (неразпределени)';
    	$reason603   = 'Разходи за амортизация (неразпределени)';
    	$reason604 = $reason605 = 'Разходи за Труд (неразпределени)';
    	
    	$accs = array();
    	foreach(array('601', '602', '603', '60010', '60020') as $sysId){
    		$id = acc_Accounts::getRecBySystemId($sysId)->id;
    		$accs[$id] = $sysId;
    	}
    	
    	$amount601 = $amount602 = $amount603 = $amount60010 = $amount60020 = 0;
    	$quantity601 = $quantity602 = $quantity603 = $quantity60010 = $quantity60020 = 0;
    	while ($dRec = $bQuery->fetch()){
    		if($dRec->blAmount == 0) continue;
    		
    		$amount = &${"amount{$accs[$dRec->accountId]}"};
    		$quantity = &${"quantity{$accs[$dRec->accountId]}"};
    		
    		if($accs[$dRec->accountId] == 602 || $accs[$dRec->accountId] == 60020){
    			$accountDebit = array('61102');
    		} else {
    			$accountDebit = array('61101', array('cat_Products', ${"resource{$accs[$dRec->accountId]}"}), 'quantity' => $dRec->blQuantity);
    		}
    		
    		if($accs[$dRec->accountId] == '60020'){
    			$creditArr = array($accs[$dRec->accountId], array('hr_Departments', $baseDepartment), $dRec->ent2Id, 'quantity' => $dRec->blQuantity);
    		} else {
    			$creditArr = array($accs[$dRec->accountId], $dRec->ent1Id, 'quantity' => $dRec->blQuantity);
    		}
    		
    		$entries[] = array('amount'  => abs($dRec->blAmount), 
    							'debit'  => $accountDebit, 
    							'credit' => $creditArr,
    							'reason' => ${"reason{$accs[$dRec->accountId]}"});
    		
    		$total += abs($dRec->blAmount);
    		$amount += abs($dRec->blAmount);
    		$quantity += $dRec->blQuantity;
    	}
    	
    	$saleClassId = sales_Sales::getClassId();
    	acc_BalanceDetails::filterQuery($query3, $this->balanceId, '60201');
    	$query3->where("#ent1Id IS NOT NULL || #ent2Id IS NOT NULL || #ent3Id IS NOT NULL");
    	
    	$amount602 += $amount60020;
    	$amount602 += $amount61102;
    	
    	// Прехвърляне на разходи
    	while($dRec = $query3->fetch()){
    		$entry = array();
    		$pRec = cat_Products::fetch(acc_Items::fetchField($dRec->ent2Id, 'objectId'), 'canConvert,fixedAsset,canStore');
    		
    		// Прехвърлят се само разходите, по артикули, които не са вложими, не са складируеми и не са ДА
    		if($pRec->canStore == 'yes' || $pRec->fixedAsset == 'yes' || $pRec->canConvert == 'yes') continue;
    		
    		// Ако разхода е отнесен към продажба, увеличава се прихода и
    		if(acc_Items::fetchField($dRec->ent1Id, 'classId') == $saleClassId){
    			$saleId = acc_Items::fetchField($dRec->ent1Id, 'objectId');
    			$saleRec = sales_Sales::fetch($saleId, 'contragentId,contragentClassId');
    			$contragentItemId = acc_Items::fetchItem($saleRec->contragentClassId, $saleRec->contragentId)->id;
    			
    			$incomeRes[$contragentItemId][$dRec->ent1Id] += $dRec->blAmount;
    			
    			if($dRec->blQuantity > 0){
    				$entry = array('amount' => $dRec->blAmount,
    								 'debit' => array('700', $contragentItemId, $dRec->ent1Id),
    						         'credit' => array('60201', $dRec->ent1Id, $dRec->ent2Id, 'quantity' => $dRec->blQuantity), 'reason' => 'Разходи за услуги и консумативи (неразпределени)');
    			} elseif($dRec->blQuantity <= 0) {
    				$entry = array('amount' => abs($dRec->blAmount),
    								 'debit' => array('60201', $dRec->ent1Id, $dRec->ent2Id, 'quantity' => abs($dRec->blQuantity)),
    								 'credit' => array('700', array($saleRec->contragentClassId, $saleRec->contragentId), $dRec->ent1Id), 'reason' => 'Разходи за услуги и консумативи (неразпределени)');
    			}
    			
    		} else {
    			
    			// Ако разхода не е към продажба, отива към Общите разходи
    			if($dRec->blQuantity > 0){
    				$entry = array('amount' => round($dRec->blAmount, 7),
    								 'debit' => array('61102'),
    								 'credit' => array('60201', $dRec->ent1Id, $dRec->ent2Id, 'quantity' => $dRec->blQuantity), 'reason' => 'Отчитане на отнесени разходи от друга сделка');
    			
    			} elseif($dRec->blQuantity <= 0) {
    				$entry = array('amount' => round(abs($dRec->blAmount), 7),
    						'debit' => array('60201', $dRec->ent1Id, $dRec->ent2Id, 'quantity' => abs($dRec->blQuantity)),
    						'credit' => array('61102'), 'reason' => 'Отчитане на отнесени разходи от друга сделка');
    			}
    			
    			$amount602 += $dRec->blAmount;
    		}
    		
			$total += abs($dRec->blAmount);
			$entries[] = $entry;
    	}
    	
    	$amount601 += $amount60010;
    	
    	// От ще прехвърлим това което сме натрупали до момента в нея + крайното и салдо
    	foreach (array('601', '602', '603') as $sysId){
    		if(${"amount{$sysId}"} == 0) continue;
    		$var  = ${"amount{$sysId}"};
    		$amount = abs(${"amount{$sysId}"});
    		
    		if($sysId == '602'){
    			$creditArr = array('61102');
    			
    			// Ако има зададено салдо за запазване
    			if($rec->amountKeepBalance){
    				
    				// Ако салдото за прехвърляне е по-малко от това за оставяне не го бутаме
    				if($amount <= $rec->amountKeepBalance){
    					continue;
    				} else {
    					
    					// Иначе прихвърляме толкова, че да остане минимум зададеното салдо
    					$oldAmount = $amount;
    					$oldVar = $var;
    					$amount -= $rec->amountKeepBalance;
    					$var -= $rec->amountKeepBalance;
    					
    					// Не се поддържат отрицателни салда
    					if($var < 0){
    						$amount = $oldAmount;
    						$var = $oldVar;
    					}
    				}
    			}
    		} else {
    			$creditArr = array('61101', array('cat_Products', ${"resource{$sysId}"}), 'quantity' => ${"quantity{$sysId}"});
    		}
    		
    		if($var > 0){
    			$entries[] = array('amount' => $amount,
    							   'debit'  => array('123', $this->date->year),
    					           'credit' => $creditArr,
    					           'reason' => ${"reason{$sysId}"});
    		} else {
    			$entries[] = array('amount' => $amount,
    					           'debit'  => $creditArr,
    					           'credit' => array('123', $this->date->year),
    					           'reason' => ${"reason{$sysId}"});
    		}
    		 
    		$total += $amount;
    	}
    	
    	// Колко е крайното салдо по 604
    	$bQuery = acc_BalanceDetails::getQuery();
    	acc_BalanceDetails::filterQuery($bQuery, $this->balanceId, '604');
    	$rec604 = $bQuery->fetch();
    	
    	// Крайното салдо по 605
    	$bQuery = acc_BalanceDetails::getQuery();
    	acc_BalanceDetails::filterQuery($bQuery, $this->balanceId, '605');
    	$rec605 = $bQuery->fetch();
    	
    	$selfValueLabor = planning_ObjectResources::getSelfValue($resource604, 1, $this->periodRec->end);
    	if(!is_object($rec604)){
    		$rec604 = new stdClass();
    	}
    	if(!is_object($rec605)){
    		$rec605 = new stdClass();
    	}
    	
    	if(!empty($selfValueLabor)){
    		$rec604->blQuantity = $rec604->blAmount / $selfValueLabor;
    		$rec605->blQuantity = $rec605->blAmount / $selfValueLabor;
    	} else {
    		$rec604->blQuantity = 0;
    		$rec605->blQuantity = 0;
    	}
    	
    	if(round($rec604->blAmount, 2) != 0){
    		$entries[] = array('amount' => abs($rec604->blAmount),
    				'debit' => array('61101', array('cat_Products', $resource604), 'quantity' => $rec604->blQuantity),
    				'credit' => array('604'), 'reason' => $reason604);
    		 
    		$total += abs($rec604->blAmount);
    	}
    	
    	if(round($rec605->blAmount, 2) != 0){
    		$entries[] = array('amount' => abs($rec605->blAmount),
    				'debit' => array('61101', array('cat_Products', $resource605), 'quantity' => $rec605->blQuantity),
    				'credit' => array('605'), 'reason' => $reason605);
    		 
    		 
    		$total += abs($rec605->blAmount);
    	}
    	
    	
    	$tAmount = abs($rec604->blAmount) + abs($rec605->blAmount);
    	
    	if(round($tAmount, 2) != 0){
    		$entries[] = array('amount' => $tAmount,
    				'debit' => array('123', $this->date->year),
    				'credit' => array('61101', array('cat_Products', $resource604), 'quantity' => ($rec604->blQuantity + $rec605->blQuantity)),
    				'reason' => $reason604);
    		 
    		$total += $tAmount;
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
    			$entries[] = array('amount' => abs($dRec->blAmount), 'debit' => array('624'), 'credit' => array('481', $dRec->ent1Id, 'quantity' => $dRec->blQuantity), 'reason' => 'Курсови разлики');
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
