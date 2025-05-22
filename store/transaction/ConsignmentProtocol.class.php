<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа store_ConsignmentProtocols
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see acc_TransactionSourceIntf
 *
 */
class store_transaction_ConsignmentProtocol extends acc_DocumentTransactionSource
{
    /**
     * @param int $id
     *
     * @return stdClass
     *
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public function getTransaction($id)
    {
        // Извличане на мастър-записа
        expect($rec = $this->class->fetchRec($id));
        $rec->valior = empty($rec->valior) ? dt::today() : $rec->valior;
        
        $result = (object) array(
            'reason' => "Протокол за отговорно пазене №{$rec->id}",
            'valior' => $rec->valior,
            'totalAmount' => null,
            'entries' => array()
        );
        
        if ($rec->id) {
            $result->entries = $this->getEntries($rec);
        }
        
        return $result;
    }
    
    
    /**
     * Подготвя записите
     *
     * За предадените артикули:
     *
     * 		Dt: 3231. Предадени на ОП наши СМЗ              (Контрагенти, Артикули)
            или Dt: 3232. Получени на ОП чужди СМЗ          (Контрагенти, Артикули)
     *
     *      Ct: 321. Суровини, материали, продукция, стоки	(Складове, Артикули)
     *
     * За върнатите артикули:
     *
     * 		Dt: 321. Суровини, материали, продукция, стоки	(Складове, Артикули)
     *
     *      Ct: 3232. Получени на ОП чужди СМЗ				(Контрагенти, Артикули)
     *      или Ct: 3232. Получени на ОП чужди СМЗ          (Контрагенти, Артикули)
     */
    private function getEntries($rec)
    {
        $entries = array();
        $productsArr = array();

        $rate = currency_CurrencyRates::getRate($rec->valior, $rec->currencyId, null);

        // Намираме всички предадени артикули
        $sendQuery = store_ConsignmentProtocolDetailsSend::getQuery();
        $sendQuery->where("#protocolId = {$rec->id}");
        $sendAll = $sendQuery->fetchAll();

        if (acc_Journal::throwErrorsIfFoundWhenTryingToPost()) {
            if(!store_Setup::canDoShippingWhenStockIsNegative()){
                if ($warning = deals_Helper::getWarningForNegativeQuantitiesInStore($sendAll, $rec->storeId, $rec->state)) {
                    acc_journal_RejectRedirect::expect(false, $warning);
                }
            }
        }

        $receivedQuery = store_ConsignmentProtocolDetailsReceived::getQuery();
        $receivedQuery->where("#protocolId = {$rec->id}");

        $receivedAll = $receivedQuery->fetchAll();

        $sendArr = $receivedArr = array();


        // Ако е за "Наши артикули"
        if($rec->productType == 'ours'){
            array_walk($sendAll, function($a) use (&$sendArr) {
                if(!array_key_exists($a->productId, $sendArr)){
                    $sendArr[$a->productId] = (object)array('productId' => $a->productId, 'quantity' => 0);
                }
                $sendArr[$a->productId]->quantity += $a->quantity;
            });

            array_walk($receivedAll, function($a) use (&$receivedArr) {
                if(!array_key_exists($a->productId, $receivedArr)){
                    $receivedArr[$a->productId] = (object)array('productId' => $a->productId, 'quantity' => 0);
                }
                $receivedArr[$a->productId]->quantity += $a->quantity;
            });

            // Има ли артикули, които се предават и връщат със същия документ
            $intersectedArr = array_keys(array_intersect_key($sendArr, $receivedArr));

            // Ако има ще се отчита само резултатната операция, целта е да не се кръстостват сметките със стратегия
            foreach ($intersectedArr as $intersectedProductId){
                $clone = clone $sendArr[$intersectedProductId];
                $clone->quantity = $sendArr[$intersectedProductId]->quantity -= $receivedArr[$intersectedProductId]->quantity;
                unset($sendArr[$intersectedProductId], $receivedArr[$intersectedProductId]);

                if($clone->quantity < 0){
                    $clone->quantity = abs($clone->quantity);
                    $receivedArr[$intersectedProductId] = $clone;
                } else {
                    $sendArr[$intersectedProductId] = $clone;
                }
            }
        } else {
            $sendArr = $sendAll;
            $receivedArr = $receivedAll;
        }

        foreach ($sendArr as $sendRec) {
            $productsArr[$sendRec->productId] = $sendRec->productId;
            $debitAccId = ($rec->productType == 'ours') ? '3231' : '3232';

            $entry = array(
                'debit' => array($debitAccId,
                    array($rec->contragentClassId, $rec->contragentId),
                    array('cat_Products', $sendRec->productId),
                    'quantity' => $sendRec->quantity),
            );

            if($rec->productType == 'ours'){
                $entry['credit'] = array('321',
                                        array('store_Stores', $rec->storeId),
                                        array('cat_Products', $sendRec->productId),
                                        'quantity' => $sendRec->quantity);
            } else {
                $entry['credit'] = array('3230',
                                        array('store_Stores', $rec->storeId),
                                        array($rec->contragentClassId, $rec->contragentId),
                                        array('cat_Products', $sendRec->productId),
                                        'quantity' => $sendRec->quantity);
            }

            if($debitAccId == '3232'){
                $amount = round($sendRec->amount * $rate, 2);
                $entry['amount'] = $amount;
            }

            $entries[] = $entry;
        }

        // Намираме всички върнати артикули
        foreach ($receivedArr as $recRec) {
            $productsArr[$recRec->productId] = $recRec->productId;
            $creditAccId = ($rec->productType == 'ours') ? '3231' : '3232';

            $entry = array(
                'credit' => array($creditAccId,
                    array($rec->contragentClassId, $rec->contragentId),
                    array('cat_Products', $recRec->productId),
                    'quantity' => $recRec->quantity),
            
            );

            if($rec->productType == 'ours'){
                $entry['debit'] = array('321',
                                    array('store_Stores', $rec->storeId),
                                    array('cat_Products', $recRec->productId),
                                'quantity' => $recRec->quantity);
            } else {
                $entry['debit'] = array('3230',
                                    array('store_Stores', $rec->storeId),
                                    array($rec->contragentClassId, $rec->contragentId),
                                    array('cat_Products', $recRec->productId),
                                    'quantity' => $recRec->quantity);
            }

            if($creditAccId == '3232'){
                $amount = round($recRec->amount * $rate, 2);
                $entry['amount'] = $amount;
            }

            $entries[] = $entry;
        }

        if (acc_Journal::throwErrorsIfFoundWhenTryingToPost()) {
            $sendProductsArr = arr::extractValuesFromArray($sendArr, 'productId');
            $receivedProductsArr = arr::extractValuesFromArray($receivedArr, 'productId');

            $haveMetaToSend = cls::get('store_ConsignmentProtocolDetailsSend')->getExpectedProductMetaProperties($rec->productType, 'send');
            $errorMsg = 'трябва да са складируеми и да не са генерични';
            if($redirectError = deals_Helper::getContoRedirectError($sendProductsArr, $haveMetaToSend, 'generic', $errorMsg)){
                acc_journal_RejectRedirect::expect(false, $redirectError);
            }

            $haveMetaToReceive = cls::get('store_ConsignmentProtocolDetailsReceived')->getExpectedProductMetaProperties($rec->productType, 'receive');
            if($redirectError = deals_Helper::getContoRedirectError($receivedProductsArr, $haveMetaToReceive, 'generic', $errorMsg)){
                acc_journal_RejectRedirect::expect(false, $redirectError);
            }
        }
        
        // Връщаме записите
        return $entries;
    }
}
