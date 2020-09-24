<?php


/**
 * Интерфейс за източници на рейтинги на продажбите
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за източници на рейтинги на продажбите
 */
class sales_RatingsSourceIntf
{
    /**
     * Клас имплементиращ мениджъра
     */
    public $class;
    
    
    /**
     * Подготовка на рейтингите за продажба на артикулите
     * @see sales_RatingsSourceIntf
     *
     * @return array $res - масив с обекти за върнатите данни
     *                 o objectClassId - ид на клас на обект
     *                 o objectId      - ид на обект
     *                 o classId       - текущия клас
     *                 o key           - ключ
     *                 o value         - стойност
     */
    public function getSaleRatingsData()
    {
        return $this->class->getSaleRatingsData();
    }
}