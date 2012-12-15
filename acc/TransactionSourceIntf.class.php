<?php



/**
 * Интерфейс за документ, генериращ счетоводни транзакции
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title:     Източник на счетоводни транзакции
 */
class acc_TransactionSourceIntf
{
    
    
    /**
     * Връща линк към документа с посоченото id
     */
    function getLink($id)
    {
        return $this->class->getLink($id);
    }
    
    
    /**
     * Връща счетоводната транзакция, породена от документа.
     *
     * Резултатът е обект със следната структура:
     *
     * - reason         string
     * - valior date    string (date)
     * - totalAmount    number - опционално
     * - entries        array
     *
     * Член-променливата `entries` е масив от обекти, всеки със следната структура:
     *
     * - amount             number
     *
     * - debitQuantity           number
     * - debitPrice              number
     * 
     * - creditQuantity          number
     * - debitPrice              number
     *
     * - debitAcc           key(mvc=acc_Accounts, key=sid)
     * - debitAccId         key(mvc=acc_Accounts)
     *
     * - debitItem1         '->cls' = име на мениджър/инстанция с acc_RegistryIntf, '->id' = ид на обект от този мениджър
     * - debitItem1Id        key(mvc=acc_Items) - перо от 1-вата разбивка на `debitAccId`
     *
     * - debitItem2          '->cls' = име на мениджър/инстанция с acc_RegistryIntf, '->id' = ид на обект от този мениджър
     * - debitItem2Id        key(mvc=acc_Items) - перо от 2-рата разбивка на `debitAccId`
     *
     * - debitItem3          '->cls' = име на мениджър/инстанция с acc_RegistryIntf, '->id' = ид на обект от този мениджър
     * - debitItem3Id        key(mvc=acc_Items) - перо от 3-тата разбивка на `debitAccId`
     *
     * - creditAcc          key(mvc=acc_Accounts, key=sid)
     * - creditAccId        key(mvc=acc_Accounts)
     *
     * - creditItem1        '->cls' = име на мениджър/инстанция с acc_RegistryIntf, '->id' = ид на обект от този мениджър
     * - creditItem1Id      key(mvc=acc_Items) - перо от 1-вата разбивка на `creditAccId`
     *
     * - creditItem2        '->cls' = име на мениджър/инстанция с acc_RegistryIntf, '->id' = ид на обект от този мениджър
     * - creditItem2Id      key(mvc=acc_Items) - перо от 2-рата разбивка на `creditAccId`

     * - creditItem3        '->cls' = име на мениджър/инстанция с acc_RegistryIntf, '->id' = ид на обект от този мениджър
     * - creditItem3Id        key(mvc=acc_Items) - перо от 3-тата разбивка на `creditAccId`

          
     
     

     *
     * @param int $id ид на документ
     * @return stdClass
     */
    function getTransaction($id)
    {
        return $this->class->getTransaction($id);
    }
    
    
    /**
     * Нотификация за успешно записана счетоводна транзакция.
     *
     * @param int $id ид на документ
     */
    function finalizeTransaction($id)
    {
        return $this->class->finalizeTransaction($id);
    }
    
    
    /**
     * Нотификация за сторнирана счетоводна транзакция.
     *
     * @param int $id ид на документ
     */
    function rejectTransaction($id)
    {
        return $this->class->rejectTransaction($id);
    }
}