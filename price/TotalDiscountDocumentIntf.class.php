<?php


/**
 * Интерфейс за документи на които може да се задават общи отстъпки
 *
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за документи на които може да се задават общи отстъпки
 */
class price_TotalDiscountDocumentIntf
{


    /**
     * Как се казва политиката
     *
     * @param stdClass $rec - запис
     * @return array
     *            [rate]       - валутен курс
     *            [valior]     - вальор
     *            [currencyId] - код на валута
     *            [chargeVat]  - режим на начисляване на ДДС
     *            [amount]     - сума в основна валута без ддс и отстъпка
     */
    public function getTotalDiscountSourceData($rec)
    {
        return $this->class->getTotalDiscountSourceData($rec);
    }


    /**
     * Как се казва политиката
     *
     * @param stdClass $rec - запис
     * @return bool
     */
    public function canHaveTotalDiscount($rec)
    {
        return $this->class->canHaveTotalDiscount($rec);
    }
}