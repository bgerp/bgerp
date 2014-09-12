<?php

/**
 * Помощен модел за лесна работа с баланс, в който участват само определени пера и сметки
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_ActiveShortBalance {
	
	
	/**
	 * Променлива в която ще се помни баланса
	 */
	private $balance = array();
	
	
	/**
	 * Извлечените записи
	 */
	private $recs;
	
	
	/**
	 * От дата
	 */
	private $from;
	
	
	/**
	 * До дата
	 */
	private $to;
	
	
	/**
	 * acc_Balances
	 */
	private $acc_Balances;
	
	
	/**
	 * Конструктор на обекта
	 * 
	 * Масив $params с атрибути
	 * 			['itemsAll'] - списък от ид-та на пера, които може да са на всяка позиция
	 * 			['accs'] 	 - списък от систем ид-та на сметки
	 * 			['item1']    - списък от ид-та на пера, поне едно от които може да е на първа позиция
	 * 			['item2']    - списък от ид-та на пера, поне едно от които може да е на втора позиция
	 * 			['item3']    - списък от ид-та на пера, поне едно от които може да е на трета позиция
	 * 			['from']     - От дата
	 * 			['to']       - До дата
	 */
	function __construct($params = array())
	{
		if(!isset($params['to'])){
			$params['to'] = dt::now();
		}
		
		$this->from = $params['from'];
		$this->to = $params['to'];
		
		// Подготвяме заявката към базата данни
		$jQuery = acc_JournalDetails::getQuery();
		acc_JournalDetails::filterQuery($jQuery, $params['from'], $params['to'], $params['accs'], $params['itemsAll'], $params['item1'], $params['item2'], $params['item3']);
		
		// Изчисляваме мини баланса
		$this->recs = $jQuery->fetchAll();
		
		// Изчисляваме и кешираме баланса
		$this->calcBalance($this->recs, $this->balance);
		
		$this->acc_Balances = cls::get('acc_Balances');
	}
	
	
	/**
	 * Изчислява мини баланса
	 */
	private function calcBalance($recs, &$balance = array(), $sumDC = TRUE)
	{
		if(count($recs)){
			
			// За всеки запис
			foreach ($recs as $rec){
				
				// За дебита и кредита
				foreach (array('debit', 'credit') as $type){
					$accId = $rec->{"{$type}AccId"};
					$item1 = $rec->{"{$type}Item1"};
					$item2 = $rec->{"{$type}Item2"};
					$item3 = $rec->{"{$type}Item3"};
					
					// За всяка уникална комбинация от сметка и пера, сумираме количествата и сумите
					$sign = ($type == 'debit') ? 1 : -1;
					$index = $accId . "|" . $item1 . "|" . $item2 . "|" . $item3;
					
					$b = &$balance[$index];
					
					$b['accountId'] = $accId;
					$b['accountSysId'] = acc_Accounts::fetchField($accId, 'systemId');
					$b['ent1Id'] = $item1;
					$b['ent2Id'] = $item2;
					$b['ent3Id'] = $item3;
					
					// Ако искаме да се сумира и оборота по дебит и кредит
					if($sumDC === TRUE){
						$b["{$type}Quantity"] += $rec->{"{$type}Quantity"};
						$b["{$type}Amount"] += $rec->amount;
					}
					
					$b['blQuantity'] += $rec->{"{$type}Quantity"} * $sign;
					$b['blAmount'] += $rec->amount * $sign;
				}
				
				// Закръгляме крайните суми и количества
				foreach ($balance as &$bl){
					$bl['blQuantity'] = round($bl['blQuantity'], 6);
					$bl['blAmount'] = round($bl['blAmount'], 6);
				}
			}
		}
	}
	
	
	/**
	 * Връща крайното салдо на няколко сметки
	 * 
	 * @param mixed $accs - масив от систем ид-та на сметка
	 * @return stdClass $res - масив с 'amount' - крайното салдо
	 */
	public function getAmount($accs, $itemId = FALSE)
	{
		$arr = arr::make($accs);
		
        expect(count($arr));
		
		$res = 0;
		foreach ($arr as $accSysId){
			foreach ($this->balance as $index => $b){
				
				// Ако филтрираме и по перо, пропускаме тези записи, в които то не участва
				if($itemId){
					$indexArr = explode('|', $index);
					if(!in_array($itemId, $indexArr)) continue;
				}
				
				if($b['accountSysId'] == $accSysId){
					$res += $b['blAmount'];
				}
			}
		}
		
		return $res;
	}
	
	
	/**
	 * Връща краткия баланс с посочените сметки
	 */
	public function getShortBalance($accs)
	{
		$arr = arr::make($accs);
		if(!count($arr)) return $this->balance;
		
		$newArr = array();
		foreach ($arr as $accSysId){
				
			foreach ($this->balance as $index => $b){
				if($b['accountSysId'] == $accSysId){
					$newArr[$index] = $b;
				}
			}
		}
		
		return $newArr;
	}
	
	
	/**
	 * Връща изчислен баланс за няколко сметки
	 * взима началните салда от последния изчислен баланс и към тях натрупва записите от журнала
	 * които не са влезли в баланса
	 */
	public function getBalance($accs)
	{
		$newBalance = array();
		
		// Намираме последния изчислен баланс преди началната дата
		$balanceRec = $this->acc_Balances->getBalanceBefore($this->from);
		
		// Обръщаме сис ид-та на сметките в техните ид-та
		$accArr = arr::make($accs);
		if(count($accArr)){
			foreach ($accArr as &$acc){
				$acc = acc_Accounts::fetchField("#systemId = {$acc}");
			}
		}
		
		$newFrom = NULL;
		
		// Ако има такъв баланс
		if($balanceRec){
			
			// Извличаме неговите записи
			$bQuery = acc_BalanceDetails::getQuery();
			$bQuery->where("#balanceId = {$balanceRec->id}");
			$bQuery->show('accountId,ent1Id,ent2Id,ent3Id,blAmount,blQuantity');
			while($bRec = $bQuery->fetch()){
				
				// Ако е за синтетична сметка, пропускаме го
				if(empty($bRec->ent1Id) && empty($bRec->ent2Id) && empty($bRec->ent3Id)) continue;
				
				// Ако има подадени сметки и сметката на записа не е в масива пропускаме
				if(count($accArr) && !in_array($bRec->accountId, $accArr)) continue;
				
				// Натруваме в $newBalance
				$index = $bRec->accountId . "|" . $bRec->ent1Id . "|" . $bRec->ent2Id . "|" . $bRec->ent3Id;
				
				$bRec = (array)$bRec;
				$newBalance[$index] = $bRec;
			}
			
			$newFrom = dt::addDays(1, $balanceRec->toDate);
			$newFrom = dt::verbal2mysql($newFrom, FALSE);
		}
		
		$newTo = dt::addDays(-1, $this->from);
		$newTo = dt::verbal2mysql($newTo, FALSE);
		
		// Извличаме всички записи които са между последния баланс и избраната дата за начало на търсенето
		$jQuery = acc_JournalDetails::getQuery();
		acc_JournalDetails::filterQuery($jQuery, $newFrom, $newTo, $accs);
		
		// Натрупваме им сумите към началния баланс
		$this->calcBalance($jQuery->fetchAll(), $newBalance);
		
		// Изчислените крайни салда стават начални салда на показвания баланс
		if(count($newBalance)){
			foreach ($newBalance as &$r){
				$r['baseAmount'] = $r['blAmount'];
				$r['baseQuantity'] = $r['blQuantity'];
				unset($r['debitAmount'], $r['creditAmount'], $r['debitQuantity'], $r['creditQuantity']);
			}
		}
		
		// Извличаме записите, направени в избрания период на търсене
		$jQuery = acc_JournalDetails::getQuery();
		acc_JournalDetails::filterQuery($jQuery, $this->from, $this->to, $accs);
		$this->calcBalance($jQuery->fetchAll(), $newBalance);
		
		// Оставяме само тези, които са на избраната сметка
		if(count($newBalance)){
			foreach ($newBalance as $index => &$r){
				$r = (object)$r;
				if(count($accArr) && !in_array($r->accountId, $accArr)){
					unset($newBalance[$index]);
				}
			}
		}
		
		return $newBalance;
	}
}