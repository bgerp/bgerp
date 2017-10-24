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
	 * Метод за вземане на резултатност на хората. За определена дата се изчислява
     * успеваемостта на човека спрямо ресурса, които е изпозлвал 
	 *
	 * @param date $timeline  - Времето, след което да се вземат всички модифицирани/създадени записи
	 * @return array $result  - масив с обекти
	 *
	 * 			o date        - дата на стайноста
	 * 		    o personId    - ид на лицето
	 *          o docId       - ид на документа
	 *          o docClass    - клас ид на документа
	 *          o indicatorId - ид на индикатора
	 *          o value       - стойноста на инфикатора
	 *          o isRejected  - оттеглена или не. Ако е оттеглена се изтрива от индикаторите
	 */
    public function getIndicatorValues($timeline) 
    {
        return $this->class->getSalaryIndicators($timeline);
    }


    /**
     * Връща масив, в който са ид-тата на индикаторите и техните имена,
     * които се поддържат от дадения източник
     */
    public function getIndicatorNames()
    {
        return $this->class->getIndicatorNames($afterTheTime);
    }
}