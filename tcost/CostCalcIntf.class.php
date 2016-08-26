<?php



/**
 * Клас 'tcost_CostCalcIntf' - Интерфейс за класове, които определят цената за транспорт
 *
 *
 * @category  bgerp
 * @package   tcost
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class tcost_CostCalcIntf
{
	
	
    /**
     * Определяне на обемното тегло, на база на обема на товара
     * 
     * @param    double  $weight    Тегло на товара
     * @param    double  $volume    Обем  на товара
     *
     * @return   double             Обемно тегло на товара  
     */
    public function getVolumicWeight($weight, $volume)
    {
        return $this->class->getVolumicWeight($weight, $volume);
    }


    /**
     * Определяне цената за транспорт при посочените параметри
     *
     * @param   int     $fromCountry    id на страната на мястото на изпращане
     * @param   string  $fromPCode      пощенски код на мястото на изпращане
     * @param   int     $toCountry      id на страната на мястото за получаване
     * @param   string  $toPCode        пощенски код на мястото за получаване
     * @param   double  $totalWeight    Общо тегло на товара
     * @param   double  $weight         Тегло за което искаме да изчислим цената
     *
     * @return double                   Цена, която ще бъде платена за $weight тегло
     */
    function getTransportFee($fromCountry, $fromPCode, $toCountry, $toPCode, $totalWeight, $weight = 1)
    {
        return $this->class->getTransportFee($fromCountry, $fromPCode, $toCountry, $toPCode, $totalWeight, $weight);
    }
}