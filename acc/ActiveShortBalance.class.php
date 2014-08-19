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
	 * Конструктор на обекта
	 * 
	 * Масив $params с атрибути
	 * 			['itemsAll'] - списък от ид-та на пера, които може да са на всяка позиция
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
		
		// Подготвяме заявката към базата данни
		$jQuery = acc_JournalDetails::getQuery();
		acc_JournalDetails::filterQuery($jQuery, $params['from'], $params['to'], NULL, $params['itemsAll'], $params['item1'], $params['item2'], $params['item3']);
		
		// Изчисляваме мини баланса
		$this->calcBalance($jQuery->fetchAll());
	}
	
	
	/**
	 * Изчислява мини баланса
	 */
	private function calcBalance($recs)
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
					$b = &$this->balance[$index];
					
					$b['accountId'] = $accId;
					$b['accountSysId'] = acc_Accounts::fetchField($accId, 'systemId');
					$b['ent1Id'] = $item1;
					$b['ent2Id'] = $item2;
					$b['ent3Id'] = $item3;
					$b['debitQuantity'] += $rec->{"debitQuantity"};
					$b['creditQuantity'] += $rec->{"creditQuantity"};
					$b['blQuantity'] += $rec->{"{$type}Quantity"} * $sign;
					$b['blAmount'] += $rec->amount * $sign;
				}
				
				// Закръгляме крайните суми и количества
				foreach ($this->balance as &$bl){
					$bl['blQuantity'] = round($bl['blQuantity'], 6);
					$bl['blAmount'] = round($bl['blAmount'], 6);
				}
			}
		}
	}
	
	
	/**
	 * Връща крайното салдо на няколко сметки
	 * 
	 * @param mixxed $accs - масив от систем ид-та на сметка
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
}