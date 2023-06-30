<?php


/**
 * Интерфейс за документи източници на декларации за съответствие
 *
 *
 * @category  bgerp
 * @package   dec
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за документи източници на декларации за съответствие
 */
class dec_SourceIntf
{
    /**
     * Клас имплементиращ мениджъра
     */
    public $class;


    /**
     * Помощна ф-я връщаща артикулите за избор в декларацията от източника
     *
     * @param stdClass $rec
     * @return array
     *          'productId'
     *          'batches'
     */
    public function getProducts4Declaration($rec)
    {
        return $this->class->getProducts4Declaration($rec);
    }
}