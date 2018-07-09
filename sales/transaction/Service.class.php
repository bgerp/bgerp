<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа sales_Services
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see acc_TransactionSourceIntf
 *
 */
class sales_transaction_Service extends acc_DocumentTransactionSource
{
    /**
     *
     * @var sales_Services
     */
    public $class;
    
    
    /**
     * Транзакция за запис в журнала
     *
     * @param int $id
     */
    public function getTransaction($id)
    {
        $entries = array();
        
        $rec = $this->class->fetchRec($id);
        $origin = $this->class->getOrigin($rec);
        
        if ($rec->id) {
            $dQuery = sales_ServicesDetails::getQuery();
            $dQuery->where("#shipmentId = {$rec->id}");
            $rec->details = $dQuery->fetchAll();
        }
        
        $entries = array();
        
        // Всяко ЕН трябва да има поне един детайл
        if (count($rec->details) > 0) {
            if ($rec->isReverse == 'yes') {
                
                // Ако ЕН е обратна, тя прави контировка на СР но с отрицателни стойностти
                $reverseSource = cls::getInterface('acc_TransactionSourceIntf', 'purchase_Services');
                $entries = $reverseSource->getReverseEntries($rec, $origin);
            } else {
                // Записите от тип 1 (вземане от клиент)
                $entries = $this->getEntries($rec, $origin);
            }
        }
        
        $transaction = (object) array(
            'reason' => 'Протокол за доставка на услуги #' . $rec->id,
            'valior' => $rec->valior,
            'entries' => $entries,
        );
        
        return $transaction;
    }
    
    
    /**
     * Записите на транзакцията
     */
    public function getEntries($rec, $origin, $reverse = false)
    {
        $entries = array();
        $sign = ($reverse) ? -1 : 1;
        
        if (count($rec->details)) {
            deals_Helper::fillRecs($this->class, $rec->details, $rec, array('alwaysHideVat' => true));
            $currencyId = currency_Currencies::getIdByCode($rec->currencyId);
            
            foreach ($rec->details as $dRec) {
                $amount = $dRec->amount;
                $amount = ($dRec->discount) ?  $amount * (1 - $dRec->discount) : $amount;
                $amount = round($amount, 2);
                
                $entries[] = array(
                    'amount' => $sign * $amount * $rec->currencyRate, // В основна валута
                    
                    'debit' => array(
                        $rec->accountId,
                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
                        array($origin->className, $origin->that),			// Перо 2 - Сделка
                        array('currency_Currencies', $currencyId),     		// Перо 3 - Валута
                        'quantity' => $sign * $amount, // "брой пари" във валутата на продажбата
                    ),
                    
                    'credit' => array(
                        '703', // Сметка "703". Приходи от продажби на услуги
                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
                        array($origin->className, $origin->that),			// Перо 2 - Сделка
                        array('cat_Products', $dRec->productId), // Перо 3 - Артикул
                        'quantity' => $sign * $dRec->quantity, // Количество продукт в основната му мярка
                    ),
                );
            }
            
            if ($this->class->_total->vat) {
                $vat = $this->class->_total->vat;
                $vatAmount = $this->class->_total->vat * $rec->currencyRate;
                $entries[] = array(
                    'amount' => $sign * $vatAmount, // В основна валута
                    
                    'debit' => array(
                        $rec->accountId,
                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
                        array($origin->className, $origin->that),			// Перо 2 - Сделка
                        array('currency_Currencies', $currencyId), // Перо 3 - Валута
                        'quantity' => $sign * $vat, // "брой пари" във валутата на продажбата
                    ),
                    
                    'credit' => array(
                        '4530',
                        array($origin->className, $origin->that),
                    ),
                );
            }
        }
        
        return $entries;
    }
    
    
    /**
     * Връща обратна контировка на стандартната
     */
    public function getReverseEntries($rec, $origin)
    {
        return $this->getEntries($rec, $origin, true);
    }
}
