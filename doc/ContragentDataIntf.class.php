<?php



/**
 * Клас 'doc_ContragentDataIntf' - Интерфейс за данните на адресанта
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
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
     * $obj->salutation - Обръщение
     */
    function getContragentData($id)
    {
        return $this->class->getContragentData($id);
    }
    
    
    /**
     * Връща тялото по подразбиране на имейл-а
     */
    function getDefaultEmailBody($originId)
    {
        return $this->class->getDefaultEmailBody($originId);
    }
    
    
	/**
     * Връща дали на контрагента се начислява ДДС
     */
    function shouldChargeVat($id)
    {
        return $this->class->shouldChargeVat($id);
    }
    
    
    /**
     * Връща стойността на дадено търговско условие за контрагента
     * @param int $id - ид на контрагента
     * @param string $conditionSysId - sysId на параметър (@see salecond_Others)
     * @return string $value - стойността на параметъра
     * Намира се в следния ред:
     * 	  1. Директен запис в salecond_ConditionsToCustomers
     * 	  2. Дефолт метод "get{$conditionSysId}" дефиниран в модела
     *    3. Супер дефолта на параметъра дефиниран в salecond_Others
     *    4. NULL ако нищо не е намерено
     */
    function getSaleCondition($id, $conditionSysId)
    {
    	return $this->class->getSaleCondition($id, $conditionSysId);
    }
}