<?php



/**
 * Интерфейс за сладови документи
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_iface_DocumentIntf
{
    
    
    
    /**
     * Клас имплементиращ интерфейса
     */
    public $class;
    
    
    /**
     * Информацията на документа, за показване в транспортната линия
     *
     * @param  mixed $id
     * @return array
     *                  ['baseAmount'] double|NULL - сумата за инкасиране във базова валута
     *                  ['amount']     double|NULL - сумата за инкасиране във валутата на документа
     *                  ['currencyId'] string|NULL - валутата на документа
     *                  ['notes']      string|NULL - забележки за транспортната линия
     *                  ['stores']     array       - склад(ове) в документа
     *                  ['weight']     double|NULL - общо тегло на стоките в документа
     *                  ['volume']     double|NULL - oбщ обем на стоките в документа
     *                  ['transportUnits'] array   - използваните ЛЕ в документа, в формата ле -> к-во
     *                  [transUnitId] => quantity
     */
    public function getTransportLineInfo($id)
    {
        return $this->class->getTransportLineInfo($id);
    }
    
    
    /**
     * Трябва ли ръчно да се подготвя документа в Транспортната линия
     *
     * @param  mixed   $id - ид или запис на документа
     * @return boolean - TRUE или FALSE
     */
    public function requireManualCheckInTransportLine($id)
    {
        return $this->class->requireManualCheckInTransportLine($id);
    }
}
