<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа store_Transfers
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see acc_TransactionSourceIntf
 *
 */
class store_transaction_Transfer extends acc_DocumentTransactionSource
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
            'reason' => "Междускладов трансфер №{$rec->id}",
            'valior' => $rec->valior,
            'totalAmount' => null,
            'entries' => array()
        );
        
        $productArr = array();
        $error = true;
        $dQuery = store_TransfersDetails::getQuery();
        $dQuery->where("#transferId = '{$rec->id}'");
        $details = $dQuery->fetchAll();

        // Ако ще се доведе до отрицателна, количност и не е разрешено да се сетне грешка
        if (Mode::get('saveTransaction')) {
            $allowNegativeShipment = store_Setup::get('ALLOW_NEGATIVE_SHIPMENT');
            if($allowNegativeShipment == 'no'){
                if ($warning = deals_Helper::getWarningForNegativeQuantitiesInStore($details, $rec->fromStore, $rec->state, 'newProductId')) {
                    acc_journal_RejectRedirect::expect(false, $warning);
                }
            }
        }

        foreach ($details as $dRec) {
            $productArr[$dRec->newProductId] = $dRec->newProductId;
            if (empty($dRec->quantity)) {
                if (Mode::get('saveTransaction')) {
                    continue;
                }
            } else {
                $error = false;
            }
            
            // Ако артикула е вложим сметка 321
            $accId = '321';
            $result->entries[] = array(
                'credit' => array($accId,
                    array('store_Stores', $rec->fromStore), // Перо 1 - Склад
                    array('cat_Products', $dRec->newProductId),  // Перо 2 - Артикул
                    'quantity' => $dRec->quantity, // Количество продукт в основната му мярка,
                ),
                
                'debit' => array($accId,
                    array('store_Stores', $rec->toStore), // Перо 1 - Склад
                    array('cat_Products', $dRec->newProductId),  // Перо 2 - Артикул
                    'quantity' => $dRec->quantity, // Количество продукт в основната му мярка
                ),
            );
        }
        
        if (Mode::get('saveTransaction')) {
            if($redirectError = deals_Helper::getContoRedirectError($productArr, 'canStore', 'generic', 'трябва да са складируеми и да не са генерични')){
                acc_journal_RejectRedirect::expect(false, $redirectError);
            } elseif ($error === true) {
                acc_journal_RejectRedirect::expect(false, 'Трябва да има поне един ред с ненулево количество|*!');
            }
        }
        
        return $result;
    }
}
