<?php



/**
 * Клас 'doc_ContragentDataIntf' - Интерфейс за данните на адресата
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
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
    function getContragentData($id)
    {
        return $this->class->getContragentData($id);
    }
    
    
	/**
     * Връща пълния конкатениран адрес на контрагента
     * 
     * @param int $id - ид на контрагент
     * @param boolean $translitarate - дали да се транслитерира адреса
     * @param boolean|NULL $showCountry - да се показвали винаги държавата или Не, NULL означава че автоматично ще се определи
     * @return core_ET $tpl - адреса
     */
    public function getFullAdress($id, $translitarate = FALSE, $showCountry = NULL)
    {
        return $this->class->getFullAdress($id, $translitarate);
    }
    
    
	/**
     * Връща дали на контрагента се начислява ДДС
     */
    function shouldChargeVat($id)
    {
        return $this->class->shouldChargeVat($id);
    }
    
    
    /**
     * Форсира контрагент в дадена група
     * 
     * @param int $id -ид на продукт
     * @param varchar $groupSysId - sysId на група
     */
    public function forceGroup($id, $groupSysId)
    {
    	return $this->class->forceGroup($id, $groupSysId);
    }
}