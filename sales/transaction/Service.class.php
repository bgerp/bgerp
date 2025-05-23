<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа sales_Services
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2024 Experta OOD
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

        $rec->valior = empty($rec->valior) ? dt::today() : $rec->valior;

        // Всяко ЕН трябва да има поне един детайл
        if (countR($rec->details)) {
            if ($rec->isReverse == 'yes') {
                
                // Ако ЕН е обратна, тя прави контировка на СР но с отрицателни стойностти
                $reverseSource = cls::getInterface('acc_TransactionSourceIntf', 'purchase_Services');
                $entries = $reverseSource->getReverseEntries($rec, $origin);
            } else {

                // Записите от тип 1 (вземане от клиент)
                $entries = sales_transaction_Sale::getProductionEntries($rec, 'sales_Services', 'storeId');
                $entries1 = $this->getEntries($rec, $origin);
                $entries = array_merge($entries, $entries1);
            }
        }
        
        $transaction = (object) array('reason' => 'Протокол за доставка на услуги #' . $rec->id,
                                      'valior' => $rec->valior,
                                      'entries' => $entries,);
        
        // Ако някой от артикулите не може да бдъе произведем сетваме, че ще правим редирект със съобщението
        if (acc_Journal::throwErrorsIfFoundWhenTryingToPost()) {
            // Проверка на артикулите
            $property = ($rec->isReverse == 'yes') ? 'canBuy' : 'canSell';
            
            $productArr = arr::extractValuesFromArray($rec->details, 'productId');
            if (countR($productArr)) {
                $msg = ($rec->isReverse == 'yes') ? 'купуваеми услуги' : 'продаваеми услуги';
                $msg = "трябва да са {$msg} и да не са генерични";
                
                if($redirectError = deals_Helper::getContoRedirectError($productArr, $property, 'canStore,generic', $msg)){
                    
                    acc_journal_RejectRedirect::expect(false, $redirectError);
                }
            }
        }
        
        return $transaction;
    }
    
    
    /**
     * Записите на транзакцията
     */
    public function getEntries($rec, $origin, $reverse = false)
    {
        $entries = array();
        $sign = ($reverse) ? -1 : 1;
        
        if (countR($rec->details)) {
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
