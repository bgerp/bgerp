<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа purchase_Invoices
 *
 * @category  bgerp
 * @package   purchase
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see acc_TransactionSourceIntf
 *
 */
class purchase_transaction_Invoice extends acc_DocumentTransactionSource
{
    /**
     *
     * @var purchase_Invoices
     */
    public $class;
    
    
    /**
     *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
     *  Създава транзакция която се записва в Журнала, при контирането
     *
     *  Dt: 4531 - Начислен ДДС за покупките
     *  Ct: 401  - Задължения към доставчици
     */
    public function getTransaction($id)
    {
        // Извличаме записа
        expect($rec = $this->class->fetchRec($id));
        $cloneRec = clone $rec;
        setIfNot($rec->journalDate, $this->class->getDefaultAccDate($rec->date));
       
        $result = (object) array(
            'reason' => "Входяща фактура №{$rec->number}", // основанието за ордера
            'valior' => $rec->journalDate,   // датата на ордера
            'entries' => array(),
        );
        
        if (acc_Journal::throwErrorsIfFoundWhenTryingToPost()) {
            if (empty($rec->number)) {
                if ($rec->type == 'dc_note') {
                    $name = ($rec->dealValue <= 0) ? 'Кредитното известие' : 'Дебитното известие';
                } else {
                    $name = 'Фактурата';
                }
                
                acc_journal_RejectRedirect::expect(false, "{$name} няма номер");
            }
        }
        
        $origin = $this->class->getOrigin($rec);
        if (Mode::get('saveTransaction')) {
            if ($rec->type != 'invoice' && empty($rec->changeAmount)) {
                $this->class->updateMaster_($cloneRec, false);
                if(round($rec->dealValue, 4) != round($cloneRec->dealValue, 4) || round($rec->vatAmount, 4) != round($cloneRec->vatAmount, 4) || round($rec->discountAmount, 4) != round($cloneRec->discountAmount, 4)){
                    wp('Оправяне на грешна сума във входяща фактура', $rec, $cloneRec);
                    $rec->dealValue = $cloneRec->dealValue;
                    $rec->vatAmount = $cloneRec->vatAmount;
                    $rec->discountAmount = $cloneRec->discountAmount;
                    $this->class->save_($rec, 'dealValue,vatAmount,discountAmount');
                }
            }
        }

        // Ако е ДИ или КИ се посочва към коя фактура е то
        if ($rec->type != 'invoice') {
            $type = ($rec->dealValue > 0) ? 'Дебитно известие' : 'Кредитно известие';
            $result->reason = "{$type} към фактура №" . $origin->getVerbal('number');
            
            // Намираме оридиджана на фактурата върху която е ДИ или КИ
            $origin = $origin->getOrigin();
            
            // Ако е Ди или Ки без промяна не може да се контира
            if (acc_Journal::throwErrorsIfFoundWhenTryingToPost()) {
                if (!$rec->dealValue) {
                    acc_journal_RejectRedirect::expect(false, 'Дебитното/кредитното известие не може да бъде контирано, докато сумата е нула');
                }
            }
        } else {
            if (acc_Journal::throwErrorsIfFoundWhenTryingToPost()) {
                $noZeroQuantity = purchase_InvoiceDetails::fetch("#invoiceId = {$rec->id} AND (#quantity IS NOT NULL && #quantity != '' && #quantity != 0)");
                if (empty($noZeroQuantity) && empty($rec->dpAmount)) {
                    acc_journal_RejectRedirect::expect(false, 'Трябва да има поне един ред с ненулево количество|*!');
                }
            }
        }
        
        if ($origin->isInstanceOf('findeals_AdvanceReports')) {
            $origin = $origin->getOrigin();
        }
        
        $entries = array();
        
        if (isset($cloneRec->vatAmount)) {
            $entries[] = array(
                'amount' => $cloneRec->vatAmount * (($rec->type == 'credit_note') ? -1 : 1),  // равностойноста на сумата в основната валута
                'debit' => array('4531'),
                'credit' => array('4530', array($origin->className, $origin->that)),
            );
        }
        
        if (Mode::get('saveTransaction')) {
            $productArr = array();
            $Detail = cls::get('purchase_InvoiceDetails');
            $dQuery = $Detail->getQuery();
            $dQuery->where("#invoiceId = {$rec->id}");
            $dRecs = $dQuery->fetchAll();

            if($rec->type != 'invoice'){
                $Detail::modifyDcDetails($dRecs, $rec, $Detail);
            }

            foreach ($dRecs as $dRec) {
                if($rec->type != 'invoice'){
                    if ($dRec->changedQuantity !== true && $dRec->changedPrice !== true) continue;
                }
                $productArr[$dRec->productId] = $dRec->productId;
            }
            
            // Проверка дали артикулите отговарят на нужните свойства
            if (acc_Journal::throwErrorsIfFoundWhenTryingToPost() && countR($productArr)) {
                if($redirectError = deals_Helper::getContoRedirectError($productArr, 'canBuy', 'generic', 'трябва да са купуваеми и да не са генерични')){
                    
                    acc_journal_RejectRedirect::expect(false, $redirectError);
                }
            }
        }
        
        $result->entries = $entries;
        
        return $result;
    }
}
