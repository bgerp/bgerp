<?php


/**
 * Интерфейс за документи които могат да се закачат към транспортна линия
 *
 *
 * @category  bgerp
 * @package   trans
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за документи които могат да се закачат към транспортна линия
 */
class trans_TransportableIntf
{
    /**
     * Клас имплементиращ мениджъра
     */
    public $class;
    
    
    /**
     * Информацията на документа, за показване в транспортната линия
     *
     * @param mixed $id
     * @param int $lineId
     *
     * @return array
     *               ['baseAmount']     double|NULL - сумата за инкасиране във базова валута
     *               ['amount']         double|NULL - сумата за инкасиране във валутата на документа
     *               ['amountVerbal']   double|NULL - сумата за инкасиране във валутата на документа
     *               ['currencyId']     string|NULL - валутата на документа
     *               ['notes']          string|NULL - забележки за транспортната линия
     *               ['stores']         array       - склад(ове) в документа
     *               ['weight']         double|NULL - общо тегло на стоките в документа
     *               ['volume']         double|NULL - общ обем на стоките в документа
     *               ['transportUnits'] array   - използваните ЛЕ в документа, в формата ле -> к-во
     *               ['contragentName'] double|NULL - име на контрагента
     */
    public function getTransportLineInfo($id, $lineId)
    {
        return $this->class->getTransportLineInfo($id, $lineId);
    }
    
    
    /**
     * Трябва ли ръчно да се подготвя документа в Транспортната линия
     *
     * @param mixed $id - ид или запис на документа
     *
     * @return bool - TRUE или FALSE
     */
    public function requireManualCheckInTransportLine($id)
    {
        return $this->class->requireManualCheckInTransportLine($id);
    }
}