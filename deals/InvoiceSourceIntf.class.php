<?php



/**
 * Интерфейс за документи източници на фактури/проформи
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за документи източници на фактури/проформи
 */
class deals_InvoiceSourceIntf
{
    
    
    /**
     * Клас имплементиращ мениджъра
     */
    public $class;
    
    
    /**
     * Артикули които да се заредят във фактурата/проформата, когато е създадена от
     * определен документ
     *
     * @param  mixed               $id     - ид или запис на документа
     * @param  deals_InvoiceMaster $forMvc - клас наследник на deals_InvoiceMaster в който ще наливаме детайлите
     * @return array               $details - масив с артикули готови за запис
     *                                    o productId      - ид на артикул
     *                                    o packagingId    - ид на опаковка/основна мярка
     *                                    o quantity       - количество опаковка
     *                                    o quantityInPack - количество в опаковката
     *                                    o discount       - отстъпка
     *                                    o price          - цена за единица от основната мярка
     */
    public function getDetailsFromSource($id, deals_InvoiceMaster $forMvc)
    {
        return $this->class->getDetailsFromSource($id, $forMvc);
    }
}
