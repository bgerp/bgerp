<?php


/**
 * Клас 'doc_ContragentDataIntf' - Интерфейс за данните на адресата
 *
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за данните на адресата
 */
class doc_ContragentDataIntf
{
    /**
     * Връща данните на получателя
     * return object
     *
     * $obj->company    - Името на компанията
     * $obj->companyId  - Id' то на компанията - key(mvc=crm_Companies)
     * $obj->country    - Името на държавата
     * $obj->countryId  - Id' то на
     * $obj->vatNo      - ДДС номер на компанията
     * $obj->uicId      - Национален номер на компанията
     * $obj->pCode      - код
     * $obj->place      -
     * $obj->email      - Имейл
     * $obj->tel        - Телефон
     * $obj->fax        - Факс
     * $obj->address    - Адрес
     *
     * $obj->name       - Име на физическо лице
     * $obj->personId   - ИД на лице - key(mvc=crm_Persons)
     * $obj->pTel       - Персонален телефон
     * $obj->pMobile    - Мобилен
     * $obj->pFax       - Персонален
     * $obj->pAddress   - Персонален адрес
     * $obj->pEmail     - Персонален имейл
     */
    public function getContragentData($id, $date = null)
    {
        return $this->class->getContragentData($id, $date);
    }


    /**
     * Връща пълния конкатениран адрес на контрагента
     *
     * @param int        $id            - ид на контрагент
     * @param bool       $translitarate - дали да се транслитерира адреса
     * @param bool|NULL  $showCountry   - да се показвали винаги държавата или Не, NULL означава че автоматично ще се определи
     * @param bool       $showAddress   - да се показва ли адреса
     * @param date|null  $date          - да се показва ли адреса
     *
     * @return core_ET $tpl - адреса
     */
    public function getFullAdress($id, $translitarate = false, $showCountry = null, $showAddress = true, $date = null)
    {
        return $this->class->getFullAdress($id, $translitarate, $showCountry, $showAddress, $date);
    }


    /**
     * Дали на лицето се начислява ДДС:
     * Начисляваме винаги ако е в ЕУ (ако е регистриран по ДДС)
     *
     * @param int $id                - id' то на записа
     * @param mixed $class           - за кой клас
     * @param int|null $ownCompanyId - ид на "Моята фирма"
     *
     * @return bool TRUE/FALSE
     */
    public function shouldChargeVat($id, $class, $ownCompanyId = null)
    {
        return $this->class->shouldChargeVat($id, $class, $ownCompanyId);
    }
    
    
    /**
     * Форсира контрагент в дадена група
     *
     * @param int    $id         -ид на продукт
     * @param string $groupSysId - sysId на група
     */
    public function forceGroup($id, $groupSysId)
    {
        return $this->class->forceGroup($id, $groupSysId);
    }
}
