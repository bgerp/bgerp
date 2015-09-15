<?php

cls::load('acc_strategy_Strategy');


/**
 * Клас 'acc_strategy_WAC' - за средно притеглена цена
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_strategy_WAC extends acc_strategy_Strategy
{
    
	
	/**
	 * Текущото количество
	 */
	protected $quantity = 0;
	
	
	/**
	 * Текущата сума
	 */
    protected $amount = 0;
    
    
    /**
     * Захранване на стратегията с данни
     *
     * @param double $quantity
     * @param double $amount
     */
    function feed($quantity, $amount)
    {
        // Ако сумата или к-то са отрицателни не захранваме стратегията
        //if($quantity < 0 || $amount < 0) return;
        
        $this->quantity += $quantity;
        $this->amount += $amount;
    }
    
    
    /**
     * Връща сумата спрямо количеството
     */
    function consume($quantity)
    {
        if ($quantity == 0) {
            return NULL;
        }
        
        if ($this->quantity == 0) {
            return NULL;
        }
        
        return $quantity * ($this->amount / $this->quantity);
    }
    
    
    /**
     * Връща среднопритеглената сума към дадена дата на дадена аналитична сметка
     * за подадено количество.
     * 
     * Връща среднопритеглената сума от началото на месеца на подадената дата до подадената дата.
     * Ако няма движения през този период, проверяваме в предишния и така докато се намери някаква цена
     * Ако абсолютно никога не е имало движения връщаме NULL
     * 
     * @param double $quantity - к-то което ще проверяваме
     * @param date $date       - дата към която търсим цената
     * @param string $accSysId - систем ид на сметка със стратегия
     * @param mixed $item1     - ид на перо на първа позиция / NULL ако няма / '*' Всички пера
     * @param mixed $item2     - ид на перо на първа позиция / NULL ако няма / '*' Всички пера
     * @param mixed $item3     - ид на перо на първа позиция / NULL ако няма / '*' Всички пера
     * @return mixed $amount   - сумата за количеството спрямо средно притеглената цена
     */
    public static function getAmount($quantity, $date, $accSysId, $item1, $item2, $item3)
    {
    	// Изчисляваме начална и крайна дата, която ще извличаме
    	$from = dt::mysql2verbal($date, 'Y-m-1');
    	$from = dt::verbal2mysql($from, FALSE);
    	$to = dt::verbal2mysql($date, FALSE);
    	
    	// Ще извличаме данните от първия ден на месеца от подадената дата до нея
    	$jQuery = acc_JournalDetails::getQuery();
    	
    	acc_JournalDetails::filterQuery($jQuery, $from, $to, $accSysId);
    	foreach (range(1, 3) as $i){
    		$param = ${"item{$i}"};
    		
    		// Поставяме условие за перо на определена позиция само ако е зададено
    		// Ако перото е зададено с '*' значи искаме всички записи
    		if(isset($param) && $param != '*'){
    			$jQuery->where("(#debitItem{$i} = {$param}) OR (#creditItem{$i} = {$param})");
    		} elseif(is_null($param)){
    			
    			// Ако няма стойност искаме и в запиа да няма
    			$jQuery->where("(#debitItem{$i} IS NULL) OR (#creditItem{$i} IS NULL)");
    		}
    	}
    	
    	// Инстанцираме стратегията
    	$accRec = acc_Accounts::getRecBySystemId($accSysId);
    	$strategy = new acc_strategy_WAC($accRec->id);
    	
    	// Трябва сметката да е със стратегия
    	expect(isset($accRec->strategy));
    	
    	// За всеки запис
    	while($rec = $jQuery->fetch()){
    		
    		// Обикаляме дебита и кредита
    		foreach (array('debit', 'credit') as $type){
    			$accId = $rec->{"{$type}AccId"};
    			$pos1 = $rec->{"{$type}Item1"};
    			$pos2 = $rec->{"{$type}Item2"};
    			$pos3 = $rec->{"{$type}Item3"};
    			
    			// Ако страната отговаря точно на аналитичната сметка
    			if($accId == $accRec->id && ($item1 == '*' || $pos1 == $item1) && ($item2 == '*' || $pos2 == $item2) && ($item3 == '*' || $pos3 == $item3)){
    				$feedType = ($type == 'debit') ? 'active' : 'credit';
    		
    				// Ако типа на сметката, позволява да бъде 'хранена'
    				if($accRec->type == $feedType){
    					
    					// Захранваме сметката със съответното к-во и сума
    					$strategy->feed($rec->{"{$type}Quantity"}, $rec->amount);
    				}
    			}
    		}
    	}
    	
    	// Опитваме се да намерим сумата за к-то
    	$amount = $strategy->consume($quantity);
    	
    	// Ако няма
    	if(!isset($amount)){
    		
    		// За нова дата към която ще търсим става последния ден от периода преди началото на търсенето
    		$newTo = dt::addDays(-1, $from);
    		$newTo = dt::verbal2mysql($newTo, FALSE);
    		
    		// Ако има баланс преди тази дата
    		if(cls::get('acc_Balances')->getBalanceBefore($date)){
    			
    			// Рекурсирно извикваме същата функция
    			return self::getAmount($quantity, $newTo, $accSysId, $item1, $item2, $item3);
    		}
    	}
    	
    	// Връщаме намерената сума (ако има)
    	return $amount;
    }
}
