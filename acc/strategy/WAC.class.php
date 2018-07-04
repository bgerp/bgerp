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
    public function feed($quantity, $amount)
    {
        // Ако сумата или к-то са отрицателни не захранваме стратегията
        //if($quantity < 0 || $amount < 0) return;
        
        $this->quantity += $quantity;
        $this->amount += $amount;
    }
    
    
    /**
     * Връща сумата спрямо количеството
     */
    public function consume($quantity)
    {
        if ($quantity == 0) {
            return;
        }
        
        if ($this->quantity == 0) {
            return;
        }
        
        return $quantity * ($this->amount / $this->quantity);
    }
    
    
    /**
     * Връща среднопритеглената сума към дадена дата на дадена аналитична сметка
     * за подадено количество.
     *
     * Връща среднопритеглената сума от началото на месеца на подадената дата до подадената дата.
     * Ако няма движения през този период, проверяваме в предишния и така докато се намери
     * някаква цена, или не се достигне максималния брой опити
     * Ако абсолютно никога не е имало движения връщаме NULL
     *
     * @param double   $quantity   - к-то което ще проверяваме
     * @param date     $date       - дата към която търсим цената
     * @param string   $accSysId   - систем ид на сметка със стратегия
     * @param mixed    $item1      - ид на перо на първа позиция / NULL ако няма / '*' Всички пера
     * @param mixed    $item2      - ид на перо на първа позиция / NULL ако няма / '*' Всички пера
     * @param mixed    $item3      - ид на перо на първа позиция / NULL ако няма / '*' Всички пера
     * @param int|NULL $maxTries   - максимален брой опити, NUll за безкрай
     * @param int|NULL $currentTry - текущ опит
     *
     * @return mixed $amount   - сумата за количеството спрямо средно притеглената цена
     */
    public static function getAmount($quantity, $date, $accSysId, $item1, $item2, $item3, $maxTries = null, &$currentTry = null)
    {
        // Увеличаваме брояча
        $currentTry++;
        
        // Изчисляваме начална и крайна дата, която ще извличаме
        $from = dt::mysql2timestamp($date);
        $from = date('Y-m-01', $from);
        $to = dt::verbal2mysql($date, false);
        
        // Ще извличаме данните от първия ден на месеца от подадената дата до нея
        $jQuery = acc_JournalDetails::getQuery();
        
        acc_JournalDetails::filterQuery($jQuery, $from, $to, $accSysId);
        
        foreach (range(1, 3) as $i) {
            $param = ${"item{$i}"};
            
            // Поставяме условие за перо на определена позиция само ако е зададено
            // Ако перото е зададено с '*' значи искаме всички записи
            if (isset($param) && $param != '*') {
                $jQuery->where("(#debitItem{$i} = {$param}) OR (#creditItem{$i} = {$param})");
            } elseif (is_null($param)) {
                
                // Ако няма стойност искаме и в запиа да няма
                $jQuery->where("(#debitItem{$i} IS NULL) OR (#creditItem{$i} IS NULL)");
            }
        }
        
        // Инстанцираме стратегията
        $accRec = acc_Accounts::getRecBySystemId($accSysId);
        $strategy = new acc_strategy_WAC($accRec->id);
        
        // Трябва сметката да е със стратегия
        expect(isset($accRec->strategy));
        
        // Ако има предишен баланс, захранваме стратегията с крайните му салда, ако са положителни
        if ($balanceRec = cls::get('acc_Balances')->getBalanceBefore($from)) {
            $bQuery = acc_BalanceDetails::getQuery();
            
            $bItem1 = ($item1 == '*') ? null : $item1;
            $bItem2 = ($item2 == '*') ? null : $item2;
            $bItem3 = ($item3 == '*') ? null : $item3;
            acc_BalanceDetails::filterQuery($bQuery, $balanceRec->id, $accSysId, null, $bItem1, $bItem2, $bItem3);
            
            while ($bRec = $bQuery->fetch()) {
                
                // "Захранваме" обекта стратегия с количество и сума, ако к-то е неотрицателно
                if ($bRec->blQuantity >= 0) {
                    $strategy->feed($bRec->blQuantity, $bRec->blAmount);
                }
            }
        }
        
        // За всеки запис
        while ($rec = $jQuery->fetch()) {
            
            // Обикаляме дебита и кредита
            foreach (array('debit', 'credit') as $type) {
                $accId = $rec->{"{$type}AccId"};
                $pos1 = $rec->{"{$type}Item1"};
                $pos2 = $rec->{"{$type}Item2"};
                $pos3 = $rec->{"{$type}Item3"};
                
                // Ако страната отговаря точно на аналитичната сметка
                if ($accId == $accRec->id && ($item1 == '*' || $pos1 == $item1) && ($item2 == '*' || $pos2 == $item2) && ($item3 == '*' || $pos3 == $item3)) {
                    $feedType = ($type == 'debit') ? 'active' : 'credit';
                    
                    // Ако типа на сметката, позволява да бъде 'хранена'
                    if ($accRec->type == $feedType) {
                        
                        // Захранваме сметката със съответното к-во и сума
                        $strategy->feed($rec->{"{$type}Quantity"}, $rec->amount);
                    }
                }
            }
        }
        
        // Опитваме се да намерим сумата за к-то
        $amount = $strategy->consume($quantity);
        
        // Ако няма сума и няма максимален брой ипити или има максимален брой и не сме ги достигнали
        if (!isset($amount) && (!isset($maxTries) || (isset($maxTries) && $currentTry < $maxTries))) {
            
            // За нова дата към която ще търсим става последния ден от периода преди началото на търсенето
            $newTo = dt::addDays(-1, $from);
            $newTo = dt::verbal2mysql($newTo, false);
            
            // Ако има баланс преди тази дата
            if (cls::get('acc_Balances')->getBalanceBefore($date)) {
                
                // Рекурсирно извикваме същата функция
                return self::getAmount($quantity, $newTo, $accSysId, $item1, $item2, $item3, $maxTries, $currentTry);
            }
        }
        
        // Връщаме намерената сума (ако има)
        return $amount;
    }
}
