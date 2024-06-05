<?php


/**
 * Интерфейс за източници на клиентски карти
 *
 * @category  bgerp
 * @package   crm
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за източници на клиентски карти
 */
class crm_interface_CardSourceIntf
{
    /**
     * Клас
     */
    public $class;


    /**
     * Връща информация за наличните клиентски карти
     *
     * @return array масив от обекти
     *      (string)   number    - номер
     *      (int)      personId  - ид на лице
     *      (string)   type      - вид на картата personal/company
     *      (int|null) companyId - ид на фирма, null ако е лична
     *      (string)   state     - състояние active/closed
     */
    public function getCards()
    {
        return $this->class->getCards();
    }


    /**
     * Линк до който да води номера на картата
     *
     * @param string $number
     * @param int $contragentClassId
     * @param int $contragentId
     * @return string|core_ET
     */
    public function getNumberLink($number, $contragentClassId, $contragentId)
    {
        return $this->class->getNumberLink($number, $contragentClassId, $contragentId);
    }
}