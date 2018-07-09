<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа accda_Da
 *
 * @category  bgerp
 * @package   accda
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see acc_TransactionSourceIntf
 *
 */
class accda_transaction_Da extends acc_DocumentTransactionSource
{
    /**
     *
     * @var accda_Da
     */
    public $class;
    
    
    /**
     *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
     *  Създава транзакция която се записва в Журнала, при контирането
     */
    public function getTransaction($id)
    {
        // Извличаме записа
        expect($rec = $this->class->fetchRec($id));
        
        $entries = array();
        
        if ($rec->id) {
            $pInfo = cat_Products::getProductInfo($rec->productId);
            if (isset($pInfo->meta['canStore'])) {
                $creditArr = array('321',
                    array('store_Stores', $rec->storeId),
                    array('cat_Products', $rec->productId),
                    'quantity' => 1,
                );
            } else {
                $creditArr = array('613',
                    array('cat_Products', $rec->productId),
                    'quantity' => 1,);
            }
            
            $debitArr = array(acc_Accounts::fetchField($rec->accountId, 'systemId'), array('accda_Da', $rec->id));
            
            $entries[] = array(
                'debit' => $debitArr,
                'credit' => $creditArr,
            );
        }
        
        // Подготвяме информацията която ще записваме в Журнала
        $result = (object) array(
            'reason' => $this->class->getRecTitle($rec), // основанието за ордера
            'valior' => $rec->valior,   // датата на ордера
            'entries' => $entries);
        
        return $result;
    }
}
