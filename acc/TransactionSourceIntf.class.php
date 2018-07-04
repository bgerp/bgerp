<?php



/**
 * Интерфейс за документ, генериращ счетоводни транзакции
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title:    Интерфейс източник на счетоводни транзакции
 */
class acc_TransactionSourceIntf
{
    
    
    /**
     * Връща линк към документа с посоченото id
     */
    public function getLink($id)
    {
        return $this->class->getLink($id);
    }
    
    
    /**
     * Връща счетоводната транзакция, породена от документа.
     *
     * Резултатът е обект или масив със следната структура:
     *
     * - reason         string
     * - valior date    string (date)
     * - entries        array
     *
     * Член-променливата `entries` е масив от обекти или масиви, всеки със следната структура:
     *
     * - amount             number
     *
     * - credit         array
     * [0]          string          systemId на счетоводна сметка
     * [1]          array(клас, ид) перо на първата аналитичност (ако има)
     * [2]          array(клас, ид) перо на втората аналитичност (ако има)
     * [3]          array(клас, ид) перо на третата аналитичност (ако има)
     * [quantity]   numeric         количество, ако кредит сметката има размерна аналитичност
     *
     * - debit          array           аналогично по структура на 'credit'
     * [0]          string          systemId на счетоводна сметка
     * [1]          array(клас, ид) перо на първата аналитичност (ако има)
     * [2]          array(клас, ид) перо на втората аналитичност (ако има)
     * [3]          array(клас, ид) перо на третата аналитичност (ако има)
     * [quantity]   numeric         количество, ако дебит сметката има размерна аналитичност
     *
     *
     * @example Плащане на 100 евро в брой от каса по задължение към фирма-доставчик
     *
     * Ако
     * $contragentId е ID-то на доставчика в crm_Companies
     * $caseId       е ID-то на касата в cash_Cases, откъдето излизат парите
     * $currencyId   е ID-то на EUR в currency_Currencies
     *
     * 1.95583 e курса на BGN (осн. валута) спрямо EUR към 28 януари 2013
     *
     * То
     * следната транзакция описва това плащане счетоводно:
     *
     * array(
     * 'reason'  => 'Плащане към доставчик',
     * 'valior   => '2013-01-28 17:33',
     * 'entries' => array(
     * array(
     * 'amount => 100 * 1.95583,
     * 'credit' => array(
     * '501', // Каси,
     * array('cash_Cases', $caseId),
     * array('currency_Currencies', $currencyId),
     * 'quantity' => 100, // бр. EUR
     * ),
     * 'debit' => array(
     * '401', // Задължения към доставчици
     * array('crm_Companies', $contragentId),
     * array('currency_Currencies', $currencyId),
     * 'quantity' => 100, // бр. EUR
     * )
     * )
     * )
     * )
     *
     *
     * @param  int      $id ид на документ
     * @return stdClass
     */
    public function getTransaction($id)
    {
        return $this->class->getTransaction($id);
    }
    
    
    /**
     * Нотификация за успешно записана счетоводна транзакция.
     *
     * @param int $id ид на документ
     */
    public function finalizeTransaction($id)
    {
        return $this->class->finalizeTransaction($id);
    }
}
