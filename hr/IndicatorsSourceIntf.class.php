<?php

 /**
 * Интерфейс
 *
 * @category  bgerp
 * @package   hr
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за сформиране на заплатите
 */
class hr_IndicatorsSourceIntf
{
    
    
    /**
     * Метод за вземане на резултатност на хората
     * За определена дата се изчислява
     * успеваемостта на човека спрямо ресурса, които е изпозлвал 
     * 
     * 
     * @param   $afterTheTime  $datetime    Времето, след което да се вземат всички модифицирани/създадени записи
     * @return array $result (date date, 
     *                        int personId,
     *                        int docId, 
     *                        int docClass, 
     *                        varchar indicator, 
     *                        double value,
     *                        bool isRejected,
     */
    public function getSalaryIndicators($afterTheTime) 
    {
        return $this->class->getSalaryIndicators($afterTheTime);
    }
}