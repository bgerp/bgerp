<?php


/**
 * Помощен клас-баща за източник на транзакция на контировката на документ за приключване на сделки
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 *
 * @see deals_ClosedDealTransaction
 *
 */
abstract class deals_ClosedDealTransaction extends acc_DocumentTransactionSource
{
    
    
    /**
     * Финализиране на транзакцията
     */
    public function finalizeTransaction($id)
    {
        // Извличаме записа
        $rec = $this->class->fetchRec($id);
    
        // Промяна на състоянието на документа
        $rec->state = $this->finalizedState;
        if (!$rec->valior) {
            $rec->valior = $this->class->getValiorDate($rec);
        }
        
        // Запазване на промененото състояние
        if ($id = $this->class->save($rec)) {
            
            // Ако записа е успешен, нотифицираме документа, че е бил активиран
            $this->class->invoke('AfterActivation', array($rec));
        }
    
        return $id;
    }
}
