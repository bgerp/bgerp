<?php


/**
 * Интерфейс за складови документи
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_iface_DocumentIntf
{
    /**
     * Клас имплементиращ интерфейса
     */
    public $class;


    /**
     * Връща датите на които ще има действия с документа
     *
     * @param int|stdClass $rec
     * @return array
     *          ['readyOn']    - готовност на
     *          ['shipmentOn'] - експедиране на
     *          ['loadingOn']  - натоварване на
     *          ['unloadingOn']  - натоварване на
     *          ['deliveryOn'] - доставка на
     *          ['valior']     - вальор на
     */
    public function getCalcedDates($rec)
    {
        return $this->class->getCalcedDates($rec);
    }


    /**
     * Kои са полетата за датите за експедирането
     *
     * @param mixed $rec
     * @return array $res
     */
    public function getShipmentDateFields($rec = null)
    {
        return $this->class->getShipmentDateFields($rec);
    }
}
