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
	 * Стойността, която ще се върне ако е имало грешка в изчислението
	 */
	const ZONE_FIND_ERROR = -2;
    const DELIMITER_ERROR = -4;
    const EMPTY_WEIGHT_ERROR = -8;
	
	
    /**
     * Определяне на обемното тегло, на база на обема на товара
     * 
     * @param double $weight  - Тегло на товара
     * @param double $volume  - Обем  на товара
     *
     * @return double         - Обемно тегло на товара  
     */
    public function getVolumicWeight($weight, $volume)
    {
        return $this->class->getVolumicWeight($weight, $volume);
    }


    /**
     * Определяне цената за транспорт при посочените параметри
     *
     * @param int $deliveryTermId    -условие на доставка
     * @param int $productId         - ид на артикул
     * @param int $packagingId       - ид на опаковка/мярка
     * @param int $quantity          - количество
     * @param int $totalWeight       - Общо тегло на товара
     * @param int $toCountry         - id на страната на мястото за получаване
     * @param string $toPostalCode   - пощенски код на мястото за получаване
     * @param int $fromCountry       - id на страната на мястото за изпращане
     * @param string $fromPostalCode - пощенски код на мястото за изпращане
     *
     * @return array
     * 			['fee']              - цена, която ще бъде платена за теглото на артикул, ако не може да се изчисли се връща < 0
     * 			['deliveryTime']     - срока на доставка в секунди ако го има
     */
    function getTransportFee($deliveryTermId, $productId, $packagingId, $quantity, $totalWeight, $toCountry, $toPostalCode, $fromCountry, $fromPostalCode)
    {
        return $this->class->getTransportFee($deliveryTermId, $productId, $packagingId, $quantity, $totalWeight, $toCountry, $toPostalCode, $fromCountry, $fromPostalCode);
    }
}