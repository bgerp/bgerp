<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа sales_Invoices
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see acc_TransactionSourceIntf
 *
 */
class sales_transaction_Invoice extends acc_DocumentTransactionSource
{
    /**
     *
     * @var sales_Invoices
     */
    public $class;
    
    
    /**
     *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
     *  Създава транзакция която се записва в Журнала, при контирането
     *
     *  Dt: 411  - ДДС за начисляване
     *  Ct: 4532 - Начислен ДДС за продажбите
     */
    public function getTransaction($id)
    {
        // Извличаме записа
        expect($rec = $this->class->fetchRec($id));
        $cloneRec = clone $rec;

        $result = (object) array(
            'reason' => "Фактура №{$rec->id}", // основанието за ордера
            'valior' => $rec->date,   // датата на ордера
            'entries' => array(),
        );

        if($rec->type != 'dc_note'){
            $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
            if($receiptId = pos_Receipts::fetchField("#transferredIn = {$firstDoc->that}")) {
                if(pos_Reports::getReportReceiptIsIn($receiptId)){

                    // Ако ф-та е към договор към приключена пос бележка с отчет да не прави контировка
                    return $result;
                }
            }
        }

        if (Mode::get('saveTransaction')) {
            $productArr = array();
            $error = null;
            if (!$this->class->isAllowedToBePosted($rec, $error, true)) {
                acc_journal_RejectRedirect::expect(false, $error);
            }

            $Detail = cls::get('sales_InvoiceDetails');
            $exportParamId = acc_Setup::get('INVOICE_MANDATORY_EXPORT_PARAM');
            $productsWithoutExportParam = array();
            $onlyZeroQuantities = true;
            $dQuery = $Detail->getQuery();
            $dQuery->where("#invoiceId = {$rec->id}");
            $dRecs = $dQuery->fetchAll();
            if($rec->type != 'invoice'){
                $Detail::modifyDcDetails($dRecs, $rec, $Detail);
            }

            // Проверяват се всички артитули имат ли го зададен
            foreach ($dRecs as $dRec) {
                if($rec->type != 'invoice'){
                    if ($dRec->changedQuantity !== true && $dRec->changedPrice !== true) continue;
                }

                $productArr[$dRec->productId] = $dRec->productId;
                if ($exportParamId) {
                    if (!cat_Products::getParams($dRec->productId, $exportParamId)) {
                        $productsWithoutExportParam[$dRec->productId] = cat_Products::getTitleById($dRec->productId);
                    }
                }
                
                if (!empty($dRec->quantity)) {
                    $onlyZeroQuantities = false;
                }
            }
            
            // Ако има такива контирането се спира
            if (countR($productsWithoutExportParam)) {
                $param = cat_Params::getTitleById($exportParamId);
                $productsWithoutExportParam = implode(',', $productsWithoutExportParam);
                $error = "Следните артикули нямат задължителен параметър|* <b>{$param}</b>: {$productsWithoutExportParam}";
                acc_journal_RejectRedirect::expect(false, $error);
            }
            
            if ($rec->type == 'invoice' && $onlyZeroQuantities === true && empty($rec->dpAmount)) {
                acc_journal_RejectRedirect::expect(false, 'Трябва да има поне един ред с ненулево количество|*!');
            }
            
            // Проверка дали артикулите отговарят на нужните свойства
            if (countR($productArr)) {
                if($redirectError = deals_Helper::getContoRedirectError($productArr, 'canSell', 'generic', 'вече не са продаваеми или са генерични')){
                    
                    acc_journal_RejectRedirect::expect(false, $redirectError);
                }
            }
        }
        
        $origin = $this->class->getOrigin($rec);
        if (Mode::get('saveTransaction')) {
            if ($rec->type != 'invoice' && empty($rec->changeAmount)) {
                $this->class->updateMaster_($cloneRec, false);
                if (round($rec->dealValue, 4) != round($cloneRec->dealValue, 4) || round($rec->vatAmount, 4) != round($cloneRec->vatAmount, 4) || round($rec->discountAmount, 4) != round($cloneRec->discountAmount, 4)) {
                    wp('Оправяне на грешна сума във фактура', $rec, $cloneRec);
                    $rec->dealValue = $cloneRec->dealValue;
                    $rec->vatAmount = $cloneRec->vatAmount;
                    $rec->discountAmount = $cloneRec->discountAmount;
                    $this->class->save_($rec, 'dealValue,vatAmount,discountAmount');
                }
            }
        }

        // Ако е ДИ или КИ се посочва към коя фактура е то
        if ($rec->type != 'invoice') {
            $origin = $this->class->getOrigin($rec);
            
            $type = ($rec->dealValue > 0) ? 'Дебитно известие' : 'Кредитно известие';
            if (!$origin) {
                
                return $result;
            }
            $result->reason = "{$type} към фактура №" . $origin->getVerbal('number');
            
            // Намираме оридиджана на фактурата върху която е ДИ или КИ
            $origin = $origin->getOrigin();
            
            // Ако е Ди или Ки без промяна не може да се контира
            if (Mode::get('saveTransaction')) {
                if (!$rec->dealValue) {
                    acc_journal_RejectRedirect::expect(false, 'Дебитното/кредитното известие не може да бъде контирано, докато сумата е нула');
                }
            }
        }
        
        // Ако фактурата е от пос продажба не се контира ддс
        if ($cloneRec->type == 'invoice' && isset($cloneRec->docType, $cloneRec->docId)) {
            
            return $result;
        }
        
        $entries = array();
        
        if (isset($cloneRec->vatAmount)) {
            $entries[] = array(
                'amount' => $cloneRec->vatAmount * (($rec->type == 'credit_note') ? -1 : 1),  // равностойноста на сумата в основната валута
                'debit' => array('4530', array($origin->className, $origin->that)),
                'credit' => array('4532'),
            );
        }
        
        // Проверка на артикулите
        $productCheck = deals_Helper::checkProductForErrors($productArr, 'canSell');
            
        if(countR($productCheck['notActive'])){
             acc_journal_RejectRedirect::expect(false, "Артикулите|*: " . implode(', ', $productCheck['notActive']) . " |не са активни|*!");
        } elseif($productCheck['metasError']){
             acc_journal_RejectRedirect::expect(false, "Артикулите|*: " . implode(', ', $productCheck['metasError']) . " |трябва да са продаваеми|*!");
        }
        
        $result->entries = $entries;
        
        return $result;
    }
}
