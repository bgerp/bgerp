<?php


/**
 * Интерфейс за документи използващи ДДС на артикула
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cat_interface_DocumentVatIntf
{

    /**
     * Инстанция на класа имплементиращ интерфейса
     */
    public $class;


    /**
     * Проверява дали артикула е използван в документ с ДДС-то му след подадената дата
     *
     * @param int $productId  - ид на артикула
     * @param date|null $date - към коя дата
     * @return mixed
     */
    public function isUsedAfterInVatDocument($productId, $date = null)
    {
        return $this->class->isUsedAfterInVatDocument($productId, $date);
    }
}