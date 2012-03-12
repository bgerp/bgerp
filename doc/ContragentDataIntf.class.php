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
     * $obj->name       - Име
     * $obj->company    - Името на компанията
     * $obj->companyId  - Id' то на компанията
     * $obj->country    - Името на държавата
     * $obj->countryId  - Id' то на държвата
     * $obj->pCode      - Пощенск код
     * $obj->place      - Гдар
     * $obj->email      - Имейл
     * $obj->tel        - Телефон
     * $obj->fax        - Факс
     * $obj->address    - Адрес
     * $obj->pTel       - Персонален телефон
     * $obj->pMobile    - Мобилен
     * $obj->pFax       - Персонален фак
     * $obj->pAddress   - Персонален адрес
     * $obj->pEmail     - Персонален имейл
     * $obj->salutation - Обръщение
     */
    function getContragentData($id)
    {
        return $this->class->getContragentData($id);
    }
    
    
    /**
     * Връща тялото по подразбиране на имейла
     */
    function getDefaultEmailBody($originId)
    {
        return $this->class->getDefaultEmailBody($originId);
    }
}