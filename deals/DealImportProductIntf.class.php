<?php



/**
 * Интерфейс за детайли на бизнес документи в които да се импортират артикули
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за детайли на бизнес документи, в които да се импортират артикули
 */
class deals_DealImportProductIntf extends acc_RegisterIntf
{
    
    
    /**
     * Инпортиране на артикул генериран от ред на csv файл
     * @param  int    $masterId - ид на мастъра на детайла
     * @param  array  $row      - Обект представляващ артикула за импортиране
     *                          ->code - код/баркод на артикула
     *                          ->quantity - К-во на опаковката или в основна мярка
     *                          ->price - цената във валутата на мастъра, ако няма се изчислява директно
     * @return string $html - съобщение с резултата
     */
    public function import($masterId, $row)
    {
        return $this->class->import($masterId, $row);
    }
}
