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
